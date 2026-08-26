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

// do some table modifications
echo "Install_InitSchema_Model::alterTables() START<br>";
$status = Install_InitSchema_Model::alterTables();
if ($status !== true) {
	echo "Errors (Check Index to get Query):<pre>";
	var_dump($status);
	echo "</pre>";
}
echo "Install_InitSchema_Model::alterTables() END<br>";

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
            if ($packageName == 'ModComments' || $packageName == 'Projects' || $packageName == 'MailManager' || $packageName == 'Search') {
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

echo "Add ModComments email metadata fields START...<br>";
Install_InitSchema_Model::addNewFields();
echo "Add ModComments email metadata fields END...<br>";

echo "Update Packages DONE<br><br>";



// ---------------------------------------------------------
// EmailConfigurator installation into Settings
// ---------------------------------------------------------
echo "Install Email Configurator into Settings START<br>";
{
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
        echo "(001) Insert new entry into vtiger_settings_field ...<br>";

        $sql1 = "INSERT INTO `vtiger_settings_field`
                (`fieldid`, `blockid`, `name`, `iconpath`, `description`, `linkto`, `sequence`, `active`, `pinned`)
                VALUES
                (?, 5, 'LBL_EMAILCONFIGURATOR', NULL, 'LBL_EMAILCONFIGURATOR_DESCRIPTION',
                'index.php?module=EmailConfigurator&view=Index&parent=Settings', 14, 0, 0)";
        $params1 = array($max_fieldid + 1);
        $res1 = $adb->pquery($sql1, $params1);

        // ---------------------------------------------------------
        // (002) Update sequence table for vtiger_settings_field
        // ---------------------------------------------------------
        echo "(002) Update vtiger_settings_field_seq ...<br>";

        $sql2 = "UPDATE `vtiger_settings_field_seq` SET `id` = ?";
        $params2 = array($max_fieldid + 2);
        $res2 = $adb->pquery($sql2, $params2);

        // ---------------------------------------------------------
        // (003) Create EmailConfigurator data table
        // Stores configured sender addresses
        // ---------------------------------------------------------
        echo "(003) Create table crmnow_emailconfig ...<br>";

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
        echo "(004) Create table crmnow_emailconfig_seq ...<br>";

        $sql4 = "CREATE TABLE IF NOT EXISTS `crmnow_emailconfig_seq` (
            `id` int(11) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $params4 = array();
        $res4 = $adb->pquery($sql4, $params4);

        // ---------------------------------------------------------
        // (005) Initialize sequence table with starting value
        // ---------------------------------------------------------
        echo "(005) Insert initial sequence value into crmnow_emailconfig_seq ...<br>";

        $sql5 = "INSERT INTO `crmnow_emailconfig_seq` (`id`) VALUES (?)";
        $params5 = array(0);
        $res5 = $adb->pquery($sql5, $params5);

    }
}
echo "Install Email Configurator into Settings END<br>";

echo "<br>update Tag version to 47.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.1.0.1'";
$adb->pquery($query, array());
echo " Tag version done.<br>";


