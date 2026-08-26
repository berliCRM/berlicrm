<!DOCTYPE html><html>
<meta http-equiv="cache-control" content="no-cache, must-revalidate, post-check=0, pre-check=0" />
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<?php
ini_set('include_path',ini_get('include_path').':../..');
require_once('includes/main/WebUI.php');
require_once('include/utils/utils.php');
include_once('vtlib/Vtiger/Module.php');
include_once('include/Webservices/Utils.php');
require_once('vtlib/Vtiger/Package.php');
global $adb;

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
            if ($packageName == 'Import' || $packageName == 'ModComments' || $packageName == 'Projects') {
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

// add new webservice operation get_new_multi_relations
echo "Adding new Webservices 'get_new_multi_relations' START<br>";
$query = "SELECT * FROM vtiger_ws_operation WHERE name = ?;";
$res = $adb->pquery($query, array('get_new_multi_relations'));
if ($res && $adb->num_rows($res) == 0) {
	$operationId = vtws_addWebserviceOperation('get_new_multi_relations', 'include/Webservices/Custom/getNewMultiRelations.php', 'berli_get_new_multi_relations', 'GET', '0');
	if ($operationId) {
		vtws_addWebserviceOperationParam($operationId,'id','string','1');
		vtws_addWebserviceOperationParam($operationId,'relModule','string','2');
		echo "Adding new Webservices 'get_new_multi_relations' DONE<br>";
	} else {
		echo "ERROR WHILE TRYING TO GET WS-ID!<br>";
	}
} else {
	$errorMsg = $adb->database->errorMsg();
	if (!empty($errorMsg)) {
		echo "QUERY ERROR: ".$adb->database->errorMsg().'<br>';
	} elseif ($adb->num_rows($res) != 0) {
		echo "Webservices 'get_new_multi_relations' already present<br>";
	} else {
		echo 'UNKNOWN ERROR<br>';
	}
}
echo '<br>';

echo 'Adding UITypes for new fields<br>';
require_once('modules/Install/models/InitSchema.php');
Install_InitSchema_Model::addUITypes();

echo 'Adding Fields...<br>';#
$arrFields = array(
    // for HelpDesk
    'HelpDesk' => array(
        'LBL_TICKET_INFORMATION' => array(
            array('Owner', 'ticketowner', 'V~O', 'crmnow15', 'VARCHAR(100)', '', 'Der Prozessverantwortliche des Tickets.')
        )
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
echo "Fields added.<br><br>";

echo "<br>update Tag version to 45.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.45'";
$adb->pquery($query, array());
echo " Tag version done.<br>";
