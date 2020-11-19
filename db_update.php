<!DOCTYPE html><html><head><title>BerliCRM updater</title><style>body { font-family: Open sans,sans-serif;}</style>
<?php
require_once 'includes/main/WebUI.php';
require_once 'include/utils/utils.php';
include_once 'vtlib/Vtiger/Module.php';
include_once 'include/Webservices/Utils.php';
require_once 'vtlib/Vtiger/Package.php';
require_once 'vtigerversion.php';
ini_set('display_errors','on'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
global $adb;

$res = $adb->query("SELECT tag_version FROM vtiger_version");
$installedtag = $adb->query_result($res,0,'tag_version');
if ($installedtag == $current_release_tag) {
	die("This installation of berliCRM is up to date.");
}

echo "<h1>Updating from $installedtag to $current_release_tag..</h1>";

echo "Remove special UI types of salutation and name fields in leads and contacts...";
$query = "UPDATE vtiger_field SET uitype = '15', displaytype = 1, summaryfield = 1 WHERE columnname = 'salutation'";
$adb->pquery($query, array());
$query = "UPDATE vtiger_field SET uitype = '1' WHERE columnname = 'firstname'";
$adb->pquery($query, array());
$query = "UPDATE vtiger_field SET uitype = '2' WHERE columnname = 'lastname'";
$adb->pquery($query, array());
echo " done<br>";


//  Import Cron correction
echo "Proper text for Import Cron (if it exists)... ";
$query = "UPDATE vtiger_cron_task SET description = ? WHERE module = ?;";
$res = $adb->pquery($query, array('The recommended frequency for Imports is 15 minutes.', 'Import'));
if ($res) {
	echo "done<br>";
} else {
	echo "failed<br>";
}

// add new Webservices
echo "New web services:<br>";
// check existance (for migrated clients)
$query = "SELECT * FROM `vtiger_ws_operation` WHERE `name` = 'retrievedocattachment'";
$res = $adb->pquery($query, array());
if ($adb->num_rows($res) > 0) {
	// add web service: retrievedocattachment
	$operationId = vtws_addWebserviceOperation('retrievedocattachment','include/Webservices/RetrieveDocAttachment.php','berli_retrievedocattachment','Get','0');
	vtws_addWebserviceOperationParam($operationId,'id','string','1');
	vtws_addWebserviceOperationParam($operationId,'returnfile','string','2');
	echo "retrievedocattachment added<br>";
}
else {
	echo "retrievedocattachment already exists<br>";
}
// check existance (for migrated clients)
$query = "SELECT * FROM `vtiger_ws_operation` WHERE `name` = 'update_product_relations'";
$res = $adb->pquery($query, array());
if ($adb->num_rows($res) > 0) {
	// add web service: update_product_relations
	$operationId = vtws_addWebserviceOperation('update_product_relations','include/Webservices/Custom/ProductRelation.php','vtws_update_product_relations','POST','0');
	vtws_addWebserviceOperationParam($operationId,'productid','string','1');
	vtws_addWebserviceOperationParam($operationId,'relids','string','2');
	vtws_addWebserviceOperationParam($operationId,'preserve','string','3');
	echo "update_product_relations added<br>";
}
else {
	echo "update_product_relations already exists<br>";
}
// check existance (for migrated clients)
$query = "SELECT * FROM `vtiger_ws_operation` WHERE `name` = 'get_multi_relations'";
$res = $adb->pquery($query, array());
if ($adb->num_rows($res) > 0) {
	// add web service: get_multi_relations
	$operationId = vtws_addWebserviceOperation('get_multi_relations','include/Webservices/Custom/getMultiRelations.php','berli_get_multi_relations','Get','0');
	vtws_addWebserviceOperationParam($operationId,'id','string','1');
	echo "get_multi_relations added<br>";
}
else {
	echo "get_multi_relations already exists<br>";
}

// update vtiger_smsnotifier_servers structure (if required)
echo "<br>Update vtiger_smsnotifier_servers structure (if required)<br>Table ";
$query = "SHOW COLUMNS FROM vtiger_smsnotifier_servers LIKE 'countryprefix';";
$res = $adb->pquery($query, array());
if ($res) {
	echo "found";
	if ($adb->num_rows($res) < 1) {
		echo " and update required";
		$query = "ALTER TABLE vtiger_smsnotifier_servers ADD `countryprefix` VARCHAR (5) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''";
		$res = $adb->pquery($query, array());
		if ($res) {
			echo "<br>Update successful";
		} else {
			echo "<br>Update NOT successful";
		}
	} else {
		echo " and update NOT required";
	}
} else {
	echo "not found";
}

//// adding fields and index missing from modcomments manifest before v1.0.11

echo '<br>Alter missing modcomments fields... ';

$moduleInstance = Vtiger_Module::getInstance('ModComments');
if($moduleInstance) {
	$customer = Vtiger_Field::getInstance('customer', $moduleInstance);
	if (!$customer) {
		$customer = new Vtiger_Field();
		$customer->name = 'customer';
		$customer->label = 'Customer';
		$customer->uitype = '10';
		$customer->displaytype = '3';
		$blockInstance = Vtiger_Block::getInstance('LBL_MODCOMMENTS_INFORMATION', $moduleInstance);
		$blockInstance->addField($customer);
		$customer->setRelatedModules(array('Contacts'));
	}

	$modCommentsUserId = Vtiger_Field::getInstance("userid", $moduleInstance);
	if(!$modCommentsUserId){
		$blockInstance = Vtiger_Block::getInstance('LBL_MODCOMMENTS_INFORMATION', $moduleInstance);
		$userId = new Vtiger_Field();
		$userId->name = 'userid';
		$userId->label = 'UserId';
		$userId->uitype = '10';
		$userId->displaytype = '3';
		$blockInstance->addField($userId);
	}

	$modCommentsReasonToEdit = Vtiger_Field::getInstance("reasontoedit", $moduleInstance);
	if(!$modCommentsReasonToEdit){
		$blockInstance = Vtiger_Block::getInstance('LBL_MODCOMMENTS_INFORMATION', $moduleInstance);
		$reasonToEdit = new Vtiger_Field();
		$reasonToEdit->name = 'reasontoedit';
		$reasonToEdit->label = 'ReasonToEdit';
		$reasonToEdit->uitype = '19';
		$reasonToEdit->displaytype = '1';
		$blockInstance->addField($reasonToEdit);
	}
    $adb->query("ALTER TABLE `vtiger_modcomments` ADD PRIMARY KEY ( `modcommentsid` )");
}

echo 'done<br>';

echo 'Creating ws_fieldtype entry for new uitype...';
$adb->query("INSERT INTO vtiger_ws_fieldtype (`uitype` ,`fieldtype`) VALUES ('cr16', 'autocompletedtext')");
echo 'done<br>';

$moduleInstance = Vtiger_Module::getInstance("Mailchimp");
if($moduleInstance) {
    echo "<br>delete not needed files for new Mailchimp version ";
    require_once('config.inc.php');
    $filePath = "modules/Mailchimp/actions/logfileWriter.php";
    $file = $root_directory.''.$filePath;
    unlink($file);
    $filePath = "modules/Mailchimp/actions/MailchimpSyncStep1.php";
    $file = $root_directory.''.$filePath;
    unlink($file);
    $filePath = "modules/Mailchimp/actions/MailchimpSyncStep2.php";
    $file = $root_directory.''.$filePath;
    unlink($file);
    $filePath = "modules/Mailchimp/actions/MailchimpSyncStep3.php";
    $file = $root_directory.''.$filePath;
    unlink($file);
    $filePath = "modules/Mailchimp/actions/MailchimpSyncStep4.php";
    $file = $root_directory.''.$filePath;
    unlink($file);
    echo "file deletion done.<br>";
}


echo '<br>Creating ws_fieldtype entry for new uitype...';
$adb->query("INSERT INTO vtiger_ws_fieldtype (`uitype` ,`fieldtype`) VALUES ('crs16', 'autocompletedsingleuse')");
echo 'done<br>';

echo "<br>Change uitype of certain numberfields that were text until now... ";
$query = "UPDATE `vtiger_field` SET uitype = 7 WHERE uitype = 1 AND typeofdata LIKE 'N%';";
$adb->pquery($query, array());
echo "uitype change done.<br>";

echo "<br>Delete files not needed for new SMS Notifier version ";
require_once('config.inc.php');
$filePath = "modules/SMSNotifier/views/CheckStatus.php";
$file = $root_directory.''.$filePath;
unlink($file);
echo "file deletion done.<br>";

echo "<br>Set constraint for SMS Notifier tables";
$query = "delete vtiger_smsnotifier FROM vtiger_smsnotifier
	LEFT JOIN vtiger_crmentity ON ( vtiger_crmentity.crmid = vtiger_smsnotifier.smsnotifierid)
	WHERE vtiger_smsnotifier.smsnotifierid IS NOT NULL
	AND vtiger_crmentity.crmid IS NULL;";
$adb->pquery($query, array());
$query = "ALTER TABLE `vtiger_smsnotifier` ADD CONSTRAINT `fk_crmid_vtiger_smsnotifier` FOREIGN KEY (`smsnotifierid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE;";
$adb->pquery($query, array());
echo " set constraint done for vtiger_smsnotifier.<br>";


$query = "delete vtiger_smsnotifiercf FROM vtiger_smsnotifiercf
	LEFT JOIN vtiger_crmentity ON ( vtiger_crmentity.crmid = vtiger_smsnotifiercf.smsnotifierid)
	WHERE vtiger_smsnotifiercf.smsnotifierid IS NOT NULL
	AND vtiger_crmentity.crmid IS NULL;";
$adb->pquery($query, array());
$query = "ALTER TABLE `vtiger_smsnotifiercf` ADD CONSTRAINT `fk_crmid_vtiger_smsnotifiercf` FOREIGN KEY (`smsnotifierid`) REFERENCES `vtiger_smsnotifier` (`smsnotifierid`) ON DELETE CASCADE;";
$adb->pquery($query, array());
echo " set constraint done for vtiger_smsnotifiercf.<br>";

echo "add primary key to vtiger_smsnotifier<br>";
$query = "ALTER TABLE vtiger_smsnotifier ADD PRIMARY KEY(smsnotifierid)";
$adb->pquery($query, array());
echo "add primary key done.<br>";

echo "<br>Delete files not needed for new CKeditor version... ";
require_once('config.inc.php');
$filePath = "libraries/jquery/ckeditor/ckeditor_php4.php";
$file = $root_directory.''.$filePath;
unlink($file);
$filePath = "libraries/jquery/ckeditor/ckeditor_php5.php";
$file = $root_directory.''.$filePath;
unlink($file);
echo "done.<br>";


echo "<br>delete old theme<br>";
$dirPath = "libraries/jquery/ckeditor/skins/icy_orange/";
$dir = $root_directory.''.$dirPath;

if (is_dir($dir)) {
  foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $filename) {
    if ($filename->isDir()) continue;
    unlink($filename);
  }
}
$dirPath = "libraries/jquery/ckeditor/skins/icy_orange/images/hidpi/";
$dir = $root_directory.''.$dirPath;
rmdir($dir);
$dirPath = "libraries/jquery/ckeditor/skins/icy_orange/images/";
$dir = $root_directory.''.$dirPath;
rmdir($dir);
$dirPath = "libraries/jquery/ckeditor/skins/icy_orange/";
$dir = $root_directory.''.$dirPath;
rmdir($dir);
echo "dir deletion done.<br>";


