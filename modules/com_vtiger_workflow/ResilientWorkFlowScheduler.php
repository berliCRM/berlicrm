<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * All Rights Reserved.
 ************************************************************************************/

require_once 'modules/com_vtiger_workflow/WorkFlowScheduler.php';

/**
 * Executes scheduled workflows with an error boundary around every workflow.
 */
class ResilientWorkFlowScheduler extends WorkFlowScheduler {

	/** @var PearDatabase */
	protected $database;

	/** @var Users */
	protected $workflowUser;

	/**
	 * Initializes the scheduler dependencies.
	 *
	 * @param PearDatabase $database Database connection.
	 */
	public function __construct($database) {
		parent::__construct($database);
		$this->database = $database;
		$util = new VTWorkflowUtils();
		$this->workflowUser = $util->adminUser();
	}

	/**
	 * Queues all due workflows and continues after a workflow-specific failure.
	 *
	 * @return array<int,array<string,mixed>> Collected workflow error details.
	 */
	public function queueScheduledWorkflowTasksResilient() {
		global $default_timezone;

		$workflowManager = new VTWorkflowManager($this->database);
		$taskQueue = new VTTaskQueue($this->database);
		$entityCache = new VTEntityCache($this->workflowUser);
		$errors = array();

		$admin = Users::getActiveAdminUser();
		$adminTimeZone = $admin->time_zone;
		@date_default_timezone_set($adminTimeZone);
		$currentTimestamp = date('Y-m-d H:i:s');
		@date_default_timezone_set($default_timezone);

		$scheduledWorkflowResult = $this->database->pquery(
			'SELECT workflow_id, summary, module_name FROM com_vtiger_workflows '
			.'WHERE execution_condition = ? '
			.'AND (nexttrigger_time = ? OR nexttrigger_time IS NULL OR nexttrigger_time <= ?)',
			array(VTWorkflowManager::$ON_SCHEDULE, '', $currentTimestamp)
		);
		$scheduledWorkflowCount = $this->database->num_rows($scheduledWorkflowResult);
		for ($workflowIndex = 0; $workflowIndex < $scheduledWorkflowCount; ++$workflowIndex) {
			$workflowId = (int)$this->database->query_result($scheduledWorkflowResult, $workflowIndex, 'workflow_id');
			$workflowContext = new stdClass();
			$workflowContext->id = $workflowId;
			$workflowContext->description = $this->database->query_result($scheduledWorkflowResult, $workflowIndex, 'summary');
			$workflowContext->moduleName = $this->database->query_result($scheduledWorkflowResult, $workflowIndex, 'module_name');
			$workflow = null;
			$workflowCompleted = false;

			try {
				$workflow = $workflowManager->retrieve($workflowId);
				if (!$workflow) {
					throw new RuntimeException('The scheduled workflow no longer exists.');
				}
				$workflowContext = $workflow;
				$this->queueWorkflowTasks($workflow, $taskQueue, $entityCache, $admin);
				$workflowCompleted = true;
			} catch (Throwable $exception) {
				$errors[] = $this->createErrorDetails($workflowContext, $exception, 'scheduled_workflow');
			}

			if ($workflowCompleted) {
				try {
					$workflowManager->updateNexTriggerTime($workflow);
				} catch (Throwable $exception) {
					$errors[] = $this->createErrorDetails($workflowContext, $exception, 'next_trigger_time');
				}
			}
		}

		return $errors;
	}

	/**
	 * Queues or immediately executes the active tasks of one scheduled workflow.
	 *
	 * @param Workflow $workflow Workflow being processed.
	 * @param VTTaskQueue $taskQueue Delayed task queue.
	 * @param VTEntityCache $entityCache Workflow entity cache.
	 * @param Users $admin Active administrator used for notifications.
	 * @return void
	 */
	protected function queueWorkflowTasks($workflow, $taskQueue, $entityCache, $admin) {
		$taskManager = new VTTaskManager($this->database);
		$tasks = $taskManager->getTasksForWorkflow($workflow->id);
		if (!$tasks) {
			return;
		}

		$records = $this->getEligibleWorkflowRecords($workflow);
		$recordCount = count($records);
		$maximumRecords = 5000;
		if ($recordCount > $maximumRecords) {
			$this->sendRecordLimitNotification($workflow, $recordCount, $maximumRecords, $admin);
			$recordCount = $maximumRecords;
		}

		for ($recordIndex = 0; $recordIndex < $recordCount; ++$recordIndex) {
			$recordId = $records[$recordIndex];
			$moduleName = ($workflow->moduleName === 'Calendar')
				? vtws_getCalendarEntityType($recordId)
				: $workflow->moduleName;
			$webserviceEntityId = vtws_getWebserviceEntityId($moduleName, $recordId);
			$entityData = $entityCache->forId($webserviceEntityId);
			$data = $entityData->getData();
			$hasActiveTask = false;

			foreach ($tasks as $task) {
				if (!$task->active) {
					continue;
				}
				$hasActiveTask = true;
				$delay = 0;
				$trigger = $task->trigger;
				if ($trigger !== null) {
					$delay = strtotime($data[$trigger['field']]) + $trigger['days'] * 86400;
				}
				if ($task->executeImmediately == true) {
					$task->doTask($entityData, true);
				} else {
					$taskQueue->queueTask($task->id, $entityData->getId(), $delay);
				}
			}

			set_time_limit(0);
			if (!$hasActiveTask) {
				break;
			}
		}
	}

	/**
	 * Sends the existing warning when a scheduled workflow exceeds the record limit.
	 *
	 * @param Workflow $workflow Workflow exceeding the limit.
	 * @param int $recordCount Number of matching records.
	 * @param int $maximumRecords Maximum number processed in one run.
	 * @param Users $admin Recipient administrator.
	 * @return void
	 */
	protected function sendRecordLimitNotification($workflow, $recordCount, $maximumRecords, $admin) {
		global $site_URL, $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID;

		$subject = vtranslate('LBL_WORKFLOW_TOO_MANY_SUBJECT');
		$content = vtranslate('LBL_WORKFLOW_TOO_MANY_CONTENT');
		$workflowUrl = $site_URL.'index.php?module=Workflows&parent=Settings&view=Edit&record='.$workflow->id;
		$content = sprintf($content, $workflowUrl, $recordCount, $maximumRecords, $maximumRecords);
		if (!empty($admin->email1)) {
			send_mail(
				'com_vtiger_workflow',
				$admin->email1,
				$HELPDESK_SUPPORT_NAME,
				$HELPDESK_SUPPORT_EMAIL_ID,
				$subject,
				$content
			);
		}
	}

	/**
	 * Converts a workflow exception into serializable diagnostic information.
	 *
	 * @param Workflow|stdClass $workflow Failed workflow context.
	 * @param Throwable $exception Caught error or exception.
	 * @param string $phase Processing phase in which the error occurred.
	 * @return array<string,mixed> Error details for logging and email notification.
	 */
	protected function createErrorDetails($workflow, Throwable $exception, $phase) {
		return array(
			'phase' => $phase,
			'workflow_id' => isset($workflow->id) ? (int)$workflow->id : null,
			'workflow_name' => isset($workflow->description) ? decode_html($workflow->description) : '',
			'module' => isset($workflow->moduleName) ? $workflow->moduleName : '',
			'task_id' => null,
			'task_name' => '',
			'entity_id' => '',
			'exception' => get_class($exception),
			'message' => $exception->getMessage(),
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
		);
	}
}
