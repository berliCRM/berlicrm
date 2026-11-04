<?php
/**
 * Administrator POST action for repeatable Outlook add-in installation.
 *
 * @package berliCRM.OfficeAddins
 */

class Settings_OutlookAddin_Install_Action extends Vtiger_Action_Controller {
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
     * Runs the shared Outlook setup and redirects to its settings result.
     *
     * @param Vtiger_Request $request Current CRM request.
     * @return void
     */
    public function process(Vtiger_Request $request) {
        $status = 'success';
        try {
            require_once 'outlook-addin/update.php';
            berli_outlookAddinUpdate();
        } catch (Throwable $error) {
            error_log('Outlook add-in setup failed: ' . $error->getMessage());
            $status = 'error';
        }
        header('Location: ' . Settings_OutlookAddin_Module_Model::INDEX_URL . '&setup=' . $status, true, 303);
    }
}
