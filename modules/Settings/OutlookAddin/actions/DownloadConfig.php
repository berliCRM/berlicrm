<?php
/**
 * Administrator-only download of installation-specific Outlook PHP settings.
 *
 * @package berliCRM.OfficeAddins
 */

class Settings_OutlookAddin_DownloadConfig_Action extends Vtiger_Action_Controller {
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
     * Validates POST write access and the CRM CSRF token.
     *
     * @param Vtiger_Request $request Current CRM request.
     * @return bool
     * @throws Exception If write-access validation fails.
     */
    public function validateRequest(Vtiger_Request $request): bool {
        return $request->validateWriteAccess();
    }

    /**
     * Downloads validated installation-specific PHP settings without storing credentials.
     *
     * @param Vtiger_Request $request Current CRM request.
     * @return void
     */
    public function process(Vtiger_Request $request) {
        global $site_URL;
        try {
            $configuration = Settings_OutlookAddin_Module_Model::createConfiguration($site_URL,
                $request->get('client_id'), $request->get('tenant_id'));
        } catch (Throwable $error) {
            throw new AppException(vtranslate($error->getMessage(), $request->getModule(false)));
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="outlook.config.php"');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        echo $configuration;
    }
}
