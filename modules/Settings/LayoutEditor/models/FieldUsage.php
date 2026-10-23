<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Finds configuration records which still depend on a field.
 *
 * A field referenced by a workflow, report, custom list view or registered cron task
 * must not be hidden or deleted. This model deliberately checks the stored values in
 * PHP after using a coarse LIKE filter. That prevents similarly named fields (for
 * example cf_123 and cf_1234) from being treated as the same field.
 */
class Settings_LayoutEditor_FieldUsage_Model extends Vtiger_Base_Model {

	/** @var Vtiger_Field_Model */
	protected $field;

	/** @var array|null */
	protected $usages;

	/**
	 * Creates a usage checker for the supplied field.
	 *
	 * @param Vtiger_Field_Model $field
	 * @return self
	 */
	public static function getInstance(Vtiger_Field_Model $field) {
		$instance = new self();
		$instance->field = $field;
		return $instance;
	}

	/**
	 * Determines whether at least one supported configuration uses the field.
	 *
	 * @return bool
	 */
	public function isUsed() {
		return count($this->getUsages()) > 0;
	}

	/**
	 * Returns all workflows, reports, list views and cron tasks which reference the field.
	 * Results are cached for the lifetime of this model instance.
	 *
	 * @return array
	 */
	public function getUsages() {
		if ($this->usages === null) {
			$this->usages = array_merge(
				$this->findWorkflowUsages(),
				$this->findReportUsages(),
				$this->findListViewUsages(),
				$this->findCronUsages()
			);
		}

		return $this->usages;
	}

	/**
	 * Returns an HTML message because the Layout Editor displays action errors in a
	 * PNotify message. All database values are escaped before being included.
	 *
	 * @param string $operation delete|deactivate
	 * @return string
	 */
	public function getBlockedMessage($operation) {
		$languageModule = 'Settings:LayoutEditor';
		$messageKey = ($operation === 'delete')
			? 'LBL_FIELD_USAGE_CANNOT_DELETE'
			: 'LBL_FIELD_USAGE_CANNOT_DEACTIVATE';
		$fieldLabel = $this->field->get('label');
		if (!$fieldLabel) {
			$fieldLabel = $this->field->get('fieldlabel');
		}

		$message = sprintf(vtranslate($messageKey, $languageModule), $this->escape($fieldLabel)).'<ul>';
		foreach ($this->getUsages() as $usage) {
			$message .= '<li><a href="'.$this->escape($usage['url']).'" target="_blank" rel="noopener noreferrer">'
				.$this->escape(vtranslate($usage['type'], $languageModule).': '.$usage['name']).'</a></li>';
		}
		return $message.'</ul>';
	}

	/**
	 * Finds workflows which use the field in conditions or serialized tasks.
	 *
	 * @return array
	 */
	protected function findWorkflowUsages() {
		$db = PearDatabase::getInstance();
		$moduleName = $this->field->getModuleName();
		$fieldName = $this->field->getName();
		$columnName = $this->field->get('column');

		$result = $db->pquery(
			'SELECT workflow.workflow_id, workflow.summary, workflow.module_name, workflow.test, task.task '
			.'FROM com_vtiger_workflows workflow '
			.'LEFT JOIN com_vtiger_workflowtasks task ON task.workflow_id = workflow.workflow_id',
			array()
		);

		$matches = array();
		for ($i = 0; $i < $db->num_rows($result); ++$i) {
			$workflowId = (int) $db->query_result($result, $i, 'workflow_id');
			$condition = (string) $db->query_result($result, $i, 'test');
			$task = (string) $db->query_result($result, $i, 'task');
			$workflowModule = (string) $db->query_result($result, $i, 'module_name');
			$referencesField = $this->containsIdentifier($condition, $fieldName)
				|| $this->containsIdentifier($task, $fieldName)
				|| ($columnName !== $fieldName && ($this->containsIdentifier($condition, $columnName)
					|| $this->containsIdentifier($task, $columnName)));
			if (!$referencesField) {
				continue;
			}

			// Custom field names are globally unique. For standard fields in a workflow
			// of another module, also require the related module name to avoid matching
			// common names such as "description" in an unrelated workflow.
			$isCustomFieldName = preg_match('/^cf_[0-9]+$/', $fieldName) === 1;
			if ($workflowModule !== $moduleName && !$isCustomFieldName
				&& !$this->containsIdentifier($condition, $moduleName)
				&& !$this->containsIdentifier($task, $moduleName)) {
				continue;
			}

			$matches[$workflowId] = array(
				'type' => 'LBL_FIELD_USAGE_TYPE_WORKFLOW',
				'id' => $workflowId,
				'name' => $db->query_result($result, $i, 'summary'),
				'url' => 'index.php?module=Workflows&parent=Settings&view=Edit&record='.$workflowId,
			);
		}

		return array_values($matches);
	}

