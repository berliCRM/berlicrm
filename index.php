<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

//Overrides GetRelatedList : used to get related query
//TODO : Eliminate below hacking solution

if (!file_exists('vendor/autoload.php')) {
    installComposer();
}

include_once 'include/Webservices/Relation.php';
include_once 'vtlib/Vtiger/Module.php';
include_once 'includes/main/WebUI.php';

$webUI = new Vtiger_WebUI();
$webUI->process(new Vtiger_Request($_REQUEST, $_REQUEST));

function installComposer() {
	$cmd = 'composer install 2>&1';

	$output = [];
	$returnCode = 0;
	exec($cmd, $output, $returnCode);
	
	file_put_contents('logs/installLog.txt', implode(PHP_EOL, $output), FILE_APPEND);

	try {
		require_once 'vendor/autoload.php';
	} catch (\Throwable $th) {
		$returnCode = 1;
	}
	return $returnCode === 0;
}