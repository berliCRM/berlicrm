<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Calendar_Export_View extends Vtiger_Export_View {

	public function preprocess(Vtiger_Request $request,$display=false) {
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$selected_ids = $request->get('selected_ids');

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('ACTION', 'ExportData');
		$viewer->assign('SELECTED_IDS', $selected_ids);
		
		$viewer->view('Export.tpl', $moduleName);
	}

	public function postprocess(Vtiger_Request $request) {
	}
}