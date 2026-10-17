<?php
/*+*******************************************************************************
 *  The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *
 *********************************************************************************/

require_once 'include/Webservices/Retrieve.php';

/**
 * Retrieve inventory record with LineItems.
 *
 * @berli-openapi-begin
 * {"summary":"Retrieve an inventory record with line items","description":"Returns an accessible inventory record together with its product and service line items.","tags":["Inventory"],"parameters":{"id":{"required":true,"description":"Inventory record webservice ID.","example":"7x144420"}},"result":{"schema":{"allOf":[{"$ref":"#/components/schemas/Entity"},{"type":"object","required":["LineItems"],"properties":{"LineItems":{"type":"array","items":{"type":"object","additionalProperties":true}}}}]},"example":{"id":"7x144420","LineItems":[]}}}
 * @berli-openapi-end
 */
function vtws_retrieve_inventory($id){
	global $current_user;

	$record = vtws_retrieve($id, $current_user);

	$handler = vtws_getModuleHandlerFromName('LineItem', $user);
    $id = vtws_getIdComponents($id);
    $id = $id[1];
	$inventoryLineItems = $handler->getAllLineItemForParent($id);

	$record['LineItems'] = $inventoryLineItems;

	return $record;
}

?>
