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

// Function to start updates of all Modules.
function berli_updateAllModules() {
    echo "<br>\n Updating all modules automatically... ";
    $moduleFolders = array('packages/vtiger/mandatory', 'packages/vtiger/optional');

    foreach ($moduleFolders as $moduleFolder) {
        if ($handle = opendir($moduleFolder)) {
            while (false != ($file = readdir($handle))) {

                // only zip files
                $parts = explode('.', $file);
                if (end($parts) !== 'zip') {
                    continue;
                }
                array_pop($parts);
                //$zipName = implode('', $parts);

                $packagepath = "$moduleFolder/$file";
                $package = new Vtiger_Package();
                $moduleName = $package->getModuleNameFromZip($packagepath);

                if ($moduleName) {
                    echo "<br>\n Auto-update for module: {$moduleName} ";
                    berli_updateModuleByName($moduleName, $moduleFolder, $file);
                }
            }
            closedir($handle);
        }
    }
    echo "<br>\n Finished automatic module updates. ";
}
// Function to update a module
function berli_updateModuleByName($moduleName, $moduleFolder, $file) {
    $packagepath = "$moduleFolder/$file";
    $package = new Vtiger_Package();
    $module = $package->getModuleNameFromZip($packagepath);
    if ($module != null) {
        $moduleInstance = Vtiger_Module::getInstance($module);

        if (!$moduleInstance) {
            echo "<br>\n Module {$moduleName} is not installed. Skipping update.";
            return; // installVtlibModule($module, $packagepath);
        }
        else{
            echo "<br>\n Updating module {$moduleName}... ";
            updateVtlibModule($module, $packagepath);
        }
    }
}


// Function to add normal fields (and blocks)
function berli_addFields($arrFields) {
    global $adb;

    foreach ($arrFields as $moduleName => $blocks) {

        echo "<br>\n Start adding fields for module: $moduleName ";

        $moduleInstance = Vtiger_Module::getInstance($moduleName);
        if (!$moduleInstance) {
            echo "<br>\n Module $moduleName not found, skipping. ";
            continue;
        }

        foreach ($blocks as $blockName => $fieldInfos) {

            echo "<br>\n Processing block: $blockName ";

            // Block create or find and update
            $blockInstance = Vtiger_Block::getInstance($blockName, $moduleInstance);
            if (!$blockInstance) {
                $blockInstance = new Vtiger_Block();
                $blockInstance->label = $blockName;
                $moduleInstance->addBlock($blockInstance);
                echo "<br>\n Created new block: $blockName ";
            }

            foreach ($fieldInfos as $fieldInfo) {

                // fieldInfo structure:
                // [0] label
                // [1] columnname
                // [2] typeofdata
                // [3] uitype
                // [4] columntype
                // [5] picklistValues OR related modules (array)
                // [6] helpinfo (optional)

                list($label, $name, $typeofdata, $uitype, $columntype) = $fieldInfo;
                $extra = isset($fieldInfo[5]) ? $fieldInfo[5] : null;
                $helpinfo = isset($fieldInfo[6]) ? $fieldInfo[6] : null;

                $fieldObj = Vtiger_Field::getInstance($name, $moduleInstance);

                if ($fieldObj) {
                    echo "<br>\n Field '$name' already exists in $moduleName, skipping. ";
                    continue;
                }

                echo "<br>\n Adding field '$name' ($label) to $moduleName ... ";

                // new fields create
                $fieldObj = new Vtiger_Field();
                $fieldObj->name = $name;
                $fieldObj->label = $label;
                $fieldObj->table = $moduleInstance->basetable;
                $fieldObj->typeofdata = $typeofdata;
                $fieldObj->uitype = $uitype;
                $fieldObj->columntype = $columntype;
                $fieldObj->info_type = 'BAS';
                $fieldObj->displaytype = '1';
                $fieldObj->generatedtype = '2';

                if (!empty($helpinfo)) {
                    $fieldObj->helpinfo = $helpinfo;
                }

                // Picklist values set
                if (($uitype == 15 || $uitype == 16 || $uitype == 33) && is_array($extra)) {
                    $fieldObj->setPicklistValues($extra);
                }

                // add field to Block 
                $blockInstance->addField($fieldObj);

                // -------------------------
                // uitype 10 → Relation
                // -------------------------
                if ($uitype == 10 && is_array($extra)) {

                    foreach ($extra as $relModuleName) {

                        $query = "SELECT * FROM vtiger_fieldmodulerel WHERE fieldid = ? AND relmodule = ?";
                        $res = $adb->pquery($query, array($fieldObj->id, $relModuleName));

                        if ($adb->num_rows($res) == 0) {
                            $adb->pquery(
                                "INSERT INTO vtiger_fieldmodulerel VALUES(?, ?, ?, ?, ?)",
                                array($fieldObj->id, $moduleName, $relModuleName, NULL, NULL)
                            );

                            $relInstance = Vtiger_Module::getInstance($relModuleName);
                            if ($relInstance) {
                                $relInstance->setRelatedList($moduleInstance, $moduleName, array('ADD'), 'get_dependents_list');
                            }
                        }
                    }

                    // Index für Relation-Feld
                    $adb->pquery("ALTER TABLE `{$fieldObj->table}` ADD INDEX (`{$fieldObj->name}`)", array());
                }

                // -------------------------
                // uitype 13 → Emails
                // -------------------------
                elseif ($uitype == 13) {

                    $query = "SELECT * FROM vtiger_relatedlists WHERE tabid = ? AND related_tabid = ?";
                    $res = $adb->pquery($query, array($moduleInstance->getId(), getTabId('Emails')));

                    if ($adb->num_rows($res) == 0) {
                        $relInstance = Vtiger_Module::getInstance('Emails');
                        $moduleInstance->setRelatedList($relInstance, 'Emails', array('ADD'), 'get_emails');
                    }
                }

                // -------------------------
                // uitype 56 → Checkbox default
                // -------------------------
                elseif ($uitype == 56) {
                    $adb->pquery(
                        "UPDATE {$fieldObj->table} SET {$fieldObj->name} = 0 WHERE {$fieldObj->name} IS NULL",
                        array()
                    );
                }

                echo "<br>\n Field '$name' added successfully. ";
            }
        }
    }
}

