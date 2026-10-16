<?php
/************************************************************************************************************************************************************ 
Description: update multiple elements with one WS call, optional to disable EventHandlers // Workflows
  Parameters:
    - encoded elements: array containing WS elements
	- boolean skipHandlers: trigger to disable EventHandlers and Workflows
  Returns:
    - array('SUCCESS' => array($id => $element, ...), 'FAILED' => array($id//$key => $errorMsg))
  Comments:
    - this function respects the vtiger CRM profile privilege system and returns only entities accessible by the user accessing the REST functionality
Copyright (C) crm-now GmbH
All Rights Reserved.
************************************************************************************************************************************************************ */
function berli_bulkUpdate($elements, $skipHandlers, $user) {
	require_once('include/Webservices/Update.php');
	if (!is_array($elements)) {
		throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'No array provided');
	}
	
	if ($skipHandlers === true || $skipHandlers == 'true' || $skipHandlers == 1) {
		global $BERLI_SKIP_EH;
		$BERLI_SKIP_EH = true;
	}
	
	$arrRet = array();
	
	foreach ($elements AS $key => $element) {
		try {
			$updated = vtws_update($element, $user);
			$arrRet['SUCCESS'][$updated['id']] = $updated;
		} catch (WebServiceException $e) {
			$error = $e->getMessage();
			if (!empty($element['id'])) {
				$key = $element['id'];
			}
			$arrRet['FAILED'][$key] = $error;
		}
		set_time_limit(0);
	}
	return $arrRet;
}