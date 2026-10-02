<?php
/*+*******************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ********************************************************************************/

/**
 * Runs the configured cron services and reports collected execution errors.
 *
 * Defines the resilient scheduler used by the workflow cron service. Each started
 * cron task has its running status reset in a finally block, and execution errors
 * are collected for a notification after the remaining services have run.
 */
include_once 'vtlib/Vtiger/Cron.php';
require_once 'config.inc.php';
require_once('modules/Emails/mail.php');
require_once('modules/Users/Users.php');
require_once('includes/runtime/BaseModel.php');
require_once('includes/runtime/Globals.php');
require_once('includes/runtime/LanguageHandler.php');

if (file_exists('config_override.php')) {
    include_once 'config_override.php';
}

// Extended inclusions
require_once 'includes/Loader.php';
vimport('includes.runtime.EntryPoint');
require_once 'modules/Settings/CronTasks/models/Config.php';

require_once 'modules/com_vtiger_workflow/WorkFlowScheduler.php';

/**
 * Executes scheduled workflows with an error boundary around every workflow.
 *
 * Defined in the cron entry point for use by com_vtiger_workflow.service.
 * Workflow failures are returned as diagnostic data so later workflows can run
 * and the service can include all collected errors in the cron notification.
 */
class ResilientWorkFlowScheduler extends WorkFlowScheduler {

	/** @var PearDatabase Database connection used to retrieve workflows and tasks. */
	protected $database;

