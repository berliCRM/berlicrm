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


class Settings_berliCleverReach_Index_View extends Settings_Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
		$allModelsList = Vtiger_Menu_Model::getAll(true);
		
		$SubscriberType = Settings_berliCleverReach_Module_Model::getSubscriberType();
		$ApiCredentials = Settings_berliCleverReach_Module_Model::getApiCredentials();
		
		$moduleName = $request->getModule();
        $qualifiedName = $request->getModule(FALSE);

		if (!empty($ApiCredentials["accesstoken"])) {
			require_once('modules/berliCleverReach/providers/cleverreach.php');

			$CR = new cleverreachAPI;
			$rest = $CR->getrest();
			
			try {
			$whoami = $rest->get("/clients/whoami");
			} catch (\Exception $e){}
            
            // creating attributes can take MINUTES after the REST operation, so do it here - as early as possible				
            $clvrfields = (array) $rest->get("/attributes");
            foreach ($clvrfields as $clvrfield) {
                $tmpfields[$clvrfield->name]=$clvrfield->type;
            }
            $fieldsneeded = array_diff_assoc(cleverreachAPI::$fields,$tmpfields);

            foreach ($fieldsneeded as $fieldname => $fieldtype)	{
                    $newfield = array("name"=>$fieldname, "type"=>$fieldtype);
                    $rest->post("/attributes", $newfield);
            }
		}
		
		$viewer = $this->getViewer($request);
		$viewer->assign('SUBSCRIBERTYPE', $SubscriberType);
		$viewer->assign('APICREDENTIALS', $ApiCredentials);
		$viewer->assign('MODULE_NAME', $moduleName);
		if (!empty($whoami)) {
			$viewer->assign('WHOAMI', $whoami);
		}
		
		$viewer->view('berliCleverReachSettings.tpl', $qualifiedName);
	}
}
