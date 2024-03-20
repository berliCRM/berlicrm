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
include_once dirname(__FILE__) . '/FetchRecord.php';
include_once 'include/Webservices/Create.php';
include_once 'include/Webservices/Update.php';

class crmtogo_WS_SaveRecord extends crmtogo_WS_FetchRecord {
	protected $recordValues = false;
	
	// Avoid retrieve and return the value obtained after Create or Update
	protected function processRetrieve(crmtogo_API_Request $request , $module='') {
		return $this->recordValues;
	}

	function process(crmtogo_API_Request $request) {
		$current_user = $this->getActiveUser();
		$module = $request->get('module');
		//update if recordid exist
		$recordid = $request->get('record');
		$valueArray = crmtogo_API_Request::getvaluemap($request);
		$values = '';
		if(!empty($valueArray) && is_string($valueArray)) {
			$values = Zend_Json::decode($valueArray);
		} 
		else {
			$values = $valueArray;
		}
		//catch error
		$response = new crmtogo_API_Response();
		if (empty($values)) {
			$response->setError(1501, "Values cannot be empty!");
			return $response;
		}
		try {
			// Retrieve or Initialize
			if (!empty($recordid)) {
				$this->recordValues = parent::processRetrieve($request, $module);
			} 
			else {
				$this->recordValues = array();
			}
		
			if ($module == 'Events' || $module == 'Calendar') {
				//Start Date and Time values
				$startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($values["time_start"]);
				/* Leo 11-04-2017 Problem with start and end time */
				$auxTime = $values["date_start"]." ".$startTime;
				$auxTime = crmtogo_WS_Utils::convertTimeZone($current_user, $auxTime);
				$startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($auxTime);
				/* Cont line 69 */
				list($startDate, $startTime) = explode(' ', $startDateTime);
				$values["time_start"] = $startTime;
				$values["date_start"] = $startDate;
				
				/* Leo 11-04-2017 Problem with start and end time */
				//End Date and Time values
				if (isset ($values["time_end"])) {
					$endTime = $values["time_end"];
					$endTime = Vtiger_Time_UIType::getTimeValueWithSeconds($endTime);
					$auxTime = $values["due_date"]." ".$endTime;
					$auxTime = crmtogo_WS_Utils::convertTimeZone($current_user, $auxTime);
				}
				else {
					$endTime = '00:00:00';
					$auxTime = $values["due_date"]." ".$endTime;
				}
				$endDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($auxTime);
				/*Fin */
				list($endDate, $endTime) = explode(' ', $endDateTime);
				$values["due_date"] = $endDate;
				$values["time_end"] = $endTime;
			}
			// Set the modified values
			foreach($values as $name => $value) {
				//for multi picklist remove _empty
				if (is_array($value)) {
					$value = array_flip($value);
					unset($value['_empty']);
					$value = array_flip($value);
				}
				$this->recordValues[$name] = $value; 
			}
			// assigned to group?
			if ($this->recordValues['assigntype']=='T') {
				$this->recordValues['assigned_user_id'] = $this->recordValues['assigned_group_id']; 
			}
			// Update or Create
			if (isset($this->recordValues['id'])) {
				$this->recordValues = vtws_update($this->recordValues, $current_user);
			} 
			else {
				// Set right target module name for Calendar/Event record
				if ($module == 'Calendar') {
					if (!empty($this->recordValues['eventstatus']) && $this->recordValues['activitytype'] != 'Task') {
						$module = 'Events';
					}
					// make sure visibility is not NULL
					if (empty($this->recordValues['visibility'])) {
						$this->recordValues['visibility'] = 'Public';
					}
					//unset (	$this->recordValues['time_end']);
				}
				$this->recordValues = vtws_create($module, $this->recordValues, $current_user);
			}
			// Update the record id
			$request->set('record', $this->recordValues['id']);
			$request->set('id', $this->recordValues['id']);
			
			// Gather response with full details
			$response = parent::process($request);
		} 
		catch(Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		return $response;
	}
	
}