// Function to see if primary key exist or not
function hasPrimaryKey($tableName) {
    global $adb;
    $sql = "
        SELECT COUNT(*) AS cnt
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND CONSTRAINT_NAME = 'PRIMARY'
    ";
    $res = $adb->pquery($sql, [$tableName]);
    return ((int)$adb->query_result($res, 0, 'cnt') > 0);
}

$res = $adb->query("SELECT tag_version FROM vtiger_version");
$installedtag = $adb->query_result($res,0,'tag_version');
if ($installedtag == $current_release_tag) {
	die("This installation of berliCRM is up to date.");
}

echo "<h1>Updating from $installedtag to $current_release_tag..</h1>";
{
    echo "Remove special UI types of salutation and name fields in leads and contacts...";
    $query = "UPDATE vtiger_field SET uitype = '15', displaytype = 1, summaryfield = 1 WHERE columnname = 'salutation'";
    $adb->pquery($query, array());
    $query = "UPDATE vtiger_field SET uitype = '1' WHERE columnname = 'firstname'";
    $adb->pquery($query, array());
    $query = "UPDATE vtiger_field SET uitype = '2' WHERE columnname = 'lastname'";
    $adb->pquery($query, array());
}
echo "<br>\n  done ";


//  Import Cron correction
echo "Proper text for Import Cron (if it exists)... ";
$query = "UPDATE vtiger_cron_task SET description = ? WHERE module = ?;";
$res = $adb->pquery($query, array('The recommended frequency for Imports is 15 minutes.', 'Import'));
if ($res) {
	echo "<br>\n done ";
} else {
	echo "<br>\n failed ";
}

// add new Webservices
echo "<br>\n New web services: ";
// check existance (for migrated clients)
$query = "SELECT * FROM `vtiger_ws_operation` WHERE `name` = 'retrievedocattachment'";
$res = $adb->pquery($query, array());
if ($adb->num_rows($res) == 0) {
    // add web service: retrievedocattachment
    $operationId = vtws_addWebserviceOperation('retrievedocattachment','include/Webservices/RetrieveDocAttachment.php','berli_retrievedocattachment','Get','0');
    vtws_addWebserviceOperationParam($operationId,'id','string','1');
    vtws_addWebserviceOperationParam($operationId,'returnfile','string','2');
    echo "<br>\n retrievedocattachment added ";
}
else {
    echo "<br>\n retrievedocattachment already exists ";
}


// check existance (for migrated clients)
// add new webservice operation get_new_multi_relations
echo "<br>\n Adding new Webservices 'get_new_multi_relations' START ";
$query = "SELECT * FROM vtiger_ws_operation WHERE name = ?;";
$res = $adb->pquery($query, array('get_new_multi_relations'));
if ($res && $adb->num_rows($res) == 0) {
    $operationId = vtws_addWebserviceOperation('get_new_multi_relations', 'include/Webservices/Custom/getNewMultiRelations.php', 'berli_get_new_multi_relations', 'GET', '0');
    if ($operationId) {
        vtws_addWebserviceOperationParam($operationId,'id','string','1');
        vtws_addWebserviceOperationParam($operationId,'relModule','string','2');
        echo "<br>\n Adding new Webservices 'get_new_multi_relations' DONE ";
    } 
    else {
        echo "<br>\n ERROR WHILE TRYING TO GET WS-ID! ";
    }
} 
else {
    $errorMsg = $adb->database->errorMsg();
    if (!empty($errorMsg)) {
        echo "<br>\n QUERY ERROR: ".$adb->database->errorMsg().' ';
    } 
    elseif ($adb->num_rows($res) != 0) {
        echo "<br>\n Webservices 'get_new_multi_relations' already present ";
    } 
    else {
        echo '<br>\n UNKNOWN ERROR ';
    }
}
echo '<br>\n ';


// check existance (for migrated clients)
$query = "SELECT * FROM `vtiger_ws_operation` WHERE `name` = 'update_product_relations'";
$res = $adb->pquery($query, array());
if ($adb->num_rows($res) == 0) {
    // add web service: update_product_relations
    $operationId = vtws_addWebserviceOperation('update_product_relations','include/Webservices/Custom/ProductRelation.php','vtws_update_product_relations','POST','0');
    vtws_addWebserviceOperationParam($operationId,'productid','string','1');
    vtws_addWebserviceOperationParam($operationId,'relids','string','2');
    vtws_addWebserviceOperationParam($operationId,'preserve','string','3');
    echo "<br>\n update_product_relations added ";
}
else {
    echo "<br>\n update_product_relations already exists ";
}


// check existance (for migrated clients)
$query = "SELECT * FROM `vtiger_ws_operation` WHERE `name` = 'get_multi_relations'";
$res = $adb->pquery($query, array());
if ($adb->num_rows($res) == 0) {
    // add web service: get_multi_relations
    $operationId = vtws_addWebserviceOperation('get_multi_relations','include/Webservices/Custom/getMultiRelations.php','berli_get_multi_relations','Get','0');
    vtws_addWebserviceOperationParam($operationId,'id','string','1');
    echo "<br>\n get_multi_relations added ";
}
else {
    echo "<br>\n get_multi_relations already exists ";
}


// update vtiger_smsnotifier_servers structure (if required)
echo "<br>\n Update vtiger_smsnotifier_servers structure (if required) <br>\n Table ";
$query = "SHOW COLUMNS FROM vtiger_smsnotifier_servers LIKE 'countryprefix';";
$res = $adb->pquery($query, array());
if ($res) {
    echo "found";
    if ($adb->num_rows($res) < 1) {
        echo " and update required";

        // ALTER TABLE absichern
        if (!columnExists_db('countryprefix', 'vtiger_smsnotifier_servers')) {
            $query = "ALTER TABLE vtiger_smsnotifier_servers ADD `countryprefix` VARCHAR (5) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''";
            $res = $adb->pquery($query, array());
            if ($res) {
                echo "<br>\n Update successful";
            } else {
                echo "<br>\n Update NOT successful";
            }
        } else {
            echo "<br>\n Column countryprefix already exists, skipping.";
        }

    } else {
        echo " and update NOT required";
    }
} else {
    echo "not found";
}

// adding fields and index missing from modcomments manifest before v1.0.11

