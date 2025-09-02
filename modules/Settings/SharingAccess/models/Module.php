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
 * Sharng Access Vtiger Module Model Class
 */
class Settings_SharingAccess_Module_Model extends Vtiger_Module_Model {

	/**
	 * Constants for mapping module's Sharing Access permissions editable
	 */
	const EDITABLE = 0;
	const READONLY = 1;
	const HIDDEN = 2;

	/**
	 * Constants used for mapping module's Sharing Access Permission
	 */
	const SHARING_ACCESS_READ_ONLY = 0;
	const SHARING_ACCESS_READ_CREATE = 1;
	const SHARING_ACCESS_PUBLIC = 2;
	const SHARING_ACCESS_PRIVATE = 3;

	public function getPermissionValue() {
		return $this->get('permission');
	}

	/**
	 * Function checks if the sharing access for the module is enabled or not
	 * @return <Boolean>
	 */
	public function isSharingEditable() {
		return ($this->get('editstatus') == self::EDITABLE);
	}

	/**
	 * Function checks if the module is Private
	 * @return Boolean
	 */
	public function isPrivate() {
		return ((int)$this->get('permission') == self::SHARING_ACCESS_PRIVATE);
	}

	/**
	 * Function checks if the module is Public
	 * @return Boolean
	 */
	public function isPublic() {
		return ($this->get('editstatus') == self::SHARING_ACCESS_PUBLIC);
	}

	public function getRulesListUrl() {
		return '?module=SharingAccess&parent=Settings&view=IndexAjax&mode=showRules&for_module='.$this->getId();
	}

	public function getCreateRuleUrl() {
		return '?module=SharingAccess&parent=Settings&view=IndexAjax&mode=editRule&for_module='.$this->getId();
	}

	public function getSharingRules() {
		return Settings_SharingAccess_Rule_Model::getAllByModule($this);
	}

	public function getRules() {
		return Settings_SharingAccess_Rule_Model::getAllByModule($this);
	}

	public function save() {
		// prevent issue with Accounts & Contacts relation that would set NULL value for Contacts if Accounts is disabled and create a broken array
		if ($this->get('permission') != '' && $this->get('permission') != NULL) {
			$db = PearDatabase::getInstance();

			$sql = 'UPDATE vtiger_def_org_share SET permission = ? WHERE tabid = ?';
			$params = array($this->get('permission'), $this->getId());
			$db->pquery($sql, $params);
		}
	}

	/**
	 * Static Function to get the instance of Vtiger Module Model for the given id or name
	 * @param mixed id or name of the module
	 */
	public static function getInstance($value) {
		$db = PearDatabase::getInstance();
		$instance = false;

		$query = false;
		if(Vtiger_Utils::isNumber($value)) {
			$query = 'SELECT * FROM vtiger_def_org_share INNER JOIN vtiger_tab ON vtiger_tab.tabid = vtiger_def_org_share.tabid WHERE vtiger_tab.tabid=?';
		} else {
			$query = 'SELECT * FROM vtiger_def_org_share INNER JOIN vtiger_tab ON vtiger_tab.tabid = vtiger_def_org_share.tabid WHERE name=?';
		}
		$result = $db->pquery($query, array($value));
		if($db->num_rows($result)) {
			$row = $db->query_result_rowdata($result, 0);
			$instance = new Settings_SharingAccess_Module_Model();
			$instance->initialize($row);
			$instance->set('permission', $row['permission']);
			$instance->set('editstatus', $row['editstatus']);
		}
		return $instance;
	}

	/**
	 * Static Function to get the instance of Vtiger Module Model for all the modules
	 * @return <Array> - List of Vtiger Module Model or sub class instances
	 */
	public static function getAll($editable=false, $b=false) {
		$db = PearDatabase::getInstance();

		$moduleModels = array();

		$query = 'SELECT * FROM vtiger_def_org_share INNER JOIN vtiger_tab ON vtiger_tab.tabid = vtiger_def_org_share.tabid WHERE vtiger_tab.presence IN (0,2)';
		$params = array();
		if($editable) {
			$query .= ' AND editstatus = ?';
			array_push($params, self::EDITABLE);
		}
		$result = $db->pquery($query, $params);
		$noOfModules = $db->num_rows($result);
		for($i=0; $i<$noOfModules; ++$i) {
			$row = $db->query_result_rowdata($result, $i);
			$instance = new Settings_SharingAccess_Module_Model();
			$instance->initialize($row);
			$instance->set('permission', $row['permission']);
			$instance->set('editstatus', $row['editstatus']);
			$moduleModels[$row['tabid']] = $instance;
		}
		return $moduleModels;
	}
	
	/**
	 * Static Function to get the instance of Vtiger Module Model for all the modules
	 * @return <Array> - List of Vtiger Module Model or sub class instances
	 */
	public static function getDependentModules() {
		$dependentModulesList = array();
		$dependentModulesList['Accounts'] = array('Potentials', 'HelpDesk', 'Quotes', 'SalesOrder', 'Invoice');

		return $dependentModulesList;
	}
	/**
	 * Function recalculate the sharing rules
	 */
	public static function recalculateSharingRules() {
		set_time_limit(vglobal('php_max_execution_time'));
		$db = PearDatabase::getInstance();

		require_once('modules/Users/CreateUserPrivilegeFile.php');
		$result = $db->pquery('SELECT id FROM vtiger_users WHERE deleted = ?', array(0));
		$numOfRows = $db->num_rows($result);

		for($i=0; $i<$numOfRows; $i++) {
			createUserSharingPrivilegesfile($db->query_result($result, $i, 'id'));
		}
	}

}
