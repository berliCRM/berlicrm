<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Inventory_ProductsPopupAjax_View extends Inventory_ProductsPopup_View {
	
	function __construct() {
		parent::__construct();
		$this->exposeMethod('getListViewCount');
		$this->exposeMethod('getRecordsCount');
		$this->exposeMethod('getPageCount');
	}
	
	/**
	 * Function returns module name for which Popup will be initialized
	 * @param type $request
	 */
	public function getModule(Vtiger_Request $request): string{
		return 'Products';
	}
	
	function preProcess(Vtiger_Request $request, $display = true): void{

	}

	function postProcess(Vtiger_Request $request): void{

	}

	function process (Vtiger_Request $request): void{
		$mode = $request->get('mode');
		if(!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
		}
		$viewer = $this->getViewer ($request);

		$this->initializeListViewContents($request, $viewer);
		$moduleName = 'Inventory';
		$viewer->assign('MODULE_NAME',$moduleName);
		echo $viewer->view('PopupContents.tpl', $moduleName, true);
	}
}