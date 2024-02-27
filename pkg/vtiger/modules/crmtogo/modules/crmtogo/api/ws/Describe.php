<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified by crm-now GmbH, www.crm-now.com
 ************************************************************************************/
include_once 'include/Webservices/DescribeObject.php';
include_once dirname(__FILE__) . '/Utils.php';

class crmtogo_WS_Describe extends crmtogo_WS_Controller {
	protected function cacheDescribeInfo($describeInfo) {
		$this->_cachedDescribeInfo = $describeInfo;
		$this->_cachedDescribeFieldInfo = array();
		if(!empty($describeInfo['fields'])) {
			foreach($describeInfo['fields'] as $describeFieldInfo) {
				$this->_cachedDescribeFieldInfo[$describeFieldInfo['name']] = $describeFieldInfo;
			}
		}
	}
	
	function process(crmtogo_API_Request $request) {
		$current_user = $this->getActiveUser();
		$module = $request->get('module');
		$newrecord = self::transformToBlocks($module);
		$response = new crmtogo_API_Response();
		$response->setResult(array('record' => $newrecord));
		return $response;
	}
	
	
	protected function transformToBlocks($module) {
		$current_user = $this->getActiveUser();
		$moduleFieldGroups = crmtogo_WS_Utils::gatherModuleFieldGroupInfo($module);
		$describeInfo = vtws_describe($module, $current_user);
		crmtogo_WS_Utils::fixDescribeFieldInfo($module, $describeInfo,$current_user);
		$modifiedResult = array();
		$blocks = array(); 
		$labelFields = false;
		foreach($moduleFieldGroups as $blocklabel => $fieldgroups) {
			$fields = array();
			foreach($fieldgroups as $fieldname => $fieldinfo) {
                $field = array();
				$field['name'] = $fieldname;	
				$field['value'] = '';	
				$field['label'] =  self::searcharray($fieldname, 'name', $describeInfo['fields']);
				$field['uitype'] = $fieldinfo['uitype'];	
				$field['typeofdata'] = $fieldinfo['typeofdata'];
				$field['mandatory']= $fieldinfo['mandatory'];
				foreach($describeInfo['fields'] as $describeField) {
					if ($describeField['name']== $fieldname) {
						$field['type'] = array();
						if (isset($describeField['type']) && $describeField['type']!='') {
							$picklistValues = $describeField['type']['picklistValues'];
							$field['type']['value'] = array ('value' =>$picklistValues,'name' => $fieldname);
						}
					}
				}
				if($field['uitype'] == '51' || $field['uitype'] == '59' || $field['uitype'] == '10'){
					$field['relatedmodule'] = crmtogo_WS_Utils::getEntityName($field['name'], $module);
				}
				// 17-02-2016 Leo - Select the user who is using the application.
				if ($field['uitype'] == '53') {
					$prefix = crmtogo_WS_Utils::getEntityModuleWSId('Users') ;
					$assigned_user_id = $prefix."x".$current_user->id;
					$fieldvalue = crmtogo_WS_Utils::getassignedtoValues($current_user,$module,$assigned_user_id);;
			        $field['type']['value'] = array('value' => $fieldvalue, 'name' =>$fieldname);
				}
				$fields[] = $field;
			}
			$blocks[] = array( 'label' => $blocklabel, 'fields' => $fields );
		}
		$sections = array();
		$moduleFieldGroupKeys = array_keys($moduleFieldGroups);
		foreach($moduleFieldGroupKeys as $blocklabel) {
			// eliminate empty blocks
			if(isset($groups[$blocklabel]) && !empty($groups[$blocklabel])) {
				$sections[] = array( 'label' => $blocklabel, 'count' => count($groups[$blocklabel]) );
			}
		}
		$modifiedResult = array('blocks' => $blocks, 'id' => $resultRecord['id']);
		if($labelFields) {
			$modifiedResult['labelFields'] = $labelFields;
		}
		return $modifiedResult;
	}
	
	static function searcharray($value, $key, $array) {
	   foreach ($array as $k => $val) {
		   if ($val[$key] == $value) {
			  return $val['label'];
		   }
	   }
	}

}