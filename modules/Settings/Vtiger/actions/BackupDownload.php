<?php

class Settings_Vtiger_BackupDownload_Action extends Settings_Vtiger_Basic_Action
{
    public function validateRequest(Vtiger_Request $request): bool
    {
        return $request->validateReadAccess();
    }

    public function checkPermission(Vtiger_Request $request)
    {
        parent::checkPermission($request);
        if (!Settings_Vtiger_Backup_Model::isEnabled()) {
            throw new AppException(vtranslate('LBL_BACKUP_DISABLED', 'Settings:Vtiger'));
        }
    }

    public function process(Vtiger_Request $request)
    {
        // Permission is checked by the Settings controller, including direct URLs.
        $lock = Settings_Vtiger_Backup_Model::lock('queue');
        try {
            $job = Settings_Vtiger_Backup_Model::read($request->get('id'));
            $path = Settings_Vtiger_Backup_Model::path($job['id'], '.zip');
            if ($job['status'] !== 'ready' || !is_file($path)) {
                throw new AppException(vtranslate('LBL_BACKUP_NOT_FOUND', 'Settings:Vtiger'));
            }
            $file = Settings_Vtiger_Backup_Model::openPayload($path);
            if (!$file) {
                throw new AppException(vtranslate('LBL_BACKUP_NOT_FOUND', 'Settings:Vtiger'));
            }
            $size = fstat($file)['size'] - strlen(Settings_Vtiger_Backup_Model::GUARD);
        } finally {
            fclose($lock);
        }
        session_write_close();
        ini_set('zlib.output_compression', '0');
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="berliCRM-' . $job['id'] . '.zip"');
        header('Content-Length: ' . $size);
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        set_time_limit(0);
        while (!feof($file) && !connection_aborted()) {
            $chunk = fread($file, 1024 * 1024);
            if ($chunk === false) {
                break;
            }
            echo $chunk;
            flush();
            usleep(100000); // Limit each download to approximately 10 MiB/s.
        }
        fclose($file);
        exit;
    }
}
