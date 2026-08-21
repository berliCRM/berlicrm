<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'include/events/VTEntityData.inc';

class VTEntityDelta extends VTEventHandler {
	private static $oldEntity;
	private static $newEntity;
	private static $entityDelta;

	function  __construct() {
		
	}

	function handleEvent($eventName, $entityData) {

		$adb = PearDatabase::getInstance();
		$moduleName = $entityData->getModuleName();
		$recordId = $entityData->getId();
		
		if($eventName == 'vtiger.entity.beforesave') {
			if(!empty($recordId)) {
				$entityData = VTEntityData::fromEntityId($adb, $recordId);
				if($moduleName == 'HelpDesk') {
					$entityData->set('comments', getTicketComments($recordId));
				} elseif($moduleName == 'Invoice'){
					$entityData->set('invoicestatus', getInvoiceStatus($recordId));
				}
				self::$oldEntity[$moduleName][$recordId] = $entityData;
			}
		}

		if($eventName == 'vtiger.entity.aftersave'){
			$this->fetchEntity($moduleName, $recordId);
			$this->computeDelta($moduleName, $recordId);
		}
	}

	function fetchEntity($moduleName, $recordId) {
		$adb = PearDatabase::getInstance();
		$entityData = VTEntityData::fromEntityId($adb, $recordId, $moduleName);
		if($moduleName == 'HelpDesk') {
			$entityData->set('comments', getTicketComments($recordId));
		} elseif($moduleName == 'Invoice') {
			$entityData->set('invoicestatus', getInvoiceStatus($recordId));
		}
		self::$newEntity[$moduleName][$recordId] = $entityData;
	}

	function computeDelta($moduleName, $recordId) {

		$delta = array();

		$oldData = array();
		if(!empty(self::$oldEntity[$moduleName][$recordId])) {
			$oldEntity = self::$oldEntity[$moduleName][$recordId];
			$oldData = $oldEntity->getData();
		}
		$newEntity = self::$newEntity[$moduleName][$recordId];
		$newData = $newEntity->getData();
		/** Detect field value changes **/
		foreach($newData as $fieldName => $fieldValue) {
			$isModified = false;
			if(empty($oldData[$fieldName])) {
				if(!empty($newData[$fieldName])) {
					$isModified = true;
				}
			} elseif(strval($oldData[$fieldName]) !== strval($newData[$fieldName])) {
				$isModified = true;
			}
			if($isModified) {
				$delta[$fieldName] = array('oldValue' => $oldData[$fieldName],
										'currentValue' => $newData[$fieldName]);
			}
		}
		self::$entityDelta[$moduleName][$recordId] = $delta;
	}

	function getEntityDelta($moduleName, $recordId, $forceFetch=false) {
		if($forceFetch) {
			$this->fetchEntity($moduleName, $recordId);
			$this->computeDelta($moduleName, $recordId);
		}
		return self::$entityDelta[$moduleName][$recordId];
	}

	function getOldValue($moduleName, $recordId, $fieldName) {
		$entityDelta = self::$entityDelta[$moduleName][$recordId];
		return $entityDelta[$fieldName]['oldValue'];
	}

	function getCurrentValue($moduleName, $recordId, $fieldName) {
		$entityDelta = self::$entityDelta[$moduleName][$recordId];
		return $entityDelta[$fieldName]['currentValue'];
	}

	function getOldEntity($moduleName, $recordId) {
		return self::$oldEntity[$moduleName][$recordId];
	}

	function getNewEntity($moduleName, $recordId) {
		return self::$newEntity[$moduleName][$recordId];
	}

	/**
	 * Releases the cached delta data for one completely processed CRM record.
	 *
	 * VTEntityDelta keeps old entity data, new entity data and the calculated
	 * field delta in static arrays so every handler participating in the same
	 * save can access them. Long-running bulk imports can save many thousands of
	 * records in one PHP process. Without an explicit cleanup those static arrays
	 * retain every processed entity until the process ends and can exhaust the
	 * available PHP or operating-system memory.
	 *
	 * Call this only after vtiger.entity.aftersave.final has completed. At that
	 * point workflows, module handlers and ModTracker have already consumed the
	 * delta belonging to this save.
	 *
	 * @param string $moduleName CRM module name.
	 * @param int    $recordId   CRM record ID.
	 *
	 * @return void
	 */
	public static function clearEntity($moduleName, $recordId) {
		$moduleName = (string)$moduleName;
		$recordId = (int)$recordId;

		if ($moduleName === '' || $recordId <= 0) {
			return;
		}

		unset(self::$oldEntity[$moduleName][$recordId]);
		unset(self::$newEntity[$moduleName][$recordId]);
		unset(self::$entityDelta[$moduleName][$recordId]);

		if (isset(self::$oldEntity[$moduleName]) && empty(self::$oldEntity[$moduleName])) {
			unset(self::$oldEntity[$moduleName]);
		}

		if (isset(self::$newEntity[$moduleName]) && empty(self::$newEntity[$moduleName])) {
			unset(self::$newEntity[$moduleName]);
		}

		if (isset(self::$entityDelta[$moduleName]) && empty(self::$entityDelta[$moduleName])) {
			unset(self::$entityDelta[$moduleName]);
		}
	}
	
	function hasChanged($moduleName, $recordId, $fieldName, $fieldValue = NULL) {
		if(empty(self::$oldEntity[$moduleName][$recordId])) {
			return false;
		}
		$fieldDelta = self::$entityDelta[$moduleName][$recordId][$fieldName];
		$result = $fieldDelta['oldValue'] != $fieldDelta['currentValue'];
		if ($fieldValue !== NULL) {
			$result = $result && ($fieldDelta['currentValue'] === $fieldValue);
		}
		return $result;
	}

}
?>
