<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_LoginHistory_DownloadFile_Action extends Vtiger_Action_Controller {

    /**
     * Checks if the current user has admin privileges.
     *
     * @param Vtiger_Request $request The request object.
     */
    public function checkPermission(Vtiger_Request $request): void {
        global $current_user;

        if ($current_user->is_admin !== "on") {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
        }
    }

    /**
     * Processes the file download request and deletes the file if necessary.
     *
     * @param Vtiger_Request $request The request object containing parameters.
     */
    public function process(Vtiger_Request $request): void {
        $filetyp = $request->get('filetyp');
        $mode = $request->get('mode');
        $selecteduser = $request->get('selecteduser');
        $offset = $request->get('offset');
        $turn = $request->get('turn');

        // First, attempt to download the file.
        $isDownloaded = $this->download($filetyp, $mode, $selecteduser, $offset, $turn);

        if ($isDownloaded === "loaddel") {
            // If file was successfully downloaded, delete it afterward.
            $isDeleted = $this->deleteunlink($filetyp, $mode, $selecteduser, $offset, $turn);
            if ($isDeleted === "deleted") {
                // File has been successfully deleted.
            }
        } 
		elseif ($isDownloaded === "File not found!") {
            // Handle case where the file is not found.
            echo "File to download was not found!" . PHP_EOL;
        } 
		else {
            // Placeholder for future functionality.
        }
    }

    /**
     * Downloads the requested file if it exists.
     *
     * @param string $filetyp The file type (e.g., csv, zip).
     * @param string $mode The mode of the login history.
     * @param string|null $selecteduser The selected user.
     * @param string|null $offset The offset for pagination.
     * @param string|null $turn The turn identifier.
     * @return string Returns "File not found!" if the file is missing, otherwise returns $turn.
     */
    public function download(string $filetyp, string $mode, ?string $selecteduser, ?string $offset, ?string $turn): string {
        $path = "storage/";
        $fileName = "LoginHistory" . $mode . "." . $filetyp;

        $files = glob($path . $fileName);
        $file = $files[0] ?? null;
        if (!$file) {
            return "File not found!";
        }

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        $size = filesize($file);

        // Set headers for file download
        header("Content-type: text/csv");
        header("Pragma: public");
        header("Cache-Control: private");
        header("Content-Disposition: attachment; filename=$fileName");
        header("Content-Length: $size");

        readfile($file);
        return $turn ?? "unknown";
    }

    /**
     * Deletes the specified file from storage.
     *
     * @param string $filetyp The file type.
     * @param string $mode The mode of the login history.
     * @param string|null $selecteduser The selected user.
     * @param string|null $offset The offset for pagination.
     * @param string|null $turn The turn identifier.
     * @return string Returns "deleted" if the file was successfully removed, otherwise an error message.
     */
    public function deleteunlink(string $filetyp, string $mode, ?string $selecteduser, ?string $offset, ?string $turn): string {
        $path = "storage/";
        $fileName = "LoginHistory" . $mode . "." . $filetyp;

        if (file_exists($path . $fileName)) {
            error_clear_last();
            if (!unlink($path . $fileName)) {
                $error = error_get_last();
                return $error ? $error['message'] : "Deletion failed!";
            }
            return "deleted";
        }
        return "File not found!";
    }
}
