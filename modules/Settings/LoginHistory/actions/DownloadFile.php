<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_LoginHistory_DownloadFile_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
        global $current_user;

        if ($current_user->is_admin != "on") {
            //throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
        }
	}

    public function process(Vtiger_Request $request) {
        $filetyp = $request->get('filetyp');
        $mode = $request->get('mode');
        $selecteduser = $request->get('selecteduser');
        $offset = $request->get('offset');
        $turn = $request->get('turn');

        // first we need to download the file.
        $isDownloaded = $this->download($filetyp, $mode, $selecteduser, $offset, $turn );
        
        if($isDownloaded == "loaddel"){
            // then we del it here only if it was allready downloaded.
            $isDeleted = $this->deleteunlink($filetyp, $mode, $selecteduser, $offset, $turn );
            if($isDeleted == "deleted"){
                // the file is now deleted. 
            }
        }
        else if($isDownloaded == "File not found!"){
            // File to download was not found!
            echo "File to download was not found!". "\n";
        }
        else{
            // maybe another option in the future.
        }
        
	}

	public function download($filetyp, $mode, $selecteduser, $offset, $turn) {

        $path = "storage/";
		$fileName = "LoginHistory".$mode.".".$filetyp;
        //$datetime = date("Y-m-d",$ftime);

        if (file_exists($path.$fileName)) {
            // lazily find filename of backup
            list($file) = glob($path.$fileName);

            if (ob_get_level()){
                // clear output buffer if used (or readfile might run into memory limits)
                ob_end_clean();     
            }
            
            //$ftime = filemtime($file);
            $size = filesize($file);

            //header("Content-type: application/zip");
            header("Content-type: text/csv");
            header("Pragma: public");
            header("Cache-Control: private");
            header("Content-Disposition: attachment; filename=$fileName");
            header("Content-Length: $size");
            readfile($file);

            $result = $turn;
            return $result;
        }
        else{
            return "File not found!";
        }
        
	}

    public function deleteunlink($filetyp, $mode, $selecteduser, $offset, $turn) {

        $path = "storage/";
        $fileName = "LoginHistory".$mode.".".$filetyp;

        // if this file exist, delete it.
        if (file_exists($path.$fileName)) {
            if (@unlink($path.$fileName) == true) {
                return "deleted";
            }
        }
    }

}