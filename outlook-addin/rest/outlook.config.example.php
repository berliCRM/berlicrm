<?php

return array(
    // CRM users sign in through the add-in. Do not store CRM login credentials here.
    // Set this to the URL of this berliCRM installation's webservice.php.
    'crm_webservice_url' => 'https://crm.example.org/webservice.php',
    'default_request_timeout' => 30,
    // Match the effective Exchange recipient limit (To + Cc + Bcc combined).
    // This is an add-in setting; it does not change the Exchange mailbox limit.
    'recipient_limit' => 500,
    // Microsoft Entra SPA registration: delegated Contacts.ReadWrite, no secret.
    // Register redirect_uri as a Single-page application redirect URI.
    'contacts_microsoft' => array(
        'client_id' => '',
        'tenant_id' => '',
        // Empty: derived from crm_webservice_url + /outlook-addin/contacts-auth.html.
        'redirect_uri' => ''
    )
);
