<?php

/**
 * Handles authenticated administrator requests to configure Word web services.
 *
 * @package berliCRM.WordAddin
 */

/** Sets up the services after POST/CSRF validation and redirects to settings. */
class Settings_WordAddin_Install_Action extends Vtiger_Action_Controller {
    /**
     * Restricts setup to CRM administrators.
     *
     * @param Vtiger_Request $request Current settings request.
     * @return void
     * @throws AppException If the current user is not an administrator.
     */
    public function checkPermission(Vtiger_Request $request) {
        if (!Users_Record_Model::getCurrentUserModel()->isAdminUser()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
        }
    }

    /**
     * Requires POST and the CRM's standard write-access/CSRF validation.
     *
     * @param Vtiger_Request $request Submitted setup request.
     * @return bool True when request validation succeeds.
     * @throws Exception If the HTTP method or write-access checks fail.
     */
    public function validateRequest(Vtiger_Request $request): bool {
        return $request->validateWriteAccess();
    }

    /**
     * Repairs registrations and redirects with a success or failure indicator.
     *
     * Logs registration errors on the server before returning to the page.
     *
     * @param Vtiger_Request $request Authorized and validated setup request.
     * @return void
     */
    public function process(Vtiger_Request $request) {
        $status = 'success';
        try {
            Settings_WordAddin_Module_Model::registerOperations();
        } catch (Throwable $error) {
            error_log('Word add-in setup failed: ' . $error->getMessage());
            $status = 'error';
        }
        header('Location: ' . Settings_WordAddin_Module_Model::INDEX_URL . '&setup=' . $status, true, 303);
    }
}
