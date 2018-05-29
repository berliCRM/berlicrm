<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * modified by crm-now
 *************************************************************************************/
/**
 * Vtiger Entity Record Model Class
 */
class Vtiger_Record_Model extends Vtiger_Base_Model {

	protected $module = false;

	/**
	 * Function to get the id of the record
	 * @return <Number> - Record Id
	 */
	public function getId() {
		return $this->get('id');
	}

	/**
	 * Function to set the id of the record
	 * @param <type> $value - id value
	 * @return <Object> - current instance
	 */
	public function setId($value) {
		return $this->set('id',$value);
	}

	/**
	 * Fuction to get the Name of the record
	 * @return <String> - Entity Name of the record
	 */
	public function getName() {
		$displayName = $this->get('label');
		if(empty($displayName)) {
			$displayName = $this->getDisplayName();
		}
		return Vtiger_Util_Helper::toSafeHTML(decode_html($displayName));
	}

	/**
	 * Function to get the Module to which the record belongs
	 * @return Vtiger_Module_Model
	 */
	public function getModule() {
		return $this->module;
	}

	/**
	 * Function to set the Module to which the record belongs
	 * @param <String> $moduleName
	 * @return Vtiger_Record_Model or Module Specific Record Model instance
	 */
	public function setModule($moduleName) {
		$this->module = Vtiger_Module_Model::getInstance($moduleName);
		return $this;
	}

	/**
	 * Function to set the Module to which the record belongs from the Module model instance
	 * @param <Vtiger_Module_Model> $module
	 * @return Vtiger_Record_Model or Module Specific Record Model instance
	 */
	public function setModuleFromInstance($module) {
		$this->module = $module;
		return $this;
	}

	/**
	 * Function to get the entity instance of the recrod
	 * @return CRMEntity object
	 */
	public function getEntity() {
		return $this->entity;
	}

	/**
	 * Function to set the entity instance of the record
	 * @param CRMEntity $entity
	 * @return Vtiger_Record_Model instance
	 */
	public function setEntity($entity) {
		$this->entity = $entity;
		return $this;
	}

	/**
	 * Function to get raw data
	 * @return <Array>
	 */
	public function getRawData() {
		return $this->rawData;
	}

	/**
	 * Function to set raw data
	 * @param <Array> $data
	 * @return Vtiger_Record_Model instance
	 */
	public function setRawData($data) {
		$this->rawData = $data;
		return $this;
	}

	/**
	 * Function to get the Detail View url for the record
	 * @return <String> - Record Detail View Url
	 */
	public function getDetailViewUrl() {
		$module = $this->getModule();
		return 'index.php?module='.$this->getModuleName().'&view='.$module->getDetailViewName().'&record='.$this->getId();
	}

	/**
	 * Function to get the complete Detail View url for the record
	 * @return <String> - Record Detail View Url
	 */
	public function getFullDetailViewUrl() {
		$module = $this->getModule();
		return 'index.php?module='.$this->getModuleName().'&view='.$module->getDetailViewName().'&record='.$this->getId().'&mode=showDetailViewByMode&requestMode=full';
	}

	/**
	 * Function to get the Edit View url for the record
	 * @return <String> - Record Edit View Url
	 */
	public function getEditViewUrl() {
		$module = $this->getModule();
		return 'index.php?module='.$this->getModuleName().'&view='.$module->getEditViewName().'&record='.$this->getId();
	}

	/**
	 * Function to get the Update View url for the record
	 * @return <String> - Record Upadte view Url
	 */
	public function getUpdatesUrl() {
		return $this->getDetailViewUrl()."&mode=showRecentActivities&page=1&tab_label=LBL_UPDATES";
	}

	/**
	 * Function to get the Delete Action url for the record
	 * @return <String> - Record Delete Action Url
	 */
	public function getDeleteUrl() {
		$module = $this->getModule();
		return 'index.php?module='.$this->getModuleName().'&action='.$module->getDeleteActionName().'&record='.$this->getId();
	}

