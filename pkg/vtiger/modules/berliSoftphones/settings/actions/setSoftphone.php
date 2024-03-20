<?php
class Settings_berliSoftphones_setSoftphone_Action extends Settings_Vtiger_Index_Action {

    // To save softphone settings
    public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$checkboxid = $request->get('checkboxid');
		$id = explode('_', $checkboxid);
  
        $response = new Vtiger_Response();
        try {
			$query = 'UPDATE berli_softphones SET phactive=? ';
			$db->pquery($query, array(''));
			if ($id[1] !='all') {
				$query = 'UPDATE berli_softphones SET phactive=?  WHERE phoneid = ?';
				$test = $db->pquery($query, array('checked', $id[1]));
 				$response->setResult(array(vtranslate('LBL_CONFIG_SAVED', 'PBXManager')));
			}
			else {
				$response->setResult(array(vtranslate('LBL_CONFIG_CANCEL', 'PBXManager')));
			}
        } catch (Exception $e) {
                $response->setError($e->getMessage());
        }
        $response->emit();
    }
}
