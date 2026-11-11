<?php

/**
 * Internal helpers for the single canonical row in vtiger_version.
 */
class BerliCRM_Version_Utils
{
    /**
     * Returns the installed release tag or null when the row is missing.
     *
     * @param PearDatabase $adb Database connection.
     * @return string|null
     */
    public static function getInstalledTag($adb)
    {
        $result = $adb->pquery(
            'SELECT `tag_version` FROM `vtiger_version` WHERE `id` = ?',
            array(1)
        );
        if (!$result || $adb->num_rows($result) === 0) {
            return null;
        }

        return (string)$adb->query_result($result, 0, 'tag_version');
    }

    /**
     * Creates or updates the canonical CRM version row.
     *
     * @param PearDatabase $adb Database connection.
     * @param string $releaseTag Release tag to store.
     * @param string $baseVersion Underlying vtiger version for a missing row.
     * @return void
     */
    public static function setInstalledTag($adb, $releaseTag, $baseVersion)
    {
        $adb->pquery(
            'INSERT INTO `vtiger_version` (`id`, `old_version`, `current_version`, `tag_version`) '
            .'VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `tag_version` = ?',
            array(1, $baseVersion, $baseVersion, $releaseTag, $releaseTag)
        );
    }
}
