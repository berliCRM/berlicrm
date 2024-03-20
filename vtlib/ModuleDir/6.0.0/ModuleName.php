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

class ModuleName extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_<modulename>';
	var $table_index= '<modulename>id';

	
	/**
	 * Mandatory table for supporting related module.
	 */
	var $related_tables = Array('vtiger_<modulename>cf' => array('<modulename>id'));

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_<modulename>cf', '<modulename>id');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_<modulename>', 'vtiger_<modulename>cf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_<modulename>' => '<modulename>id',
		'vtiger_<modulename>cf'=>'<modulename>id');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'<entityfieldlabel>' => Array('<modulename>', '<entitycolumn>'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $list_fields_name = Array (
		/* Format: Field Label => fieldname */
		'<entityfieldlabel>' => '<entityfieldname>',
		'Assigned To' => 'assigned_user_id',
	);

	// Make the field link to detail view
	var $list_link_field = '<entityfieldname>';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'<entityfieldlabel>' => Array('<modulename>', '<entitycolumn>'),
		'Assigned To' => Array('vtiger_crmentity','assigned_user_id'),
	);
	var $search_fields_name = Array (
		/* Format: Field Label => fieldname */
		'<entityfieldlabel>' => '<entityfieldname>',
		'Assigned To' => 'assigned_user_id',
	);

	// For Popup window record selection
	var $popup_fields = Array ('<entityfieldname>');

	// For Alphabetical search
	var $def_basicsearch_col = '<entityfieldname>';

	// Column value to use on detail view record text display
	var $def_detailview_recname = '<entityfieldname>';

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('<entityfieldname>','assigned_user_id');

	var $default_order_by = '<entityfieldname>';
	var $default_sort_order='ASC';

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
			$moduleInstance  = Vtiger_Module::getInstance($moduleName);
			Vtiger_Access::setDefaultSharing($moduleInstance);
			
			$blockInstance = Vtiger_Block::getInstance('LBL_DESCRIPTION_INFORMATION', $moduleInstance);
			if (!$blockInstance) {
				$blockcf = new Vtiger_Block();
				$blockcf->label = 'LBL_DESCRIPTION_INFORMATION';
				$moduleInstance->addBlock($blockcf);
			}
			$description = Vtiger_Field::getInstance('description', $moduleInstance);
			
			if (!$description) {
				$description = new Vtiger_Field();
				$description->name = 'description';
				$description->label = 'Description';
				$description->table = 'vtiger_crmentity';
				$description->typeofdata = 'V~O';
				$description->uitype = '19';
				$description->info_type = 'BAS';
				$description->displaytype = '1';
				$blockInstance = Vtiger_Block::getInstance('LBL_DESCRIPTION_INFORMATION', $moduleInstance);
				$blockInstance->addField($description);
			}
				if(strlen($moduleName) >=3){
					$num=substr($moduleName,0,3);
				}
				else{
					$num=$moduleName;
				}			
			//set the module numbering
			$result = $adb->pquery("SELECT 1 FROM vtiger_modentity_num WHERE semodule = ? AND active = 1", array($moduleName));
			if (!($adb->num_rows($result))) {
				//Initialize module sequence for the module
				$num=(strtoupper($num));
				$adb->pquery("INSERT INTO vtiger_modentity_num values(?,?,?,?,?,?)", array($adb->getUniqueId("vtiger_modentity_num"), $moduleName, "$num-", 1, 1, 1));
			}
			
			// add ModTracker
			$modTrackerModuleInstance = Vtiger_Module::getInstance('ModTracker');
			if($modTrackerModuleInstance && file_exists('modules/ModTracker/ModTracker.php')) {
				require_once('modules/ModTracker/ModTracker.php'); 
				if(class_exists('ModTracker')) { 
					ModTracker::enableTrackingForModule($moduleInstance->id); 
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