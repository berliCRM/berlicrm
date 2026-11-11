<?php
/**
 * Administrator view for Outlook add-in configuration and deployment.
 *
 * @package berliCRM.OfficeAddins
 */

class Settings_OutlookAddin_Index_View extends Settings_Vtiger_Index_View {
    /**
     * Returns the localized Outlook settings page title.
     *
     * @param Vtiger_Request $request Current CRM request.
     * @return string
     */
    public function getPageTitle(Vtiger_Request $request): string {
        return vtranslate('LBL_OUTLOOK_ADDIN', $request->getModule(false));
    }

    /**
     * Renders setup state, public Entra IDs, registration checks and installation instructions.
     *
     * @param Vtiger_Request $request Current CRM request.
     * @return void
     */
    public function process(Vtiger_Request $request) {
        global $site_URL;
        $model = new Settings_OutlookAddin_Module_Model();
        $urls = array(); $manifestError = ''; $configError = ''; $operationError = '';
        $ids = array('client_id' => '', 'tenant_id' => '');
        try { $urls = $model->getUrls($site_URL); $model->createManifest($site_URL); }
        catch (Throwable $error) { $manifestError = $error->getMessage(); }
        try { $ids = $model->getMicrosoftIds(); }
        catch (Throwable $error) { $configError = 'LBL_OUTLOOK_CONFIG_ERROR'; }
        $configured = false;
        try {
            $model->validateMicrosoftId($ids['client_id'], 'client_id');
            $model->validateMicrosoftId($ids['tenant_id'], 'tenant_id');
            $configured = true;
        } catch (InvalidArgumentException $error) { /* Show the required setup fields. */ }
        $operations = array();
        try { $operations = $model->getOperationStatus(); }
        catch (Throwable $error) { $operationError = 'LBL_OUTLOOK_DATABASE_ERROR'; }
        $viewer = $this->getViewer($request);
        $viewer->assign('QUALIFIED_MODULE', $request->getModule(false));
        $viewer->assign('WIDTHTYPE', Users_Record_Model::getCurrentUserModel()->get('rowheight') ?: 'medium');
        $viewer->assign('CRM_URL', $site_URL);
        $viewer->assign('ADDIN_LANGUAGE', Users_Record_Model::getCurrentUserModel()->get('language') ?: 'en_us');
        $viewer->assign('ADDIN_URLS', $urls);
        $viewer->assign('MISSING_FILES', $model->getMissingFiles());
        $viewer->assign('OPERATIONS', $operations);
        $viewer->assign('OPERATION_ERROR', $operationError);
        $viewer->assign('MANIFEST_ERROR', $manifestError);
        $viewer->assign('CONFIG_ERROR', $configError);
        $viewer->assign('CONFIGURED', $configured);
        $viewer->assign('MICROSOFT_IDS', $ids);
        $viewer->assign('GUID_PATTERN', '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}');
        $viewer->assign('SETUP_SUCCESS', $request->get('setup') === 'success');
        $viewer->assign('SETUP_ERROR', $request->get('setup') === 'error');
        $viewer->view('Index.tpl', $request->getModule(false));
    }
}
