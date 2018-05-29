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

class Settings_Mailchimp_Index_View extends Settings_Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
		$allModelsList = Vtiger_Menu_Model::getAll(true);
		$SubscriberType = Settings_Mailchimp_Module_Model::getSubscriberType();
		$ApiKey = Settings_Mailchimp_Module_Model::getApikey();
		$moduleName = $request->getModule();
        $qualifiedName = $request->getModule(FALSE);
		
		$viewer = $this->getViewer($request);
		$viewer->assign('SUBSCRIBERTYPE', $SubscriberType);
		$viewer->assign('APIKEY', $ApiKey);
		$viewer->assign('MODULE_NAME', $moduleName);
		
		$viewer->view('MailChimpSettings.tpl', $qualifiedName);
	}
}