	/**
	 * Function to get the name of the module to which the record belongs
	 * @return <String> - Record Module Name
	 */
	public function getModuleName() {
		return $this->getModule()->get('name');
	}

	/**
	 * Function to get the Display Name for the record
	 * @return <String> - Entity Display Name for the record
	 */
	public function getDisplayName() {
		return Vtiger_Util_Helper::getLabel($this->getId());
	}

	/**
	 * Function to retieve display value for a field
	 * @param <String> $fieldName - field name for which values need to get
	 * @return <String>
	 */
	public function getDisplayValue($fieldName,$recordId = false) {
		if(empty($recordId)) {
			$recordId = $this->getId();
		}
		$fieldModel = $this->getModule()->getField($fieldName);
       
        // For showing the "Date Sent" and "Time Sent" in email related list in user time zone
        if($fieldName == "time_start" && $this->getModule()->getName() == "Emails"){
            $date = new DateTime();
            $dateTime = new DateTimeField($date->format('Y-m-d').' '.$this->get($fieldName));
            $value = $dateTime->getDisplayTime();
            $this->set($fieldName, $value);
            return $value;
        }else if($fieldName == "date_start" && $this->getModule()->getName() == "Emails"){
            $dateTime = new DateTimeField($this->get($fieldName).' '.$this->get('time_start'));
            $value = $dateTime->getDisplayDate();
            $this->set($fieldName, $value);
            return $value;
        }
        // End
        
		if($fieldModel) {
			return $fieldModel->getDisplayValue($this->get($fieldName), $recordId, $this);
		}
		return false;
	}

	/**
	 * Function returns the Vtiger_Field_Model
	 * @param <String> $fieldName - field name
	 * @return <Vtiger_Field_Model>
	 */
	public function getField($fieldName) {
		return $this->getModule()->getField($fieldName);
	}

	/**
	 * Function returns all the field values in user format
	 * @return <Array>
	 */
	public function getDisplayableValues() {
		$displayableValues = array();
		$data = $this->getData();
		foreach($data as $fieldName=>$value) {
			$fieldValue = $this->getDisplayValue($fieldName);
			$displayableValues[$fieldName] = ($fieldValue) ? $fieldValue : $value;
		}
		return $displayableValues;
	}

	/**
	 * Function to save the current Record Model
	 */
	public function save() {
		$this->getModule()->saveRecord($this);
	}

	/**
	 * Function to delete the current Record Model
	 */
	public function delete() {
		$this->getModule()->deleteRecord($this);
	}

