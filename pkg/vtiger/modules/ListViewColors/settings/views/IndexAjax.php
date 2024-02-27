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
class Settings_ListViewColors_IndexAjax_View extends Settings_Vtiger_IndexAjax_View {

    function __construct() {
        parent::__construct();
        $this->exposeMethod('getColorFieldDetailsForModule');
 		$this->exposeMethod('getColorFieldsForModule');
		$this->exposeMethod('getColorValuesForField');
        $this->exposeMethod('saveColorValuesForField');
		$this->exposeMethod('getSupportedUITypes');
    }

    public function process(Vtiger_Request $request) {
        $mode = $request->get('mode');
        if($this->isMethodExposed($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }

	
	//provides the content of the select field based on selected module
	public function getColorFieldsForModule(Vtiger_Request $request) {
        $sourceModule = $request->get('source_module');
		$moduleInstance = Vtiger_Module_Model::getInstance($sourceModule);
		$moduleFieldInstances = $moduleInstance->getFields();
		foreach ($moduleFieldInstances as $fieldobj) {
			$uitype = $fieldobj->get ('uitype');
			if (in_array($uitype, self::getSupportedUITypes())) {
				//exclude: firstname attached to salutation on uitype 55, isconvertedfromlead from Contacts
				if ($fieldobj->get('name') != 'firstname' AND $fieldobj->get('name') != 'isconvertedfromlead') {
				$fieldinfo [] = array ('fieldid'=>$fieldobj->get ('id'), 'fieldname'=>$fieldobj->get('name'),'fieldlabel'=>$fieldobj->get ('label'),'sourceModule'=>$sourceModule);
				}
			}
		}
        $qualifiedName = $request->getModule(false);

        $viewer = $this->getViewer($request);
        $viewer->assign('MODULE_FIELDS',$fieldinfo);
		$viewer->assign('SELECTED_MODULE_NAME',$sourceModule);
		$viewer->assign('QUALIFIED_MODULE',$qualifiedName);
        $viewer->view('colorFieldDetails.tpl',$qualifiedName);
		
	}
	
    public function getColorValuesForField(Vtiger_Request $request) {
		$sourceModule = $request->get('source_module');
		$selectedFieldId = $request->get('selectedField');
		$fieldModel = Settings_ListViewColors_Field_Model::getInstance($selectedFieldId);
		$fieldlabel = $fieldModel->get('label');
		$fieldname = $fieldModel->get('name');
		$moduleName = $request->getModule();
        $qualifiedName = $request->getModule(false);
		
        $viewer = $this->getViewer($request);
		$viewer->assign('SELECTED_MODULE_NAME',$sourceModule);
		$viewer->assign('MODULE',$moduleName);
		$viewer->assign('FIELDLABEL',$fieldlabel);
		$viewer->assign('FIELDMODEL',$fieldModel);
		$viewer->assign('QUALIFIED_MODULE',$qualifiedName);
		$viewer->view('ColorFieldsValueDetail.tpl',$qualifiedName);
   }
   
    public function saveColorValuesForField(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$recordValue = $request->get('recordValue');
		$selectedColor = $request->get('selectedColor');
		$selectedField = $request->get('selectedField');
		$selectedFieldarr = explode("_",$selectedField);
		$selectedFieldid = $selectedFieldarr[1];
		$selectedValue = $request->get('selectedValue');
		$selectedValuearr = explode("_",$selectedValue);
		$selectedpicklistid = $selectedValuearr[1];
		$sourceModule = $request->get('source_module');
		$tabid = getTabid($sourceModule);
		if ($selectedColor == '') {
			$sql = "DELETE FROM berli_listview_colors WHERE listfieldid = ?  AND fieldcontent =?";
			$params = array($selectedFieldid, $recordValue);
			$result = $db->pquery($sql, $params);
			exit;
		}
		$sql = "DELETE FROM berli_listview_colors WHERE listfieldid = ?  AND fieldcontent = ?";
		$params = array($selectedFieldid,  $recordValue);
		$db->pquery($sql, $params);
		$sql = "INSERT INTO berli_listview_colors(listfieldid, listcolor, fieldcontent) VALUES (?,?,?)";
		$params = array($selectedFieldid,  $selectedColor,  $recordValue);
		$db->pquery($sql, $params);
	}
	
	//list of supported UI types
	// 15,16,55 picklists
	// 56 checkbox
    public static function getSupportedUITypes() {
        $supporteduis = array (15,16,55,56);
		return $supporteduis;
    }
}