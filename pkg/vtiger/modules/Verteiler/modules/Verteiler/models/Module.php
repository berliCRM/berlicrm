<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Verteiler_Module_Model extends Vtiger_Module_Model {
    
    public function getRelationQuery($recordId, $functionName, $relatedModule) {
		$relatedModuleName = $relatedModule->getName();

		$focus = CRMEntity::getInstance($this->getName());
		$focus->id = $recordId;

		$result = $focus->$functionName($recordId, $this->getId(), $relatedModule->getId());
		$query = $result['query'] .' '. $this->getSpecificRelationQuery($relatedModuleName);
		$nonAdminQuery = $this->getNonAdminAccessControlQueryForRelation($relatedModuleName);

		//modify query if any module has summary fields, those fields we are displayed in related list of that module
		$relatedListFields = $relatedModule->getConfigureRelatedListFields();
		
        if(count($relatedListFields) > 0) {
			$currentUser = Users_Record_Model::getCurrentUserModel();
			$queryGenerator = new QueryGenerator($relatedModuleName, $currentUser);
			$queryGenerator->setFields($relatedListFields);
			$selectColumnSql = $queryGenerator->getSelectClauseColumnSQL();
			$newQuery = preg_split("/from/i", $query);
			$newQuery[0] = "SELECT vtiger_crmentity.crmid, $selectColumnSql, users2.user_name as added_by_user_name, users2.id as added_by_user_id, vtiger_verteilercontrel.parent, vtiger_account.accountname ";
			$query = implode("FROM", $newQuery);
		}

		if ($nonAdminQuery) {
			$query = appendFromClauseToQuery($query, $nonAdminQuery);
		}
    
		return $query;
	}
}