	/**
	 * Static Function to get the instance of a clean Vtiger Record Model for the given module name
	 * @param <String> $moduleName
	 * @return Vtiger_Record_Model or Module Specific Record Model instance
	 */
	public static function getCleanInstance($moduleName) {
		//TODO: Handle permissions
		$focus = CRMEntity::getInstance($moduleName);
		$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'Record', $moduleName);
		$instance = new $modelClassName();
		return $instance->setData($focus->column_fields)->setModule($moduleName)->setEntity($focus);
	}

	/**
	 * Static Function to get the instance of the Vtiger Record Model given the recordid and the module name
	 * @param <Number> $recordId
	 * @param <String> $moduleName
	 * @return Vtiger_Record_Model or Module Specific Record Model instance
	 */
	public static function getInstanceById($recordId, $module=null) {
		//TODO: Handle permissions
		if(is_object($module) && is_a($module, 'Vtiger_Module_Model')) {
			$moduleName = $module->get('name');
		} elseif (is_string($module)) {
			$module = Vtiger_Module_Model::getInstance($module);
			$moduleName = $module->get('name');
		} elseif(empty($module)) {
			$moduleName = getSalesEntityType($recordId);
			$module = Vtiger_Module_Model::getInstance($moduleName);
		}

		$focus = CRMEntity::getInstance($moduleName);
		$focus->id = $recordId;
		$focus->retrieve_entity_info($recordId, $moduleName);
		$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'Record', $moduleName);
		$instance = new $modelClassName();
		return $instance->setData($focus->column_fields)->set('id',$recordId)->setModuleFromInstance($module)->setEntity($focus);
	}
	
	/**
	 * Function to get the listquery for a full search
	 * @param  string $tabid  -- tabid of the module to search
	 * @param  string $searchKey -- search term
	 */
	
	public static function dofullmodulesearch($tabid, $searchKey){
		require_once 'include/utils/utils.php';
		$db = PearDatabase::getInstance();
		$moduleName = vtlib_getModuleNameById($tabid);
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		
		if (!empty ($moduleModel)  and $moduleModel->isActive() and $moduleName!='PBXManager') {
			$fieldModels = $moduleModel->getFields();
			$listquery = getListQuery($moduleName);
			$searchcolumn_query = $db->pquery("select displayfield from berli_globalsearch_settings where gstabid =?", array($tabid));
			$serachcol_arr = Vtiger_Record_Model::getDisplayLabelsArray($tabid);
			foreach ($serachcol_arr as $fieldname) {
				//there could be the case that a custom field was deleted and is still in berli_globalsearch_settings 
				if ($fieldname=='accountid'){
					$newfiled = 'account_id';
				}
				else {
					$newfiled = $fieldname;
				}
				if (!empty($fieldModels[$newfiled])) {
						$fieldtable[] = $fieldModels[$newfiled]->table.'.'.$fieldname;
				}
			}
			if (!empty($fieldtable) and is_array($fieldtable)){
				//fields to display are defined in berli_globalsearch_settings
				$query_select = implode(",", $fieldtable);
				$listviewquery = substr($listquery, strpos($listquery, 'FROM'), strlen($listquery));
				$listquery = "select vtiger_crmentity.crmid, vtiger_crmentity.createdtime, vtiger_crmentity.smownerid, " . $query_select . "  " . $listviewquery;
			}
			else {
				//cover all other cases
				$listviewquery = substr($listquery, strpos($listquery, 'FROM'), strlen($listquery));
				$metainfo = Vtiger_Functions::getEntityModuleInfo($moduleName);
				$listquery = "select vtiger_crmentity.crmid, vtiger_crmentity.createdtime, vtiger_crmentity.smownerid, " . $metainfo['tablename'].".".$metainfo['fieldname'] . "  " . $listviewquery;
			}
			$where = Vtiger_Record_Model::getUnifiedWhere($listquery,$moduleName,$searchKey);
			if($where != ''){
				$listquery .= ' and ('.$where.')';
			}
		}
		return $listquery;
	}
	/**
	 * Function to get the where condition for a module based on the field table entries
	 * @param  string $listquery  -- ListView query for the module
	 * @param  string $module     -- module name
	 * @param  string $search_val -- entered search string value
	 * @return string $where      -- where condition for the module based on field table entries
	 */
	static function getUnifiedWhere($listquery,$module,$search_val){
		global $current_user;
		$db = PearDatabase::getInstance();
		require('user_privileges/user_privileges_'.$current_user->id.'.php');

		$search_val = $db->sql_escape_string($search_val);
		if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] ==0){
			$query = "SELECT columnname, tablename FROM vtiger_field WHERE tabid = ? and vtiger_field.presence in (0,2)";
			$qparams = array(getTabid($module));
		}else{
			$profileList = getCurrentUserProfileList();
			$query = "SELECT columnname, tablename FROM vtiger_field INNER JOIN vtiger_profile2field ON vtiger_profile2field.fieldid = vtiger_field.fieldid INNER JOIN vtiger_def_org_field ON vtiger_def_org_field.fieldid = vtiger_field.fieldid WHERE vtiger_field.tabid = ? AND vtiger_profile2field.visible = 0 AND vtiger_profile2field.profileid IN (". generateQuestionMarks($profileList) . ") AND vtiger_def_org_field.visible = 0 and vtiger_field.presence in (0,2) GROUP BY vtiger_field.fieldid";
			$qparams = array(getTabid($module), $profileList);
		}
		$result = $db->pquery($query, $qparams);
		$noofrows = $db->num_rows($result);

		$where = '';
		for($i=0;$i<$noofrows;$i++){
			$columnname = $db->query_result($result,$i,'columnname');
			$tablename = $db->query_result($result,$i,'tablename');

			// Search / Lookup customization
			if($module == 'Contacts' && $columnname == 'accountid') {
				$columnname = "accountname";
				$tablename = "vtiger_account";
			}
			// END

			//Before form the where condition, check whether the table for the field has been added in the listview query
			if(strstr($listquery,$tablename)){
				if($where != ''){
					$where .= " OR ";
				}
				$where .= $tablename.".".$columnname." LIKE '". formatForSqlLike($search_val) ."'";
			}
		}
		return $where;
	}

	/**
	 * Function to get details for user have the permissions to do actions
	 * @return <Boolean> - true/false
	 */
	public static function getDisplayLabelsArray ($tabid) {
		$db = PearDatabase::getInstance();
		$displayfield_query = $db->pquery("select displayfield from berli_globalsearch_settings where gstabid =?", array($tabid));
		$displayfield = $db->query_result($displayfield_query,0,'displayfield');
		$serachcol_array = array ();
		if (trim($displayfield) !='') {
			$serachcol_array = explode(",",$displayfield);
		}
		else {
			//there is no special settings = get the standard table
			$entityname_query = $db->pquery("select fieldname from vtiger_entityname where tabid =?", array($tabid));
			$entitynamecolumn = $db->query_result($entityname_query,0,'fieldname');
			$serachcol_array = explode(",",$entitynamecolumn);
		}
		return $serachcol_array;
	}
	/**
	 * Static Function to get the list of records matching the search key
	 * @param <String> $searchKey
	 * @return <Array> - List of Vtiger_Record_Model or Module Specific Record Model instances
	 */
	public static function getSearchResult($searchKey, $module=false) {
		$db = PearDatabase::getInstance();
		//decide search mode
		$matchingRecords =array ();
		if ($module == false) {
			//get all tabid settings for search
			$searchdata = 'SELECT * FROM berli_globalsearch_settings WHERE turn_off = 1';
			$searchdata_result = $db->pquery($searchdata, array());
			$num_of_all = $db->num_rows($searchdata_result);
			for($v=0; $v<$num_of_all; ++$v) {
				if ($db->query_result($searchdata_result, $v, "searchall")==1){
					//search all activated
					$tabid = $db->query_result($searchdata_result, $v, "gstabid");
					$query = Vtiger_Record_Model::dofullmodulesearch($tabid, $searchKey);
					$tab_result = $db->pquery($query, array());
					$tab_noOfRows = $db->num_rows($tab_result);
					$moduleName = vtlib_getModuleNameById($tabid);
					for($u=0; $u<$tab_noOfRows; ++$u) {
						$row = $db->query_result_rowdata($tab_result, $u);
						$globalsearchlist[$tabid][$u] = $row['crmid'];
						$moduleModels = array();
						if(!array_key_exists($moduleName, $moduleModels)) {
							$moduleModels[$moduleName] = Vtiger_Module_Model::getInstance($moduleName);
						}
						$moduleModel = $moduleModels[$moduleName];
						$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'Record', $moduleName);
						$recordInstance = new $modelClassName();

						//resolve related fields by uitype
						$moduleInfo = Vtiger_Functions::getModuleFieldInfos($moduleName);
						$moduleInfoExtend = [];
						if (count($moduleInfo) > 0) {
							foreach ($moduleInfo as $field => $fieldInfo) {
								$moduleInfoExtend[$fieldInfo['columnname']] = $fieldInfo;
							}
						}
						$label_name = array();
						$label_search = array();
						$serachcol_arr = Vtiger_Record_Model::getDisplayLabelsArray($tabid);
						foreach ($serachcol_arr as $columnName) {
							if ($moduleInfoExtend && in_array($moduleInfoExtend[$columnName]['uitype'], array(10, 51,73,76, 75, 81))) {
								//get module of the related record
								if ($row[$columnName] > 0) {
									$setype = 'SELECT setype FROM vtiger_crmentity WHERE crmid = ?';
									$setype_result = $db->pquery($setype, array($row[$columnName]));
									$entityinfo = 'SELECT tablename, fieldname, entityidfield FROM vtiger_entityname WHERE modulename = ?';
									$entityinfo_result = $db->pquery($entityinfo, array($db->query_result($setype_result, 0, "setype")));
									$label_query = "Select ".$db->query_result($entityinfo_result, 0, "fieldname")." from ".$db->query_result($entityinfo_result, 0, "tablename")." where ".$db->query_result($entityinfo_result, 0, "entityidfield")." =?";
									$label_result = $db->pquery($label_query, array($row[$columnName]));
									$label_name[$columnName] = $db->query_result($label_result, 0, $db->query_result($entityinfo_result, 0, "fieldname"));
								}
								else {
									$label_name[$columnName] ='';
								}
							}
							else {
								$label_name[$columnName] = $row[$columnName];
							}
						}
						$row['label'] ='';
						foreach ($serachcol_arr as $displaylabel) {
							if ($row['label'] =='') {
								$row['label'] = $label_name[$displaylabel];
							}
							else {
								$row['label'] .= ' |'.$label_name[$displaylabel];
							}
						}
						$row['id'] =$row['crmid'];
						$matchingRecords[$moduleName][$row['id']] = $recordInstance->setData($row)->setModuleFromInstance($moduleModel);
					}
				}
				else {
					//"search all" is not activated
					$moduleModels = $leadIdsList = $convertedInfo = array();
					$tabid = $db->query_result($searchdata_result, $v, "gstabid");
					$searchModule = vtlib_getModuleNameById($tabid);
					$query = 'SELECT searchlabel, crmid, setype, createdtime FROM berli_globalsearch_data inner join vtiger_crmentity on vtiger_crmentity.crmid = berli_globalsearch_data.gscrmid WHERE searchlabel like ? AND vtiger_crmentity.deleted = 0 and setype=?';
					$params = array("%$searchKey%", $searchModule);
					$result = $db->pquery($query, $params);
					$noOfRows = $db->num_rows($result);
					$serachcol_arr = Vtiger_Record_Model::getDisplayLabelsArray($tabid);
					for($i=0, $recordsCount = 0; $i<$noOfRows && $recordsCount<100; ++$i) {
						$row = $db->query_result_rowdata($result, $i);
						if ($row['setype'] == 'Leads') {
							//exclude converted Leads from search results
							$leadresult = $db->pquery("SELECT converted FROM vtiger_leaddetails WHERE leadid =? ", array($row['crmid']));
							if ($db->query_result($leadresult, 0, 'converted') == 1) {
								continue;
							}
						}
						if(Users_Privileges_Model::isPermitted($row['setype'], 'DetailView', $row['crmid'])) {
							$row['id'] = $row['crmid'];
							$moduleName = $row['setype'];
							if(!array_key_exists($moduleName, $moduleModels)) {
								$moduleModels[$moduleName] = Vtiger_Module_Model::getInstance($moduleName);
							}
							$moduleModel = $moduleModels[$moduleName];
							$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'Record', $moduleName);
							$recordInstance = new $modelClassName();
							$matchingRecords[$moduleName][$row['id']] = $recordInstance->setData($row)->setModuleFromInstance($moduleModel);
							$recordsCount++;
						}
					}
				}
			}
		}
		else {
			//individual module search
			$moduleModels = $matchingRecords = $leadIdsList = array();
			$query = 'SELECT label, searchlabel, crmid, setype, createdtime, smownerid FROM vtiger_crmentity crm 
				INNER JOIN vtiger_entityname e ON crm.setype = e.modulename 
				INNER JOIN berli_globalsearch_settings gs ON e.tabid = gs.gstabid 
				LEFT JOIN berli_globalsearch_data ON crm.crmid = berli_globalsearch_data.gscrmid WHERE searchlabel LIKE ? AND crm.deleted = 0 and gs.turn_off=1';
			$params = array("%$searchKey%");
			$query .= ' AND setype = ?';
			$params[] = $module;
			$result = $db->pquery($query, $params);
			$noOfRows = $db->num_rows($result);

			for($i=0, $recordsCount = 0; $i<$noOfRows && $recordsCount<100; ++$i) {
				$row = $db->query_result_rowdata($result, $i);
				if ($row['setype'] == 'Leads') {
					//exclude converted Leads from search results
					$leadresult = $db->pquery("SELECT converted FROM vtiger_leaddetails WHERE leadid =? ", array($row['crmid']));
					if ($db->query_result($leadresult, 0, 'converted') == 1) {
						continue;
					}
				}
				if(Users_Privileges_Model::isPermitted($row['setype'], 'DetailView', $row['crmid'])) {
					$row['id'] = $row['crmid'];
					$moduleName = $row['setype'];
					if(!array_key_exists($moduleName, $moduleModels)) {
						$moduleModels[$moduleName] = Vtiger_Module_Model::getInstance($moduleName);
					}
					$moduleModel = $moduleModels[$moduleName];
					$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'Record', $moduleName);
					$recordInstance = new $modelClassName();
					$matchingRecords[$moduleName][$row['id']] = $recordInstance->setData($row)->setModuleFromInstance($moduleModel);
					$recordsCount++;
				}
			}
		}
		return $matchingRecords;
	}

	/**
	 * Function to get details for user have the permissions to do actions
	 * @return <Boolean> - true/false
	 */
	public function isEditable() {
		return Users_Privileges_Model::isPermitted($this->getModuleName(), 'EditView', $this->getId());
	}

	/**
	 * Function to get details for user have the permissions to do actions
	 * @return <Boolean> - true/false
	 */
	public function isDeletable() {
		return Users_Privileges_Model::isPermitted($this->getModuleName(), 'Delete', $this->getId());
	}

	/**
	 * Funtion to get Duplicate Record Url
	 * @return <String>
	 */
	public function getDuplicateRecordUrl() {
		$module = $this->getModule();
		return 'index.php?module='.$this->getModuleName().'&view='.$module->getEditViewName().'&record='.$this->getId().'&isDuplicate=true';

	}

	/**
	 * Function to get Display value for RelatedList
	 * @param <String> $value
	 * @return <String>
	 */
	public function getRelatedListDisplayValue($fieldName) {
		$fieldModel = $this->getModule()->getField($fieldName);
		return $fieldModel->getRelatedListDisplayValue($this->get($fieldName));
	}

	/**
	 * Function to delete corresponding image
	 * @param <type> $imageId
	 */
	public function deleteImage($imageId) {
		$db = PearDatabase::getInstance();

		$checkResult = $db->pquery('SELECT crmid FROM vtiger_seattachmentsrel WHERE attachmentsid = ?', array($imageId));
		$crmId = $db->query_result($checkResult, 0, 'crmid');

		if ($this->getId() === $crmId) {
			$db->pquery('DELETE FROM vtiger_attachments WHERE attachmentsid = ?', array($imageId));
			$db->pquery('DELETE FROM vtiger_seattachmentsrel WHERE attachmentsid = ?', array($imageId));
			return true;
		}
		return false;
	}

	/**
	 * Function to get Descrption value for this record
	 * @return <String> Descrption
	 */
	public function getDescriptionValue() {
		$description = $this->get('description');
		if(empty($description)) {
			$db = PearDatabase::getInstance();
			$result = $db->pquery("SELECT description FROM vtiger_crmentity WHERE crmid = ?", array($this->getId()));
			$description =  $db->query_result($result, 0, "description");
		}
		return $description;
	}

	/**
	 * Function to transfer related records of parent records to this record
	 * @param <Array> $recordIds
	 * @return <Boolean> true/false
	 */
	public function transferRelationInfoOfRecords($recordIds = array()) {
		if ($recordIds) {
			$moduleName = $this->getModuleName();
			$focus = CRMEntity::getInstance($moduleName);
			if (method_exists($focus, 'transferRelatedRecords')) {
				$focus->transferRelatedRecords($moduleName, $recordIds, $this->getId());
			}
		}
		return true;
	}
	

}
