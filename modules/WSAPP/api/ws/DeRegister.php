<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
require_once 'modules/WSAPP/SyncServer.php';
/**
 * @berli-openapi-begin
 * {"summary":"Deregister a synchronization client","description":"Removes a WSAPP synchronization registration for the authenticated user.","tags":["WSAPP"],"parameters":{"type":{"required":true,"description":"Synchronization application type.","example":"Outlook"},"key":{"required":true,"description":"Registration key returned by wsapp_register.","example":"client-key"}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"status":"success"}}}
 * @berli-openapi-end
 */
function wsapp_deregister ($type, $key, $user) {

	$instance = new SyncServer();
	return $instance->deregister($type, $key, $user);
}
