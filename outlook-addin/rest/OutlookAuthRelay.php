<?php
/** Temporary encrypted sign-in responses, scoped to the initiating CRM session. */
class OutlookAuthRelay
{
    private $directory;
    private $clock;
    const LIFETIME = 300;

    /** The optional directory and clock are supplied only by server configuration/tests. */
    public function __construct(array $config, ?callable $clock = null)
    {
        $base = $config['contacts_auth_relay_directory'] ?? sys_get_temp_dir();
        $this->directory = rtrim($base, '/\\') . '/berlicrm-auth-' . hash('sha256', $config['crm_webservice_url'] ?? '');
        $this->clock = $clock ?? static function () { return time(); };
        if (is_link($this->directory) || (!is_dir($this->directory) && !@mkdir($this->directory, 0700, true) && !is_dir($this->directory))) {
            throw new RuntimeException(berli_outlookT('MICROSOFT_SIGN_IN_RELAY_UNAVAILABLE'));
        }
        if (DIRECTORY_SEPARATOR === '/' && (fileperms($this->directory) & 0077) !== 0) {
            throw new RuntimeException(berli_outlookT('MICROSOFT_SIGN_IN_RELAY_UNAVAILABLE'));
        }
        $checked = 0;
        foreach (new DirectoryIterator($this->directory) as $file) {
            if (++$checked > 128) break;
            if (!$file->isLink() && $file->isFile() && preg_match('/^[a-f0-9]{64}\.json$/D', $file->getFilename())
                && $file->getMTime() < ($this->clock)() - self::LIFETIME) {
                @unlink($file->getPathname());
            }
        }
    }

    private function filename($id)
    {
        if (!is_string($id) || !preg_match('/^[a-f0-9]{64}$/D', $id)) throw new InvalidArgumentException(berli_outlookT('INVALID_CRM_RESPONSE'));
        return $this->directory . '/' . $id . '.json';
    }

    private function owner(array $record, $session)
    {
        if (!is_string($session) || !hash_equals($record['owner'], hash('sha256', $session))) {
            throw new RuntimeException(berli_outlookT('THE_SIGNED_IN_ACCOUNT_HAS_CHANGED_PLEASE_START_THE_OPERATION_AGAIN'));
        }
    }

    private function transaction($id, callable $work, bool $allowExpired = false)
    {
        $path = $this->filename($id);
        if (is_link($path) || !is_file($path)) throw new RuntimeException(berli_outlookT('THE_SIGN_IN_HAS_EXPIRED_PLEASE_START_THE_CONNECTION_AGAIN_FROM_THE_CONTA'));
        $handle = @fopen($path, 'r+b');
        if (!$handle) throw new RuntimeException(berli_outlookT('MICROSOFT_SIGN_IN_RELAY_UNAVAILABLE'));
        try {
            if (!flock($handle, LOCK_EX)) throw new RuntimeException(berli_outlookT('MICROSOFT_SIGN_IN_RELAY_UNAVAILABLE'));
            $original = stream_get_contents($handle);
            $record = json_decode($original, true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($record) || (!$allowExpired && ($record['expiresAt'] ?? 0) <= ($this->clock)())) {
                throw new RuntimeException(berli_outlookT('THE_SIGN_IN_HAS_EXPIRED_PLEASE_START_THE_CONNECTION_AGAIN_FROM_THE_CONTA'));
            }
            $result = $work($record);
            $bytes = json_encode($record, JSON_THROW_ON_ERROR);
            if ($bytes !== $original) {
                rewind($handle);
                if (!ftruncate($handle, 0) || fwrite($handle, $bytes) !== strlen($bytes) || !fflush($handle)) {
                    throw new RuntimeException(berli_outlookT('MICROSOFT_SIGN_IN_RELAY_UNAVAILABLE'));
                }
            }
            return $result;
        } finally { flock($handle, LOCK_UN); fclose($handle); }
    }

    /** Called after the gateway has validated the CRM session. */
    public function begin(string $session): array
    {
        $id = bin2hex(random_bytes(32)); $writeKey = bin2hex(random_bytes(32));
        $expires = ($this->clock)() + self::LIFETIME;
        $path = $this->filename($id);
        $handle = @fopen($path, 'x+b');
        if (!$handle) throw new RuntimeException(berli_outlookT('MICROSOFT_SIGN_IN_RELAY_UNAVAILABLE'));
        try {
            chmod($path, 0600);
            $bytes = json_encode(['owner' => hash('sha256', $session), 'writeHash' => hash('sha256', $writeKey),
                'expiresAt' => $expires, 'state' => 'pending', 'payload' => null], JSON_THROW_ON_ERROR);
            if (fwrite($handle, $bytes) !== strlen($bytes)) throw new RuntimeException(berli_outlookT('MICROSOFT_SIGN_IN_RELAY_UNAVAILABLE'));
        } finally { fclose($handle); }
        return ['flowId' => $id, 'writeKey' => $writeKey, 'expiresAt' => $expires * 1000];
    }

    /** The popup may submit one encrypted reply using its unguessable write credential. */
    public function complete($id, $writeKey, $payload): array
    {
        if (!is_string($writeKey) || !preg_match('/^[a-f0-9]{64}$/D', $writeKey) || !is_array($payload)
            || !is_string($payload['iv'] ?? null) || !preg_match('/^[A-Za-z0-9_-]{16}$/D', $payload['iv'])
            || !is_string($payload['data'] ?? null) || strlen($payload['data']) < 22 || strlen($payload['data']) > 65536
            || !preg_match('/^[A-Za-z0-9_-]+$/D', $payload['data'])) {
            throw new InvalidArgumentException(berli_outlookT('INVALID_CRM_RESPONSE'));
        }
        return $this->transaction($id, static function (&$record) use ($writeKey, $payload) {
            if (!hash_equals($record['writeHash'], hash('sha256', $writeKey)) || $record['state'] !== 'pending') {
                throw new RuntimeException(berli_outlookT('INVALID_CRM_RESPONSE'));
            }
            $record['payload'] = ['iv' => $payload['iv'], 'data' => $payload['data']]; $record['state'] = 'ready';
            return ['accepted' => true];
        });
    }

    public function poll($id, string $session): array
    {
        return $this->transaction($id, function (&$record) use ($session) {
            $this->owner($record, $session);
            return ['state' => $record['state'], 'payload' => $record['payload']];
        });
    }

    public function close($id, string $session): array
    {
        $result = $this->transaction($id, function (&$record) use ($session) {
            $this->owner($record, $session); $record['state'] = 'closed'; $record['payload'] = null;
            return ['closed' => true];
        }, true);
        // A concurrent writer sees the closed state even if the filesystem delays deletion.
        @unlink($this->filename($id));
        return $result;
    }
}
