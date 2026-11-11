<?php
/**
 * Event labels and display preferences for the authenticated CRM user.
 *
 * @package berliCRM.OfficeAddins
 */

require_once 'include/Webservices/DescribeObject.php';

// Only display metadata for the authenticated user; no user ID is accepted as input.
/**
 * Returns event field labels, date preferences and permissions for the authenticated user.
 *
 * @param Users $user Authenticated CRM user.
 * @return array<string,mixed>
 * @throws WebServiceException When event metadata is not readable.
 * @berli-openapi-begin
 * {"summary":"Get Outlook event display settings","description":"Returns event field labels, date preferences and create/update permissions for the authenticated user.","tags":["Outlook"],"parameters":{},"result":{"schema":{"type":"object","required":["dateFormat","fieldLabels","canCreate","canUpdate"],"properties":{"dateFormat":{"type":"string"},"fieldLabels":{"type":"object","additionalProperties":{"type":"string"}},"canCreate":{"type":"boolean"},"canUpdate":{"type":"boolean"}},"additionalProperties":false},"example":{"dateFormat":"dd-mm-yyyy","fieldLabels":{"subject":"Subject","location":"Location"},"canCreate":true,"canUpdate":true}}}
 * @berli-openapi-end
 */
function berli_outlookGetEventDisplay($user)
{
    $description = vtws_describe('Events', $user);
    $labels = array();
    foreach ($description['fields'] as $field) {
        if (in_array($field['name'], array('subject', 'location', 'description', 'date_start', 'due_date'), true)) {
            $labels[$field['name']] = html_entity_decode($field['label'], ENT_QUOTES, 'UTF-8');
        }
    }

    return array(
        'dateFormat' => !empty($user->date_format) ? $user->date_format : 'dd-mm-yyyy',
        'fieldLabels' => $labels,
        'canCreate' => !empty($description['createable']),
        'canUpdate' => !empty($description['updateable'])
    );
}
