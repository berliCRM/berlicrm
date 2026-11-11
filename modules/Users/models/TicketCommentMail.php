<?php

/** Persistent automatic CC preference for the current CRM user. */
class Users_TicketCommentMail_Model
{
    const PREFERENCE_KEY = 'ticket_comment_auto_cc';

    private static function getPreferences(): array
    {
        $db = PearDatabase::getInstance();
        $userId = Users_Record_Model::getCurrentUserModel()->getId();
        $result = $db->pquery('SELECT user_preferences FROM vtiger_users WHERE id = ?', array($userId));
        if (!$result || !$db->num_rows($result)) {
            throw new Exception('Unable to read ticket comment mail preference');
        }
        $stored = $db->query_result($result, 0, 'user_preferences');
        $preferences = $stored ? unserialize(base64_decode($stored), array('allowed_classes' => false)) : array();
        return is_array($preferences) ? $preferences : array();
    }

    public static function isAutomaticCcEnabled(): bool
    {
        $preferences = self::getPreferences();
        return !array_key_exists(self::PREFERENCE_KEY, $preferences) || (bool) $preferences[self::PREFERENCE_KEY];
    }

    public static function saveAutomaticCc(bool $enabled): void
    {
        $preferences = self::getPreferences();
        $preferences[self::PREFERENCE_KEY] = $enabled;
        $userId = Users_Record_Model::getCurrentUserModel()->getId();
        $db = PearDatabase::getInstance();
        $result = $db->pquery('UPDATE vtiger_users SET user_preferences = ? WHERE id = ?',
            array(base64_encode(serialize($preferences)), $userId));
        if (!$result) {
            throw new Exception('Unable to save ticket comment mail preference');
        }
        $_SESSION['USER_PREFERENCES'] = $preferences;
        global $current_user;
        $current_user->user_preferences = $preferences;
    }
}
