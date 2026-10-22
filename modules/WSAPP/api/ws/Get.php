<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
require_once 'include/Webservices/GetUpdates.php';
require_once 'modules/WSAPP/Utils.php';

/**
 * @berli-openapi-begin
 * {"summary":"Download synchronization changes","description":"Returns WSAPP changes for a module after the supplied synchronization token.","tags":["WSAPP"],"parameters":{"key":{"required":true,"description":"Registered synchronization client key.","example":"client-key"},"module":{"required":true,"description":"CRM module name.","example":"Contacts"},"token":{"required":false,"description":"Token returned by the previous synchronization response.","example":"1710000000"}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"created":[],"updated":[],"deleted":[],"token":"1710000000"}}}
 * @berli-openapi-end
 */
function wsapp_get ($key, $module, $token, $user) {
        $name = wsapp_getApplicationName($key);
        $handlerDetails  = wsapp_getHandler($name);
        require_once $handlerDetails['handlerpath'];
        $handler = new $handlerDetails['handlerclass']($key);
        return $handler->get($module,$token,$user);
}
