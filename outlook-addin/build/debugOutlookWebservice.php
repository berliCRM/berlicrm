<?php

// Standalone HTTP diagnostic: no CRM bootstrap, database changes, or registrations.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Dieses Diagnosewerkzeug ist nur auf der Kommandozeile verfügbar.\n");
}

class OutlookWebserviceDebugger
{
    private $endpoint;
    private $transport;
    private $output;
    private $secrets = array();

    public function __construct($endpoint, callable $transport = null, callable $output = null)
    {
        $parts = parse_url($endpoint);
        if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new InvalidArgumentException('Bitte eine HTTPS-Webservice-URL ohne Zugangsdaten oder Query-Parameter angeben.');
        }
        $this->endpoint = preg_match('/\.php$/i', $parts['path'] ?? '')
            ? $endpoint : rtrim($endpoint, '/') . '/webservice.php';
        $this->transport = $transport ?: array($this, 'send');
        $this->output = $output ?: function ($line) { echo $line . PHP_EOL; };
    }

    public function protect($value)
    {
        if (is_string($value) && $value !== '') {
            $this->secrets[] = $value;
            $this->secrets[] = rawurlencode($value);
            $this->secrets[] = urlencode($value);
            $this->secrets[] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }

    public function log($message)
    {
        $secrets = array_unique($this->secrets);
        usort($secrets, function ($a, $b) { return strlen($b) - strlen($a); });
        $message = str_replace($secrets, '[VERDECKT]', (string)$message);
        // Keep each server-controlled message on one terminal line.
        $message = preg_replace('/[\x00-\x1f\x7f]/', ' ', $message);
        call_user_func($this->output, $message);
    }

    public function request($operation, array $params = array(), $method = 'GET', $label = null)
    {
        $allowed = array('getchallenge' => 'GET', 'login' => 'POST', 'listtypes' => 'GET',
            'describe' => 'GET', 'query' => 'GET', 'logout' => 'POST');
        if (!isset($allowed[$operation]) || $allowed[$operation] !== $method
            || ($operation === 'query' && !preg_match('/^SELECT\s/i', $params['query'] ?? ''))) {
            throw new InvalidArgumentException('Nicht zugelassene Diagnoseoperation.');
        }
        $label = $label ?: $operation;
        $params['operation'] = $operation;
        $params['format'] = 'json';
        foreach (array('sessionName', 'accessKey') as $key) {
            $this->protect($params[$key] ?? null);
        }
        try {
            $response = call_user_func($this->transport, $this->endpoint, $method, $params);
        } catch (Throwable $error) {
            $this->log('[' . $label . '] Transportfehler: ' . $error->getMessage());
            return array('ok' => false, 'status' => 0, 'result' => null);
        }
        $status = (int)$response['status'];
        $decoded = json_decode($response['body'], true);
        if (is_array($decoded['result'] ?? null)) {
            foreach (array('token', 'sessionName', 'accessKey') as $key) {
                $this->protect($decoded['result'][$key] ?? null);
            }
        }
        $ok = $status >= 200 && $status < 300 && is_array($decoded)
            && ($decoded['success'] ?? false) === true;
        $this->log('[' . $label . '] ' . $method . ' -> HTTP ' . $status . ' / ' . ($ok ? 'OK' : 'FEHLER'));
        if (!is_array($decoded)) {
            $this->log('  Keine gültige JSON-Antwort. Content-Type: ' . ($response['contentType'] ?? '(fehlt)'));
        } elseif (!$ok) {
            $error = $decoded['error'] ?? array();
            $this->log('  CRM-Code: ' . (is_array($error) ? ($error['code'] ?? '(fehlt)') : '(fehlt)'));
            $message = is_array($error) ? ($error['message'] ?? '') : (is_string($error) ? $error : '');
            $this->log('  CRM-Meldung: ' . substr((string)$message, 0, 1000));
        }
        return array('ok' => $ok, 'status' => $status, 'result' => $decoded['result'] ?? null);
    }

    public function run($username, $accessKey, $search)
    {
        $this->protect($accessKey);
        $this->log('Webservice: ' . $this->endpoint);
        $this->log('CRM-Benutzer: ' . $username);
        $this->log('Es werden keine CRM-Datensätze oder Berechtigungen geändert.');
        $challenge = $this->request('getchallenge', array('username' => $username));
        if (!$challenge['ok'] || !is_string($challenge['result']['token'] ?? null)) {
            return 2;
        }
        $hash = md5($challenge['result']['token'] . $accessKey);
        $this->protect($hash);
        $login = $this->request('login', array('username' => $username, 'accessKey' => $hash), 'POST');
        if (!$login['ok']) {
            return 2;
        }
        $session = $login['result']['sessionName'] ?? '';
        $userId = $login['result']['userId'] ?? '';
        if (!is_string($session) || $session === '') {
            $this->log('Die Loginantwort enthält keine gültige Sitzung.');
            return 2;
        }
        $this->protect($session);
        try {
            if (!is_string($userId) || !preg_match('/^[1-9][0-9]*x[1-9][0-9]*$/D', $userId)) {
                $this->log('Die Loginantwort enthält keine gültige CRM-Benutzer-ID.');
                return 2;
            }
            $this->log('Benutzer-Webservice-ID aus Login: ' . $userId);
            $types = $this->request('listtypes', array('sessionName' => $session));
            $visible = is_array($types['result']['types'] ?? null) ? $types['result']['types'] : array();
            if ($types['ok']) {
                $this->log('Verfügbare Module: ' . implode(', ', $visible));
                $this->log('Verteiler in listtypes: ' . (in_array('Verteiler', $visible, true) ? 'JA' : 'NEIN'));
            }
            $description = $this->request('describe', array('elementType' => 'Verteiler', 'sessionName' => $session));
            if ($description['ok']) {
                $fields = array();
                foreach (($description['result']['fields'] ?? array()) as $field) {
                    if (is_string($field['name'] ?? null)) {
                        $fields[] = $field['name'];
                    }
                }
                $this->log('Verfügbare Verteilerfelder: ' . implode(', ', $fields));
                $this->log('Zuständigkeitsfeld assigned_user_id: ' . (in_array('assigned_user_id', $fields, true) ? 'JA' : 'NEIN'));
            }
            $term = str_replace("'", "''", trim($search));
            $queries = array(
                'Kontakte-Kontrollabfrage' => 'SELECT id FROM Contacts LIMIT 1;',
                'Verteiler-ohne-Filter' => 'SELECT id FROM Verteiler LIMIT 1;',
                'Eigene-Verteiler-alle-Felder' => "SELECT * FROM Verteiler WHERE assigned_user_id = '$userId' LIMIT 5;",
                'Add-in-Suchabfrage' => "select id, verteilername from Verteiler where verteilername like '%$term%' and assigned_user_id = '$userId' limit 50;"
            );
            $results = array();
            foreach ($queries as $label => $query) {
                $this->log('Abfrage: ' . $query);
                $result = $this->request('query', array('sessionName' => $session, 'query' => $query), 'GET', $label);
                if ($result['ok'] && is_array($result['result'])) {
                    $this->log('  Gefundene Datensätze: ' . count($result['result']));
                }
                $results[$label] = $result;
            }
            $this->log('--- Auswertung ---');
            if ($types['ok'] && !in_array('Verteiler', $visible, true)) {
                $this->log('Verteiler fehlt in listtypes. Die Standardabfrage kann schon an der Modulfreigabe scheitern, bevor WHERE ausgewertet wird.');
                $this->log('Zu prüfen: Webservice-Registrierung des Moduls, Modulstatus, effektive Profilrechte und Berechtigungs-Cache.');
            } elseif ($results['Verteiler-ohne-Filter']['ok'] && !$results['Eigene-Verteiler-alle-Felder']['ok']) {
                $this->log('Der Modulzugriff funktioniert. Der Fehler entsteht bei der Zuständigkeitsbedingung oder der Feldauswahl.');
            } elseif ($results['Eigene-Verteiler-alle-Felder']['ok'] && !$results['Add-in-Suchabfrage']['ok']) {
                $this->log('Eigene Verteiler sind abfragbar. Bitte Verteilername-Feld und die konkrete Suchabfrage prüfen.');
            } elseif ($results['Add-in-Suchabfrage']['ok']) {
                $this->log('Die direkte Standard-Suchabfrage funktioniert. Bei weiterem Add-in-Fehler Sitzungsdaten und outlook-addin/rest/outlook.config.php vergleichen.');
            } else {
                $this->log('Die HTTP-Statuswerte und CRM-Meldungen oben zeigen den fehlschlagenden Schritt.');
            }
            foreach (array_merge(array($types, $description), array_values($results)) as $result) {
                if (!$result['ok']) {
                    return 2;
                }
            }
            return 0;
        } finally {
            // Only close the separate session created by this diagnostic login.
            $this->request('logout', array('sessionName' => $session), 'POST');
        }
    }

    private function send($endpoint, $method, array $params)
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Die PHP-CLI benötigt die cURL-Erweiterung.');
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $endpoint . ($method === 'GET' ? '?' . http_build_query($params) : ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => array('Expect:', 'Accept: application/json')
        ));
        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        try {
            $body = curl_exec($curl);
            if ($body === false) {
                throw new RuntimeException('cURL ' . curl_errno($curl) . ': ' . curl_error($curl));
            }
            return array('status' => curl_getinfo($curl, CURLINFO_HTTP_CODE), 'body' => $body,
                'contentType' => curl_getinfo($curl, CURLINFO_CONTENT_TYPE));
        } finally {
            curl_close($curl);
        }
    }
}

