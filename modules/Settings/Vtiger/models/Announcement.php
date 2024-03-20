<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_Vtiger_Announcement_Model extends Vtiger_Base_Model {
    
    const tableName  = 'vtiger_announcement';
    
    
    public function save() {
        $db = PearDatabase::getInstance();
        $currentUserId = Users_Record_Model::getCurrentUserModel()->getId();
        $dbTime = $db->formatDate(date('Y-m-d H:i:s'),true);
        $announcement = html_entity_decode($this->get('announcement'));
        $q = 'INSERT INTO '.self::tableName.' SET creatorid=?,announcement=?,title=?,time=? ON DUPLICATE KEY UPDATE announcement=?,time=?,title=?';
        $db->pquery($q,array($currentUserId,$announcement,"announcement",$dbtime,$announcement,$dbtime,"announcement"));
    }
    
    public static function getInstanceByCreator(Users_Record_Model $user) {
        $db = PearDatabase::getInstance();
        $query = 'SELECT * FROM '.self::tableName.' WHERE creatorid=?';
        $result = $db->pquery($query,array($user->getId()));
        $instance = new self();
        if($db->num_rows($result) > 0) {
            $row = $db->query_result_rowdata($result,0);
            $instance->setData($row);
        }
        return $instance;
    }
}