echo '<br>\n Alter missing modcomments fields... ';

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

    // ALTER TABLE absichern
    if (!columnExists_db('modcommentsid', 'vtiger_modcomments')) {
        echo "<br>\n WARNING: modcommentsid column missing, cannot add PRIMARY KEY. ";
    } else {
        // Prüfen ob PK schon existiert
        // $pkCheck = $adb->pquery("SHOW KEYS FROM vtiger_modcomments WHERE Key_name='PRIMARY'", []);
        $pkCheck = $adb->pquery("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'vtiger_modcomments'
            AND CONSTRAINT_TYPE = 'PRIMARY KEY' ", []
        );

        if ($pkCheck && $adb->num_rows($pkCheck) == 0) {
            //// kann es sein, dass es schon ein anderes Primary Key hat? Wenn doch, konnte auch hier der Fehler sein.
            $adb->query("ALTER TABLE `vtiger_modcomments` ADD PRIMARY KEY ( `modcommentsid` )");
        } else {
            echo "<br>\n Primary key already exists, skipping. ";
        }
    }
}

echo '<br>\n done ';

echo '<br>\n Creating ws_fieldtype entry for new uitype...';
$check = $adb->pquery( "SELECT 1 FROM vtiger_ws_fieldtype WHERE uitype = 'cr16' AND fieldtype = 'autocompletedtext' ", [] );
if ($adb->num_rows($check) == 0) {
    $adb->query("INSERT INTO vtiger_ws_fieldtype (`uitype` ,`fieldtype`) VALUES ('cr16', 'autocompletedtext')");
}
echo '<br>\n done ';

$moduleInstance = Vtiger_Module::getInstance("Mailchimp");
if($moduleInstance) {
    echo "<br>\n delete not needed files for new Mailchimp version ";
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
    echo "<br>\n file deletion done. ";
}

echo '<br>\n Creating ws_fieldtype entry for new uitype...';
$check = $adb->pquery( "SELECT 1 FROM vtiger_ws_fieldtype WHERE uitype = 'crs16' AND fieldtype = 'autocompletedsingleuse' ", [] );
if ($adb->num_rows($check) == 0) {
    $adb->query("INSERT INTO vtiger_ws_fieldtype (`uitype` ,`fieldtype`) VALUES ('crs16', 'autocompletedsingleuse')");
}
echo '<br>\n done ';

echo "<br>\n Change uitype of certain numberfields that were text until now... ";
$query = "UPDATE `vtiger_field` SET uitype = 7 WHERE uitype = 1 AND typeofdata LIKE 'N%';";
$adb->pquery($query, array());
echo "<br>\n uitype change done. ";

echo "<br>\n Delete files not needed for new SMS Notifier version ";
require_once('config.inc.php');
$filePath = "modules/SMSNotifier/views/CheckStatus.php";
$file = $root_directory.''.$filePath;
unlink($file);
echo "<br>\n file deletion done. ";


echo "<br>\n Set constraint for SMS Notifier tables";
// zuerst verwaiste Datensätze löschen (idempotent)
$query = "DELETE vtiger_smsnotifier FROM vtiger_smsnotifier
    LEFT JOIN vtiger_crmentity ON ( vtiger_crmentity.crmid = vtiger_smsnotifier.smsnotifierid)
    WHERE vtiger_smsnotifier.smsnotifierid IS NOT NULL
    AND vtiger_crmentity.crmid IS NULL;";
$adb->pquery($query, array());

// FK nur hinzufügen, wenn er noch nicht existiert
$fkCheck = $adb->pquery("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'vtiger_smsnotifier'
       AND CONSTRAINT_NAME = 'fk_crmid_vtiger_smsnotifier' ", []
);


if ($fkCheck && $adb->num_rows($fkCheck) == 0) {
    $query = "ALTER TABLE `vtiger_smsnotifier` ADD CONSTRAINT `fk_crmid_vtiger_smsnotifier` FOREIGN KEY (`smsnotifierid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE;";
    $adb->pquery($query, array());
    echo "<br>\n  set constraint done for vtiger_smsnotifier. ";
} else {
    echo "<br>\n \n  constraint fk_crmid_vtiger_smsnotifier already exists, skipping. ";
}


$query = "DELETE vtiger_smsnotifiercf FROM vtiger_smsnotifiercf
    LEFT JOIN vtiger_crmentity ON ( vtiger_crmentity.crmid = vtiger_smsnotifiercf.smsnotifierid)
    WHERE vtiger_smsnotifiercf.smsnotifierid IS NOT NULL
    AND vtiger_crmentity.crmid IS NULL;";
$adb->pquery($query, array());

$fkCheckCf = $adb->pquery(
    "SELECT CONSTRAINT_NAME 
     FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'vtiger_smsnotifiercf'
       AND CONSTRAINT_NAME = 'fk_crmid_vtiger_smsnotifiercf'",
    []
);
if ($fkCheckCf && $adb->num_rows($fkCheckCf) == 0) {
    $query = "ALTER TABLE `vtiger_smsnotifiercf` ADD CONSTRAINT `fk_crmid_vtiger_smsnotifiercf` FOREIGN KEY (`smsnotifierid`) REFERENCES `vtiger_smsnotifier` (`smsnotifierid`) ON DELETE CASCADE;";
    $adb->pquery($query, array());
    echo "<br>\n  set constraint done for vtiger_smsnotifiercf. ";
} else {
    echo "<br>\n  constraint fk_crmid_vtiger_smsnotifiercf already exists, skipping. ";
}


echo "<br>\n add primary key to vtiger_smsnotifier ";
{
    if (!hasPrimaryKey('vtiger_smsnotifier')) {
        $query = "ALTER TABLE vtiger_smsnotifier ADD PRIMARY KEY(smsnotifierid)";
        $res = $adb->pquery($query, array());
        if ($res) {
            echo "<br>\n add primary key done. ";
        } else {
            echo "<br>\n add primary key failed: " . $adb->database->errorMsg() . " ";
        }
    } else {
        echo "<br>\n primary key already exists, skipping. ";
    }
}


echo "<br>\n Delete files not needed for new CKeditor version... ";
require_once('config.inc.php');
$filePath = "libraries/jquery/ckeditor/ckeditor_php4.php";
$file = $root_directory.''.$filePath;
unlink($file);
$filePath = "libraries/jquery/ckeditor/ckeditor_php5.php";
$file = $root_directory.''.$filePath;
unlink($file);
echo "<br>\n done. ";


echo "<br>\n delete old theme ";
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
echo "<br>\n dir deletion done. ";


echo "<br>\n delete not needed lang files... ";
$dirPath = "libraries/jquery/ckeditor/plugins/a11yhelp/lang/";
$dir = $root_directory.''.$dirPath;

