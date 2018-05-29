<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
require_once 'modules/berlimap/lib/GeoCoder.inc.php';

class berlimap_ListAjax_Action extends Vtiger_BasicAjax_Action {

	public function __construct() {
		parent::__construct();
		$this->exposeMethod('getGeoData');
		$this->exposeMethod('getGeoDistance');
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->get('mode');
		if(!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	/**
	 * Function to get related Records count from this relation
	 * @param <Vtiger_Request> $request
	 * @return <Number> Number of record from this relation
	 */
	public function getGeoData(Vtiger_Request $request) {
		global $current_user, $adb;
		$db = PearDatabase::getInstance();
		
		$moduleName = $request->getModule();
		$viewId = $request->get('vid');
		$targetModule = $request->get('targetModule');
		
		$queryGenerator = new QueryGenerator($targetModule, $current_user);
		$fields = $queryGenerator->getModuleFields();
		if ($targetModule=='Accounts') {
			$queryGenerator->setFields(array('accountname', 'bill_city', 'bill_code','bill_state','bill_country','bill_street','accountid'));
		}
		elseif ($targetModule=='Contacts') {
			$queryGenerator->setFields(array('lastname','firstname', 'mailingcity', 'mailingstate','mailingzip','mailingcountry','mailingstreet','contactid','accountid'));
		}
		elseif ($targetModule=='Leads'){
			$queryGenerator->setFields(array('lastname','firstname',  'city', 'code','state','country','lane','leadid'));
		}

		$GeoCoder = new GeoCoder();
		$query = $queryGenerator->getCustomViewQueryById($viewId);
		$queryResult = $db->pquery($query, array());
		$locations = array();
		$limitwarning = 0;
		$response = new Vtiger_Response();
		
		while($record = $db->fetchByAssoc($queryResult)){
			set_time_limit(0);
			if ($targetModule=='Accounts') {
				$records[$record['accountid']]= array('street' => decode_html($record['bill_street']),'city' => decode_html($record['bill_city']),'code' => $record['bill_code'],'state' => decode_html($record['bill_state']),'country' => decode_html($record['bill_country']),'name' => decode_html($record['accountname']),'targetModule' => $targetModule);
				$geodata = $GeoCoder->getGeoCode($record['accountid'],$records[$record['accountid']]['state'],$records[$record['accountid']]['city'],$records[$record['accountid']]['code'],$records[$record['accountid']]['street'],$records[$record['accountid']]['country']);
				if ($geodata =='JS_OVER_24H_LIMIT') {
					$limitwarning = $limitwarning+1;
					continue;
				}
				elseif (empty ($geodata)) {
					continue;
				}
				else {
					$locations[$record['accountid']] = $geodata;
				}
				$locations[$record['accountid']]->targetModule=$targetModule;
				$locations[$record['accountid']]->name=$record['accountname'];
				$locations[$record['accountid']]->targetURL = $this->getDetailViewURL($record['accountid'], $targetModule);
			}	
			elseif ($targetModule=='Contacts') {
				$records[$record['contactid']]= array('street' => decode_html($record['mailingstreet']),'city' => decode_html($record['mailingcity']),'code' => $record['mailingzip'],'state' => decode_html($record['mailingstate']),'country' => decode_html($record['mailingcountry']),'name' => decode_html($record['firstname'].' '.$record['lastname']),'targetModule' => $targetModule);
				$geodata = $GeoCoder->getGeoCode($record['contactid'],$records[$record['contactid']]['state'],$records[$record['contactid']]['city'],$records[$record['contactid']]['code'],$records[$record['contactid']]['street'],$records[$record['contactid']]['country']);
				if ($geodata =='JS_OVER_24H_LIMIT') {
					$limitwarning = $limitwarning+1;
					continue;
				}
				elseif (empty ($geodata)) {
					continue;
				}
				else {
					$locations[$record['contactid']] = $geodata;
				}
				$locations[$record['contactid']]->targetModule=$targetModule;
				if (!empty ($record['accountid'])) {
					$locations[$record['contactid']]->name = $records[$record['contactid']]['name'].' - '.getAccountName($record['accountid']);
				}
				else {
					$locations[$record['contactid']]->name = $records[$record['contactid']]['name'];
				}
				$locations[$record['contactid']]->targetURL = $this->getDetailViewURL($record['contactid'], $targetModule);
			}	
			elseif ($targetModule=='Leads') {
				$records[$record['leadid']]= array('street' => decode_html($record['lane']),'city' => decode_html($record['city']),'code' => $record['code'],'state' => decode_html($record['state']),'country' => decode_html($record['country']),'name' => decode_html($record['firstname'].' '.$record['lastname']),'targetModule' => $targetModule);
				
				$geodata = $GeoCoder->getGeoCode($record['leadid'],$records[$record['leadid']]['state'],$records[$record['leadid']]['city'],$records[$record['leadid']]['code'],$records[$record['leadid']]['street'],$records[$record['leadid']]['country']);
				if ($geodata =='JS_OVER_24H_LIMIT') {
					$limitwarning = $limitwarning+1;
					continue;
				}
				elseif (empty ($geodata)) {
					continue;
				}
				else {
					$locations[$record['leadid']] = $geodata;
				}
				$locations[$record['leadid']]->targetModule=$targetModule;
				$locations[$record['leadid']]->name=$records[$record['leadid']]['name'];
				$locations[$record['leadid']]->targetURL = $this->getDetailViewURL($record['leadid'], $targetModule);
			}	
		}

		$results = array ('locations'=>$locations, 'limitwarning'=>$limitwarning);
		$response->setResult($results);
		$response->emit();
	}
	
	
	public function getDetailViewURL ($recordId, $moduleName) {
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		$url = $recordModel->getDetailViewUrl();
		return $url;
	}
	
	/*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
	/*::                                                                         :*/
	/*::  This routine calculates the distance between two points (given the     :*/
	/*::  latitude/longitude of those points). It is being used to calculate     :*/
	/*::  the distance between two locations using GeoDataSource(TM) Products    :*/
	/*::                                                                         :*/
	/*::  Definitions:                                                           :*/
	/*::    South latitudes are negative, east longitudes are positive           :*/
	/*::                                                                         :*/
	/*::  Passed to function:                                                    :*/
	/*::    lat1, lon1 = Latitude and Longitude of point 1 (in decimal degrees)  :*/
	/*::    lat2, lon2 = Latitude and Longitude of point 2 (in decimal degrees)  :*/
	/*::    unit = the unit you desire for results                               :*/
	/*::           where: 'M' is statute miles (default)                         :*/
	/*::                  'K' is kilometers                                      :*/
	/*::                  'N' is nautical miles                                  :*/
	/*::  Worldwide cities and other features databases with latitude longitude  :*/
	/*::  are available at http://www.geodatasource.com                          :*/
	/*::                                                                         :*/
	/*::  For enquiries, please contact sales@geodatasource.com                  :*/
	/*::                                                                         :*/
	/*::  Official Web site: http://www.geodatasource.com                        :*/
	/*::                                                                         :*/
	/*::         GeoDataSource.com (C) All Rights Reserved 2015		   		     :*/
	/*::                                                                         :*/
	/*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
	function getGeoDistance (Vtiger_Request $request) {
		$lat1 = $request->get('currentloclatt');
		$lon1 = $request->get('currentloclong');
		$lat2 = $request->get('targetloclatt');
		$lon2 = $request->get('targetloclong');
		$unit = $request->get('unit');
		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;
		$unit = strtoupper($unit);

		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		if ($unit == "K") {
			$response->setResult(round($miles * 1.609344,2));
		} 
		else if ($unit == "N") {
			$response->setResult(round($miles * 0.8684,2));
		} 
		else {
			$response->setResult(round($miles,2));
		}
		$response->emit();
	}
		
}