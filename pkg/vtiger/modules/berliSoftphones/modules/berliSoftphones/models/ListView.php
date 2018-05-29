<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * berliSoftphones ListView Model Class
 */

class berliSoftphones_ListView_Model extends Vtiger_ListView_Model {
    
    /**
    * Overrided to remove not needed menu items 
    */
    public function getBasicLinks(){
		$basicLinks = array();
		return $basicLinks;
	}
    
 	public function getListViewLinks($linkParams) {
    }

    public function getListViewMassActions($linkParams) {
		return false;
	}
    
	public function getListViewHeaders() {
		return false;
	}
	
	public function getListViewCount() {
		return false;
	}
	public function getSettingLinks() {
		return false;
	}
    public function isPagingSupported() {
        return false;
    }

}