echo "<br>delete not needed lang files... ";
$dirPath = "libraries/jquery/ckeditor/plugins/a11yhelp/lang/";
$dir = $root_directory.''.$dirPath;

if (is_dir($dir)) {
  foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $filename) {
    if ($filename->isDir()) continue;
    unlink($filename);
  }
}
rmdir($dir);
echo "lang files deletion done.<br>";


echo "Remove berliSoftphone as entity... ";
$query = "DELETE FROM `vtiger_ws_entity` WHERE `vtiger_ws_entity`.`name` = 'berliSoftphones';";
$adb->pquery($query, array());
echo "uitype change done.<br>";

//Update modules
echo 'Updating modules where applicable...<ul>';
$moduleFolders = array('packages/vtiger/mandatory', 'packages/vtiger/optional');
foreach($moduleFolders as $moduleFolder) {
	if ($handle = opendir($moduleFolder)) {
		while (false !== ($file = readdir($handle))) {
			$packageNameParts = explode(".",$file);
			if($packageNameParts[count($packageNameParts)-1] != 'zip'){
				continue;
			}
			array_pop($packageNameParts);
			$packageName = implode("",$packageNameParts);
			$packagepath = "$moduleFolder/$file";
			$package = new Vtiger_Package();
			$module = $package->getModuleNameFromZip($packagepath);
			if($module != null) {
				$moduleInstance = Vtiger_Module::getInstance($module);
				$oldver = $moduleInstance->version;
				if ($oldver) {
					echo "<li>Found v{$moduleInstance->version} of $module... ";
					if($moduleInstance) {
						try {
							updateVtlibModule($module, $packagepath);
							$moduleInstance = Vtiger_Module::getInstance($module);
							if ($moduleInstance->version != $oldver) {
								echo "<b>successfully updated to v{$moduleInstance->version}</b>.";
							}
							else {
								echo "no update required";
							}
						} catch (Exception $e) {
							echo "Exception while updating: ",  $e->getMessage();
						}
					}
					echo "</li>";
				}
			}
		}
		closedir($handle);
	}
}
echo '</ul>Finished updating modules.<br>';


