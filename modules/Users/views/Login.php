<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Users_Login_View extends Vtiger_View_Controller {

	function loginRequired() {
		return false;
	}
	
	function checkPermission(Vtiger_Request $request) {
		return true;
	}
	
	function process (Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$language = vglobal('default_language');
		$viewer->assign('MODULE', 'Vtiger');
		if(isset($_SESSION["loginerror"]))
		{
			$login_error = $_SESSION['loginerror'];
		}
		if(isset($login_error) && $login_error != "") {
			$viewer->assign("LOGIN_ERROR", $login_error);
		}
		
		$viewer->assign('LANGSTRING', vglobal('default_language'));
		$viewer->view('Login.tpl', 'Users');
	}
}
