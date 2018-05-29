<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/

require_once("PortalConfig.php");
require_once('nusoap/lib/nusoap.php');

global $Server_Path;
global $client;

$client = new nusoap_client($Server_Path."/vtigerservice.php?service=customerportal", false, $proxy_host, $proxy_port, $proxy_username, $proxy_password,0,100);

//We have to overwrite the character set which was set in nusoap/lib/nusoap.php file (line 87)
$client->soap_defencoding = $default_charset;

?>
