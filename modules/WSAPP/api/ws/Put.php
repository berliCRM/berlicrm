<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
require_once 'include/Webservices/Create.php';
require_once 'include/Webservices/Update.php';
require_once 'include/Webservices/Delete.php';
require_once 'modules/WSAPP/Utils.php';

/**
 * @berli-openapi-begin
 * {"summary":"Upload synchronization changes","description":"Applies created, updated or deleted records from a registered WSAPP client.","tags":["WSAPP"],"parameters":{"key":{"required":true,"description":"Registered synchronization client key.","example":"client-key"},"element":{"required":true,"description":"JSON synchronization change set.","example":"{\"created\":[],\"updated\":[],\"deleted\":[]}","decodedSchema":{"type":"object","additionalProperties":true}}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"created":[],"updated":[],"deleted":[]}}}
 * @berli-openapi-end
 */
function wsapp_put ($key, $element, $user) {
        $name = wsapp_getApplicationName($key);
        $handlerDetails  = wsapp_getHandler($name);
        require_once $handlerDetails['handlerpath'];
        $handler = new $handlerDetails['handlerclass']($key);
        return $handler->put($element,$user);
}

