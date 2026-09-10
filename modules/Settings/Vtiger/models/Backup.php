<?php

/** CRM-owned backup storage. Every data file is also guarded against direct PHP requests. */
class Settings_Vtiger_Backup_Model
{
    const URL = 'index.php?module=Vtiger&parent=Settings&view=Backup';
    const GUARD = "<?php http_response_code(404); exit; __halt_compiler();\n";

    public static function root()
    {
        global $root_directory;
        return realpath($root_directory ?: __DIR__ . '/../../../..');
    }

    public static function directory()
    {
        $root = self::root();
        $directory = $root . '/storage/Backup';
        if (is_link($directory) || is_link($root . '/storage')) {
            throw new RuntimeException('LBL_BACKUP_DIRECTORY_ERROR');
        }
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('LBL_BACKUP_DIRECTORY_ERROR');
        }
        if (!is_writable($directory) || (fileperms($directory) & 0077) !== 0
            || (function_exists('posix_geteuid') && fileowner($directory) !== posix_geteuid())) {
            throw new RuntimeException('LBL_BACKUP_DIRECTORY_ERROR');
        }
        $deny = "Require all denied\n";
        if (!is_file($directory . '/.htaccess')) {
            if (file_put_contents($directory . '/.htaccess', $deny, LOCK_EX) === false) {
                throw new RuntimeException('LBL_BACKUP_DIRECTORY_ERROR');
            }
        }
        return $directory;
    }

    public static function createFile($path)
    {
        $file = fopen($path, 'x+b');
        if (!$file || !chmod($path, 0600)) {
            throw new RuntimeException('LBL_BACKUP_DIRECTORY_ERROR');
        }
        self::writeAll($file, self::GUARD);
        return $file;
    }

    public static function writeAll($file, $data)
    {
        for ($offset = 0; $offset < strlen($data); $offset += $written) {
            $written = fwrite($file, substr($data, $offset));
            if (!$written) {
                throw new RuntimeException('LBL_BACKUP_SPACE');
            }
        }
    }

    public static function openPayload($path)
    {
        $file = fopen($path, 'rb');
        if (!$file || fread($file, strlen(self::GUARD)) !== self::GUARD) {
            if (is_resource($file)) { fclose($file); }
            throw new RuntimeException('LBL_BACKUP_NOT_FOUND');
        }
        return $file;
    }

    public static function lock($name, $blocking = true)
    {
        $path = self::directory() . '/' . $name . '.lock.php';
        if (is_link($path)) {
            throw new RuntimeException('LBL_BACKUP_DIRECTORY_ERROR');
        }
        $handle = fopen($path, 'c');
        if (!$handle || !chmod($path, 0600)) {
            throw new RuntimeException('LBL_BACKUP_DIRECTORY_ERROR');
        }
        if (!flock($handle, LOCK_EX | ($blocking ? 0 : LOCK_NB))) {
            fclose($handle);
            return false;
        }
        if (fstat($handle)['size'] === 0) {
            self::writeAll($handle, self::GUARD);
            fflush($handle);
        }
        return $handle;
    }

    public static function path($id, $suffix = '.json')
    {
        if (!preg_match('/\A[a-f0-9]{32}\z/D', (string) $id)) {
            throw new RuntimeException('LBL_BACKUP_NOT_FOUND');
        }
        if (!in_array($suffix, array('.json', '.zip', '.partial', '.central'), true)) {
            throw new RuntimeException('LBL_BACKUP_NOT_FOUND');
        }
        $path = self::directory() . '/' . $id . $suffix . '.php';
        if (is_link($path)) {
            throw new RuntimeException('LBL_BACKUP_NOT_FOUND');
        }
        return $path;
    }

    public static function read($id)
    {
        $path = self::path($id);
        if (!is_file($path)) {
            throw new RuntimeException('LBL_BACKUP_NOT_FOUND');
        }
        $file = self::openPayload($path);
        try {
            $job = json_decode(stream_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        } finally { fclose($file); }
        if (($job['id'] ?? '') !== $id) {
            throw new RuntimeException('LBL_BACKUP_NOT_FOUND');
        }
        return $job;
    }

    public static function save(array $job)
    {
        $path = self::path($job['id']);
        $temp = self::directory() . '/.state-' . bin2hex(random_bytes(16)) . '.php';
        try {
            $file = self::createFile($temp);
            try {
                self::writeAll($file, json_encode($job, JSON_THROW_ON_ERROR));
                fflush($file);
            } finally { fclose($file); }
            if (!rename($temp, $path)) {
                throw new RuntimeException('LBL_BACKUP_DIRECTORY_ERROR');
            }
        } finally {
            if ($temp && is_file($temp)) {
                unlink($temp);
            }
        }
    }

    public static function jobs()
    {
        $jobs = array();
        foreach (glob(self::directory() . '/*.json.php') as $path) {
            $id = basename($path, '.json.php');
            if (preg_match('/\A[a-f0-9]{32}\z/D', $id)) {
                $jobs[] = self::read($id);
            }
        }
        usort($jobs, function ($a, $b) { return $b['created'] <=> $a['created']; });
        return $jobs;
    }

    public static function enqueue($user)
    {
        $lock = self::lock('queue');
        try {
            $jobs = self::jobs();
            foreach ($jobs as $job) {
                if (in_array($job['status'], array('queued', 'running'), true)) {
                    throw new RuntimeException('LBL_BACKUP_BUSY');
                }
            }
            if (count($jobs) >= 3) {
                throw new RuntimeException('LBL_BACKUP_LIMIT');
            }
            // Unlike vtigercron, the web bootstrap does not load the mail helpers.
            if (!function_exists('getUserEmailId')) {
                require_once __DIR__ . '/../../../Emails/mail.php';
            }
            $email = getUserEmailId('id', $user->getId());
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('LBL_BACKUP_EMAIL_REQUIRED');
            }
            self::save(array('id' => bin2hex(random_bytes(16)), 'user_id' => (int) $user->getId(),
                'created' => time(), 'status' => 'queued', 'error' => '', 'mail' => 'pending',
                'attempts' => 0, 'size' => 0));
        } finally {
            fclose($lock);
        }
    }

    public static function delete($id)
    {
        $lock = self::lock('queue');
        try {
            $job = self::read($id);
            if (in_array($job['status'], array('queued', 'running'), true)) {
                throw new RuntimeException('LBL_BACKUP_BUSY');
            }
            foreach (array('.zip', '.partial', '.central', '.json') as $suffix) {
                $path = self::path($id, $suffix);
                if (is_file($path) && !unlink($path)) {
                    throw new RuntimeException('LBL_BACKUP_DIRECTORY_ERROR');
                }
            }
        } finally {
            fclose($lock);
        }
    }

    /** Idempotent registration, called from installation and database update. */
    public static function install()
    {
        $db = PearDatabase::getInstance();
        if (!class_exists('Vtiger_Cron')) { require_once 'vtlib/Vtiger/Cron.php'; }
        if (!Vtiger_Cron::getInstance('Backup')) {
            Vtiger_Cron::register('Backup', 'cron/Backup.service', 900, 'Settings', 1, 0,
                'Create requested CRM backups');
        }
        $existing = $db->pquery('SELECT fieldid FROM vtiger_settings_field WHERE name = ?', array('LBL_BACKUP'));
        if ($db->num_rows($existing)) {
            return;
        }
        $block = $db->pquery('SELECT blockid FROM vtiger_settings_blocks WHERE label = ?', array('LBL_INTEGRATION'));
        if (!$db->num_rows($block)) {
            $blockId = $db->getUniqueID('vtiger_settings_blocks');
            if (!$db->pquery('INSERT INTO vtiger_settings_blocks (blockid, label, sequence) VALUES (?, ?, ?)',
                array($blockId, 'LBL_INTEGRATION', 5))) {
                throw new RuntimeException('Backup integration block registration failed');
            }
        } else {
            $blockId = $db->query_result($block, 0, 'blockid');
        }
        $id = $db->getUniqueID('vtiger_settings_field');
        $result = $db->pquery('INSERT INTO vtiger_settings_field '
            . '(fieldid, blockid, name, iconpath, description, linkto, sequence, active, pinned) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array($id, $blockId,
                'LBL_BACKUP', '', 'LBL_BACKUP_DESCRIPTION', self::URL, 15, 0, 0));
        if (!$result) {
            throw new RuntimeException('Backup settings registration failed');
        }
    }
}
