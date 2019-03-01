<?php

class Settings_ModComments_Edit_View extends Settings_Vtiger_Index_View {

	public function process(Vtiger_Request $request) {

        $allModules = Settings_ModuleManager_Module_Model::getEntityModules();
        $qualifiedModuleName = $request->getModule(false);

        // search through modules, remove ModComments, Calendar/Events and PBXManager, get module name translation and isCommentEnabled-flag
        foreach ($allModules as $mod) {
            if($mod->name != "ModComments" && $mod->name != "Calendar" && $mod->name != "Events" && $mod->name != "PBXManager") {
                $mod->isCommentEnabled = Vtiger_Module_Model::getInstance($mod->name)->isCommentEnabled();
                $translatedMod[vtranslate($mod->name)] = $mod;
            }
        }
        // sort by translated name
        ksort($translatedMod);

        $viewer = $this->getViewer($request);
        $viewer->assign('ALL_MODULES', $translatedMod);
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->view('index.tpl',  $qualifiedModuleName);
    }
}
