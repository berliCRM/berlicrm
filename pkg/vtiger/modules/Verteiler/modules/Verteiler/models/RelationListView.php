<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Verteiler_RelationListView_Model extends Vtiger_RelationListView_Model {

    // fields to filter related list view by
    public $relsearchfields = array(   "vtiger_contactdetails.salutation", 
                                        "vtiger_contactdetails.firstname",
                                        "vtiger_contactdetails.lastname",
                                        "vtiger_contactaddress.mailingcity",
                                        "vtiger_contactaddress.mailingcountry",
                                        "vtiger_account.accountname",
                                        "vtiger_users.first_name",
                                        "vtiger_users.last_name",
                                        "vtiger_groups.groupname",
                                        "vtiger_verteilercontrel.parent",
                                        "users2.user_name");
	/**
	* Function to get Total number of record in this relation (by string-transforming RelationQuery)
	* @return <Integer>
	*/
	public function getRelatedEntriesCount() {
		$db = PearDatabase::getInstance();
		$relationQuery = $this->getRelationQuery();
		$relationQuery = preg_replace("/[ \t\n\r]+/", " ", $relationQuery);
        $pos = stripos($relationQuery,' FROM '); // replace "SELECT col1,col2,..." by "SELECT COUNT.."
		if ($pos !== false) {
			$relationQuery = 'SELECT COUNT(vtiger_crmentity.crmid) AS count' . substr($relationQuery,$pos); 
		}
        $pos = stripos($relationQuery,' GROUP BY '); // remove any GROUPing
        if ($pos !== false) {
            $relationQuery = substr($relationQuery,0,$pos);
        }
        
        // apply filter for related list
        if (!empty($_REQUEST["filter"])) {
            $filter = $db->sql_escape_string($_REQUEST["filter"]);
            foreach ($this->relsearchfields as $f) {
                $cond[] = "$f LIKE '%$filter%'";
            }
            $relationQuery .= " AND (". implode(" OR ",$cond).") ";
        }
        
		$result = $db->pquery($relationQuery, array());
		return $db->query_result($result, 0, 'count');
	}

	/**
	* Function to get related records
	*/    
    public function getEntries($pagingModel) {
		$db = PearDatabase::getInstance();
		$parentModule = $this->getParentRecordModel()->getModule();
		$relationModule = $this->getRelationModel()->getRelationModuleModel();
		$relationModuleName = $relationModule->get('name');
		$relatedColumnFields = $relationModule->getConfigureRelatedListFields();
		if(count($relatedColumnFields) <= 0){
			$relatedColumnFields = $relationModule->getRelatedListFields();
		}
        
		$query = $this->getRelationQuery();

		if ($this->get('whereCondition')) {
			$query = $this->updateQueryWithWhereCondition($query);
		}

		// $startIndex = $pagingModel->getStartIndex();
		// $pageLimit = $pagingModel->getPageLimit();

		$orderBy = $this->getForSql('orderby');
		$sortOrder = $this->getForSql('sortorder');

        if (!$orderBy & PerformancePrefs::getBoolean('LISTVIEW_DEFAULT_SORTING', true)) {
                $entityModule=CRMEntity::getInstance($relationModule->name);
                $orderBy=$entityModule->default_order_by;
                $sortOrder=$entityModule->default_sort_order;
        }

        // apply filter for related list
        if (!empty($_REQUEST["filter"])) {
            $filter = $db->sql_escape_string($_REQUEST["filter"]);
            foreach ($this->relsearchfields as $f) {
                $cond[] = "$f LIKE '%$filter%'";
            }
            $query .= " AND (". implode(" OR ",$cond).") ";
        }

		if($orderBy) {

            $orderByFieldModuleModel = $relationModule->getFieldByColumn($orderBy);
            if($orderByFieldModuleModel && $orderByFieldModuleModel->isReferenceField()) {
                //If reference field then we need to perform a join with crmentity with the related to field
                $pos = stripos($query,' where ');
                $selectAndFromClause = substr($query,0,$pos);
                $whereCondition = substr($query,$pos);
                $qualifiedOrderBy = 'vtiger_crmentity'.$orderByFieldModuleModel->get('column');
                $selectAndFromClause .= ' LEFT JOIN vtiger_crmentity AS '.$qualifiedOrderBy.' ON '.
                                        $orderByFieldModuleModel->get('table').'.'.$orderByFieldModuleModel->get('column').' = '.
                                        $qualifiedOrderBy.'.crmid ';
                $query = $selectAndFromClause.$whereCondition;
                $query .= ' ORDER BY '.$qualifiedOrderBy.'.label '.$sortOrder;
            } elseif($orderByFieldModuleModel && $orderByFieldModuleModel->isOwnerField()) {
				 $query .= ' ORDER BY COALESCE(CONCAT(vtiger_users.first_name,vtiger_users.last_name),vtiger_groups.groupname) '.$sortOrder;
			} else{
                // Qualify the the column name with table to remove ambugity
                $qualifiedOrderBy = $orderBy;
                $orderByField = $relationModule->getFieldByColumn($orderBy);
                if ($orderByField) {
					$qualifiedOrderBy = $relationModule->getOrderBySql($qualifiedOrderBy);
				}
                $query = "$query ORDER BY $qualifiedOrderBy $sortOrder";
				}
		}

		$limitQuery = $query ;//.' LIMIT '.$startIndex.','.$pageLimit;

		$result = $db->pquery($limitQuery, array());
		$relatedRecordList = array();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$groupsIds = Vtiger_Util_Helper::getGroupsIdsForUsers($currentUser->getId());
		for($i=0; $i< $db->num_rows($result); $i++ ) {
			$row = $db->fetch_row($result,$i);
            $recordId = $db->query_result($result,$i,'crmid');
			$newRow = array();
			foreach($row as $col=>$val){
				if(array_key_exists($col,$relatedColumnFields)){
                   $newRow[$relatedColumnFields[$col]] = $val;
                }
            }
			//To show the value of "Assigned to"
			$newRow['assigned_user_id'] = $row['smownerid'];
			
			$record = Vtiger_Record_Model::getCleanInstance($relationModule->get('name'));
            $record->setData($newRow)->setModuleFromInstance($relationModule);
            $record->setId($row['crmid']);
            
            $record->set("added_by_user_name",$row['added_by_user_name']);
            $record->set("added_by_user_id",$row['added_by_user_id']);
            $record->set("parent",$row['parent']);
            
            $relatedRecordList[] = $record;
		}
		// $pagingModel->calculatePageRange($relatedRecordList);

		// $nextLimitQuery = $query. ' LIMIT '.($startIndex+$pageLimit).' , 1';
		// $nextPageLimitResult = $db->pquery($nextLimitQuery, array());
		// if($db->num_rows($nextPageLimitResult) > 0){
			// $pagingModel->set('nextPageExists', true);
		// }else{
			// $pagingModel->set('nextPageExists', false);
		// }
		return $relatedRecordList;
	}
    
}