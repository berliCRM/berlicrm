<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PurchaseOrder_CompanyDetails_Action extends Vtiger_Action_Controller {

	function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if(!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
			throw new AppException(vtranslate($moduleName).' '.vtranslate('LBL_NOT_ACCESSIBLE'));
		}
	}

	function process(Vtiger_Request $request) {
		$companyModel = Vtiger_CompanyDetails_Model::getInstanceById();
        $companyDetails = array(
            'street' => $companyModel->get('organizationname') .' '.$companyModel->get('address'),
            'city' => $companyModel->get('city'),
            'state' => $companyModel->get('state'),
            'code' => $companyModel->get('code'),
            'country' =>  $companyModel->get('country'),
            );
		$response = new Vtiger_Response();
		$response->setResult($companyDetails);
		$response->emit();
	}
}

?>