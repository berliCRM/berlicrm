<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

define('XML_HTMLSAX3', dirname(__FILE__) . '/../third-party/XML/');
include_once dirname(__FILE__) . '/../third-party/HTML.Safe.php';

class MailManager_Utils_Helper {

	public function safe_html_string( $string) {
		$htmlSafe = new HTML_Safe();
		array_push($htmlSafe->whiteProtocols, 'cid');
		return $htmlSafe->parse($string);
	}

	public function allowedFileExtension($filename) {
		$parts = explode('.', $filename);
		if (count($parts) > 1) {
			$extension = $parts[count($parts)-1];
			return (in_array(strtolower($extension), vglobal('upload_badext')) === false);
		}
		return false;
	}

	public function emitJSON($object) {
		Zend_Json::$useBuiltinEncoderDecoder = true;
		echo Zend_Json::encode($object);
	}
}

?>