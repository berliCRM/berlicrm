<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 *************************************************************************************/

require_once('modules/Mailchimp/providers/MailChimp.php');

class Mailchimp_LoadSyncValues_Action extends Vtiger_Action_Controller{
    public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());

		if(!$permission) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}
    
    public function process(Vtiger_Request $request) {
       $actions = $request->get('get');
        $listid = $request->get('listeid');
        $crmid = $request->get('id');
		$response = new Vtiger_Response();
		if($actions=="getGroupInfos") {
			$Settings_Mailchimp_Module_Model = new Settings_Mailchimp_Module_Model();
			$MailChimpAPIKey = $Settings_Mailchimp_Module_Model -> getApikey();
			$mailchimpname = Mailchimp_Module_Model::getMailchimpName($crmid);
			$api = new MailChimp($MailChimpAPIKey);
			$GroupsArray = $api->get('lists');
			if (is_array($GroupsArray['lists'])) {
				foreach ($GroupsArray['lists'] as $arrkey => $arrvalue) {
					if ($arrvalue['name'] == $mailchimpname) {
						$response->setResult($arrvalue['name']);
						$response->emit();
						return;
					}
				}
			}
			$response->setResult('nogroupfound');
			$response->emit();
			return;
		}
		$response->setResult('nogroupfound');
		$response->emit();
		return;
   }
}	
?>