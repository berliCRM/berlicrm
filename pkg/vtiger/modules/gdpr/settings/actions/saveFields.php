<?php
class Settings_gdpr_saveFields_Action extends Settings_Vtiger_Index_Action {

    // To save gdpr module fields settings
    public function process(Vtiger_Request $request) {

    // echo "<pre>";var_dump($_REQUEST);echo "</pre>";die;

        $db = PearDatabase::getInstance();
        $modules = $request->get("gdprRelevantModule");
        $delmodes = $request->get("pickListDelete");
        $fields = $request->get("gdprFields");
        
        $setting_date = date("Y-m-d H:i:s");
        
        foreach ($modules as $tabid => $enabled) {
            
            if ($enabled > 0) {
                $fieldids = "";
                if (is_array($fields[$tabid])) {
                    $fieldids = implode(";",$fields[$tabid]);
                }
                $q = "INSERT INTO berli_dsgvo_module SET setting_date =?, tabid=?, deletion_mode=?, fieldids=?";
                $db->pquery($q,array($setting_date, $tabid, $delmodes[$tabid], $fieldids));
            }
        }
        header("Location: index.php?parent=Settings&module=gdpr&view=Index");
    }
}
