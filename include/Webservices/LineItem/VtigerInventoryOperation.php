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
			//check at least for valid Product IDs before creating the parent
			foreach ($lineItems AS $lineItem) {
				$pid = $lineItem['productid'];
				try {
					$xHandler = vtws_getModuleHandlerFromId($pid, $this->user);
					$xName = $xHandler->meta->getTabName();
					if ($xName == 'Products' || $xName == 'Services') {
						$xHandler->retrieve($pid);
					} else {
						throw new Exception('error');
					}
				} catch (Exception $e) {
					throw new WebServiceException(WebServiceErrorCode::$REFERENCEINVALID, "LineItem productid missing or invalid: ".json_encode($lineItem));
				}
			}
			$handler = vtws_getModuleHandlerFromName('LineItem', $this->user);
            $element = parent::create($elementType, $element);
			$this->trackChanges($handler, $element['id'], $lineItems);
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

            $this->executeDelayedTriggers();
            
		} else {
			throw new WebServiceException(WebServiceErrorCode::$MANDFIELDSMISSING, "Mandatory Fields Missing: LineItems");
		}
		return array_merge($element,$parent);
	}

	public function update($element) {
		$element = $this->sanitizeInventoryForInsert($element);
		$element = $this->sanitizeShippingTaxes($element);
		$lineItemList = $element['LineItems'];
		$handler = vtws_getModuleHandlerFromName('LineItem', $this->user);
		if (!empty($lineItemList)) {
			//check at least for valid Product IDs before creating the parent
			foreach ($lineItemList AS $lineItem) {
				$pid = $lineItem['productid'];
				try {
					$xHandler = vtws_getModuleHandlerFromId($pid, $this->user);
					$xName = $xHandler->meta->getTabName();
					if ($xName == 'Products' || $xName == 'Services') {
						$xHandler->retrieve($pid);
					} else {
						throw new Exception('error');
					}
				} catch (Exception $e) {
					throw new WebServiceException(WebServiceErrorCode::$REFERENCEINVALID, "LineItem productid missing or invalid: ".json_encode($lineItem));
				}
			}
			$this->trackChanges($handler, $element['id'], $lineItemList);
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
			$components = vtws_getIdComponents($element['id']);
            $parentId = $components[1];
			$updatedElement['LineItems'] = $handler->getAllLineItemForParent($parentId);

            $this->executeDelayedTriggers();

		} else {
			$updatedElement = $this->revise($element);
		}
		return $updatedElement;
	}

    private function executeDelayedTriggers() {
            // execute delayed triggers after lineitems have been saved
            if (is_array($_SESSION["delayedtrigger"])) {
                    $_SESSION["delayedtrigger"]["em"]->triggerEvent("vtiger.entity.aftersave", $_SESSION["delayedtrigger"]["data"]);
                    $_SESSION["delayedtrigger"]["em"]->triggerEvent("vtiger.entity.aftersave.final", $_SESSION["delayedtrigger"]["data"]);
                unset($_SESSION["delayedtrigger"]);
            }
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
			//check at least for valid Product IDs before creating the parent
			foreach ($lineItemList AS $lineItem) {
				$pid = $lineItem['productid'];
				try {
					$xHandler = vtws_getModuleHandlerFromId($pid, $this->user);
					$xName = $xHandler->meta->getTabName();
					if ($xName == 'Products' || $xName == 'Services') {
						$xHandler->retrieve($pid);
					} else {
						throw new Exception('error');
					}
				} catch (Exception $e) {
					throw new WebServiceException(WebServiceErrorCode::$REFERENCEINVALID, "LineItem productid missing or invalid: ".json_encode($lineItem));
				}
			}
			$this->trackChanges($handler, $element['id'], $lineItemList);
		} else {
			$lineItemList = $handler->getAllLineItemForParent($parentId);
		}

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

		$this->executeDelayedTriggers();
       
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
	
	public function query($q) {
		$output = parent::query($q);
		$handler = vtws_getModuleHandlerFromName('LineItem', $this->user);
		
		foreach ($output AS &$element) {
			$components = vtws_getIdComponents($element['id']);
            $parentId = $components[1];
			$element['LineItems'] = $handler->getAllLineItemForParent($parentId);
		}
		return $output;
	}
	
	private function trackChanges($handler, $parentId, $newLineItems) {
		try {
			$parentTypeHandler = vtws_getModuleHandlerFromId($parentId, $this->user);
			$parentTypeMeta = $parentTypeHandler->getMeta();
			$module = $parentTypeMeta->getEntityName();
			
			if(file_exists('modules/ModTracker/ModTrackerUtils.php')) {
				require_once 'modules/ModTracker/ModTracker.php';
				if (ModTracker::isTrackingEnabledForModule($module)) {
					$components = vtws_getIdComponents($parentId);
					$parentId = $components[1];
					$oldLineItems = $handler->getAllLineItemForParent($parentId);
					
					// loop through old items, check for updates, unset new items that were present in old items
					$deltaFields = array('productid', 'sequence_no', 'quantity', 'listprice', 'discount_percent', 'discount_amount', 'comment', 'description');
					// if ($module == 'SalesOrder') {
						// foreach (SalesOrder::$date_fields AS $date_field) {
							// $deltaFields[] = $date_field;
						// }
						// foreach (SalesOrder::$vitro_fields AS $vitro_field) {
							// $deltaFields[] = $vitro_field;
						// }
						// // not set by GUI
						// foreach (SalesOrder::$add_vitro_fields AS $add_vitro_field) {
							// $deltaFields[] = $add_vitro_field;
						// }
					// }
					foreach ($oldLineItems AS $oLineItem) {
						$oProdId = vtws_getIdComponents($oLineItem['productid'])[1];
						$oLineItem['productid'] = $oProdId;
						$found = false;
						foreach ($newLineItems AS $nKey => $nLineItem) {
							if ($oLineItem['id'] != $nLineItem['id']) continue;
							
							$found = true;
							
							$nProdId = vtws_getIdComponents($nLineItem['productid'])[1];
							$nLineItem['productid'] = $nProdId;
							
							foreach ($deltaFields AS $dFieldName) {
								if ($oLineItem[$dFieldName] != $nLineItem[$dFieldName]) {
									//skip entries where 0 turns to NULL or vice versa
									if (empty($oLineItem[$dFieldName]) && empty($nLineItem[$dFieldName])) continue;
									if (!isset($modid)) {
										$modid = $this->pearDB->getUniqueId('vtiger_modtracker_basic');
										$query = "INSERT INTO vtiger_modtracker_basic(id, crmid, module, whodid, changedon, status) VALUES(?,?,?,?,?,?);";
										$status = ModTracker::$UPDATED;
										$this->pearDB->pquery($query, array($modid, $parentId, $module, $this->user->id, date('Y-m-d H:i:s'), $status));
									}
									$query = "INSERT INTO vtiger_modtracker_detail(id,fieldname,prevalue,postvalue) VALUES(?,?,?,?);";
									$this->pearDB->pquery($query, array($modid, $dFieldName, $oLineItem[$dFieldName], $nLineItem[$dFieldName].'|#KAY#|'.$nLineItem['productid']));
								}
							}
							unset($newLineItems[$nKey]);
							break;
						}
						//no new item found -> deleted old item but treat it as updated to NULL for simplicity
						if (!$found) {
							if (!isset($modid)) {
								// $modid = $this->pearDB->getUniqueId('vtiger_modtracker_basic');
								$query = "INSERT INTO vtiger_modtracker_basic(id, crmid, module, whodid, changedon, status) VALUES(?,?,?,?,?,?);";
								$status = ModTracker::$UPDATED;
								$this->pearDB->pquery($query, array($modid, $parentId, $module, $this->user->id, date('Y-m-d H:i:s'), $status));
							}
							$query = "INSERT INTO vtiger_modtracker_detail(id,fieldname,prevalue,postvalue) VALUES(?,?,?,?);";
							$this->pearDB->pquery($query, array($modid, 'productid', $oProdId, NULL));
						}
					}
					// remaining new items to be added instead of updated
					foreach ($newLineItems AS $nKey => $nLineItem) {
						foreach ($deltaFields AS $dFieldName) {
							if (!isset($modid)) {
								$modid = $this->pearDB->getUniqueId('vtiger_modtracker_basic');
								$query = "INSERT INTO vtiger_modtracker_basic(id, crmid, module, whodid, changedon, status) VALUES(?,?,?,?,?,?);";
								$status = ModTracker::$UPDATED;
								$this->pearDB->pquery($query, array($modid, $parentId, $module, $this->user->id, date('Y-m-d H:i:s'), $status));
							}
							$query = "INSERT INTO vtiger_modtracker_detail(id,fieldname,prevalue,postvalue) VALUES(?,?,?,?);";
							$this->pearDB->pquery($query, array($modid, $dFieldName, NULL, $nLineItem[$dFieldName].'|#KAY#|'.$nLineItem['productid']));
						}
					}
				}
			}
		} catch (Exception $e) {
			//don't let this ruin any followup code
			//syslog(LOG_DEBUG, __FILE__.' tracker error');
		}
	}
}

?>