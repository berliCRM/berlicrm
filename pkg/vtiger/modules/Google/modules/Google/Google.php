<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */
require_once 'vtlib/Vtiger/Module.php';
require_once('include/events/include.inc');

class Google {

    const module = 'Google';

    /**
     * Invoked when special actions are to be performed on the module.
     * @param String Module name
     * @param String Event Type
     */
    function vtlib_handler($moduleName, $eventType) {
        $adb = PearDatabase::getInstance();
        $forModules = array('Contacts', 'Leads','Accounts');
        $syncModules = array('Contacts' => 'Google Contacts', 'Calendar' => 'Google Calendar');

        if ($eventType == 'module.postinstall') {
            $adb->pquery('UPDATE vtiger_tab SET customized=0 WHERE name=?', array($moduleName));
            $this->addMapWidget($forModules);
            $this->addWidgetforSync($syncModules);
			//initiate settings table
			$this->createSettingsTableContents();
			//add settings menu
			$this->createSettingsMenueEntry();
            //register handlers
            require_once 'modules/WSAPP/Utils.php';
            wsapp_RegisterHandler('Google_vtigerHandler', 'Google_Vtiger_Handler', 'modules/Google/handlers/Vtiger.php'); 
            wsapp_RegisterHandler('Google_vtigerSyncHandler', 'Google_VtigerSync_Handler', 'modules/Google/handlers/VtigerSync.php'); 
        } else if ($eventType == 'module.disabled') {
            $this->removeMapWidget($forModules);
            $this->removeWidgetforSync($syncModules);
        } else if ($eventType == 'module.enabled') {
            $this->addMapWidget($forModules);
            $this->addWidgetforSync($syncModules);
        } else if ($eventType == 'module.preuninstall') {
            $this->removeMapWidget($forModules);
            $this->removeWidgetforSync($syncModules);
			//remove settings menue
			$this->removeSettingsLinks();
			
        } else if ($eventType == 'module.preupdate') {
            // TODO Handle actions before this module is updated.
        } else if ($eventType == 'module.postupdate') {
			//initiate settings table
			$this->createSettingsTableContents();
			//add settings menu
			$this->createSettingsMenueEntry();
          
        }
    }

    /**
     * Add widget to other module.
     * @param Array $moduleNames
     * @param String $widgetType
     * @param String $widgetName
     * @return
     */
    function addMapWidget($moduleNames, $widgetType = 'DETAILVIEWSIDEBARWIDGET', $widgetName = 'Google Map') {
        if (empty($moduleNames))
            return;

        if (is_string($moduleNames))
            $moduleNames = array($moduleNames);

        foreach ($moduleNames as $moduleName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->addLink($widgetType, $widgetName, 'module=Google&view=Map&mode=showMap&viewtype=detail', '', '', '');
            }
        }
    }

    /**
     * Remove widget from other modules.
     * @param Array $moduleNames
     * @param String $widgetType
     * @param String $widgetName
     * @return
     */
    function removeMapWidget($moduleNames, $widgetType = 'DETAILVIEWSIDEBARWIDGET', $widgetName = 'Google Map') {
        if (empty($moduleNames))
            return;

        if (is_string($moduleNames))
            $moduleNames = array($moduleNames);

        foreach ($moduleNames as $moduleName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->deleteLink($widgetType, $widgetName, 'module=Google&view=Map&mode=showMap&viewtype=detail');
            }
        }
    }

    /**
     * Add widget to other module
     * @param String $widgetType
     * @param String $widgetName
     * @return
     */
    function addWidgetforSync($moduleNames, $widgetType = 'LISTVIEWSIDEBARWIDGET') {
        if (empty($moduleNames))
            return;

        if (is_string($moduleNames))
            $moduleNames = array($moduleNames);

        foreach ($moduleNames as $moduleName => $widgetName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->addLink($widgetType, $widgetName, "module=Google&view=List&sourcemodule=$moduleName", '', '', '');
            }
        }
    }

    /**
     * Remove widget from other modules.
     * @param String $widgetType
     * @param String $widgetName
     * @return
     */
    function removeWidgetforSync($moduleNames, $widgetType = 'LISTVIEWSIDEBARWIDGET') {
        if (empty($moduleNames))
            return;

        if (is_string($moduleNames))
            $moduleNames = array($moduleNames);

        foreach ($moduleNames as $moduleName => $widgetName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->deleteLink($widgetType, $widgetName);
            }
        }
    }
	
    /**
     * add Google settings menu
     */
	function createSettingsMenueEntry(){
		$db = PearDatabase::getInstance();
		$fieldid = $db->getUniqueID('vtiger_settings_field');
		$blockid = getSettingsBlockId('LBL_OTHER_SETTINGS');
		$seq_res = $db->pquery("SELECT max(sequence) AS max_seq FROM vtiger_settings_field WHERE blockid = ?", array($blockid));
		if ($db->num_rows($seq_res) > 0) {
			$cur_seq = $db->query_result($seq_res, 0, 'max_seq');
			if ($cur_seq != null)	$seq = $cur_seq + 1;
		}

		$result=$db->pquery('SELECT 1 FROM vtiger_settings_field WHERE name=?',array('Google'));
		if(!$db->num_rows($result)){
			$db->pquery('INSERT INTO vtiger_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence)
				VALUES (?,?,?,?,?,?,?)', array($fieldid, $blockid, 'Google' , '', 'LBL_GOOGLE_DESCRIPTION', 'index.php?parent=Settings&module=Google&view=Index', $seq));
		}			
	}
     /**
     * To delete Settings link
    */
    function removeSettingsLinks(){
		global $log;
		$db = PearDatabase::getInstance();
        $db->pquery('DELETE FROM vtiger_settings_field WHERE name=?', array('Google'));
        $log->fatal('Settings Field Removed');
        
    }
   /**
     * add Google settings menu
     */
	function createSettingsTableContents(){
		$db = PearDatabase::getInstance();
		$result=$db->pquery('SELECT 1 FROM berli_google_settings WHERE id=?',array('1'));
		if(!$db->num_rows($result)){
			$db->pquery("INSERT INTO `berli_google_settings` (`id`, `google_username`, `google_password`, `google_api_id`, `type`) VALUES (1, '', '', '', 'mapapikey'),(2, '', '', '', 'geodataapikey');");
		}
	}

}

?>
