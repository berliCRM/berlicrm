<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by crm-now are Copyright (C) crm-now GmbH for berliCRM.
 * All Rights Reserved.
 *************************************************************************************/
require_once('include/events/include.inc');

class ListViewColors {

	var $LBL_LISTVIEWCOLORS='ListViewColors';
	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		require_once('include/utils/utils.php');			
		if($event_type == 'module.postinstall') {
			$db = PearDatabase::getInstance();
			include_once('vtlib/Vtiger/Module.php');
			//adds settings menu
			$this->updateSettings();
			// Mark the module as Standard module and not as entity module
			$db->pquery('UPDATE vtiger_tab SET customized=0,isentitytype=0  WHERE name=?', array($this->LBL_LISTVIEWCOLORS));
		} 
		else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
			return;
		} 
		else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
			return;
		} 
		else if($event_type == 'module.preuninstall') {
            $this->removeSettingsLinks();
		} 
		else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
			return;			
		} 
		else if($event_type == 'module.postupdate') {
			return;			
		}
	}
	
	function updateSettings(){
		$db = PearDatabase::getInstance();
		$fieldid = $db->getUniqueID('vtiger_settings_field');
		$blockid = getSettingsBlockId('LBL_OTHER_SETTINGS');
		$seq_res = $db->pquery("SELECT max(sequence) AS max_seq FROM vtiger_settings_field WHERE blockid = ?", array($blockid));
		if ($db->num_rows($seq_res) > 0) {
			$cur_seq = $db->query_result($seq_res, 0, 'max_seq');
			if ($cur_seq != null)	$seq = $cur_seq + 1;
		}

		$result=$db->pquery('SELECT 1 FROM vtiger_settings_field WHERE name=?',array($this->LBL_LISTVIEWCOLORS));
		if(!$db->num_rows($result)){
			$db->pquery('INSERT INTO vtiger_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence)
				VALUES (?,?,?,?,?,?,?)', array($fieldid, $blockid, $this->LBL_LISTVIEWCOLORS , '', 'LBL_LISTVIEWCOLORS_SETUP_DESCRIPTION', 'index.php?module=ListViewColors&parent=Settings&view=Index', $seq));
		}			
	}
    /**
     * To delete Settings link
    */
    function removeSettingsLinks(){
		global $log;
		$db = PearDatabase::getInstance();
        $db->pquery('DELETE FROM vtiger_settings_field WHERE name=?', array($this->LBL_LISTVIEWCOLORS));
        $log->fatal('Settings Field Removed');
        
    }

}
?>