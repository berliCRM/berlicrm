<!DOCTYPE html><html>
<meta http-equiv="cache-control" content="no-cache, must-revalidate, post-check=0, pre-check=0" />
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />

<head><title>BerliCRM updater</title><style>body { font-family: Open sans,sans-serif;}</style>
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

echo 'module update berliWidgets start<br>';
$moduleInstance = Vtiger_Module::getInstance("berliWidgets");
if($moduleInstance) {
	updateVtlibModule("berliWidgets", "packages/vtiger/mandatory/berliCleverReach.zip");
}
echo 'module update berliWidgets done<br>';

echo 'module update berliCleverReach start<br>';
$moduleInstance = Vtiger_Module::getInstance("berliCleverReach");
if($moduleInstance) {
	updateVtlibModule("berliCleverReach", "packages/vtiger/optional/berliCleverReach.zip");
}
echo 'module update berliCleverReach done<br>';

$moduleName = 'Users';
$moduleInstance = Vtiger_Module::getInstance($moduleName);

if($moduleInstance) {
	// use vtlib to add new block
	$blockInstance = Vtiger_Block::getInstance('LBL_USERSIGNATUR', $moduleInstance);
	if (!$blockInstance) {
		echo 'adding LBL_USERSIGNATUR Block ... <br>';
		$blockcf = new Vtiger_Block();
		$blockcf->label = 'LBL_USERSIGNATUR';
		$moduleInstance->addBlock($blockcf);
		echo 'adding LBL_USERSIGNATUR Block done<br>';
		
		$blockInstance = Vtiger_Block::getInstance('LBL_USERSIGNATUR', $moduleInstance);
		$newblockid = $blockInstance->id;
		
		if ($newblockid) {
			// move signature field
			$updateQuery = "UPDATE `vtiger_field` SET block = ? WHERE tablename = 'vtiger_users' AND columnname = 'signature' AND fieldname = 'signature'";
			$adb->pquery($updateQuery, array($newblockid));
		}
	}
}

echo "<br>Create berlicrm_recurringreferences table for calendar recurring events<br>";

$query = "
CREATE TABLE IF NOT EXISTS `berlicrm_recurringreferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parentactivityid` int(11) NOT NULL,
  `activityid` int(11) NOT NULL,
  `startdate` date DEFAULT NULL,
  `enddate` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
";
$adb->pquery($query, array());
echo "create berlicrm_recurringreferences table done.<br>";

echo "<br>set proper format for modifiedtime and createdtime fields<br>";
$query = "Update `vtiger_field` set typeofdata ='DT~O' where columnname = 'modifiedtime' and typeofdata ='T~O';";
$adb->pquery($query, array());
$query = "Update `vtiger_field` set typeofdata ='DT~O' where columnname = 'createdtime' and typeofdata ='T~O';";
$adb->pquery($query, array());
echo "set proper format done.<br>";

echo "<br>delete not needed files for new tcpt version ";
require_once('config.inc.php');

$filePath = 'libraries/tcpdf/fonts/old/.noencode';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/fonts/.noencode';
$file = $root_directory.''.$filePath;
unlink($file);

$dirname = 'libraries/tcpdf/fonts/old';
rmdir($dirname);

$filePath = 'libraries/tcpdf/tcpdf_parser.php';
$file = $root_directory.''.$filePath;
unlink($file);
echo "<br>file deletion done<br>";

echo "<br>remove fonts no longer available<br>";
$adb->pquery("DELETE FROM `berli_pdffonts` WHERE `berli_pdffonts`.`fontid` = 22", array());
$adb->pquery("DELETE FROM `berli_pdffonts` WHERE `berli_pdffonts`.`fontid` = 23", array());
$adb->pquery("DELETE FROM `berli_pdffonts` WHERE `berli_pdffonts`.`fontid` = 24", array());
$adb->pquery("DELETE FROM `berli_pdffonts` WHERE `berli_pdffonts`.`fontid` = 25", array());
echo "<br>font deletion done<br>";