	/** @var Users Administrator used to load workflow entities. */
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
	 * Due times are evaluated in the active administrator's time zone. A workflow's
	 * next trigger advances only after its tasks have been processed successfully.
	 * Failed workflows remain due; tasks already processed before a failure may be
	 * processed again on a later run.
	 *
	 * @see ResilientWorkFlowScheduler::createErrorDetails()
	 * @return array<int,array<string,mixed>> Workflow and trigger update errors; empty on success.
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
	 * Processes at most 5000 matching records and notifies the administrator when
	 * that limit is exceeded. Inactive tasks are skipped. Task failures propagate
	 * to the caller's workflow error boundary and stop processing this workflow.
	 *
	 * @param Workflow $workflow Workflow being processed.
	 * @param VTTaskQueue $taskQueue Delayed task queue.
	 * @param VTEntityCache $entityCache Workflow entity cache.
	 * @param Users $admin Active administrator used for notifications.
	 * @return void
	 * @throws Throwable If record retrieval, notification or task processing fails.
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
	 * @throws Throwable If notification preparation or delivery throws an error.
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
	 * @param string $phase Either scheduled_workflow or next_trigger_time.
	 * @return array{
	 *     phase: string,
	 *     workflow_id: int|null,
	 *     workflow_name: string,
	 *     module: string,
	 *     task_id: null,
	 *     task_name: string,
	 *     entity_id: string,
	 *     exception: string,
	 *     message: string,
	 *     file: string,
	 *     line: int
	 * } Error details compatible with the workflow cron notification formatter.
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

/**
 * Returns the primary email address of a CRM user.
 *
 * @param Users $user CRM user record.
 * @return string Email address or an empty string.
 */
function vtigerCronGetUserEmail($user) {
    if (!empty($user->email1)) {
        return trim((string)$user->email1);
    }
    if (!empty($user->column_fields['email1'])) {
        return trim((string)$user->column_fields['email1']);
    }

    return '';
}

/**
 * Splits a configuration value containing one or more email addresses.
 *
 * @param string $value Comma- or semicolon-separated email addresses.
 * @return array<int,string> Individual recipient values.
 */
function vtigerCronSplitRecipients($value) {
    return preg_split('/[,;]+/', (string)$value, -1, PREG_SPLIT_NO_EMPTY);
}

/**
 * Builds a validated, case-insensitively deduplicated notification list.
 *
 * @param Users $adminUser Active administrator who is always notified.
 * @param array<int,string> $configuredRecipients Additional configured recipients.
 * @return array<int,string> Valid email recipients.
 */
function vtigerCronGetNotificationRecipients($adminUser, array $configuredRecipients) {
    $recipients = array_merge(array(vtigerCronGetUserEmail($adminUser)), $configuredRecipients);
    $uniqueRecipients = array();
    foreach ($recipients as $recipient) {
        $recipient = trim((string)$recipient);
        if ($recipient !== '' && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $uniqueRecipients[strtolower($recipient)] = $recipient;
        }
    }

    return array_values($uniqueRecipients);
}

/**
 * Sends a cron notification to all configured recipients without aborting the cron.
 *
 * @param array<int,string> $recipients Email recipients.
 * @param string $senderName Sender display name.
 * @param string $senderEmail Sender email address.
 * @param string $subject Email subject.
 * @param string $content HTML email content.
 * @return void
 */
function vtigerCronSendNotification(array $recipients, $senderName, $senderEmail, $subject, $content) {
    if ($senderEmail === '') {
        echo "[ERROR] Cron notification could not be sent: sender email is not configured.\n";
        return;
    }

    foreach ($recipients as $recipient) {
        try {
            $mailStatus = send_mail('Settings', $recipient, $senderName, $senderEmail, $subject, $content, '', '', '', '', '', true);
            if ($mailStatus !== 1 && $mailStatus !== true) {
                echo sprintf("[ERROR] Cron notification to %s failed: %s\n", $recipient, (string)$mailStatus);
            }
        } catch (Throwable $mailError) {
            echo sprintf("[ERROR] Cron notification to %s failed: %s: %s\n", $recipient, get_class($mailError), $mailError->getMessage());
        }
    }
}

$version = explode('.', phpversion());

$php = ($version[0] * 10000 + $version[1] * 100 + $version[2]);
if ($php < 50300) {
    $hostName = php_uname('n');
} else {
    $hostName = gethostname();
}


if ((PHP_SAPI === "cgi-fcgi" && empty($_SESSION)) || empty($_SERVER['REMOTE_ADDR']) || (isset($_SESSION["authenticated_user_id"]) && isset($_SESSION["app_unique_key"]) && $_SESSION["app_unique_key"] == $application_unique_key)) {

    $cronTasks = false;
    //crm-now: removed dependency on $_REQUEST = always execute all crons
    $cronTasks = Vtiger_Cron::listAllActiveInstances();

    // echo PHP_EOL . print_r($cronTasks, true) . PHP_EOL;


    // 
    // with the parameter 'service' you can force run a specific cron task
    // even if the time to run again is not completed. Works only on cli not on web.
    //
    // samples: 
    // https://your_crm_system/vtigercron.php?service=your_cron_task_name
    // sudo -u crmuser env QUERY_STRING='service=your_cron_task_name' /path/to/php-cgi  /path/to/your_crm_system/vtigercron.php service="your_cron_task_name"
    // 
    $service = '';
    if (!empty($_REQUEST['service']) && empty($_SERVER['REMOTE_ADDR'])) {
        $service = vtlib_purify($_REQUEST['service']);
    }


    $cronRunId = microtime(true);
    $cronStarts = date('Y-m-d H:i:s');
	$pId = getmypid();

    //set global current user permissions
    global $current_user, $site_URL;
    $current_user = Users::getActiveAdminUser();
    $cronMailConfig = Settings_CronTasks_Config_Model::getInstance()->getData();
    $cronMailRecipientEmail = trim((string)$cronMailConfig['sender_email']);
    $cronMailNotificationEmails = vtigerCronSplitRecipients($cronMailConfig['notification_email']);
    $cronConfiguration = file_exists('config.cron.php') ? require 'config.cron.php' : array();
    if (!is_array($cronConfiguration)) {
        $cronConfiguration = array();
    }
    $cronConfiguredRecipients = isset($cronConfiguration['error_notification_recipients'])
        && is_array($cronConfiguration['error_notification_recipients'])
        ? $cronConfiguration['error_notification_recipients']
        : array();
    $cronMailRecipients = vtigerCronGetNotificationRecipients(
        $current_user,
        array_merge(array($cronMailRecipientEmail), $cronMailNotificationEmails, $cronConfiguredRecipients)
    );
    $cronAdminEmail = vtigerCronGetUserEmail($current_user);
    $cronMailSenderEmail = $cronMailRecipientEmail;
    $cronMailSenderName = trim((string)$cronMailConfig['sender_name']);
    if ($cronMailSenderEmail === '') {
        $cronMailSenderEmail = isset($HELPDESK_SUPPORT_EMAIL_ID)
            ? trim((string)$HELPDESK_SUPPORT_EMAIL_ID) : '';
        $cronMailSenderName = isset($HELPDESK_SUPPORT_NAME)
            ? trim((string)$HELPDESK_SUPPORT_NAME) : '';
    }
    if ($cronMailSenderEmail === '') {
        $cronMailSenderEmail = $cronAdminEmail;
        $cronMailSenderName = $current_user->user_name;
    }
    if ($cronMailSenderName === '') {
        $cronMailSenderName = $current_user->user_name;
    }

    echo sprintf('[CRON PID: %s],"%s",%s,Instance,"%s","",[STARTS]', $pId, $cronRunId, $site_URL, $cronStarts) . "\n";
    $cronExecutionErrors = array();
    foreach ($cronTasks as $cronTask) {
        if (!empty($service) && $cronTask->getName() != $service) {
            continue;
        }
        $cronTaskMustBeFinished = false;
        try {
            $cronTask->setBulkMode(true);

            // Not ready to run yet?
            if (!$cronTask->isRunnable() && empty($service)) {
                echo sprintf("[INFO] %s - not ready to run as the time to run again is not completed\n", $cronTask->getName());
                continue;
            }

            // already running?
            if ($cronTask->isRunning()) {
                // check if it timed out too long ago, if > 24h then reset and inform admin
                $lastStart = $cronTask->getLastStart();
                $now = time();
                if ($lastStart == 0 || $now - $lastStart > 86400) {
                    $cronTaskMustBeFinished = true;
                    $subject = sprintf(vtranslate('LBL_CRON_TIMEOUT_SUBJECT'), $cronTask->getName(), $site_URL);
                    $content = sprintf(vtranslate('LBL_CRON_TIMEOUT_CONTENT'), $site_URL, $cronTask->getName());
                    vtigerCronSendNotification($cronMailRecipients, $cronMailSenderName, $cronMailSenderEmail, $subject, $content);
                    echo sprintf("[INFO] %s - running for more than 24h, sent notification and restarted", $cronTask->getName());
                    // if time since last start < 24h just skip it
                } else {
                    echo sprintf("[INFO] %s - not ready to run because it is running already", $cronTask->getName());
                    continue;
                }
            }

            // Timeout could happen if intermediate cron-tasks fails
            // and affect the next task. Which need to be handled in this cycle.
            // doesn't work, 0 is returned from 'lastend' entry
            // if ($cronTask->hadTimedout()) {
            // echo sprintf("[INFO] %s - cron task had timedout as it is not completed last time it run- restarting\n", $cronTask->getName());
            // }

            $cronTaskMustBeFinished = true;
            // Mark the status - running
            $cronTask->markRunning();
            echo sprintf('[CRON PID: %s],"%s",%s,%s,"%s","",[STARTS]', $pId, $cronRunId, $site_URL, $cronTask->getName(), date('Y-m-d H:i:s', $cronTask->getLastStart())) . "\n";

            checkFileAccess($cronTask->getHandlerFile());
            require_once $cronTask->getHandlerFile();

        } catch (Throwable $e) {
            $cronExecutionErrors[] = array(
                'task' => $cronTask->getName(),
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            );
            echo sprintf("[ERROR] %s - cron task execution failed: %s: %s\n", $cronTask->getName(), get_class($e), $e->getMessage());
        } finally {
            if ($cronTaskMustBeFinished) {
                try {
                    $cronTask->markFinished();
                    echo "\n" . sprintf('[CRON PID: %s],"%s",%s,%s,"%s","%s",[ENDS]', $pId, $cronRunId, $site_URL, $cronTask->getName(), date('Y-m-d H:i:s', $cronTask->getLastStart()), date('Y-m-d H:i:s', $cronTask->getLastEnd())) . "\n";
                } catch (Throwable $finishError) {
                    $cronExecutionErrors[] = array(
                        'task' => $cronTask->getName(),
                        'type' => get_class($finishError),
                        'message' => 'Cron status could not be reset: '.$finishError->getMessage(),
                        'file' => $finishError->getFile(),
                        'line' => $finishError->getLine(),
                    );
                    echo sprintf("[ERROR] %s - cron status could not be reset: %s\n", $cronTask->getName(), $finishError->getMessage());
                }
            }
        }
    }

    if ($cronExecutionErrors) {
        $errorItems = '';
        foreach ($cronExecutionErrors as $cronExecutionError) {
            $errorLocation = $cronExecutionError['file'].':'.$cronExecutionError['line'];
            $errorItems .= '<li>'
                .'<strong>Cron task:</strong> '.htmlspecialchars($cronExecutionError['task'], ENT_QUOTES, 'UTF-8').'<br>'
                .'<strong>Exception:</strong> '.htmlspecialchars($cronExecutionError['type'], ENT_QUOTES, 'UTF-8').'<br>'
                .'<strong>Error:</strong><br>'.nl2br(htmlspecialchars($cronExecutionError['message'], ENT_QUOTES, 'UTF-8')).'<br>'
                .'<strong>Source:</strong> '.htmlspecialchars($errorLocation, ENT_QUOTES, 'UTF-8')
                .'</li>';
        }

        $subject = sprintf('[CRON-ERROR] %s - %d error(s)', $site_URL, count($cronExecutionErrors));
        $content = '<p>Errors occurred while running cron on <strong>'
            .htmlspecialchars($site_URL, ENT_QUOTES, 'UTF-8')
            .'</strong>. The running state was reset for each affected cron task where possible.</p>'
            .'<p><strong>Start:</strong> '.htmlspecialchars($cronStarts, ENT_QUOTES, 'UTF-8').'</p>'
            .'<ol>'.$errorItems.'</ol>';
        vtigerCronSendNotification($cronMailRecipients, $cronMailSenderName, $cronMailSenderEmail, $subject, $content);
    }

    $cronEnds = date('Y-m-d H:i:s');
    echo sprintf('[CRON PID: %s],"%s",%s,Instance,"%s","%s",[ENDS]', $pId, $cronRunId, $site_URL, $cronStarts, $cronEnds) . "\n";

} else {
    echo ("Access denied!");
}
