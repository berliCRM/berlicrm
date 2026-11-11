<?php

/**
 * Serves an installation-specific Office XML manifest to CRM administrators.
 *
 * @package berliCRM.WordAddin
 */

/** Downloads generated XML without exposing credentials or changing the template. */
class Settings_WordAddin_DownloadManifest_Action extends Vtiger_Action_Controller {
    /**
     * Restricts manifest downloads to CRM administrators.
     *
     * @param Vtiger_Request $request Current download request.
     * @return void
     * @throws AppException If the current user is not an administrator.
     */
    public function checkPermission(Vtiger_Request $request) {
        if (!Users_Record_Model::getCurrentUserModel()->isAdminUser()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
        }
    }

    /**
     * Generates the manifest from site_URL and emits a non-cacheable attachment.
     *
     * @global string $site_URL Configured CRM installation URL.
     * @param Vtiger_Request $request Authorized download request.
     * @return void
     * @throws InvalidArgumentException If site_URL is unsuitable for Office.
     * @throws RuntimeException If the manifest template is missing or invalid.
     */
    public function process(Vtiger_Request $request) {
        global $site_URL;
        $manifest = Settings_WordAddin_Module_Model::createManifest($site_URL);
        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="berliCRM-Word-Addin-manifest.xml"');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        echo $manifest;
    }
}
