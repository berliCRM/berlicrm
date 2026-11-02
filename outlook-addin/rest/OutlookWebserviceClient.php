<?php

class OutlookWebserviceClient
{
    private $endpointUrl;
    private $defaultTimeout;
    private $apiUsername;
    private $apiAccessKey;
    public $lastError = '';

    public function __construct(array $config = [])
    {
        $this->endpointUrl = rtrim($config['crm_webservice_url'] ?? '', '/');
        $this->defaultTimeout = isset($config['default_request_timeout']) ? (int)$config['default_request_timeout'] : 30;
        $this->apiUsername = $config['api_username'] ?? '';
        $this->apiAccessKey = $config['api_access_key'] ?? '';

        if (empty($this->endpointUrl)) {
            throw new InvalidArgumentException('CRM-Webservice-URL ist nicht konfiguriert.');
        }
        if (strpos($this->endpointUrl, '.php') === false) {
            $this->endpointUrl .= '/webservice.php';
        }
    }

    public function login(string $username, string $accessKey): array
    {
        $challenge = $this->getChallenge($username);
        $generatedKey = md5($challenge . $accessKey);

        $result = $this->callOperation('login', [
            'username' => $username,
            'accessKey' => $generatedKey,
        ], 'POST');

        if (!is_array($result)) {
            throw new RuntimeException('Login: Ungültige Antwort vom CRM.');
        }

        return $result;
    }

    public function query(string $query, string $sessionName): array
    {
        return $this->callOperation('query', ['query' => $query, 'sessionName' => $sessionName], 'GET');
    }

    public function retrieve(string $recordId, string $sessionName): array
    {
        return $this->callOperation('retrieve', ['id' => $recordId, 'sessionName' => $sessionName], 'GET');
    }

    public function create(array $element, string $sessionName): array
    {
        return $this->callOperation('create', ['element' => json_encode($element), 'sessionName' => $sessionName], 'POST');
    }

    public function customOperation(string $operationName, array $params, string $sessionName): array
    {
        $params['sessionName'] = $sessionName;
        return $this->callOperation($operationName, $params, 'POST');
    }

    public function loginWithApiUser(): array
    {
        if (empty($this->apiUsername) || empty($this->apiAccessKey)) {
            throw new RuntimeException('API-Benutzer für automatische Anmeldung ist nicht konfiguriert.');
        }
        return $this->login($this->apiUsername, $this->apiAccessKey);
    }

    private function getChallenge(string $username): string
    {
        $result = $this->callOperation('getchallenge', ['username' => $username], 'GET');
        if (!is_array($result) || !isset($result['token'])) {
            throw new RuntimeException('Challenge konnte nicht angefordert werden.');
        }
        return $result['token'];
    }

    private function callOperation(string $operationName, array $params, string $method = 'GET')
    {
        $params['operation'] = $operationName;
        $params['format'] = 'json';
        if (!isset($params['sessionName'])) {
            $params['sessionName'] = '';
        }
        return $this->sendRequest($params, strtoupper($method), $operationName);
    }

    private function sendRequest(array $params, string $method, string $operationName)
    {
        $curl = curl_init();
        if ($method === 'GET') {
            $url = $this->endpointUrl . '?' . http_build_query($params);
            curl_setopt($curl, CURLOPT_URL, $url);
        } else {
            curl_setopt($curl, CURLOPT_URL, $this->endpointUrl);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->defaultTimeout);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Expect:']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($curl);
        if ($response === false) {
            $this->lastError = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException('CRM-Anfrage fehlgeschlagen: ' . $this->lastError);
        }

        $httpStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpStatus < 200 || $httpStatus >= 300) {
            $decoded = json_decode($response, true);
            $message = '';
            if (is_array($decoded)) {
                if (isset($decoded['error']['message']) && is_string($decoded['error']['message'])) {
                    $message = $decoded['error']['message'];
                } elseif (isset($decoded['message']) && is_string($decoded['message'])) {
                    $message = $decoded['message'];
                } elseif (isset($decoded['error']) && is_string($decoded['error'])) {
                    $message = $decoded['error'];
                }
            } elseif (trim($response) === 'Unknown operation requested') {
                // OperationManager can fail before assignment in webservice.php.
                // That error is returned as plain text instead of a JSON envelope.
                $message = 'Unknown operation requested: Die Webservice-Operation ist nicht registriert.';
            }

            $error = "CRM-Webservice-Operation '$operationName' ist mit HTTP-Status $httpStatus fehlgeschlagen.";
            if ($message !== '') {
                $error .= ' ' . substr($message, 0, 300);
            }
            throw new RuntimeException($error);
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            throw new RuntimeException('Ungültige JSON-Antwort vom CRM: ' . $response);
        }

        if (isset($decoded['success']) && $decoded['success'] === false) {
            $message = $decoded['error']['message'] ?? json_encode($decoded['error']);
            throw new RuntimeException('CRM-Fehler: ' . $message);
        }

        return $decoded['result'] ?? $decoded;
    }
}
