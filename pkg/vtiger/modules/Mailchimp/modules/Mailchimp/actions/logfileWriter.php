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

class Mailchimp_logfileWriter_Action extends Mailchimp_MailChimpStepController_Action{

	function __construct() {
		parent::__construct();
		$this->exposeMethod('writeLogEventText');
	}
	
	static public function writeLogEventText($logtext,$logstring,$color='',$size='',$bold='',$margin='') {
		$style ='';
		if (!empty($color)) {
			$style = 'color:'.$color.';';
		}
		if (!empty($size)) {
			$style .= 'font-size:'.$size.'rem;';
		}
		if (!empty($bold)) {
			$style .= 'font-weight:bold;';
		}
		if (!empty($margin)) {
			$style .= 'margin:'.$margin.'px; margin-top:0; margin-bottom:0;';
		}
		$logtext[] =  array (
		  'text' => $logstring,
		  'style' => $style
		);
		$this->parent->logtext = $logtext;
	}
 

}