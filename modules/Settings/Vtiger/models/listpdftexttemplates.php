<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_Vtiger_listpdftexttemplates_Model extends Settings_Vtiger_Module_Model {

	var $baseTable_conclusion = 'berli_multiendtext';
	var $baseTable_letter = 'berli_multistarttext';
	var $baseIndex = 'endtextid';
	var $listFields = array('starttexttitle','endtexttitle');
	var $nameFields = array('starttexttitle','endtexttitle');
	var $texttype_array= array('0'=>'Quotes','1'=>'PurchaseOrder','2'=>'SalesOrder', '3'=>'Invoice');
	var $templatemodules = array ('quotes','invoices','sorders','porders');
	var $textrelation = array ('0'=>'LETTER','1'=>'CONCLUSION');

	var $fields = array(
			'endtexttitle' => 'text',
			'multietext' => 'textarea',
			'starttexttitle' => 'text',
			'multistext' => 'textarea'
	);
	
	/**
	 * Function to get text relation
	 * @return <Array>
	 */
	public function getTextRelations() {
		return $this->textrelation;
	}
	/**
	 * Function to get text types
	 * @return <Array>
	 */
	public function getTextTypes() {
		return $this->texttype_array;
	}
	/**
	 * Function to get template modules
	 * @return <Array>
	 */
	public function getTemplateModules() {
		return $this->templatemodules;
	}
	
	/**
	 * Function to get Edit view Url
	 * @return <String> Url
	 */
	public function getEditViewUrl() {
		return 'index.php?module=Vtiger&parent=Settings&view=createpdfstexttemplate';
	}
	
	/**
	 * Function to get CompanyDetails Menu item
	 * @return menu item Model
	 */
	public function getMenuItem() {
		$menuItem = Settings_Vtiger_MenuItem_Model::getInstance('LBL_COMPANY_DETAILS');
		return $menuItem;
	}
	
	/**
	 * Function to get Index view Url
	 * @return <String> URL
	 */
	public function getIndexViewUrl() {
		$menuItem = $this->getMenuItem();
		return 'index.php?module=Vtiger&parent=Settings&view=CompanyDetails&block='.$menuItem->get('blockid').'&fieldid='.$menuItem->get('fieldid');
	}

	/**
	 * Function to get fields
	 * @return <Array>
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * Function to get the Module Model
	 * @return Vtiger_Module_Model instance
	 */
	public function getModule() {
		return $this->get('module');
	}


	/**
	 * Function to save the Company details
	 */
	public function save() {
		$db = PearDatabase::getInstance();
		$id = $this->get('id');
		$fieldsList = $this->getFields();
		$tableName = $this->baseTable_letter;

		if ($id) {
			$params = array();

			$query = "UPDATE $tableName SET ";
			foreach ($fieldsList as $fieldName => $fieldType) {
				$query .= " $fieldName = ?, ";
				array_push($params, $this->get($fieldName));
			}
			$query .= "  WHERE starttextid = ?";

			array_push($params, $id);
		} else {
			$params = $this->getData();

			$query = "INSERT INTO $tableName (";
			foreach ($fieldsList as $fieldName => $fieldType) {
				$query .= " $fieldName,";
			}
			$query .= " starttextid) VALUES (". generateQuestionMarks($params). ", ?)";

			array_push($params, $db->getUniqueID($this->baseTable_letter));
		}
		$db->pquery($query, $params);
	}

	/**
	 * Function to get the instance of Company details module model
	 * @return <Settings_Vtiger_CompanyDetais_Model> $moduleModel
	 */
	public static function getInstance($name='Settings:Vtiger') {
		$moduleModel = new self();
		$db = PearDatabase::getInstance();

		$result = $db->pquery("SELECT * FROM berli_multistarttext", array());
		if ($db->num_rows($result) == 1) {
			$moduleModel->setData($db->query_result_rowdata($result));
			$moduleModel->set('id', $moduleModel->get('starttextid'));
		}

		$moduleModel->getFields();
		return $moduleModel;
	}
        
        /** 
        * @var array(string => string) 
        */ 
       private static $settings = array();  

       /** 
        * @param string $fieldname 
        * @return string 
        */ 
       public static function getSetting($fieldname) { 
            global $adb; 
            if (!self::$settings) { 
                    self::$settings = $adb->database->GetRow("SELECT * FROM berli_multistarttext"); 
            } 
            return self::$settings[$fieldname]; 
       } 
		/**
		* Function to get list of existing letter text
		* @return <Array>
		*/
		public static function getLetterText($id='') { 
			// get data for letter
			global $adb;
			$return_data_letter = array ();
			$x=0;
			if ($id=='') {
				$query ="SELECT * FROM berli_multistarttext  order by starttextid DESC";
				$result = $adb->pquery($query, array());
			}
			else {
				$query ="SELECT * FROM berli_multistarttext where starttextid=?";
				$result = $adb->pquery($query, array($id));
			}
			while ($temprow = $adb->fetch_array($result)) {
				  $return_data_letter[$x]['templatename'] = $temprow["starttexttitle"];
				  $return_data_letter[$x]['templateid'] = $temprow["starttextid"];
				  $return_data_letter[$x]['multistext'] = $temprow["multistext"];
				  $return_data_letter[$x]['texttypes'] = $temprow["texttypes"];
				$x++;
			}
			return $return_data_letter;
		}
		/**
		* Function to get list of existing conclusion text
		* @return <Array>
		*/
		public static function getConclusionText($id='') { 
			// get data for conclusion
			global $adb;
			$return_data_conclusion = array ();
			$x=0;
			if ($id=='') {
				$query ="SELECT * FROM berli_multiendtext  order by endtextid DESC";
				$result = $adb->pquery($query, array());
			}
			else {
				$query ="SELECT * FROM berli_multiendtext where endtextid=?";
				$result = $adb->pquery($query, array($id));
			}
			while ($temprow = $adb->fetch_array($result)) {
				  $return_data_conclusion[$x]['templatename'] = $temprow["endtexttitle"];
				  $return_data_conclusion[$x]['templateid'] = $temprow["endtextid"];
				  $return_data_conclusion[$x]['multietext'] = $temprow["multietext"];
				  $return_data_conclusion[$x]['texttypes'] = $temprow["texttypes"];
				$x++;
			}
			return $return_data_conclusion;
		}
	   
	   
}