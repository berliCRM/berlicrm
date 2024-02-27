<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_ExportData_Action extends Vtiger_Mass_Action {

	function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if(!$currentUserPriviligesModel->hasModuleActionPermission($moduleModel->getId(), 'Export')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Function is called by the controller
	 * @param Vtiger_Request $request
	 */
	function process(Vtiger_Request $request) {
		$this->ExportData($request);
	}

	private $moduleInstance;
	private $focus;

	/**
	 * Function exports the data based on the mode
	 * @param Vtiger_Request $request
	 */
	function ExportData(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$moduleName = $request->get('source_module');

		$this->moduleInstance = Vtiger_Module_Model::getInstance($moduleName);
		$this->moduleFieldInstances = $this->moduleInstance->getFields();
		$this->focus = CRMEntity::getInstance($moduleName);

		$query = $this->getExportQuery($request);
		$result = $db->pquery($query, array());

		$headers = array();
		//Query generator set this when generating the query
		if(!empty($this->accessibleFields)) {
			$accessiblePresenceValue = array(0,2);
			foreach($this->accessibleFields as $fieldName) {
				$fieldModel = $this->moduleFieldInstances[$fieldName];
				// Check added as querygenerator is not checking this for admin users
				$presence = $fieldModel->get('presence');
				if(in_array($presence, $accessiblePresenceValue)) {
					$headers[] = $fieldModel->get('label');
				}
			}
		} else {
			foreach($this->moduleFieldInstances as $field) $headers[] = $field->get('label');
		}
		$translatedHeaders = array();
		foreach($headers as $header) $translatedHeaders[] = vtranslate(html_entity_decode($header, ENT_QUOTES), $moduleName);

		$entries = array();
		for($j=0; $j<$db->num_rows($result); $j++) {
			$entries[] = $this->sanitizeValues($db->fetchByAssoc($result, $j));
		}

		$this->output($request, $translatedHeaders, $entries);
	}

	/**
	 * Function that generates Export Query based on the mode
	 * @param Vtiger_Request $request
	 * @return <String> export query
	 */
	function getExportQuery(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$mode = $request->getMode();
		$cvId = $request->get('viewname');
		$moduleName = $request->get('source_module');

		$queryGenerator = new QueryGenerator($moduleName, $currentUser);
		$queryGenerator->initForCustomViewById($cvId);
		$fieldInstances = $this->moduleFieldInstances;

        $accessiblePresenceValue = array(0,2);
		foreach($fieldInstances as $field) {
            // Check added as querygenerator is not checking this for admin users
            $presence = $field->get('presence');
            if(in_array($presence, $accessiblePresenceValue)) {
                $fields[] = $field->getName();
            }
        }
		$queryGenerator->setFields($fields);
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		if(empty($orderBy) && empty($sortOrder) && $moduleName != "Users"){
			$orderBy = 'vtiger_crmentity.modifiedtime';
			$sortOrder = 'DESC';
			if (PerformancePrefs::getBoolean('LISTVIEW_DEFAULT_SORTING', true)) {
				$moduleFocus = CRMEntity::getInstance($moduleName);
				$orderBy = $moduleFocus->default_order_by;
				$sortOrder = $moduleFocus->default_sort_order;
			}
		}
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		if(!empty($orderBy)){
			$columnFieldMapping = $moduleModel->getColumnFieldMapping();
			$orderByFieldName = $columnFieldMapping[$orderBy];
			$orderByFieldModel = $moduleModel->getField($orderByFieldName);
			if($orderByFieldModel && $orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::REFERENCE_TYPE){
				$queryGenerator->addWhereField($orderByFieldName);
			}
		}
		
		$searchParams = $request->get('search_params');
		$searchParams = Vtiger_Util_Helper::transferListSearchParamsToFilterCondition($searchParams, $moduleModel);
        if(empty($searchParams)) {
            $searchParams = array();
        }
        $glue = "";
        if(count($queryGenerator->getWhereFields()) > 0 && (count($searchParams)) > 0) {
            $glue = QueryGenerator::$AND;
        }
        $queryGenerator->parseAdvFilterList($searchParams, $glue);
		
		$query = $queryGenerator->getQuery();
		if(!empty($orderBy)) {
			if($orderByFieldModel && $orderByFieldModel->isReferenceField()){
				$referenceModules = $orderByFieldModel->getReferenceList();
				$referenceNameFieldOrderBy = array();
				foreach($referenceModules as $referenceModuleName) {
					$referenceModuleModel = Vtiger_Module_Model::getInstance($referenceModuleName);
					$referenceNameFields = $referenceModuleModel->getNameFields();

					$columnList = array();
					foreach($referenceNameFields as $nameField) {
						$fieldModel = $referenceModuleModel->getField($nameField);
						$columnList[] = $fieldModel->get('table').$orderByFieldModel->getName().'.'.$fieldModel->get('column');
					}
					if(count($columnList) > 1) {
						$referenceNameFieldOrderBy[] = getSqlForNameInDisplayFormat(array('first_name'=>$columnList[0],'last_name'=>$columnList[1]),'Users', '').' '.$sortOrder;
					} else {
						$referenceNameFieldOrderBy[] = implode('', $columnList).' '.$sortOrder ;
					}
				}
				$orderQuery = ' ORDER BY '. implode(',',$referenceNameFieldOrderBy);
			}
			else if (!empty($orderBy) && $orderBy === 'smownerid') { 
				$fieldModel = Vtiger_Field_Model::getInstance('assigned_user_id', $moduleModel); 
				if ($fieldModel->getFieldDataType() == 'owner') { 
					$orderBy = 'COALESCE(CONCAT(vtiger_users.first_name,vtiger_users.last_name),vtiger_groups.groupname)'; 
				} 
				$orderQuery = ' ORDER BY '. $orderBy . ' ' .$sortOrder;
			}
			else{
				$orderQuery = ' ORDER BY '. $orderBy . ' ' .$sortOrder;
			}
		}

		if(in_array($moduleName, getInventoryModules())){
			$query = $this->moduleInstance->getExportQuery($this->focus, $query);
		}

		$this->accessibleFields = $queryGenerator->getFields();

		switch($mode) {
			case 'ExportAllData' :		break;

			case 'ExportCurrentPage' :	$pagingModel = new Vtiger_Paging_Model();
										$limit = $pagingModel->getPageLimit();

										$currentPage = $request->get('page');
										if(empty($currentPage)) $currentPage = 1;

										$currentPageStart = ($currentPage - 1) * $limit;
										if ($currentPageStart < 0) $currentPageStart = 0;
										$limitQuery = ' LIMIT '.$currentPageStart.','.$limit;

										break;

			case 'ExportSelectedRecords' :	$idList = $this->getRecordsListFromRequest($request);
											$baseTable = $this->moduleInstance->get('basetable');
											$baseTableColumnId = $this->moduleInstance->get('basetableid');
											if(!empty($idList)) {
												if(!empty($baseTable) && !empty($baseTableColumnId)) {
													$idList = implode(',' , $idList);
													$query .= ' AND '.$baseTable.'.'.$baseTableColumnId.' IN ('.$idList.')';
												}
											} else {
												$query .= ' AND '.$baseTable.'.'.$baseTableColumnId.' NOT IN ('.implode(',',$request->get('excluded_ids')).')';
											}
											break;


			default :					break;
		}
		if (isset($orderQuery)) $query .= $orderQuery;
		if (isset($limitQuery)) $query .= $limitQuery;
		return $query;
	}

	/**
	 * Function returns the export type - This can be extended to support different file exports
	 * @param Vtiger_Request $request
	 * @return <String>
	 */
	function getExportContentType(Vtiger_Request $request) {
		$type = $request->get('export_type');
		if(empty($type)) {
			return 'text/csv';
		}
		return $type;
	}

	/**
	 * Function that create the exported file
	 * @param Vtiger_Request $request
	 * @param <Array> $headers - output file header
	 * @param <Array> $entries - outfput file data
	 */
	function output($request, $headers, $entries) {
		$moduleName = $request->get('source_module');
		$fileName = str_replace(' ','_',decode_html(vtranslate($moduleName, $moduleName)));
		$exportType = $this->getExportContentType($request);
		
		header("Content-Type:$exportType;charset=UTF-8");
		header("Expires: Mon, 31 Dec 2000 00:00:00 GMT" );
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
		header("Cache-Control: post-check=0, pre-check=0", false );
		
		if ($exportType == 'text/csv') {
			$fileName .= '.csv';
			header("Content-Disposition:attachment;filename=$fileName");
			$fp = fopen("php://output", "w");
			fputcsv($fp, $headers);

			foreach($entries as $row) {
				fputcsv($fp, $row);
			}
			
			fclose($fp);
		} elseif ($exportType == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
			$fileName .= '.xls';
			header("Content-Disposition:attachment;filename=$fileName");
			require_once("libraries/PHPExcel/PHPExcel.php");

			$workbook = new PHPExcel();
			$worksheet = $workbook->setActiveSheetIndex(0);
			
			//header
			$count = 0;
			$rowcount = 1;
			$header_styles = array(
				'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb'=>'E1E0F7') ),
				//'font' => array( 'bold' => true )
			);
			foreach($headers as $value) {
				$worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $value, true);
				$worksheet->getStyleByColumnAndRow($count, $rowcount)->applyFromArray($header_styles);

				$count++;
			}
			$rowcount++;
			foreach($entries AS $array_value) {
				$count = 0;
				foreach($array_value AS $fieldName => $value) {
					$fieldInfo = $this->fieldArray[$fieldName];
					// $uitype = $fieldInfo->get('uitype');
					$fieldname = $fieldInfo->get('name');
					$type = $this->fieldDataTypeCache[$fieldName];
					
					$currencyId = (isset($current_user)) ? $current_user->currency_id : 1;
					$currencyRateAndSymbol = getCurrencySymbolandCRate($currencyId);
					$currencySymbol = $currencyRateAndSymbol['symbol'];
					$currencySymbolPlacement = (isset($current_user)) ? $current_user->currency_symbol_placement : '$';
					$currencyFormat = '#,##0.00_-';
					$tmpCurrencySymbol = '"'.$currencySymbol.'"';
					$currencyFormat = (strpos($currencySymbolPlacement, '$') === 0) ? $tmpCurrencySymbol.$currencyFormat : $currencyFormat.$tmpCurrencySymbol;
					
					if ($type == 'date' || $type == 'datetime') {
						if (!empty($value) && $value != '--') {
							list($date, $time) = explode(' ', $value);
							$date = DateTimeField::convertToDBFormat($date).' '.$time;
							$value = PHPExcel_Shared_Date::PHPToExcel(strtotime($date));
						} else {
							$value = '';
						}
						
						$worksheet->setCellValueByColumnAndRow($count, $rowcount, $value);
						$worksheet->getStyleByColumnAndRow($count, $rowcount)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_XLSX14);
					} elseif ($type == 'double' || $type == 'currency') {
						if (isset($currencySymbol)) $value = str_replace($currencySymbol, '', $value);
						$value = CurrencyField::convertToDBFormat($value, null, true);
						$worksheet->setCellValueByColumnAndRow($count, $rowcount, $value, PHPExcel_Cell_DataType::TYPE_NUMERIC);
						if ($type == 'currency') $worksheet->getStyleByColumnAndRow($count, $rowcount)->getNumberFormat()->setFormatCode($currencyFormat);
					} else {
						if ($type == 'reference') {
							list($parent_module, $value) = explode('::::', $value);
						}
						$worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $value, PHPExcel_Cell_DataType::TYPE_STRING);
					}
					$count++;
				}
				$rowcount++;
			}
			$workbookWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel2007');
			$workbookWriter->save('php://output');
		}
	}

	private $picklistValues;
	private $fieldArray;
	private $fieldDataTypeCache = array();
	/**
	 * this function takes in an array of values for an user and sanitizes it for export
	 * @param array $arr - the array of values
	 */
	function sanitizeValues($arr){
		$db = PearDatabase::getInstance();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$roleid = $currentUser->get('roleid');
		if(empty ($this->fieldArray)){
			$this->fieldArray = $this->moduleFieldInstances;
			foreach($this->fieldArray as $fieldName => $fieldObj){
				//In database we have same column name in two tables. - inventory modules only
				if($fieldObj->get('table') == 'vtiger_inventoryproductrel' && ($fieldName == 'discount_amount' || $fieldName == 'discount_percent')){
					$fieldName = 'item_'.$fieldName;
					$this->fieldArray[$fieldName] = $fieldObj;
				} else {
					$columnName = $fieldObj->get('column');
					$this->fieldArray[$columnName] = $fieldObj;
				}
			}
		}
		$moduleName = $this->moduleInstance->getName();
		foreach($arr as $fieldName=>&$value){
			if(isset($this->fieldArray[$fieldName])){
				$fieldInfo = $this->fieldArray[$fieldName];
			}else {
				unset($arr[$fieldName]);
				continue;
			}
			$value = trim(decode_html($value),"\"");
			$uitype = $fieldInfo->get('uitype');
			$fieldname = $fieldInfo->get('name');

			if(!$this->fieldDataTypeCache[$fieldName]) {
				$this->fieldDataTypeCache[$fieldName] = $fieldInfo->getFieldDataType();
			}
			$type = $this->fieldDataTypeCache[$fieldName];

			if($fieldname != 'hdnTaxType' && ($uitype == 15 || $uitype == 16 || $uitype == 33)){
				if(empty($this->picklistValues[$fieldname])){
					$this->picklistValues[$fieldname] = $this->fieldArray[$fieldname]->getPicklistValues();
				}
				// If the value being exported is accessible to current user
				// or the picklist is multiselect type.
				if($uitype == 33 || $uitype == 16 || array_key_exists($value,$this->picklistValues[$fieldname])){
					// NOTE: multipicklist (uitype=33) values will be concatenated with |# delim
					$value = trim($value);
				} else {
					$value = '';
				}
			} elseif($uitype == 52 || $type == 'owner') {
				$value = html_entity_decode(Vtiger_Util_Helper::getOwnerName($value));
			}elseif($type == 'reference'){
				$value = trim($value);
				if(!empty($value)) {
					$parent_module = getSalesEntityType($value);
					$displayValueArray = getEntityName($parent_module, $value);
					if(!empty($displayValueArray)){
						foreach($displayValueArray as $k=>$v){
							$displayValue = $v;
						}
					}
					if(!empty($parent_module) && !empty($displayValue)){
						$value = html_entity_decode($parent_module."::::".$displayValue);
					}else{
						$value = "";
					}
				} else {
					$value = '';
				}
			} elseif($uitype == 72 || $uitype == 71 || $type == 'double' || $type == 'percentage') {
                $value = CurrencyField::convertToUserFormat($value, null, true);
			} elseif($uitype == 7 && $fieldInfo->get('typeofdata') == 'N~O' || $uitype == 9){
				$value = decimalFormat($value);
			} else if($type == 'date' || $type == 'datetime'){
				if (!empty($value)) {
					$value = DateTimeField::convertToUserFormat($value);
				}
            }
			if($moduleName == 'Documents' && $fieldname == 'description'){
				$value = strip_tags($value);
				$value = str_replace('&nbsp;','',$value);
				array_push($new_arr,$value);
			}
		}
		return $arr;
	}
}
