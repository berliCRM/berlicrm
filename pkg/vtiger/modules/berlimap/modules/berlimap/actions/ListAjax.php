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
		$queryGenerator-> initForCustomViewById($viewId);

		if ($targetModule=='Accounts') {
			$fieldsToSet = array('city' => 'bill_city',
								 'zip' => 'bill_code',
								 'state' => 'bill_state',
								 'country' => 'bill_country',
								 'street' => 'bill_street',
								 'id' => 'accountid',
								 'name1' => 'accountname'
						   );
		}
		elseif ($targetModule=='Contacts') {
			$fieldsToSet = array('city' => 'mailingcity',
								 'zip' => 'mailingzip',
								 'state' => 'mailingstate',
								 'country' => 'mailingcountry',
								 'street' => 'mailingstreet', 
								 'id' => 'contactid', 
								 'name1' => 'lastname',
								 'name2' => 'firstname'
						   );
		}
		elseif ($targetModule=='Leads'){
			$fieldsToSet = array('city' => 'city',
								 'zip' => 'code',
								 'state' => 'state', 
								 'country' => 'country',
								 'street' => 'lane',
								 'id' => 'leadid',
								 'name1' => 'lastname',
								 'name2' => 'firstname'
						   );
		}
		$queryGenerator->setFields(array_merge(array('id'), $fieldsToSet));
		$query = $queryGenerator->getQuery();

		$queryResult = $db->pquery($query, array());
		$locations = array();
		$limitwarning = 0;
		
		$GeoCoder = new GeoCoder();
		$locations = array();
		
		$targetModuleModel = Vtiger_Module_Model::getInstance($targetModule);
		
		while($record = $db->getNextRow($queryResult, false)) {
			set_time_limit(0);
			
			//city, code, state, country, street, id, name, latitude,longitude
			$city = $record[$fieldsToSet['city']];
			$code = $record[$fieldsToSet['zip']];
			$state = $record[$fieldsToSet['state']];
			$country = $record[$fieldsToSet['country']];
			$street = $record[$fieldsToSet['street']];
			$id = $record[$fieldsToSet['id']];
			
			$name = $record[$fieldsToSet['name1']];
			if (isset($fieldsToSet['name2'])) {
				$name = trim($record[$fieldsToSet['name2']].' '.$name);
			}
			$lat = '';
			$lng = '';
			if (isset($fieldsToSet['latitude'])) {
				$lat = $record[$fieldsToSet['latitude']];
				$lng = $record[$fieldsToSet['longitude']];
			}
			
			if (!empty($lat) && !empty($lng)) {
				$geodata = new GeoCode($lat,$lng);
			}
			else {
				if ($targetModule != 'Locations') {
					$geodata = $GeoCoder->getGeoCode($id, $state, $city, $code, $street, $country);
				}
				else{
					$geodata = array();
				}
			}
			if ($geodata == 'JS_OVER_24H_LIMIT') {
				$limitwarning = $limitwarning+1;
				continue;
			}
			elseif (empty($geodata)) {
				continue;
			}
			else {
				$locations[$id] = $geodata;
				$locations[$id]->targetModule = $targetModule;
				$locations[$id]->name = $name;
				$locations[$id]->targetURL =  $targetModuleModel->getDetailViewUrl($id);
			}
		}

		$results = array ('locations'=>$locations, 'limitwarning'=>$limitwarning);
		$response = new Vtiger_Response();
		$response->setResult($results);
		$response->emit();
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