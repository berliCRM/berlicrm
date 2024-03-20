<?php
class Settings_gdpr_setGdprParameter_Action extends Settings_Vtiger_Index_Action {

    // To save gdpr settings
    public function process(Vtiger_Request $request) {
        $db = PearDatabase::getInstance();
        $response = new Vtiger_Response();

        $picklistid = $request->get("picklistid");
        $value = $request->get("val");

        if ($picklistid == "globalMode") {
            if ($value == "a" || $value == "m" || $value == "d") {
                $q = "UPDATE berli_dsgvo_global SET op_mode=?";
                $db->pquery($q,array($value));
                $response->setResult(array());
            }
            else {
                $response->setError(array());
            }
        }
        elseif ($picklistid == "noti_time") {
            $seconds = (int) $value;
            if ($seconds > 0) {
                $q = "UPDATE berli_dsgvo_global SET del_note_time_days=?";
                $db->pquery($q,array($seconds));
                $response->setResult(array());
            }
            else {
                $response->setError(array());
            }
        }                    
        elseif ($picklistid == "pickListDelete") {
            if ($value == "0" || $value == "1") {
                $q = "UPDATE berli_dsgvo_global SET del_mode=?";
                $db->pquery($q,array($value));
                $response->setResult(array());
            }
            else {
                $response->setError(array());
            }
        }
        else {
            $response->setError(array());                          
        }

        $response->emit();
    }
}
