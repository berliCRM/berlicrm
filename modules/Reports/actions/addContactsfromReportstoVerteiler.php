<?php
class Reports_addContactsfromReportstoVerteiler_Action extends Vtiger_Action_Controller {

	function checkPermission(Vtiger_Request $request) {
		return;
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$current_user = Users_Record_Model::getCurrentUserModel();
		$modulename = $request->get('modulename');
		$reportid = $request->get('reportid');
		$verteilerlistid = $request->get('verteilerid');
		$contactidlist = $request->get('contactids');
		$contactidlist_array = array ();
	
		$response = new Vtiger_Response();
		
		if (!is_numeric($reportid) or !is_numeric($verteilerlistid)) {
			$result = 'FAILURE';
		}
		else {
			//get the contacts from report
			$contactidlist_array = explode('|', $contactidlist);
			//clean up
			$contactidlist_array = array_diff($contactidlist_array, array(''));
			//add the contacts to Verteiler
			if (count($contactidlist_array)!=0) {
				$count_quantity = 0;
				foreach ($contactidlist_array as $key=>$value)  {
					//check whether this contact is still not deleted		
					$query = "select contactid from vtiger_contactdetails inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_contactdetails.contactid where vtiger_crmentity.deleted =0 and contactid =?";
					$result = $db->pquery($query, array($value));
					if($db->num_rows($result)>0){
						//check for duplicates
						$dup_query = "Select `contactid` from `vtiger_verteilercontrel` where `verteilerid`=? and `contactid`=? ";
						$dup_result = $db->pquery($dup_query, array($verteilerlistid, $value));
						if($db->num_rows($dup_result) == 0){
							//add to Verteiler
							$query1 = "INSERT INTO `vtiger_verteilercontrel` (`verteilerid` ,`contactid` ,`addedbyuserid`) VALUES (?, ?, ?)";
							$result = $db->pquery($query1, array($verteilerlistid, $value, $current_user->id));
							$count_quantity = $count_quantity +1;
						}
					}
				}
				if ($count_quantity < count($contactidlist_array)) {
					$result = vtranslate('LBL_VE_SUCCESS_CONTACT_LESS','Reports')." ".$count_quantity." ".vtranslate('LBL_VE_SUCCESS_CONTACT_LESS1','Reports');
				}
				else {
					$result = vtranslate('LBL_VE_SUCCESS_CONTACT','Reports');
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