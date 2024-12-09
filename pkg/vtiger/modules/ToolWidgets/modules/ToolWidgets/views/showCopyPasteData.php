<?php

/**
 * Class ToolWidgets_showCopyPasteData_View
 * Handles the display of the "Copy-Paste Data" widget in the detail view.
 */
class ToolWidgets_showCopyPasteData_View extends Vtiger_Detail_View {
    /**
     * Checks user permissions for accessing this view.
     * 
     * @param Vtiger_Request $request The request object containing parameters.
     * @return void
     */
    public function checkPermission(Vtiger_Request $request): void {
        // No specific permissions required for this view.
        return;
    }

    /**
     * Processes the request and renders the "Copy-Paste Data" widget.
     * 
     * @param Vtiger_Request $request The request object containing parameters.
     * @return void
     */
    public function process(Vtiger_Request $request): void {
        // Retrieve module, source module, and record ID from the request
        $moduleName = $request->getModule();
        $sourceModule = $request->get('source_module');
        $recordId = $request->get('record');

        // Initialize the viewer and assign template variables
        $viewer = $this->getViewer($request);
        $viewer->assign('SOURCEMODULE', $sourceModule);
        $viewer->assign('RECORD', $recordId);
        $viewer->assign('MODULE', $moduleName);

        // Render the view
        $viewer->view('showCopyPasteDataWidget.tpl', 'ToolWidgets');
    }
}
