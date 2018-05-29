<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 * 27.7.bb
 * 31.7.	api helper class
 *************************************************************************************/
require_once('modules/berliCleverReach/providers/cleverreach.php');

class berliCleverReach_showGroupOverlay_View extends Vtiger_Edit_View {

	public function process(Vtiger_Request $request) {
		
		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		$CR = new cleverreachAPI();
		
		try {
			$rest = $CR->getrest();
			// lade Gruppen von Cleverreach
			$groups = $rest->get("/groups");

			if (is_array($groups)) {
				foreach ($groups as $group)	{
					$lists[] = array("name"=>$group->name,"id"=>$group->id);
				}
			}
			
			$viewer->assign('MODULE',$moduleName);
			$viewer->assign('APILISTE',$lists);
			$viewer->assign('ID', $record );
			
		} catch (Exception $e) {
			list($code,$msg) = explode (";", $e->getMessage());
			if ($code==401) $errorcode= "noauth";
			if ($code==0) $errorcode= "timeout";
			$viewer->assign('MODULE',$moduleName);
			$viewer->assign('ERRORCODE',$errorcode);
			$viewer->assign('ID', $record );
		}

		$viewer->view('showGroupOverlay.tpl', $moduleName);
	}
}
?>