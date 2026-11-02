<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'payload' => 'Nur POST-Anfragen sind erlaubt.']);
    exit;
}

require_once __DIR__ . '/OutlookWebserviceClient.php';
require_once __DIR__ . '/OutlookContactsConfig.php';

$config = [];
if (file_exists(__DIR__ . '/outlook.config.php')) {
    $config = require __DIR__ . '/outlook.config.php';
}

$input = parseRequest();
actionHandler($input, $config);

function parseRequest(): array
{
    $data = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true) ?: [];
    }
    return array_merge($_REQUEST, $data);
}

function actionHandler(array $input, array $config): void
{
    $action = $input['action'] ?? '';
    if (!$action) {
        jsonResponse(false, 'Action fehlt.');
    }

    $clientConfig = [
        'crm_webservice_url' => $config['crm_webservice_url'] ?? '',
        'default_request_timeout' => $config['default_request_timeout'] ?? 30,
        'api_username' => $config['api_username'] ?? '',
        'api_access_key' => $config['api_access_key'] ?? '',
    ];

    try {
        if ($action === 'getContactsMicrosoftConfig') {
            jsonResponse(true, outlookContactsMicrosoftConfig($config));
        }
        $service = new OutlookWebserviceClient($clientConfig);

        switch ($action) {
            case 'archiveEmail':
                requireParams($input, ['sessionName', 'archive']);
                if (!is_array($input['archive'])) jsonResponse(false, 'Ungültige E-Mail-Daten.');
                $result = $service->customOperation('outlook_archive_email',
                    ['input' => json_encode($input['archive'])], $input['sessionName']);
                jsonResponse(true, $result);
                break;

            case 'contactTransfer':
                requireParams($input, ['sessionName', 'transfer']);
                if (!is_array($input['transfer'])) jsonResponse(false, 'Ungültige Kontaktdaten.');
                $result = $service->customOperation('outlook_contact_transfer',
                    ['input' => json_encode($input['transfer'])], $input['sessionName']);
                jsonResponse(true, $result);
                break;

            case 'getRecipientSettings':
                requireParams($input, ['sessionName']);
                jsonResponse(true, ['recipientLimit' => configuredRecipientLimit($config)]);
                break;

            case 'login':
                requireParams($input, ['username', 'accessKey']);
                $result = $service->login($input['username'], $input['accessKey']);
                jsonResponse(true, $result);
                break;

            case 'searchContacts':
                requireParams($input, ['query', 'sessionName']);
                $query = buildContactSearchQuery($input['query']);
                $contacts = $service->query($query, $input['sessionName']);
                jsonResponse(true, ['contacts' => normalizeRecords($contacts)]);
                break;

            case 'searchAccounts':
                requireParams($input, ['query', 'sessionName']);
                $query = buildAccountSearchQuery($input['query']);
                $accounts = $service->query($query, $input['sessionName']);
                jsonResponse(true, ['accounts' => normalizeRecords($accounts)]);
                break;

            case 'searchVerteiler':
                requireParams($input, ['query', 'sessionName', 'userId']);
                $query = buildVerteilerSearchQuery($input['query'], $input['userId']);
                $verteilers = $service->query($query, $input['sessionName']);
                jsonResponse(true, ['verteilers' => normalizeRecords($verteilers)]);
                break;

            case 'getVerteilerContacts':
            case 'countVerteilerContacts':
                requireParams($input, ['verteilerId', 'sessionName']);
                $contacts = $service->customOperation(
                    'outlook_get_verteiler_contacts',
                    ['verteilerId' => $input['verteilerId']],
                    $input['sessionName']
                );
                $emailContacts = [];
                foreach (normalizeRecords($contacts) as $contact) {
                    if (!isset($contact['email']) || !is_string($contact['email']) || trim($contact['email']) === '') {
                        continue;
                    }
                    $contact['email'] = trim($contact['email']);
                    $emailContacts[] = $contact;
                }
                if ($action === 'countVerteilerContacts') {
                    jsonResponse(true, ['emailContactCount' => count($emailContacts)]);
                }
                jsonResponse(true, ['contacts' => $emailContacts]);
                break;

            case 'listEvents':
                requireParams($input, ['sessionName']);
                $query = buildEventSearchQuery($input['start'] ?? null, $input['end'] ?? null);
                $events = $service->query($query, $input['sessionName']);
                $display = $service->customOperation('outlook_get_event_display', [], $input['sessionName']);
                jsonResponse(true, ['events' => normalizeRecords($events), 'display' => $display]);
                break;

            case 'calendarEvent':
                requireParams($input, ['sessionName', 'calendar']);
                if (!is_array($input['calendar'])) {
                    jsonResponse(false, 'Ungültige Kalenderdaten.');
                }
                $result = $service->customOperation('outlook_calendar_event',
                    ['input' => json_encode($input['calendar'])], $input['sessionName']);
                jsonResponse(true, $result);
                break;

            case 'createEvent':
                requireParams($input, ['sessionName', 'event']);
                $eventElement = is_array($input['event']) ? $input['event'] : json_decode($input['event'], true);
                if (!is_array($eventElement)) {
                    jsonResponse(false, 'Ungültiges Event-Element.');
                }
                $result = $service->create($eventElement, $input['sessionName']);
                jsonResponse(true, ['event' => $result]);
                break;

            case 'sendEmail':
                requireParams($input, ['sessionName', 'subject', 'body']);
                $receiverIds = $input['receiverIds'] ?? [];
                if (is_string($receiverIds)) {
                    $receiverIds = json_decode($receiverIds, true);
                }
                if (!is_array($receiverIds)) {
                    $receiverIds = [];
                }
                $receiverEmails = $input['receiverEmails'] ?? [];
                if (is_string($receiverEmails)) {
                    $receiverEmails = json_decode($receiverEmails, true);
                }
                if (!is_array($receiverEmails)) {
                    $receiverEmails = [];
                }
                $allReceivers = array_values(array_filter(array_merge($receiverIds, $receiverEmails)));
                if (empty($allReceivers)) {
                    jsonResponse(false, 'Empfänger fehlen.');
                }
                $params = [
                    'receiverIds' => json_encode($allReceivers),
                    'templateId' => $input['templateId'] ?? '',
                    'subject' => $input['subject'],
                    'body' => $input['body'],
                    'documentIds' => $input['documentIds'] ?? '',
                    'mailFieldToUse' => $input['mailFieldToUse'] ?? '',
                    'fromId' => $input['fromId'] ?? '',
                    'fromName' => $input['fromName'] ?? '',
                    'attachments' => json_encode($input['attachments'] ?? []),
                ];
                $result = $service->customOperation('outlook_send_mail', $params, $input['sessionName']);
                jsonResponse(true, ['emailResult' => $result]);
                break;

            case 'retrieve':
                requireParams($input, ['sessionName', 'recordId']);
                $record = $service->retrieve($input['recordId'], $input['sessionName']);
                jsonResponse(true, ['record' => $record]);
                break;

            default:
                jsonResponse(false, 'Unbekannte Aktion: ' . $action);
        }
    } catch (Throwable $e) {
        jsonResponse(false, $e->getMessage());
    }
}

