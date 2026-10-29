<?php

/**
 * Supplies authorized CRM list views, records and display values to the Word add-in.
 *
 * Registered as the authenticated getWordMailMergeData POST operation by the
 * CRM updater, Word settings or the standalone CLI installer.
 *
 * @package berliCRM.WordAddin
 */

// Required for field validation; this function is not guaranteed to be loaded for custom operations.
require_once 'include/Webservices/DescribeObject.php';

/**
 * Returns available list views or mail-merge data to the Word add-in.
 *
 * If no list view ID is provided, the list views available to the user are
 * returned. If a list view ID is provided, either a page of records or the
 * explicitly requested records are returned.
 *
 * @param string $moduleName CRM web-service module name.
 * @param string $listId Saved list view ID; empty to retrieve the available views.
 * @param string|int $page Record-list page number, starting at 1.
 * @param string $recordIds Comma-separated IDs of explicitly selected records.
 * @param string $fieldNames Comma-separated web-service names of the selected fields.
 * @param Users $user Authenticated berliCRM web-service user.
 * @return array List views or records, including previews and pagination data.
 * @throws WebServiceException If access is denied or parameters are invalid.
 */
function berli_getWordMailMergeData($moduleName, $listId, $page, $recordIds, $fieldNames, $user) {
	global $adb;

	// Allow only modules supported as record sources by the Word add-in.
	$allowedModules = array('Contacts', 'Accounts', 'Leads', 'HelpDesk');
	if (!in_array($moduleName, $allowedModules, true)) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Dieses Modul ist für den Word-Seriendruck nicht freigegeben.');
	}

	// Require both web-service module access and berliCRM detail-view permission.
	$types = vtws_listtypes(null, $user);
	if (!in_array($moduleName, $types['types'], true)) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Sie dürfen dieses CRM-Modul nicht lesen.');
	}

	$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
	if (!$moduleModel || !$moduleModel->isPermitted('DetailView')) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Sie dürfen dieses CRM-Modul nicht lesen.');
	}

	// CustomView_Record_Model returns only views available to this user.
	$views = CustomView_Record_Model::getAll($moduleName);
	if (empty($listId)) {
		$result = array();
		foreach ($views as $view) {
			$result[] = array(
				'id' => (string) $view->getId(),
				'name' => $view->getCustomViewName()
			);
		}
		return array('views' => $result);
	}

	// The requested view ID must belong to the list of views the user is allowed to access.
	$view = null;
	foreach ($views as $availableView) {
		if ((string) $availableView->getId() === (string) $listId) {
			$view = $availableView;
			break;
		}
	}
	if (!$view) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Die Listenansicht ist nicht verfügbar.');
	}

	$selectedFields = array();
	$fieldModels = array();
	if (!empty($fieldNames)) {
		// Validate against the module description so only fields available to the user are processed.
		$description = vtws_describe($moduleName, $user);
		$availableFields = array();
		foreach ($description['fields'] as $field) {
			if (isset($field['name'])) {
				$availableFields[$field['name']] = true;
			}
		}
		foreach (array_unique(explode(',', $fieldNames)) as $fieldName) {
			if (!isset($availableFields[$fieldName])) {
				throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Ein ausgewähltes Feld ist für diesen Benutzer nicht verfügbar.');
			}
			$selectedFields[] = $fieldName;
			$fieldModels[$fieldName] = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
		}
	}

	// This branch handles selections spanning multiple pages and the subsequent export.
	if (!empty($recordIds)) {
		$requestedIds = explode(',', $recordIds);
		if (count($requestedIds) > 100) {
			throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'Pro Webservice-Aufruf können höchstens 100 Datensätze verarbeitet werden.');
		}
		if (empty($selectedFields)) {
			throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'Mindestens ein Mergefeld muss ausgewählt sein.');
		}
		foreach ($requestedIds as $requestedId) {
			if (!ctype_digit($requestedId) || (int) $requestedId < 1) {
				throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'Ungültige Datensatz-ID.');
			}
		}
		$requestedIds = array_values(array_unique($requestedIds));
		// Only records belonging to the selected view may be exported through this endpoint.
		$availableIds = $view->getRecordIds();
		$availableIdMap = array_fill_keys(array_map('strval', $availableIds), true);
		$webserviceObject = VtigerWebserviceObject::fromName($adb, $moduleName);
		$records = array();

		foreach ($requestedIds as $recordId) {
			if (!isset($availableIdMap[$recordId])) {
				throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Ein ausgewählter Datensatz gehört nicht zur Listenansicht.');
			}
			// vtws_retrieve also enforces the user's record-level access permissions.
			$record = vtws_retrieve(vtws_getId($webserviceObject->getEntityId(), $recordId), $user);
			$record = berli_wordMailMergeResolveReferenceFields($record, $selectedFields, $fieldModels, $user);
			$records[] = array(
				'id' => (string) $recordId,
				'name' => berli_wordMailMergeRecordLabel($recordId, $record, $selectedFields, $fieldModels),
				'fields' => array_intersect_key($record, array_flip($selectedFields))
			);
		}
		return array('records' => $records, 'nextPage' => false);
	}

	// For regular list-view requests, validate the page number and load at most 100 records.
	$pageNumber = (int) $page;
	if ($pageNumber < 1 || $pageNumber > 10000) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'Ungültige Seitennummer.');
	}

	$recordIdsInView = $view->getRecordIds();
	$totalCount = count($recordIdsInView);
	$pageSize = 100;
	$pageRecordIds = array_slice($recordIdsInView, ($pageNumber - 1) * $pageSize, $pageSize);
	$webserviceObject = VtigerWebserviceObject::fromName($adb, $moduleName);
	$records = array();
	foreach ($pageRecordIds as $recordId) {
		// Use the web-service permission path when loading records for page navigation as well.
		$record = vtws_retrieve(vtws_getId($webserviceObject->getEntityId(), $recordId), $user);
		$record = berli_wordMailMergeResolveReferenceFields($record, $selectedFields, $fieldModels, $user);
		$records[] = array(
			'id' => (string) $recordId,
			'name' => berli_wordMailMergeRecordLabel($recordId, $record, $selectedFields, $fieldModels),
			'fields' => array_intersect_key($record, array_flip($selectedFields))
		);
	}

	return array(
		'records' => $records,
		'nextPage' => ($pageNumber * $pageSize) < $totalCount,
		'totalCount' => $totalCount
	);
}

