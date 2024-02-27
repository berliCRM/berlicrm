<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Vtiger JS Script Model Class
 */
class Vtiger_JsScript_Model extends Vtiger_Base_Model {

	const DEFAULT_TYPE = 'text/javascript';

    // Define an array to store dynamic properties
    private $dynamicProperties = [];

    // __set magic method to handle dynamic property assignment
    public function __set($name, $value) {
        $this->dynamicProperties[$name] = $value;
    }
	
    // __get magic method to retrieve dynamic properties
    public function __get($name) {
        return isset($this->dynamicProperties[$name]) ? $this->dynamicProperties[$name] : null;
    }

    // Getter method for dynamicProperties
    public function getDynamicProperties() {
        return $this->dynamicProperties;
    }

    public function setData($values) {
        // Merge declared and dynamic properties
        $allProperties = array_merge(get_object_vars($this), $this->dynamicProperties, $values);

        // Set the merged properties to valueMap
        $this->valueMap = $allProperties;

        return $this;
    }

	/**
	 * Function to get the type attribute value
	 * @return <String>
	 */
	public function getType() {
		$type = $this->get('type');
		if(empty($type)){
			$type = self::DEFAULT_TYPE;
		}
		return $type;
	}

	/**
	 * Function to get the src attribute value
	 * @return <String>
	 */
	public function getSrc() {
		$src = $this->get('src');
		if(empty($src)) {
            $src = $this->get('linkurl');
		}
		return $src;
	}

	/**
	 * Static Function to get an instance of Vtiger JsScript Model from a given Vtiger_Link object
	 * @param Vtiger_Link $linkObj
	 * @return Vtiger_JsScript_Model instance
	 */

	public static function getInstanceFromLinkObject(Vtiger_Link $linkObj) {
        $objectProperties = get_object_vars($linkObj);
        $linkModel = new self();
        foreach ($objectProperties as $propertyName => $propertyValue) {
           $linkModel->dynamicProperties[$propertyName] = $propertyValue;
 
        }
        return $linkModel->setData($objectProperties);
    }
    
}