<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Verteiler_ExcelExport_Action extends Vtiger_BasicAjax_Action {

	function preProcess(Vtiger_Request $request, $display=false) {
		return false;
	}

	function postProcess(Vtiger_Request $request) {
		return false;
	}
    
    function process(Vtiger_Request $request) {
            include_once("modules/Reports/ReportRun.php");
            $record = $request->get('record');
            
            $filter = array(
                    array("columns" => array (
                        array ( "columnname" => "vtiger_verteiler:verteilerid:Verteiler_VerteilerID:verteilerid:V",
                                "comparator" => "e",
                                "value" => $record,
                                "column_condition" => "")
                                )));

            $reportModel = Reports_Record_Model::getInstanceById(74); // hardwired report id
            $reportModel->set('advancedFilter', $filter);
            $reportModel->getReportXLS();

    }
}