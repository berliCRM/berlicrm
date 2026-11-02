<?php

class Settings_OutlookAddin_Install_Action extends Vtiger_Action_Controller {
    public function checkPermission(Vtiger_Request $request) {
        if (!Users_Record_Model::getCurrentUserModel()->isAdminUser()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
        }
    }

    public function validateRequest(Vtiger_Request $request): bool {
        return $request->validateWriteAccess();
    }

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
