<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */
vimport('~~/modules/ModTracker/core/ModTracker_Basic.php');

class ModTracker_Record_Model extends Vtiger_Record_Model {

	const UPDATE = 0;
	const DELETE = 1;
	const CREATE = 2;
	const RESTORE = 3;
	const LINK = 4;
	const UNLINK = 5;

	/**
	 * Function to get the history of updates on a record
	 * @param <type> $record - Record model
	 * @param <type> $limit - number of latest changes that need to retrieved
	 * @return <array> - list of  ModTracker_Record_Model
	 */
	public static function getUpdates($parentRecordId, $pagingModel, $sortOrder = 'DESC') {
		$db = PearDatabase::getInstance();
		$recordInstances = array();

        // Paging
		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();

        // SortOrder absichern
        $sortOrder = strtoupper($sortOrder) == 'ASC' ? 'ASC' : 'DESC';
		$listQuery = "SELECT * FROM vtiger_modtracker_basic 
        WHERE crmid = ? 
        ORDER BY changedon $sortOrder, id $sortOrder 
        LIMIT $startIndex, $pageLimit ";

        $moduleName = Vtiger_Functions::getCRMRecordType($parentRecordId);

		$result = $db->pquery($listQuery, array($parentRecordId));
		$rows = $db->num_rows($result);

		for ($i=0; $i<$rows; $i++) {
			$row = $db->query_result_rowdata($result, $i);
			$recordInstance = new self();
			$recordInstance->setData($row); 

            if ($recordInstance === null) {
                error_log("setData() hat null zurückgegeben - unerwartet!");
            } 
            else {
                $recordInstance->setParent($row['crmid'], $row['module']);
            }
			$recordInstances[] = $recordInstance;
		}

        // check if it was next site present.
        $countQuery = " SELECT COUNT(*) AS count 
        FROM vtiger_modtracker_basic 
        WHERE crmid = ? ";

        $countResult = $db->pquery($countQuery, array($parentRecordId));
        $totalCount = (int)$db->query_result($countResult, 0, 'count');

        $pagingModel->set('totalCount', $totalCount);
        $pagingModel->calculatePageRange($recordInstances);

		return $recordInstances;
	}

    public static function getFilteredUpdates($recordId, $pagingModel, $filterField, $searchTerm , $sortOrder = 'DESC') {
        $db = PearDatabase::getInstance();

        // SortOrder absichern
        $sortOrder = strtoupper($sortOrder) == 'ASC' ? 'ASC' : 'DESC';

        $params = [$recordId];
        $where = "b.crmid = ?";

        if ($filterField && $searchTerm) {
            $where .= " AND d.fieldname = ? AND (d.prevalue LIKE ? OR d.postvalue LIKE ?)";
            $params[] = $filterField;
            $params[] = "%$searchTerm%";
            $params[] = "%$searchTerm%";
        } elseif ($filterField) {
            $where .= " AND d.fieldname = ?";
            $params[] = $filterField;
        } elseif ($searchTerm) {
            $where .= " AND (d.prevalue LIKE ? OR d.postvalue LIKE ?)";
            $params[] = "%$searchTerm%";
            $params[] = "%$searchTerm%";
        }

        $start = $pagingModel->getStartIndex();
        $limit = $pagingModel->getPageLimit();

        $sql = "SELECT DISTINCT b.* 
        FROM vtiger_modtracker_basic b 
        LEFT JOIN vtiger_modtracker_detail d ON d.id = b.id 
        WHERE $where 
        ORDER BY b.changedon $sortOrder, b.id $sortOrder 
        LIMIT $start, $limit 
        ";

        $res = $db->pquery($sql, $params);

        // Count
        $countSql = "SELECT COUNT(DISTINCT b.id) AS cnt
        FROM vtiger_modtracker_basic b
        LEFT JOIN vtiger_modtracker_detail d ON d.id = b.id
        WHERE $where
        ";
        $countRes = $db->pquery($countSql, $params);
        $totalRecordCountOfSearch = (int)$db->query_result($countRes, 0, 'cnt');

		$rows = $db->num_rows($res);

        $recordInstances = array();
		for ($i=0; $i<$rows; $i++) {
			$row = $db->query_result_rowdata($res, $i);
			$recordInstance = new self();
			$recordInstance->setData($row); 
            if ($recordInstance === null) {
                error_log("setData() hat null zurückgegeben - unerwartet!");
            } 
            else {
                $recordInstance->setParent($row['crmid'], $row['module']);
            }
			$recordInstances[] = $recordInstance;
		}
        
        $pagingModel->set('totalCount', $totalRecordCountOfSearch );
        $pagingModel->calculatePageRange($recordInstances);

        return $recordInstances;
    }

