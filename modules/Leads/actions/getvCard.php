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

class Leads_getvCard_Action {
	
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
		
			$leadsRecord = vtws_retrieve(vtws_getWebserviceEntityId('Leads', $recordid), $current_user);
			$accountRecord = null;
			if (!empty($leadsRecord['company'])) {
				$accountRecord = $leadsRecord['company'];
			}
			$lastfirstname = trim(sprintf("%s %s", $leadsRecord['lastname'], $leadsRecord['firstname']));
			$firstlastname = trim(sprintf("%s %s", $leadsRecord['firstname'], $leadsRecord['lastname']));
			
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
			self::outvalue("TITLE:", $leadsRecord, "designation");
			
			self::outvalue("TEL;TYPE=WORK,VOICE:", $leadsRecord, "phone");
			self::outvalue("TEL;TYPE=CELL,VOICE:", $leadsRecord, "mobile");
			
			$workaddress = trim(sprintf("%s;%s;%s;%s;%s", $leadsRecord['street'], $leadsRecord['city'],$leadsRecord['state'],$leadsRecord['zip'],$leadsRecord['country']));
			
			self::out("ADR;TYPE=WORK:;;{$workaddress}");
			self::outvalue("EMAIL;TYPE=PREF,INTERNET:", $leadsRecord, "email");
						
			$lastrevision = self::formatTimestamp($leadsRecord,"modifiedtime");			
			self::out("REV:{$lastrevision}Z");
			
			self::out("END:VCARD");
			
		} catch(Exception $e) {
			self::out("ERROR: " . $e->getMessage());
		}		
	}	
}
