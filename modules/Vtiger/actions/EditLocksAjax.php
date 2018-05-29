<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_EditLocksAjax_Action extends Vtiger_Action_Controller {

	function checkPermission(Vtiger_Request $request) {
		return;
	}

    // basic mechanism to provide edit locks for crm entities, gets called by vtiger/Edit.js
	public function process(Vtiger_Request $request) {
        global $current_user;
		$crmid = (int) $request->get('record');
        $mode = $request->get('mode');

        if ($crmid > 0) {
            // release lock for $crmid
            if ($mode == "release") {
                unlink("logs/editlock_$crmid.txt");
                $result = true;
            }

            // set lock for $crmid
            if ($mode == "lock") {
                file_put_contents("logs/editlock_$crmid.txt",trim($current_user->first_name." ".$current_user->last_name),LOCK_EX);
                $result = true;
            }

            // check lock for $crmid
            if ($mode == "isLocked") {
                $username = @file_get_contents("logs/editlock_$crmid.txt");

                if (!empty($username)) {
                    $locktime = filemtime("logs/editlock_$crmid.txt");
                    // release lock after 2 min to prevent deadlock (javascript will refresh it every 115s. while edit view is open)
                    if ($locktime < time() - 120) {
                        unlink("logs/editlock_$crmid.txt");
                        $result = false;
                    }
                    else {
                        $result = array("lockedByUser" => $username);
                    }
                }
                else {
                    $result = false;
                }
            }
        }
        else {
            $result = false;
        }
        $response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
    }
}