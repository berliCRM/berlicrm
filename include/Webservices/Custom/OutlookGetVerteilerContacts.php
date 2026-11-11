<?php
/**
 * Expansion of owned distribution lists into readable email contacts.
 *
 * @package berliCRM.OfficeAddins
 */
require_once dirname(__DIR__, 2) . '/utils/OfficeAddinI18n.php';


require_once 'include/Webservices/Retrieve.php';

/**
 * Expands an owned distribution list into readable contacts with non-empty email fields.
 *
 * @param string $verteilerId Distribution-list webservice identifier.
 * @param Users $user Authenticated CRM user.
 * @return array
 * @throws WebServiceException On invalid identifiers, missing rights or database errors.
 * @berli-openapi-begin
 * {"summary":"Expand an Outlook distribution list","description":"Returns readable Contacts with non-empty email addresses from a distribution list owned by the authenticated user.","tags":["Outlook"],"parameters":{"verteilerId":{"required":true,"description":"Verteiler record webservice ID.","example":"40x100","schema":{"type":"string","pattern":"^[1-9][0-9]*x[1-9][0-9]*$"}}},"result":{"schema":{"type":"array","items":{"$ref":"#/components/schemas/Entity"}},"example":[{"id":"12x345","firstname":"Jane","lastname":"Doe","email":"jane@example.org"}]}}
 * @berli-openapi-end
 */
function berli_outlookGetVerteilerContacts($verteilerId, $user)
{
    global $adb;

    if (!is_string($verteilerId) || !preg_match('/^[1-9][0-9]*x[1-9][0-9]*$/D', $verteilerId)) {
        throw new WebServiceException(WebServiceErrorCode::$INVALIDID, berli_outlookT('INVALID_DISTRIBUTION_LIST_ID'));
    }
    $verteilerIdComponents = vtws_getIdComponents($verteilerId);
    $verteilerObject = VtigerWebserviceObject::fromId($adb, $verteilerIdComponents[0]);
    if ($verteilerObject->getEntityName() !== 'Verteiler') {
        throw new WebServiceException(
            WebServiceErrorCode::$INVALIDID,
            berli_outlookT('THE_SUPPLIED_RECORD_IS_NOT_A_BERLICRM_DISTRIBUTION_LIST')
        );
    }

    $handlerPath = $verteilerObject->getHandlerPath();
    $handlerClass = $verteilerObject->getHandlerClass();
    require_once $handlerPath;
    $handler = new $handlerClass($verteilerObject, $user, $adb);
    $verteilerMeta = $handler->getMeta();

    if (!$verteilerMeta->hasReadAccess()
        || !$verteilerMeta->hasPermission(EntityMeta::$RETRIEVE, $verteilerId)
        || !$verteilerMeta->exists($verteilerIdComponents[1])) {
        throw new WebServiceException(
            WebServiceErrorCode::$ACCESSDENIED,
            berli_outlookT('PERMISSION_TO_READ_THE_DISTRIBUTION_LIST_IS_DENIED')
        );
    }

    // Recheck ownership on expansion so a supplied ID cannot select someone else's list.
    $ownerResult = $adb->pquery(
        'SELECT crmid FROM vtiger_crmentity WHERE crmid = ? AND setype = ? AND deleted = 0 AND smownerid = ?',
        array($verteilerIdComponents[1], 'Verteiler', (int)$user->id)
    );
    if ($ownerResult === false) {
        throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR, berli_outlookT('COULD_NOT_CHECK_DISTRIBUTION_LIST_OWNERSHIP'));
    }
    if (!$adb->num_rows($ownerResult)) {
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, berli_outlookT('THE_DISTRIBUTION_LIST_IS_NOT_ASSIGNED_TO_THE_SIGNED_IN_CRM_USER'));
    }

    $contactsObject = VtigerWebserviceObject::fromName($adb, 'Contacts');
    $contactsHandlerPath = $contactsObject->getHandlerPath();
    $contactsHandlerClass = $contactsObject->getHandlerClass();
    require_once $contactsHandlerPath;
    $contactsHandler = new $contactsHandlerClass($contactsObject, $user, $adb);
    $contactsMeta = $contactsHandler->getMeta();

    if (!$contactsMeta->hasReadAccess()) {
        throw new WebServiceException(
            WebServiceErrorCode::$ACCESSDENIED,
            berli_outlookT('PERMISSION_TO_READ_CONTACTS_IS_DENIED')
        );
    }

    $contactIds = Verteiler_Relation_Model::getContactIdsFromVerteiler($verteilerIdComponents[1]);
    $contacts = array();
    foreach ($contactIds as $contactId) {
        $contactWebserviceId = vtws_getId($contactsObject->getEntityId(), $contactId);
        if (!$contactsMeta->hasPermission(EntityMeta::$RETRIEVE, $contactWebserviceId)
            || !$contactsMeta->exists($contactId)) {
            continue;
        }
        $contact = vtws_retrieve($contactWebserviceId, $user);
        // Check the readable CRM record, so hidden or empty email fields are excluded.
        if (!isset($contact['email']) || !is_string($contact['email'])) {
            continue;
        }
        $contact['email'] = trim($contact['email']);
        if ($contact['email'] === '') {
            continue;
        }
        $contacts[] = $contact;
    }

    VTWS_PreserveGlobal::flush();
    return $contacts;
}
