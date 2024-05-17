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

class ToolWidgets {

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($moduleName, $event_type) {
		require_once('include/utils/utils.php');			
		if($event_type == 'module.postinstall') {
			$this->initToolWidgets();
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
			return;
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
			return;
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
			$this->uninstallToolWidgets();
			return;		
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
			return;			
		} else if($event_type == 'module.postupdate') {
			return;	
		}
	}
	
	function initToolWidgets() {
		include_once('vtlib/Vtiger/Module.php');
		$module = Vtiger_Module::getInstance('Contacts');
		$module->addLink('DETAILVIEWSIDEBARWIDGET', 'LBL_COPY_CONTACTDETAILS', 'module=ToolWidgets&view=showCopyPasteData&mode=showEntries&source_module=Contacts&viewtype=detail');
		$module = Vtiger_Module::getInstance('Accounts');
		$module->addLink('DETAILVIEWSIDEBARWIDGET', 'LBL_COPY_CONTACTDETAILS', 'module=ToolWidgets&view=showCopyPasteData&mode=showEntries&source_module=Accounts&viewtype=detail');	
	}


	function uninstallToolWidgets() {
		include_once('vtlib/Vtiger/Module.php');
		$module = Vtiger_Module::getInstance('ToolWidgets');
		if($module) {
			// Delete from system
			$module->delete();
			echo "Module deleted!";
		} 
		else {
			echo "Module was not found and could not be deleted!";
		}	
	}
}
?>