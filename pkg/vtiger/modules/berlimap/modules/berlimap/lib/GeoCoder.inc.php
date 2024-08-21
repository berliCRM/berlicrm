<?php

/* Helper class for GeoCoder, provides latitude and longitude */
class GeoCode {
    public $latitude;
    public $longitude;
    public $approx;

	public function __construct(string $latitude, string $longitude, bool $approx = false) {
		$this->latitude = $latitude;
		$this->longitude = $longitude;
		$this->approx = $approx;
	}
}

class GeoCoder {
    private $baseUrl;
    private $over24hlimit = false;

    public function __construct() {
        $this->baseUrl = 'https://maps.googleapis.com/maps/api/geocode/xml?sensor=false&output=xml';
    }

	public function getOver24hlimit(): bool {
		return $this->over24hlimit;
	}

	public function setOver24hlimit(bool $value): void {
		$this->over24hlimit = $value;
	}

	private function searchCache(
		string $id, 
		string $state, 
		string $city, 
		string $postalCode, 
		string $street = "", 
		string $country = ""
	): ?GeoCode { 
		$adb = PearDatabase::getInstance();

        $params = [$state, $city, $postalCode];
        $query = "SELECT lat, lng, IF(street='', 1, 0) AS approx FROM berli_map WHERE state=? AND city=? AND postalCode=?";

        if (trim($street) != '') {
            $query .= " AND street=?";
            $params[] = $street;
        }
        if (trim($country) != '') {
            $query .= " AND country=?";
            $params[] = $country;
        }

        $result = $adb->pquery($query, $params);

        if ($result && $adb->num_rows($result) > 0) {
            $row = $adb->fetch_array($result);
            return new GeoCode($row['lat'], $row['lng'], $row['approx']);
        }

        return null;
    }

    public function getGeoCode($id, $state, $city, $postalCode, $street = "", $country = "") {

        if (empty($city)) {
            // toDo error handling
            return null;
        }

        $geoCode = $this->searchCache($id, $state, $city, $postalCode, $street, $country);

        if ($geoCode) {
            return $geoCode;
        }

        if ($this->getOver24hlimit()) {
            return 'JS_OVER_24H_LIMIT';
        }

        return $this->retrieveGeoDataOnline($id, $state, $city, $postalCode, $street, $country);
    }

		private function updateCache(
			array $location, 
			SimpleXMLElement $xml
		): ?GeoCode {
		$adb = PearDatabase::getInstance();
		
		// Extract Latitude and Longitude
        $lat = (string)$xml->result->geometry->location->lat;
        $lng = (string)$xml->result->geometry->location->lng;
		// Unpack Location Array:
        list($id, $state, $city, $postalCode, $street, $country) = $location;
		// Prepare and Execute SQL Query:
        $query = "INSERT INTO berli_map (mapid, state, city, postalCode, country, street, lat, lng) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$id, decode_html($state), decode_html($city), $postalCode, decode_html($country), decode_html($street), $lat, $lng];
        $result = $adb->pquery($query, $params);

        return $result ? new GeoCode($lat, $lng, empty($street)) : null;
    }

	private function retrieveGeoDataOnline(
		string $id, 
		string $state, 
		string $city, 
		string $postalCode, 
		string $street = "", 
		string $country = ""
	): ?GeoCode {
        $GoogleGeoApiKey = getenv('GOOGLE_GEO_API_KEY');
        if (empty($GoogleGeoApiKey)) {
            // toDo error handling
            return null;
        }

        $attempts = 0;
        $delay = 500000;

        while ($attempts < 10) {
            $address = "{$street}, {$postalCode}, {$city}, {$country}, {$state}";
            $requestUrl = $this->baseUrl . "&address=" . urlencode($address) . "&key=" . $GoogleGeoApiKey;

			try {
				$xml = simplexml_load_file($requestUrl);
				if ($xml === false) {
					// toDo error handling
				}
			} 
			catch (Exception $e) {
				// toDo error handling
				return null;
			}

            $status = (string)$xml->status;

            if ($status == 'OK') {
                return $this->updateCache([$id, $state, $city, $postalCode, $street, $country], $xml);
            } 
			elseif ($status == 'OVER_QUERY_LIMIT') {
                $delay += 200000;
                $attempts++;
                if ($attempts >= 10) {
                    $this->setOver24hlimit(true);
                    return 'JS_OVER_24H_LIMIT';
                }
            } 
			elseif ($status == 'ZERO_RESULTS') {
                return $this->tryAlternativeAddressFormat($id, $state, $city, $postalCode);
            } 
			else {
                // toDO error handling
                return null;
            }

            usleep($delay);
        }
    }

    private function tryAlternativeAddressFormat($id, $state, $city, $postalCode) {

        $GoogleGeoApiKey = getenv('GOOGLE_GEO_API_KEY');
        $requestUrl = $this->baseUrl . "&address=" . urlencode("$postalCode, $city, $state") . "&key=" . $GoogleGeoApiKey;
		try {
			$xml = simplexml_load_file($requestUrl);
			if ($xml === false) {
				// toDO error handling
			}
		} 
		catch (Exception $e) {
			// toDO error handling
			return null;
		}
        if ($xml && (string)$xml->status == 'OK') {
            return $this->updateCache([$id, $state, $city, $postalCode, "", ""], $xml);
        }

        return null;
    }
}
?>