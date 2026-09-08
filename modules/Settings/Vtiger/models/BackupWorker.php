<?php
require_once __DIR__ . '/BackupArchive.php';
require_once __DIR__ . '/BackupDatabase.php';

class Settings_Vtiger_BackupWorker_Model
{
    public static function run()
    {
        $worker = Settings_Vtiger_Backup_Model::lock('worker', false);
        if (!$worker) {
            return;
        }
        try {
            $lock = Settings_Vtiger_Backup_Model::lock('queue');
            try {
                $next = null;
                foreach (Settings_Vtiger_Backup_Model::jobs() as $job) {
                    if ($job['status'] === 'running') {
                        // No worker holds the lock: the previous run was interrupted.
                        $job['status'] = 'failed';
                        $job['error'] = 'LBL_BACKUP_INTERRUPTED';
                        foreach (array('.partial', '.central', '.zip') as $suffix) {
                            $path = Settings_Vtiger_Backup_Model::path($job['id'], $suffix);
                            if (is_file($path)) {
                                unlink($path);
                            }
                        }
                        Settings_Vtiger_Backup_Model::save($job);
                    } elseif ($job['status'] === 'queued') {
                        $next = $job;
                    }
                }
                if ($next) {
                    $next['status'] = 'running';
                    Settings_Vtiger_Backup_Model::save($next);
                }
            } finally {
                fclose($lock);
            }
            if ($next) {
                static::build($next);
            }
            self::notify();
        } finally {
            fclose($worker);
        }
    }

    protected static function database()
    {
        return new Settings_Vtiger_BackupDatabase_Model();
    }

    private static function build(array $job)
    {
        global $dbconfig, $backup_extra_paths;
        $archive = null;
        try {
            if (PHP_INT_SIZE < 8 || !extension_loaded('mysqli')) {
                throw new RuntimeException('LBL_BACKUP_RUNTIME');
            }
            $archive = new Settings_Vtiger_BackupArchive_Model($job['id']);
            $archive->add('database.sql', static::database()->export());
            $archive->tree(Settings_Vtiger_Backup_Model::root(), 'crm');
            $external = array();
            foreach (($backup_extra_paths ?? array()) as $index => $extra) {
                if (!is_string($extra) || $extra === '' || $extra[0] !== '/') {
                    throw new RuntimeException('LBL_BACKUP_EXTRA_PATH');
                }
                $name = 'external/' . count($external);
                $archive->tree($extra, $name);
                $external[$name] = $extra;
            }
            $archive->add('manifest.json', array(json_encode(array('format' => 2,
                'created_utc' => gmdate('c'), 'database' => $dbconfig['db_name'],
                'root' => Settings_Vtiger_Backup_Model::root(), 'external_paths' => $external,
                'consistency' => 'InnoDB snapshot; live files, no atomic database/filesystem snapshot.',
                'excluded' => array('storage/Backup (including symlink aliases)', '.git', '.idea'),
                'symlinks' => 'Targets included as ordinary files/directories'), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)));
            $job['size'] = $archive->finish();
            unset($archive);
            $path = Settings_Vtiger_Backup_Model::path($job['id'], '.zip');
            if (!rename(Settings_Vtiger_Backup_Model::path($job['id'], '.partial'), $path)) {
                throw new RuntimeException('LBL_BACKUP_FAILED');
            }
            $job['status'] = 'ready';
            $job['completed'] = time();
        } catch (Throwable $e) {
            $job['status'] = 'failed';
            $job['error'] = preg_match('/\ALBL_BACKUP_[A-Z_]+\z/', $e->getMessage()) ? $e->getMessage() : 'LBL_BACKUP_FAILED';
        } finally {
            unset($archive);
            foreach (array('.partial', '.central') as $suffix) {
                $path = Settings_Vtiger_Backup_Model::path($job['id'], $suffix);
                if (is_file($path)) { unlink($path); }
            }
        }
        $lock = Settings_Vtiger_Backup_Model::lock('queue');
        try {
            Settings_Vtiger_Backup_Model::save($job);
        } finally {
            fclose($lock);
        }
    }

    private static function notify()
    {
        global $site_URL;
        foreach (Settings_Vtiger_Backup_Model::jobs() as $job) {
            if ($job['status'] !== 'ready' || $job['mail'] === 'sent' || $job['attempts'] >= 3) {
                continue;
            }
            // Reserve an attempt under the queue lock, but release it before SMTP.
            $lock = Settings_Vtiger_Backup_Model::lock('queue');
            try {
                if (!is_file(Settings_Vtiger_Backup_Model::path($job['id']))) {
                    continue;
                }
                $job['attempts']++;
                $job['mail'] = 'failed';
                Settings_Vtiger_Backup_Model::save($job);
            } finally {
                fclose($lock);
            }
            $sent = false;
            try {
                $db = PearDatabase::getInstance();
                $result = $db->pquery('SELECT id FROM vtiger_users WHERE id = ? AND is_admin = ? AND status = ?',
                    array($job['user_id'], 'on', 'Active'));
                if ($db->num_rows($result)) {
                    $email = getUserEmailId('id', $job['user_id']);
                    $url = htmlspecialchars(rtrim($site_URL, '/') . '/' . Settings_Vtiger_Backup_Model::URL, ENT_QUOTES, 'UTF-8');
                    $subject = vtranslate('LBL_BACKUP_MAIL_SUBJECT', 'Settings:Vtiger');
                    $body = htmlspecialchars(vtranslate('LBL_BACKUP_MAIL_BODY', 'Settings:Vtiger'), ENT_QUOTES, 'UTF-8')
                        . '<br><a href="' . $url . '">Backup</a>';
                    $sent = filter_var($email, FILTER_VALIDATE_EMAIL)
                        && send_mail('Users', $email, 'berliCRM', '', $subject, $body) == 1;
                }
            } catch (Throwable $e) {
                // Mail failure must never invalidate a downloadable backup.
            }
            $lock = Settings_Vtiger_Backup_Model::lock('queue');
            try {
                if (is_file(Settings_Vtiger_Backup_Model::path($job['id']))) {
                    $job['mail'] = $sent ? 'sent' : 'failed';
                    Settings_Vtiger_Backup_Model::save($job);
                }
            } finally {
                fclose($lock);
            }
        }
    }
}
