<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_DeleteAjax_Action extends Vtiger_Delete_Action {

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$operation = $request->get('operation');

		if($operation == "checkRecurr"){

			$idsReferencesArray = $this->findRecurringreferencesIDs($recordId);
			
			$response = new Vtiger_Response();
            $result = array('idsReferencesArray'=>$idsReferencesArray,'error'=>false);
            $response->setResult($result);
            $response->emit();
            exit;

		}
		/*else if($operation == "deleteevent" ){

			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$recordModel->delete();

			$cvId = $request->get('viewname');
			$response = new Vtiger_Response();
			$response->setResult(array('viewname'=>$cvId, 'module'=>$moduleName));
			$response->emit();
			exit;

		}*/
		else if($operation == "deleteManyEvents"){

			$idsReferencesArray = $request->get('idsReferencesArray');
			for($a = 0; $a < count($idsReferencesArray) ; $a++){
				$recordModel = Vtiger_Record_Model::getInstanceById($idsReferencesArray[$a], $moduleName);
				if( !empty($recordModel) ){
					$recordModel->delete();
				}
				
			}
			$cvId = $request->get('viewname');
			$response = new Vtiger_Response();
			$response->setResult(array('viewname'=>$cvId, 'module'=>$moduleName));
			$response->emit();
			exit;

		}
		else{ 
			//// it cannot step here, $operation must be given in request. error.
			//// but because it are often not writen in request, we need to have a way to delete it anyway.
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$recordModel->delete();

			$cvId = $request->get('viewname');
			$response = new Vtiger_Response();
			$response->setResult(array('viewname'=>$cvId, 'module'=>$moduleName));
			$response->emit();
			exit;
		}
	}

	public function findRecurringreferencesIDs($idRef){
		// if $idRef belong to RecurringEvent so this funktion find all IDs, that belong to it to.
		global $adb;
		$sqlRef1 = 'SELECT * FROM `berlicrm_recurringreferences` WHERE activityid = ?';
		$resultRef1 = $adb->pquery($sqlRef1, array( $idRef ));
		$rowsRef1 = $adb->num_rows($resultRef1);

		$idsReferencesArray = array();
		$parentactivityidMe ='';
		//$rowsRef1, if it is not 0 so it can be only 1.
		if($rowsRef1 > 0){
			for($a = 0; $a < $rowsRef1 ; $a++){
				$row1 = $adb->query_result_rowdata($resultRef1, $a);
				$parentactivityidMe = $row1['parentactivityid'];
			}
			if($parentactivityidMe != ''){
				$sqlRef2 = 'SELECT activityid FROM `berlicrm_recurringreferences` WHERE parentactivityid = ?';
				$resultRef2 = $adb->pquery($sqlRef2, array(  $parentactivityidMe  ));
				$rowsRef2 = $adb->num_rows($resultRef2);
				// it can be nothing, one, or many here.
				if($rowsRef2 > 0){
					for($b = 0; $b < $rowsRef2 ; $b++){
						$row2 = $adb->query_result_rowdata($resultRef2, $b);
						$activityidMe = $row2['activityid'];
						// add the belong IDs to the array.
						$idsReferencesArray[] = $activityidMe;
					}
				}else{
					// ERROR it can not step here, because if "parentactivityid" exist, it muss exist "activityid" to.
				}
			}else{
				// ERROR it can not step here, because "parentactivityid" field are not found, but muss be in DB.
			}
		}else{
			// here step it only if this $idRef not be in the table as 'activityid'
		}
		// if the array is empty, so it was a normal Event without recurring.
		// doubles remove (it can not have a double values, but just in case)
		$idsReferencesArray = array_unique($idsReferencesArray); 
		// from smallest to largest value.  (normally it is allready sorted, but just im case)
		sort($idsReferencesArray); 
		return $idsReferencesArray;
	}

}
