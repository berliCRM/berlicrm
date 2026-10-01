<?php

/** Streaming, uncompressed ZIP64. Central directory is spooled to disk, not RAM. */
class Settings_Vtiger_BackupArchive_Model
{
    private $file;
    private $central;
    private $count = 0;
    private $offset = 0;
    private $rate;
    private $reserve;
    private $deadline;
    private $memory;
    private $directory;

    public function __construct($id)
    {
        global $backup_rate_bytes, $backup_max_seconds, $backup_memory_mb;
        if (PHP_INT_SIZE < 8) { throw new RuntimeException('LBL_BACKUP_RUNTIME'); }
        $this->rate = max(65536, (int) ($backup_rate_bytes ?? 5242880));
        $this->reserve = Settings_Vtiger_Backup_Model::reserveBytes();
        $this->deadline = microtime(true) + max(1, (int) ($backup_max_seconds ?? 86400));
        $this->memory = memory_get_usage(true) + max(16, (int) ($backup_memory_mb ?? 128)) * 1048576;
        $this->directory = Settings_Vtiger_Backup_Model::directory();
        $this->file = Settings_Vtiger_Backup_Model::createFile(Settings_Vtiger_Backup_Model::path($id, '.partial'));
        $this->central = Settings_Vtiger_Backup_Model::createFile(Settings_Vtiger_Backup_Model::path($id, '.central'));
    }

    public function check()
    {
        if (microtime(true) > $this->deadline) { throw new RuntimeException('LBL_BACKUP_TIMEOUT'); }
        if (memory_get_usage(true) > $this->memory) { throw new RuntimeException('LBL_BACKUP_MEMORY'); }
        if (disk_free_space($this->directory) < $this->reserve + 2097152) {
            throw new RuntimeException('LBL_BACKUP_SPACE');
        }
    }

    private function write($bytes, $throttle = false)
    {
        for ($at = 0; $at < strlen($bytes); $at += 1048576) {
            $start = microtime(true);
            $this->check();
            $chunk = substr($bytes, $at, 1048576);
            Settings_Vtiger_Backup_Model::writeAll($this->file, $chunk);
            $this->offset += strlen($chunk);
            if ($throttle) {
                $delay = strlen($chunk) / $this->rate - (microtime(true) - $start);
                if ($delay > 0) { usleep((int) ($delay * 1000000)); }
            }
        }
    }

