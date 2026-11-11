<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0.
 *************************************************************************************/

require_once 'include/InventoryPdfGenerator.php';

/** Save the global first-page and continuation-page PDF letterheads. */
class Pdfsettings_SaveLetterhead_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
        if (!Users_Record_Model::getCurrentUserModel()->isAdminUser()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
        }
    }

    public function validateRequest(Vtiger_Request $request): bool
    {
        return $request->validateWriteAccess();
    }

    public function process(Vtiger_Request $request)
    {
        $user = Users_Record_Model::getCurrentUserModel();
        InventoryPdfLetterheadSettings::save(
            $request->get('first_document_id'),
            $request->get('continuation_document_id'),
            $user->getId()
        );
        InventoryPdfLetterheadResolver::resetCache();

        $response = new Vtiger_Response();
        $response->setResult(array('success' => true));
        $response->emit();
    }
}
