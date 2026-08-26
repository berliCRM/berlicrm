<!DOCTYPE html><html>
<meta http-equiv="cache-control" content="no-cache, must-revalidate, post-check=0, pre-check=0" />
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<?php
ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING
require_once('includes/main/WebUI.php');
require_once('include/utils/utils.php');
include_once('vtlib/Vtiger/Module.php');
include_once('include/Webservices/Utils.php');
require_once('vtlib/Vtiger/Package.php');
global $adb;

// Logischerweise sollte das Loschen der Tabelle nach Migration stehen, aber wir sollten hier schon den db_script beenden, damit es nicht doppelt ausgefuhrt wird.
$deleteChoice = isset($_POST['delete_modcommentsscope']) ? strtolower(trim($_POST['delete_modcommentsscope'])) : null;
if ($deleteChoice !== null) {
    echo "Delete decision received for table 'vtiger_modcommentsscope' ...<br>";
    if ($deleteChoice === 'yes') {
        echo "Removing table 'vtiger_modcommentsscope' ...<br>";
        $query = 'DROP TABLE `vtiger_modcommentsscope`';
        $res = $adb->pquery($query, array());
        if (!$res) {
            echo "Error: " . $adb->database->errorMsg() . "<br>";
        } else {
            echo "Table 'vtiger_modcommentsscope' deleted successfully.<br>";
        }
        echo "Removing table 'vtiger_modcommentsscope' END ...<br>";
    } else {
        echo "Skipping deletion of table 'vtiger_modcommentsscope' based on user choice.<br>";
    }
    // db_script hier beenden. 
    exit;
    die;
}

//  'LBL_USER_ADV_OPTIONS' => 'erweiterte Optionen',
echo "Add new Field to Modul: Users, Block: ('LBL_USER_ADV_OPTIONS' ) 'erweiterte Optionen' ...<br>";
$arrFields = array(
    'Users' => array(
        'LBL_USER_ADV_OPTIONS' => array( 
            array('LBL_SSO_UNIQUE_ID' ,'sso_unique_id' ,'V~O' ,1 ,'VARCHAR(255)'   ),
        )
    )
);

foreach ($arrFields as $moduleName => $blocks) {
    $moduleInstance = Vtiger_Module::getInstance($moduleName);
    if (!$moduleInstance) {
        die("$moduleName no instance");
    }

    foreach ($blocks as $blockName => $fieldInfos) {
        $blockInstance = Vtiger_Block::getInstance($blockName, $moduleInstance);

        if (!$blockInstance) {
            // create new block
            $blockInstance = new Vtiger_Block();
            $blockInstance->label = $blockName;
            $moduleInstance->addBlock($blockInstance);

        } else {
            // if need to del the BLOCK:
            // $blockInstance->delete();
        }

        foreach ($fieldInfos as $fieldInfo) {
            $fieldObj = Vtiger_Field::getInstance($fieldInfo[1], $moduleInstance);
            if (!$fieldObj) {
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
                // $fieldObj->delete();
            }
        }
    }
}
// need set the sequence to 2 to set it right from accesskey in the block. (But in Object we have no set sequence, no variable of it.)
echo "<br>set sequence of 'sso_unique_id'. ";
$query = "UPDATE `vtiger_field` SET `sequence` = '2' WHERE `vtiger_field`.`columnname` = 'sso_unique_id' ";
$adb->pquery($query, array());
echo " set sequence of 'sso_unique_id' to 2 done.<br>";
echo "Add new Field to Modul: Users, Block: ('LBL_USER_ADV_OPTIONS' ) END ...<br>";

// Adding ModComments Fields
echo "Add new fields to the module: ModComments, Block: LBL_OTHER_INFORMATION ...<br>";
Install_InitSchema_Model::addNewFields();
echo "Add new fields to the module: ModComments, Block: LBL_OTHER_INFORMATION END ...<br>";