	/**
	 * Finds reports which use the field as a selected, filtered, sorted, grouped,
	 * date-filtered or summary column.
	 *
	 * @return array
	 */
	protected function findReportUsages() {
		$db = PearDatabase::getInstance();
		$reportIds = array();
		$tableName = $this->field->get('table');
		if (!$tableName) {
			$tableName = $this->field->get('tablename');
		}
		$columnName = $this->field->get('column');
		$reference = $tableName.':'.$columnName.':';

		$locations = array(
			array('table' => 'vtiger_selectcolumn', 'id' => 'queryid', 'column' => 'columnname'),
			array('table' => 'vtiger_relcriteria', 'id' => 'queryid', 'column' => 'columnname'),
			array('table' => 'vtiger_reportsortcol', 'id' => 'reportid', 'column' => 'columnname'),
			array('table' => 'vtiger_reportdatefilter', 'id' => 'datefilterid', 'column' => 'datecolumnname'),
			array('table' => 'vtiger_reportsummary', 'id' => 'reportsummaryid', 'column' => 'columnname'),
			array('table' => 'vtiger_reportgroupbycolumn', 'id' => 'reportid', 'column' => 'sortcolname'),
		);

		foreach ($locations as $location) {
			$query = 'SELECT '.$location['id'].', '.$location['column'].' FROM '.$location['table']
				.' WHERE '.$location['column'].' LIKE ?';
			$result = $db->pquery($query, array('%'.$columnName.'%'));
			for ($i = 0; $i < $db->num_rows($result); ++$i) {
				$value = (string) $db->query_result($result, $i, $location['column']);
				if (strpos($value, $reference) !== false) {
					$reportIds[(int) $db->query_result($result, $i, $location['id'])] = true;
				}
			}
		}

		if (!$reportIds) {
			return array();
		}

		$ids = array_keys($reportIds);
		$result = $db->pquery(
			'SELECT reportid, reportname, reporttype FROM vtiger_report WHERE reportid IN ('.generateQuestionMarks($ids).')',
			$ids
		);
		$matches = array();
		for ($i = 0; $i < $db->num_rows($result); ++$i) {
			$reportId = (int) $db->query_result($result, $i, 'reportid');
			$viewName = ($db->query_result($result, $i, 'reporttype') === 'chart') ? 'ChartDetail' : 'Detail';
			$matches[] = array(
				'type' => 'LBL_FIELD_USAGE_TYPE_REPORT',
				'id' => $reportId,
				'name' => $db->query_result($result, $i, 'reportname'),
				'url' => 'index.php?module=Reports&view='.$viewName.'&record='.$reportId,
			);
		}

		return $matches;
	}

	/**
	 * Finds custom list views which display or filter by the field.
	 *
	 * @return array
	 */
	protected function findListViewUsages() {
		$db = PearDatabase::getInstance();
		$viewIds = array();
		$tableName = $this->field->get('table');
		if (!$tableName) {
			$tableName = $this->field->get('tablename');
		}
		$columnName = $this->field->get('column');
		$reference = $tableName.':'.$columnName.':';

		foreach (array('vtiger_cvcolumnlist', 'vtiger_cvadvfilter', 'vtiger_cvstdfilter') as $table) {
			$result = $db->pquery(
				'SELECT cvid, columnname FROM '.$table.' WHERE columnname LIKE ?',
				array('%'.$columnName.'%')
			);
			for ($i = 0; $i < $db->num_rows($result); ++$i) {
				if (strpos((string) $db->query_result($result, $i, 'columnname'), $reference) !== false) {
					$viewIds[(int) $db->query_result($result, $i, 'cvid')] = true;
				}
			}
		}

		if (!$viewIds) {
			return array();
		}

		$ids = array_keys($viewIds);
		$result = $db->pquery(
			'SELECT cvid, viewname, entitytype FROM vtiger_customview WHERE cvid IN ('.generateQuestionMarks($ids).')',
			$ids
		);
		$matches = array();
		for ($i = 0; $i < $db->num_rows($result); ++$i) {
			$viewId = (int) $db->query_result($result, $i, 'cvid');
			$moduleName = $db->query_result($result, $i, 'entitytype');
			$matches[] = array(
				'type' => 'LBL_FIELD_USAGE_TYPE_LIST_VIEW',
				'id' => $viewId,
				'name' => $db->query_result($result, $i, 'viewname'),
				'url' => 'index.php?module='.rawurlencode($moduleName).'&view=List&viewname='.$viewId,
			);
		}

		return $matches;
	}

