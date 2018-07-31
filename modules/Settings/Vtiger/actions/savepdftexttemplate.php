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

require_once('include/utils/utils.php');
global $log, $adb;
$texttype = strtolower(vtlib_purify($_REQUEST["displaymodul"]));
$textmodules = vtlib_purify($_REQUEST["textmodules"]);

$textmodules_array = array('quotes'=>'qu','invoices'=>'in','sorders'=>'so','porders'=>'po');
$text_databases = array('letter'=>'berli_multistarttext','conclusion'=>'berli_multiendtext');
$text_databases_tables['berli_multistarttext'] = array('starttextid','starttexttitle','multistext','texttypes');
$text_databases_tables['berli_multiendtext'] = array('endtextid','endtexttitle','multietext','texttype');
$log->debug("the template is for ".$textmodules." and of type ".$texttype );
$templatename = from_html(urldecode (vtlib_purify($_REQUEST["templatename"])));
$log->debug("the templatename is ".$templatename);
$templateid = vtlib_purify($_REQUEST["templateid"]);
$log->debug("the templateid is ".$templateid);
$templatetext = urldecode (vtlib_purify($_REQUEST["body"]));
$log->debug("the body is ".$templatetext); 

if(isset($templateid) && $templateid !='') {
	$log->info("an existing template will be edited");  
	$sql = "update ".$text_databases[$texttype]." set ".$text_databases_tables[$text_databases[$texttype]][0]." =?, ".$text_databases_tables[$text_databases[$texttype]][1]." =?, ".$text_databases_tables[$text_databases[$texttype]][2]." =?, ".$text_databases_tables[$text_databases[$texttype]][3]." =? where ".$text_databases_tables[$text_databases[$texttype]][0]." =?";
	$params = array($templateid,$templatename, $templatetext, $textmodules_array[$textmodules], $templateid);
	$result = $adb->pquery($sql, $params);
	echo 'OK';
	$log->info("updated existing text template in db");  
	exit;  
}
else {
	$log->info("an new template will be created");  
	$templateid = $adb->getUniqueID($text_databases[$texttype]);
	$sql = "insert into ".$text_databases[$texttype]." values (?,?,?,?)";
	$params = array(Null,$templatename, $templatetext, $textmodules_array[$textmodules]);
	$result = $adb->pquery($sql, $params);
	echo 'OK';
	$log->info("added a new text template to the db ");
	exit;  
}
?>