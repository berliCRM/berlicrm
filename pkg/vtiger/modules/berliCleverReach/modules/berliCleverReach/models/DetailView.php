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

class berliCleverReach_DetailView_Model extends Vtiger_DetailView_Model {
	/**
	 * Function to get the detail view links (links and widgets)
	 * @param <array> $linkParams - parameters which will be used to calicaulate the params
	 * @return <array> - array of link models in the format as below
	 *                   array('linktype'=>list of link models);
	 */
	public function getDetailViewLinks($linkParams) {
		$currentUserModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$berliCleverReachModuleModel = Vtiger_Module_Model::getInstance('berliCleverReach');
		$recordModel = $this->getRecord();
		$linkModelList = parent::getDetailViewLinks($linkParams);

		// creates "sync" button on detail view
		$basicActionLink = array(
			'linktype' => 'DETAILVIEWBASIC',
			'linklabel' => 'LBL_START_SYNC_BTN',
			'linkurl' => 'javascript:berliCleverReachCommon.showlist('.$recordModel->getId().')',
			'linkicon' => ''
			);
		$linkModelList['DETAILVIEWBASIC'][] = Vtiger_Link_Model::getInstanceFromValues($basicActionLink);
		
		// creates "empty log" button in detail view
		$basicActionLink = array(
			'linktype' => 'DETAILVIEWBASIC',
			'linklabel' => 'LBL_EMPTY_LOG',
			'linkurl' => 'javascript:berliCleverReachCommon.emptyLog();',
			'linkicon' => ''
			);
		$linkModelList['DETAILVIEWBASIC'][] = Vtiger_Link_Model::getInstanceFromValues($basicActionLink);

		return $linkModelList;
	}

}
