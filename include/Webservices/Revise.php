<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
	
function vtws_revise($element,$user){
    
    global $log,$adb;
    $idList = vtws_getIdComponents($element['id']);
    
    $webserviceObject = VtigerWebserviceObject::fromId($adb,$idList[0]);
    $handlerPath = $webserviceObject->getHandlerPath();
    $handlerClass = $webserviceObject->getHandlerClass();
    
    require_once $handlerPath;
    
    $handler = new $handlerClass($webserviceObject,$user,$adb,$log);
    $meta = $handler->getMeta();
    $entityName = $meta->getObjectEntityName($element['id']);
    
    $types = vtws_listtypes(null, $user);
    if(!in_array($entityName,$types['types'])){
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
    }
    
    if($entityName !== $webserviceObject->getEntityName()){
        throw new WebServiceException(WebServiceErrorCode::$INVALIDID,"Id specified is incorrect");
    }
    
    if(!$meta->hasPermission(EntityMeta::$UPDATE,$element['id'])){
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to read given object is denied");
    }
    
    if(!$meta->exists($idList[1])){
        throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,"Record you are trying to access is not found");
    }
    
    if($meta->hasWriteAccess()!==true){
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to write is denied");
    }
    
    $referenceFields = $meta->getReferenceFieldDetails();
    foreach($referenceFields as $fieldName=>$details){
        if(isset($element[$fieldName]) && strlen($element[$fieldName]) > 0){
            $ids = vtws_getIdComponents($element[$fieldName]);
            $elemTypeId = $ids[0];
            $elemId = $ids[1];
            $referenceObject = VtigerWebserviceObject::fromId($adb,$elemTypeId);
            if (!in_array($referenceObject->getEntityName(),$details)){
                throw new WebServiceException(WebServiceErrorCode::$REFERENCEINVALID,
                    "Invalid reference specified for $fieldName");
            }
            if ($referenceObject->getEntityName() == 'Users') {
                if(!$meta->hasAssignPrivilege($element[$fieldName])) {
                    throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, "Cannot assign record to the given user");
                }
            }
            if (!in_array($referenceObject->getEntityName(), $types['types']) && $referenceObject->getEntityName() != 'Users') {
                throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,
                    "Permission to access reference type is denied ".$referenceObject->getEntityName());
            }
        }
    }
    //check if the element has mandtory fields filled
    $meta->isUpdateMandatoryFields($element);

    $ownerFields = $meta->getOwnerFields();
    if(is_array($ownerFields) && sizeof($ownerFields) >0){
        foreach($ownerFields as $ownerField){
            if(isset($element[$ownerField]) && $element[$ownerField]!==null && 
                !$meta->hasAssignPrivilege($element[$ownerField])){
                throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, "Cannot assign record to the given user");
            }
        }
    }
    
    // validate entries for picklist and autocomplete fields (uitypes 15/16/cr16/crs16), only allow values from picklist or current value
    $moduleFields = $meta->getModuleFields();
    unset($moduleFields["activitytype"]);
    foreach ($moduleFields as $fieldName => $field) {
        $uitype = $field->getUIType();
        if ($uitype == "crs16" && $element[$fieldName] !="") {
            // crs16 : only allow unused picklist values or current value
            $modulemodel = Vtiger_Module_Model::getInstance($meta->getTabId());
            $fieldmodel = Vtiger_Field_Model::getInstance($fieldName,$modulemodel);
            $sql = "SELECT $fieldName FROM vtiger_$fieldName LEFT JOIN {$fieldmodel->table} USING ({$fieldmodel->column})
                WHERE presence = 1 AND $fieldName = ? AND ({$fieldmodel->table}.$fieldName IS NULL OR {$fieldmodel->block->module->basetableid} = {$idList[1]})";
            $res = $adb->pquery($sql,array($element[$fieldName]));
            if ($res && $adb->num_rows($res) == 0) {
                $sql = "SELECT $fieldName FROM vtiger_$fieldName WHERE presence = 1 AND $fieldName = ?";
                $res = $adb->pquery($sql,array($element[$fieldName]));
                if ($adb->num_rows($res) == 0) {
                    throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, "Illegal value (".$element[$fieldName].") for $fieldName (".$fieldmodel->get('label').")");
                }
                else {
                    throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, "Value given (".$element[$fieldName].") for $fieldName (".$fieldmodel->get('label').") already in use (may only be used once)");
                }
            }
        }
        elseif (($uitype == "15" || $uitype == "16" || $uitype == "cr16") && $element[$fieldName] !="") {
            $modulemodel = Vtiger_Module_Model::getInstance($meta->getTabId());
            $fieldmodel = Vtiger_Field_Model::getInstance($fieldName,$modulemodel);
			$recordModel = Vtiger_Record_Model::getCleanInstance($entityName);
			$tab_name_index = $recordModel->entity->tab_name_index;
			if (empty($tab_name_index)) {
				$basetableid = $fieldmodel->block->module->basetableid;
			}
			else {
				$basetableid = $tab_name_index[$fieldmodel->table];
			}
			$column_name_rel = array($fieldmodel->name=>$fieldmodel->column);
			$column = $column_name_rel[$fieldName];
            $sql = "SELECT $fieldName FROM vtiger_$fieldName WHERE presence = 1 AND $fieldName = ? 
                UNION SELECT 1 FROM {$fieldmodel->table} WHERE {$basetableid} = {$idList[1]} AND $column = ?";
            $res = $adb->pquery($sql,array($element[$fieldName],$element[$fieldName]));
            if ($res && $adb->num_rows($res) == 0) {
                throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, "Illegal value (".$element[$fieldName].") for $fieldName (".$fieldmodel->get('label').")");
            }
        }
    }

    $entity = $handler->revise($element);
    VTWS_PreserveGlobal::flush();
    return $entity;
}

