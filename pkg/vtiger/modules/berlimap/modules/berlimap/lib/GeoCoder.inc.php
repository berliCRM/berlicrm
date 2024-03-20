<?php

/* Helper class for GeoCoder, provides latitude and longitude */
class GeoCode {
	public $latitude;
	public $longitude;
	public $approx;

	public function __construct($latitude, $longitude, $approx=false) {
		$this->latitude = $latitude;
		$this->longitude = $longitude;
		$this->approx = $approx;
	}
}


class GeoCoder {
 	private $baseUrl = "";
 	private $over24hlimit = false;

	private function initialize() {
	}

	public function __construct() {
		$this->baseUrl = 'https://maps.googleapis.com/maps/api/geocode/xml?sensor=false&output=xml';
		$this->initialize();
	}

	public function getOver24hlimit() {
		return $this->over24hlimit;
	}

	public function setOver24hlimit($value) {
		$this->over24hlimit = $value;
	}

	/** 
		Search the given location in cache
		if $id is specified, retrieve directly the record
	*/
	private function searchCache($id, $state, $city, $postalCode, $street="", $country="") {
		global $adb, $log;
		$basicquery = "SELECT lat,lng,if(street='',1,0) as approx FROM berli_map WHERE state=? AND city=? AND postalCode=? ";
		$params = array($state,$city,$postalCode);
		if (trim($street)!='') {
			$basicquery .= " AND street=? ";
			array_push($params, $street);
		}
		if (trim($country)!='') {
			$basicquery .= " AND country=? ";
			array_push($params, $country);
		}
		$result = $adb->pquery($basicquery,$params);
		if ($result && $adb->num_rows($result)>0) {
			$row = $adb->fetch_array($result);
			$log->debug("searchCache successful: approx query result row for recordid=".$id." ->".implode("|",$row)." ");
			return new GeoCode($row['lat'],$row['lng'],$row['approx']);
		}
		$log->debug("searchCache not successful: for recordid=".$id);
		return null;
	}

	/**
	Search the given location and return the geographic coordination, 
	First serach in the cache (database table), if no result found lookup the location on Google Maps GeoCoder and save the response for future requests.
	@return a GeoCode() object on success, null otherwise
	*/
	public function getGeoCode($id, $state, $city, $postalCode, $street="", $country="") {
		global $log;		
		$log->debug("getGeoCode for: recordid->".$id." city->".$city." zip->".$postalCode." street->".$street." state->".$state." country->".$country."");
		if(!$city) {
			$log->debug("Warning: no city for recordid->".$id." ");
			return null;
		}
		$ret = null;
		//check cache
		$ret = $this->searchCache($id, $state, $city, $postalCode, $street, $country);
		if(is_object($ret)) {
			$log->debug("data in cache found for: recordid->".$id." city->".decode_html($city)." zip->".$postalCode." street->".decode_html($street)." state->".decode_html($state)." country->".decode_html($country)." latitude:".$ret->latitude." longitude:".$ret->longitude);
			return $ret;
		}
		else {
			//retrieve data from Google maps geocoder and save the data into database
			$log->debug("getGeoCode: retrieve data from Google maps geocoder and save the data into database for recordid->".$id." ");

			if ($this->getOver24hlimit() == false) {
				$log->debug("get geo data for: recordid->".$id." city->".decode_html($city)." zip->".$postalCode." street->".decode_html($street)." state->".decode_html($state)." country->".decode_html($country));
				$ret = $this->retrieveGeoDataOnline($id, $state, $city, $postalCode, $street, $country);
			}
			else {
				$log->debug("24h limit reached for: recordid->".$id." city->".decode_html($city)." zip->".$postalCode." street->".decode_html($street)." state->".decode_html($state)." country->".decode_html($country));
				return 'JS_OVER_24H_LIMIT';
			}
		}
		return $ret;
	}

	/**
	Save new coordinates to database.
	@return a GeoCode() object on success, null otherwise
	*/
	private function updateCache($location,$xml) {
		global $adb;
		$lat_arr = (array) $xml->result->geometry->location->lat;
		$lng_arr = (array) $xml->result->geometry->location->lng;
		$lat = $lat_arr [0];
		$lng = $lng_arr [0];
		$id = $location[0];
		$state = decode_html($location[1]);
		$city = decode_html($location[2]);
		$postalCode = $location[3];
		$street = decode_html($location[4]);
		$country = decode_html($location[5]);

		$query1 = "INSERT INTO berli_map (mapid,state,city,postalCode,country,street,lat,lng) VALUES (?,?,?,?,?,?,?,?)";
		$update_result = $adb->pquery($query1,array($id,$state,$city,$postalCode,$country,$street,$lat,$lng));
		if (!$update_result) {
			return null;
		}
		else {
			return new GeoCode($lat,$lng,$street=="");
		}
	}

