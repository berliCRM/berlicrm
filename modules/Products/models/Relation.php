<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Products_Relation_Model extends Vtiger_Relation_Model {
	
	/**
	 * Function returns the Query for the relationhips
	 * @param <Vtiger_Record_Model> $recordModel
	 * @param type $actions
	 * @return <String>
	 */
	public function getQuery($recordModel, $actions=false){
		$functionName = $this->get('name');
		$query = parent::getQuery($recordModel, $actions);
		$newQuery = preg_split("/from/i", $query);
		if($functionName == 'get_product_pricebooks'){
			$newQuery[0] .= ' ,vtiger_pricebookproductrel.listprice, vtiger_pricebook.currency_id, vtiger_products.unit_price ';
		}
		if($functionName == 'get_service_pricebooks'){
			$newQuery[0] .= ' ,vtiger_pricebookproductrel.listprice, vtiger_pricebook.currency_id, vtiger_service.unit_price ';
		}
		$query = implode("FROM", $newQuery);
		return $query;
	}
	
	/**
	 * Function that deletes PriceBooks related records information
	 * @param <Integer> $sourceRecordId - Product/Service Id
	 * @param <Integer> $relatedRecordId - Related Record Id
	 */
	public function deleteRelation($sourceRecordId, $relatedRecordId) {
		$sourceModuleName = $this->getParentModuleModel()->get('name');
		$relatedModuleName = $this->getRelationModuleModel()->get('name');
		if(($sourceModuleName == 'Products' || $sourceModuleName == 'Services') && $relatedModuleName == 'PriceBooks') {
			//Description: deleteListPrice function is deleting the relation between Pricebook and Product/Service 
			$priceBookModel = Vtiger_Record_Model::getInstanceById($relatedRecordId, $relatedModuleName);
			$priceBookModel->deleteListPrice($sourceRecordId);
		} else if($sourceModuleName == $relatedModuleName){
			$this->deleteProductToProductRelation($sourceRecordId, $relatedRecordId);
		} else {
			parent::deleteRelation($sourceRecordId, $relatedRecordId);
		}
	}
	
	/**
	 * Function to delete the product to product relation(product bundles)
	 * @param type $sourceRecordId
	 * @param type $relatedRecordId true / false
	 * @return <boolean>
	 */
	public function deleteProductToProductRelation($sourceRecordId, $relatedRecordId) {
		$db = PearDatabase::getInstance();
		if(!empty($sourceRecordId) && !empty($relatedRecordId)){
			$db->pquery('DELETE FROM vtiger_seproductsrel WHERE crmid = ? AND productid = ?', array($relatedRecordId, $sourceRecordId));
			return true;
		}
	}
    
    /**
     * Function which will specify whether the relation is deletable
     * @return <Boolean>
     */
    public function isDeletable() {
        $relatedModuleModel = $this->getRelationModuleModel();
        $relatedModuleName = $relatedModuleModel->get('name');
        $inventoryModulesList = array('Invoice','Quotes','PurchaseOrder','SalesOrder');
        
        //Inventoty relationship cannot be deleted from the related list
        if(in_array($relatedModuleName, $inventoryModulesList)){
            return false;
        }
        return parent::isDeletable();
    }
	
	
	public function isSubProduct($subProductId){
		if(!empty($subProductId)){
			$db = PearDatabase::getInstance();
			$result = $db->pquery('SELECT crmid FROM vtiger_seproductsrel WHERE crmid = ?', array($subProductId));
			if($db->num_rows($result) > 0){
				return true;
			}
		}
	}
	
	/**
	 * Function to add Products/Services-PriceBooks Relation
	 * @param <Integer> $sourceRecordId
	 * @param <Integer> $destinationRecordId
	 * @param <Integer> $listPrice
	 */
	public function addListPrice($sourceRecordId, $destinationRecordId, $listPrice) {
		$sourceModuleName = $this->getParentModuleModel()->get('name');
		$relatedModuleName = $this->getRelationModuleModel()->get('name');
		$relationModuleModel = Vtiger_Record_Model::getInstanceById($destinationRecordId, $relatedModuleName);
		
		$productModel = Vtiger_Record_Model::getInstanceById($sourceRecordId, $sourceModuleName);
		$productModel->updateListPrice($destinationRecordId, $listPrice, $relationModuleModel->get('currency_id'));
	}
	
}
