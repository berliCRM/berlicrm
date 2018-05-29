<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class berliSoftphones_Module_Model extends Vtiger_Module_Model {

     public static function getInstance($value){
        return new self();
    }

	/**
	 * Function to check whether the module is an entity type module or not
	 * @return <Boolean> true/false
	 */
	public function isQuickCreateSupported() {
		//PBXManager module is not enabled for quick create
		return false;
	}

	public function isWorkflowSupported() {
		return false;
	}
    
    /**
	 * Overided to make editview=false for this module
	 */
	public function isPermitted($actionName) {
		return false;
	}
    
 	public function getSideBarLinks($linkParams) {
		$parentQuickLinks = parent::getSideBarLinks($linkParams);
		unset ($parentQuickLinks['SIDEBARLINK']);
		return $parentQuickLinks;
	}
     
    public function isPagingSupported() {
        return false;
    }

	/**
	 * Function to get Settings links
	 * @return <Array>
	 */
	public function getSettingLinks(){
               if(!$this->isEntityModule()) {
            return array();
        }
		vimport('~~modules/com_vtiger_workflow/VTWorkflowUtils.php');

		$layoutEditorImagePath = Vtiger_Theme::getImagePath('LayoutEditor.gif');
		$editWorkflowsImagePath = Vtiger_Theme::getImagePath('EditWorkflows.png');
		$settingsLinks = array();

		return $settingsLinks;
	}
    
    /**
     * Funxtion to identify if the module supports quick search or not
     */
    public function isQuickSearchEnabled() {
        return false;
    }
    
    public function isListViewNameFieldNavigationEnabled() {
        return false;
    }
}
?>
