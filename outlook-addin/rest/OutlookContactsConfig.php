<?php
/**
 * Public Microsoft Entra SPA configuration validation for Outlook contacts.
 *
 * @package berliCRM.OfficeAddins
 */
require_once dirname(__DIR__, 2) . '/include/utils/OfficeAddinI18n.php';


// Public SPA configuration only. Never expose CRM credentials or client secrets.
/**
 * Validates and returns only public Entra SPA settings for the contacts client.
 *
 * @param array<string,mixed> $config Private installation configuration; credentials are never returned.
 * @return array<string,mixed>
 * @throws InvalidArgumentException On invalid tenant, client or redirect settings.
 */
function outlookContactsMicrosoftConfig(array $config): array
{
    $settings = $config['contacts_microsoft'] ?? array();
    if (!is_array($settings)) throw new InvalidArgumentException(berli_outlookT('INVALID_MICROSOFT_CONTACTS_CONFIGURATION'));
    $client = $settings['client_id'] ?? '';
    $tenant = $settings['tenant_id'] ?? '';
    if ($client === '' && $tenant === '') return array('configured' => false);
    $guid = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD';
    if (!is_string($client) || !is_string($tenant) || !preg_match($guid, $client) || !preg_match($guid, $tenant)) {
        throw new InvalidArgumentException(berli_outlookT('VALID_MICROSOFT_CLIENT_AND_TENANT_IDS_MUST_BE_CONFIGURED_FOR_THE_CONTACT'));
    }
    $redirect = $settings['redirect_uri'] ?? '';
    if ($redirect === '') {
        $crmUrl = $config['crm_webservice_url'] ?? '';
        if (!is_string($crmUrl)) throw new InvalidArgumentException(berli_outlookT('INVALID_CRM_WEBSERVICE_ADDRESS'));
        $redirect = preg_replace('~/webservice\\.php/?$~', '', rtrim($crmUrl, '/')) . '/outlook-addin/contacts-auth.html';
    }
    $parts = is_string($redirect) ? parse_url($redirect) : false;
    if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
        || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
        || !filter_var($redirect, FILTER_VALIDATE_URL) || substr($parts['path'] ?? '', -19) !== '/contacts-auth.html') {
        throw new InvalidArgumentException(berli_outlookT('THE_MICROSOFT_REDIRECT_URI_MUST_BE_THE_HTTPS_ADDRESS_OF_OUTLOOK_ADDIN_CO'));
    }
    return array('configured' => true, 'clientId' => $client, 'tenantId' => $tenant, 'redirectUri' => $redirect);
}
