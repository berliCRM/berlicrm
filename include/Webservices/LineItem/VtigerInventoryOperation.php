<?php
/*+*******************************************************************************
 *  The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *
 *********************************************************************************/
require_once 'include/Webservices/VtigerModuleOperation.php';
require_once 'include/Webservices/Utils.php';

/**
 * Description of VtigerInventoryOperation
 */
class VtigerInventoryOperation extends VtigerModuleOperation {

	public function create($elementType, $element) {
		$element = $this->sanitizeInventoryForInsert($element);
		$element = $this->sanitizeShippingTaxes($element);
		$lineItems = $element['LineItems'];
		if (!empty($lineItems)) {
            $element = parent::create($elementType, $element);
            $handler = vtws_getModuleHandlerFromName('LineItem', $this->user);
			$handler->setLineItems('LineItem', $lineItems, $element);
            $parent = $handler->getParentById($element['id']);
			$handler->updateParent($lineItems, $parent);
            $updatedParent = $handler->getParentById($element['id']);
            //since subtotal and grand total is updated in the update parent api 
            $parent['hdnSubTotal'] = $updatedParent['hdnSubTotal'];
            $parent['hdnGrandTotal'] = $updatedParent['hdnGrandTotal'];
            $parent['pre_tax_total'] = $updatedParent['pre_tax_total'];
            $components = vtws_getIdComponents($element['id']);
            $parentId = $components[1]; 
            $parent['LineItems'] = $handler->getAllLineItemForParent($parentId);
            
		} else {
			throw new WebServiceException(WebServiceErrorCode::$MANDFIELDSMISSING, "Mandatory Fields Missing..");
		}
		return array_merge($element,$parent);
	}

	public function update($element) {
		$element = $this->sanitizeInventoryForInsert($element);
		$element = $this->sanitizeShippingTaxes($element);
		$lineItemList = $element['LineItems'];
		$handler = vtws_getModuleHandlerFromName('LineItem', $this->user);
		if (!empty($lineItemList)) {
			$updatedElement = parent::update($element);
			$handler->setLineItems('LineItem', $lineItemList, $updatedElement);
			$parent = $handler->getParentById($element['id']);
			$handler->updateParent($lineItemList, $parent);
            $updatedParent = $handler->getParentById($element['id']);
            //since subtotal and grand total is updated in the update parent api 
            $parent['hdnSubTotal'] = $updatedParent['hdnSubTotal'];
            $parent['hdnGrandTotal'] = $updatedParent['hdnGrandTotal'];
            $parent['pre_tax_total'] = $updatedParent['pre_tax_total'];
            $updatedElement = array_merge($updatedElement,$parent);
		} else {
			$updatedElement = $this->revise($element);
		}
		return $updatedElement;
	}

	public function revise($element) {
		$element = $this->sanitizeInventoryForInsert($element);
		$element = $this->sanitizeShippingTaxes($element);
		$handler = vtws_getModuleHandlerFromName('LineItem', $this->user);
		$components = vtws_getIdComponents($element['id']);
		$parentId = $components[1];

		if (!empty($element['LineItems'])) {
			$lineItemList = $element['LineItems'];
			unset($element['LineItems']);

			$updatedElement = parent::revise($element);
			$handler->setLineItems('LineItem', $lineItemList, $updatedElement);
			$parent = $handler->getParentById($element['id']);
			$handler->updateParent($lineItemList, $parent);
			$updatedParent = $handler->getParentById($element['id']);
			//since subtotal and grand total is updated in the update parent api
			$parent['hdnSubTotal'] = $updatedParent['hdnSubTotal'];
			$parent['hdnGrandTotal'] = $updatedParent['hdnGrandTotal'];
			$parent['pre_tax_total'] = $updatedParent['pre_tax_total'];
			$parent['LineItems'] = $handler->getAllLineItemForParent($parentId);
		} else {
			$prevAction = $_REQUEST['action'];
			// This is added as we are passing data in user format, so in the crmentity insertIntoEntity API
			// should convert to database format, we have added a check based on the action name there. But 
			// while saving Invoice and Purchase Order we are also depending on the same action file names to
			// not to update stock if its an ajax save. In this case also we do not want line items to change.
			$_REQUEST['action'] = 'FROM_WS';

			$parent = parent::revise($element);
			$_REQUEST['action'] = $prevAction;
		}
		return array_merge($element,$parent);
	}

