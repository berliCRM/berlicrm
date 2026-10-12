<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Class Settings_Vtiger_UpdateCompanyLogo_Action
 *
 * Handles secure upload and update of the company logo in CRM settings.
 *
 * Security measures implemented:
 * - Validates upload error state
 * - Ensures file was uploaded via HTTP POST
 * - Restricts allowed extensions to jpg, jpeg, png
 * - Validates MIME type using finfo (authoritative check)
 * - Verifies actual image type via getimagesize()
 * - Re-encodes the image using GD to prevent polyglot / embedded payload attacks
 * - Rejects corrupted or invalid images
 *
 * Supported formats:
 * - image/jpeg
 * - image/png
 *
 * @package Settings
 * @subpackage Vtiger
 */
class Settings_Vtiger_UpdateCompanyLogo_Action extends Settings_Vtiger_Basic_Action {

    /**
     * Processes the company logo upload request.
     *
     * Performs strict validation of the uploaded file and re-encodes
     * the image before saving it to prevent malicious payload injection.
     *
     * @param Vtiger_Request $request
     * @return void
     */
    public function process(Vtiger_Request $request) {
        $moduleModel = Settings_Vtiger_CompanyDetails_Model::getInstance();

        if (empty($_FILES['logo']) || !is_array($_FILES['logo'])) {
            return $this->redirectWithError($moduleModel, 'LBL_INVALID_IMAGE');
        }

        $logo = $_FILES['logo'];

        // 1) Upload error check
        if (!isset($logo['error']) || $logo['error'] !== UPLOAD_ERR_OK) {
            return $this->redirectWithError($moduleModel, 'LBL_INVALID_IMAGE');
        }

        if (empty($logo['tmp_name']) || !is_uploaded_file($logo['tmp_name'])) {
            return $this->redirectWithError($moduleModel, 'LBL_IMAGE_CORRUPTED');
        }

        // 2) Extension whitelist (secondary validation)
        $ext = strtolower(pathinfo($logo['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            return $this->redirectWithError($moduleModel, 'LBL_INVALID_IMAGE');
        }

        // 3) MIME validation (authoritative)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($logo['tmp_name']);

        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            return $this->redirectWithError($moduleModel, 'LBL_INVALID_IMAGE');
        }

        // 4) Validate real image type
        $imgInfo = @getimagesize($logo['tmp_name']);
        if ($imgInfo === false) {
            return $this->redirectWithError($moduleModel, 'LBL_INVALID_IMAGE');
        }

        $imageType = (int) $imgInfo[2];
        if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            return $this->redirectWithError($moduleModel, 'LBL_INVALID_IMAGE');
        }

        // 5) Re-encode image (strong protection against embedded payloads)
        if ($imageType === IMAGETYPE_JPEG) {
            $img = @imagecreatefromjpeg($logo['tmp_name']);
        } else {
            $img = @imagecreatefrompng($logo['tmp_name']);
        }

        if ($img === false) {
            return $this->redirectWithError($moduleModel, 'LBL_IMAGE_CORRUPTED');
        }

        $tmpReencoded = tempnam(sys_get_temp_dir(), 'vtlogo_');
        if ($tmpReencoded === false) {
            imagedestroy($img);
            return $this->redirectWithError($moduleModel, 'LBL_IMAGE_CORRUPTED');
        }

        // Save based on detected type
        if ($imageType === IMAGETYPE_JPEG) {
            $success = imagejpeg($img, $tmpReencoded, 90);
            $finalName = 'companylogo.jpg';
            $finalMime = 'image/jpeg';
        } else {
            $success = imagepng($img, $tmpReencoded, 6);
            $finalName = 'companylogo.png';
            $finalMime = 'image/png';
        }

        imagedestroy($img);

        if (!$success) {
            @unlink($tmpReencoded);
            return $this->redirectWithError($moduleModel, 'LBL_IMAGE_CORRUPTED');
        }

        // Replace uploaded temp file with sanitized version
        $_FILES['logo']['tmp_name'] = $tmpReencoded;
        $_FILES['logo']['type'] = $finalMime;

        // Persist logo via model
        $moduleModel->saveLogo();
        $moduleModel->set('logoname', $finalName);
        $moduleModel->save();

        @unlink($tmpReencoded);

        header('Location: ' . $moduleModel->getIndexViewUrl());
    }

    /**
     * Redirects back to company settings view with an error parameter.
     *
     * @param Settings_Vtiger_CompanyDetails_Model $moduleModel
     * @param string $errorLabel
     * @return void
     */
    private function redirectWithError($moduleModel, $errorLabel) {
        $reloadUrl = $moduleModel->getIndexViewUrl() . '&error=' . $errorLabel;
        header('Location: ' . $reloadUrl);
        exit;
    }

    /**
     * Validates write permissions for the request.
     *
     * Ensures the current user has access to modify company settings.
     *
     * @param Vtiger_Request $request
     * @return void
     */
    public function validateRequest(Vtiger_Request $request) {
        $request->validateWriteAccess();
    }
}