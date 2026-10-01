<?php

class Settings_Vtiger_Backup_Action extends Settings_Vtiger_Basic_Action
{
    public function process(Vtiger_Request $request)
    {
        $response = new Vtiger_Response();
        try {
            if ($request->getMode() === 'space') {
                $space = Settings_Vtiger_BackupSpace_Model::calculate();
                foreach (array('free', 'archive', 'required', 'reserve') as $key) {
                    $space[$key] = Settings_Vtiger_BackupSpace_Model::formatBytes($space[$key]);
                }
                $response->setResult($space);
                $response->emit();
                return;
            }
            if ($request->getMode() === 'status') {
                $response->setResult(array('version' => hash('sha256', json_encode(Settings_Vtiger_Backup_Model::jobs()))));
                $response->emit();
                return;
            }
            if ($request->getMode() === 'create') {
                Settings_Vtiger_Backup_Model::install();
                Settings_Vtiger_Backup_Model::enqueue(Users_Record_Model::getCurrentUserModel());
            } elseif ($request->getMode() === 'delete') {
                Settings_Vtiger_Backup_Model::delete($request->get('id'));
            } else {
                throw new RuntimeException('LBL_BACKUP_NOT_FOUND');
            }
            $response->setResult(array('success' => true));
        } catch (Throwable $e) {
            $key = strpos($e->getMessage(), 'LBL_BACKUP_') === 0 ? $e->getMessage() : 'LBL_BACKUP_FAILED';
            $response->setError(0, vtranslate($key, 'Settings:Vtiger'));
        }
        $response->emit();
    }
}
