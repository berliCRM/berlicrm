<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class MailManager_MainUI_View extends MailManager_Abstract_View {

    /**
     * Process the request for displaying UI
     * @global String $moduleName
     * @param Vtiger_Request $request
     * @return MailManager_Response
     */
	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$response = new Vtiger_Response();
		$viewer = $this->getViewer($request);
		if($this->getOperationArg($request) == "_quicklinks") {
			$content = $viewer->view('MainuiQuickLinks.tpl', $moduleName, true);
			$response->setResult( array('ui' => $content));
			return $response;
		} else {
			if ($this->hasMailboxModel()) {
				$connector = $this->getConnector();

				if ($connector->hasError()) {
					$viewer->assign('ERROR', $connector->lastError());
				} else {
					$folders = $connector->folders();
					$connector->updateFolders();
					$viewer->assign('FOLDERS', $folders);
				}
				$this->closeConnector();
			}
			$viewer->assign('MODULE', $moduleName);
			$content = $viewer->view('Mainui.tpl', $moduleName, true);
			$response->setResult( array('mailbox' => $this->hasMailboxModel(), 'ui' => $content));
			return $response;
		}
	}
        
        public function validateRequest(Vtiger_Request $request) { 
            return $request->validateReadAccess(); 
        } 
}
?>