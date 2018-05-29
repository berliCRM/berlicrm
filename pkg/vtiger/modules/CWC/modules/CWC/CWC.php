<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by crm-now are Copyright (C) crm-now GmbH.
 * All Rights Reserved.
 *************************************************************************************/
require_once('include/events/include.inc');

class CWC {

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		require_once('include/utils/utils.php');			
		if($event_type == 'module.postinstall') {
			$this->initCustomWebserviceOperations();
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
			return;
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
			return;
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
			return;		
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
			return;			
		} else if($event_type == 'module.postupdate') {
			$this->initCustomWebserviceOperations();
		}
	}
	
	function initCustomWebserviceOperations() {
		$operations = array();

		$cwc_campaign_parameters    = array('campaignid' => 'string','returnresults' => 'string');
		$operations['get_campaign_entities'] = array(
			'file' => 'modules/CWC/getCampaignEntities.php', 'handler' => 'vtws_get_campaign_entities', 'reqtype' => 'GET', 'prelogin' => '0',
			'parameters' => $cwc_campaign_parameters );

		$cwc_doc_parameters    = array('docid' => 'string', 'relids' => 'string', 'preserve' => 'Boolean');
		$operations['update_document_relations'] = array(
			'file' => 'modules/CWC/UpdateDocRel.php', 'handler' => 'vtws_update_document_relations', 'reqtype' => 'POST', 'prelogin' => '0',
			'parameters' => $cwc_doc_parameters );

		$cwc_doc_parameters2   = array('docids' => 'string');
		$operations['get_document_relations'] = array(
			'file' => 'modules/CWC/getDocRel.php', 'handler' => 'vtws_get_document_relations', 'reqtype' => 'GET', 'prelogin' => '0',
			'parameters' => $cwc_doc_parameters2 );

		$this->registerCustomWebservices( $operations );
	}
	
	function registerCustomWebservices( $operations ) {
		global $adb;

		foreach($operations as $operation_name => $operation_info) {	
			$checkres = $adb->pquery("SELECT operationid FROM vtiger_ws_operation WHERE name=?", array($operation_name));
			if($checkres && $adb->num_rows($checkres) < 1) {
				$operation_id = $adb->getUniqueId('vtiger_ws_operation');
			
				$operation_res = $adb->pquery(
					"INSERT INTO vtiger_ws_operation (operationid, name, handler_path, handler_method, type, prelogin) 
					VALUES (?,?,?,?,?,?)",
					array($operation_id, $operation_name, $operation_info['file'], $operation_info['handler'], 
						$operation_info['reqtype'], $operation_info['prelogin'])
				);

				$operation_parameters = $operation_info['parameters'];
				$parameter_index = 0;	
				foreach($operation_parameters as $parameter_name => $parameter_type) {
					$adb->pquery(
						"INSERT INTO vtiger_ws_operation_parameters (operationid, name, type, sequence) 
						VALUES(?,?,?,?)", array($operation_id, $parameter_name, $parameter_type, ($parameter_index+1))
					);
					++$parameter_index;
				}
				Vtiger_Utils::Log("Operation $operation_name enabled successfully.");
			} else {
				Vtiger_Utils::Log("Operation $operation_name already exists.");
			}
		}
	}

}
?>