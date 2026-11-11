<?php

/** Account-wide display preference, stored alongside the existing user preferences. */
class Users_CommentDisplay_Model {
    const PREFERENCE_KEY = 'comments_expanded';

    private static function getPreferences() {
        $db = PearDatabase::getInstance();
        $userId = Users_Record_Model::getCurrentUserModel()->getId();
        $result = $db->pquery('SELECT user_preferences FROM vtiger_users WHERE id = ?', array($userId));
        if (!$result || !$db->num_rows($result)) {
            throw new Exception('Unable to read comment display preference');
        }
        $stored = $db->query_result($result, 0, 'user_preferences');
        $preferences = $stored ? unserialize(base64_decode($stored), array('allowed_classes' => false)) : array();
        return is_array($preferences) ? $preferences : array();
    }

    public static function isExpanded() {
        $preferences = self::getPreferences();
        return !empty($preferences[self::PREFERENCE_KEY]);
    }

    public static function saveExpanded($expanded) {
        // Read the current database value so another session's preferences are preserved.
        $preferences = self::getPreferences();
        $preferences[self::PREFERENCE_KEY] = (bool) $expanded;
        $db = PearDatabase::getInstance();
        $userId = Users_Record_Model::getCurrentUserModel()->getId();
        $result = $db->pquery('UPDATE vtiger_users SET user_preferences = ? WHERE id = ?',
            array(base64_encode(serialize($preferences)), $userId));
        if (!$result) {
            throw new Exception('Unable to save comment display preference');
        }
        $_SESSION['USER_PREFERENCES'] = $preferences;
        $_SESSION[self::PREFERENCE_KEY] = (bool) $expanded;
        global $current_user;
        $current_user->user_preferences = $preferences;
    }
}
