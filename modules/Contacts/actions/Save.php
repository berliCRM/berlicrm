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
 * Class Contacts_Save_Action
 *
 * Saves Contacts records and hardens profile image uploads.
 *
 * SECURITY SUMMARY (Audit Notes):
 * - transformUploadedFiles() only normalizes the $_FILES structure; it does not validate file security.
 * - Uploaded contact images are explicitly validated using Vtiger_Functions::validateImage()
 *   BEFORE persistence to prevent storing non-image or polyglot payloads.
 * - Validation is performed without changing vtiger’s expected $_FILES structure used for saving.
 */
class Contacts_Save_Action extends Vtiger_Save_Action {

	/**
	 * Processes the Contacts save request.
	 *
	 * Steps:
	 * - Normalizes upload array with Vtiger_Util_Helper::transformUploadedFiles()
	 * - Validates the uploaded image (if any) with Vtiger_Functions::validateImage()
	 * - Normalizes salutationtype field
	 * - Delegates actual save to parent::process()
	 *
	 * SECURITY (Audit Notes):
	 * - Explicit validation is required because transformUploadedFiles() does not enforce allowlists,
	 *   MIME checks, or image structural validation.
	 * - Throws a generic AppException on validation failure to avoid leaking validation details.
	 *
	 * @param Vtiger_Request $request Incoming request for Contacts save.
	 * @return void
	 * @throws AppException If an uploaded image is invalid.
	 */
	public function process(Vtiger_Request $request) {
		$result = Vtiger_Util_Helper::transformUploadedFiles($_FILES, true);

		// IMPORTANT: keep vtiger legacy behavior for downstream save logic
		$_FILES = $result['imagename'];

		/*
		 * SECURITY: Validate uploaded contact image explicitly (if provided).
		 * We must locate the actual file array regardless of nesting/flattening.
		 */
		$imageFile = null;

		// Shape A: flattened file array already
		if (is_array($_FILES) && isset($_FILES['tmp_name'], $_FILES['name'])) {
			$imageFile = $_FILES;
		}
		// Shape B: nested under 'imagename'
		else if (is_array($_FILES) && isset($_FILES['imagename']) && is_array($_FILES['imagename'])) {
			$imageFile = $_FILES['imagename'];
		}
		// Shape C: multi-upload style [0 => fileArray]
		else if (is_array($_FILES) && isset($_FILES[0]) && is_array($_FILES[0])) {
			$imageFile = $_FILES[0];
		}

		if (!empty($imageFile) && !empty($imageFile['name']) && !empty($imageFile['tmp_name'])) {
			$isValid = Vtiger_Functions::validateImage($imageFile);
			if (is_string($isValid)) {
				$isValid = ($isValid === 'true');
			}
			if (!$isValid) {
				// SECURITY: generic error (avoid attacker feedback)
				throw new AppException('LBL_INVALID_IMAGE');
			}
		}

		// To stop saving the value of salutation as '--None--'
		$salutationType = $request->get('salutationtype');
		if ($salutationType === '--None--') {
			$request->set('salutationtype', '');
		}

		parent::process($request);
	}
}