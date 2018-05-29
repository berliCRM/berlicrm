<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Pdfsettings_Module_Model extends Vtiger_Module_Model {
    public function getSideBarLinks($linkParams) {
		$quickLink = array(
            'linktype' => 'SIDEBARLINK',
            'linklabel' => 'LBL_MODULE_NAME',
            'linkurl' => $this->getListViewUrl(),
            'linkicon' => '',
		);
        $links['SIDEBARLINK'][] = Vtiger_Link_Model::getInstanceFromValues($quickLink);
		return $links;
	}
 }
