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
		$emailbody =str_replace('\n', '', $emailbody);
		$emailbody =str_replace('<a href', '<a target="_blank" href', $emailbody);
		$description = getMergedDescription($emailbody,$firstid,$pmodule);

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('EMAIL_CONTENT', $description);
		$viewer->view('showEmailContent.tpl', $moduleName);
		
	}
}

?>