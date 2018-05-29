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
        $batchsize=100;
        $offset=0;
        $lists = array();
        do {
            $APILists = $api->get('lists',array("count"=>$batchsize,"offset"=>$offset));
            if (is_array($APILists['lists'])) {
                foreach ($APILists['lists'] as $key => $value) {
                    $lists[] = array("name"=>$value['name'],"id"=>$value['id']);
                }
            }
            $offset +=$batchsize;
        } while ($APILists["total_items"]>$offset);

        uasort($lists, array($this,"sorthelp"));

		$viewer->assign('MODULE',$moduleName);
		$viewer->assign('APILISTE',$lists);
		$viewer->assign('ID', $record );
		$viewer->view('showGroupOverlay.tpl', $moduleName);
	}
}
?>