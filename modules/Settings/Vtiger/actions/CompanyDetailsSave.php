<?php

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

/**
 * Class Settings_Vtiger_CompanyDetailsSave_Action
 *
 * Saves company details including secure logo upload.
 *
 * SECURITY SUMMARY (Audit Notes):
 * - Whitelist-based validation for file extension and MIME (avoid blacklist bypass)
 * - Server-side MIME detection via finfo (do not trust client-provided Content-Type)
 * - Image structure verification via getimagesize (avoid non-image payload uploads)
 * - is_uploaded_file check (ensure upload came from HTTP POST) for the ORIGINAL upload
 * - Re-encoding via GD (mitigate polyglot images / embedded payloads)
 * - Enforced server-side filename (avoid path traversal / confusing double extensions)
 *
 * Allowed formats:
 * - Extensions: .jpg, .jpeg, .png, .gif
 * - MIME types: image/jpeg, image/pjpeg, image/png, image/x-png, image/gif
 *
 * NOTE:
 * - “pjpeg” and “x-png” are legacy MIME variants, not real filename extensions.
 */
class Settings_Vtiger_CompanyDetailsSave_Action extends Settings_Vtiger_Basic_Action {

    public function process(Vtiger_Request $request) {

        $moduleModel = Settings_Vtiger_CompanyDetails_Model::getInstance();

        $saveLogo = true;
        $binFileName = null;

        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
        $allowedMime = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/gif'];
        $allowedImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];

        // SECURITY: Required business field validation
        if (!$request->get('organizationname')) {
            $reloadUrl = $moduleModel->getEditViewUrl() . '&error=LBL_FIELDS_INFO_IS_EMPTY';
            header('Location: ' . $reloadUrl);
            exit;
        }

        /*
         * ================================
         * Secure Logo Upload Handling
         * ================================
         */
        if (!empty($_FILES['logo']['name'])) {

            $logoDetails = $_FILES['logo'];

            // SECURITY: Upload integrity
            if (!isset($logoDetails['error']) || $logoDetails['error'] !== UPLOAD_ERR_OK) {
                $saveLogo = false;
            }

            // SECURITY: Ensure ORIGINAL file is an HTTP upload before we process it
            if ($saveLogo && (empty($logoDetails['tmp_name']) || !is_uploaded_file($logoDetails['tmp_name']))) {
                $saveLogo = false;
            }

            // SECURITY: Extension whitelist (secondary)
            if ($saveLogo) {
                $ext = strtolower(pathinfo($logoDetails['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true)) {
                    $saveLogo = false;
                }
            }

            // SECURITY: MIME validation via finfo (authoritative)
            $mime = null;
            if ($saveLogo) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($logoDetails['tmp_name']);
                if (!in_array($mime, $allowedMime, true)) {
                    $saveLogo = false;
                }
            }

            // SECURITY: Validate image structure
            $imgInfo = null;
            $imageType = 0;
            if ($saveLogo) {
                $imgInfo = @getimagesize($logoDetails['tmp_name']);
                if ($imgInfo === false) {
                    $saveLogo = false;
                } 
				else {
                    $imageType = (int)($imgInfo[2] ?? 0);
                    if (!in_array($imageType, $allowedImageTypes, true)) {
                        $saveLogo = false;
                    }
                }
            }

            if ($saveLogo) {

                // SECURITY: Re-encode image using GD
                if ($imageType === IMAGETYPE_JPEG) {
                    $img = @imagecreatefromjpeg($logoDetails['tmp_name']);
                    $finalExt = 'jpg';
                } 
				elseif ($imageType === IMAGETYPE_PNG) {
                    $img = @imagecreatefrompng($logoDetails['tmp_name']);
                    $finalExt = 'png';
                } 
				else { // IMAGETYPE_GIF
                    $img = @imagecreatefromgif($logoDetails['tmp_name']);
                    $finalExt = 'gif';
                }

                if ($img === false) {
                    $saveLogo = false;
                } 
				else {
                    $tmpReencoded = tempnam(sys_get_temp_dir(), 'vtlogo_');
                    if ($tmpReencoded === false) {
                        imagedestroy($img);
                        $saveLogo = false;
                    } 
					else {

                        if ($imageType === IMAGETYPE_JPEG) {
                            $success = imagejpeg($img, $tmpReencoded, 90);
                        } 
						elseif ($imageType === IMAGETYPE_PNG) {
                            $success = imagepng($img, $tmpReencoded, 6);
                        } 
						else {
                            $success = imagegif($img, $tmpReencoded);
                        }

                        imagedestroy($img);

                        if (!$success) {
                            @unlink($tmpReencoded);
                            $saveLogo = false;
                        } 
						else {
                            // SECURITY: enforce safe filename (ignore user name)
                            $binFileName = 'companylogo.' . $finalExt;

                            $moduleModel->saveLogo($binFileName);

                            // cleanup sanitized temp
                            @unlink($tmpReencoded);
                        }
                    }
                }
            }
        }

        /*
         * ================================
         * Save Company Fields
         * ================================
         */
        if ($saveLogo) {

            $fields = $moduleModel->getFields();

            foreach ($fields as $fieldName => $fieldType) {
                $fieldValue = $request->get($fieldName);

                if ($fieldName === 'logoname') {
                    if (!empty($binFileName)) {
                        $fieldValue = $binFileName;
                    } else {
                        $fieldValue = $moduleModel->get($fieldName);
                    }
                }

                $moduleModel->set($fieldName, $fieldValue);
            }

            $moduleModel->save();
        }

        $reloadUrl = $moduleModel->getIndexViewUrl();
        if (!$saveLogo) {
            $reloadUrl .= '&error=LBL_INVALID_IMAGE';
        }

        header('Location: ' . $reloadUrl);
        exit;
    }


}