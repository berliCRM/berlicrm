<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class Vtiger_DocumentsFileUpload_UIType extends Vtiger_Base_UIType {
	/**
	 * Function to get the Template name for the current UI Type Object
	 * @return <String> - Template Name
	 */
	public function getTemplateName() {
		return 'uitypes/DocumentsFileUpload.tpl';
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param <String> $value
	 * @param <Integer> $recordId
	 * @param <Vtiger_Record_Model>
	 * @return <String>
	 */
	public function getDisplayValue($value, $recordId=false, $recordModel=false) {
		$displayValue = parent::getDisplayValue($value, $recordId, $recordModel);
		if($recordModel && !empty($value)) {
			$fileLocationType = $recordModel->get('filelocationtype');
			$fileStatus = $recordModel->get('filestatus');
			if($fileStatus) {
				if($fileLocationType == 'I') {
					if($recordModel->isDownloadAvailable()) {
						$downloadUrl = parent::getDisplayValue($recordModel->getDownloadFileURL());
						$downloadTitle = parent::getDisplayValue(vtranslate('LBL_DOWNLOAD_FILE', 'Documents'));
						return '<a href="'.$downloadUrl.'" title="'.$downloadTitle.'">'.$displayValue.'</a>';
					}

					$notAvailableTitle = parent::getDisplayValue(vtranslate('LBL_FILE_NOT_AVAILABLE', 'Documents'));
					return '<span title="'.$notAvailableTitle.'">'.$displayValue.'</span>';
				} elseif($fileLocationType == 'E' && $recordModel->isDownloadAvailable()) {
					$downloadTitle = parent::getDisplayValue(vtranslate('LBL_DOWNLOAD_FILE', 'Documents'));
					return '<a href="'.$displayValue.'" target="_blank" title="'.$downloadTitle.'">'.$displayValue.'</a>';
				}
			}
		}
		return $displayValue;
	}
}
