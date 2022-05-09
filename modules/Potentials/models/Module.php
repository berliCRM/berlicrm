<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ************************************************************************************/

class Potentials_Module_Model extends Vtiger_Module_Model {

	/**
	 * Function to get the Quick Links for the module
	 * @param <Array> $linkParams
	 * @return <Array> List of Vtiger_Link_Model instances
	 */
	public function getSideBarLinks($linkParams) {
		$parentQuickLinks = parent::getSideBarLinks($linkParams);

		$quickLink = array(
			'linktype' => 'SIDEBARLINK',
			'linklabel' => 'LBL_DASHBOARD',
			'linkurl' => $this->getDashBoardUrl(),
			'linkicon' => '',
		);
		
		//Check profile permissions for Dashboards
		$moduleModel = Vtiger_Module_Model::getInstance('Dashboard');
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());
		if($permission) {
			$parentQuickLinks['SIDEBARLINK'][] = Vtiger_Link_Model::getInstanceFromValues($quickLink);
		}
		
		return $parentQuickLinks;
	}

	/**
	 * Function returns number of Open Potentials in each of the sales stage
	 * @param <Integer> $owner - userid
	 * @return <Array>
	 */
	public function getPotentialsCountBySalesStage($owner, $dateFilter) {
		$db = PearDatabase::getInstance();

		if (!$owner) {
			$currenUserModel = Users_Record_Model::getCurrentUserModel();
			$owner = $currenUserModel->getId();
		} else if ($owner === 'all') {
			$owner = '';
		}

		$params = array();
		if(!empty($owner)) {
			$ownerSql =  ' AND smownerid = ? ';
			$params[] = $owner;
		}
		if(!empty($dateFilter)) {
			$dateFilterSql = ' AND closingdate BETWEEN ? AND ? ';
			$params[] = $dateFilter['start'];
			$params[] = $dateFilter['end'];
		}

		$result = $db->pquery('SELECT COUNT(*) count, sales_stage FROM vtiger_potential
						INNER JOIN vtiger_crmentity ON vtiger_potential.potentialid = vtiger_crmentity.crmid
						AND deleted = 0 '.Users_Privileges_Model::getNonAdminAccessControlQuery($this->getName()). $ownerSql . $dateFilterSql . ' AND sales_stage NOT IN ("Closed Won", "Closed Lost")
							GROUP BY sales_stage ORDER BY count desc', $params);
		
		$response = array();
		for($i=0; $i<$db->num_rows($result); $i++) {
			$saleStage = $db->query_result($result, $i, 'sales_stage');
			$response[$i][0] = $saleStage;
			$response[$i][1] = $db->query_result($result, $i, 'count');
			$response[$i][2] = vtranslate($saleStage, $this->getName());
		}
		return $response;
	}

	/**
	 * Function returns number of Open Potentials for each of the sales person
	 * @param <Integer> $owner - userid
	 * @return <Array>
	 */
	public function getPotentialsCountBySalesPerson() {
		$db = PearDatabase::getInstance();
		//TODO need to handle security
		$params = array();
		$result = $db->pquery('SELECT COUNT(*) AS count, concat(first_name," ",last_name) as last_name, vtiger_potential.sales_stage FROM vtiger_potential
						INNER JOIN vtiger_crmentity ON vtiger_potential.potentialid = vtiger_crmentity.crmid
						INNER JOIN vtiger_users ON vtiger_users.id=vtiger_crmentity.smownerid AND vtiger_users.status="ACTIVE"
						AND vtiger_crmentity.deleted = 0'.Users_Privileges_Model::getNonAdminAccessControlQuery($this->getName()).'
						INNER JOIN vtiger_sales_stage ON vtiger_potential.sales_stage =  vtiger_sales_stage.sales_stage 
						GROUP BY smownerid, sales_stage ORDER BY vtiger_sales_stage.sortorderid', $params);

		$response = array();
		for($i=0; $i<$db->num_rows($result); $i++) {
			$row = $db->query_result_rowdata($result, $i);
			$response[$i]['count'] = $row['count'];
			$response[$i]['last_name'] = decode_html($row['last_name']);
			$response[$i]['sales_stage'] = $row['sales_stage'];
			//$response[$i][2] = $row['']
 		}
		return $response;
	}

	/**
	 * Function returns Potentials Amount for each Sales Person
	 * @return <Array>
	 */
	function getPotentialsPipelinedAmountPerSalesPerson() {
		$db = PearDatabase::getInstance();
		//TODO need to handle security
		$params = array();
		$result = $db->pquery('SELECT sum(amount) AS amount, concat(first_name," ",last_name) as last_name, vtiger_potential.sales_stage FROM vtiger_potential
						INNER JOIN vtiger_crmentity ON vtiger_potential.potentialid = vtiger_crmentity.crmid
						INNER JOIN vtiger_users ON vtiger_users.id=vtiger_crmentity.smownerid AND vtiger_users.status="ACTIVE"
						AND vtiger_crmentity.deleted = 0 '.Users_Privileges_Model::getNonAdminAccessControlQuery($this->getName()).
						'INNER JOIN vtiger_sales_stage ON vtiger_potential.sales_stage =  vtiger_sales_stage.sales_stage 
						WHERE vtiger_potential.sales_stage NOT IN ("Closed Won", "Closed Lost")
						GROUP BY smownerid, sales_stage ORDER BY vtiger_sales_stage.sortorderid', $params);
		for($i=0; $i<$db->num_rows($result); $i++) {
			$row = $db->query_result_rowdata($result, $i);
                        $row['last_name'] = decode_html($row['last_name']);
			$data[] = $row;
		}
		return $data;
	}

	/**
	 * Function returns Total Revenue for each Sales Person
	 * @return <Array>
	 */
	function getTotalRevenuePerSalesPerson($dateFilter) {
		$db = PearDatabase::getInstance();
		//TODO need to handle security
		$params = array();
		$params[] = 'Closed Won';
		
		$dateFilterSql ='';
		if(!empty($dateFilter)) {
			$dateFilterSql = ' AND createdtime BETWEEN ? AND ? ';
			//client is not giving time frame so we are appending it
			$params[] = $dateFilter['start']. ' 00:00:00';
			$params[] = $dateFilter['end']. ' 23:59:59';
		}
		
		$result = $db->pquery('SELECT sum(amount) amount, concat(first_name," ",last_name) as last_name,vtiger_users.id as id,DATE_FORMAT(closingdate, "%d-%m-%Y") AS closingdate  FROM vtiger_potential
						INNER JOIN vtiger_crmentity ON vtiger_potential.potentialid = vtiger_crmentity.crmid
						INNER JOIN vtiger_users ON vtiger_users.id=vtiger_crmentity.smownerid AND vtiger_users.status="ACTIVE"
						AND vtiger_crmentity.deleted = 0 '.Users_Privileges_Model::getNonAdminAccessControlQuery($this->getName()).'WHERE sales_stage = ? '.' '.$dateFilterSql.' GROUP BY smownerid', $params);
		$data = array();
		for($i=0; $i<$db->num_rows($result); $i++) {
			$row = $db->query_result_rowdata($result, $i);
                        $row['last_name'] = decode_html($row['last_name']);
			$data[] = $row;
		}
		return $data;
	}

	 /**
	 * Function returns Top Potentials Header
	 * 
	 */
    
    function getTopPotentialsHeader() {
        
         $headerArray = array('potentialname' => 'Potential Name');
         $fieldsToDisplay=  array("amount","related_to"); 
         $moduleModel = Vtiger_Module_Model::getInstance('Potentials');
          foreach ($fieldsToDisplay as $value) {
                     $fieldInstance = Vtiger_Field_Model::getInstance($value,$moduleModel); 
                          if($fieldInstance->isViewable()){
                                $headerArray = array_merge($headerArray,array($value =>$fieldInstance->label));
                               }
           }
        return $headerArray;
    }
    
	/**
	 * Function returns Top Potentials
	 * @return <Array of Vtiger_Record_Model>
	 */
	function getTopPotentials($pagingModel) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$db = PearDatabase::getInstance();
   
        $moduleModel = Vtiger_Module_Model::getInstance('Potentials');
        $fieldsToDisplay=  array("amount","related_to");
         
        $query = "SELECT crmid , potentialname " ; 
        foreach ($fieldsToDisplay as $value) {
                      $fieldInstance = Vtiger_Field_Model::getInstance($value,$moduleModel); 

                            if($fieldInstance->isViewable()){
                                $query= $query. ', ' .$value;
                          }
           }
        
           $query = $query . " FROM vtiger_potential
						INNER JOIN vtiger_crmentity ON vtiger_potential.potentialid = vtiger_crmentity.crmid
							AND deleted = 0 ".Users_Privileges_Model::getNonAdminAccessControlQuery($this->getName())."
						WHERE sales_stage NOT IN ('Closed Won', 'Closed Lost') AND amount > 0
						ORDER BY amount DESC LIMIT ".$pagingModel->getStartIndex().", ".$pagingModel->getPageLimit()."";
     	$result = $db->pquery($query, array());

		$models = array();
		for($i=0; $i<$db->num_rows($result); $i++) {
			$modelInstance = Vtiger_Record_Model::getCleanInstance('Potentials');
			$modelInstance->setId($db->query_result($result, $i, 'crmid'));
			$modelInstance->set('amount', $db->query_result($result, $i, 'amount'));
			$modelInstance->set('potentialname', $db->query_result($result, $i, 'potentialname'));
			$modelInstance->set('related_to', $db->query_result($result, $i, 'related_to'));
			$models[] = $modelInstance;
		}
		return $models;
	}

	/**
	 * Function returns Potentials Forecast Amount
	 * @return <Array>
	 */
	function getForecast($closingdateFilter,$dateFilter) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$db = PearDatabase::getInstance();

		$params = array();
		$params[] = $currentUser->getId();
		if(!empty($closingdateFilter)) {
			$closingdateFilterSql = ' AND closingdate BETWEEN ? AND ? ';
			$params[] = $closingdateFilter['start'];
			$params[] = $closingdateFilter['end'];
		}
		
		if(!empty($dateFilter)) {
			$dateFilterSql = ' AND createdtime BETWEEN ? AND ? ';
			//client is not giving time frame so we are appending it
			$params[] = $dateFilter['start']. ' 00:00:00';
			$params[] = $dateFilter['end']. ' 23:59:59';
		}
		
		$result = $db->pquery('SELECT forecast_amount, DATE_FORMAT(closingdate, "%m-%d-%Y") AS closingdate FROM vtiger_potential
					INNER JOIN vtiger_crmentity ON vtiger_potential.potentialid = vtiger_crmentity.crmid
					AND deleted = 0 AND smownerid = ? WHERE closingdate >= CURDATE() AND sales_stage NOT IN ("Closed Won", "Closed Lost")'.
					' '.$closingdateFilterSql.$dateFilterSql,
					$params);

		$forecast = array();
		for($i=0; $i<$db->num_rows($result); $i++) {
			$row = $db->query_result_rowdata($result, $i);
			$forecast[] = $row;
		}
		return $forecast;

	}

	/**
	 * Function to get relation query for particular module with function name
	 * @param <record> $recordId
	 * @param <String> $functionName
	 * @param Vtiger_Module_Model $relatedModule
	 * @return <String>
	 */
	public function getRelationQuery($recordId, $functionName, $relatedModule) {
		if ($functionName === 'get_activities') {
			$userNameSql = getSqlForNameInDisplayFormat(array('first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'), 'Users');

			$query = "SELECT CASE WHEN (vtiger_users.user_name not like '') THEN $userNameSql ELSE vtiger_groups.groupname END AS user_name,
						vtiger_crmentity.*, vtiger_activity.activitytype, vtiger_activity.subject, vtiger_activity.date_start, vtiger_activity.time_start,
						vtiger_activity.recurringtype, vtiger_activity.due_date, vtiger_activity.time_end, vtiger_activity.visibility, vtiger_seactivityrel.crmid AS parent_id,
						CASE WHEN (vtiger_activity.activitytype = 'Task') THEN (vtiger_activity.status) ELSE (vtiger_activity.eventstatus) END AS status
						FROM vtiger_activity
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_activity.activityid
						LEFT JOIN vtiger_seactivityrel ON vtiger_seactivityrel.activityid = vtiger_activity.activityid
						LEFT JOIN vtiger_cntactivityrel ON vtiger_cntactivityrel.activityid = vtiger_activity.activityid
						LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
						LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
							WHERE vtiger_crmentity.deleted = 0 AND vtiger_activity.activitytype <> 'Emails'
								AND vtiger_seactivityrel.crmid = ".$recordId;

			$relatedModuleName = $relatedModule->getName();
			$query .= $this->getSpecificRelationQuery($relatedModuleName);
			$nonAdminQuery = $this->getNonAdminAccessControlQueryForRelation($relatedModuleName);
			if ($nonAdminQuery) {
				$query = appendFromClauseToQuery($query, $nonAdminQuery);
			}
		} else {
			$query = parent::getRelationQuery($recordId, $functionName, $relatedModule);
		}

		return $query;
	}
	
	/**
	 * Function returns Potentials Amount for each Sales Stage
	 * @return <Array>
	 */
	function getPotentialTotalAmountBySalesStage() {
		//$currentUser = Users_Record_Model::getCurrentUserModel();
		$db = PearDatabase::getInstance();

		$data = array();
		$result = $db->pquery('SELECT SUM(amount) AS amount,sales_stage FROM vtiger_potential
							   INNER JOIN vtiger_crmentity ON vtiger_potential.potentialid = vtiger_crmentity.crmid
							   JOIN vtiger_sales_stage USING (sales_stage)
							   '.Users_Privileges_Model::getNonAdminAccessControlQuery($this->getName()).' WHERE deleted = 0 GROUP BY sales_stage ORDER BY sortorderid', array());

		while ($row = $db->fetchByAssoc($result,-1,false)) {
			if(!empty($row['amount'])){
				// collect data, 0 => value, 1 => label, 2 => condition for linked listview
				$data[] = array($row['amount'], vtranslate($row['sales_stage'], $this->getName()), $row['sales_stage']);
			}
		}
		return $data;
	}

	/**
	 * Function returns query for module record's search
	 * @param <String> $searchValue - part of record name (label column of crmentity table)
	 * @param <Integer> $parentId - parent record id
	 * @param <String> $parentModule - parent module name
	 * @return <String> - query
	 */
	public function getSearchRecordsQuery($searchValue, $parentId=false, $parentModule=false) {
		if($parentId && in_array($parentModule, array('Accounts', 'Contacts'))) {
			$query = "SELECT * FROM vtiger_crmentity
						INNER JOIN vtiger_potential ON vtiger_potential.potentialid = vtiger_crmentity.crmid
						WHERE deleted = 0 AND vtiger_potential.related_to = $parentId AND label like '%$searchValue%'";
			return $query;
		}
		return parent::getSearchRecordsQuery($parentId, $parentModule);
	}
}