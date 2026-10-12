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
    public static function getUpdatesUnified($recordId, $pagingModel, $filterField = null, $searchTerm = null, $sortOrder = 'DESC') {
        $db = PearDatabase::getInstance();

        // SortOrder absichern
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        // Basis-Parameter
        $params = [$recordId];

        // Basis-Where
        $where = "b.crmid = ?";

        // Dynamische Filter
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

        // Paging
        $start = $pagingModel->getStartIndex();
        $limit = $pagingModel->getPageLimit();

        // Haupt-Query
        $sql = "
            SELECT DISTINCT b.* 
            FROM vtiger_modtracker_basic b 
            LEFT JOIN vtiger_modtracker_detail d ON d.id = b.id 
            WHERE $where 
            GROUP BY b.id 
            ORDER BY b.changedon $sortOrder, b.id $sortOrder 
            LIMIT $start, $limit 
        ";

        $res = $db->pquery($sql, $params);

        // Count-Query
        $countSql = "
            SELECT COUNT(DISTINCT b.id) AS cnt 
            FROM vtiger_modtracker_basic b 
            LEFT JOIN vtiger_modtracker_detail d ON d.id = b.id 
            WHERE $where 
        ";

        $countRes = $db->pquery($countSql, $params);
        $totalCount = (int)$db->query_result($countRes, 0, 'cnt');

        // Datensätze aufbauen
        $recordInstances = [];
        $rows = $db->num_rows($res);

        for ($i = 0; $i < $rows; $i++) {
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

        // Paging aktualisieren
        $pagingModel->set('totalCount', $totalCount);
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

    public static function getUpdatesCount($recordId, $filterField = null, $searchTerm = null) {
        $db = PearDatabase::getInstance();

        $params = [$recordId];
        $where = "b.crmid = ?";

        // Filterbedingungen dynamisch anhängen
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

        // Count-Query
        $sql = "
            SELECT COUNT(DISTINCT b.id) AS cnt
            FROM vtiger_modtracker_basic b
            LEFT JOIN vtiger_modtracker_detail d ON d.id = b.id
            WHERE $where
        ";

        $res = $db->pquery($sql, $params);
        return (int)$db->query_result($res, 0, 'cnt');
    }


}