    /** Producer yields small strings; sizes and CRC are calculated as data arrives. */
    public function add($name, iterable $chunks, $mode = 0100600, $mtime = null)
    {
        if (strlen($name) > 65535 || strpos($name, '\\') !== false || strpos($name, "\0") !== false
            || $name[0] === '/' || in_array('..', explode('/', $name), true)) {
            throw new RuntimeException('LBL_BACKUP_FAILED');
        }
        $stamp = getdate($mtime ?? time());
        $date = ((max(1980, min(2107, $stamp['year'])) - 1980) << 9) | ($stamp['mon'] << 5) | $stamp['mday'];
        $time = ($stamp['hours'] << 11) | ($stamp['minutes'] << 5) | ($stamp['seconds'] >> 1);
        $start = $this->offset;
        // UTF-8 names, data descriptor, ZIP64 sizes even for small entries.
        $this->write(pack('VvvvvvVVVvv', 0x04034b50, 45, 0x0808, 0, $time, $date,
            0, 0xffffffff, 0xffffffff, strlen($name), 20) . $name . pack('vvPP', 1, 16, 0, 0));
        $crc = hash_init('crc32b');
        $size = 0;
        $buffer = '';
        foreach ($chunks as $chunk) {
            $buffer .= $chunk;
            if (strlen($buffer) >= 65536) {
                hash_update($crc, $buffer);
                $size += strlen($buffer);
                $this->write($buffer, true);
                $buffer = '';
            }
        }
        if ($buffer !== '') {
            hash_update($crc, $buffer);
            $size += strlen($buffer);
            $this->write($buffer, true);
        }
        $checksum = hexdec(hash_final($crc));
        $this->write(pack('VVPP', 0x08074b50, $checksum, $size, $size));
        $extra = pack('vvPPP', 1, 24, $size, $size, $start);
        $record = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 0x032d, 45, 0x0808, 0, $time, $date,
            $checksum, 0xffffffff, 0xffffffff, strlen($name), strlen($extra), 0, 0, 0,
            ($mode << 16) | (substr($name, -1) === '/' ? 0x10 : 0), 0xffffffff);
        Settings_Vtiger_Backup_Model::writeAll($this->central, $record . $name . $extra);
        ++$this->count;
        usleep(1000);
    }

    public function tree($path, $name, array $ancestors = array())
    {
        foreach (self::entries($path, $name, array($this, 'check'), $ancestors) as $entry) {
            list($path, $name, $stat) = $entry;
            if (substr($name, -1) === '/') {
                $this->add($name, array(), $stat['mode'], $stat['mtime']);
                continue;
            }
            $file = fopen($path, 'rb');
            if (!$file) { throw new RuntimeException('LBL_BACKUP_FAILED'); }
            try {
                $before = fstat($file);
                $this->add($name, self::chunks($file), $before['mode'], $before['mtime']);
                $after = fstat($file);
                if ($before['size'] !== $after['size'] || $before['mtime'] !== $after['mtime']) {
                    throw new RuntimeException('LBL_BACKUP_FILE_CHANGED');
                }
            } finally { fclose($file); }
        }
    }

    /** Shared traversal keeps the space estimate and archive exclusions identical. */
    public static function entries($path, $name, callable $check, array $ancestors = array())
    {
        $check();
        $real = realpath($path);
        if ($real === false) { throw new RuntimeException('LBL_BACKUP_FAILED'); }
        $directory = Settings_Vtiger_Backup_Model::directory();
        // Never recursively include this instance's own archives, including aliases.
        if ($real === $directory || strpos($real, $directory . '/') === 0) { return; }
        if (is_dir($path)) {
            if (in_array($real, $ancestors, true)) { throw new RuntimeException('LBL_BACKUP_RECURSION'); }
            $ancestors[] = $real;
            $stat = @stat($path);
            if ($stat === false) { throw new RuntimeException('LBL_BACKUP_FAILED'); }
            yield array($path, $name . '/', $stat);
            $entries = @opendir($path);
            if (!$entries) { throw new RuntimeException('LBL_BACKUP_FAILED'); }
            try {
                while (($entry = readdir($entries)) !== false) {
                    if (in_array($entry, array('.', '..', '.git', '.idea'), true)) { continue; }
                    yield from self::entries($path . '/' . $entry, $name . '/' . $entry, $check, $ancestors);
                }
            } finally { closedir($entries); }
        } elseif (is_file($path)) {
            $stat = @stat($path);
            if ($stat === false || !is_readable($path)) { throw new RuntimeException('LBL_BACKUP_FAILED'); }
            yield array($path, $name, $stat);
        } else { throw new RuntimeException('LBL_BACKUP_SPECIAL_FILE'); }
    }

    public static function chunks($file)
    {
        while (!feof($file)) {
            $chunk = fread($file, 1048576);
            if ($chunk === false) { throw new RuntimeException('LBL_BACKUP_FAILED'); }
            if ($chunk !== '') { yield $chunk; }
        }
    }

    public function finish()
    {
        $centralOffset = $this->offset;
        fflush($this->central);
        fseek($this->central, strlen(Settings_Vtiger_Backup_Model::GUARD));
        foreach (self::chunks($this->central) as $chunk) { $this->write($chunk, true); }
        $centralSize = $this->offset - $centralOffset;
        $endOffset = $this->offset;
        $this->write(pack('VPvvVVPPPP', 0x06064b50, 44, 45, 45, 0, 0,
            $this->count, $this->count, $centralSize, $centralOffset));
        $this->write(pack('VVPV', 0x07064b50, 0, $endOffset, 1));
        $this->write(pack('VvvvvVVv', 0x06054b50, 0, 0, 0xffff, 0xffff,
            0xffffffff, 0xffffffff, 0));
        fflush($this->file);
        if (!fsync($this->file)) { throw new RuntimeException('LBL_BACKUP_SPACE'); }
        return $this->offset;
    }

    public function __destruct()
    {
        if (is_resource($this->file)) { fclose($this->file); }
        if (is_resource($this->central)) { fclose($this->central); }
    }
}
