<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
require_once 'modules/WSAPP/Utils.php';

/**
 * @berli-openapi-begin
 * {"summary":"Map an external synchronization record","description":"Maps an application record to its CRM representation for the registered WSAPP client.","tags":["WSAPP"],"parameters":{"key":{"required":true,"description":"Registered synchronization client key.","example":"client-key"},"element":{"required":true,"description":"JSON external record to map.","example":"{\"module\":\"Contacts\",\"id\":\"external-1\"}","decodedSchema":{"type":"object","additionalProperties":true}}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"id":"12x345"}}}
 * @berli-openapi-end
 */
function wsapp_map ($key, $element, $user) {
        $name = wsapp_getApplicationName($key);
        $handlerDetails  = wsapp_getHandler($name);
        require_once $handlerDetails['handlerpath'];
        $handler = new $handlerDetails['handlerclass']($key);
		return $handler->map($element, $user);
}