$module = Vtiger_Module::getInstance('Vendors');
if($module) {
    echo "<br>Add Documents related list to Vendor";
    // avoid duplicates
    $module->unsetRelatedList(Vtiger_Module::getInstance('Documents'), 'Documents','get_attachments');
    $module->setRelatedList(Vtiger_Module::getInstance('Documents'), 'Documents',Array('ADD','SELECT'),'get_attachments');
    echo "Related List added<br>";
}

echo "<br>Alter com_vtiger_workflowtasks.task to MEDIUMTEXT if applicable..";
$adb->pquery("ALTER TABLE `com_vtiger_workflowtasks` CHANGE `task` `task` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL");

echo "<br>Alter vtiger_mailmanager_mailrecord.mbody to MEDIUMTEXT if applicable..";
$adb->pquery("ALTER TABLE `vtiger_mailmanager_mailrecord` CHANGE `mbody` `mbody` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL");

echo "<br>Alter vtiger_berlicleverreach_settings.accesstoken to VARCHAR(600) if applicable..";
$adb->pquery("ALTER TABLE `vtiger_berlicleverreach_settings` CHANGE `accesstoken` `accesstoken` VARCHAR( 600 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL");

echo "<br>update crmtogo settings if applicable..";
$result = $adb->pquery("Select crmtogo_user  from berli_crmtogo_modules group by crmtogo_user", array());
$num_rows=$adb->num_rows($result);
for($i=0;$i<$num_rows;$i++) {
	$userid=$adb->query_result($result,$i,'crmtogo_user');
	$checkresult = $adb->pquery("Select * from berli_crmtogo_modules where crmtogo_module = 'Events' and crmtogo_user =?", array($userid));
	$checknum_rows=$adb->num_rows($checkresult);
	if ($checknum_rows==0) {
		if(vtlib_isModuleActive('Calendar') === true) {
			$seq_result = $adb->pquery("SELECT `order_num` FROM `berli_crmtogo_modules` WHERE `crmtogo_user` = ? ORDER BY order_num DESC LIMIT 1", array($userid));
			$seq=$adb->query_result($seq_result,0,'order_num');		
			$adb->pquery("INSERT INTO `berli_crmtogo_modules` (`crmtogo_user`, `crmtogo_module`, `crmtogo_active`, `order_num`) VALUES (?, ?, ?, ?)", array($userid,'Events', '1', $seq+1));
		}
		
	}
}

