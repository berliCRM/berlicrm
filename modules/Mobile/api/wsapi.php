<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**********************************************************************
 * Expose the extensions added over webservice API
 */

/**
 * @berli-openapi-begin
 * {"summary":"List mobile alerts","description":"Returns all alerts available to the authenticated mobile client.","tags":["Mobile"],"parameters":{},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"alerts":[]}}}
 * @berli-openapi-end
 */
function mobile_ws_fetchAllAlerts($user) {
	$request = new Mobile_API_Request();
	return Mobile_WS_API::process($request, $user, 'Mobile_WS_FetchAllAlerts', 'ws/FetchAllAlerts.php');
}

/**
 * @berli-openapi-begin
 * {"summary":"Get mobile alert details","description":"Returns details and the display message for one mobile alert.","tags":["Mobile"],"parameters":{"alertid":{"required":true,"description":"Mobile alert identifier.","example":"1"}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"alertid":"1","message":"Alert details"}}}
 * @berli-openapi-end
 */
function mobile_ws_alertDetailsWithMessage($alertid, $user) {
	$request = new Mobile_API_Request();
	$request->set('alertid', $alertid);
	return Mobile_WS_API::process($request, $user, 'Mobile_WS_AlertDetailsWithMessage', 'ws/AlertDetailsWithMessage.php');
}

/**
 * @berli-openapi-begin
 * {"summary":"List mobile module filters","description":"Returns accessible list filters for a CRM module.","tags":["Mobile"],"parameters":{"module":{"required":true,"description":"CRM module name.","example":"Contacts"}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"filters":[]}}}
 * @berli-openapi-end
 */
function mobile_ws_fetchModuleFilters($module, $user) {
	$request = new Mobile_API_Request();
	$request->set('module', $module);
	return Mobile_WS_API::process($request, $user, 'Mobile_WS_FetchModuleFilters', 'ws/FetchModuleFilters.php');
}

/**
 * @berli-openapi-begin
 * {"summary":"Fetch a mobile record","description":"Returns a record formatted for the mobile client.","tags":["Mobile"],"parameters":{"record":{"required":true,"description":"Record webservice ID.","example":"12x345"}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"record":{"id":"12x345"}}}}
 * @berli-openapi-end
 */
function mobile_ws_fetchRecord($record, $user) {
	$request = new Mobile_API_Request();
	$request->set('record', $record);
	return Mobile_WS_API::process($request, $user, 'Mobile_WS_FetchRecord', 'ws/FetchRecord.php');
}

/**
 * @berli-openapi-begin
 * {"summary":"Fetch a grouped mobile record","description":"Returns a record with fields grouped for mobile display.","tags":["Mobile"],"parameters":{"record":{"required":true,"description":"Record webservice ID.","example":"12x345"}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"record":{"id":"12x345"},"blocks":[]}}}
 * @berli-openapi-end
 */
function mobile_ws_fetchRecordWithGrouping($record, $user) {
	$request = new Mobile_API_Request();
	$request->set('record', $record);
	return Mobile_WS_API::process($request, $user, 'Mobile_WS_FetchRecordWithGrouping', 'ws/FetchRecordWithGrouping.php');
}

/**
 * @berli-openapi-begin
 * {"summary":"Get mobile filter details and count","description":"Returns filter metadata together with the matching record count.","tags":["Mobile"],"parameters":{"filterid":{"required":true,"description":"CRM custom-view filter ID.","example":"1"}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"filterid":"1","count":0}}}
 * @berli-openapi-end
 */
function mobile_ws_filterDetailsWithCount($filterid, $user) {
	$request = new Mobile_API_Request();
	$request->set('filterid', $filterid);
	return Mobile_WS_API::process($request, $user, 'Mobile_WS_FilterDetailsWithCount', 'ws/FilterDetailsWithCount.php');
}

/**
 * @berli-openapi-begin
 * {"summary":"List mobile module records","description":"Returns a page of module records using mobile filter, search and paging options.","tags":["Mobile"],"parameters":{"elements":{"required":true,"description":"JSON object containing module, filter, search and paging options.","example":"{\"module\":\"Contacts\",\"page\":0}","decodedSchema":{"type":"object","required":["module"],"additionalProperties":true}}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"records":[],"page":0}}}
 * @berli-openapi-end
 */
function mobile_ws_listModuleRecords($elements, $user) {
	$request = new Mobile_API_Request($elements); // elements can have key (module, alertid, filterid, search, page)
	return Mobile_WS_API::process($request, $user, 'Mobile_WS_ListModuleRecords', 'ws/ListModuleRecords.php');
}

