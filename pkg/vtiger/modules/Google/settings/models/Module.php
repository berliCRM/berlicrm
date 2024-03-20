<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 *************************************************************************************/

class Settings_Google_Module_Model extends Vtiger_Module_Model {
	
	/**
	 * Function to get the google api key for google map
	 */
	public static function getGoogleApikey() {
		$Apikey = '';
		$db = PearDatabase::getInstance();
		$query = 'SELECT google_api_id FROM berli_google_settings WHERE type=?';
		$result = $db->pquery($query, array('mapapikey'));
		if ($db->num_rows($result) > 0) {
			$Apikey = $db->query_result($result, 0, 'google_api_id');
		}
		return $Apikey;
	}
	/**
	 * Function to get the google api key for google geo data
	 */
	public static function getGoogleGeoApikey() {
		$Apikey = '';
		$db = PearDatabase::getInstance();
		$query = 'SELECT google_api_id FROM berli_google_settings WHERE type=?';
		$result = $db->pquery($query, array('geodataapikey'));
		if ($db->num_rows($result) > 0) {
			$Apikey = $db->query_result($result, 0, 'google_api_id');
		}
		return $Apikey;
	}

	/**
	 * Function to get the google api key for google geo data
	 */
	public static function checkGoogleGeoApikey() {
		global $log;
		$Apikey = self::getGoogleGeoApikey();
		$address = self::getDefaultAddress();
		$baseUrl = 'https://maps.google.com/maps/api/geocode/xml?sensor=false';
		$request_url = $baseUrl."&address=".urlencode($address) . "&key=" .$Apikey;
		$xml = simplexml_load_file($request_url);
		if(!$xml) {
			$log->debug("Can't retrieve ".$address." whith url=".$request_url." ");
			$status = array ('success' => false, 'error' =>'undefined');
		}
		else if ($xml->status != 'OK') {
			$log->debug("Google API Error with status: ".$xml->status.". Can't retrieve geo data for ".$address." whith url=".$request_url." ");
			$status = array ('success' => false, 'error' =>$xml->status);
		}
		else {
			$log->debug("Successfully retrieved geo data for ".$address." whith url=".$request_url." ");
			$status = array ('success' => true);
		}
		return $status;
	}
	/**
	 * Function to get Google Menu item
	 * @return menu item Model
	 */
	public static function getMenuItem() {
		$menuItem = Settings_Vtiger_MenuItem_Model::getInstance('Google');
		return $menuItem;
	}
	
	/**
	 * Function to get Index view Url
	 * @return <String> URL
	 */
	public static function getIndexViewUrl() {
		$menuItem = self::getMenuItem();
		return 'index.php?parent=Settings&module=Google&view=Index&block='.$menuItem->get('blockid').'&fieldid='.$menuItem->get('fieldid');
	}
	
	// default address for checks
	static function getDefaultAddress() {
		$street = 'Stromstrasse 5';
		$postalCode = '10555';
		$city = 'Berlin';
		$country = 'Germany';
		$state = 'Berlin';
		$address = "{$street}, {$postalCode}, {$city}, {$country}, {$state}";
		return $address;
	}

}
