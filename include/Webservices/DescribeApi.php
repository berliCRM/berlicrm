<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License.
 *************************************************************************************/

require_once 'include/Webservices/OpenApiGenerator.php';

/**
 * Return the public OpenAPI contract for all registered REST operations.
 *
 * @berli-openapi-begin
 * {
 *   "summary": "Describe the REST API",
 *   "description": "Returns the current OpenAPI 3.0.3 contract assembled from the webservice registry and structured handler metadata.",
 *   "tags": ["Documentation"],
 *   "parameters": {},
 *   "result": {
 *     "schema": {"type": "object", "required": ["openapi", "info", "paths"], "properties": {"openapi": {"type": "string", "example": "3.0.3"}, "info": {"type": "object"}, "paths": {"type": "object"}}, "additionalProperties": true},
 *     "example": {"openapi": "3.0.3", "info": {"title": "berliCRM REST Webservices", "version": "0.22"}, "paths": {}}
 *   }
 * }
 * @berli-openapi-end
 */
function vtws_describeapi()
{
    global $adb;
    $generator = new BerliCRM_OpenApiGenerator($adb, dirname(dirname(__DIR__)));
    return $generator->generate();
}

