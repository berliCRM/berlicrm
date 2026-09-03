<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class ModTracker_Relation_Model extends Vtiger_Record_Model {
	protected static $linkedRecordCache = array();
	protected $linkedRecord = null;

	function setParent($parent) {
		$this->parent = $parent;
	}

	function getParent() {
		return $this->parent;
	}

	function getLinkedRecord() {
		if ($this->linkedRecord !== null) {
			return $this->linkedRecord;
		}
        $db = PearDatabase::getInstance();
        
		$targetId = $this->get('targetid');
		$targetModule = $this->get('targetmodule');
		$cacheKey = $targetModule . ':' . $targetId;
		if (array_key_exists($cacheKey, self::$linkedRecordCache)) {
			$this->linkedRecord = self::$linkedRecordCache[$cacheKey];
			return $this->linkedRecord;
		}
        
        $query = 'SELECT * FROM vtiger_crmentity WHERE crmid = ?';
		$params = array($targetId);
		$result = $db->pquery($query, $params);
		$noOfRows = $db->num_rows($result);
		$moduleModels = array();
		if($noOfRows) {
			if(!array_key_exists($targetModule, $moduleModels)) {
				$moduleModel = Vtiger_Module_Model::getInstance($targetModule);
			}
			$row = $db->query_result_rowdata($result, 0);
			$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'Record', $targetModule);
			$recordInstance = new $modelClassName();
			$recordInstance->setData($row)->setModuleFromInstance($moduleModel);
			$recordInstance->set('id', $row['crmid']);
			self::$linkedRecordCache[$cacheKey] = $recordInstance;
			$this->linkedRecord = $recordInstance;
			return $this->linkedRecord;
		}
		self::$linkedRecordCache[$cacheKey] = false;
		$this->linkedRecord = false;
		return $this->linkedRecord;
	}
}