if (is_dir($dir)) {
  foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $filename) {
    if ($filename->isDir()) continue;
    unlink($filename);
  }
}
rmdir($dir);
echo "<br>\n lang files deletion done. ";


echo "Remove berliSoftphone as entity... ";
$query = "DELETE FROM `vtiger_ws_entity` WHERE `vtiger_ws_entity`.`name` = 'berliSoftphones';";
$adb->pquery($query, array());
echo "<br>\n uitype change done. ";


$module = Vtiger_Module::getInstance('Vendors');
if($module) {
    echo "<br>\n Add Documents related list to Vendor";
    // avoid duplicates
    $module->unsetRelatedList(Vtiger_Module::getInstance('Documents'), 'Documents','get_attachments');
    $module->setRelatedList(Vtiger_Module::getInstance('Documents'), 'Documents',Array('ADD','SELECT'),'get_attachments');
    echo "<br>\n Related List added ";
}

echo "<br>\n Alter com_vtiger_workflowtasks.task to MEDIUMTEXT if applicable..";
$adb->pquery("ALTER TABLE `com_vtiger_workflowtasks` CHANGE `task` `task` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL");

echo "<br>\n Alter vtiger_mailmanager_mailrecord.mbody to MEDIUMTEXT if applicable..";
$adb->pquery("ALTER TABLE `vtiger_mailmanager_mailrecord` CHANGE `mbody` `mbody` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL");

echo "<br>\n Alter vtiger_berlicleverreach_settings.accesstoken to VARCHAR(600) if applicable..";
$adb->pquery("ALTER TABLE `vtiger_berlicleverreach_settings` CHANGE `accesstoken` `accesstoken` VARCHAR( 600 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL");

echo "<br>\n update crmtogo settings if applicable..";
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


if (version_compare($installedtag, $current_release_tag) < 0) {
	echo '<br>\n Add INDEX to vtiger_email_track ';

    $idxCheck = $adb->pquery(
        "SHOW INDEX FROM `vtiger_email_track` WHERE Key_name = 'mailid'",
        []
    );
    ////
    if ($idxCheck && $adb->num_rows($idxCheck) == 0) {
        $query = "ALTER TABLE `vtiger_email_track` ADD INDEX (`mailid`);";
	    $adb->pquery($query, array());
        echo '<br>\n Adding INDEX done ';
    } else {
        echo '<br>\n INDEX mailid already exists, skipping. ';
    }

}

/* 
// unnötig da mandatory Module automatisch hier updated werden und die optionale sollten eher manuell installiert werden bei bedarf.
// übernommen aus den db_script 35 oder von früheren.
echo '<br>\n module update berliWidgets start ';
$moduleInstance = Vtiger_Module::getInstance("berliWidgets");
if($moduleInstance) {
	updateVtlibModule("berliWidgets", "packages/vtiger/mandatory/berliWidgets.zip");
}
echo '<br>\n module update berliWidgets done ';

echo '<br>\n module update berliCleverReach start ';
$moduleInstance = Vtiger_Module::getInstance("berliCleverReach");
if($moduleInstance) {
	updateVtlibModule("berliCleverReach", "packages/vtiger/optional/berliCleverReach.zip");
}
echo '<br>\n module update berliCleverReach done '; 
*/

$moduleName = 'Users';
$moduleInstance = Vtiger_Module::getInstance($moduleName);

if($moduleInstance) {
	// use vtlib to add new block
	$blockInstance = Vtiger_Block::getInstance('LBL_USERSIGNATUR', $moduleInstance);
	if (!$blockInstance) {
		echo '<br>\n adding LBL_USERSIGNATUR Block ...  ';
		$blockcf = new Vtiger_Block();
		$blockcf->label = 'LBL_USERSIGNATUR';
		$moduleInstance->addBlock($blockcf);
		echo '<br>\n adding LBL_USERSIGNATUR Block done ';
		
		$blockInstance = Vtiger_Block::getInstance('LBL_USERSIGNATUR', $moduleInstance);
		$newblockid = $blockInstance->id;
		
		if ($newblockid) {
			// move signature field
			$updateQuery = "UPDATE `vtiger_field` SET block = ? WHERE tablename = 'vtiger_users' AND columnname = 'signature' AND fieldname = 'signature'";
			$adb->pquery($updateQuery, array($newblockid));
		}
	}
}

echo "<br>\n Create berlicrm_recurringreferences table for calendar recurring events ";

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
echo "<br>\n create berlicrm_recurringreferences table done. ";

echo "<br>\n set proper format for modifiedtime and createdtime fields ";
$query = "Update `vtiger_field` set typeofdata ='DT~O' where columnname = 'modifiedtime' and typeofdata ='T~O';";
$adb->pquery($query, array());
$query = "Update `vtiger_field` set typeofdata ='DT~O' where columnname = 'createdtime' and typeofdata ='T~O';";
$adb->pquery($query, array());
echo "<br>\n set proper format done. ";

echo "<br>\n delete not needed files for new tcpt version ";
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
echo "<br>\n file deletion done ";

// dazu sollte kein Fremdschlussel existieren, sonst konnte es hier zu fehler kommen. 
echo "<br>\n remove fonts no longer available ";
$queryDel = "DELETE FROM berli_pdffonts WHERE fontid IN (22,23,24,25) ";
$adb->pquery($queryDel, array());
echo "<br>\n font deletion done ";


echo "<br>\n add new fonts ";

$fonts = [
    ['38', 'aealarabiya', 'aeAlArabiya'],
    ['39', 'aefurat', 'AeFurat'],
    ['40', 'courier', 'Courier'],
    ['41', 'freemono', 'Free Mono'],
    ['42', 'pdfacourier', 'PDFA Courier'],
    ['43', 'pdfahelvetica', 'PDFA Helvetika'],
    ['44', 'pdfasymbol', 'PDFA Symbol'],
    ['45', 'pdfatimes', 'PDFA Times'],
    ['46', 'times', 'Times'],
    ['47', 'kozminproregular', 'Kozminpro Regular'],
    ['48', 'kozgopromedium', 'Kozgopro Medium'],
    ['49', 'msungstdlight', 'Msungstd Light'],
    ['50', 'hysmyeongjostdmedium', 'Hysmyeongjostd Medium'],
];

