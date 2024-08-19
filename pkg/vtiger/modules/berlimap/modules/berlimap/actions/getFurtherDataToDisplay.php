<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class berlimap_getFurtherDataToDisplay_Action extends Vtiger_BasicAjax_Action {

    public function __construct() {
        parent::__construct();
        $this->exposeMethod('getDisplayData');
    }

    public function process(Vtiger_Request $request): void {
        $mode = $request->get('mode');
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }

    /**
     * Function to get display data.
     * @param Vtiger_Request $request
     * @return void
     */
    public function getDisplayData(Vtiger_Request $request): void {
        // Example data in html, replace with actual data retrieval logic
        // Example data: '<br>value 1<br>value 2'
		$providedData ='';

		$result = array('success'=>true, 'result'=>$providedData);
		echo json_encode($result);
    }
}
