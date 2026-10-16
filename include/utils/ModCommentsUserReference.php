<?php

/**
 * Repair only the shipped ModComments.userid reference metadata.
 * No comment data is changed. Unknown custom references require manual review.
 */
function berli_updateModCommentsUserReference($adb) {
    $result = $adb->pquery(
        'SELECT f.fieldid FROM vtiger_field f INNER JOIN vtiger_tab t ON t.tabid = f.tabid WHERE t.name = ? AND f.fieldname = ?',
        array('ModComments', 'userid')
    );
    if (!$result) {
        throw new Exception('Cannot read ModComments.userid metadata');
    }
    $count = $adb->num_rows($result);
    if ($count == 0) {
        return false; // Module not installed.
    }
    if ($count != 1) {
        throw new Exception('Multiple ModComments.userid fields found; review metadata before updating');
    }
    $fieldId = $adb->query_result($result, 0, 'fieldid');
    $result = $adb->pquery('SELECT relmodule FROM vtiger_fieldmodulerel WHERE fieldid = ?', array($fieldId));
    if (!$result) {
        throw new Exception('Cannot read ModComments.userid references');
    }
    $hasUsers = false;
    $hasWrongReference = false;
    for ($i = 0; $i < $adb->num_rows($result); ++$i) {
        $module = $adb->query_result($result, $i, 'relmodule');
        if ($module === 'Users') {
            $hasUsers = true;
        } elseif ($module === 'ModComments') {
            $hasWrongReference = true;
        } else {
            throw new Exception('Unexpected ModComments.userid reference; review custom metadata before updating');
        }
    }
    // Add the correct reference first, so a failed insert leaves existing metadata intact.
    if (!$hasUsers && !$adb->pquery(
            'INSERT INTO vtiger_fieldmodulerel (fieldid, module, relmodule) VALUES (?, ?, ?)',
            array($fieldId, 'ModComments', 'Users'))) {
        throw new Exception('Cannot add ModComments.userid Users reference');
    }
    if ($hasWrongReference && !$adb->pquery(
            'DELETE FROM vtiger_fieldmodulerel WHERE fieldid = ? AND relmodule = ?',
            array($fieldId, 'ModComments'))) {
        throw new Exception('Cannot remove incorrect ModComments.userid reference');
    }
    return true;
}
