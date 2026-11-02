<?php

class Settings_OutlookAddin_DownloadConfig_Action extends Vtiger_Action_Controller {
    public function checkPermission(Vtiger_Request $request) {
        if (!Users_Record_Model::getCurrentUserModel()->isAdminUser()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
        }
    }

    public function validateRequest(Vtiger_Request $request): bool {
        return $request->validateWriteAccess();
    }

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