// recreate tabdata files
create_tab_data_file();
create_parenttab_data_file();

echo 'module berliSoftphones update start<br>';
//update berliSoftphones module
$moduleFolders = array('packages/vtiger/mandatory', 'packages/vtiger/optional');
foreach($moduleFolders as $moduleFolder) {
	if ($handle = opendir($moduleFolder)) {
		while (false !== ($file = readdir($handle))) {
			$packageNameParts = explode(".",$file);
			if($packageNameParts[count($packageNameParts)-1] != 'zip'){
				continue;
			}
			array_pop($packageNameParts);
			$packageName = implode("",$packageNameParts);
			if ($packageName =='berliSoftphones') {
				$packagepath = "$moduleFolder/$file";
				$package = new Vtiger_Package();
				$module = $package->getModuleNameFromZip($packagepath);
				if($module != null) {
					$moduleInstance = Vtiger_Module::getInstance($module);
					if($moduleInstance) {
						updateVtlibModule($module, $packagepath);
					} 
					else {
						installVtlibModule($module, $packagepath);
					}
				}
			}
		}
		closedir($handle);
	}
}
echo 'module update berliSoftphones done <br>';

if (version_compare($installedtag, $current_release_tag) < 0) {
	echo 'Add INDEX to vtiger_email_track<br>';
	$query = "ALTER TABLE `vtiger_email_track` ADD INDEX (`mailid`);";
	$adb->pquery($query, array());
	echo 'Adding INDEX done<br>';
}

echo 'module update berliCleverReach start<br>';
$moduleInstance = Vtiger_Module::getInstance("berliCleverReach");
if($moduleInstance) {
	updateVtlibModule("berliCleverReach", "packages/vtiger/optional/berliCleverReach.zip");
}
echo 'module update berliCleverReach done<br>';

$query = "UPDATE `vtiger_version` SET `tag_version` = ?";
$adb->pquery($query, array($current_release_tag));
echo "<h2>Finished updating to $current_release_tag!</h2>";