/**
 * Replaces selected reference IDs with the display names of their target records.
 *
 * @param array $record Source record returned by the web service.
 * @param array $selectedFields Selected web-service field names.
 * @param array $fieldModels Corresponding CRM field models.
 * @param Users $user Authenticated CRM web-service user.
 * @return array Record with readable reference values.
 * @throws WebServiceException If a referenced record is inaccessible or invalid.
 */
function berli_wordMailMergeResolveReferenceFields($record, $selectedFields, $fieldModels, $user) {
	foreach ($selectedFields as $fieldName) {
		$fieldModel = isset($fieldModels[$fieldName]) ? $fieldModels[$fieldName] : null;
		if (!$fieldModel || !in_array($fieldModel->getFieldDataType(), array('reference', 'owner'), true)
			|| !isset($record[$fieldName])) {
			continue;
		}

		$referenceModules = $fieldModel->getFieldDataType() === 'owner'
			? array('Users', 'Groups')
			: (array) $fieldModel->getReferenceList();
		$record[$fieldName] = berli_wordMailMergeResolveReferenceValue(
			$record[$fieldName],
			$referenceModules,
			$user
		);
	}
	return $record;
}

/**
 * Resolves a reference value through the CRM web service so access checks apply.
 *
 * @param mixed $value CRM reference value.
 * @param array $referenceModules Modules allowed by the reference field.
 * @param Users $user Authenticated CRM web-service user.
 * @return string Display name, or an empty string when the reference is empty.
 * @throws WebServiceException If a referenced record cannot be retrieved.
 */
