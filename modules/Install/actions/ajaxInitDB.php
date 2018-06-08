<?php
class Install_ajaxInitDB_Action extends Vtiger_BasicAjax_Action {
	
	function loginRequired() {
		return false;
	}
	
	public function checkPermission() {
		$webuiInstance = new Vtiger_WebUI();
		$isInstalled = $webuiInstance->isInstalled();

		if($isInstalled) {
			throw new AppException('Already installed');
		}
	}
	
	function process(Vtiger_Request $request) {
		// $this->checkPermission();
		$ret = array(false, 'Unknown error');
		$mode = $request->get('mode');
		if ($mode == 'config') {
			try {
				// Create configuration file
				$configParams = $_SESSION['config_file_info'];
				$configFile = new Install_ConfigFileUtils_Model($configParams);
				$ret[0] = $configFile->createConfigFile();
			} catch (Exception $e) {
				$ret[1] = $e->getMessage();
			}
		} elseif ($mode == 'database') {
			// Initialize and set up tables
			$tmp = Install_InitSchema_Model::initialize();
			if ($tmp) {
				$ret[0] = true;
			} else {
				$ret[1] = $tmp;
			}
		} elseif ($mode == 'user') {
			try {
				//create admin user + files
				$ret[0] = Install_InitSchema_Model::createUser();
			} catch (Exception $e) {
				$ret[1] = $e->getMessage();
			}
		} elseif ($mode == 'modules') {
			try {
				// Install all the available modules
				$ret[0] = Install_Utils_Model::installModules();
			} catch (Exception $e) {
				$ret[1] = $e->getMessage();
			}
		} elseif ($mode == 'final') {
			try {
				// Install_InitSchema_Model::upgrade();
				$ret[0] = Install_InitSchema_Model::setCRMNOWmodifications();
			} catch (Exception $e) {
				$ret[1] = $e->getMessage();
			}
		}
		$result = array("success" => $ret[0], "message" => $ret[1]);
		
		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}