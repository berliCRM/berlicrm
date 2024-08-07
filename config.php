<?php
/*********************************************************************************
* The contents of this file are subject to the SugarCRM Public License Version 1.1.2
* ("License"); You may not use this file except in compliance with the
* License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
* Software distributed under the License is distributed on an  "AS IS"  basis,
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
* the specific language governing rights and limitations under the License.
* The Original Code is:  SugarCRM Open Source
* The Initial Developer of the Original Code is SugarCRM, Inc.
* Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.;
* All Rights Reserved.
* Contributor(s): ______________________________________.
********************************************************************************/
	    
/**
 * The configuration file for FHS system
 * is located at /etc/vtigercrm directory.
 */

include('config.inc.php');

// if 'true' then description field will be saved without smiles or another spezial symbols, only normal text. Default is false.
global $set_utf8_special_chars_to_empty_string;
$set_utf8_special_chars_to_empty_string = false;

?>