echo "Update Packages START...<br>";
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
            if ($packageName == 'ModComments' || $packageName == 'Search') {
                echo "&nbsp;Update package $packageName START<br>";
                $packagepath = "$moduleFolder/$file";
                $package = new Vtiger_Package();
                $module = $package->getModuleNameFromZip($packagepath);
                if ($module != null) {
                    $moduleInstance = Vtiger_Module::getInstance($module);
                    if ($moduleInstance) {
                        updateVtlibModule($module, $packagepath);
                    } else {
                        installVtlibModule($module, $packagepath);
                    }
                }
                echo "&nbsp;Update package $packageName DONE<br>";
            }
        }
        closedir($handle);
    }
}
echo "Update Packages DONE<br><br>";

echo "Migrate ModComments fields from vtiger_modcommentsscope START...<br>";
function tableExists($tableName) {
    global $adb;
    // damit es richtig ausgefuhrt und als String erkannt wird, zuerst escapen. (seltsamerweise einfach mit ? geht nicht).
    $tableNameEsc = $adb->sql_escape_string($tableName);
    $query = "SHOW TABLES LIKE '$tableNameEsc'";
    $res = $adb->pquery($query, array());
    return ($res && $adb->num_rows($res) > 0);
}

$sourceTable = 'vtiger_modcommentsscope';
$targetTable = 'vtiger_modcomments';
$requiredColumns = array('mailto', 'external', 'timeneeded');

$canMigrate = true;

if (!tableExists($sourceTable)) {
    echo "Source table '$sourceTable' not found, nothing to migrate.<br>";
    $canMigrate = false;
}

if (!tableExists($targetTable)) {
    echo "Target table '$targetTable' not found, migration skipped.<br>";
    $canMigrate = false;
}

$missingColumns = array();
foreach ($requiredColumns as $col) {
    if (!columnExists($col, $targetTable)) {
        $missingColumns[] = $col;
    }
}

if (!empty($missingColumns)) {
    echo "Missing columns in '$targetTable': " . implode(', ', $missingColumns) . ". Migration skipped.<br>";
    $canMigrate = false;
}

// Migration nur machen, wenn alles OK
if($canMigrate){
    echo "Running migration...<br>";
    $query = "UPDATE vtiger_modcomments mc
    INNER JOIN vtiger_modcommentsscope ms ON mc.modcommentsid = ms.modcommentsid
    SET mc.mailto = COALESCE(NULLIF(mc.mailto,''), ms.mailto),
        mc.external = COALESCE(NULLIF(mc.external,''), ms.external),
        mc.timeneeded = COALESCE(NULLIF(mc.timeneeded,''), ms.timeneeded);
    ";
    $res = $adb->pquery($query, array());
    if (!$res) {
    echo "Error: " . $adb->database->errorMsg() . "<br>";
    } 
    else {
        $affected = $adb->getAffectedRowCount($res);
        echo "Migrated $affected records into '$targetTable'.<br>";
    }
}

echo "Migrate ModComments fields from vtiger_modcommentsscope END....<br>";

echo "New tables for config settings to set on email sending from ticket another email or name <br>";
$errors = Install_InitSchema_Model::addNewConfigEditSettingTables();
if(!empty($errors)){
    echo $errors;
}
echo "New tables done<br>";

echo "Delete prompt for table 'vtiger_modcommentsscope' ...<br>";
echo "<form method='post' style='margin:10px 0;'>";
echo "<label>Do you want to delete table <strong>vtiger_modcommentsscope</strong>?</label><br>";
echo "<button type='submit' name='delete_modcommentsscope' value='yes'>Yes, delete table</button> ";
echo "<button type='submit' name='delete_modcommentsscope' value='no'>No, keep table</button>";
echo "</form>";
echo "Awaiting user decision for deleting 'vtiger_modcommentsscope'.<br>";


echo "<br>update Tag version to 48.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.48'";
$adb->pquery($query, array());
echo " Tag version done.<br>";

