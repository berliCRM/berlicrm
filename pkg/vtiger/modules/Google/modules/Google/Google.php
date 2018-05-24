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
        } else if ($eventType == 'module.disabled') {
            $this->removeMapWidget($forModules);
            $this->removeWidgetforSync($syncModules);
        } else if ($eventType == 'module.enabled') {
            $this->addMapWidget($forModules);
            $this->addWidgetforSync($syncModules);
        } else if ($eventType == 'module.preuninstall') {
            $this->removeMapWidget($forModules);
            $this->removeWidgetforSync($syncModules);
        } else if ($eventType == 'module.preupdate') {
            // TODO Handle actions before this module is updated.
        } else if ($eventType == 'module.postupdate') {
            
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

}

?>