foreach ($fonts as $font) {
    list($fontid, $tcpdfname, $namedisplay) = $font;

    $check = $adb->pquery(
        "SELECT 1 FROM `berli_pdffonts` WHERE `fontid` = ?",
        [$fontid]
    );

    if ($adb->num_rows($check) == 0) {
        $adb->pquery(
            "INSERT INTO `berli_pdffonts` (`fontid`, `tcpdfname`, `namedisplay`)
             VALUES (?, ?, ?)",
            [$fontid, $tcpdfname, $namedisplay]
        );
        echo "<br>\n font $fontid added ";
    } else {
        echo "<br>\n font $fontid already exists, skipping. ";
    }
}

echo "<br>\n adding new fonts done  ";


echo "<br>\n increase password field length for Mail Manager and MailScanner ";
$query = "ALTER TABLE `vtiger_mailscanner` CHANGE `password` `password` VARCHAR( 4000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ;";
$adb->pquery($query, array());
$query = "ALTER TABLE `vtiger_mail_accounts` CHANGE `mail_password` `mail_password` VARCHAR( 4000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;";
$adb->pquery($query, array());
echo "<br>\n increase done. ";

// Module tabid update //get all tabids for module berliCleverReach, Mailchimp
$arrModule = array('berliCleverReach', 'Mailchimp');

foreach($arrModule as $ModuleName){
	$moduleToUpdate = $ModuleName;
	if( Vtiger_Module::getInstance($moduleToUpdate) ){
		echo '<br>\n module '.$moduleToUpdate.' update start '; 
		//get all tabids for module berliCleverReach, Mailchimp
		$query = 'SELECT `tabid` FROM `vtiger_tab` WHERE `name`=?;';
		$result = $adb->pquery($query, array($moduleToUpdate));
		$numOfRows = $adb->num_rows($result);
		
        $tabidarr = [];
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
		echo '<br>\n module '.$moduleToUpdate.' update done  ';
	}
}


// Vendors and Service Relation
echo "<br>\n set relation in Vendors to Services (to add related list into Vendors), increase vtiger_relatedlists_seq number. ";
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
	echo "<br>\n Vendors to Services DB relation and increase done. ";
}
else{
	echo "<br>\n Vendors to Services DB relation and increase allready exist. Done. ";
}


//  for db create new table for email tracking   add email tracking to send function. Was last changed on 2025.03.24 tag 101
echo "<br>\n new table for email tracking ";
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
echo "<br>\n done new table for email tracking  ";


