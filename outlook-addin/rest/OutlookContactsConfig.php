<?php
/**
 * Public Microsoft Entra SPA configuration validation for Outlook contacts.
 *
 * @package berliCRM.OfficeAddins
 */

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
    if (!is_array($settings)) throw new InvalidArgumentException('Ungültige Microsoft-Kontakte-Konfiguration.');
    $client = $settings['client_id'] ?? '';
    $tenant = $settings['tenant_id'] ?? '';
    if ($client === '' && $tenant === '') return array('configured' => false);
    $guid = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD';
    if (!is_string($client) || !is_string($tenant) || !preg_match($guid, $client) || !preg_match($guid, $tenant)) {
        throw new InvalidArgumentException('Für die Kontakte-Anbindung müssen gültige Microsoft-Client-ID und Mandanten-ID konfiguriert werden.');
    }
    $redirect = $settings['redirect_uri'] ?? '';
    if ($redirect === '') {
        $crmUrl = $config['crm_webservice_url'] ?? '';
        if (!is_string($crmUrl)) throw new InvalidArgumentException('Ungültige CRM-Webservice-Adresse.');
        $redirect = preg_replace('~/webservice\.php/?$~', '', rtrim($crmUrl, '/')) . '/outlook-addin/contacts-auth.html';
    }
    $parts = is_string($redirect) ? parse_url($redirect) : false;
    if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
        || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
        || !filter_var($redirect, FILTER_VALIDATE_URL) || substr($parts['path'] ?? '', -19) !== '/contacts-auth.html') {
        throw new InvalidArgumentException('Die Microsoft-Redirect-URI muss die HTTPS-Adresse von outlook-addin/contacts-auth.html sein.');
    }
    return array('configured' => true, 'clientId' => $client, 'tenantId' => $tenant, 'redirectUri' => $redirect);
}
