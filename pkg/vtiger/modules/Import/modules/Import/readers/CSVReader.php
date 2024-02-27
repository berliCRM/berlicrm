<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
ini_set("auto_detect_line_endings", true);

class Import_CSVReader_Reader extends Import_FileReader_Reader {
    
    public function arrayCombine($key, $value) { 
        $combine = array(); 
        $dup = array(); 
        for($i=0;$i<count($key);$i++) { 
            if(array_key_exists($key[$i], $combine)){ 
                if(!$dup[$key[$i]]) $dup[$key[$i]] = 1;
                $key[$i] = $key[$i]."(".++$dup[$key[$i]].")";
            } 
            $combine[$key[$i]] = $value[$i]; 
        } 
        return $combine; 
    }
    
	public function getFirstRowData($hasHeader=true) {
		global $default_charset;

		$fileHandler = $this->getFileHandler();

		$headers = array();
		$firstRowData = array();
		$currentRow = 0;
		while($data = fgetcsv($fileHandler, 0, $this->request->get('delimiter'))) {
			if($currentRow == 0 || ($currentRow == 1 && $hasHeader)) {
				if($hasHeader && $currentRow == 0) {
					foreach($data as $key => $value) {
						$headers[$key] = $this->convertCharacterEncoding($value, $this->request->get('file_encoding'), $default_charset);
					}
				} else {
					foreach($data as $key => $value) {
						$firstRowData[$key] = $this->convertCharacterEncoding($value, $this->request->get('file_encoding'), $default_charset);
					}
					break;
				}
			}
			$currentRow++;
		}

		if($hasHeader) {
			$noOfHeaders = count($headers);
			$noOfFirstRowData = count($firstRowData);
			// Adjust first row data to get in sync with the number of headers
			if($noOfHeaders > $noOfFirstRowData) {
				$firstRowData = array_merge($firstRowData, array_fill($noOfFirstRowData, $noOfHeaders-$noOfFirstRowData, ''));
			} elseif($noOfHeaders < $noOfFirstRowData) {
				$firstRowData = array_slice($firstRowData, 0, count($headers), true);
			}
			$rowData = $this->arrayCombine($headers, $firstRowData);
		} else {
			$rowData = $firstRowData;
		}

		unset($fileHandler);
		return $rowData;
	}

	public function read() {
		global $default_charset, $current_user;

		$fileHandler = $this->getFileHandler();
		$status = $this->createTable();
		if(!$status) {
			return false;
		}

		$fieldMapping = $this->request->get('field_mapping');
		
		$moduleMeta = $this->moduleModel->getModuleMeta();
		$mandatoryFields = $moduleMeta->getMandatoryFields();
		$bCheckMandatory = $this->request->get('checkMandatory');
		$errMsg = '';

		$i=-1;
		while($data = fgetcsv($fileHandler, 0, $this->request->get('delimiter'))) {
			$i++;
			if($this->request->get('has_header') && $i == 0) continue;
			$mappedData = array();
			$allValuesEmpty = true;
			$arrMandatory = $mandatoryFields;
			foreach($fieldMapping as $fieldName => $index) {
				$fieldValue = $data[$index];
				$mappedData[$fieldName] = $fieldValue;
				if($this->request->get('file_encoding') != $default_charset) {
					$mappedData[$fieldName] = $this->convertCharacterEncoding($fieldValue, $this->request->get('file_encoding'), $default_charset);
				}
				if(!empty($fieldValue)) $allValuesEmpty = false;
				
				if (isset($arrMandatory[$fieldName]) && isset($fieldValue) && $fieldValue != '') {
					unset($arrMandatory[$fieldName]);
				}
			}
			// needs to be empty to have all mandatory fields set
			if ($bCheckMandatory && !empty($arrMandatory)) {
				$j = $i+1;
				$errMsg .= "<br>".vtranslate('LBL_IMPORT_MISSING_MANDATORY', 'Import')." $j:<br>";
				foreach ($arrMandatory AS $iName => $label) {
					$errMsg .= '"'.vtranslate($label, $this->moduleModel->getName()). "\" ($iName)<br>";
				}
			}
			
			if($allValuesEmpty) continue;
			$fieldNames = array_keys($mappedData);
			$fieldValues = array_values($mappedData);
			$this->addRecordToDB($fieldNames, $fieldValues);
		}
		unset($fileHandler);
		if (!empty($errMsg)) {
			Import_Utils_Helper::clearUserImportInfo($current_user);
			throw new Exception($errMsg);
		}
	}
}
?>