/**
 * @berli-openapi-begin
 * {"summary":"Save a mobile record","description":"Creates or updates a module record from mobile field values.","tags":["Mobile"],"parameters":{"module":{"required":true,"description":"CRM module name.","example":"Contacts"},"record":{"required":false,"description":"Existing record webservice ID; leave empty to create.","example":"12x345"},"values":{"required":true,"description":"JSON object of field values.","example":"{\"lastname\":\"Doe\"}","decodedSchema":{"type":"object","additionalProperties":true}}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"record":{"id":"12x345","lastname":"Doe"}}}}
 * @berli-openapi-end
 */
function mobile_ws_saveRecord($module, $record, $values, $user) {
	$request = new Mobile_API_Request();
	$request->set('module', $module);
	$request->set('record', $record);
	$request->set('values', $values);
	return Mobile_WS_API::process($request, $user, 'Mobile_WS_SaveRecord', 'ws/SaveRecord.php');
}

/**
 * @berli-openapi-begin
 * {"summary":"Synchronize mobile module records","description":"Returns a page of module changes after a mobile synchronization token.","tags":["Mobile"],"parameters":{"module":{"required":true,"description":"CRM module name.","example":"Contacts"},"syncToken":{"required":false,"description":"Token returned by a previous synchronization call.","example":"1710000000"},"page":{"required":false,"description":"Zero-based page number.","example":"0","default":"0"}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"records":[],"syncToken":"1710000000","page":0}}}
 * @berli-openapi-end
 */
function mobile_ws_syncModuleRecords($module, $syncToken, $page, $user) {
	$request = new Mobile_API_Request();
	$request->set('module', $module);
	$request->set('syncToken', $syncToken);
	$request->set('page', $page);
	return Mobile_WS_API::process($request, $user, 'Mobile_WS_SyncModuleRecords', 'ws/SyncModuleRecords.php');
}

/**
 * @berli-openapi-begin
 * {"summary":"Query mobile records","description":"Executes a module query and returns one page formatted for mobile use.","tags":["Mobile"],"parameters":{"module":{"required":true,"description":"CRM module name.","example":"Contacts"},"query":{"required":false,"description":"Mobile search query.","example":"Doe"},"page":{"required":false,"description":"Zero-based page number.","example":"0","default":"0"}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"records":[],"page":0}}}
 * @berli-openapi-end
 */
function mobile_ws_query($module, $query, $page, $user) {
	$request = new Mobile_API_Request();
	$request->set('module', $module);
	$request->set('query', $query);
	$request->set('page', $page);
	return Mobile_WS_API::process($request, $user, 'Mobile_WS_Query', 'ws/Query.php');
}

/**
 * @berli-openapi-begin
 * {"summary":"Query grouped mobile records","description":"Executes a module query and returns grouped mobile results.","tags":["Mobile"],"parameters":{"module":{"required":true,"description":"CRM module name.","example":"Contacts"},"query":{"required":false,"description":"Mobile search query.","example":"Doe"},"page":{"required":false,"description":"Zero-based page number.","example":"0","default":"0"}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"groups":[],"page":0}}}
 * @berli-openapi-end
 */
function mobile_ws_queryWithGrouping($module, $query, $page, $user) {
	$request = new Mobile_API_Request();
	$request->set('module', $module);
	$request->set('query', $query);
	$request->set('page', $page);
	return Mobile_WS_API::process($request, $user, 'Mobile_WS_QueryWithGrouping', 'ws/QueryWithGrouping.php');
}
/**********************************************************************/

/**
 * Mobile WS API Controller
 */
include_once dirname(__FILE__) . '/Request.php';
include_once dirname(__FILE__) . '/Response.php';
include_once dirname(__FILE__) . '/Session.php';
include_once dirname(__FILE__) . '/ws/Controller.php';

class Mobile_WS_API {
	private $controller;
	
	function initController($className, $handlerPath, $user) {
		include_once dirname(__FILE__) . "/$handlerPath";
		
		$this->controller = new $className();
		Mobile_API_Session::init(session_id());
		$this->controller->initActiveUser($user);
		return $this->controller;
	}
	
	function getController() {
		return $this->controller;
	}
	
	static function process(Mobile_API_Request $request, $user, $className, $handlerPath) {
		
		if(vtlib_isModuleActive('Mobile') === false) {
			throw new WebServiceException('1501', 'Service not available');
		}
		
		$wsapiController = new self();
		$response = $wsapiController->initController($className, $handlerPath, $user)->process($request);
		if($response->hasError()) {
			$error = $response->getError();
			throw new WebServiceException($error['code'], $error['message']);
		}
		return $response->getResult();
	} 
	
}
