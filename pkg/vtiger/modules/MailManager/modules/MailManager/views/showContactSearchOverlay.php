<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 *************************************************************************************/
include_once 'config.php';
require_once 'include/utils/utils.php';
include_once 'include/Webservices/Query.php';
require_once 'includes/runtime/Cache.php';
include_once 'include/Webservices/DescribeObject.php';
require_once 'modules/Vtiger/helpers/Util.php';
include_once 'modules/Settings/MailConverter/handlers/MailScannerAction.php';
include_once 'modules/Settings/MailConverter/handlers/MailAttachmentMIME.php';

class MailManager_showContactSearchOverlay_View extends Vtiger_Edit_View {

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$viewer = $this->getViewer ($request);
		//get all contacts
		$query = "select contactid, contact_no, firstname, lastname, email from vtiger_contactdetails inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_contactdetails.contactid
			 where  vtiger_crmentity.deleted = 0 ORDER BY lastname ASC";
		$res = $db->pquery($query,array());
		$contacts = array();
		while ($row=$db->getNextRow($res, false)) {
			$contacts[$row['contactid']]['lastname'] = $row['lastname'];
			$contacts[$row['contactid']]['firstname'] = $row['firstname'];
			$contacts[$row['contactid']]['email'] = $row['email'];
			$contacts[$row['contactid']]['contact_no'] = $row['contact_no'];
		}
		$viewer->assign('MSGNO', $request->get('_msgno'));
		$viewer->assign('FOLDER', $request->get('_folder'));
		$viewer->assign("CONTACTS", $contacts);
		$viewer->assign('MODULE',$moduleName);
		$viewer->assign('APILISTE',$lists);
		$viewer->assign('ID', $record );
		$viewer->view('sendMailToContact.tpl', 'MailManager');
	}
}
?>