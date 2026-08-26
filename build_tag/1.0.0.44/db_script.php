<!DOCTYPE html><html>
<meta http-equiv="cache-control" content="no-cache, must-revalidate, post-check=0, pre-check=0" />
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<?php
ini_set('include_path', ini_get('include_path').':../..');
require_once('include/utils/utils.php');
// include_once('vtlib/Vtiger/Module.php');
// include_once('include/Webservices/Utils.php');
// require_once('vtlib/Vtiger/Package.php');
// global $adb;


error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED);
ini_set('display_errors', 'on');
global $current_user;
// $current_user = Users::getActiveAdminUser();
$adb = PearDatabase::getInstance();
// die('<pre>Hallo');


// ########################################################
echo "<pre>start";


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

if (isComposerUsed('../..')) {
    echo '<pre>Composer config found, try to update project ...</pre>';
    putenv("COMPOSER_HOME=../..");
    $composerCmd = '';

    // Try 'composer --version' (works on Linux, macOS, and Windows if in PATH)
    exec('composer --version 2>&1', $output, $returnVar);
    if ($returnVar === 0 && isset($output[0]) && stripos($output[0], 'composer') !== false) {
        $composerCmd = 'composer -d ../.. -n update';
        echo '<pre>' . $composerCmd . ' found: ' . $output[0] . '</pre>';
    } else {
        unset($output);
        exec('php ../../composer.phar --version 2>&1', $output, $returnVar);
        if ($returnVar === 0 && isset($output[0]) && stripos($output[0], 'composer') !== false) {
            $composerCmd = 'php ../../composer.phar -d ../.. -n update';
            echo '<pre>' . $composerCmd . ' found: ' . $output[0] . '</pre>';
        }
    }
    if (!empty($composerCmd)) {
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

echo "<br>update Tag version to 44.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.44'";
$adb->pquery($query, array());
echo " Tag version done.<br>";



exit;

function isComposerUsed(string $projectPath = __DIR__): bool
{
    $composerJson = $projectPath . DIRECTORY_SEPARATOR . 'composer.json';
    $composerLock = $projectPath . DIRECTORY_SEPARATOR . 'composer.lock';

    return file_exists($composerJson) && file_exists($composerLock);
}

echo "<br>update Tag version to 40.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.44'";
$adb->pquery($query, array());
echo " Tag version done.<br>";