function outlookDebugReadAccessKey()
{
    $key = getenv('BERLICRM_DEBUG_ACCESS_KEY');
    if (is_string($key) && $key !== '') {
        return $key;
    }
    if (!function_exists('stream_isatty') || !stream_isatty(STDIN)) {
        throw new RuntimeException('Bitte BERLICRM_DEBUG_ACCESS_KEY setzen oder das Skript in einer interaktiven Konsole starten.');
    }
    if (PHP_OS_FAMILY === 'Windows') {
        if (!function_exists('proc_open')) {
            throw new RuntimeException('Bitte den Access Key über BERLICRM_DEBUG_ACCESS_KEY bereitstellen.');
        }
        // ReadKey(true) hides input; stdout is a private pipe back to PHP.
        $command = '$s = New-Object System.Text.StringBuilder; while ($true) { '
            . '$k = [Console]::ReadKey($true); if ($k.Key -eq "Enter") { break }; '
            . 'if ($k.Key -eq "Backspace") { if ($s.Length -gt 0) { $s.Length-- }; continue }; '
            . 'if (-not [char]::IsControl($k.KeyChar)) { [void]$s.Append($k.KeyChar) } }; '
            . '[Console]::Write($s.ToString())';
        fwrite(STDERR, 'CRM Access Key (Eingabe verdeckt): ');
        $process = proc_open(array('powershell.exe', '-NoProfile', '-Command', $command),
            array(0 => STDIN, 1 => array('pipe', 'w'), 2 => STDERR), $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Access Key konnte nicht verdeckt eingelesen werden.');
        }
        $key = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $status = proc_close($process);
        fwrite(STDERR, PHP_EOL);
        if ($status !== 0) {
            throw new RuntimeException('Access-Key-Eingabe fehlgeschlagen. Alternativ BERLICRM_DEBUG_ACCESS_KEY verwenden.');
        }
        return $key;
    }
    if (!function_exists('exec')) {
        throw new RuntimeException('Bitte den Access Key über BERLICRM_DEBUG_ACCESS_KEY bereitstellen.');
    }
    $state = exec('stty -g 2>/dev/null', $unused, $status);
    if ($status !== 0 || !preg_match('/^[a-fA-F0-9:]+$/D', $state)) {
        throw new RuntimeException('Access Key kann nicht verdeckt eingelesen werden. Bitte BERLICRM_DEBUG_ACCESS_KEY verwenden.');
    }
    exec('stty -echo', $unused, $status);
    if ($status !== 0) {
        throw new RuntimeException('Terminal konnte nicht auf verdeckte Eingabe umgestellt werden.');
    }
    try {
        fwrite(STDERR, 'CRM Access Key (Eingabe verdeckt): ');
        return rtrim((string)fgets(STDIN), "\r\n");
    } finally {
        exec('stty ' . escapeshellarg($state));
        fwrite(STDERR, PHP_EOL);
    }
}

