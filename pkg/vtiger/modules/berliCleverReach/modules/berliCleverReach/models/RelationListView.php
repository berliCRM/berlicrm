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

class berliCleverReach_RelationListView_Model extends Vtiger_RelationListView_Model {
	/**
	 * Function to get the links for related list
	 * @return <Array> List of action models <Vtiger_Link_Model>
	 */
	public function getLinks() {
		$relatedLinks = parent::getLinks();
		$relationModel = $this->getRelationModel();
		$relatedModuleName = $relationModel->getRelationModuleModel()->getName();

		if (array_key_exists($relatedModuleName, $relationModel->getEmailEnabledModulesInfoForDetailView())) {
			$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
			if ($currentUserPriviligesModel->hasModulePermission(getTabid('berliCleverReach'))) {
				$deleteLink = Vtiger_Link_Model::getInstanceFromValues(array(
						'linktype' => 'LISTVIEWBASIC',
						'linklabel' => vtranslate('LBL_DELETE', $relatedModuleName),
						'linkurl' => "",
						'linkicon' => ''
				));
				$deleteLink->set('_massDelete',true);
				$relatedLinks['LISTVIEWBASIC'][] = $deleteLink;
			}
		}
		return $relatedLinks;
	}


}