	function setParent($id, $moduleName) {
		$this->parent = Vtiger_Record_Model::getInstanceById($id, $moduleName);
	}

	function getParent() {
		return $this->parent;
	}

	function checkStatus($callerStatus) {
		$status = $this->get('status');
		if ($status == $callerStatus) {
			return true;
		}
		return false;
	}

	function isCreate() {
		return $this->checkStatus(self::CREATE);
	}

	function isUpdate() {
		return $this->checkStatus(self::UPDATE);
	}

	function isDelete() {
		return $this->checkStatus(self::DELETE);
	}

	function isRestore() {
		return $this->checkStatus(self::RESTORE);
	}

	function isRelationLink() {
		return $this->checkStatus(self::LINK);
	}

	function isRelationUnLink() {
		return $this->checkStatus(self::UNLINK);
	}

	function getModifiedBy() {
		$changeUserId = $this->get('whodid');
		return Users_Record_Model::getInstanceById($changeUserId, 'Users');
	}

	function getActivityTime() {
		return $this->get('changedon');
	}

	function getFieldInstances() {
		$id = $this->get('id');
		$db = PearDatabase::getInstance();

		$fieldInstances = array();
		if($this->isCreate() || $this->isUpdate()) {
			$result = $db->pquery('SELECT * FROM vtiger_modtracker_detail WHERE id = ?', array($id));
			$rows = $db->num_rows($result);
			for($i=0; $i<$rows; $i++) {
				$data = $db->query_result_rowdata($result, $i);
				$row = array_map('html_entity_decode', $data);

				if($row['fieldname'] == 'record_id' || $row['fieldname'] == 'record_module') continue;

				$fieldModel = Vtiger_Field_Model::getInstance($row['fieldname'], $this->getParent()->getModule());
				if(!$fieldModel) continue;
				
				$fieldInstance = new ModTracker_Field_Model();
				$fieldInstance->setData($row)->setParent($this)->setFieldInstance($fieldModel);
				$fieldInstances[] = $fieldInstance;
			}
		}
		return $fieldInstances;
	}

	function getRelationInstance() {
		$id = $this->get('id');
		$db = PearDatabase::getInstance();

		if($this->isRelationLink() || $this->isRelationUnLink()) {
			$result = $db->pquery('SELECT * FROM vtiger_modtracker_relations WHERE id = ?', array($id));
			$row = $db->query_result_rowdata($result, 0);
			$relationInstance = new ModTracker_Relation_Model();
			$relationInstance->setData($row)->setParent($this);
		}
		return $relationInstance;
	}
        
	public static function getTotalRecordCount($recordId) {
    	$db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT COUNT(*) AS count FROM vtiger_modtracker_basic WHERE crmid = ?", array($recordId));
        return $db->query_result($result, 0, 'count');
	}

    public static function getFilteredUpdatesCount( $parentRecordId, $filterField, $searchTerm ) {
        $db = PearDatabase::getInstance();

        $params = array($parentRecordId);

        $query = "SELECT COUNT(DISTINCT b.id) AS cnt
        FROM vtiger_modtracker_basic b
        INNER JOIN vtiger_modtracker_detail d ON d.id = b.id
        WHERE b.crmid = ?
        ";

        if (!empty($filterField)) {
            $query .= " AND d.fieldname = ? ";
            $params[] = $filterField;
        }

        if (!empty($searchTerm)) {
            $query .= " AND (d.prevalue LIKE ? OR d.postvalue LIKE ?) ";
            $params[] = '%' . $searchTerm . '%';
            $params[] = '%' . $searchTerm . '%';
        }

        $result = $db->pquery($query, $params);
        $totalRecordCountOfSearch = (int)$db->query_result($result, 0, 'cnt');

        return $totalRecordCountOfSearch;
    }

}