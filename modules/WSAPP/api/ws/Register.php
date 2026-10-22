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
 * {"summary":"Register a synchronization client","description":"Registers a WSAPP client and synchronization mode for the authenticated user.","tags":["WSAPP"],"parameters":{"type":{"required":true,"description":"Synchronization application type.","example":"Outlook"},"synctype":{"required":true,"description":"Requested synchronization mode.","example":"manual"}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"key":"client-key"}}}
 * @berli-openapi-end
 */
function wsapp_register ($type,$syncType, $user) {
	$instance = new SyncServer();
	return $instance->register($type,$syncType,$user);
}
