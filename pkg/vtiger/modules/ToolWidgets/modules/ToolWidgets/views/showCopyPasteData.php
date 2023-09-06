<?php
class ToolWidgets_showCopyPasteData_View extends Vtiger_Detail_View {
	function checkPermission(Vtiger_Request $request) {
		return;
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$source_module = $request->get('source_module');
		$recordid = $request->get('record');

		$Index_View_Obj = new Vtiger_Index_View();
		$viewer = $Index_View_Obj->getViewer($request);
		$viewer->assign('COPYPASTESTRING', $copypastestring);
		$viewer->assign('SOURCEMODULE', $source_module);
        $viewer->assign('RECORD', $recordid);
        $viewer->assign('MODULE', $moduleName);
        $viewer->view('showCopyPasteDataWidget.tpl', 'ToolWidgets');
	}
}
?>