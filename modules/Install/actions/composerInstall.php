<?php
class Install_composerInstall_Action extends Vtiger_BasicAjax_Action {
	
	function loginRequired():bool {
		return false;
	}
	
	public function checkPermission(Vtiger_Request $request) {
		$ret = true;
		$path = Install_Utils_Model::INSTALL_FINISHED;
		$isInstalled = file_exists($path);

		if($isInstalled) {
			$ret = false;
		}
		return $ret;
	}
	
	function process(Vtiger_Request $request) { 
		$ret = array(false, 'Unknown error');
		if (!$this->checkPermission($request)) {
			$ret[1] = 'Already installed<br>(if this is in error, delete /test/installFinished)';
		}

		try {
			// $ret[0] = true;
			// $ret[1] = getTranslatedString('LBL_YES', 'Vtiger');
			if(Install_Utils_Model::installComposer()) {
				$ret[0] = true;
			}
		} catch (Exception $e) {
			$ret[1] = $e->getMessage();
		}
		$result = array("success" => $ret[0], "message" => $ret[1]);
		
		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}