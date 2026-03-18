<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Accounts_ExportOverviewActivities_Action extends Vtiger_Action_Controller {
	
	function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if(!$currentUserPriviligesModel->hasModuleActionPermission($moduleModel->getId(), 'Export')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
		return true;
	}

	public function process(Vtiger_Request $request) {
		$adb = PearDatabase::getInstance();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$moduleName = $request->getModule();
		$relModuleName = 'Calendar';
		
		require_once("libraries/PHPExcel/PHPExcel.php");

		$workbook = new PHPExcel();
		$worksheet = $workbook->setActiveSheetIndex(0);
		$headerStyles = array(
			'fill' => array(
				'type' => PHPExcel_Style_Fill::FILL_SOLID,
				'color' => array('rgb'=>'E1E0F7')
			),
			'font' => array(
				'bold' => true)
		);
		$altRowStyles = array(
			'fill' => array(
				'type' => PHPExcel_Style_Fill::FILL_SOLID,
				'color' => array('rgb'=>'c5eff7')
			)
		);
		$errorRowStyles = array(
			'fill' => array(
				'type' => PHPExcel_Style_Fill::FILL_SOLID,
				'color' => array('rgb'=>'fcccb')
			)
		);
		
		$range = "1:1";
		$worksheet->getStyle($range)->applyFromArray($headerStyles);
		
		$fileName = "Uebersicht_Aktivitaeten_".date('Y-m-d').".xlsx";
		$accHeaders = array('account_no', 'accountname', 'tickersymbol', 'accounttype', 'assigned_user_id');
		$actHeaders = array('subject', 'createdtime', 'activitytype');
		
		$moduleInstance = Vtiger_Module::getInstance($moduleName);
		$allHeaders = array();
		$diffNames = array();
		foreach ($accHeaders AS $key => $fieldName) {
			$fieldObj = Vtiger_Field::getInstance($fieldName, $moduleInstance);
			if (!$fieldObj) {
				unset($accHeaders[$key]);
			} else {
				$allHeaders[] = vtranslate($fieldObj->label, $moduleName);
				// change to columnname in case it differs
				if ($fieldObj->name != $fieldObj->column) {
					$diffNames[$fieldObj->name] = $fieldObj->column;
				}
			}
		}
		
		$relModuleInstance = Vtiger_Module::getInstance($relModuleName);
		foreach ($actHeaders AS $key => $fieldName) {
			$fieldObj = Vtiger_Field::getInstance($fieldName, $relModuleInstance);
			if (!$fieldObj) {
				unset($actHeaders[$key]);
			} else {
				$allHeaders[] = vtranslate($fieldObj->label, $relModuleName);
				// change to columnname in case it differs
				if ($fieldObj->name != $fieldObj->column) {
					$diffNames[$fieldObj->name] = $fieldObj->column;
				}
			}
		}
		
		$toWrite = array($allHeaders);
		$rowNo = 2;
		
		$queryGenerator = new QueryGenerator($moduleName, $currentUserModel);
		$queryGenerator->setFields(array_merge(array('id'), $accHeaders));
		
		$columnList = $queryGenerator->getSelectClauseColumnSQL();
		$columnList .= ', '.implode(', ', $actHeaders);
		// do transformation here
		$columnList = str_replace('createdtime', 'ent2.createdtime', $columnList);
		$columnList = str_replace('vtiger_crmentity.smownerid', "CASE WHEN vtiger_groups.groupname != '' AND vtiger_groups.groupname IS NOT NULL THEN vtiger_groups.groupname ELSE CONCAT(vtiger_users.first_name,' ',vtiger_users.last_name) END AS smownerid", $columnList);

		$generatedQuery = "SELECT $columnList";
		$generatedQuery .= $queryGenerator->getFromClause();
		$generatedQuery .= "LEFT JOIN vtiger_seactivityrel ON vtiger_seactivityrel.crmid = vtiger_crmentity.crmid
							LEFT JOIN vtiger_activity ON vtiger_seactivityrel.activityid = vtiger_activity.activityid
							LEFT JOIN vtiger_crmentity AS ent2 ON ent2.crmid = vtiger_activity.activityid";
		$whereClause = $queryGenerator->getWhereClause();
		$whereClause .= " AND (ent2.deleted = 0 OR ent2.deleted IS NULL)";
		$generatedQuery .= $whereClause;
		// join activities of Contacts as well
		$generatedQuery .= " UNION ";
		$generatedQuery .= "SELECT $columnList";
		$generatedQuery .= $queryGenerator->getFromClause();
		$generatedQuery .= "INNER JOIN vtiger_contactdetails ON vtiger_contactdetails.accountid = vtiger_account.accountid
							INNER JOIN vtiger_crmentity AS ent3 ON ent3.crmid = vtiger_contactdetails.contactid
							INNER JOIN vtiger_seactivityrel ON vtiger_seactivityrel.crmid = vtiger_contactdetails.contactid
							INNER JOIN vtiger_activity ON vtiger_activity.activityid = vtiger_seactivityrel.activityid
							INNER JOIN vtiger_crmentity AS ent2 ON ent2.crmid = vtiger_activity.activityid";
		$generatedQuery .= $whereClause." AND ent3.deleted = 0";
		
		// var_dump($generatedQuery);
		// return;

		$res = $adb->pquery($generatedQuery, array());
		
		if ($res) {
			while ($row = $adb->getNextRow($res, false)) {
				$tmp = array();
				foreach ($accHeaders AS $key => $fieldName) {
					if (isset($diffNames[$fieldName])) {
						$fieldName = $diffNames[$fieldName];
					}
					$tmp[$fieldName] = $row[$fieldName];
				}
				foreach ($actHeaders AS $key => $fieldName) {
					if (isset($diffNames[$fieldName])) {
						$fieldName = $diffNames[$fieldName];
					}
					$tmp[$fieldName] = $row[$fieldName];
				}
				
				$accId = $row['accountid'];
				if (!isset($toWrite[$accId]) || $toWrite[$accId]['createdtime'] < $row['createdtime']) {
					$toWrite[$row['accountid']] = $tmp;
					
					// $worksheet->getCellByColumnAndRow(0, $rowNo)->getHyperlink()->setUrl($cLink);
					// $worksheet->getCellByColumnAndRow(1, $rowNo)->getHyperlink()->setUrl($gLink1);
					// $worksheet->getCellByColumnAndRow(2, $rowNo)->getHyperlink()->setUrl($gLink2);
					// $rowNo += 1;
				}
			}
		} else {
			$toWrite = array("Error: ".$adb->database->errorMsg());
		}
		
		
		$worksheet->fromArray($toWrite);
		$asc = 65;
		for ($i = 0; $i < count($allHeaders); $i++) {
			$worksheet->getColumnDimension(chr($asc))->setAutoSize(true);
			$asc += 1;
		}
		
		$workbookWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel2007');
		
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="'.$fileName.'"');
		$workbookWriter->save('php://output');
	}
}