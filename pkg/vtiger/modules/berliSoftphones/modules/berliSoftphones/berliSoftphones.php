<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by crm-now are Copyright (C) crm-now GmbH.
 * All Rights Reserved.
 *************************************************************************************/
include_once 'modules/Vtiger/CRMEntity.php';

class berliSoftphones {
	var $table_name = 'berli_softphones';
	var $table_index= 'phoneid';
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('berli_softphonescf', 'phoneid');
	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'berli_softphones', 'berli_softphonescf');
	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'berli_softphones' => 'phoneid',
		'berli_softphonescf'=>'phoneid');

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		require_once('include/utils/utils.php');			
		if($event_type == 'module.postinstall') {
			$this->createSupportedPhones();
			$this->createSettingsMenueEntry();
		} else if($event_type == 'module.disabled') {
			$this->removeSettingsLinks();
			// TODO Handle actions when this module is disabled.
			return;
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
			return;
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
			return;		
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
			return;			
		} else if($event_type == 'module.postupdate') {
			return;			
		}
	}
	
	function createSupportedPhones() {
		global $adb;
		$phones_res = $adb->pquery(
					"INSERT INTO `berli_softphones` (`phoneid`, `phonename`, `phoneprefix`, `phactive`, `phdescription`) VALUES
					(0, 'Session Initiation Protocol (SIP)', 'sip:', '', 'LBL_PHONE_SIP'),
					(1, 'startCall Option', 'startCall:', '', 'LBL_PHONE_STARTCALL'),
					(2, 'X-LIGHT', 'sip:', '', 'LBL_PHONE_XLIGTH'),
					(3, 'Phoner', 'phoner:', '', 'LBL_PHONE_PHONER'),
					(4, 'Efftel', 'callto:', '', 'LBL_PHONE_EFFTEL'),
					(5, 'nfon', 'tel:', '', 'LBL_PHONE_NFON'),
					(6, 'Zoiper', 'callto:', '', 'LBL_PHONE_ZOIPER');",	array()
		);
	}
	
	function createSettingsMenueEntry(){
		$db = PearDatabase::getInstance();
		$fieldid = $db->getUniqueID('vtiger_settings_field');
		$blockid = getSettingsBlockId('LBL_OTHER_SETTINGS');
		$seq_res = $db->pquery("SELECT max(sequence) AS max_seq FROM vtiger_settings_field WHERE blockid = ?", array($blockid));
		if ($db->num_rows($seq_res) > 0) {
			$cur_seq = $db->query_result($seq_res, 0, 'max_seq');
			if ($cur_seq != null)	$seq = $cur_seq + 1;
		}

		$result=$db->pquery('SELECT 1 FROM vtiger_settings_field WHERE name=?',array('Softphones'));
		if(!$db->num_rows($result)){
			$db->pquery('INSERT INTO vtiger_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence)
				VALUES (?,?,?,?,?,?,?)', array($fieldid, $blockid, 'Softphones' , '', 'LBL_SOFTPHONES_SETUP_DESCRIPTION', 'index.php?module=berliSoftphones&parent=Settings&view=Index', $seq));
		}			
	}
    /**
     * To delete Settings link
    */
    function removeSettingsLinks(){
		global $log;
		$db = PearDatabase::getInstance();
        $db->pquery('DELETE FROM vtiger_settings_field WHERE name=?', array($this->LBL_SOFTPHONES));
        $log->fatal('Settings Field Removed');
        
    }
	public function getNonAdminAccessControlQuery($module, $user,$scope='') {
		require('user_privileges/user_privileges_'.$user->id.'.php');
		require('user_privileges/sharing_privileges_'.$user->id.'.php');
		$query = ' ';
		$tabId = getTabid($module);
		if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2]
				== 1 && $defaultOrgSharingPermission[$tabId] == 3) {
			$tableName = 'vt_tmp_u'.$user->id.'_t'.$tabId;
			$sharingRuleInfoVariable = $module.'_share_read_permission';
			$sharingRuleInfo = $$sharingRuleInfoVariable;
			$sharedTabId = null;
			$this->setupTemporaryTable($tableName, $sharedTabId, $user,
					$current_user_parent_role_seq, $current_user_groups);

			$sharedUsers = $this->getListViewAccessibleUsers($user->id);
            // we need to include group id's in $sharedUsers list to get the current user's group records
            if($current_user_groups){
                $sharedUsers = $sharedUsers.','. implode(',',$current_user_groups);
            }
			$query = " INNER JOIN $tableName $tableName$scope ON ($tableName$scope.id = ".
					"vtiger_crmentity$scope.smownerid and $tableName$scope.shared=0 and $tableName$scope.id IN ($sharedUsers)) ";
		}
		return $query;
	}

}
?>