	public function retrieve($id) {
		$element = parent::retrieve($id);
		$skipLineItemFields = getLineItemFields();
		foreach ($skipLineItemFields as $key => $field) {
			if (array_key_exists($field, $element)) {
				unset($element[$field]);
			}
		}
		$handler = vtws_getModuleHandlerFromName('LineItem', $this->user);
		$idComponents = vtws_getIdComponents($id);
		$lineItems = $handler->getAllLineItemForParent($idComponents[1]);
		$element['LineItems'] = $lineItems;
		return $element;
	}

	public function delete($id) {
		$components = vtws_getIdComponents($id);
		$parentId = $components[1];
		$handler = vtws_getModuleHandlerFromName('LineItem', $this->user);
		$handler->cleanLineItemList($id);
		$result = parent::delete($id);
		return $result;
	}
	/**
	 * function to display discounts,taxes and adjustments
	 * @param type $element
	 * @return type
	 */
	protected function sanitizeInventoryForInsert($element) {
		$meta = $this->getMeta();
		if (!empty($element['hdnTaxType'])) {
			$_REQUEST['taxtype'] = $element['hdnTaxType'];
		}
		if (!empty($element['hdnSubTotal'])) {
			$_REQUEST['subtotal'] = $element['hdnSubTotal'];
		}

		if (($element['hdnDiscountAmount'])) {
			$_REQUEST['discount_type_final'] = 'amount';
			$_REQUEST['discount_amount_final'] = $element['hdnDiscountAmount'];
		} elseif (($element['hdnDiscountPercent'])) {
			$_REQUEST['discount_type_final'] = 'percentage';
			$_REQUEST['discount_percentage_final'] = $element['hdnDiscountPercent'];
		} else {
			$_REQUEST['discount_type_final'] = '';
			$_REQUEST['discount_percentage_final'] = '';
		}
		

		if (($element['txtAdjustment'])) {
			$_REQUEST['adjustmentType'] = ((int) $element['txtAdjustment'] < 0) ? '-' : '+';
			$_REQUEST['adjustment'] = abs($element['txtAdjustment']);
		}else {
			$_REQUEST['adjustmentType'] = '';
			$_REQUEST['adjustment'] = '';
		}
		if (!empty($element['hdnGrandTotal'])) {
			$_REQUEST['total'] = $element['hdnGrandTotal'];
		}
		
		return $element;
	}
	
	public function sanitizeShippingTaxes($element){
		$_REQUEST['shipping_handling_charge'] = $element['hdnS_H_Amount'];
		$taxDetails = getAllTaxes('all', 'sh');
		foreach ($taxDetails as $taxInfo) {
			//removing previous taxes
			unset($_REQUEST[$taxInfo['taxname'] . '_sh_percent']);
			if ($taxInfo['deleted'] == '0' || $taxInfo['deleted'] === 0) {
				if(isset($element['hdnS_H_Percent']) && $element['hdnS_H_Percent'] != 0){
					$_REQUEST[$taxInfo['taxname'] . '_sh_percent'] = $element['hdnS_H_Percent'];
					break;
				} else {
					if(isset($element[$taxInfo['taxname'] . '_sh_percent'])){
						$_REQUEST[$taxInfo['taxname'] . '_sh_percent'] = $element[$taxInfo['taxname'] . '_sh_percent'];
					}
					//if there is Shipping Amount and shipping taxes is provided with 0 
					elseif($element['hdnS_H_Amount'] > 0 && $element['hdnS_H_Percent'] === 0){
						$_REQUEST[$taxInfo['taxname'] . '_sh_percent'] = 0;
					}else{
						$_REQUEST[$taxInfo['taxname'] . '_sh_percent'] = $taxInfo['percentage'];
					}
				}
			}
		}
		return $element;		
	}
    /* NOTE: Special case to pull the default setting of TermsAndCondition */

    public function describe($elementType) {
        $describe = parent::describe($elementType);
        $tandc = getTermsAndConditions();
        foreach ($describe['fields'] as $key => $list){
            if($list["name"] == 'terms_conditions'){
                $describe['fields'][$key]['default'] = $tandc;
            }
        }
        return $describe;
    }
}

?>