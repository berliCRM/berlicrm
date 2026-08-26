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
            if ($packageName == 'ToolWidgets' || $packageName == 'RecycleBin' || $packageName == 'Pdfsettings') {
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

echo '<br>module Verteiler and Import update start<br>';
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
			if ($packageName =='Verteiler' || $packageName =='Import') {
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
echo '<br>module update Verteiler and Import done <br>';

echo "<br>update Tag version to 42.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.42'";
$adb->pquery($query, array());
echo " Tag version done.<br>";
