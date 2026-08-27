<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_DetailViewCounts_Action extends Vtiger_Action_Controller {

	protected $permissionChecked = false;

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = (int) $request->get('record');
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		if (!$moduleModel || !$recordId ||
				!Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $recordId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}

		$this->permissionChecked = true;
		return true;
	}

	public function process(Vtiger_Request $request) {
		// Some framework pseudo-modules bypass WebUI::triggerCheckPermission().
		// Enforce the parent-record check here as well for a generic action.
		if (!$this->permissionChecked) {
			$this->checkPermission($request);
		}

		$moduleName = $request->getModule();
		$recordId = (int) $request->get('record');
		$relationIds = $request->get('relation_ids', array());
		if (!is_array($relationIds)) {
			$relationIds = array();
		}
		$includeComments = (int) $request->get('include_comments') === 1;
		if (empty($relationIds) && !$includeComments) {
			$response = new Vtiger_Response();
			$response->setResult(array(
					'module' => $moduleName,
					'record' => $recordId,
					'comments' => null,
					'relations' => array()
			));
			$response->emit();
			return;
		}

		$detailViewModel = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		$result = $detailViewModel->getDeferredRelatedRecordCounts(
				$relationIds, $includeComments);

		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
