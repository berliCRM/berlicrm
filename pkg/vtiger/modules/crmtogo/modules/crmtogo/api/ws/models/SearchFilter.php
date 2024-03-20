<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include_once 'include/Webservices/Query.php';
include_once dirname(__FILE__) . '/Filter.php';
	
class crmtogo_WS_SearchFilterModel extends crmtogo_WS_FilterModel {
	protected $criterias;
	
	function __construct($moduleName) {
		$this->moduleName = $moduleName;
	}
	
	function query() {
		return false;
	}
	
	function queryParameters() {
		return false;
	}
	
	function setCriterias($criterias) {
		$this->criterias = $criterias;
	}
	
	function execute($fieldnames, $paging = false, $calwhere ='') {
		$interval = false;
		$dias = 0;
		$selectClause = sprintf("SELECT %s", implode(',', $fieldnames));
		$fromClause = sprintf("FROM %s", $this->moduleName);
		if (($this->moduleName = 'Calendar' || $this->moduleName = 'Events') and $calwhere !='') {
			$whereClause = " WHERE date_start >= '".$calwhere['start']."' AND date_start <= '".$calwhere['end']."'";
			// You can not get more than 100 records
			$dias = (strtotime($calwhere['start']) - strtotime($calwhere['end'])) / 86400;
			$dias = abs($dias);
			$dias = floor($dias);
			$dias = $dias + 1;
			if($dias > 20)
				$interval = true;
		}
		else {
			$whereClause = "";
		}
		$orderClause = "";
		$groupClause = "";
		if ($paging) {
			$config = crmtogo_WS_Controller::getUserConfigSettings();
			$limitClause = "LIMIT 0,".$config['NavigationLimit'];
		}
		if (!empty($this->criterias)) {
			$_sortCriteria = $this->criterias['_sort'];
			if(!empty($_sortCriteria)) {
				$orderClause = $_sortCriteria;
			}
		}
		
		// getting a query with more than 100 records
		if($interval){
			$ws_query = array();
			$ok = true;
			$cociente = $dias / 7;
			$cociente = round($cociente,0, PHP_ROUND_HALF_DOWN);
			$resto = $dias % 7;
			for($i = 0; $i<$cociente; $i++){
				$date = date_create($calwhere['start']);
				$dias = $i*7;
				$dias = $dias.' days';
				date_add($date, date_interval_create_from_date_string($dias));
    			$fecha_ini = date_format($date, "Y-m-d");

    			$date = date_create($calwhere['start']);
				$dias = ($i+1)*7;
				$dias = $dias.' days';
				date_add($date, date_interval_create_from_date_string($dias));
    			$fecha_fin = date_format($date, "Y-m-d");
    			if($i > 0)
					$whereClause = " WHERE date_start > '".$fecha_ini."' AND date_start <= '".$fecha_fin."'";
				else
					$whereClause = " WHERE date_start >= '".$fecha_ini."' AND date_start <= '".$fecha_fin."'";
				
				$query = sprintf("%s %s %s %s %s %s;", $selectClause, $fromClause, $whereClause, $orderClause, $groupClause, $limitClause);
				$ws_query_2 = vtws_query($query, $this->getUser());

				$ws_query = array_merge($ws_query, $ws_query_2);
			}
			if($resto > 0){
				$date = date_create($calwhere['start']);
				$dias = $cociente*7;
				$dias = $dias.' days';
				date_add($date, date_interval_create_from_date_string($dias));
    			$fecha_ini = date_format($date, "Y-m-d");
    			$whereClause = " WHERE date_start > '".$fecha_ini."' AND date_start <= '".$calwhere['end']."'";
    			$query = sprintf("%s %s %s %s %s %s;", $selectClause, $fromClause, $whereClause, $orderClause, $groupClause, $limitClause);
				$ws_query_2 = vtws_query($query, $this->getUser());

				$ws_query = array_merge($ws_query, $ws_query_2);
			}
		}
		else {
			$query = sprintf("%s %s %s %s %s %s;", $selectClause, $fromClause, $whereClause, $orderClause, $groupClause, $limitClause);

			$ws_query = vtws_query($query, $this->getUser());
		}
		return $ws_query;
	}
	
	static function modelWithCriterias($moduleName, $criterias = false) {
		$model = new crmtogo_WS_SearchFilterModel($moduleName);
		$model->setCriterias($criterias);
		return $model;
	}
}