echo "<br>add new fonts<br>";
$adb->pquery("INSERT INTO `berli_pdffonts` (`fontid` ,`tcpdfname` ,`namedisplay`) VALUES 
('38', 'aealarabiya', 'aeAlArabiya'),
('39', 'aefurat', 'AeFurat'),
('40', 'courier', 'Courier'),
('41', 'freemono', 'Free Mono'),
('42', 'pdfacourier', 'PDFA Courier'),
('43', 'pdfahelvetica', 'PDFA Helvetika'),
('44', 'pdfasymbol', 'PDFA Symbol'),
('45', 'pdfatimes', 'PDFA Times'),
('46', 'times', 'Times'),
('47', 'kozminproregular', 'Kozminpro Regular'),
('48', 'kozgopromedium', 'Kozgopro Medium'),
('49', 'msungstdlight', 'Msungstd Light'),
('50', 'hysmyeongjostdmedium', 'Hysmyeongjostd Medium')", array());
echo "<br>adding new fonts done <br>";

echo "<br>increase password field length for Mail Manager and MailScanner<br>";
$query = "ALTER TABLE `vtiger_mailscanner` CHANGE `password` `password` VARCHAR( 4000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ;";
$adb->pquery($query, array());
$query = "ALTER TABLE `vtiger_mail_accounts` CHANGE `mail_password` `mail_password` VARCHAR( 4000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;";
$adb->pquery($query, array());
echo "increase done.<br>";


// Module tabid update //get all tabids for module berliCleverReach, Mailchimp
$arrModule = array('berliCleverReach', 'Mailchimp');
foreach($arrModule as $ModuleName){
	$moduleToUpdate = $ModuleName;
	if( Vtiger_Module::getInstance($moduleToUpdate) ){
		echo '<br>module '.$moduleToUpdate.' update start<br>'; 
		//get all tabids for module berliCleverReach, Mailchimp
		$query = 'SELECT `tabid` FROM `vtiger_tab` WHERE `name`=?;';
		$result = $adb->pquery($query, array($moduleToUpdate));
		$numOfRows = $adb->num_rows($result);
		
		for ($i=0; $i<$numOfRows; $i++) {
			$tabidarr[] = $adb->query_result($result, $i, "tabid");
		}
		foreach ($tabidarr as $tabid) {
			//update
			$updateQuery = "UPDATE `vtiger_field` set `quickcreate`='3', `fieldname`='createdtime' WHERE `tabid`=? and `fieldname`='CreatedTime';";
			$adb->pquery($updateQuery, array($tabid));
			$updateQuery = "UPDATE `vtiger_field` set `quickcreate`='3', `fieldname`='modifiedtime' WHERE `tabid`=? and `fieldname`='ModifiedTime';";
			$adb->pquery($updateQuery, array($tabid));	
		}
		echo '<br>module '.$moduleToUpdate.' update done <br>';
	}
}


// Vendors and Service Relation
echo "<br>set relation in Vendors to Services (to add related list into Vendors), increase vtiger_relatedlists_seq number.<br>";
// first we need to check, if it is allready in DB
$sql = "SELECT * FROM vtiger_relatedlists
WHERE tabid = (SELECT tabid FROM vtiger_tab WHERE name = 'Vendors')
AND related_tabid = (SELECT tabid FROM vtiger_tab WHERE name = 'Services')
AND name = 'get_services' 
AND sequence = 7
AND label = 'Services'
AND presence = 0 
AND actions = 'SELECT'";
$resultsql = $adb->pquery($sql, array()); 
$num_rows = $adb->num_rows($resultsql);
if($num_rows <= 0){
	// because it not exist we need to insert it.
	$query = "INSERT INTO vtiger_relatedlists (relation_id, tabid, related_tabid, name, sequence, label, presence, actions)
	VALUES ( 
	((SELECT max(id) FROM vtiger_relatedlists_seq)+1), 
	(SELECT tabid FROM vtiger_tab WHERE name = 'Vendors'), 
	(SELECT tabid FROM vtiger_tab WHERE name = 'Services'), 
	'get_services',
	7,
	'Services',
	0,
	'SELECT');";
	$adb->pquery($query, array());
	
	$query = "UPDATE vtiger_relatedlists_seq
	SET id = ((SELECT max(relation_id) FROM vtiger_relatedlists)) 
	WHERE id = ((SELECT max(relation_id) FROM vtiger_relatedlists)-1);";
	$adb->pquery($query, array());
	echo "Vendors to Services DB relation and increase done.<br>";
}
else{
	echo "Vendors to Services DB relation and increase allready exist. Done.<br>";
}

// module update standard if module exist.
$moduleToUpdateArr = array('Pdfsettings', 'Projects', 'EmailTemplates', 'berlimap', 'MailManager', 
	'Mailchimp', 'SMSNotifier', 'crmtogo', 'ServiceContracts' 
);
foreach($moduleToUpdateArr as $moduleToUpdate){
	if( Vtiger_Module::getInstance($moduleToUpdate) ){
		// if we are here, then the Module exist. And we can update.
		echo '<br>module '.$moduleToUpdate.' update start<br>'; 
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
					if ($packageName == $moduleToUpdate) { 
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
		echo '<br>module update '.$moduleToCheck.' done <br>';  
	}else{
		// This module does not exist.

	}
}

//  for db create new table for email tracking   add email tracking to send function. Was last changed on 2025.03.24 tag 101
echo "new table for email tracking<br>";
$query = 'CREATE TABLE IF NOT EXISTS `berlicrm_mailtracker` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `subject` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
    `receiver` text COLLATE utf8_unicode_ci NOT NULL,
    `send_date` datetime NOT NULL,
    `send_user` int(11) NOT NULL,
    `crmid` int(11) DEFAULT NULL,
    `smtp_answer` text COLLATE utf8_unicode_ci NOT NULL,
    `messageid` text COLLATE utf8_unicode_ci,
    PRIMARY KEY (`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';
$res = $adb->pquery($query, array());
if(!$res) {
	echo "Error: ".$adb->database->errorMsg();
}
echo "done new table for email tracking <br>";

// added Google module update
echo '<br>module Google update start<br>';
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
			if ($packageName =='Google') {
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
echo '<br>module Google done <br>';

//update crmtogo module
echo '<br>module crmtogo update start<br>';
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
			if ($packageName =='crmtogo') {
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
echo '<br>module crmtogo done <br>';

// add recurring frequency
$queryCheck = "SELECT 1 FROM `vtiger_recurring_frequency` WHERE `recurring_frequency` = ?";
$checkRes = $adb->pquery($queryCheck, array('every 4 months'));
if ($adb->num_rows($checkRes) === 0) {
	// control sort order
	echo "add recurring frequency of 4 month<br>";
	$query = 'update `vtiger_recurring_frequency` set recurring_frequency_id = 7, sortorderid = 7 where recurring_frequency = ?';
	$res = $adb->pquery($query, array('Yearly'));
	if(!$res) {
		echo "Error: ".$adb->database->errorMsg();
	}
	echo "sortoder done<br>";

	$query = "INSERT INTO `vtiger_recurring_frequency` (`recurring_frequency_id`, `recurring_frequency`, `sortorderid`, `presence`) VALUES (6, 'every 4 months', 6, 1)";
	$res = $adb->pquery($query, array());
	if(!$res) {
		echo "Error: ".$adb->database->errorMsg();
	}

	$query = "UPDATE `vtiger_recurring_frequency_seq` SET id = 7";
	$res = $adb->pquery($query, array());
	if(!$res) {
		echo "Error: ".$adb->database->errorMsg();
	}
}
echo "recurring frequency done<br>";

// add 14 days payment interval
echo "add 14 days payment interval<br>";
$queryCheck = "SELECT 1 FROM `vtiger_payment_duration` WHERE `payment_duration` = ?";
$checkRes = $adb->pquery($queryCheck, array('Net 14 days'));

if ($adb->num_rows($checkRes) === 0) {
	$query = 'UPDATE vtiger_payment_duration SET sortorderid = sortorderid + 1 WHERE sortorderid >= 1';
	$res = $adb->pquery($query, array());
	if(!$res) {
		echo "Error: ".$adb->database->errorMsg();
	}
	echo "sortoder done<br>";

    $query0 = "SELECT max( payment_duration_id ) AS maxid FROM `vtiger_payment_duration` ";
    $res0 = $adb->pquery($query0, array());
    $maxid = 0;
    if($adb->num_rows($res0) == 1){
        $maxid = intval($adb->query_result($res0,0,"maxid"));
    }
    $maxid = $maxid + 1;

    $query = "INSERT INTO vtiger_payment_duration (payment_duration_id, payment_duration, sortorderid, presence) VALUES ($maxid, 'Net 14 days', 1, 1)";
	$res = $adb->pquery($query, array());
	if(!$res) {
		echo "Error: ".$adb->database->errorMsg();
	}

	$query = "UPDATE `vtiger_payment_duration_seq` SET id = $maxid";
	$res = $adb->pquery($query, array());
	if(!$res) {
		echo "Error: ".$adb->database->errorMsg();
	}
}
echo "14 days payment interval done<br>";


echo '<br>module Projects update start<br>';
//update Projects module
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
			if ($packageName =='Projects') {
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
echo '<br>module update Projects done <br>';


echo '<br>module berlimap update start<br>';
//update berlimap module
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
			if ($packageName =='berlimap') {
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
echo '<br>module update berlimap done <br>';

echo '<br>module Verteiler update start<br>';
//update Verteiler module
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
					if(false) {
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
echo '<br>module update Verteiler done <br>';

echo '<br>module Import update start<br>';
//update Verteiler module
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
			if ($packageName =='Import') {
				$packagepath = "$moduleFolder/$file";
				$package = new Vtiger_Package();
				$module = $package->getModuleNameFromZip($packagepath);
				if($module != null) {
					$moduleInstance = Vtiger_Module::getInstance($module);
					if(false) {
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
echo '<br>module update Import done <br>';

echo '<br>module Toolwidget update start<br>';

function checkAndAddLink($moduleName, $label, $url) {
    global $adb;
    
    $query = "SELECT 1 FROM vtiger_links WHERE tabid = (SELECT tabid FROM vtiger_tab WHERE name = ?) AND linklabel = ? AND linkurl = ?";
    $result = $adb->pquery($query, array($moduleName, $label, $url));
    
    if ($adb->num_rows($result) == 0) {
        $module = Vtiger_Module::getInstance($moduleName);
        if ($module) {
            $module->addLink('DETAILVIEWSIDEBARWIDGET', $label, $url);
            echo "Link hinzugefügt für Modul: $moduleName<br>";
        } else {
            echo "Modul nicht gefunden: $moduleName<br>";
        }
    } else {
        echo "Link existiert bereits für Modul: $moduleName<br>";
    }
}

function deleteOldLinks($moduleName, $correctUrlPattern) {
    global $adb;

    // Ermittelt die tabid für das Modul
    $tabidQuery = "SELECT tabid FROM vtiger_tab WHERE name = ?";
    $tabidResult = $adb->pquery($tabidQuery, array($moduleName));
    if ($adb->num_rows($tabidResult) == 0) {
        echo "Modul nicht gefunden: $moduleName<br>";
        return;
    }
    $tabid = $adb->query_result($tabidResult, 0, 'tabid');

    // Lösche alle fehlerhaften Links, die nicht exakt dem gewünschten Muster entsprechen
    $deleteQuery = "DELETE FROM vtiger_links 
                    WHERE tabid = ? 
                    AND linklabel = 'LBL_COPY_CONTACTDETAILS'
                    AND linkurl LIKE 'module=ToolWidgets&view=showCopyPasteDataW%'
                    AND linkurl NOT LIKE ?";
    
    $adb->pquery($deleteQuery, array($tabid, $correctUrlPattern));

    echo "Alte fehlerhafte Links für Modul $moduleName wurden entfernt.<br>";
}

$correctContactsUrl = 'module=ToolWidgets&view=showCopyPasteData&mode=showEntries&source_module=Contacts&viewtype=detail';
$correctAccountsUrl = 'module=ToolWidgets&view=showCopyPasteData&mode=showEntries&source_module=Accounts&viewtype=detail';

// Update Toolwidget module
$moduleFolders = array('packages/vtiger/mandatory', 'packages/vtiger/optional');
foreach ($moduleFolders as $moduleFolder) {
    if ($handle = opendir($moduleFolder)) {
        while (false !== ($file = readdir($handle))) {
            $packageNameParts = explode(".", $file);
            if ($packageNameParts[count($packageNameParts) - 1] != 'zip') {
                continue;
            }
            array_pop($packageNameParts);
            $packageName = implode("", $packageNameParts);
            if ($packageName == 'ToolWidgets' || $packageName == 'RecycleBin') {
                $packagepath = "$moduleFolder/$file";
                $package = new Vtiger_Package();
                $module = $package->getModuleNameFromZip($packagepath);
                if ($module != null) {
                    $moduleInstance = Vtiger_Module::getInstance($module);
                    if (false) {
                        updateVtlibModule($module, $packagepath);
                    } else {
                        installVtlibModule($module, $packagepath);
                    }
                }
            }
        }
        closedir($handle);
    }
}

// check and add links
checkAndAddLink('Contacts', 'LBL_COPY_CONTACTDETAILS', 'module=ToolWidgets&view=showCopyPasteData&mode=showEntries&source_module=Contacts&viewtype=detail');
checkAndAddLink('Accounts', 'LBL_COPY_CONTACTDETAILS', 'module=ToolWidgets&view=showCopyPasteData&mode=showEntries&source_module=Accounts&viewtype=detail');

// deletes old links for "Contacts" and "Accounts"
deleteOldLinks('Contacts', $correctContactsUrl);
deleteOldLinks('Accounts', $correctAccountsUrl);

$filePath = 'modules/ToolWidgets/views/showCopyPasteDataWidget.php';

if (file_exists($filePath)) {
    if (unlink($filePath)) {
        echo "Veraltete Datei erfolgreich gelöscht.";
    } else {
        echo "Fehler beim Löschen der Datei.";
    }
} else {
    echo "Datei existiert nicht.";
}

echo '<br>module update Toolwidget done <br>';

// Update entries for projects in vtiger_relatedlists
$query = "UPDATE vtiger_relatedlists SET actions = 'ADD'
WHERE relation_id IN (
    SELECT vtiger_relatedlists.relation_id
    FROM vtiger_relatedlists
    INNER JOIN vtiger_tab ON vtiger_relatedlists.tabid = vtiger_tab.tabid
    WHERE vtiger_relatedlists.name = 'get_dependents_list'
    AND vtiger_relatedlists.label = 'Projects'
    AND vtiger_tab.name IN ('Contacts', 'Accounts')";
$result = $adb->pquery($query, array());
echo "<br>Updated vtiger_relatedlists successfully<br>";

$query = "ALTER TABLE `vtiger_schedulereports` 
ADD COLUMN `attfolderid` INT(19) DEFAULT NULL,
ADD COLUMN `savetype` VARCHAR(30) DEFAULT NULL;";
$result = $adb->pquery($query, array());
echo "<br>vtiger_schedulereports table updated successfully<br>";


// ######################################################## Adding extra fields for eInvoice ... Rev.25134
echo "start";


$arrFields = array(
    'Accounts' => array(
        'LBL_CUSTOM_INFORMATION' => array(
            array('Leitweg-ID', 'buyerreference', 'V~O', 1, 'VARCHAR(50)', '', 'Die Leitweg-ID ist ein Kennzeichen einer elektronischen Rechnung zur eindeutigen Adressierung von öffentlichen Auftraggebern in Deutschland (Beispiele: Behörden, Kommunen, Ministerien).'),
        )
    ),
    'Invoice' => array(
        'LBL_INVOICE_INFORMATION' => array(
            // array('Statusdatum', 'statusdate', 'D~O', 5, 'DATE', '', 'Datum des letzten Status, nicht ändern, wird vom automatischen Mahnwesen verwendet.'), // Datum
            array('Lieferdatum', 'deliveryperiod', 'D~O', 5, 'DATE', '', 'Lieferdatum, wird für E-Rechnung verwendet'), // Datum
        )

        //'27' => array(
        //      array('Kunde', 'kundenid', 'V~O', 10, 'VARCHAR(100)', 'Kunden')//, // Bezugsfeld
        //),

    )
);

foreach ($arrFields as $moduleName => $blocks) {
    echo "Start $moduleName...<br>";
    $moduleInstance = Vtiger_Module::getInstance($moduleName);
    if (!$moduleInstance) {
        die("$moduleName no instance");
    }

    echo "first step $moduleName...<br>";

    foreach ($blocks as $blockName => $fieldInfos) {


        echo "BLOCKs..<br>";

        $blockInstance = Vtiger_Block::getInstance($blockName, $moduleInstance);

        if (!$blockInstance) {
            // die ("\"$blockName\" no block instance found");

            //// to create new block
            $blockInstance = new Vtiger_Block();
            $blockInstance->label = $blockName;
            $moduleInstance->addBlock($blockInstance);

        } else {
            // if we need to del the BLOCK:
            //$blockInstance->delete();
            //echo "The Block: \"$blockName\" is deleted! from Module \"$moduleName\" ..<br>";
            // die ("The Block: \"$blockName\" is deleted! from Module \"$moduleName\" ");
        }

        echo "foreachs..<br>";

        foreach ($fieldInfos as $fieldInfo) {
            $fieldObj = Vtiger_Field::getInstance($fieldInfo[1], $moduleInstance);


            if (!$fieldObj) {

                echo "Adding $moduleName field {$fieldInfo[0]}...<br>";
                $fieldObj = new Vtiger_Field();
                $fieldObj->name = $fieldInfo[1];
                $fieldObj->label = $fieldInfo[0];
                $fieldObj->table = $moduleInstance->basetable;
                $fieldObj->typeofdata = $fieldInfo[2];
                $fieldObj->uitype = $fieldInfo[3];
                $fieldObj->columntype = $fieldInfo[4];
                $fieldObj->info_type = 'BAS';
                $fieldObj->displaytype = '1';
                if (!empty($fieldInfo[6])) {
                    $fieldObj->helpinfo = $fieldInfo[6];
                }

                if (($fieldInfo[3] == 16 || $fieldInfo[3] == 33) && !empty($fieldInfo[5])) {
                    $fieldObj->setPicklistValues($fieldInfo[5]);
                }

                $blockInstance->addField($fieldObj);

                if ($fieldInfo[3] == 10 && isset($fieldInfo[5])) {
                    $query = "SELECT * FROM vtiger_fieldmodulerel WHERE fieldid = ?;";
                    $res = $adb->pquery($query, array($fieldObj->id));

                    echo "fieldid " . ($fieldObj->id) . "...<br>";


                    if ($adb->num_rows($res) == 0) {
                        $query = "INSERT INTO vtiger_fieldmodulerel VALUES(?, ?, ?, ?, ?);";
                        $adb->pquery($query, array($fieldObj->id, $moduleName, $fieldInfo[5], null, null));
                        $relInstance = Vtiger_Module::getInstance($fieldInfo[5]);
                        $relInstance->setRelatedList($moduleInstance, $moduleName, array('ADD'), 'get_dependents_list');
                    }
                } elseif ($fieldInfo[3] == 13) {
                    $query = "SELECT * FROM vtiger_relatedlists WHERE tabid = ? AND related_tabid = ?;";
                    $res = $adb->pquery($query, array($moduleInstance->getId(), getTabId('Emails')));

                    if ($adb->num_rows($res) == 0) {
                        $relInstance = Vtiger_Module::getInstance('Emails');
                        $moduleInstance->setRelatedList($relInstance, 'Emails', array('ADD'), 'get_emails');
                    }
                }

            } else {
                echo "fieldObj: \"$fieldInfo[0]\" exist !  ..<br>";
                //$fieldObj->delete();
                //echo "fieldObj delete   ..<br>";
            }
        }
    }
}


$fieldName = 'invoicestatus';
$tableName = 'vtiger_invoice';
$newValues = ['Mahnstufe 1', 'Mahnstufe 2', 'Mahnstufe 3', 'Mahnstopp'];

// Get field ID and table name
$result = $adb->pquery("SELECT fieldid FROM vtiger_field WHERE fieldname = ? AND tablename = ?", [$fieldName, $tableName]);
$fieldId = $adb->query_result($result, 0, 'fieldid');

// Get existing max sortorder
$result = $adb->pquery("SELECT MAX(sortorderid) AS maxsort FROM vtiger_$fieldName", []);
$sortId = (int) $adb->query_result($result, 0, 'maxsort');

foreach ($newValues as $value) {
    // Check if value already exists
    $check = $adb->pquery("SELECT picklist_valueid FROM vtiger_$fieldName WHERE $fieldName = ?", [$value]);
    if ($adb->num_rows($check) > 0) {
        echo "Value '$value' already exists, skipping.\n";
        continue;
    }
    $sortId++;
    // get new picklist value ID
    $res = $adb->pquery("SELECT id FROM vtiger_picklistvalues_seq");
    $picklistValueId = $adb->query_result($res, 0, 'id') + 1;

    // Get new invoicestatus ID (internal)
    $res = $adb->pquery("SELECT id FROM vtiger_invoicestatus_seq");
    $invoicestatusId = (int)$adb->query_result($res, 0, 'id') + 1;

    echo '<pre>';
    echo "INSERT INTO vtiger_$fieldName (invoicestatusid, $fieldName, sortorderid, presence, picklist_valueid) VALUES ($invoicestatusId, $value, $sortId, 1, $picklistValueId)" . PHP_EOL;
    echo '</pre>';


    $adb->pquery("INSERT INTO vtiger_$fieldName (invoicestatusid, $fieldName, sortorderid, presence, picklist_valueid) VALUES (?, ?, ?, 1, ?)", [$invoicestatusId, $value, $sortId, $picklistValueId]);

    $adb->pquery("UPDATE vtiger_picklistvalues_seq SET id = ?", [$picklistValueId]);
    $adb->pquery("UPDATE vtiger_invoicestatus_seq SET id = ?", [$invoicestatusId]);

    // Get new picklist value ID
    // $result = $adb->pquery("SELECT picklist_valueid FROM vtiger_$fieldName WHERE $fieldName = ?", [$value]);
    // $picklistValueId = $adb->query_result($result, 0, 'picklist_valueid');

    // Assign to all roles
    $picklistResult = $adb->pquery("SELECT * FROM vtiger_picklist WHERE name = ?", [$fieldName]);

    // Check if picklist exists, get picklist ID
    if ($adb->num_rows($picklistResult) > 0) {
        $picklistid = $adb->query_result($picklistResult, 0, 'picklistid');
        echo "Picklist '$fieldName' exists with ID '$picklistid'.\n";
    } else {
        echo "Picklist '$fieldName' does not exist, skipping assignment. num_rows=" . $adb->num_rows($picklistResult) . "\n";
        continue; // Skip if picklist does not exist
    }

    $roles = $adb->pquery("SELECT roleid FROM vtiger_role", []);
    for ($i = 0; $i < $adb->num_rows($roles); $i++) {
        $roleId = $adb->query_result($roles, $i, 'roleid');
        $adb->pquery("INSERT INTO vtiger_role2picklist (roleid, picklistvalueid, picklistid) VALUES (?, ?, ?)", [$roleId, $picklistValueId, $picklistid]);
        printf("Assigned picklist value '%s' to role '%s' for picklist wiht id '%s'.\n", $picklistValueId, $roleId, $picklistid);
    }
}
echo "end";
// ######################################################## Adding extra fields for eInvoice ... 






$query = "UPDATE `vtiger_version` SET `tag_version` = ?";
$adb->pquery($query, array($current_release_tag));
echo "<h2>Finished updating to $current_release_tag!</h2>";
