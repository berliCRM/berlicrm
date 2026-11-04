<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});

function check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

class VersionTestDatabase
{
    public $row;

    public function __construct($tag, $hasRow = true)
    {
        $this->row = $hasRow ? array(
            'id' => 1,
            'old_version' => '6.5.0',
            'current_version' => '6.5.0',
            'tag_version' => $tag,
        ) : null;
    }

    public function pquery($sql, $parameters)
    {
        if (strpos($sql, 'SELECT `tag_version`') === 0) {
            return $this->row === null ? array() : array($this->row);
        }
        if (strpos($sql, 'INSERT INTO `vtiger_version`') === 0) {
            if ($this->row === null) {
                $this->row = array(
                    'id' => $parameters[0],
                    'old_version' => $parameters[1],
                    'current_version' => $parameters[2],
                    'tag_version' => $parameters[3],
                );
            } else {
                $this->row['tag_version'] = $parameters[4];
            }
            return true;
        }

        throw new RuntimeException('Unexpected SQL in version test: '.$sql);
    }

    public function num_rows($result)
    {
        return count($result);
    }

    public function query_result($result, $row, $column)
    {
        return $result[$row][$column];
    }
}

require_once dirname(__DIR__, 2).'/include/utils/VersionUtils.php';
require_once dirname(__DIR__, 2).'/includes/runtime/cache/Connectors.php';

$targetTag = 'berlicrm-1.1.3';
$versionCases = array(
    'current' => new VersionTestDatabase($targetTag),
    'older' => new VersionTestDatabase('berlicrm-1.1.2'),
    'empty' => new VersionTestDatabase(''),
    'missing' => new VersionTestDatabase(null, false),
);

check(BerliCRM_Version_Utils::getInstalledTag($versionCases['current']) === $targetTag, 'Current tag was not read');
check(BerliCRM_Version_Utils::getInstalledTag($versionCases['older']) === 'berlicrm-1.1.2', 'Older tag was not read');
check(BerliCRM_Version_Utils::getInstalledTag($versionCases['empty']) === '', 'Empty tag was not preserved');
check(BerliCRM_Version_Utils::getInstalledTag($versionCases['missing']) === null, 'Missing row was not detected');

foreach (array('older', 'empty', 'missing') as $case) {
    BerliCRM_Version_Utils::setInstalledTag($versionCases[$case], $targetTag, '6.5.0');
    check($versionCases[$case]->row['id'] === 1, $case.' did not use canonical row id');
    check($versionCases[$case]->row['tag_version'] === $targetTag, $case.' did not receive current tag');
}

$cache = new Vtiger_Cache_Connector_Memory();
check($cache->get('missing') === false, 'Missing cache key did not return false');
$cache->set('to_html-Dashboard', 42);
check($cache->get('to_html-Dashboard') === 42, 'Cache value was not returned');
check($cache->flush() === true, 'Cache flush did not report success');
check($cache->get('to_html-Dashboard') === false, 'Cache flush retained a value');

$packageUpdate = file_get_contents(dirname(__DIR__, 2).'/vtlib/Vtiger/PackageUpdate.php');
check(
    preg_match('/\bvar\s+\$_migrations\s*=\s*false\s*;/', $packageUpdate) === 1,
    'Vtiger_PackageUpdate::$_migrations is not declared'
);

$installer = file_get_contents(dirname(__DIR__, 2).'/modules/Install/models/InitSchema.php');
check(strpos($installer, "installer_info']['svn_tag") === false, 'Installer still uses the legacy session tag');

$updater = file_get_contents(dirname(__DIR__, 2).'/db_update.php');
$currentCheck = strpos($updater, 'if ($installedtag == $current_release_tag)');
$versionWrite = strpos($updater, 'BerliCRM_Version_Utils::setInstalledTag');
check($currentCheck !== false && $versionWrite !== false, 'Updater version handling is incomplete');
check($currentCheck < $versionWrite, 'Current installations are not stopped before the version write');
check(strpos($updater, 'an unversioned installation') !== false, 'Unversioned installations are not identified');

echo "Version and PHP compatibility tests passed.\n";
