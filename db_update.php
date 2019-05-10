<!DOCTYPE html><html><?php
require_once("includes/main/WebUI.php");
require_once('include/utils/utils.php');
include_once('vtlib/Vtiger/Module.php');
include_once('include/Webservices/Utils.php');
require_once('vtlib/Vtiger/Package.php');
global $adb;

echo "<br>Remove special UI types of salutation and name fields in leads and contacts...";
$query = "UPDATE vtiger_field SET uitype = '15', displaytype = 1, summaryfield = 1 WHERE columnname = 'salutation'";
$adb->pquery($query, array());
$query = "UPDATE vtiger_field SET uitype = '1' WHERE columnname = 'firstname'";
$adb->pquery($query, array());
$query = "UPDATE vtiger_field SET uitype = '2' WHERE columnname = 'lastname'";
$adb->pquery($query, array());
echo " done";


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
echo "add new web services ";
// check existance (for migrated clients)
$query = "SELECT * FROM `vtiger_ws_operation` WHERE `name` = 'retrievedocattachment'";
$res = $adb->pquery($query, array());
if ($adb->num_rows($res) > 0) {
	// add web service: retrievedocattachment
	$operationId = vtws_addWebserviceOperation('retrievedocattachment','include/Webservices/RetrieveDocAttachment.php','berli_retrievedocattachment','Get','0');
	vtws_addWebserviceOperationParam($operationId,'id','string','1');
	vtws_addWebserviceOperationParam($operationId,'returnfile','string','2');
	echo "retrievedocattachment added";
}
else {
	echo "retrievedocattachment already exists";
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
	echo "update_product_relations added";
}
else {
	echo "update_product_relations already exists";
}
// check existance (for migrated clients)
$query = "SELECT * FROM `vtiger_ws_operation` WHERE `name` = 'get_multi_relations'";
$res = $adb->pquery($query, array());
if ($adb->num_rows($res) > 0) {
	// add web service: get_multi_relations
	$operationId = vtws_addWebserviceOperation('get_multi_relations','include/Webservices/Custom/getMultiRelations.php','berli_get_multi_relations','Get','0');
	vtws_addWebserviceOperationParam($operationId,'id','string','1');
	echo "get_multi_relations added";
}
else {
	echo "get_multi_relations already exists";
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

echo 'Alter missing modcomments fields... ';

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
    echo "<br>Updating Mailchimp module ";
    updateVtlibModule("Mailchimp", "packages/vtiger/mandatory/Mailchimp.zip");
    echo "- done";
    
    echo "<br>create table for new Mailchimp version ";
    $query = "CREATE TABLE IF NOT EXISTS `vtiger_mailchimp_synced_entities` (
      `crmid` int(11) NOT NULL,
      `mcgroupid` int(11) NOT NULL,
      `recordid` int(11) NOT NULL,
      KEY `recordidx` (`recordid`),
      KEY `crmid` (`crmid`),
      CONSTRAINT `fk_1_vtiger_mailchimp_synced_entities` FOREIGN KEY (`crmid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    $adb->pquery($query, array());
    echo "create table done.<br>";

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

$moduleInstance = Vtiger_Module::getInstance("berlimap");
if($moduleInstance) {
    echo "<br>Updating berlimap module ";
    updateVtlibModule("berlimap", "packages/vtiger/optional/berlimap.zip");
    echo "- done";
}

$moduleInstance = Vtiger_Module::getInstance("gdpr");
if($moduleInstance) {
    echo "<br>Updating gdpr module ";
    updateVtlibModule("gdpr", "packages/vtiger/optional/gdpr.zip");
    echo "- done";
}

echo 'Creating ws_fieldtype entry for new uitype...';
$adb->query("INSERT INTO vtiger_ws_fieldtype (`uitype` ,`fieldtype`) VALUES ('crs16', 'autocompletedsingleuse')");
echo 'done<br>';

echo "change uitype of certain numberfields that were text until now<br>";
$query = "UPDATE `vtiger_field` SET uitype = 7 WHERE uitype = 1 AND typeofdata LIKE 'N%';";
$adb->pquery($query, array());
echo "uitype change done.<br>";

  

echo "<br>delete not needed files for new SMS Notifier version ";
require_once('config.inc.php');
$filePath = "modules/SMSNotifier/views/CheckStatus.php";
$file = $root_directory.''.$filePath;
unlink($file);
echo "file deletion done.<br>";

echo "<br>set constraint for SMS Notifier tables";
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

echo "<br>delete not needed files for new CKeditor version ";
require_once('config.inc.php');
$filePath = "libraries/jquery/ckeditor/ckeditor_php4.php";
$file = $root_directory.''.$filePath;
unlink($file);
$filePath = "libraries/jquery/ckeditor/ckeditor_php5.php";
$file = $root_directory.''.$filePath;
unlink($file);
echo "file deletion done.<br>";


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


echo "<br>delete not needed lang files<br>";
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


echo "remove berliSoftphone as entity <br>";
$query = "DELETE FROM `vtiger_ws_entity` WHERE `vtiger_ws_entity`.`name` = 'berliSoftphones';";
$adb->pquery($query, array());
echo "uitype change done.<br>";


echo 'module install Verteiler start<br>';
//install Verteiler module
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
			if ($packageName =='Verteiler') {
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
echo 'module install Verteiler done <br>';


echo "<br>update Tag version to 17.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.17'";
 
$adb->pquery($query, array());
echo " Tag version done.<br>";