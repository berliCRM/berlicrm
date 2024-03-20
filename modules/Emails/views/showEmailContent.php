<?php
class Emails_showEmailContent_View extends Vtiger_Edit_View {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();

		if(!Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $request->get('record'))) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}
	}

	function postProcess(Vtiger_Request $request) {
		return;
	}
    function preProcess(Vtiger_Request $request, $display=true) {
        if($request->getMode() == 'previewEmail'){
            return;
        }
        return parent::preProcess($request,$display);
    }

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();
		
		$emailbody = $request->get('emailbody');
		$receivers = $request->get('receivers');

		$firstid = key($receivers); 
		$pmodule = getSalesEntityType($firstid);
		$recordModel = Vtiger_Record_Model::getInstanceById($firstid,$pmodule);
		
		$column_fields = $recordModel->entity->column_fields;
		$emailbody =str_replace('\n', '', $emailbody);
		$emailbody =str_replace('<a href', '<a target="_blank" href', $emailbody);
		$description = getMergedDescription($emailbody,$firstid,$pmodule);
	
		$names = vtws_getModuleNameList();
		foreach ($names as $tab) {
			$recordModel = Vtiger_Record_Model::getCleanInstance($tab);
			$table_index = $recordModel->entity->table_index;
			$search_index = $table_index;
			// special for accounts
			if ($table_index == 'accountid' && array_key_exists('account_id', $column_fields)) {
				$search_index = 'account_id';
			}
			if ((array_key_exists($search_index, $column_fields)  && !empty($column_fields[$search_index]))) {
				$description = getMergedDescription($description,$column_fields[$search_index],$tab);
			}
		}
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('EMAIL_CONTENT', $description);
		$viewer->view('showEmailContent.tpl', $moduleName);
		
	}
}

?>