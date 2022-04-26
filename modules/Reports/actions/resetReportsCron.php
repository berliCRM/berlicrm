<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Reports_resetReportsCron_Action extends Vtiger_BasicAjax_Action {

    public function __construct() {
        parent::__construct();
		$this->exposeMethod('resetReportsCron');
	}
    
    public function process(Vtiger_Request $request) {
		$action = $request->get('action');
		if(!empty($action)) {
			$this->invokeExposedMethod($action, $request);

			return;
		}
	}
    
    /**
	 * Function to get related Records count from this relation
	 * @param <Vtiger_Request> $request
	 * @return <Number> Number of record from this relation
	 */
	public function resetReportsCron(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Reports_Module_Model::getInstance($moduleName);
		$loadUrl = $moduleModel->getListViewUrl();
		$response = new Vtiger_Response();
		try {
			$CronTasks_Record_Model = Settings_CronTasks_Record_Model::getInstanceByName('ScheduleReports');
			//$CronTasks_Record_Model->setModule('Reports');
			$CronTasks_Record_Model->set('mode', 'edit');
			$CronTasks_Record_Model->set('status', '1');
			$CronTasks_Record_Model->set('laststart', strtotime(date('Y-m-d H:i:s')));
			$CronTasks_Record_Model->save();
			$response->setResult(array('success'=>true, 'listViewUrl'=>$loadUrl));
			$response->emit();
		}
		catch (Exception $e) {
			$response->setError($e->getCode(),$e->getMessage());
			$response->emit();
		}

    }
 
}
