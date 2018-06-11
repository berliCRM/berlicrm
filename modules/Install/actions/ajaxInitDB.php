<?php
class Install_ajaxInitDB_Action extends Vtiger_BasicAjax_Action {
	
	function loginRequired() {
		return false;
	}
	
	public function checkPermission() {
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
		$mode = $request->get('mode');
		if (!$this->checkPermission()) {
			$ret[1] = 'Already Installed';
			$mode = 'failed';
		}
		$path = Install_Utils_Model::INSTALL_LOG;
		$fh = fopen($path, 'a+');
		fwrite($fh, "[".date('Y-m-d h:i:s')."] Start $mode\n");
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
		fwrite($fh, "[".date('Y-m-d h:i:s')."] End $mode\n\n");
		fclose($fh);
		$result = array("success" => $ret[0], "message" => $ret[1]);
		
		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}