<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/
class Settings_Vtiger_deletepdftexttemplate_Action extends Settings_Vtiger_Basic_Action {
    
	function __construct() {
		global $log;
		$this->db = new PearDatabase();
		$this->log = $log;
	}

    public function process(Vtiger_Request $request) {
		$text_databases = array('letter'=>'berli_multistarttext','conclusion'=>'berli_multiendtext');
		$text_databases_tables['berli_multistarttext'] = array('starttextid','starttexttitle','multistext','texttypes');
		$text_databases_tables['berli_multiendtext'] = array('endtextid','endtexttitle','multietext','texttype');
		
		$texttype = $request->get('texttype');
		$idlist = $request->get('idlist');

		$texttype = vtlib_purify($_REQUEST['texttype']);
		$idlist = vtlib_purify($_REQUEST['idlist']);
		$id_array=explode(';', $idlist);
		$db = new PearDatabase();

		for($i=0;$i < count($id_array);$i++) {
				$sql = "delete from ".$text_databases[$texttype]." where ".$text_databases_tables[$text_databases[$texttype]][0]." =?";
				$this->db->pquery($sql, array($id_array[$i]));
		}
		$result = array('success'=>true, 'message'=>vtranslate('LBL_FOLDER_SAVED', $moduleName), 'info'=>'alles gut');
		$response = new Vtiger_Response();
 		$response->setResult($result);
		$response->emit();
    }
}
?>