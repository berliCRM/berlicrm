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

class berliCleverReach_Relation_Model extends Vtiger_Relation_Model {

	/**
	 * Function to get Email enabled modules list for detail view of record
	 * @return <array> List of modules
	 */
	public function getEmailEnabledModulesInfoForDetailView() {
		return array(
				'Leads' => array('fieldName' => 'leadid', 'tableName' => 'vtiger_crmentityrel'),
				'Contacts' => array('fieldName' => 'contactid', 'tableName' => 'vtiger_crmentityrel')
		);
	}

}