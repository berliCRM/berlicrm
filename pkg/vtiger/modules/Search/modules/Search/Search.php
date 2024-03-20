<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by crm-now are Copyright (C) crm-now GmbH.
 * All Rights Reserved.
 *************************************************************************************/
require_once('include/events/include.inc');

class Search {

	var $LBL_SEARCH='Search';
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
			$db->pquery('UPDATE vtiger_tab SET customized=0,isentitytype=0  WHERE name=?', array($this->LBL_SEARCH));
			// set default values
			$this->setDefaultValues();
		} 
		else if($event_type == 'module.disabled') {
			//remove handler
			$this->disableSearchHandler();
			// TODO Handle actions when this module is disabled.
			return;
		} 
		else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
			$this->activateSearchHandler();
			return;
		} 
		else if($event_type == 'module.preuninstall') {
			$this->removeSearchHandler();
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

		$result=$db->pquery('SELECT 1 FROM vtiger_settings_field WHERE name=?',array($this->LBL_SEARCH));
		if(!$db->num_rows($result)){
			$db->pquery('INSERT INTO vtiger_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence)
				VALUES (?,?,?,?,?,?,?)', array($fieldid, $blockid, $this->LBL_SEARCH , '', 'LBL_SEARCH_SETUP_DESCRIPTION', 'index.php?module=Search&parent=Settings&view=Index', $seq));
		}			
	}
	function setDefaultValues(){
		$db = PearDatabase::getInstance();
		//fill the new tables with default values
		$db->pquery("INSERT IGNORE INTO berli_globalsearch_settings (gstabid) (SELECT vtiger_entityname.tabid FROM vtiger_entityname LEFT JOIN berli_globalsearch_settings ON vtiger_entityname.tabid = berli_globalsearch_settings.gstabid where vtiger_entityname.modulename !='Users' AND vtiger_entityname.modulename !='PBXManager' )",array());
		//deactivate search in email
		$emailtab_res = $db->pquery("SELECT vtiger_entityname.tabid FROM vtiger_entityname where vtiger_entityname.modulename =?",array('Emails'));
		$db->pquery("UPDATE `berli_globalsearch_settings` SET `turn_off` = '0' WHERE `berli_globalsearch_settings`.`gstabid` =?",array($db->query_result($emailtab_res,0,'tabid')));
		//copy label content from crmentity
		$db->pquery("INSERT IGNORE INTO berli_globalsearch_data (gscrmid,searchlabel) (SELECT crmid,label FROM vtiger_crmentity LEFT JOIN berli_globalsearch_data ON vtiger_crmentity.crmid = berli_globalsearch_data.gscrmid)",array());
	}
    /**
     * To delete Settings link
    */
    function removeSettingsLinks(){
		global $log;
		$db = PearDatabase::getInstance();
        $db->pquery('DELETE FROM vtiger_settings_field WHERE name=?', array($this->LBL_SEARCH));
        $log->fatal('Settings Field Removed');
        
    }
    function disableSearchHandler(){
		global $log;
		$db = PearDatabase::getInstance();
        $db->pquery('Update vtiger_eventhandlers set is_active = 0 WHERE handler_class=?', array('Settings_Search_RecordSearchLabelUpdater_Handler'));
    }
    function activateSearchHandler(){
		global $log;
		$db = PearDatabase::getInstance();
        $db->pquery('Update vtiger_eventhandlers set is_active = 1 WHERE handler_class=?', array('Settings_Search_RecordSearchLabelUpdater_Handler'));
    }
    function removeSearchHandler(){
		global $log;
		$db = PearDatabase::getInstance();
        $db->pquery('Delete from vtiger_eventhandlers WHERE handler_class=?', array('Settings_Search_RecordSearchLabelUpdater_Handler'));
    }

}
?>