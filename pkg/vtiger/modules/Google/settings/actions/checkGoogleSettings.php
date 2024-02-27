<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 ********************************************************************************/
 
class Settings_Google_checkGoogleSettings_Action extends Settings_Vtiger_Index_Action {

    // Save Google API keys
    public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$check = $request->get('check');
		$response = new Vtiger_Response();
		$status = array ('success' => false);
		if ($check=='geo') {
			$geodataapikey = $request->get('geodataapikey');
			$query = "UPDATE berli_google_settings set google_api_id =? where type = ?";
			$queryresult = $db->pquery($query, array($geodataapikey, 'geodataapikey'));
			$status = Settings_Google_Module_Model::checkGoogleGeoApikey();
		}
		$response->setResult($status);
		$response->emit();
   }
}

?>