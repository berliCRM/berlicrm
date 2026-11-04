<?php
/**
 * HTTPS CRM webservice transport for the Outlook add-in.
 *
 * @package berliCRM.OfficeAddins
 */

/** Sends authenticated CRM webservice requests for the Outlook JSON gateway. */
class OutlookWebserviceClient
{
    /** @var string HTTPS CRM webservice endpoint. */
    private $endpointUrl;
    /** @var int Maximum HTTP request duration in seconds. */
    private $defaultTimeout;
    /** @var string Optional legacy API login name. */
    private $apiUsername;
    /** @var string Optional legacy API access key; never returned to the browser. */
    private $apiAccessKey;
    /** @var string Last cURL transport failure message. */
    public $lastError = '';

    /**
     * Initializes the CRM transport from the installation configuration.
     *
     * @param array<string,mixed> $config Endpoint, timeout and optional legacy API credentials.
     * @return void
     * @throws InvalidArgumentException When the CRM endpoint is not configured.
     */
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

    /**
     * Authenticates a CRM user using the standard challenge and access-key flow.
     *
     * @param string $username CRM login name.
     * @param string $accessKey Personal CRM API access key.
     * @return array<string,mixed>
     * @throws RuntimeException When authentication or the HTTP request fails.
     */
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

    /**
     * Runs a standard CRM SELECT query in the authenticated session.
     *
     * @param string $query CRM webservice query.
     * @param string $sessionName Authenticated CRM session identifier.
     * @return array
     * @throws RuntimeException On a CRM or transport error.
     */
    public function query(string $query, string $sessionName): array
    {
        return $this->callOperation('query', ['query' => $query, 'sessionName' => $sessionName], 'GET');
    }

    /**
     * Retrieves one CRM record subject to the current session permissions.
     *
     * @param string $recordId Webservice record identifier.
     * @param string $sessionName Authenticated CRM session identifier.
     * @return array<string,mixed>
     */
    public function retrieve(string $recordId, string $sessionName): array
    {
        return $this->callOperation('retrieve', ['id' => $recordId, 'sessionName' => $sessionName], 'GET');
    }

    /**
     * Submits a record to the standard CRM create operation.
     *
     * @param array<string,mixed> $element Record fields including the target element type.
     * @param string $sessionName Authenticated CRM session identifier.
     * @return array<string,mixed>
     */
    public function create(array $element, string $sessionName): array
    {
        return $this->callOperation('create', ['element' => json_encode($element), 'sessionName' => $sessionName], 'POST');
    }

    /**
     * Invokes an authenticated add-in operation using POST.
     *
     * @param string $operationName Registered CRM operation name.
     * @param array<string,mixed> $params Operation parameters.
     * @param string $sessionName Authenticated CRM session identifier.
     * @return array
     */
    public function customOperation(string $operationName, array $params, string $sessionName): array
    {
        $params['sessionName'] = $sessionName;
        return $this->callOperation($operationName, $params, 'POST');
    }

    /**
     * Authenticates using optional legacy API credentials from the configuration.
     *
     * @return array<string,mixed>
     * @throws RuntimeException When legacy credentials are missing or login fails.
     */
    public function loginWithApiUser(): array
    {
        if (empty($this->apiUsername) || empty($this->apiAccessKey)) {
            throw new RuntimeException('API-Benutzer für automatische Anmeldung ist nicht konfiguriert.');
        }
        return $this->login($this->apiUsername, $this->apiAccessKey);
    }

    /**
     * Requests the one-time challenge token for a CRM user.
     *
     * @param string $username CRM login name.
     * @return string
     * @throws RuntimeException If the response does not contain a token.
     */
    private function getChallenge(string $username): string
    {
        $result = $this->callOperation('getchallenge', ['username' => $username], 'GET');
        if (!is_array($result) || !is_string($result['token'] ?? null) || $result['token'] === '') {
            throw new RuntimeException('Challenge konnte nicht angefordert werden.');
        }
        return $result['token'];
    }

    /**
     * Adds the CRM operation envelope and delegates the HTTP request.
     *
     * @param string $operationName CRM webservice operation.
     * @param array<string,mixed> $params Form parameters.
     * @param string $method HTTP method.
     * @return array Decoded CRM result after response validation.
     */
    private function callOperation(string $operationName, array $params, string $method = 'GET')
    {
        $params['operation'] = $operationName;
        $params['format'] = 'json';
        if (!isset($params['sessionName'])) {
            $params['sessionName'] = '';
        }
        return $this->sendRequest($params, strtoupper($method), $operationName);
    }

    /**
     * Sends a verified HTTPS request and decodes the CRM response envelope.
     *
     * @param array<string,mixed> $params Encoded webservice parameters.
     * @param string $method GET or POST.
     * @param string $operationName Operation name used in error messages.
     * @return array Decoded CRM result after response validation.
     * @throws RuntimeException On cURL, HTTP, JSON or CRM errors.
     */
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
            unset($curl);
            throw new RuntimeException('CRM-Anfrage fehlgeschlagen: ' . $this->lastError);
        }

        $httpStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        unset($curl);

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
        if (!is_array($decoded)) {
            throw new RuntimeException('Ungültige JSON-Antwort vom CRM: ' . $response);
        }

        if (isset($decoded['success']) && $decoded['success'] === false) {
            $message = $decoded['error']['message'] ?? json_encode($decoded['error']);
            throw new RuntimeException('CRM-Fehler: ' . $message);
        }

        $result = $decoded['result'] ?? $decoded;
        if (!is_array($result)) {
            throw new RuntimeException('Ungültige CRM-Ergebnisstruktur.');
        }
        return $result;
    }
}
