<?php
/**
 * Administrator-only download of the installation-specific Outlook XML manifest.
 *
 * @package berliCRM.OfficeAddins
 */

class Settings_OutlookAddin_DownloadManifest_Action extends Vtiger_Action_Controller {
    /**
     * Requires an administrator before accessing Outlook add-in settings.
     *
     * @param Vtiger_Request $request Current CRM request.
     * @return void
     * @throws AppException If the current user is not an administrator.
     */
    public function checkPermission(Vtiger_Request $request) {
        if (!Users_Record_Model::getCurrentUserModel()->isAdminUser()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
        }
    }

    /**
     * Downloads the installation-specific Outlook XML manifest.
     *
     * @param Vtiger_Request $request Current CRM request.
     * @return void
     */
    public function process(Vtiger_Request $request) {
        global $site_URL;
        try { $manifest = Settings_OutlookAddin_Module_Model::createManifest($site_URL); }
        catch (Throwable $error) { throw new AppException(vtranslate($error->getMessage(), $request->getModule(false))); }
        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="berliCRM-Outlook-Addin-manifest.xml"');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        echo $manifest;
    }
}
