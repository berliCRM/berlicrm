<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class berliSoftphones_Record_Model extends Vtiger_Record_Model{
    
	
    public static function getSoftphonePrefix(){
        $db = PearDatabase::getInstance();
        $query = "SELECT phoneprefix FROM berli_softphones where phactive ='checked'";
        $result = $db->pquery($query, array());
        $count = $db->num_rows($result);
		$softphoneprefix = '';
        if ($count == 1){
            $softphoneprefix = $db->query_result($result, 0, 'phoneprefix');
        }
        return $softphoneprefix;
    }
	
	public static function getSoftphoneCaller($callerid,$user) {
		$records = array();
		if (trim($callerid) !='' and strlen($callerid)>4) {
			$db = PearDatabase::getInstance();
			$query = "SELECT columnname, tablename FROM `vtiger_field` WHERE `uitype`=11";
			$result = $db->pquery($query, array());
			$count = $db->num_rows($result);
			for($i=0; $i<$count; $i++){
				$columnname = $db->query_result($result, $i, 'columnname');
				$tablename = $db->query_result($result, $i, 'tablename');
				$entityquery = "SELECT modulename, entityidfield, fieldname FROM `vtiger_entityname` WHERE `tablename`=?";
				$entityresult = $db->pquery($entityquery, array($tablename));
				$modulename = $db->query_result($entityresult, 0, 'modulename');
				$entityidfield = $db->query_result($entityresult, 0, 'entityidfield');
				$fieldname = $db->query_result($entityresult, 0, 'fieldname');
				$entryquery = "SELECT $entityidfield FROM $tablename 
					inner join vtiger_crmentity on vtiger_crmentity.crmid = $tablename.$entityidfield
					WHERE vtiger_crmentity.deleted=0 and (REPLACE(REPLACE(" . $columnname . " , ' ', ''), '-', ''))  LIKE '%".$callerid."'";
				$entryresult = $db->pquery($entryquery, array());
				$entrycount = $db->num_rows($entryresult);
				$entityModuleName = $modulename;
				if ($tablename =='vtiger_leadaddress') {
					$entityModuleName = 'Leads';
				}
				if ($tablename =='vtiger_contactsubdetails') {
					$entityModuleName = 'Contacts';
				}
				for($j=0; $j<$entrycount; $j++){
					$recordId = $db->query_result($entryresult, 0, $entityidfield);
					$records[$recordId] = Vtiger_Record_Model::getInstanceById($recordId, $entityModuleName);
				}
			}
		}
		return $records;
	}
}
?>
