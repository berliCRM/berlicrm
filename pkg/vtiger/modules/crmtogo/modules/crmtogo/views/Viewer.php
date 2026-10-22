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
	function assign($key, $value = NULL, $nocache = false, $scope = null) {
		$this->parameters[$key] = $value;
	}

	function viewController() {
		$smarty = new Vtiger_Viewer();

		foreach($this->parameters as $k => $v) {
			$smarty->assign($k, $v);
		}
		$smarty->assign("IS_SAFARI", crmtogo::isSafari());
		$smarty->assign("SKIN", crmtogo::config('Default.Skin'));
		$this->registFunctions($smarty);
		return $smarty;
	}

	function registFunctions($smarty = null) {
		if ($smarty == null) {
			$smarty = new Vtiger_Viewer();
		}
		// PHP-Funktionen im Template registrieren
		$functionsArray = array(
			'vtemplate_path', 'vtranslate', 'vresource_url', 'array_keys', 'vimage_path', 'ucfirst', 'stripos', 'date',
			'decode_html', 'getPurifiedSmartyParameters', 'method_exists', 'trim', 'array_merge', 'array_map', 'array_key_exists',
			'decimalFormat', 'isPermitted', 'sprintf', 'strpos', 'end', 'html_entity_decode', 'getEntityName', 'json_decode',
			'array_shift', 'getOwnerName', 'array_push', 'get_class', 'file_exists', 'str_replace', 'function_exists',
			'getTranslatedCurrencyString', 'abs', 'strstr', 'urlencode', 'strcasecmp', 'textlength_check', 'array_slice', 'sizeof', 'vtlib_purify', 'getTranslatedString'
		);

		foreach ($functionsArray as $function) {
			$smarty->registerPlugin('modifier', $function, $function);
		}
	}

	function process($templateName) {
		$smarty = $this->viewController();
		$response = new crmtogo_API_Response();
		$response->setResult($smarty->fetch(vtlib_getModuleTemplate('crmtogo', $templateName)));
		return $response;
	}
}
?>