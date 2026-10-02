<?php
require_once __DIR__ . '/BackupArchive.php';

/** Read metadata only: never create a dump or read file contents for the GUI. */
class Settings_Vtiger_BackupSpace_Model
{
    public static function calculate()
    {
        $result = array('free' => null, 'archive' => null, 'required' => null,
            'reserve' => Settings_Vtiger_Backup_Model::reserveBytes(), 'insufficient' => false);
        try {
            $directory = Settings_Vtiger_Backup_Model::directory();
            $free = @disk_free_space($directory);
            $result['free'] = $free === false ? null : $free;
            if (PHP_INT_SIZE < 8) { return $result; }
            // Bound filesystem traversal so very large/inaccessible trees cannot monopolize a request.
            $deadline = microtime(true) + 5;
            $check = function () use ($deadline) {
                if (microtime(true) > $deadline) { throw new RuntimeException('Estimate timeout'); }
            };
            $paths = Settings_Vtiger_Backup_Model::sourcePaths();
            $archive = 98; // ZIP64 end records.
            $central = 0;
            $add = function ($name, $size) use (&$archive, &$central) {
                // Local header + descriptor + central entry; central spool coexists with final ZIP.
                $archive += $size + 148 + 2 * strlen($name);
                $central += 74 + strlen($name);
            };
            foreach ($paths as $path) {
                foreach (Settings_Vtiger_BackupArchive_Model::entries($path[0], $path[1], $check) as $entry) {
                    $add($entry[1], substr($entry[1], -1) === '/' ? 0 : $entry[2]['size']);
                }
            }
            $add('database.sql', static::databaseBytes());
            // Allow for the manifest, including long external paths.
            $add('manifest.json', 65536 + 6 * strlen(json_encode($paths, JSON_THROW_ON_ERROR)));
            $result['archive'] = $archive;
            $result['required'] = ceil($archive * 1.20) + $central
                + 2 * strlen(Settings_Vtiger_Backup_Model::GUARD) + $result['reserve'] + 2097152;
            $result['insufficient'] = $result['free'] !== null && $result['free'] < $result['required'];
        } catch (Throwable $e) {
            // An incomplete scan must not be displayed as a complete estimate. Keep free space visible.
        }
        return $result;
    }

    protected static function databaseBytes()
    {
        $db = PearDatabase::getInstance();
        $query = $db->pquery('SELECT t.DATA_LENGTH AS data_bytes, t.TABLE_ROWS AS row_count, '
            . 'OCTET_LENGTH(t.TABLE_NAME) AS name_bytes, c.column_count, c.column_bytes '
            . 'FROM information_schema.TABLES t LEFT JOIN '
            . '(SELECT TABLE_SCHEMA, TABLE_NAME, COUNT(*) AS column_count, '
            . "SUM(OCTET_LENGTH(REPLACE(COLUMN_NAME, '`', '``')) + 3) AS column_bytes "
            . 'FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() '
            . 'GROUP BY TABLE_SCHEMA, TABLE_NAME) c '
            . 'ON c.TABLE_SCHEMA = t.TABLE_SCHEMA AND c.TABLE_NAME = t.TABLE_NAME '
            . "WHERE t.TABLE_SCHEMA = DATABASE() AND t.TABLE_TYPE = 'BASE TABLE'", array());
        if (!$query) { throw new RuntimeException('LBL_BACKUP_DUMP_FAILED'); }
        $bytes = 1048576; // Schema objects, views, triggers, routines and events (estimated).
        for ($i = 0; $i < $db->num_rows($query); ++$i) {
            $values = array();
            foreach (array('data_bytes', 'row_count', 'name_bytes', 'column_count', 'column_bytes') as $key) {
                $value = $db->query_result($query, $i, $key);
                if (!is_numeric($value)) { throw new RuntimeException('LBL_BACKUP_DUMP_FAILED'); }
                $values[$key] = (float) $value;
            }
            // Export hex-encodes every value and repeats INSERT + column names for each row.
            // InnoDB statistics are approximate; this is deliberately not an exact size or upper bound.
            $bytes += 2 * $values['data_bytes'] + $values['row_count']
                * (40 + 2 * $values['name_bytes'] + $values['column_bytes'] + 26 * $values['column_count'])
                + 65536;
        }
        return ceil($bytes);
    }

    public static function formatBytes($bytes)
    {
        if ($bytes === null) { return vtranslate('LBL_BACKUP_SPACE_UNKNOWN', 'Settings:Vtiger'); }
        $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
        $unit = 0;
        while ($bytes >= 1024 && $unit < count($units) - 1) { $bytes /= 1024; ++$unit; }
        return number_format($bytes, 2, '.', '') . ' ' . $units[$unit];
    }
}
