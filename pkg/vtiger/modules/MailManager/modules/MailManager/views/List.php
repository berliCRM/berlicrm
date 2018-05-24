<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class MailManager_List_View extends MailManager_Abstract_View {

	static $controllers = array(
			'mainui' =>	array( 'file' => 'controllers/MainUIController.php',	'class' => 'MailManager_MainUI_View' ),
			'folder' => array( 'file' => 'controllers/FolderController.php',	'class' => 'MailManager_Folder_View' ),
			'mail'   => array( 'file' => 'controllers/MailController.php',		'class' => 'MailManager_Mail_View'   ),
			'relation'=>array( 'file' => 'controllers/RelationController.php',	'class'=> 'MailManager_Relation_View'),
			'settings'=>array( 'file' => 'controllers/SettingsController.php',	'class'=> 'MailManager_Settings_View'),
			'search'  =>array( 'file' => 'controllers/SearchController.php',	'class'=> 'MailManager_Search_View'	 ),
	);

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
				"libraries.jquery.ckeditor.ckeditor",
				"libraries.jquery.ckeditor.adapters.jquery",
				"modules.Vtiger.resources.CkEditor",
				"modules.Emails.resources.MassEdit"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function process(Vtiger_Request $request) {
		$request = MailManager_Request::getInstance($request);

		if (!$request->has('_operation')) {
			return $this->processRoot($request);
		}
		$operation = $request->getOperation();
		$controllerInfo = self::$controllers[$operation];
		// TODO Handle case when controller information is not available
		//$controllerFile = dirname(__FILE__) . '/' . $controllerInfo['file'];
		//checkFileAccessForInclusion($controllerFile);
		//include_once $controllerFile;
		$controller = new $controllerInfo['class'];

		// Making sure to close the open connection
		if ($controller) $controller->closeConnector();
		if($controller->validateRequest($request)) { 
                    $response = $controller->process($request); 
                    if ($response) $response->emit(); 
	        } 
		
		unset($request);
		unset($response);
	}

	public function processRoot(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->view('index.tpl', $moduleName);
	}
}