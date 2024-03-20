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
require_once('modules/Mailchimp/providers/MailChimp.php');

class Mailchimp_showGroupOverlay_View extends Vtiger_Edit_View {

    public function sorthelp($a,$b) {
        return strcasecmp($a["name"], $b["name"]);
    }

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$MailChimpAPIKey = Mailchimp_Module_Model::getApikey();
		//lists
		$api = new MailChimp($MailChimpAPIKey);
		$total_items_arr = $api->get('lists',array("fields"=>'total_items'));
		$list_count = $total_items_arr['total_items'];
		$lists_from_api = $api->get('lists',array('count'=>$list_count,'offset'=>'0','fields'=>'lists.name,lists.id'));
        if (is_array($lists_from_api['lists'])) {
            foreach ($lists_from_api['lists'] as $key => $value) {
				$lists[] = array("name"=>$value['name'],"id"=>$value['id']);
			}
		}
		else {
			$lists = array();
		}

        uasort($lists, array($this,"sorthelp"));

		$viewer->assign('MODULE',$moduleName);
		$viewer->assign('APILISTE',$lists);
		$viewer->assign('ID', $record );
		$viewer->view('showGroupOverlay.tpl', $moduleName);
	}
}
?>