	/**
	 * Finds registered cron tasks whose PHP handler references the field. Comments are
	 * ignored so that documentation mentioning an old field does not block changes.
	 * Handler paths are resolved inside the CRM root before their content is read.
	 *
	 * @return array
	 */
	protected function findCronUsages() {
		$db = PearDatabase::getInstance();
		$fieldName = $this->field->getName();
		$columnName = $this->field->get('column');
		$rootDirectory = function_exists('vglobal') ? vglobal('root_directory') : '';
		if (!$rootDirectory) {
			$rootDirectory = dirname(dirname(dirname(dirname(__DIR__))));
		}
		$rootDirectory = realpath($rootDirectory);
		if ($rootDirectory === false) {
			return array();
		}

		$result = $db->pquery(
			'SELECT id, name, handler_file FROM vtiger_cron_task WHERE handler_file IS NOT NULL AND handler_file != ?',
			array('')
		);
		$matches = array();
		for ($i = 0; $i < $db->num_rows($result); ++$i) {
			$handlerFile = trim((string) $db->query_result($result, $i, 'handler_file'));
			$handlerPath = $this->resolveCronHandlerPath($rootDirectory, $handlerFile);
			if ($handlerPath === false) {
				continue;
			}

			$source = file_get_contents($handlerPath);
			if ($source === false) {
				continue;
			}
			$source = $this->removePhpComments($source);
			if (!$this->containsIdentifier($source, $fieldName)
				&& ($columnName === $fieldName || !$this->containsIdentifier($source, $columnName))) {
				continue;
			}

			$cronId = (int) $db->query_result($result, $i, 'id');
			$cronName = $db->query_result($result, $i, 'name');
			$matches[$cronId] = array(
				'type' => 'LBL_FIELD_USAGE_TYPE_CRON',
				'id' => $cronId,
				'name' => $cronName.' ('.$handlerFile.')',
				'url' => 'index.php?module=CronTasks&parent=Settings&view=List',
			);
		}

		return array_values($matches);
	}

	/**
	 * Resolves a registered cron handler and rejects missing files as well as paths
	 * outside the CRM installation directory.
	 *
	 * @param string $rootDirectory Absolute CRM root directory.
	 * @param string $handlerFile Handler path stored in vtiger_cron_task.
	 * @return string|false Absolute handler path or false when it is not readable.
	 */
	protected function resolveCronHandlerPath($rootDirectory, $handlerFile) {
		if ($handlerFile === '') {
			return false;
		}
		$relativeHandler = ltrim(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $handlerFile), DIRECTORY_SEPARATOR);
		$handlerPath = realpath($rootDirectory.DIRECTORY_SEPARATOR.$relativeHandler);
		$rootPrefix = rtrim($rootDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		if ($handlerPath === false || !is_file($handlerPath)
			|| strncmp($handlerPath, $rootPrefix, strlen($rootPrefix)) !== 0) {
			return false;
		}

		return $handlerPath;
	}

	/**
	 * Removes PHP comments while preserving executable code and string literals.
	 * Field names inside SQL strings or array keys therefore remain searchable.
	 *
	 * @param string $source PHP source code.
	 * @return string Source code without comments.
	 */
	protected function removePhpComments($source) {
		$code = '';
		foreach (token_get_all($source) as $token) {
			if (is_array($token)) {
				if ($token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT) {
					$code .= $token[1];
				}
			} else {
				$code .= $token;
			}
		}
		return $code;
	}

	/**
	 * Checks identifiers with boundaries so cf_123 does not match cf_1234.
	 *
	 * @param string $value
	 * @param string $identifier
	 * @return bool
	 */
	protected function containsIdentifier($value, $identifier) {
		if ($value === '' || $identifier === '') {
			return false;
		}
		return preg_match('/(?<![A-Za-z0-9_])'.preg_quote($identifier, '/').'(?![A-Za-z0-9_])/', $value) === 1;
	}

	/**
	 * Escapes a database value for safe inclusion in the HTML error message.
	 *
	 * @param string $value
	 * @return string
	 */
	protected function escape($value) {
		if (function_exists('decode_html')) {
			$value = decode_html($value);
		}
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}
}
