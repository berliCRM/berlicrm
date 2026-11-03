<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0.
 * The Original Code is: berliCRM Open Source.
 *************************************************************************************/

require_once 'modules/Accounts/AccountDatasheet.php';

/** Generate the current Account datasheet and send it as a PDF download. */
class Accounts_DownloadDatasheet_Action extends Vtiger_Action_Controller {

    public function checkPermission(Vtiger_Request $request) {
        $recordId = $request->get('record');
        if (!ctype_digit((string)$recordId)
            || (int)$recordId <= 0
            || !Users_Privileges_Model::isPermitted('Accounts', 'DetailView', (int)$recordId)) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Accounts'));
        }
    }

    public function process(Vtiger_Request $request) {
        $download = null;

        try {
            $download = Accounts_AccountDatasheet_Service::createTemporaryPdf((int)$request->get('record'));
            if (headers_sent()) {
                throw new RuntimeException('Cannot send Account datasheet because response headers were already sent.');
            }

            $filename = $download['filename'];
            $fallbackName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename);
            $fallbackName = trim((string)$fallbackName, '-');
            if ($fallbackName === '') {
                $fallbackName = 'Account-Datasheet.pdf';
            }

            header('Content-Type: ' . Accounts_AccountDatasheet_Service::MIME_TYPE);
            header('Content-Length: ' . filesize($download['path']));
            header('Content-Disposition: attachment; filename="' . $fallbackName
                . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, no-store, max-age=0');
            header('Pragma: no-cache');

            if (readfile($download['path']) === false) {
                throw new RuntimeException('Could not stream the Account datasheet.');
            }
        } finally {
            if (!empty($download['path']) && is_file($download['path'])) {
                @unlink($download['path']);
            }
        }
    }
}
