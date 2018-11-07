<?php
/* +***********************************************************************************************************************************
 * The contents of this file are subject to the berliCRM Public License Version 1.0 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is from the crm-now GmbH.
 * The Initial Developer of the Original Code is crm-now. Portions created by crm-now are Copyright (C) www.crm-now.de. 
 * Portions created by vtiger are Copyright (C) www.vtiger.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Settings_ListViewColors_Field_Model extends Vtiger_Field_Model {

    /**
	 * Function to get instance
	 * @param <String> $value - fieldname or fieldid
	 * @param <type> $module - optional - module instance
	 * @return <Vtiger_Field_Model>
	 */
	public static function  getInstance($value, $module = false) {
		$fieldObject = parent::getInstance($value, $module);
		if($fieldObject) {
			return self::getInstanceFromFieldObject($fieldObject);
		}
		return false;
	}
    /**
	 * Static Function to get the instance fo Vtiger Field Model from a given Vtiger_Field object
	 * @param Vtiger_Field $fieldObj - vtlib field object
	 * @return Vtiger_Field_Model instance with color information
	 */
	public static function getInstanceFromFieldObject(Vtiger_Field $fieldObj) {
		$objectProperties = get_object_vars($fieldObj);
		$fieldModel = new self();
		foreach($objectProperties as $properName=>$propertyValue) {
			$fieldModel->$properName = $propertyValue;
		}
		foreach($objectProperties as $properName=>$propertyValue) {
			if ($properName == 'name') {
				$fieldModel->coloredlistfields = self::getColoredListFields($fieldModel);
			}
		}
		return $fieldModel;
	}
	
    public function getDefaulListViewColor() {
        return '#FFFFFF';
	}

	/**
     * Function which will provide the color for picklist values for a field
     * @param type $fieldName -- string
     * @return type -- array of values
     */
    public static function getColoredListFields($fieldModel) {
        $cache = Vtiger_Cache::getInstance();
		$fieldName = $fieldModel->get('name');
        if($cache->getPicklistValues($fieldName)) {
            return $cache->getPicklistValues($fieldName);
        }
		$values = array();
		$uitype = $fieldModel -> get('uitype');
		switch ($uitype) {
			case '15': 
				$values = self::getColorForPicklistFields($fieldModel);
				break;
			case '16': 
				$values = self::getColorForPicklistFields($fieldModel);
				break;
			case '55': 
				$values = self::getColorForPicklistFields($fieldModel);
				break;
			case '56': 
				$values = self::getColorForCheckBoxFields($fieldModel);
				break;
			default: 
				break;
		}
		$cache->setPicklistValues($fieldName, $values);
        return $values;
    }
	/**
     * Function which will set the field color for picklist fields
     * @return value array
     */
    public static function getColorForPicklistFields($fieldModel) {
		$values = array();
		$picklistvalues = array_values(Vtiger_Util_Helper::getPickListValues($fieldModel -> get('name')));
		for($i = 0; $i < count($picklistvalues); $i++) {
			$values[$i] = self::getFieldColorForListView($fieldModel->get('id'),decode_html(decode_html($picklistvalues[$i])));
			$values[$i]['fieldcontent'] = $picklistvalues[$i];
			$values[$i]['picklistvalueid'] = $i;
			$values[$i]['type'] = 'LBL_PICKLIST';
		}
		return $values;
	}
    /**
     * Function which will get the field color for list view
     * @return listfieldcolor array
     */
    public static function getFieldColorForListView($fieldid,$picklistvalue) {
        $db = PearDatabase::getInstance();
		$listfieldcolor = array();
        $query = 'SELECT listcolor FROM berli_listview_colors  WHERE listfieldid =? AND fieldcontent =?';
        $result = $db->pquery($query, array($fieldid,$picklistvalue));
		if ($result && $db->num_rows($result) > 0) {
			$listfieldcolor['listcolor'] = $db->query_result($result,0,'listcolor');
		}
		else {
			$listfieldcolor['listcolor'] = '';
		}
		return $listfieldcolor;
	}
	/**
     * Function which will set the field color for checkbox fields
     * @return value array
     */
    public static function getColorForCheckBoxFields($fieldModel) {
		$values = array();
		$checkboxvalues = array('0','1');
		for($i = 0; $i < count($checkboxvalues); $i++) {
			$values[$i] = self::getFieldColorForListView($fieldModel->get('id'),$checkboxvalues[$i]);
			$values[$i]['fieldcontent'] = $checkboxvalues[$i];
			$values[$i]['picklistvalueid'] = $i;
			$values[$i]['type'] = 'LBL_CHECKBOX';
		}
		return $values;
	}
}