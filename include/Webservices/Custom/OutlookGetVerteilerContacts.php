<?php
/**
 * Expansion of owned distribution lists into readable email contacts.
 *
 * @package berliCRM.OfficeAddins
 */

require_once 'include/Webservices/Retrieve.php';

/**
 * Expands an owned distribution list into readable contacts with non-empty email fields.
 *
 * @param string $verteilerId Distribution-list webservice identifier.
 * @param Users $user Authenticated CRM user.
 * @return array
 * @throws WebServiceException On invalid identifiers, missing rights or database errors.
 */
function berli_outlookGetVerteilerContacts($verteilerId, $user)
{
    global $adb;

    if (!is_string($verteilerId) || !preg_match('/^[1-9][0-9]*x[1-9][0-9]*$/D', $verteilerId)) {
        throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'Ungültige Verteiler-ID.');
    }
    $verteilerIdComponents = vtws_getIdComponents($verteilerId);
    $verteilerObject = VtigerWebserviceObject::fromId($adb, $verteilerIdComponents[0]);
    if ($verteilerObject->getEntityName() !== 'Verteiler') {
        throw new WebServiceException(
            WebServiceErrorCode::$INVALIDID,
            'The supplied record is not a berliCRM Verteiler.'
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
            'Permission to read the Verteiler is denied.'
        );
    }

    // Recheck ownership on expansion so a supplied ID cannot select someone else's list.
    $ownerResult = $adb->pquery(
        'SELECT crmid FROM vtiger_crmentity WHERE crmid = ? AND setype = ? AND deleted = 0 AND smownerid = ?',
        array($verteilerIdComponents[1], 'Verteiler', (int)$user->id)
    );
    if ($ownerResult === false) {
        throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR, 'Verteilerzuständigkeit konnte nicht geprüft werden.');
    }
    if (!$adb->num_rows($ownerResult)) {
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Der Verteiler ist nicht dem angemeldeten CRM-Benutzer zugewiesen.');
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
            'Permission to read Contacts is denied.'
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
