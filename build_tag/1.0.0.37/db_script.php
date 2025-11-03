<!DOCTYPE html><html>
<meta http-equiv="cache-control" content="no-cache, must-revalidate, post-check=0, pre-check=0" />
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<?php
ini_set('include_path',ini_get('include_path').':../..');
require_once('include/utils/utils.php');
include_once('vtlib/Vtiger/Module.php');
include_once('include/Webservices/Utils.php');
require_once('vtlib/Vtiger/Package.php');
global $adb;

$adb = PearDatabase::getInstance();


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


echo "<br>update Tag version to 37.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.37'";
$adb->pquery($query, array());
echo " Tag version done.<br>";