<?php

class Users_ExportData_Action extends Vtiger_ExportData_Action{

	public static $headers = array(
									'user_name' => 'User Name',
									'title' => 'Title',
									'first_name' => 'First Name',
									'last_name' => 'Last Name',
									'email1' => 'Email',
									'email2' => 'Other Email',
									'secondaryemail' => 'Secondary Email',
									'phone_work' => 'Office Phone',
									'phone_mobile' => 'Mobile',
									'phone_fax' => 'Fax',
									'address_street' => 'Street',
									'address_city' => 'City',
									'address_state' => 'State', 
									'address_country' => 'Country',
									'address_postalcode' => 'Postal Code'
									);
  /**
   * Function exports the data based on the mode
   * @param Vtiger_Request $request
   */
  function ExportData(Vtiger_Request $request) {
    global $adb;
    $moduleName = $request->get('source_module');

    $this->moduleInstance = Vtiger_Module_Model::getInstance($moduleName);
    $this->moduleFieldInstances = $this->moduleInstance->getFields();
    $this->focus = CRMEntity::getInstance($moduleName);
    $query = $this->getExportQuery($request);
    $result = $adb->pquery($query, array());
    $headers = array_values(self::$headers);
    foreach($headers as $header){
      $translatedHeaders[]=vtranslate(html_entity_decode($header, ENT_QUOTES), $moduleName);
    }
    $entries = array();
	while ($row = $adb->getNextRow($result, false)) {
		$tmp = array();
		foreach (self::$headers AS $columnName => $label) {
			$tmp[] = $row[$columnName];
		}
		$entries[] = $tmp;
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
    $cvId = $request->get('viewname');
    $moduleName = $request->get('source_module');

    $queryGenerator = new QueryGenerator($moduleName, $currentUser);
    if(!empty($cvId)){
    $queryGenerator->initForCustomViewById($cvId);
  }
    $acceptedFields = array_keys(self::$headers);
    $queryGenerator->setFields($acceptedFields);
    $query = $queryGenerator->getQuery();
    return $query;
  }
}
