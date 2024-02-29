<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
class gdpr extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $related_tables = array ('vtiger_gdprcf' => Array('gdprid'));
	var $table_name = 'vtiger_gdpr';
	var $table_index= 'gdprid';
	var $column_fields = Array();

	/** Indicator if this is a custom module or standard module */
	var $IsCustomModule = false;

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_gdprcf', 'gdprid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity','vtiger_gdpr','vtiger_gdprcf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity'=>'crmid',
		'vtiger_gdpr'=>'gdprid',
		'vtiger_gdprcf'=>'gdprid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array(
   		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'GDPR No'=>Array('gdpr'=>'gdpr_no'),
        'GDPR Name'=>Array('gdpr'=>'gdprname'),
        'LBL_GDPR_PERMISSION_CHECK'=>Array('gdpr'=>'permission_check'),
        'LBL_GDPR_PERMISSION_DATE'=>Array('gdpr'=>'permission_date'),
	);
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'GDPR No'=>'gdpr_no',
        'GDPR Name'=>'gdprname',
        'LBL_GDPR_PERMISSION_CHECK'=>'permission_check',
        'LBL_GDPR_PERMISSION_DATE'=>'permission_date',
	);

	// Make the field link to detail view
	var $list_link_field= 'gdprname';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'GDPR No'=>Array('gdpr'=>'gdpr_no'),
        'GDPR Name'=>Array('gdpr'=>'gdprname'),
        'LBL_GDPR_PERMISSION_CHECK'=>Array('gdpr'=>'permission_check'),
        'LBL_GDPR_PERMISSION_DATE'=>Array('gdpr'=>'permission_date'),
	);
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'GDPR No'=>'gdpr_no',
        'GDPR Name'=>'gdprname',
        'LBL_GDPR_PERMISSION_CHECK'=>'permission_check',
        'LBL_GDPR_PERMISSION_DATE'=>'permission_date',
	);

	// For Popup window record selection
	var $popup_fields = Array ('gdprname','firstname','lastname');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'gdprname';

	// Required Information for enabling Import feature
	var $required_fields = Array('gdprname'=>1);

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('gdprname', 'assigned_user_id');

	// Callback function list during Importing
	var $special_functions = Array('set_import_assigned_user');

	var $default_order_by = 'gdprname';
	var $default_sort_order='ASC';

	var $unit_price;

	/**	Constructor which will set the column_fields in this object
	 */
	function __construct() {
		global $log;
		$this->column_fields = getColumnFields(get_class($this));
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
	}

	function save_module($module){
		//module specific save
	}

	/**
	 * Return query to use based on given modulename, fieldname
	 * Useful to handle specific case handling for Popup
	 */
	function getQueryByModuleField($module, $fieldname, $srcrecord) {
		// $srcrecord could be empty
	}

	/**
	 * Get list view query.
	 */
	function getListQuery($module, $where='') {
		$query = "SELECT vtiger_crmentity.*, $this->table_name.*";

		// Select Custom Field Table Columns if present
		if(!empty($this->customFieldTable)) $query .= ", " . $this->customFieldTable[0] . ".* ";

		$query .= " FROM $this->table_name";

		$query .= "	INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $this->table_name.$this->table_index";

		// Consider custom table join as well.
		if(!empty($this->customFieldTable)) {
			$query .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				      " = $this->table_name.$this->table_index";
		}
		$query .= " LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid";
		$query .= " LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";


		$linkedModulesQuery = $this->db->pquery("SELECT distinct fieldname, columnname, relmodule FROM vtiger_field" .
				" INNER JOIN vtiger_fieldmodulerel ON vtiger_fieldmodulerel.fieldid = vtiger_field.fieldid" .
				" WHERE uitype='10' AND vtiger_fieldmodulerel.module=?", array($module));
		$linkedFieldsCount = $this->db->num_rows($linkedModulesQuery);

		for($i=0; $i<$linkedFieldsCount; $i++) {
			$related_module = $this->db->query_result($linkedModulesQuery, $i, 'relmodule');
			$fieldname = $this->db->query_result($linkedModulesQuery, $i, 'fieldname');
			$columnname = $this->db->query_result($linkedModulesQuery, $i, 'columnname');

			$other = CRMEntity::getInstance($related_module);
			vtlib_setup_modulevars($related_module, $other);

			$query .= " LEFT JOIN $other->table_name ON $other->table_name.$other->table_index = $this->table_name.$columnname";
		}

		$query .= "	WHERE vtiger_crmentity.deleted = 0 ".$where;
		$query .= $this->getListViewSecurityParameter($module);
		return $query;
	}

	/**
	 * Apply security restriction (sharing privilege) query part for List view.
	 */
	function getListViewSecurityParameter($module) {
		global $current_user;
		require('user_privileges/user_privileges_'.$current_user->id.'.php');
		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');

		$sec_query = '';
		$tabid = getTabid($module);

		if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1
			&& $defaultOrgSharingPermission[$tabid] == 3) {

				$sec_query .= " AND (vtiger_crmentity.smownerid in($current_user->id) OR vtiger_crmentity.smownerid IN
					(
						SELECT vtiger_user2role.userid FROM vtiger_user2role
						INNER JOIN vtiger_users ON vtiger_users.id=vtiger_user2role.userid
						INNER JOIN vtiger_role ON vtiger_role.roleid=vtiger_user2role.roleid
						WHERE vtiger_role.parentrole LIKE '".$current_user_parent_role_seq."::%'
					)
					OR vtiger_crmentity.smownerid IN
					(
						SELECT shareduserid FROM vtiger_tmp_read_user_sharing_per
						WHERE userid=".$current_user->id." AND tabid=".$tabid."
					)
					OR
						(";

					// Build the query based on the group association of current user.
					if(sizeof($current_user_groups) > 0) {
						$sec_query .= " vtiger_groups.groupid IN (". implode(",", $current_user_groups) .") OR ";
					}
					$sec_query .= " vtiger_groups.groupid IN
						(
							SELECT vtiger_tmp_read_group_sharing_per.sharedgroupid
							FROM vtiger_tmp_read_group_sharing_per
							WHERE userid=".$current_user->id." and tabid=".$tabid."
						)";
				$sec_query .= ")
				)";
		}
		return $sec_query;
	}

	/**
	 * Create query to export the records.
	 */
	function create_export_query($where)
	{
		global $current_user;

		include("include/utils/ExportUtils.php");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery('gdpr', "detail_view");

		$fields_list = getFieldsListFromQuery($sql);

		$query = "SELECT $fields_list, vtiger_users.user_name AS user_name
					FROM vtiger_crmentity INNER JOIN $this->table_name ON vtiger_crmentity.crmid=$this->table_name.$this->table_index";

		if(!empty($this->customFieldTable)) {
			$query .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				      " = $this->table_name.$this->table_index";
		}

		$query .= " LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";
		$query .= " LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid = vtiger_users.id and vtiger_users.status='Active'";

		$where_auto = " vtiger_crmentity.deleted=0";

		if($where != '') $query .= " WHERE ($where) AND $where_auto";
		else $query .= " WHERE $where_auto";

		require('user_privileges/user_privileges_'.$current_user->id.'.php');
		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');

		// Security Check for Field Access
		if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1 && $defaultOrgSharingPermission[getTabid('gdpr')] == 3)
		{
			//Added security check to get the permitted records only
			$query = $query." ".getListViewSecurityParameter($thismodule);
		}
		return $query;
	}

	/**
	 * Transform the value while exporting
	 */
	function transform_export_value($key, $value) {
		if($key == 'owner') return getOwnerName($value);
		return parent::transform_export_value($key, $value);
	}

	/**
	 * Function which will give the basic query to find duplicates
	 */
	function getDuplicatesQuery($module,$table_cols,$field_values,$ui_type_arr,$select_cols='') {
		$select_clause = "SELECT ". $this->table_name .".".$this->table_index ." AS recordid, vtiger_users_last_import.deleted,".$table_cols;

		// Select Custom Field Table Columns if present
		if(isset($this->customFieldTable)) $query .= ", " . $this->customFieldTable[0] . ".* ";

		$from_clause = " FROM $this->table_name";

		$from_clause .= "	INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $this->table_name.$this->table_index";

		// Consider custom table join as well.
		if(isset($this->customFieldTable)) {
			$from_clause .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				      " = $this->table_name.$this->table_index";
		}
		$from_clause .= " LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
						LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";

		$where_clause = "	WHERE vtiger_crmentity.deleted = 0";
		$where_clause .= $this->getListViewSecurityParameter($module);

		if (isset($select_cols) && trim($select_cols) != '') {
			$sub_query = "SELECT $select_cols FROM  $this->table_name AS t " .
				" INNER JOIN vtiger_crmentity AS crm ON crm.crmid = t.".$this->table_index;
			// Consider custom table join as well.
			if(isset($this->customFieldTable)) {
				$sub_query .= " INNER JOIN ".$this->customFieldTable[0]." tcf ON tcf.".$this->customFieldTable[1]." = t.$this->table_index";
			}
			$sub_query .= " WHERE crm.deleted=0 GROUP BY $select_cols HAVING COUNT(*)>1";
		} else {
			$sub_query = "SELECT $table_cols $from_clause $where_clause GROUP BY $table_cols HAVING COUNT(*)>1";
		}


		$query = $select_clause . $from_clause .
					" LEFT JOIN vtiger_users_last_import ON vtiger_users_last_import.bean_id=" . $this->table_name .".".$this->table_index .
					" INNER JOIN (" . $sub_query . ") AS temp ON ".get_on_clause($field_values,$ui_type_arr,$module) .
					$where_clause .
					" ORDER BY $table_cols,". $this->table_name .".".$this->table_index ." ASC";

		return $query;
	}
	/**
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	// function save_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle deleting related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function delete_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle getting related list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }


	/*
	 * Function to get the primary query part of a report
	 * @param - $module primary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	// function generateReportsQuery($module){ }

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	// function generateReportsSecQuery($module,$secmodule){ }

	// Function to unlink all the dependent entities of the given Entity by Id
	function unlinkDependencies($module, $id) {
		global $log;
		parent::unlinkDependencies($module, $id);
	}

 	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	function vtlib_handler($moduleName, $eventType) {
		require_once('include/utils/utils.php');
		require_once('vtlib/Vtiger/Module.php');
		require_once('modules/com_vtiger_workflow/VTEntityMethodManager.inc');
		global $adb;

 		if($eventType == 'module.postinstall') {
			global $adb;
			//create settings entry - prepared for next version
			$this->createSettingsMenueEntry();
			// Mark the module as Standard module
			$adb->pquery('UPDATE vtiger_tab SET customized=0 WHERE name=?', array($moduleName));

			//adds sharing access
			$gdprModule  = Vtiger_Module::getInstance('gdpr');
			Vtiger_Access::setDefaultSharing($gdprModule);

			//Showing gdpr module in the related modules in the More Information Tab
			$gdprInstance = Vtiger_Module::getInstance('gdpr');
			$gdprLabel = 'gdpr';

			$contactInstance = Vtiger_Module::getInstance('Contacts');
			$contactInstance->setRelatedlist($gdprInstance,$gdprLabel,array('ADD'),'get_dependents_list');

			$leadsInstance = Vtiger_Module::getInstance('Leads');
			$leadsInstance->setRelatedlist($gdprInstance,$gdprLabel,array('ADD'),'get_dependents_list');

			//Initialize module sequence for the module
			$result = $adb->pquery("SELECT 1 FROM vtiger_modentity_num WHERE semodule = ? AND active = 1", array($moduleName));
			if (!($adb->num_rows($result))) {
				$adb->pquery("INSERT INTO vtiger_modentity_num values(?,?,?,?,?,?)", array($adb->getUniqueId("vtiger_modentity_num"), $moduleName, 'GDPR', 1, 1, 1));
			}
			
			// add ModTracker
			$modTrackerModuleInstance = Vtiger_Module::getInstance('ModTracker');
			if($modTrackerModuleInstance && file_exists('modules/ModTracker/ModTracker.php')) {
				require_once('modules/ModTracker/ModTracker.php'); 
				if(class_exists('ModTracker')) { 
					ModTracker::enableTrackingForModule($gdprModule->id); 
				}
			}
			// add Comments
			$modcommentsModuleInstance = Vtiger_Module::getInstance('ModComments');
			if($modcommentsModuleInstance && file_exists('modules/ModComments/ModComments.php')) {
				include_once 'modules/ModComments/ModComments.php';
				if(class_exists('ModComments')) {
					ModComments::addWidgetTo(array('gdpr'));
				}
			}
			//add links for GDPR PDF
			$contactInstance->addLink(
				'DETAILVIEWBASIC',
				'LBL_DSGVO_NAME',
				'index.php?module=gdpr&action=printgdpr&scr_module=Contacts&recordid=$RECORD$',
				'themes/images/Contacts.gif'
			);
			$leadsInstance->addLink(
				'DETAILVIEWBASIC',
				'LBL_DSGVO_NAME',
				'index.php?module=gdpr&action=printgdpr&scr_module=Leads&recordid=$RECORD$'
			);
            
            // create settings tables
            $adb->pquery("DROP TABLE IF EXISTS `berli_dsgvo_global`");
            $adb->pquery("CREATE TABLE `berli_dsgvo_global` (
                `op_mode` varchar(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'd',`del_note_time_days` int(10) NOT NULL,`del_mode` varchar(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0'
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
            $adb->pquery("INSERT INTO `berli_dsgvo_global` VALUES ('d',7,'0')");
            $adb->pquery("CREATE TABLE IF NOT EXISTS `berli_dsgvo_module` (
                `setting_date` datetime NOT NULL,`tabid` int(11) NOT NULL,`deletion_mode` int(11) NOT NULL,`fieldids` text COLLATE utf8_unicode_ci NOT NULL,
                PRIMARY KEY (`setting_date`,`tabid`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

            //register cron
            require_once('vtlib/Vtiger/Cron.php');
            Vtiger_Cron::register( 'DSGVO Scanner', 'cron/modules/gdpr/Scanner.service', 86400, 'gdpr', 0, 8, 'LBL_DSGVOSCANNER_DES');
		} 
		else if($eventType == 'module.disabled') {
			$this->deactivateSettingsLinks();
		} 
		else if($eventType == 'module.enabled') {
			$this->activateSettingsLinks();
		} 
		else if($eventType == 'module.preuninstall') {
		// TODO Handle actions when this module is about to be deleted.
		} 
		else if($eventType == 'module.preupdate') {
		// TODO Handle actions before this module is updated.
		} 
		else if($eventType == 'module.postupdate') {
			$result = $adb->pquery("SELECT 1 FROM vtiger_modentity_num WHERE semodule = ? AND active =1 ", array($moduleName));
			if (!($adb->num_rows($result))) {
				//Initialize module sequence for the module
				$adb->pquery("INSERT INTO vtiger_modentity_num values(?,?,?,?,?,?)", array($adb->getUniqueId("vtiger_modentity_num"), $moduleName, 'GDPR', 1, 1, 1));
			}
		}
 	}
	// Create Settings Menue Entry
	function createSettingsMenueEntry(){
		$db = PearDatabase::getInstance();
		$fieldid = $db->getUniqueID('vtiger_settings_field');
		$blockid = getSettingsBlockId('LBL_OTHER_SETTINGS');
		$seq_res = $db->pquery("SELECT max(sequence) AS max_seq FROM vtiger_settings_field WHERE blockid = ?", array($blockid));
		if ($db->num_rows($seq_res) > 0) {
			$cur_seq = $db->query_result($seq_res, 0, 'max_seq');
			if ($cur_seq != null)	$seq = $cur_seq + 1;
		}

		$result=$db->pquery('SELECT 1 FROM vtiger_settings_field WHERE name=?',array('gdpr'));
		if(!$db->num_rows($result)){
			$db->pquery('INSERT INTO vtiger_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence)
				VALUES (?,?,?,?,?,?,?)', array($fieldid, $blockid, 'gdpr' , '', 'LBL_GRDPR_SETUP_DESCRIPTION', 'index.php?module=gdpr&parent=Settings&view=Index', $seq));
		}			
	}
    /**
     * To deactivate Settings link
    */
    function deactivateSettingsLinks(){
		global $log;
		$db = PearDatabase::getInstance();
        $db->pquery('UPDATE vtiger_settings_field set active = 1 WHERE name=?', array('gdpr'));
        $log->debug('Settings Field Removed');
    }
    /**
     * To activate Settings link
    */
    function activateSettingsLinks(){
		global $log;
		$db = PearDatabase::getInstance();
        $db->pquery('UPDATE vtiger_settings_field set active = 0 WHERE name=?', array('gdpr'));
        $log->debug('Settings Field Removed');
    }
}
?>