// add recurring frequency
$queryCheck = "SELECT 1 FROM `vtiger_recurring_frequency` WHERE `recurring_frequency` = ?";
$checkRes = $adb->pquery($queryCheck, array('every 4 months'));
if ($adb->num_rows($checkRes) == 0) {
	// control sort order
	echo "<br>\n add recurring frequency of 4 month ";
	$query = 'update `vtiger_recurring_frequency` set recurring_frequency_id = 7, sortorderid = 7 where recurring_frequency = ?';
	$res = $adb->pquery($query, array('Yearly'));
	if(!$res) {
		echo "Error: ".$adb->database->errorMsg();
	}
	echo "<br>\n sortoder done ";

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
echo "<br>\n recurring frequency done ";

// add 14 days payment interval
echo "<br>\n add 14 days payment interval ";
$queryCheck = "SELECT 1 FROM `vtiger_payment_duration` WHERE `payment_duration` = ?";
$checkRes = $adb->pquery($queryCheck, array('Net 14 days'));

if ($adb->num_rows($checkRes) == 0) {
	$query = 'UPDATE vtiger_payment_duration SET sortorderid = sortorderid + 1 WHERE sortorderid >= 1';
	$res = $adb->pquery($query, array());
	if(!$res) {
		echo "Error: ".$adb->database->errorMsg();
	}
	echo "<br>\n sortoder done ";

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
echo "<br>\n 14 days payment interval done ";


echo '<br>\n module Toolwidget update started ';

function checkAndAddLink($moduleName, $label, $url) {
    global $adb;
    
    $query = "SELECT 1 FROM vtiger_links WHERE tabid = (SELECT tabid FROM vtiger_tab WHERE name = ?) AND linklabel = ? AND linkurl = ?";
    $result = $adb->pquery($query, array($moduleName, $label, $url));
    
    if ($adb->num_rows($result) == 0) {
        $module = Vtiger_Module::getInstance($moduleName);
        if ($module) {
            $module->addLink('DETAILVIEWSIDEBARWIDGET', $label, $url);
            echo "<br>\n Link hinzugefügt für Modul: $moduleName ";
        } else {
            echo "<br>\n Modul nicht gefunden: $moduleName ";
        }
    } else {
        echo "<br>\n Link existiert bereits für Modul: $moduleName ";
    }
}

function deleteOldLinks($moduleName, $correctUrlPattern) {
    global $adb;

    // Ermittelt die tabid für das Modul
    $tabidQuery = "SELECT tabid FROM vtiger_tab WHERE name = ?";
    $tabidResult = $adb->pquery($tabidQuery, array($moduleName));
    if ($adb->num_rows($tabidResult) == 0) {
        echo "<br>\n Modul nicht gefunden: $moduleName ";
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

    echo "<br>\n Alte fehlerhafte Links für Modul $moduleName wurden entfernt. ";
}

$correctContactsUrl = 'module=ToolWidgets&view=showCopyPasteData&mode=showEntries&source_module=Contacts&viewtype=detail';
$correctAccountsUrl = 'module=ToolWidgets&view=showCopyPasteData&mode=showEntries&source_module=Accounts&viewtype=detail';

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

echo '<br>\n module update Toolwidget done ';

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
echo "<br>\n Updated vtiger_relatedlists successfully ";

echo "<br>\n  Start vtiger_schedulereports table update ";
// prufen ob table existiert, colum existieren, dann erst setzen.
if (!columnExists_db('attfolderid', 'vtiger_schedulereports')) {
    $query = "ALTER TABLE vtiger_schedulereports ADD COLUMN `attfolderid` INT(19) DEFAULT NULL";
    $adb->pquery( $query, []);
    echo "<br>\n Column attfolderid added. ";
} else {
    echo "<br>\n Column attfolderid already exists, skipping. ";
}

if (!columnExists_db('savetype', 'vtiger_schedulereports')) {
    $query = "ALTER TABLE vtiger_schedulereports ADD COLUMN `savetype` VARCHAR(30) DEFAULT NULL";
    $adb->pquery($query, []);
    echo "<br>\n Column savetype added. ";
} else {
    echo "<br>\n Column savetype already exists, skipping. ";
}
echo "<br>\n vtiger_schedulereports table updated successfully ";


/* InitSchema::addNewFields() macht bereits das selbe, siehe dazu modules\Install\models\InitSchema.php funktion ::addNewFields().
// Adding extra fields for eInvoice ... Rev.25134
echo "Start Adding extra fields for eInvoice";
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
    )
);
berli_addFields($arrFields);
*/

$fieldName = 'invoicestatus';
$tableName = 'vtiger_invoice';
$newValues = ['Mahnstufe 1', 'Mahnstufe 2', 'Mahnstufe 3', 'Mahnstopp'];

// Get field ID and table name
$result = $adb->pquery("SELECT fieldid FROM vtiger_field WHERE fieldname = ? AND tablename = ?", [$fieldName, $tableName]);
$fieldId = $adb->query_result($result, 0, 'fieldid');

// Get existing max sortorder
$result = $adb->pquery("SELECT MAX(sortorderid) AS maxsort FROM vtiger_$fieldName", []);
$sortId = (int)($adb->query_result($result, 0, 'maxsort'));

foreach ($newValues as $value) {
    // Check if value already exists
    $check = $adb->pquery("SELECT invoicestatusid, picklist_valueid FROM vtiger_$fieldName WHERE $fieldName = ?", [$value]);
    if ($adb->num_rows($check) > 0) {
        echo "<br>\n Value '$value' already exists, skipping. ";
        continue;
    }

    // Determine new IDs
    $sortId++;

    // get new picklist value ID
    $res = $adb->pquery("SELECT id FROM vtiger_picklistvalues_seq");
    $picklistValueId = (int)($adb->query_result($res, 0, 'id')) + 1;

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

    // Get picklist ID
    $picklistResult = $adb->pquery("SELECT picklistid FROM vtiger_picklist WHERE name = ?", [$fieldName]);
    if ($adb->num_rows($picklistResult) == 0) {
        echo "<br>\n Picklist '$fieldName' does not exist, skipping assignment. ";
        continue;
    }

    $picklistid = $adb->query_result($picklistResult, 0, 'picklistid');
    echo "<br>\n Picklist '$fieldName' exists with ID '$picklistid'. ";

    // Assign to all roles (idempotent)
    $roles = $adb->pquery("SELECT roleid FROM vtiger_role", []);
    for ($i = 0; $i < $adb->num_rows($roles); $i++) {
        $roleId = $adb->query_result($roles, $i, 'roleid');

        // Check if assignment already exists
        $checkRole = $adb->pquery(
            "SELECT 1 FROM vtiger_role2picklist WHERE roleid = ? AND picklistvalueid = ? AND picklistid = ?",
            [$roleId, $picklistValueId, $picklistid]
        );

        if ($adb->num_rows($checkRole) == 0) {
            $adb->pquery(
                "INSERT INTO vtiger_role2picklist (roleid, picklistvalueid, picklistid)
                 VALUES (?, ?, ?)",
                [$roleId, $picklistValueId, $picklistid]
            );

            echo "<br>\n Assigned picklist value $picklistValueId to role $roleId for picklist id $picklistid. ";
        } else {
            echo "<br>\n Role $roleId already assigned to picklist value $picklistValueId, skipping. ";
        }
    }

}


function isComposerUsed(string $projectPath = __DIR__): bool
{
    $composerJson = $projectPath . DIRECTORY_SEPARATOR . 'composer.json';
    $composerLock = $projectPath . DIRECTORY_SEPARATOR . 'composer.lock';

    return file_exists($composerJson) && file_exists($composerLock);
}

if (isComposerUsed('../..')) {
    echo '<pre>Composer config found, try to update project ...</pre>';
    putenv("COMPOSER_HOME=../..");
    $composerCmd = '';

    // Try 'composer --version' (works on Linux, macOS, and Windows if in PATH)
    exec('composer --version 2>&1', $output, $returnVar);
    if ($returnVar === 0 && isset($output[0]) && stripos($output[0], 'composer') !== false) {
        // hier konnte doppelte update update entstehen, solllte es aber nur einen geben. 
        $composerCmd = 'composer -d ../.. -n'; // hier war mal "update" da
        echo '<pre>' . $composerCmd . ' found: ' . $output[0] . '</pre>';
    } else {
        unset($output);
        exec('php ../../composer.phar --version 2>&1', $output, $returnVar);
        if ($returnVar === 0 && isset($output[0]) && stripos($output[0], 'composer') !== false) {
            $composerCmd = 'php ../../composer.phar -d ../.. -n'; // hier war mal "update" da
            echo '<pre>' . $composerCmd . ' found: ' . $output[0] . '</pre>';
        }
    }
    if (!empty($composerCmd)) {
        // hier konnte doppelte update update entstehen, solllte es aber nur einen geben. 
        echo '<pre>Running ' . $composerCmd . ' update ...</pre>';

        $cmd = "$composerCmd update 2>&1";
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);
        if ($returnCode === 0) {
            echo '<pre>Composer update completed successfully.</pre>';
        } else {
            echo '<pre>Composer update failed with return code: ' . $returnCode . '</pre>';
            echo '<pre>' . implode(PHP_EOL, $output) . '</pre>';
        }
    }
} else {
    echo '<pre>Composer is not installed. Please run installComposer.php first.</pre>';
}


$tagDir = basename(__DIR__);
if (strpos($tagDir, '1.0') === 0) {
    $newTag = 'berlicrm-'. $tagDir;
} elseif (strpos($tagDir, '25.4') === 0) {
    $newTag = 'crm-now-'. $tagDir;
} else {
    $newTag = 'unknown-'. $tagDir;
}
echo "end basename";


echo '<br>\n Adding UITypes for new fields ';
require_once('modules/Install/models/InitSchema.php');
Install_InitSchema_Model::addUITypes();

/* // InitSchema::addNewFields() macht bereits das selbe, siehe dazu modules\Install\models\InitSchema.php funktion ::addNewFields().
//// Rev 25666 von 2025.11.26
echo '<br>\n Adding Fields for HelpDesk crmnow15 ... ';
{
    $arrFields = array(
        // for HelpDesk
        'HelpDesk' => array(
            'LBL_TICKET_INFORMATION' => array(
                array('Owner', 'ticketowner', 'V~O', 'crmnow15', 'VARCHAR(100)', '', 'Der Prozessverantwortliche des Tickets.')
            )
        )
    );

    berli_addFields($arrFields);
}
echo "<br>\n Fields for HelpDesk added. ";
*/

//  Adding extra fields for eInvoice ... 
echo '<br>\n Adding new RelatedLists... START ';
try {
    Install_InitSchema_Model::addNewRelatedLists();
} catch (Throwable $e) {
    echo "<br>\n InitSchema::addNewRelatedLists() aborted, continuing update. ";
}
echo '<br>\n Adding new RelatedLists... DONE ';




echo "<br>\n Add new Field to Modul: Users, Block: ('LBL_USER_ADV_OPTIONS' ) 'erweiterte Optionen' ... ";
{
    // InitSchema::addNewFields() macht bereits das selbe, siehe dazu modules\Install\models\InitSchema.php funktion ::addNewFields().
    // ABER hier wird noch zusatzlich die Sequenz_Korrektur gemacht. Da die Funktion berli_addFields() fehler Abfängt, konnte es so stehen gelassen werden. 
    /*
    //  'LBL_USER_ADV_OPTIONS' => 'erweiterte Optionen',
    $arrFields = array(
        'Users' => array(
            'LBL_USER_ADV_OPTIONS' => array( 
                array('LBL_SSO_UNIQUE_ID' ,'sso_unique_id' ,'V~O' ,1 ,'VARCHAR(255)'   ),
            )
        )
    );
    berli_addFields($arrFields);
    */

    // need set the sequence to 2 to set it right from accesskey in the block. (But in Object we have no set sequence, no variable of it.)
    echo "<br>\n set sequence of 'sso_unique_id'. ";
    $query = "UPDATE `vtiger_field` SET `sequence` = '2' WHERE `vtiger_field`.`columnname` = 'sso_unique_id' ";
    $adb->pquery($query, array());
    echo "<br>\n  set sequence of 'sso_unique_id' to 2 done. ";
}
echo "<br>\n Add new Field to Modul: Users, Block: ('LBL_USER_ADV_OPTIONS' ) END ... ";


// ------------------------------------------------------------ //// see TAG 48 db_script START ////
// Add new fields to ModComments: "Block: LBL_OTHER_INFORMATION", "email metadata", 
// ------------------------------------------------------------
echo "<br>\n Add new fields to the module: ModComments, START ... ";
// Wenn diese Funktionen nicht idempotent sind : doppelte Felder / doppelte RelatedLists.
// Install_InitSchema_Model::addNewFields();

$modulesRequired = ['Accounts','Invoice','HelpDesk','ModComments','Users'];
$canRunAddNewFields = true;

foreach ($modulesRequired as $m) {
    if (!Vtiger_Module::getInstance($m)) {
        echo "<br>\n Skipping addNewFields(): Module $m not installed.!!!  ";
        $canRunAddNewFields = false;
    }
}

if ($canRunAddNewFields) {
    echo "<br>\n Can Add new fields, now Add new fields via InitSchema ... ";
    try {
        Install_InitSchema_Model::addNewFields();
    } catch (Throwable $e) {
        echo "<br>\n InitSchema::addNewFields() aborted, continuing update. ";
    }
   
} else {
    echo "<br>\n addNewFields() skipped safely. ";
}

echo "<br>\n Add new fields to the module: ModComments, END ... ";


// ------------------------------------------------------------
// Helper functions
// ------------------------------------------------------------
function tableExists($tableName) {
    global $adb;
    $tableNameEsc = $adb->sql_escape_string($tableName);
    $query = "SHOW TABLES LIKE '$tableNameEsc' ";
    $res = $adb->pquery($query, array());
    return ($res && $adb->num_rows($res) > 0);
}

function columnExists_db($column, $table) {
    global $adb;
    $res = $adb->pquery("SHOW COLUMNS FROM $table LIKE ? ", array($column));
    return ($res && $adb->num_rows($res) > 0);
}

function recordExists($sql, $params = []) {
    global $adb;
    $res = $adb->pquery($sql, $params);
    return ($res && $adb->num_rows($res) > 0);
}

function wsOperationExists($name) {
    global $adb;
    $res = $adb->pquery("SELECT 1 FROM vtiger_ws_operation WHERE name = ? ", [$name]);
    return ($res && $adb->num_rows($res) > 0);
}

function relatedListExists($tabid, $relatedTabid, $name) {
    global $adb;
    $res = $adb->pquery(
        "SELECT 1 FROM vtiger_relatedlists WHERE tabid = ? AND related_tabid = ? AND name = ? ",
        [$tabid, $relatedTabid, $name]
    );
    return ($res && $adb->num_rows($res) > 0);
}


// ------------------------------------------------------------
// Migration from vtiger_modcommentsscope to vtiger_modcomments // von db_script48
// ------------------------------------------------------------
echo "<br>\n Migrate ModComments fields from vtiger_modcommentsscope START... ";

$sourceTable = 'vtiger_modcommentsscope';
$targetTable = 'vtiger_modcomments';
$requiredColumns = array('mailto', 'external', 'timeneeded');

$canMigrate = true;

// Check source table
if (!tableExists($sourceTable)) {
    echo "<br>\n Source table '$sourceTable' not found, nothing to migrate. ";
    $canMigrate = false;
}

// Check target table
if (!tableExists($targetTable)) {
    echo "<br>\n Target table '$targetTable' not found, migration skipped. ";
    $canMigrate = false;
}

// Check required columns
$missingColumns = array();
foreach ($requiredColumns as $col) {
    if (!columnExists_db($col, $targetTable)) {
        $missingColumns[] = $col;
    }
}

if (!empty($missingColumns)) {
    echo "<br>\n Missing columns in '$targetTable': " . implode(', ', $missingColumns) . ". Migration skipped. ";
    $canMigrate = false;
}

// Check if target table already contains data (do not overwrite newer values)
$checkQuery = "SELECT COUNT(*) AS cnt FROM $targetTable
    WHERE (mailto IS NOT NULL AND mailto <> '')
    OR (external IS NOT NULL AND external <> '')
    OR (timeneeded IS NOT NULL AND timeneeded <> '')
";
$checkRes = $adb->pquery($checkQuery, array());
$hasData = $adb->query_result($checkRes, 0, 'cnt');

if ($hasData > 0) {
    echo "<br>\n Target table already contains data, migration skipped. ";
    $canMigrate = false;
}

// ------------------------------------------------------------
// Execute migration
// ------------------------------------------------------------
if ($canMigrate) {
    echo "<br>\n Running migration... ";
    $query = " UPDATE vtiger_modcomments mc
        INNER JOIN vtiger_modcommentsscope ms ON mc.modcommentsid = ms.modcommentsid
        SET mc.mailto = COALESCE(NULLIF(mc.mailto,''), ms.mailto),
            mc.external = COALESCE(NULLIF(mc.external,''), ms.external),
            mc.timeneeded = COALESCE(NULLIF(mc.timeneeded,''), ms.timeneeded);
    ";

    $res = $adb->pquery($query, array());

    if (!$res) {
        echo "<br>\n Error: " . $adb->database->errorMsg() . " ";
    } else {
        $affected = $adb->getAffectedRowCount($res);
        echo "<br>\n Migrated $affected records into '$targetTable'. ";
    }
}

echo "<br>\n Migrate ModComments fields from vtiger_modcommentsscope END....";


echo "<br>\n New tables for config settings to set on email sending from ticket another email or name  ";
$errors = Install_InitSchema_Model::addNewConfigEditSettingTables();
if(!empty($errors)){
    echo $errors;
}
echo "<br>\n New tables done ";


// do some table modifications
echo "<br>\n Install_InitSchema_Model::alterTables() START ";
if (method_exists('Install_InitSchema_Model', 'alterTables')) {
    try {
        $status = Install_InitSchema_Model::alterTables();
        if ($status !== true) {
            echo "Errors (Check Index to get Query):<pre>";
            var_dump($status);
            echo "</pre>";
        }
    } catch (Throwable $e) {
        echo "<br>\n InitSchema::alterTables() aborted, continuing update. ";
    }
} else {
    echo "<br>\n InitSchema::alterTables() not implemented, skipping. ";
}
echo "<br>\n Install_InitSchema_Model::alterTables() END ";


// ---------------------------------------------------------
// EmailConfigurator installation into Settings
// ---------------------------------------------------------
echo "<br>\n Install Email Configurator into Settings START ";
// Check if EmailConfigurator already exists
$checked = $adb->pquery( "SELECT fieldid FROM vtiger_settings_field WHERE vtiger_settings_field.name = 'LBL_EMAILCONFIGURATOR'", [] );
if ($adb->num_rows($checked) == 0) {

    // Retrieve the current maximum fieldid from vtiger_settings_field
    $sql0 = "SELECT MAX(fieldid) AS max_fieldid FROM vtiger_settings_field";
    $params0 = array();
    $res0 = $adb->pquery($sql0, $params0);
    $max_fieldid = 0;

    while ($rowSelect = $adb->getNextRow($res0, false)) {
        $max_fieldid = $rowSelect['max_fieldid'];
    }

    if ($max_fieldid > 0) {

        // ---------------------------------------------------------
        // (001) Insert new Settings entry for EmailConfigurator
        // Block ID 5 = "Integration" section in Settings
        // ---------------------------------------------------------
        echo "<br>\n (001) Insert new entry into vtiger_settings_field ... ";

        $sql1 = "INSERT INTO `vtiger_settings_field`
            (`fieldid`, `blockid`, `name`, `iconpath`, `description`, `linkto`, `sequence`, `active`, `pinned`)
            VALUES
            (?, 5, 'LBL_EMAILCONFIGURATOR', NULL, 'LBL_EMAILCONFIGURATOR_DESCRIPTION',
            'index.php?module=EmailConfigurator&view=Index&parent=Settings', 14, 0, 0)
        ";
        $params1 = array($max_fieldid + 1);
        $res1 = $adb->pquery($sql1, $params1);

        // ---------------------------------------------------------
        // (002) Update sequence table for vtiger_settings_field
        // ---------------------------------------------------------
        echo "<br>\n (002) Update vtiger_settings_field_seq ... ";

        $sql2 = "UPDATE `vtiger_settings_field_seq` SET `id` = ?";
        // Eigentlich sollte da immer der maxid sein. Aber ich habe schon gesehen, dass dort um eins hoher stehen konnte.
        $params2 = array($max_fieldid + 1);
        $res2 = $adb->pquery($sql2, $params2);

        // ---------------------------------------------------------
        // (003) Create EmailConfigurator data table
        // Stores configured sender addresses
        // ---------------------------------------------------------
        echo "<br>\n (003) Create table crmnow_emailconfig ... ";

        $sql3 = "CREATE TABLE IF NOT EXISTS `crmnow_emailconfig` (
            `emailid` int(11) NOT NULL AUTO_INCREMENT,
            `email_firstname` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
            `email_lastname` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
            `email_address` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
            `email_desc` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
            PRIMARY KEY (`emailid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=11;";
        $params3 = array();
        $res3 = $adb->pquery($sql3, $params3);

        // ---------------------------------------------------------
        // (004) Create sequence table for EmailConfigurator
        // ---------------------------------------------------------
        echo "<br>\n (004) Create table crmnow_emailconfig_seq ... ";

        $sql4 = "CREATE TABLE IF NOT EXISTS `crmnow_emailconfig_seq` (
            `id` int(11) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $params4 = array();
        $res4 = $adb->pquery($sql4, $params4);

        // ---------------------------------------------------------
        // (005) Initialize sequence table with starting value
        // ---------------------------------------------------------
        echo "<br>\n (005) Insert initial sequence value into crmnow_emailconfig_seq ...";
        // Check if sequence table already has a row
        $seqCheck = $adb->pquery("SELECT COUNT(*) AS cnt FROM crmnow_emailconfig_seq", []);
        $seqCount = (int)$adb->query_result($seqCheck, 0, 'cnt');

        if ($seqCount == 0) {
            // Insert initial sequence value only once
            $sql5 = "INSERT INTO crmnow_emailconfig_seq (id) VALUES (?)";
            $params5 = array(0);
            $res5 = $adb->pquery($sql5, $params5);
            echo "<br>\n Sequence initialized. ";
        } else {
            echo "<br>\n Sequence already initialized, skipping. ";
        }

    }
}
echo "<br>\n Install Email Configurator into Settings END ";

///////////////////////////////////////////////////////////////////////////
// on the End, we set update of Version Nummer and updates of all Modules:
berli_updateAllModules();

$query = "UPDATE `vtiger_version` SET `tag_version` = ?";
$adb->pquery($query, array($current_release_tag));
echo "<h2>Finished updating to $current_release_tag!</h2>";
