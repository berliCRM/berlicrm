<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Campaigns_DetailView_Model extends Vtiger_DetailView_Model {

   	public function getDetailViewLinks($linkParams) {
		$currentUserModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$recordModel = $this->getRecord();
        $moduleModel = $this->getModule();
        $moduleName = $moduleModel->getName();

		$linkModelList = parent::getDetailViewLinks($linkParams);

        if(Users_Privileges_Model::isPermitted($moduleName, 'CreateView', $recordId)) {
			$duplicateLinkModel = array(
						'linktype' => 'DETAILVIEWBASIC',
						'linklabel' => 'LBL_DUPLICATE_WITH_CONTENT',
						'linkurl' => $recordModel->getDuplicateRecordUrl()."&copyRelated=true",
						'linkicon' => ''
				);
			$linkModelList['DETAILVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($duplicateLinkModel);
		}

        return $linkModelList;
    }
}