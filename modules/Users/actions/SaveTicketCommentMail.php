<?php

class Users_SaveTicketCommentMail_Action extends Vtiger_Action_Controller
{
    public function validateRequest(Vtiger_Request $request): bool
    {
        return $request->validateWriteAccess();
    }

    public function checkPermission(Vtiger_Request $request)
    {
        if (!Users_Record_Model::getCurrentUserModel()->getId()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
        }
    }

    public function process(Vtiger_Request $request)
    {
        $response = new Vtiger_Response();
        $enabled = $request->get('enabled');
        if (!in_array($enabled, array('0', '1', 0, 1), true)) {
            $response->setError('INVALID_VALUE', vtranslate('LBL_INVALID_VALUE', 'Vtiger'));
        } else {
            try {
                Users_TicketCommentMail_Model::saveAutomaticCc((bool) $enabled);
                $response->setResult(array('enabled' => (bool) $enabled));
            } catch (Exception $e) {
                $response->setError('SAVE_FAILED', vtranslate('LBL_HELPDESK_AUTO_CC_SAVE_FAILED', 'HelpDesk'));
            }
        }
        $response->emit();
    }
}
