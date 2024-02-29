<?php
/*+*******************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ********************************************************************************/

/**
 * Start the cron services configured.
 */
include_once 'vtlib/Vtiger/Cron.php';
require_once 'config.inc.php';
require_once('modules/Emails/mail.php');
require_once('modules/Users/Users.php');
require_once('includes/runtime/BaseModel.php');
require_once('includes/runtime/Globals.php');
require_once('includes/runtime/LanguageHandler.php');

if (file_exists('config_override.php')) {
	include_once 'config_override.php';
}

// Extended inclusions
require_once 'includes/Loader.php';
vimport ('includes.runtime.EntryPoint');

$version = explode('.', phpversion());

$php = ($version[0] * 10000 + $version[1] * 100 + $version[2]);
if($php <  50300){
    $hostName = php_uname('n');
} else {
    $hostName = gethostname();
}

if(PHP_SAPI === "cgi-fcgi" || empty($_SERVER['REMOTE_ADDR']) || (isset($_SESSION["authenticated_user_id"]) &&	isset($_SESSION["app_unique_key"]) && $_SESSION["app_unique_key"] == $application_unique_key)){

	$cronTasks = false;
	//crm-now: removed dependency on $_REQUEST = always execute all crons
	$cronTasks = Vtiger_Cron::listAllActiveInstances();


	$cronRunId = microtime(true);
	$cronStarts = date('Y-m-d H:i:s');

	//set global current user permissions
	global $current_user, $site_URL;
	$current_user = Users::getActiveAdminUser();
	  
	echo sprintf('[CRON],"%s",%s,Instance,"%s","",[STARTS]',$cronRunId,$site_URL,$cronStarts)."\n";
	foreach ($cronTasks as $cronTask) {
		try {
			$cronTask->setBulkMode(true);

			// Not ready to run yet?
			if (!$cronTask->isRunnable()) {
				echo sprintf("[INFO] %s - not ready to run as the time to run again is not completed\n", $cronTask->getName());
				continue;
			}

			// already running?
			if ($cronTask->isRunning()) {
				// check if it timed out too long ago, if > 24h then reset and inform admin
				$lastStart = $cronTask->getLastStart();
				$now = time();
				if ($lastStart == 0 || $now - $lastStart > 86400) {
					$subject = sprintf(vtranslate('LBL_CRON_TIMEOUT_SUBJECT'), $cronTask->getName(), $site_URL);
					$content = sprintf(vtranslate('LBL_CRON_TIMEOUT_CONTENT'), $site_URL, $cronTask->getName());
					send_mail('Settings', $current_user->email1, $current_user->user_name, $current_user->email1, $subject, $content);
					echo sprintf("[INFO] %s - running for more than 24h, informed admin and restarted", $cronTask->getName());
				// if time since last start < 24h just skip it
				} else {
					echo sprintf("[INFO] %s - not ready to run because it is running already", $cronTask->getName());
					continue;
				}
			}

			// Timeout could happen if intermediate cron-tasks fails
			// and affect the next task. Which need to be handled in this cycle.
			// doesn't work, 0 is returned from 'lastend' entry
			// if ($cronTask->hadTimedout()) {
				// echo sprintf("[INFO] %s - cron task had timedout as it is not completed last time it run- restarting\n", $cronTask->getName());	
			// }
			
			// Mark the status - running		
			$cronTask->markRunning();
			echo sprintf('[CRON],"%s",%s,%s,"%s","",[STARTS]',$cronRunId,$site_URL,$cronTask->getName(),date('Y-m-d H:i:s',$cronTask->getLastStart()))."\n";
			
			checkFileAccess($cronTask->getHandlerFile());		
			require_once $cronTask->getHandlerFile();
			
			// Mark the status - finished
			$cronTask->markFinished();
			echo "\n".sprintf('[CRON],"%s",%s,%s,"%s","%s",[ENDS]',$cronRunId,$site_URL,$cronTask->getName(),date('Y-m-d H:i:s',$cronTask->getLastStart()),date('Y-m-d H:i:s',$cronTask->getLastEnd()))."\n";
			
		} catch (Exception $e) {
			echo sprintf("[ERROR]: %s - cron task execution throwed exception.\n", $cronTask->getName());
			echo $e->getMessage();
			echo "\n";
		}		
	}

	$cronEnds = date('Y-m-d H:i:s');
	echo sprintf('[CRON],"%s",%s,Instance,"%s","%s",[ENDS]',$cronRunId,$site_URL,$cronStarts,$cronEnds)."\n";

}

else{
    echo("Access denied!");
}



?>
