<?php

require_once 'include/Webservices/DescribeObject.php';

// Only display metadata for the authenticated user; no user ID is accepted as input.
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
