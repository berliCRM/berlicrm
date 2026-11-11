<?php

class Users_SaveCommentDisplay_Action extends Vtiger_Action_Controller {
    public function validateRequest(Vtiger_Request $request): bool {
        return $request->validateWriteAccess();
    }

    public function checkPermission(Vtiger_Request $request) {
        if (!Users_Record_Model::getCurrentUserModel()->getId()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
        }
    }

    public function process(Vtiger_Request $request) {
        $response = new Vtiger_Response();
        $expanded = $request->get('expanded');
        if (!in_array($expanded, array('0', '1', 0, 1), true)) {
            $response->setError('INVALID_VALUE', vtranslate('LBL_INVALID_VALUE', 'Vtiger'));
        } else {
            try {
                Users_CommentDisplay_Model::saveExpanded((bool) $expanded);
                $response->setResult(array('expanded' => (bool) $expanded));
            } catch (Exception $e) {
                $response->setError('SAVE_FAILED', vtranslate('LBL_COMMENT_DISPLAY_SAVE_FAILED', 'Vtiger'));
            }
        }
        $response->emit();
    }
}
