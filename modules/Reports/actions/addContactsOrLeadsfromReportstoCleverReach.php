<?php
class Reports_addContactsOrLeadsfromReportstoCleverReach_Action extends Vtiger_Action_Controller {

	function checkPermission(Vtiger_Request $request) {
		return;
	}

	public function process(Vtiger_Request $request) {
		global $adb;
		$modulename = $request->get('modulename');
		$reportid = $request->get('reportid');
		$cleverreachlist = $request->get('cleverreachid');
		$contactidlist = $request->get('contactids');
		$contactidlist_array = array ();
	
		$response = new Vtiger_Response();
		
		if (!is_numeric($reportid) or !is_numeric($cleverreachlist)) {
			$result = 'FAILURE';
		}
		else {
			//get the contacts from report
			$contactidlist_array = explode('|', $contactidlist);
			//clean up
			$contactidlist_array = array_diff($contactidlist_array, array(''));
			//add the contacts to Clever Reach
			if (count($contactidlist_array)!=0) {
				$count_quantity = 0;
				foreach ($contactidlist_array as $key=>$value)  {
					if ($modulename == 'Contacts') {
						//check whether this contact is still not deleted		
						$query = "select contactid from vtiger_contactdetails inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_contactdetails.contactid where vtiger_crmentity.deleted =0 and contactid =?";
						$result = $adb->pquery($query, array($value));
						if($adb->num_rows($result)>0){
							//check for duplicates
							$dup_query = "Select crmid from vtiger_crmentityrel where crmid =? and module='berliCleverReach' and relcrmid =? and relmodule='Contacts' ";
							$dup_result = $adb->pquery($dup_query, array($cleverreachlist, $value));
							$num_rows = $adb->num_rows($dup_result);
							if($num_rows == 0){
								$count_quantity = $count_quantity +1;
							}
							else {
								//we need to make a clean up since the earlier version did not make a duplicate check
								$del_query = "DELETE FROM vtiger_crmentityrel WHERE crmid = ? AND module = 'berliCleverReach' AND relcrmid = ? AND relmodule = 'Contacts'";
								$adb->pquery($del_query, array($cleverreachlist, $value));
							}
							//add to  Clever Reach
							$query1 = "INSERT INTO vtiger_crmentityrel (crmid ,module ,relcrmid ,relmodule) VALUES (?, 'Contacts', ?, 'berliCleverReach')";
							$adb->pquery($query1, array($value,$cleverreachlist));
						}
					}
					else {
						//Leads
						//check whether this lead is still not deleted		
						$query = "select leadid from vtiger_leaddetails inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_leaddetails.leadid where vtiger_crmentity.deleted =0 and leadid =?";
						$result = $adb->pquery($query, array($value));
						if($adb->num_rows($result)>0){
							//check for duplicates
							$dup_query = "Select crmid from vtiger_crmentityrel where crmid =? and module='berliCleverReach' and relcrmid =? and relmodule='Leads' ";
							$dup_result = $adb->pquery($dup_query, array($cleverreachlist, $value));
							$num_rows = $adb->num_rows($dup_result);
							if($num_rows == 0){
								$count_quantity = $count_quantity +1;
							}
							else {
								//we need to make a clean up since the earlier version did not make a duplicate check
								$del_query = "DELETE FROM vtiger_crmentityrel WHERE crmid = ? AND module = 'berliCleverReach' AND relcrmid = ? AND relmodule = 'Leads'";
								$adb->pquery($del_query, array($cleverreachlist, $value));
							}
							//add to campaign
							$query1 = "INSERT INTO vtiger_crmentityrel (crmid ,module ,relcrmid ,relmodule) VALUES (?, 'Leads', ?, 'berliCleverReach')";
							$adb->pquery($query1, array($value,$cleverreachlist));
						}
					}
				}
				if ($modulename == 'Contacts') {
					if ($count_quantity < count($contactidlist_array)) {
						$result = vtranslate('LBL_CR_SUCCESS_CONTACT_LESS','Reports')." ".$count_quantity." ".vtranslate('LBL_CR_SUCCESS_CONTACT_LESS1','Reports');
					}
					else {
						$result = vtranslate('LBL_CR_SUCCESS_CONTACT','Reports');
					}
				}
				else {
					if ($count_quantity < count($contactidlist_array)) {
						$result = vtranslate('LBL_CR_SUCCESS_LEAD','Reports')." ".$count_quantity." ".vtranslate('LBL_CR_SUCCESS_LEAD_LESS1','Reports');
					}
					else {
						$result = vtranslate('LBL_CR_SUCCESS_LEAD','Reports');
					}
				}
			}
			else {
				$result = vtranslate('LBL_NO_CONTACTS','Reports');
			}
		}
		$response->setResult($result);
		$response->emit();
	}
}

?>