function outlookDebugMain()
{
    $options = getopt('', array('help', 'url:', 'username:', 'search:'));
    if (isset($options['help'])) {
        echo "Aufruf: php outlook-addin/build/debugOutlookWebservice.php --username CRM-BENUTZER --search test\n"
            . "Optional: --url https://crm.example.org/webservice.php\n"
            . "Ohne --url wird outlook-addin/rest/outlook.config.php gelesen.\n"
            . "Der Access Key wird verdeckt abgefragt oder aus BERLICRM_DEBUG_ACCESS_KEY gelesen.\n"
            . "Prüft Login, listtypes, describe und SELECT-Abfragen; meldet die Testsitzung danach ab.\n"
            . "Es werden keine CRM-Datensätze, Berechtigungen oder Registrierungen geändert.\n";
        return 0;
    }
    $config = array();
    $configFile = dirname(__DIR__) . '/rest/outlook.config.php';
    if (!isset($options['url']) && is_readable($configFile)) {
        $config = require $configFile;
    }
    $url = $options['url'] ?? ($config['crm_webservice_url'] ?? '');
    if ($url === '') {
        throw new InvalidArgumentException('Bitte --url angeben oder outlook-addin/rest/outlook.config.php konfigurieren.');
    }
    $debugger = new OutlookWebserviceDebugger($url);
    if (!function_exists('curl_init')) {
        throw new RuntimeException('Die PHP-CLI benötigt die cURL-Erweiterung. Bitte auf dem CRM-Server ausführen.');
    }
    $username = $options['username'] ?? '';
    if ($username === '') {
        fwrite(STDERR, 'CRM-Benutzername: ');
        $username = trim((string)fgets(STDIN));
    }
    $accessKey = outlookDebugReadAccessKey();
    if ($username === '' || $accessKey === '') {
        throw new InvalidArgumentException('CRM-Benutzername und Access Key werden benötigt.');
    }
    return $debugger->run($username, $accessKey, $options['search'] ?? 'test');
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        exit(outlookDebugMain());
    } catch (Throwable $error) {
        fwrite(STDERR, 'Diagnose konnte nicht starten: ' . $error->getMessage() . PHP_EOL);
        exit(1);
    }
}
