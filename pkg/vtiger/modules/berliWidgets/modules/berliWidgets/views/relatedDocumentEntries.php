<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class berliWidgets_relatedDocumentEntries_View extends Vtiger_Detail_View {

    /**
     * must be overriden
     * @param Vtiger_Request $request
     * @return boolean 
     */
    public function preProcess(Vtiger_Request $request, $display= true) {
        return true;
    }

    /**
     * must be overriden
     * @param Vtiger_Request $request
     * @return boolean 
     */
    public function postProcess(Vtiger_Request $request) {
        return true;
    }

    /**
     * called when the request is received.
     * if view type : detail then show related CRM entries
     * @param Vtiger_Request $request 
     */
    public function process(Vtiger_Request $request) {
        switch ($request->get('viewtype')) {
            case 'detail':$this->showRelatedEntries($request);
                break;
            default:break;
        }
    }

    /**
     * display the template.
     * @param Vtiger_Request $request 
     */
    public function showRelatedEntries(Vtiger_Request $request) {
		//document number
		$parentRecordId = $request->get('record');
		$moduleName = $request->getModule();
		$relatedEntriesObj = array ();
		$relatedEntries = self::getRelatedEntries($parentRecordId, $pagingModel);
		foreach ($relatedEntries as $key => $relatedrecord) {
			foreach ($relatedrecord as $relModuleName => $recordid) {
				$moduleInstance  = Vtiger_Module::getInstance($relModuleName);
				if ($moduleInstance) {
					$relatedEntriesObj[] = Vtiger_Record_Model::getInstanceById($recordid, $relModuleName);
				}
			}
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('RELATED_ENTRIES', $relatedEntriesObj);
		$viewer->assign('PAGING', $pagingModel);
        $viewer->assign('RECORD', $parentRecordId);
        $viewer->assign('MODULE', $moduleName);
        $viewer->view('showRelatedDocumentEntries.tpl', 'berliWidgets');
    }
	static function getRelatedEntries($documentid,$pagingModel=null) {
		$db = PearDatabase::getInstance();
		$entryDetails = array();
		$result = $db->pquery("SELECT  vtiger_crmentity.setype as modulename, vtiger_senotesrel.crmid as relatedentryid FROM vtiger_senotesrel inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_senotesrel.crmid  WHERE vtiger_crmentity.deleted = 0 and  vtiger_senotesrel.notesid  = ?", array($documentid));
		$num = $db->num_rows($result);
		if($num >0) {
			for($i=0; $i<$num; $i++){
				$modulename = $db->query_result($result,$i,'modulename');
				$relatedentryid = $db->query_result($result,$i,'relatedentryid');
				$entryDetails[$i][$modulename] = $relatedentryid;
			}
		}
		return $entryDetails;
	}

}

?>