function berli_wordMailMergeResolveReferenceValue($value, $referenceModules, $user) {
	global $adb;

	if (is_array($value) || is_object($value)) {
		$value = (array) $value;
		if (!empty($value['label']) && (!isset($value['value']) || (string) $value['label'] !== (string) $value['value'])) {
			return (string) $value['label'];
		}
		$value = isset($value['value']) ? $value['value'] : (isset($value['id']) ? $value['id'] : '');
	}
	if (!is_scalar($value) || trim((string) $value) === '') {
		return '';
	}

	$value = trim((string) $value);
	$parts = vtws_getIdComponents($value);
	if (count($parts) === 2 && ctype_digit((string) $parts[0]) && ctype_digit((string) $parts[1])) {
		$webserviceObject = VtigerWebserviceObject::fromId($adb, (int) $parts[0]);
		$moduleName = $webserviceObject->getEntityName();
		$recordId = (int) $parts[1];
	} elseif (ctype_digit($value)) {
		if (count($referenceModules) === 1) {
			$moduleName = reset($referenceModules);
		} else {
			$moduleResult = $adb->pquery(
				'SELECT setype FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0',
				array((int) $value)
			);
			$moduleName = $moduleResult && $adb->num_rows($moduleResult)
				? $adb->query_result($moduleResult, 0, 'setype')
				: '';
		}
		if (!in_array($moduleName, $referenceModules, true)) {
			return '';
		}
		$webserviceObject = VtigerWebserviceObject::fromName($adb, $moduleName);
		$recordId = (int) $value;
		$value = vtws_getId($webserviceObject->getEntityId(), $recordId);
	} else {
		return '';
	}

	if (!in_array($moduleName, $referenceModules, true) || $recordId < 1) {
		return '';
	}

	$referencedRecord = vtws_retrieve($value, $user);
	$label = berli_wordMailMergeEntityLabel($recordId, $referencedRecord);
	return $label === (string) $recordId ? '' : $label;
}

/**
 * Builds the preview shown in the record list from selected field values.
 *
 * Complex reference and multi-value fields are converted to readable labels
 * where possible. If no fields are selected, the CRM record name is used as
 * a fallback.
 *
 * @param string|int $recordId Internal record ID used as a fallback.
 * @param array $record Record values returned by the web service.
 * @param array $selectedFields Selected web-service field names.
 * @param array $fieldModels Field models used to retrieve display labels.
 * @return string Readable preview for the selection list.
 */
function berli_wordMailMergeRecordLabel($recordId, $record, $selectedFields, $fieldModels) {
	if (empty($selectedFields)) {
		return berli_wordMailMergeEntityLabel($recordId, $record);
	}

	$values = array();
	foreach ($selectedFields as $fieldName) {
		$fieldModel = isset($fieldModels[$fieldName]) ? $fieldModels[$fieldName] : null;
		$label = $fieldModel ? $fieldModel->get('label') : $fieldName;
		$value = isset($record[$fieldName]) ? $record[$fieldName] : '';
		// Web-service field values for references and multi-value fields may be arrays or objects.
		if (is_array($value)) {
			$displayValues = array();
			foreach ($value as $item) {
				if (is_array($item)) {
					$item = isset($item['label']) ? $item['label'] : (isset($item['value']) ? $item['value'] : '');
				} elseif (is_object($item)) {
					$item = isset($item->label) ? $item->label : (isset($item->value) ? $item->value : '');
				}
				if (is_scalar($item) && (string) $item !== '') {
					$displayValues[] = (string) $item;
				}
			}
			$value = implode(', ', $displayValues);
		} elseif (is_object($value)) {
			$value = isset($value->label) ? $value->label : (isset($value->value) ? $value->value : '');
		}
		$value = is_scalar($value) ? trim((string) $value) : '';
		$values[] = $label . ': ' . $value;
	}
	$label = implode(' | ', $values);
	if (trim(str_replace(array(':', '|'), '', $label)) === '') {
		return berli_wordMailMergeEntityLabel($recordId, $record);
	}
	return $label;
}

/**
 * Resolves a readable CRM record name using successive fallbacks.
 *
 * @param string|int $recordId Internal record ID.
 * @param array $record Record values returned by the web service.
 * @return string Display name, or the ID as a final fallback.
 */
function berli_wordMailMergeEntityLabel($recordId, $record) {
	// Prefer the display name already returned by the web service.
	if (!empty($record['label'])) {
		return decode_html($record['label']);
	}
	// Otherwise, resolve the CRM display name from the record ID.
	$label = Vtiger_Util_Helper::getLabel($recordId);
	if (!empty($label)) {
		return decode_html($label);
	}
	// Use the ID as a non-empty label if no display name could be resolved.
	return (string) $recordId;
}
