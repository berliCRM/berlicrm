<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/
require_once('include/database/PearDatabase.php');
require_once('include/utils/UserInfoUtil.php');
require_once('include/utils/utils.php');
global $adb,$log;
$log->debug("Entering into UpdatePDF Settings");
// all modules available
$pdfmodule = array ('Quotes','Invoice','SalesOrder','PurchaseOrder');
$entrylevel = $_REQUEST['fld_module'];

foreach ($pdfmodule as $modulename) {
	if ($entrylevel!='1') {
		//get PDF permissions from Settings
		$pdfsettings_query="select * from berli_pdfsettings where pdfmodul='".$modulename."'";
		$pdfsettings = $adb->pquery($pdfsettings_query,array());
		$noofpickrows = $adb->num_rows($pdfsettings);
		for($j = 0; $j < $noofpickrows; $j++) {
			if ($adb->query_result($pdfsettings,$j,'pdfeditable')==1){
				$pdfpermission[$modulename][$adb->query_result($pdfsettings,$j,'pdffieldname')]= 1;
			}
			else {
				$pdfpermission[$modulename][$adb->query_result($pdfsettings,$j,'pdffieldname')]= 0;
			}
		}
	}
	//define changeable columnlist for all module
	$changablecolumns = array ('position' ,'ordercode' ,'unit' ,'discount');
	//unset columnlist
	$noofrows = count ($changablecolumns);
	for($i=0; $i<$noofrows; $i++) {
		$update_query = "update berli_pdfcolums_sel set ".$changablecolumns[$i]."=? where pdfmodul=? ";
		$update_params = array('',$modulename);
		$adb->pquery($update_query, $update_params);
		}
	//get data
	foreach ($_REQUEST as $key=>$value) {
		$requestarry = explode ("_",$key);
		//get and format edit permission settings, identified by "-perm"
		if ($requestarry[2]=='perm'){
			if ($requestarry[0]== $modulename)
			//get fieldname
			$removedpermissions [$modulename][$requestarry[1]]=  0;
		}
		//get and format config data checkboxes
		elseif ($requestarry[2]=='ic' OR $requestarry[2]=='qc' OR $requestarry[2]=='sc' OR $requestarry[2]=='pc'){
			if (strtolower(substr($modulename,0,1))==substr($requestarry[2],0,1)) {
				if ($value =='on') $setvalue ='true';
				else $setvalue = 'false';
				$configuration[$modulename][$requestarry[1]]=$setvalue;
			}
		}
		//get and format config data values
		elseif ($requestarry[2]=='iv' OR $requestarry[2]=='qv' OR $requestarry[2]=='sv' OR $requestarry[2]=='pv'){
			if (strtolower(substr($modulename,0,1))==substr($requestarry[2],0,1))
				$configuration[$modulename][$requestarry[1]]=$value;
		}
		//get and format column data
		elseif ($requestarry[1] =='group' or $requestarry[1]='individual'){
	
			if ($modulename==$requestarry[0]) {
				if ($value =='on') $setvalue ='checked';
				else $setvalue = '';
				//write set values to db
				if ($pdfpermission[$modulename][$requestarry[2]] != 1){	
					$columnsettings[$modulename][$requestarry[1]][$requestarry[2]] =$setvalue;
					$update_query = "update berli_pdfcolums_sel set ".strtolower($requestarry[2])."=? where pdfmodul=? and pdftaxmode=?";
					$update_params = array($setvalue,$requestarry[0],$requestarry[1]);
					$adb->pquery($update_query, $update_params);
				}
			}
		}
	}


	//save permission list if Settings
	if ($entrylevel=='1'){
		$fieldlist = getPDFSetList($modulename);
		foreach ($fieldlist as $fieldvalue) {
			if (!isset($removedpermissions[$modulename][$fieldvalue]))
				$permissionlist[$modulename][$fieldvalue] = 0;
			else $permissionlist[$modulename][$fieldvalue] = 1;
			$update_query = "update berli_pdfsettings set pdfeditable=? where pdffieldname=? and pdfmodul=?";
			$update_params = array($permissionlist[$modulename][$fieldvalue],$fieldvalue,$modulename);
			$adb->pquery($update_query, $update_params);
			}
	}
	//save config list
	$fieldlist = getPDFConfigList($modulename);
	//remove the first two entries (id, modul name)
	unset($fieldlist[0]);
	unset($fieldlist[1]);
	foreach ($fieldlist as $fieldvalue) {
		if (isset($configuration[$modulename][$fieldvalue]))
			$configurationlist[$modulename][$fieldvalue] = $configuration[$modulename][$fieldvalue];
		else $configurationlist[$modulename][$fieldvalue] = 'false';
		//check permission
		if ($pdfpermission[$modulename][$fieldvalue] != 1){	
			$update_query = "update berli_pdfconfiguration set ".$fieldvalue."=? where pdfmodul=?";
			$update_params = array($configurationlist[$modulename][$fieldvalue],$modulename);
			$adb->pquery($update_query, $update_params);
		}
	}
}

echo "OK";
exit;

function getPDFSetList($fld_module) {
	global $adb, $log;
	global $image_path;
	$log->debug("Entering into the function getPDFSetList in UpdatePDFSettings for: ".$fld_module);
	$pdfsettings_query="select * from berli_pdfsettings where pdfmodul='".$fld_module."'";
	$pdfsettings = $adb->pquery($pdfsettings_query,array());
	$noofpickrows = $adb->num_rows($pdfsettings);
	for($j = 0; $j < $noofpickrows; $j++) {
		$pdffieldlist[$adb->query_result($pdfsettings,$j,'pdfieldid')]= $adb->query_result($pdfsettings,$j,'pdffieldname');
	}
	$log->debug("Exit getPDFSetList in UpdatePDFSettings with: ".$pdffieldlist);
	return $pdffieldlist;
}

function getPDFConfigList($fld_module) {
	global $adb, $log;
	global $image_path;
	$log->debug("Entering into the function getPDFConfigList in UpdatePDFSettings for: ".$fld_module);
	$pdfconfig_query="select * from berli_pdfconfiguration where pdfmodul=?";
	$pdfconfig_result = $adb->pquery($pdfconfig_query,array($fld_module));
	$pdfconfiglist = $adb->getFieldsArray($pdfconfig_result);
	$log->debug("Exit getPDFConfigList in UpdatePDFSettings with: ".$pdffieldlist);
	return $pdfconfiglist;
}

?>