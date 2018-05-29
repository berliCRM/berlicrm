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

require_once ("modules/berliCleverReach/providers/cleverreach.php");
 
class Settings_berliCleverReach_saveberliCleverReachSettings_Action extends Settings_Vtiger_Index_Action {

    // Save CleverReach API credentials and settings
    public function process(Vtiger_Request $request) {
		
		$db = PearDatabase::getInstance();
		$apicustomerid = $request->get('customerid');
		$apicustomername = $request->get('customername');
		$apicustomerpassword = $request->get('customerpassword');
		$createtype = $request->get('newsubscriber');	
 
		$response = new Vtiger_Response();
 
		if ($request->get("removeAPI") == "true") {
			$query = "DELETE FROM vtiger_berlicleverreach_settings WHERE `id`=1";
			$db->pquery($query, array());
			unset($_SESSION['crtoken']); 
			$response->setResult(array(getTranslatedString('LBL_SAVE_SUCCESS','berliCleverReach')));
			$response->emit();
			return;
		}


		// password entered: fetch and store token
		if ($apicustomerpassword!="") {	
		
			try {
			$token = cleverreachAPI::getToken($apicustomerid,$apicustomername,$apicustomerpassword);
			
			} 
				catch (Exception $e) {
					$response->setError(getTranslatedString('LBL_CR_LOGIN_FAILED','berliCleverReach'),getTranslatedString('LBL_CHECK_CREDS','berliCleverReach'));
					$response->emit();
					return;
				}	

				$CR = new cleverreachAPI;
				$rest = $CR->getrest();
				
				try {
				$whoami = $rest->get("/clients/whoami");
                $CR->createCleverReachAttributes();
				} catch (\Exception $e){}
				
				$whoamimsg = sprintf(getTranslatedString('LBL_API_CONNECTED_TO','berliCleverReach'),$whoami->id,$whoami->firstname,$whoami->name);
				
			try {
				$query = "INSERT INTO vtiger_berlicleverreach_settings (`id`, `customerid`, `customername`, `accesstoken`, `newsubscribertype`) VALUES ('1', ?, ?, ?, ?) 
						ON DUPLICATE KEY UPDATE `customerid`=?, `customername`=?, `accesstoken`=?, `newsubscribertype`=?";

				$result = $db->pquery($query, array($apicustomerid, $apicustomername, $token, $createtype, $apicustomerid, $apicustomername, $token, $createtype));
				
				if ($result) {
							$response->setResult(array(getTranslatedString('LBL_SAVE_SUCCESS','berliCleverReach'),$whoamimsg));
						}
						else {
							$response->setResult(array('SAVING ERROR'));
						}
			} 
			catch (Exception $e) {
					$response->setError($e->getMessage());
			}			
		}	
	
		else {
			
			$query = "UPDATE `vtiger_berlicleverreach_settings` SET `newsubscribertype`=? WHERE `id`=1";
			$result = $db->pquery($query, array($createtype));
			if ($result) {
				$response->setResult(array(getTranslatedString('LBL_SAVE_SUCCESS','berliCleverReach')));
			}
			else {
				$response->setError(array(getTranslatedString('LBL_SAVE_ERROR','berliCleverReach')));
			}
		}
			
        $response->emit();
    }
}

?>