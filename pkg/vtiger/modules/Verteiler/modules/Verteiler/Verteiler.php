<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

include_once 'modules/Vtiger/CRMEntity.php';

class Verteiler extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_verteiler';
	var $table_index= 'verteilerid';
    var $related_tables = array ('vtiger_verteilercf' => Array('verteilerid'), 'vtiger_verteilercontrel' => Array('verteilerid'));

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_verteilercf', 'verteilerid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_verteiler', 'vtiger_verteilercf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_verteiler' => 'verteilerid',
		'vtiger_verteilercf'=>'verteilerid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Verteilername' => Array('verteiler', 'verteilername'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $list_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Verteilername' => 'verteilername',
		'Assigned To' => 'assigned_user_id',
	);

	// Make the field link to detail view
	var $list_link_field = 'verteilername';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Verteilername' => Array('verteiler', 'verteilername'),
		'Assigned To' => Array('vtiger_crmentity','assigned_user_id'),
	);
	var $search_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Verteilername' => 'verteilername',
		'Assigned To' => 'assigned_user_id',
	);

	// For Popup window record selection
	var $popup_fields = Array ('verteilername');

	// For Alphabetical search
	var $def_basicsearch_col = 'verteilername';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'verteilername';

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('verteilername','assigned_user_id');

	var $default_order_by = 'verteilername';
	var $default_sort_order='ASC';

    function save_module($module) {
	}
    
    /**
    * Function to get Campaign related Contacts
    * @param  integer   $id      - campaignid
    * returns related Contacts record in array format
    */
	function get_contacts($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log, $singlepane_view,$currentModule;
		$log->debug("Entering get_contacts(".$id.") method ...");
		$this_module = $currentModule;
        
        
        $userNameSql = getSqlForNameInDisplayFormat(array('first_name'=>
							'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'), 'Users');
		$query = "SELECT vtiger_contactdetails.accountid, vtiger_account.accountname,
				CASE when (vtiger_users.user_name not like '') then $userNameSql else vtiger_groups.groupname end as user_name ,
				vtiger_contactdetails.contactid, vtiger_contactdetails.lastname, vtiger_contactdetails.firstname, vtiger_contactdetails.title,
				vtiger_contactdetails.department, vtiger_contactdetails.email, vtiger_contactdetails.phone, vtiger_crmentity.crmid,
				vtiger_crmentity.smownerid, vtiger_crmentity.modifiedtime, vtiger_verteilercontrel.parent as parent,
                users2.user_name as added_by_user_name
				FROM vtiger_contactdetails
				INNER JOIN vtiger_verteilercontrel ON vtiger_verteilercontrel.contactid = vtiger_contactdetails.contactid
				INNER JOIN vtiger_contactaddress ON vtiger_contactdetails.contactid = vtiger_contactaddress.contactaddressid
				INNER JOIN vtiger_contactsubdetails ON vtiger_contactdetails.contactid = vtiger_contactsubdetails.contactsubscriptionid
				INNER JOIN vtiger_customerdetails ON vtiger_contactdetails.contactid = vtiger_customerdetails.customerid
				INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_contactdetails.contactid
				LEFT JOIN vtiger_contactscf ON vtiger_contactdetails.contactid = vtiger_contactscf.contactid
				LEFT JOIN vtiger_groups ON vtiger_groups.groupid=vtiger_crmentity.smownerid
				LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid=vtiger_users.id
				LEFT JOIN vtiger_account ON vtiger_account.accountid = vtiger_contactdetails.accountid
                LEFT JOIN vtiger_users AS users2 ON users2.id = vtiger_verteilercontrel.addedbyuserid
				WHERE vtiger_verteilercontrel.verteilerid = ".$id." AND vtiger_crmentity.deleted=0";

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);
		$log->debug("Exiting get_contacts method ...");
		return $return_value;
    }
    
    // function to display the usage of trees
	public static function get_related_usage($id) {
		global $adb;

		$query = 'SELECT relid,usagedate,crmuser,ent2.setype as relsetype FROM vtiger_verteiler_usage 
            INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_verteiler_usage.verteilerid
            LEFT JOIN vtiger_crmentity AS ent2 ON ent2.crmid = vtiger_verteiler_usage.relid
            WHERE vtiger_crmentity.deleted = 0 AND vtiger_verteiler_usage.verteilerid = '.$id;

		$result=$adb->pquery($query, array());
		$noofrows = $adb->num_rows($result);

		$header[] = "Datum";
		$header[] = "Verwendet für";
		$header[] = "durch CRM Nutzer";

		while($result && $row = $adb->fetch_array($result))
		{
			$entries = Array();
			$entries[] = DateTimeField::convertToUserFormat($row['usagedate']);
			$entries[] = array($row['relid'],$row['relsetype']);
			$entries[] = getUserFullName($row['crmuser']);
			$entries_list[] = $entries;
		}

		$return_data = Array('headers'=>$header,'entries'=>$entries_list,'query'=>$query);

		return $return_data;
	}
    
    /*
    * Function to get the relation tables for related modules
    * @param - $secmodule secondary module name
    * returns the array with table names and fieldnames storing relations between module and this module
    */
    function setRelationTables($secmodule){
		$rel_tables = array (
			"Contacts" => array("vtiger_verteilercontrel"=>array("verteilerid","contactid"),"vtiger_verteiler"=>"verteilerid"),
		);
		return $rel_tables[$secmodule];
	}

	function save_related_module($module, $crmid, $with_module, $with_crmids) {
		global $current_user, $adb;

        $listname="";
        
        // get name of parent list/verteiler if applicable
        if ($_REQUEST["relatedModule"]=="Contacts" && !empty($_REQUEST["viewId"])) {
            $vid = (int) $_REQUEST["viewId"];
            $q = "SELECT viewname FROM vtiger_customview WHERE cvid =?";
            $res = $adb->pquery($q,array($vid));
            $listname = decode_html($adb->query_result($res,"viewname"));
        }
        
        if ($_REQUEST["mode"]=="addRelationsFromOtherVerteiler") {
            $vid = (int) $_REQUEST["verteilerId"];
            $q = "SELECT verteilername FROM vtiger_verteiler WHERE verteilerid =?";
            $res = $adb->pquery($q,array($vid));
            $listname = decode_html($adb->query_result($res,"verteilername"));
        }
        
		if(!is_array($with_crmids)) $with_crmids = Array($with_crmids);
		foreach($with_crmids as $with_crmid) {
			if($with_module == 'Contacts') {
				$sql = 'INSERT INTO vtiger_verteilercontrel SET verteilerid=?,contactid=?,addedbyuserid=?,parent=? ON DUPLICATE KEY UPDATE contactid=contactid';
				$adb->pquery($sql, array($crmid, $with_crmid, $current_user->id,$listname));
			} else {
				parent::save_related_module($module, $crmid, $with_module, $with_crmid);
			}
		}
	}
    
	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	function vtlib_handler($moduleName, $eventType) {
		global $adb;
 		if($eventType == 'module.postinstall') {
			// TODO Handle actions after this module is installed.
			include_once('vtlib/Vtiger/Module.php');
			//adds sharing access
			$ModuleInstance  = Vtiger_Module::getInstance($moduleName);
			Vtiger_Access::setDefaultSharing($ModuleInstance);

			//display module as related list
			$ContactsInstance = Vtiger_Module::getInstance('Contacts');
			$ContactsInstance->setRelatedlist($ModuleInstance,$moduleName,array('SELECT'),'get_related_verteiler_list');

			//set the module numbering
			$result = $adb->pquery("SELECT 1 FROM vtiger_modentity_num WHERE semodule = ? AND active = 1", array($moduleName));
			if (!($adb->num_rows($result))) {
				//Initialize module sequence for the module
				$adb->pquery("INSERT INTO vtiger_modentity_num values(?,?,?,?,?,?)", array($adb->getUniqueId("vtiger_modentity_num"), $moduleName, 'VT-', 1, 1, 1));
			}
			
			// add ModTracker
			$modTrackerModuleInstance = Vtiger_Module::getInstance('ModTracker');
			if($modTrackerModuleInstance && file_exists('modules/ModTracker/ModTracker.php')) {
				require_once('modules/ModTracker/ModTracker.php'); 
				if(class_exists('ModTracker')) { 
					ModTracker::enableTrackingForModule($ModuleInstance->id); 
				}
			}
			// add Comments
			$modcommentsModuleInstance = Vtiger_Module::getInstance('ModComments');
			if($modcommentsModuleInstance && file_exists('modules/ModComments/ModComments.php')) {
				include_once 'modules/ModComments/ModComments.php';
				if(class_exists('ModComments')) {
					ModComments::addWidgetTo(array($moduleName));
				}
			}
		} 
		else if($eventType == 'module.disabled') {
			// TODO Handle actions before this module is being uninstalled.
		} else if($eventType == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
 	}
}