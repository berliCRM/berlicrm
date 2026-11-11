<?php

/**
 * Displays the administrator's Word add-in installation and manifest page.
 *
 * @package berliCRM.WordAddin
 */

/** Presents server checks and installation guidance using the CRM settings UI. */
class Settings_WordAddin_Index_View extends Settings_Vtiger_Index_View {
    /**
     * Returns the translated settings-page title.
     *
     * @param Vtiger_Request $request Current settings request.
     * @return string Localized page title.
     */
    public function getPageTitle(Vtiger_Request $request): string {
        return vtranslate('LBL_WORD_ADDIN', $request->getModule(false));
    }

    /**
     * Reads installation status and renders the page without modifying CRM data.
     *
     * Manifest/URL errors are shown on the page. Administrator permission and
     * read-access checks are inherited from Settings_Vtiger_Index_View.
     *
     * @global string $site_URL Configured CRM installation URL.
     * @param Vtiger_Request $request Authorized settings request.
     * @return void
     * @throws RuntimeException If service registration metadata cannot be queried.
     */
    public function process(Vtiger_Request $request) {
        global $site_URL;
        $viewer = $this->getViewer($request);
        $model = new Settings_WordAddin_Module_Model();
        $missing = $model->getMissingFiles();
        $error = '';
        $urls = array();
        try {
            $urls = $model->getUrls($site_URL);
            $model->createManifest($site_URL);
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
        $operations = $model->getOperationStatus();
        $viewer->assign('QUALIFIED_MODULE', $request->getModule(false));
        $viewer->assign('WIDTHTYPE', Users_Record_Model::getCurrentUserModel()->get('rowheight') ?: 'medium');
        $viewer->assign('CRM_URL', $site_URL);
        $viewer->assign('ADDIN_URLS', $urls);
        $viewer->assign('MISSING_FILES', $missing);
        $viewer->assign('OPERATIONS', $operations);
        $viewer->assign('MANIFEST_ERROR', $error);
        $viewer->assign('READY', !$error && !$missing && !in_array(false, $operations, true));
        $viewer->assign('SETUP_SUCCESS', $request->get('setup') === 'success');
        $viewer->assign('SETUP_ERROR', $request->get('setup') === 'error');
        $viewer->view('Index.tpl', $request->getModule(false));
    }
}
