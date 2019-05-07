<?php
class Verteiler_showExportOptions_View extends Vtiger_View_Controller {
	public $log_text = array();
	
	function loginRequired() {
		return true;
	}

    public function __construct() {
	}
	
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());

		if(!$permission) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}
 
	function preProcess(Vtiger_Request $request, $display = true) {
	}


	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$module = $request->getModule();
		$moduleName = $request->get('modulename');
		$recordid = $request->get('recordid');
		
		$viewer = new Vtiger_Viewer();
		$active_destination_module = array ();
		
		if(vtlib_isModuleActive('Campaigns')) {
			$active_destination_module[] =	'Campaigns';
			//list of campaigns
			$query = "SELECT vtiger_campaign.campaignid, vtiger_campaign.campaignname from vtiger_campaign
						inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_campaign.campaignid
						where vtiger_crmentity.deleted=0 order by campaignname";
			$result = $db->pquery($query, array());
			$numCAlist = $db->num_rows($result);
			$list_campaigns = array();
			if ($numCAlist >0){
				for($i=0;$i < $db->num_rows($result);$i++) {
					$list_campaigns [$db->query_result($result,$i,'campaignid')] = $db->query_result($result,$i,'campaignname');
				}
			}
			$viewer->assign('CAMPAIGNLIST',$list_campaigns);
			$viewer->assign('NUMCALIST',$numCAlist);
		}
		if(vtlib_isModuleActive('berliCleverReach')) {
			$active_destination_module[] =	'berliCleverReach';
			//list of CleverReach groups
				$query = "SELECT vtiger_berlicleverreach.cleverreachid, vtiger_berlicleverreach.cleverreachname from vtiger_berlicleverreach
									inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_berlicleverreach.cleverreachid
									where vtiger_crmentity.deleted=0 order by cleverreachname";
				$result = $db->pquery($query, array());
				$numCRlist = $db->num_rows($result);
				$list_cleverreach = array();
				if ($numCRlist >0){
					for($i=0;$i < $db->num_rows($result);$i++) {
						$list_cleverreach [$db->query_result($result,$i,'cleverreachid')] = $db->query_result($result,$i,'cleverreachname');
					}
				}
			$viewer->assign('CLEVERREACHLIST',$list_cleverreach);
			$viewer->assign('NUMCRLIST',$numCRlist);
		}
		if(vtlib_isModuleActive('Mailchimp')) {
			$active_destination_module[] =	'Mailchimp';
			//list of Mailchimp groups
			$query = "SELECT vtiger_mailchimp.mailchimpid, vtiger_mailchimp.mailchimpname from vtiger_mailchimp
								inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_mailchimp.mailchimpid
								where vtiger_crmentity.deleted=0 order by mailchimpname";
			$result = $db->pquery($query, array());
			$numMClist = $db->num_rows($result);
			$list_mailchimp = array();
			if ($numMClist >0){
				for($i=0;$i < $db->num_rows($result);$i++) {
					$list_mailchimp [$db->query_result($result,$i,'mailchimpid')] = $db->query_result($result,$i,'mailchimpname');
				}
			}
			$viewer->assign('MAILCHIMPLIST',$list_mailchimp);
			$viewer->assign('NUMMCLIST',$numMClist);
		}
		if(empty($active_destination_module)) {
			$response = new Vtiger_Response();
			$response->setResult(json_encode('NODESTINATION'));
			$response->emit();
			return;
		}

		$viewer->assign('DESTINATION_MODULES',$active_destination_module);

		$viewer->assign('MODULE',$module);
		$viewer->assign('MODULENAME',$moduleName);
		$viewer->assign('RECORDID',$recordid);
		$viewer->view('showExportOptions.tpl', $module);
	}
}

?>