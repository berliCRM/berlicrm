<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_Picklist_SaveAjax_Action extends Settings_Vtiger_Basic_Action {
    
    function __construct() {
        $this->exposeMethod('add');
        $this->exposeMethod('rename');
        $this->exposeMethod('remove');
        $this->exposeMethod('assignValueToRole');
        $this->exposeMethod('saveOrder');
        $this->exposeMethod('enableOrDisable');
        $this->exposeMethod('dynamicBlocks');
    }

    public function process(Vtiger_Request $request) {
        $mode = $request->get('mode');
        $this->invokeExposedMethod($mode, $request);
    }
    
    /*
     * @function updates user tables with new picklist value for default event and status fields
     */
    public function updateDefaultPicklistValues($pickListFieldName,$oldValue,$newValue) {
        $db = PearDatabase::getInstance();            
            if($pickListFieldName == 'activitytype')
                $defaultFieldName = 'defaultactivitytype';
            else
                $defaultFieldName = 'defaulteventstatus';
            $queryToGetId = 'SELECT id FROM vtiger_users WHERE '.$defaultFieldName.' IN (';
             if(is_array($oldValue)) {
                 for($i=0;$i<count($oldValue);$i++) {
                     $queryToGetId .= '"'.$oldValue[$i].'"';
                     if($i<(count($oldValue)-1)) {
                         $queryToGetId .= ',';
                     }
                 }
                 $queryToGetId .= ')';
             }
             else {
                 $queryToGetId .= '"'.$oldValue.'")';
             }
            $result = $db->pquery($queryToGetId, array());
            $rowCount =  $db->num_rows($result);
            for($i=0; $i<$rowCount; $i++) {
                $recordId = $db->query_result_rowdata($result, $i);
                $recordId = $recordId['id'];
                $record = Vtiger_Record_Model::getInstanceById($recordId, 'Users');
                $record->set('mode','edit');
                $record->set($defaultFieldName,$newValue);
                $record->save();
            }
    }
    
    public function add(Vtiger_Request $request) {
        $newValue = $request->getRaw('newValue'); // add single value
        $newValues = $request->getRaw('newValues'); // add multiple values from textarea
        $pickListName = $request->get('picklistName');
        $moduleName = $request->get('source_module');
        $moduleModel = Settings_Picklist_Module_Model::getInstance($moduleName);
        $fieldModel = Settings_Picklist_Field_Model::getInstance($pickListName, $moduleModel);
        $rolesSelected = array();
        if($fieldModel->isRoleBased()) {
            $userSelectedRoles = $request->get('rolesSelected',array());
            //selected all roles option
            if(in_array('all',$userSelectedRoles)) {
                $roleRecordList = Settings_Roles_Record_Model::getAll(true);
                foreach($roleRecordList as $roleRecord) {
                    $rolesSelected[] = $roleRecord->getId();
                }
            }else{
                $rolesSelected = $userSelectedRoles;
            }
        }
        $response = new Vtiger_Response();
        try{
            // multiple values, skip existing entries
            $currentvalues = Vtiger_Util_Helper::getPickListValues($pickListName);
            if ($newValues !="") {
                $tmp = explode("\r",$newValues);
                foreach ($tmp as $v) {
                    $newValue = trim($v);
                    if ($newValue !="" and !in_array($newValue,$currentvalues)) {
                        $id = $moduleModel->addPickListValues($fieldModel, $newValue, $rolesSelected);
                        $resultids[] = $id['id'];
                    }
                }
                $response->setResult(array('id' => $resultids));
            }
            // single value
            else {
                $id = $moduleModel->addPickListValues($fieldModel, $newValue, $rolesSelected);
                $response->setResult(array('id' => $id['id']));
            }
        }  catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }
    
    public function rename(Vtiger_Request $request) {
        $moduleName = $request->get('source_module');
        
        $newValue = $request->getRaw('newValue');
        $pickListFieldName = $request->get('picklistName');
        $oldValue = $request->getRaw('oldValue');
		$id = $request->getRaw('id');
        
        if($moduleName == 'Events' && ($pickListFieldName == 'activitytype' || $pickListFieldName == 'eventstatus')) {
             $this->updateDefaultPicklistValues($pickListFieldName,$oldValue,$newValue);
        }   
        $moduleModel = new Settings_Picklist_Module_Model();
        $response = new Vtiger_Response();
        try{
            $status = $moduleModel->renamePickListValues($pickListFieldName, $oldValue, $newValue, $moduleName, $id);
            $response->setResult(array('success',$status));
        } catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }
    
    public function remove(Vtiger_Request $request) {
        $moduleName = $request->get('source_module');
        $valueToDelete = $request->getRaw('delete_value');
        $replaceValue = $request->getRaw('replace_value');
        $pickListFieldName = $request->get('picklistName');
        
        if($moduleName == 'Events' && ($pickListFieldName == 'activitytype' || $pickListFieldName == 'eventstatus')) {
             $this->updateDefaultPicklistValues($pickListFieldName,$valueToDelete,$replaceValue);
        } 
        $moduleModel = Settings_Picklist_Module_Model::getInstance($moduleName);
        $response = new Vtiger_Response();
        try{
            $status = $moduleModel->remove($pickListFieldName, $valueToDelete, $replaceValue, $moduleName);
            $response->setResult(array('success',$status));
        } catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }

    /**
     * Function which will assign existing values to the roles
     * @param Vtiger_Request $request
     */
    public function assignValueToRole(Vtiger_Request $request) {
        $pickListFieldName = $request->get('picklistName');
        $valueToAssign = $request->getRaw('assign_values');
        $userSelectedRoles = $request->get('rolesSelected');
        
        $roleIdList = array();
        //selected all roles option
        if(in_array('all',$userSelectedRoles)) {
            $roleRecordList = Settings_Roles_Record_Model::getAll();
            foreach($roleRecordList as $roleRecord) {
                $roleIdList[] = $roleRecord->getId();
            }
        }else{
            $roleIdList = $userSelectedRoles;
        }
        
        $moduleModel = new Settings_Picklist_Module_Model();
        
        $response = new Vtiger_Response();
        try{
            $moduleModel->enableOrDisableValuesForRole($pickListFieldName, $valueToAssign, array(),$roleIdList);
            $response->setResult(array('success',true));
        } catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }
    
    public function saveOrder(Vtiger_Request $request) {
        $pickListFieldName = $request->get('picklistName');
        $picklistValues = $request->getRaw('picklistValues');
        
        $moduleModel = new Settings_Picklist_Module_Model();
        $response = new Vtiger_Response();
        try{
            $moduleModel->updateSequence($pickListFieldName, $picklistValues);
            $response->setResult(array('success',true));
        } catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }
    
    public function enableOrDisable(Vtiger_Request $request) {
        $pickListFieldName = $request->get('picklistName');
        $enabledValues = $request->getRaw('enabled_values',array());
        $disabledValues = $request->getRaw('disabled_values',array());
        $roleSelected = $request->get('rolesSelected');
        
        $moduleModel = new Settings_Picklist_Module_Model();
		$response = new Vtiger_Response();
        try{
            $moduleModel->enableOrDisableValuesForRole($pickListFieldName, $enabledValues, $disabledValues,array($roleSelected));
            $response->setResult(array('success',true));
        } catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }

    // ajax endpoint to save "dynamic blocks" (visibility of UI blocks in dependency of an entity's picklist value)
    public function dynamicBlocks(Vtiger_Request $request) {
        $picklistId = $request->get('picklistId');
        $moduleId = $request->get('moduleId');
        $db = PearDatabase::getInstance();
        parse_str($request->get("query"),$query);
        foreach ($query["dynblock"] as $picklistvalue => $blocks) {
            foreach ($blocks as $blockid => $status) {
                if ($status["hidden"]==0 && $status["blocked"] == 0) {
                    $q = "DELETE FROM berli_dynamic_blocks WHERE moduleid=? AND picklistid=? AND picklistvalueid=? AND blockid=? LIMIT 1";
                    $db->pquery($q,array($moduleId,$picklistId,$picklistvalue,$blockid));
                }
                else {
                    $q = "INSERT INTO berli_dynamic_blocks SET moduleid=?, picklistid=?, picklistvalueid=?, blockid=?, initialstatus=?, blocked=?
                            ON DUPLICATE KEY UPDATE initialstatus=?, blocked=?";
                    $db->pquery($q,array($moduleId,$picklistId,$picklistvalue,$blockid,$status["hidden"],$status["blocked"],$status["hidden"],$status["blocked"]));        
                }
            }
        }
        $response = new Vtiger_Response();
        $response->setResult(array());
        $response->emit();
    }
 
    public function validateRequest(Vtiger_Request $request) { 
        $request->validateWriteAccess(); 
    } 
}