function requireParams(array $input, array $names): void
{
    foreach ($names as $name) {
        if (!isset($input[$name]) || $input[$name] === '') {
            jsonResponse(false, "Parameter '$name' fehlt.");
        }
    }
}

function configuredRecipientLimit(array $config): int
{
    $value = $config['recipient_limit'] ?? 500;
    if (!is_int($value) && !is_string($value)) {
        throw new InvalidArgumentException('recipient_limit muss eine ganze Zahl zwischen 1 und 1000 sein.');
    }
    $limit = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
    if ($limit === false) {
        throw new InvalidArgumentException('recipient_limit muss eine ganze Zahl zwischen 1 und 1000 sein.');
    }
    return $limit;
}

function buildContactSearchQuery(string $search): string
{
    $term = quoteSearch($search);
    return "select id, firstname, lastname, email, mobile, phone, account_id from Contacts "
        . "where firstname like '%$term%' or lastname like '%$term%' or email like '%$term%' limit 50;";
}

function buildAccountSearchQuery(string $search): string
{
    $term = quoteSearch($search);
    return "select id, accountname, phone, email1, website from Accounts "
        . "where accountname like '%$term%' or phone like '%$term%' or email1 like '%$term%' limit 50;";
}

function buildVerteilerSearchQuery(string $search, string $userId): string
{
    if (!preg_match('/^[1-9][0-9]*x[1-9][0-9]*$/D', $userId)) {
        throw new InvalidArgumentException('Ungültige CRM-Benutzer-ID. Bitte erneut anmelden.');
    }
    $term = quoteSearch($search);
    // userId comes from the standard CRM login response, not the Outlook account.
    return "select id, verteilername from Verteiler "
        . "where verteilername like '%$term%' and assigned_user_id = '$userId' limit 50;";
}

function buildEventSearchQuery(?string $start, ?string $end): string
{
    $conditions = [];
    if ($start) {
        $conditions[] = "date_start >= '" . quoteSearch($start) . "'";
    }
    if ($end) {
        $conditions[] = "date_start <= '" . quoteSearch($end) . "'";
    }
    $filter = empty($conditions) ? '' : ' where ' . implode(' and ', $conditions);
    return "select id, subject, date_start, due_date, time_start, time_end, location, description, assigned_user_id from Events{$filter} order by date_start desc limit 100;";
}

function quoteSearch(string $value): string
{
    return str_replace("'", "''", trim($value));
}

function normalizeRecords($records): array
{
    if (!is_array($records)) {
        return [];
    }
    if (isset($records['result']) && is_array($records['result'])) {
        $records = $records['result'];
    }
    if (!isset($records[0]) || !is_array($records[0])) {
        if (isset($records['id'])) {
            return [$records];
        }
        return [];
    }
    return $records;
}

function jsonResponse(bool $success, $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'payload' => $payload,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