	/**
	Populate the cache given a set of locations, pay attention to delay of each request
	Input array is a multidimensional array, each entry is a location with this composition:
		[$id, $state, $city, $postalCode, $street, $country]
		0     1       2      3            4              5 
	*/
	public function retrieveGeoDataOnline($id, $state, $city, $postalCode, $street="", $country="") {
		global $log;
		$log->debug("retrieveGeoDataOnline for: recordid->".$id." city->".$city." zip->".$postalCode." street->".$street." state->".$state." country->".$country."");
		//get Google key
		$GoogleGeoApiKey = Settings_Google_Module_Model::getGoogleGeoApikey();
		if (empty($GoogleGeoApiKey)) {
			return false;
		}
		$log->debug("used GoogleGeoApiKey in retrieveGeoDataOnline: ".$GoogleGeoApiKey);
		// Initialize delay in geocode speed
		$delay = 500000;
		$attempts = 0;

		$geocode_pending = true;
		while ($geocode_pending) {
			set_time_limit(0);
			$address = "{$street}, {$postalCode}, {$city}, {$country}, {$state}"; 
			$request_url = $this->baseUrl."&address=".urlencode($address) . "&key=" .$GoogleGeoApiKey;
			
			$url_response = @file_get_contents($request_url);
			$xml_status = '';
			if(empty($url_response)) {
				$log->debug("Can't retrieve ".$address." whith url=".$request_url." ");
				//return null;
			}
			else {
				$xml = simplexml_load_string($url_response);
				$xml_status = (string) $xml->status;
				$log->debug("retrieveGeoDataOnline XML Status: ".$xml_status);
			}

			if ($xml_status == 'OK') {
				// Successful geocode
				$geocode_pending = false;
				$log->debug("retrieveGeoDataOnline: retrieved data from Google maps geocoder, save the data into database for recordid->".$id." ");
				$update_result = $this->updateCache(array($id,$state, $city, $postalCode,$street, $country),$xml);
				
				if (!$update_result) {
					$log->debug("retrieveGeoDataOnline: could not save the Google data into database for recordid->".$id." ");
					return null;
				}
				else {
					return $update_result;
				}
			} 
			else if ($xml_status == 'OVER_QUERY_LIMIT') {
				// exceeded Google Limit because of:
				// the error occurred because the application sent too many requests per second. OR
				// the error occurred because the application sent too many requests per day (24h). The daily quotas are reset at midnight (Pacific Standard Time).
				$log->debug("retrieveGeoDataOnline: sent Geocode too fast for Google, increasing delay ");
				$delay += 200000;
				$attempts = $attempts +1;
				if ($attempts > 10) {
					$log->debug("retrieveGeoDataOnline: Google reports too many requests per day (24h)");
					$this->setOver24hlimit(true);
					$geocode_pending = false;
				}
			}
			else if ($xml_status == "ZERO_RESULTS") {
				//attempt only with state, postalCode and city
				$log->debug("retrieveGeoDataOnline: Google responded with ZERO_RESULTS, try other address format ");
				$request_url = $this->baseUrl . "&address=" . urlencode("{$postalCode}, {$city}, {$state}");
				usleep($delay);
				$url_response = @file_get_contents($request_url);
				$xml2 = simplexml_load_string($url_response);
				$xmlstatus = (string) $xml2->status;
				if($xml2) {
					if ($xmlstatus == 'OK') {
						// Successful geocode
						return $this->updateCache(array($id,$state, $city, $postalCode, "", ""),$xml2);
					}
					$geocode_pending = false; 
				}
				else
					$geocode_pending = false;
					$log->debug("retrieveGeoDataOnline: No way, Google provided no match for ".$id." => ".$address." ");
			} 
			else if ($xml_status != '') {
				// failure to geocode, catch all other cases in log file
				$geocode_pending = false;
				$log->debug("retrieveGeoDataOnline: Address ".$id." => ".$address." failed to geocoded. Received status = '".$xml_status."' ");
			}
			else {
				// failure to geocode, catch all other cases in log file
				$geocode_pending = false;
				$log->debug("retrieveGeoDataOnline: Address ".$id." => ".$address." failed to geocoded. No XML provided ");
			}
			usleep($delay);
		}
	}

}
?>