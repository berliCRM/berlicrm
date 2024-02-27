<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Services_Module_Model extends Products_Module_Model {
	
	/**
	 * Function returns query for Services-PriceBooks Relationship
	 * @param <Vtiger_Record_Model> $recordModel
	 * @param <Vtiger_Record_Model> $relatedModuleModel
	 * @return <String>
	 */
	function get_service_pricebooks($recordModel, $relatedModuleModel) {
		$query = 'SELECT vtiger_pricebook.pricebookid, vtiger_pricebook.bookname, vtiger_pricebook.active, vtiger_crmentity.crmid, 
						vtiger_crmentity.smownerid, vtiger_pricebookproductrel.listprice, vtiger_service.unit_price
					FROM vtiger_pricebook
					INNER JOIN vtiger_pricebookproductrel ON vtiger_pricebook.pricebookid = vtiger_pricebookproductrel.pricebookid
					INNER JOIN vtiger_crmentity on vtiger_crmentity.crmid = vtiger_pricebook.pricebookid
					INNER JOIN vtiger_service on vtiger_service.serviceid = vtiger_pricebookproductrel.productid
					INNER JOIN vtiger_pricebookcf on vtiger_pricebookcf.pricebookid = vtiger_pricebook.pricebookid
					LEFT JOIN vtiger_users ON vtiger_users.id=vtiger_crmentity.smownerid
					LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid '
					. Users_Privileges_Model::getNonAdminAccessControlQuery($relatedModuleModel->getName()) .'
					WHERE vtiger_service.serviceid = '.$recordModel->getId().' and vtiger_crmentity.deleted = 0';
		
		return $query;
	}
}