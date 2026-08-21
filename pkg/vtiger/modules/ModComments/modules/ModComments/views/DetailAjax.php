<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ModComments_DetailAjax_View extends Vtiger_IndexAjax_View {

    public function process(Vtiger_Request $request) {
        $record = $request->get('record');
        $moduleName = $request->getModule();
        $recordModel = ModComments_Record_Model::getInstanceById($record);
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $modCommentsModel = Vtiger_Module_Model::getInstance('ModComments');
        $parentRecordId = $recordModel->get('related_to');
        $parentModuleName = $moduleName;
        if (!empty($parentRecordId)) {
            $detectedParentModuleName = getSalesEntityType($parentRecordId);
            if (!empty($detectedParentModuleName)) {
                $parentModuleName = $detectedParentModuleName;
            }
        }

        $viewer = $this->getViewer($request);
        $viewer->assign('CURRENTUSER', $currentUserModel);
        $viewer->assign('COMMENT', $recordModel);
        $viewer->assign('COMMENTS_MODULE_MODEL', $modCommentsModel);
        $viewer->assign('MODULE_NAME', $parentModuleName);
        $viewer->assign('CREATE_PERMISSION', $modCommentsModel->isPermitted('CreateView'));
        $viewer->assign('EDIT_PERMISSION', $modCommentsModel->isPermitted('EditView'));

        global $modCommentsColors;
        $viewer->assign('COMMENTS_COLORS', $modCommentsColors);

        if ($parentModuleName === 'HelpDesk' && !empty($parentRecordId)) {
            $viewer->assign('COMMENT_NUMBERS', ModComments_Record_Model::getCommentNumbersByParentRecord($parentRecordId));
        }

        echo $viewer->view('Comment.tpl', $parentModuleName, true);
    }
}
