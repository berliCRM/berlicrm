<?php
class Settings_ModComments_save_Action extends Settings_Vtiger_Index_Action {

    // To save comment enabled modules
    public function process(Vtiger_Request $request) {

        require_once("modules/ModComments/ModComments.php");
        
        $allModules = Settings_ModuleManager_Module_Model::getEntityModules();   
        $enable = $request->get("commentsenabled");

        foreach ($allModules as $mod) {
            if($mod->name != "ModComments") {
                if (Vtiger_Module_Model::getInstance($mod->name)->isCommentEnabled() && !isset($enable[$mod->name])) {
                    ModComments::removeWidgetFrom($mod->name);
                }
                elseif (!Vtiger_Module_Model::getInstance($mod->name)->isCommentEnabled() && isset($enable[$mod->name])) {
                    ModComments::addWidgetTo($mod->name);
                }
            }
        }

        header("Location: index.php?parent=Settings&module=ModComments&view=Edit&saved=1");
    }
}
