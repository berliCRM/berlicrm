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

echo '<br>module MailManager update start<br>';
//update MailManager module
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
			if ($packageName =='MailManager') {
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

echo '<br>module install MailManager done <br>';

echo "<br>set proper format for modifiedtime and createdtime fields<br>";
$query = "Update `vtiger_field` set typeofdata ='DT~O' where columnname = 'modifiedtime' and typeofdata ='T~O';";
$adb->pquery($query, array());
$query = "Update `vtiger_field` set typeofdata ='DT~O' where columnname = 'createdtime' and typeofdata ='T~O';";
$adb->pquery($query, array());
echo "set proper format done.<br>";


echo '<br>module gdpr update start<br>';
//update gdpr module
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
			if ($packageName =='gdpr') {
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
echo '<br>module update gdpr done <br>';


echo '<br>module Pdfsettings update start<br>';
//update Pdfsettings module
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
			if ($packageName =='Pdfsettings') {
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
echo '<br>module update Pdfsettings done <br>';


echo "<br>delete not needed files for new tcpt version ";

require_once('config.inc.php');
$dirname = $root_directory.'libraries/tcpdf/barcode';
array_map('unlink', glob("$dirname/*.*"));
rmdir($dirname);

$dirname = $root_directory.'libraries/tcpdf/config/lang';
array_map('unlink', glob("$dirname/*.*"));
rmdir($dirname);

$filePath = 'libraries/tcpdf/config/tcpdf_config_alt.php';
$file = $root_directory.''.$filePath;
unlink($file);

$dirname = $root_directory.'libraries/tcpdf/doc/com.tecnick.tcpdf';
array_map('unlink', glob("$dirname/*.*"));
rmdir($dirname);


$dirname = $root_directory.'libraries/tcpdf/doc/com-tecnick-tcpdf';
array_map('unlink', glob("$dirname/*.*"));
rmdir($dirname);


$dirname = $root_directory.'libraries/tcpdf/doc/media';
array_map('unlink', glob("$dirname/*.*"));
rmdir($dirname);


$dirname = $root_directory.'libraries/tcpdf/doc';
array_map('unlink', glob("$dirname/*.*"));
rmdir($dirname);

//$dirname = $root_directory.'libraries/tcpdf/fonts';
//deleteDir($dirname);

$filePath = 'libraries/tcpdf/fonts/old/.noencode';
$file = $root_directory.''.$filePath;
unlink($file);
$filePath = 'libraries/tcpdf/fonts/.noencode';
$file = $root_directory.''.$filePath;
unlink($file);

$dirname = 'libraries/tcpdf/fonts/old';
rmdir($dirname);

$dirname = $root_directory.'libraries/tcpdf/images';
array_map('unlink', glob("$dirname/*.*"));
rmdir($dirname);

$dirname = $root_directory.'libraries/tcpdf/templates';
array_map('unlink', glob("$dirname/*.*"));
rmdir($dirname);

$filePath = 'libraries/tcpdf/2dbarcodes.php';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/barcodes.php';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/datamatrix.php';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/encodings_maps.php';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/htmlcolors.php';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/pdf.php';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/pdf417.php';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/pdfconfig.php';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/qrcode.php';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/README.TXT';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/spotcolors.php';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/sRGB.icc';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/tcpdf.crt';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/tcpdf.fdf';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/tcpdf.p12';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/tcpdf.pem';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/tcpdf_filters.php';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/tcpdf_parser.php';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/test_old.php';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/test_unicode.php';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/unicode_data.php';
$file = $root_directory.''.$filePath;
unlink($file);

$filePath = 'libraries/tcpdf/utf8test.txt';
$file = $root_directory.''.$filePath;
unlink($file);


echo "<br>file deletion done<br>";

function deleteDir($path) {
    if (empty($path)) { 
        return false;
    }
    return is_file($path) ?
            @unlink($path) :
            array_map(__FUNCTION__, glob($path.'/*')) == @rmdir($path);
}

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

echo '<br>module Verteiler update start<br>';
//update Verteiler module
$moduleFolders = array('packages/vtiger/optional');
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
echo '<br>module install Verteiler done <br>';


echo "<br>update Tag version to 33.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.33'";
$adb->pquery($query, array());
echo " Tag version done.<br>";