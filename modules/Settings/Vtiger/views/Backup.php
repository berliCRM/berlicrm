<?php

class Settings_Vtiger_Backup_View extends Settings_Vtiger_Index_View
{
    public function checkPermission(Vtiger_Request $request)
    {
        parent::checkPermission($request);
        if (!Settings_Vtiger_Backup_Model::isEnabled()) {
            throw new AppException(vtranslate('LBL_BACKUP_DISABLED', 'Settings:Vtiger'));
        }
    }

    public function process(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $jobs = array();
        $error = '';
        try {
            $jobs = Settings_Vtiger_Backup_Model::jobs();
        } catch (Throwable $e) {
            $error = vtranslate('LBL_BACKUP_DIRECTORY_ERROR', 'Settings:Vtiger');
        }
        $viewer->assign('BACKUP_EXCLUDE_SOURCE', Settings_Vtiger_Backup_Model::excludesSource());
        $viewer->assign('BACKUP_JOBS', $jobs);
        $viewer->assign('BACKUP_VERSION', hash('sha256', json_encode($jobs)));
        $viewer->assign('BACKUP_ERROR', $error);
        $viewer->assign('QUALIFIED_MODULE', $request->getModule(false));
        $viewer->view('Backup.tpl', $request->getModule(false));
    }

    public function getHeaderScripts(Vtiger_Request $request): array
    {
        return array_merge(parent::getHeaderScripts($request),
            $this->checkAndConvertJsScripts(array('modules.Settings.Vtiger.resources.Backup')));
    }
}
