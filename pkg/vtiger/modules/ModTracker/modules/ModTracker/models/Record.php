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

	protected $parent = null;
	protected $fieldInstances = null;
	protected $relationInstance = null;
	protected static $parentCache = array();
	protected static $userCache = array();
	protected static $detailRowsCache = array();
	protected static $relationRowsCache = array();

	private static function buildDetailFilter($filterField, $searchTerm) {
		$conditions = array();
		$params = array();
		$filterField = is_scalar($filterField) ? trim((string) $filterField) : '';
		$searchTerm = is_scalar($searchTerm) ? trim((string) $searchTerm) : '';
		if ($filterField !== '') {
			$conditions[] = 'd.fieldname = ?';
			$params[] = $filterField;
		}
		if ($searchTerm !== '') {
			$conditions[] = '(d.prevalue LIKE ? OR d.postvalue LIKE ?)';
			$params[] = '%' . $searchTerm . '%';
			$params[] = '%' . $searchTerm . '%';
		}
		return array(implode(' AND ', $conditions), $params);
	}

    /**
	 * Function to get the history of updates on a record
	 * @param <type> $record - Record model
	 * @param <type> $limit - number of latest changes that need to retrieved
	 * @return <array> - list of  ModTracker_Record_Model
	 */
    public static function getUpdatesUnified($recordId, $pagingModel, $filterField = '', $searchTerm = '', $sortOrder = 'DESC', $includeTotal = true) {
        $db = PearDatabase::getInstance();

        // SortOrder absichern
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

		list($detailFilter, $detailParams) = self::buildDetailFilter($filterField, $searchTerm);
		$params = array_merge(array($recordId), $detailParams);
		$where = 'b.crmid = ?' . ($detailFilter !== '' ? ' AND ' . $detailFilter : '');
		$detailJoin = $detailFilter !== '' ? ' INNER JOIN vtiger_modtracker_detail d ON d.id = b.id ' : '';

        // Paging
        $start = $pagingModel->getStartIndex();
        $limit = $pagingModel->getPageLimit();

        // Haupt-Query
        $sql = "
            SELECT DISTINCT b.* 
            FROM vtiger_modtracker_basic b 
			$detailJoin
            WHERE $where 
            GROUP BY b.id 
            ORDER BY b.changedon $sortOrder, b.id $sortOrder 
            LIMIT $start, $limit 
        ";

        $res = $db->pquery($sql, $params);

		if ($includeTotal) {
			$countSql = "SELECT COUNT(DISTINCT b.id) AS cnt FROM vtiger_modtracker_basic b $detailJoin WHERE $where";
			$countRes = $db->pquery($countSql, $params);
			$pagingModel->set('totalCount', (int) $db->query_result($countRes, 0, 'cnt'));
		}

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
        $pagingModel->calculatePageRange($recordInstances);

        return $recordInstances;
    }

	function setParent($id, $moduleName) {
		$cacheKey = $moduleName . ':' . $id;
		if (!isset(self::$parentCache[$cacheKey])) {
			self::$parentCache[$cacheKey] = Vtiger_Record_Model::getInstanceById($id, $moduleName);
		}
		$this->parent = self::$parentCache[$cacheKey];
		return $this;
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
		if (!isset(self::$userCache[$changeUserId])) {
			self::$userCache[$changeUserId] = Users_Record_Model::getInstanceById($changeUserId, 'Users');
		}
		return self::$userCache[$changeUserId];
	}

	function getActivityTime() {
		return $this->get('changedon');
	}

	function getFieldInstances() {
		if ($this->fieldInstances !== null) {
			return $this->fieldInstances;
		}
		$id = $this->get('id');
		$db = PearDatabase::getInstance();

		$fieldInstances = array();
		if($this->isCreate() || $this->isUpdate()) {
			if (!array_key_exists($id, self::$detailRowsCache)) {
				self::$detailRowsCache[$id] = array();
				$result = $db->pquery('SELECT * FROM vtiger_modtracker_detail WHERE id = ?', array($id));
				for ($i = 0; $i < $db->num_rows($result); $i++) {
					self::$detailRowsCache[$id][] = $db->query_result_rowdata($result, $i);
				}
			}
			foreach (self::$detailRowsCache[$id] as $data) {
				$row = array_map('html_entity_decode', $data);

				if($row['fieldname'] == 'record_id' || $row['fieldname'] == 'record_module') continue;

				$fieldModel = Vtiger_Field_Model::getInstance($row['fieldname'], $this->getParent()->getModule());
				if(!$fieldModel) continue;
				
				$fieldInstance = new ModTracker_Field_Model();
				$fieldInstance->setData($row)->setParent($this)->setFieldInstance($fieldModel);
				$fieldInstances[] = $fieldInstance;
			}
		}
		$this->fieldInstances = $fieldInstances;
		return $this->fieldInstances;
	}

	function getRelationInstance() {
		if ($this->relationInstance !== null) {
			return $this->relationInstance ?: false;
		}
		$id = $this->get('id');
		$db = PearDatabase::getInstance();

		if($this->isRelationLink() || $this->isRelationUnLink()) {
			if (!array_key_exists($id, self::$relationRowsCache)) {
				$result = $db->pquery('SELECT * FROM vtiger_modtracker_relations WHERE id = ?', array($id));
				self::$relationRowsCache[$id] = $db->num_rows($result) ? $db->query_result_rowdata($result, 0) : false;
			}
			if (self::$relationRowsCache[$id]) {
				$this->relationInstance = new ModTracker_Relation_Model();
				$this->relationInstance->setData(self::$relationRowsCache[$id])->setParent($this);
				return $this->relationInstance;
			}
		}
		$this->relationInstance = false;
		return false;
	}

	/**
	 * Load all detail and relation rows needed by a dashboard page in two queries.
	 */
	public static function preloadDetailsAndRelations($records) {
		$detailIds = array();
		$relationIds = array();
		foreach ($records as $record) {
			$id = (int) $record->get('id');
			if ($record->isCreate() || $record->isUpdate()) {
				self::$detailRowsCache[$id] = array();
				$detailIds[] = $id;
			} elseif ($record->isRelationLink() || $record->isRelationUnLink()) {
				self::$relationRowsCache[$id] = false;
				$relationIds[] = $id;
			}
		}

		$db = PearDatabase::getInstance();
		if ($detailIds) {
			$placeholders = implode(',', array_fill(0, count($detailIds), '?'));
			$result = $db->pquery('SELECT * FROM vtiger_modtracker_detail WHERE id IN (' . $placeholders . ')', $detailIds);
			for ($i = 0; $i < $db->num_rows($result); $i++) {
				$row = $db->query_result_rowdata($result, $i);
				self::$detailRowsCache[(int) $row['id']][] = $row;
			}
		}
		if ($relationIds) {
			$placeholders = implode(',', array_fill(0, count($relationIds), '?'));
			$result = $db->pquery('SELECT * FROM vtiger_modtracker_relations WHERE id IN (' . $placeholders . ')', $relationIds);
			for ($i = 0; $i < $db->num_rows($result); $i++) {
				$row = $db->query_result_rowdata($result, $i);
				self::$relationRowsCache[(int) $row['id']] = $row;
			}
		}
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
