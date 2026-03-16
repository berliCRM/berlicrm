<?php
/*+**********************************************************************************
 * berliCRM / vtiger style Settings Action
 ************************************************************************************/

require_once 'modules/Settings/Vtiger/models/ConfigSignature.php';
require_once 'modules/Settings/Vtiger/models/ConfigModule.php';

class Settings_Vtiger_SaveConfigSignature_Action extends Settings_Vtiger_Index_Action {

	public function process(Vtiger_Request $request) {

		$enabled = (int)$request->get('enabled');

		// IMPORTANT: keep HTML as-is
		$signatureHtml = $request->getRaw('description');

		// remove <html>, <head>, <body> wrappers if present
		$signatureHtml = preg_replace('~^\s*<!DOCTYPE[^>]*>\s*~i', '', $signatureHtml);
		$signatureHtml = preg_replace('~</?(html|head|body)[^>]*>~i', '', $signatureHtml);

		// also remove <title>...</title>
		$signatureHtml = preg_replace('~<title[^>]*>.*?</title>~is', '', $signatureHtml);

		$model = Settings_Vtiger_ConfigSignature::getInstance();
		$model->save($enabled, $signatureHtml);

		// Build proper return URL using ConfigModule model
		$configModuleModel = Settings_Vtiger_ConfigModule_Model::getInstance();

		$menuItem = $configModuleModel->getMenuItem();

		$redirectUrl = 'index.php?module=Vtiger&parent=Settings&view=ConfigEditorDetail'
			. '&block=' . $menuItem->get('blockid')
			. '&fieldid=' . $menuItem->get('fieldid');

		header('Location: ' . $redirectUrl);
		exit;
	}

}
