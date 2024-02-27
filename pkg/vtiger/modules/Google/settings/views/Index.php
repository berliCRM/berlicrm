<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 ************************************************************************************/

class Settings_Google_Index_View extends Settings_Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
		$allModelsList = Vtiger_Menu_Model::getAll(true);
		$GoogleApiKey = Settings_Google_Module_Model::getGoogleApikey();
		$GoogleGeoApiKey = Settings_Google_Module_Model::getGoogleGeoApikey();
		$moduleName = $request->getModule();
        $qualifiedName = $request->getModule(FALSE);

		$viewer = $this->getViewer($request);
		$viewer->assign('SUBSCRIBERTYPE', $SubscriberType);
		$viewer->assign('GOOGLEAPIKEY', $GoogleApiKey);
		$viewer->assign('GOOGLEGEOAPIKEY', $GoogleGeoApiKey);
		$viewer->assign('MODULE_NAME', $moduleName);
		
		$viewer->view('GoogleSettings.tpl', $qualifiedName);
	}
}
