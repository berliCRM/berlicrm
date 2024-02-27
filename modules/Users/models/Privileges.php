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
 * User Privileges Model Class
 */
class Users_Privileges_Model extends Users_Record_Model {

	/**
	 * Function to get the Global Read Permission for the user
	 * @return <Number> 0/1
	 */
	protected function getGlobalReadPermission() {
		$profileGlobalPermissions = $this->get('profile_global_permission');
		return $profileGlobalPermissions[Settings_Profiles_Module_Model::GLOBAL_ACTION_VIEW];
	}

	/**
	 * Function to get the Global Write Permission for the user
	 * @return <Number> 0/1
	 */
	protected function getGlobalWritePermission() {
		$profileGlobalPermissions = $this->get('profile_global_permission');
		return $profileGlobalPermissions[Settings_Profiles_Module_Model::GLOBAL_ACTION_EDIT];
	}

	/**
	 * Function to check if the user has Global Read Permission
	 * @return <Boolean> true/false
	 */
	public function hasGlobalReadPermission() {
		return ($this->isAdminUser() ||
				$this->getGlobalReadPermission() === Settings_Profiles_Module_Model::IS_PERMITTED_VALUE ||
				$this->getGlobalWritePermission() === Settings_Profiles_Module_Model::IS_PERMITTED_VALUE);
	}

	/**
	 * Function to check if the user has Global Write Permission
	 * @return <Boolean> true/false
	 */
	public function hasGlobalWritePermission() {
		return ($this->isAdminUser() || $this->getGlobalWritePermission() === Settings_Profiles_Module_Model::IS_PERMITTED_VALUE);
	}

	public function hasGlobalPermission($actionId) {
		if($actionId == Settings_Profiles_Module_Model::GLOBAL_ACTION_VIEW) {
			return $this->hasGlobalReadPermission();
		}
		if($actionId == Settings_Profiles_Module_Model::GLOBAL_ACTION_EDIT) {
			return $this->hasGlobalWritePermission();
		}
		return false;
	}

	/**
	 * Function to check whether the user has access to a given module by tabid
	 * @param <Number> $tabId
	 * @return <Boolean> true/false
	 */
	public function hasModulePermission($tabId) {
		$profileTabsPermissions = $this->get('profile_tabs_permission');
		$moduleModel = Vtiger_Module_Model::getInstance($tabId);
		return (($this->isAdminUser() || $profileTabsPermissions[$tabId] === 0) && $moduleModel->isActive());
	}

	/**
	 * Function to check whether the user has access to the specified action/operation on a given module by tabid
	 * @param <Number> $tabId
	 * @param <String/Number> $action
	 * @return <Boolean> true/false
	 */
	public function hasModuleActionPermission($tabId, $action) {
		if(!is_a($action, 'Vtiger_Action_Model')) {
			$action = Vtiger_Action_Model::getInstance($action);
		}
		$actionId = $action->getId();
		$profileTabsPermissions = $this->get('profile_action_permission');
		$moduleModel = Vtiger_Module_Model::getInstance($tabId);
		return (($this->isAdminUser() || $profileTabsPermissions[$tabId][$actionId] === Settings_Profiles_Module_Model::IS_PERMITTED_VALUE)
				 && $moduleModel->isActive());
	}

	/**
	 * Static Function to get the instance of the User Privileges model from the given list of key-value array
	 * @param <Array> $valueMap
	 * @return Users_Privilege_Model object
	 */
	public static function getInstance($valueMap) {
		$instance = new self();
		foreach ($valueMap as $key => $value) {
			$instance->$key = $value;
		}
		$instance->setData($valueMap);
		return $instance;
	}

	/**
	 * Static Function to get the instance of the User Privileges model, given the User id
	 * @param <Number> $userId
	 * @return Users_Privilege_Model object
	 */
	public static function getInstanceById($userId, $module=null) {
		if (empty($userId))
			return null;

		require("user_privileges/user_privileges_$userId.php");
		require("user_privileges/sharing_privileges_$userId.php");

		$valueMap = array();
		$valueMap['id'] = $userId;
		$valueMap['is_admin'] = isset($is_admin) ? (bool) $is_admin : null;
		$valueMap['roleid'] = isset($current_user_roles) ? $current_user_roles : null;
		$valueMap['parent_role_seq'] = isset($current_user_parent_role_seq) ? $current_user_parent_role_seq : null;
		$valueMap['profiles'] = isset($current_user_profiles) ? $current_user_profiles : null;
		$valueMap['profile_global_permission'] = isset($profileGlobalPermission) ? $profileGlobalPermission : null;
		$valueMap['profile_tabs_permission'] = isset($profileTabsPermission) ? $profileTabsPermission : null;
		$valueMap['profile_action_permission'] = isset($profileActionPermission) ? $profileActionPermission : null;
		$valueMap['groups'] = isset($current_user_groups) ? $current_user_groups : null;
		$valueMap['subordinate_roles'] = isset($subordinate_roles) ? $subordinate_roles : null;
		$valueMap['parent_roles'] = isset($parent_roles) ? $parent_roles : null;
		$valueMap['subordinate_roles_users'] = isset($subordinate_roles_users) ? $subordinate_roles_users : null;
		$valueMap['defaultOrgSharingPermission'] = isset($defaultOrgSharingPermission) ? $defaultOrgSharingPermission : null;
		$valueMap['related_module_share'] = isset($related_module_share) ? $related_module_share : null;

		if(isset($user_info) && is_array($user_info)) {
			$valueMap = array_merge($valueMap, $user_info);
		}

		return self::getInstance($valueMap);
	}

	/**
	 * Static function to get the User Privileges Model for the current user
	 * @return Users_Privilege_Model object
	 */
	public static function getCurrentUserPrivilegesModel() {
		//TODO : Remove the global dependency
		$currentUser = vglobal('current_user');
		$currentUserId = $currentUser->id;
		return self::getInstanceById($currentUserId);
	}

	/**
	 * Function to check permission for a Module/Action/Record
	 * @param <String> $moduleName
	 * @param <String> $actionName
	 * @param <Number> $record
	 * @return Boolean
	 */
	public static function isPermitted($moduleName, $actionName, $record=false) {
		$permission = isPermitted($moduleName, $actionName, $record);
		if($permission == 'yes') {
			return true;
		}
		return false;
	}

	
	/**
	 * Function returns non admin access control check query
	 * @param <String> $module
	 * @return <String>
	 */
	public static function getNonAdminAccessControlQuery($module) {
		$currentUser = vglobal('current_user');
		return getNonAdminAccessControlQuery($module, $currentUser);
	}
}