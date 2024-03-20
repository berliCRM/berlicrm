<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include_once 'includes/runtime/Viewer.php';

class crmtogo_UI_Viewer extends Vtiger_Viewer{

	private $parameters = array();
	function assign($key, $value = NULL, $nocache = false) {
		$this->parameters[$key] = $value;
	}

	function viewController() {
		$smarty = new Vtiger_Viewer();

		foreach($this->parameters as $k => $v) {
			$smarty->assign($k, $v);
		}
		$smarty->assign("IS_SAFARI", crmtogo::isSafari());
		$smarty->assign("SKIN", crmtogo::config('Default.Skin'));
		return $smarty;
	}

	function process($templateName) {
		$smarty = $this->viewController();
		$response = new crmtogo_API_Response();
		$response->setResult($smarty->fetch(vtlib_getModuleTemplate('crmtogo', $templateName)));
		return $response;
	}
}
?>