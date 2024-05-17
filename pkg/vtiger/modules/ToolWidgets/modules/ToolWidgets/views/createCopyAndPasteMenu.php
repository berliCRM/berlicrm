<?php
class ToolWidgets_createCopyAndPasteMenu_View extends Vtiger_Edit_View {
	function checkPermission(Vtiger_Request $request) {
		return;
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$source_module = strval($request->get('sourcemodule'));
		$recordid = $request->get('recordid');

		if ($source_module === 'Contacts') {
			$Contacts_focus = Vtiger_DetailView_Model::getInstance('Contacts', $recordid);
			$Contacts_focus = $Contacts_focus ->getRecord();

			$arrFields = array("salutationtype" => "salutationtype",
								"firstname" => "firstname",
								"lastname" => "lastname",
								"department" => "department",
								"phone" => "phone",
								"email" => "email",
								"title" => "title", 				
								"mailingstreet" => "mailingstreet",
								"mailingzip" => "mailingzip", 
								"mailingcity" => "mailingcity",
								"mailingcountry" => "mailingcountry" 
								//"anschrift" => "cf_1113"
							);

			$info = array ();
			$info['accountname'] = getAccountName($Contacts_focus->get('account_id'));
			foreach($arrFields AS $fieldName => $colName) {
				$info[$fieldName] = $Contacts_focus->get($colName);
			}
			// init variable
			$copypastestring = "";
			
			$arr2 = array('salutationtype', 'firstname', 'lastname', 'department', 'phone', 'email', 'title', 'mailingstreet', 'mailingzip', 'mailingcity', 'mailingcountry');
			foreach($arr2 AS $colName) {
				$separator = ($colName == 'firstname' || $colName == 'mailingzip') ?" " :"\n" ;
				if (!empty($info[$colName])) {
					$copypastestring.= $info[$colName].$separator;
				}
			}
		}
		else if ($source_module === 'Accounts') {
			$Accounts_focus = Vtiger_DetailView_Model::getInstance('Accounts', $recordid);
			$Accounts_focus = $Accounts_focus ->getRecord();

			$arrFields = array("accountname" => "accountname",
								"phone" => "phone",
								"website" => "website",
								"email" => "email1",
								"title" => "title",  
								"bill_street" => "bill_street",
								"bill_code" => "bill_code",
								"bill_city" => "bill_city",
								"bill_state" => "bill_state",
								"bill_country" => "bill_country"
							);
							
			$info = array ();
			$info['accountname'] = getAccountName($Accounts_focus->get('account_id'));
			foreach($arrFields AS $fieldName => $colName) {
				$info[$fieldName] = $Accounts_focus->get($colName);
			}
			// init variable
			$copypastestring = "";

			$arr2 = array('accountname', 'phone', 'website', 'email', 'bill_street', 'bill_code', 'bill_city', 'bill_country');
			foreach($arr2 AS $colName) {
				$separator = ($colName == 'bill_code') ?" " :"\n" ;
				if (!empty($info[$colName])) {
					$copypastestring.= $info[$colName].$separator;
				}
			}
		}
		$Index_View_Obj = new Vtiger_Index_View();
		$viewer = $Index_View_Obj->getViewer($request);
		$viewer->assign('COPYPASTESTRING', $copypastestring);
		$viewer->assign('SOURCEMODULE', $recordid);
        $viewer->assign('RECORD', $record);
        $viewer->assign('MODULE', $moduleName);
        $viewer->view('createCopyPasteDataMenue.tpl', 'ToolWidgets');
	}
}
?>