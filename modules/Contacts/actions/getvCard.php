<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include_once 'include/Webservices/Retrieve.php';

class Contacts_getvCard_Action {
	
	public function validateRequest(Vtiger_Request $request) { 
            $request->validateReadAccess(); 
	}
	public function loginRequired() {
		return true;
	}
	public function checkPermission() { }
	
	function preProcess(Vtiger_Request $request) {
		return true;
	}

	function postProcess(Vtiger_Request $request) {
		return true;
	}
	
	function process(Vtiger_Request $request) {
		$src_record = $request->get('src_record');
		if(!empty($src_record)) {
			$this->export( vtlib_purify( $src_record) );
		}
		return;
	}
	
	static function out($message, $delimiter="\n") {
		echo $message . $delimiter;
	}
	
	static function outvalue($prefix, $record, $fieldname, $delimiter="\n") {
		if (empty($record) || empty($record[$fieldname])) return;
		echo sprintf("%s%s%s", $prefix, $record[$fieldname], $delimiter);
	}
	
	static function formatTimestamp($record, $fieldname) {
		if (empty($record) || empty($record[$fieldname])) return;
		$lastrevision  = trim(sprintf("%s",$record[$fieldname]));
			
		$lastrevision  = str_replace(" ", "T", $lastrevision);			
		$lastrevision  = str_replace(":", "", $lastrevision);			
		$lastrevision  = str_replace("-", "", $lastrevision);
		
		return $lastrevision;
	}
	
	static function export($recordid) {
		global $current_user;
		
		try {
		
			$contactRecord = vtws_retrieve(vtws_getWebserviceEntityId('Contacts', $recordid), $current_user);
			$accountRecord = null;
			if (!empty($contactRecord['account_id'])) {
				$accountRecord = vtws_retrieve($contactRecord['account_id'], $current_user);
			}
			$lastfirstname = trim(sprintf("%s %s", $contactRecord['lastname'], $contactRecord['firstname']));
			$firstlastname = trim(sprintf("%s %s", $contactRecord['firstname'], $contactRecord['lastname']));
			
			$lastfirstname = str_replace(" ", ";", $lastfirstname);
			
			/** Send the output header and invoke function for contents output */
			header(sprintf("Content-Disposition:attachment;filename=%s.vcf", str_replace(';', '', $lastfirstname)));
			header("Content-Type:text/plain;charset=UTF-8");
			header("Cache-Control: post-check=0, pre-check=0", false );
			
			self::out("BEGIN:VCARD");
			self::out("VERSION:3.0");
			
			self::out("N:{$lastfirstname}");
			self::out("FN:{$firstlastname}");
			self::outvalue("ORG:", $accountRecord, "accountname");
			self::outvalue("TITLE:", $contactRecord, "title");
			
			self::outvalue("TEL;TYPE=WORK,VOICE:", $contactRecord, "phone");
			self::outvalue("TEL;TYPE=HOME,VOICE:", $contactRecord, "homephone");
			self::outvalue("TEL;TYPE=CELL,VOICE:", $contactRecord, "mobile");
			
			$workaddress = trim(sprintf("%s;%s;%s;%s;%s", $contactRecord['mailingstreet'], $contactRecord['mailingcity'],$contactRecord['mailingstate'],$contactRecord['mailingzip'],$contactRecord['mailingcountry']));
			$homeaddress = trim(sprintf("%s;%s;%s;%s;%s", $contactRecord['otherstreet'], $contactRecord['othercity'],$contactRecord['otherstate'],$contactRecord['othercode'],$contactRecord['othercountry']));
			
			self::out("ADR;TYPE=WORK:;;{$workaddress}");
			self::out("ADR;TYPE=HOME:;;{$homeaddress}");
			self::outvalue("EMAIL;TYPE=PREF,INTERNET:", $contactRecord, "email");
						
			$lastrevision = self::formatTimestamp($contactRecord,"modifiedtime");			
			self::out("REV:{$lastrevision}Z");
			
			self::out("END:VCARD");
			
		} catch(Exception $e) {
			self::out("ERROR: " . $e->getMessage());
		}		
	}	
}
