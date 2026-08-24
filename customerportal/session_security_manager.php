<?php

/**
 * Central session and CSRF hardening for the customer portal.
 *
 * The implementation deliberately does not bind sessions to an IP address.
 * IP addresses can change legitimately when customers use mobile networks,
 * corporate proxies or load-balanced internet connections.
 */
final class SessionSecurityManager
{
    const TIMEOUT_SECONDS = 1800;
    const CSRF_SESSION_KEY = '__csrf_token';
    const INITIALIZED_SESSION_KEY = '__security_initialized';
    const LAST_ACTIVE_SESSION_KEY = '__security_last_active';
    const USER_AGENT_SESSION_KEY = '__security_user_agent';

    /**
     * Starts the session with hardened cookie settings and validates an
     * existing authenticated session.
     */
    public static function init()
    {
        if (session_status() === PHP_SESSION_NONE) {
            self::configureSessionCookie();
            session_start();
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('PHP sessions are not available.');
        }

        $now = time();
        $userAgentHash = self::getUserAgentHash();

        if (empty($_SESSION[self::INITIALIZED_SESSION_KEY])) {
            session_regenerate_id(true);
            $_SESSION[self::INITIALIZED_SESSION_KEY] = true;
            $_SESSION[self::USER_AGENT_SESSION_KEY] = $userAgentHash;
        } elseif (!empty($_SESSION['customer_id'])) {
            $lastActive = isset($_SESSION[self::LAST_ACTIVE_SESSION_KEY])
                ? (int) $_SESSION[self::LAST_ACTIVE_SESSION_KEY]
                : $now;

            if ($now - $lastActive > self::TIMEOUT_SECONDS) {
                self::invalidateAuthenticatedSession();
            }

            $storedUserAgentHash = isset($_SESSION[self::USER_AGENT_SESSION_KEY])
                ? (string) $_SESSION[self::USER_AGENT_SESSION_KEY]
                : '';

            if ($storedUserAgentHash === '' || !hash_equals($storedUserAgentHash, $userAgentHash)) {
                self::invalidateAuthenticatedSession();
            }
        }

        $_SESSION[self::LAST_ACTIVE_SESSION_KEY] = $now;

        if (isset($_SESSION['customer_id'])) {
            self::checkSessionLock($_SESSION['customer_id']);
        }
    }

    /**
     * Rotates all security identifiers after successful authentication.
     */
    public static function onLogin($customerId = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::init();
        }

        session_regenerate_id(true);
        $_SESSION[self::INITIALIZED_SESSION_KEY] = true;
        $_SESSION[self::LAST_ACTIVE_SESSION_KEY] = time();
        $_SESSION[self::USER_AGENT_SESSION_KEY] = self::getUserAgentHash();
        $_SESSION[self::CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
        if ($customerId !== null) {
            self::storeSessionLock($customerId);
        }
    }

    /**
     * Stores a session lock file to prevent parallel logins.
     */
    private static function storeSessionLock($customerId)
    {
        $lockDirectory = self::getLockDirectory();
        if (!is_dir($lockDirectory) && !mkdir($lockDirectory, 0700, true) && !is_dir($lockDirectory)) {
            throw new RuntimeException('Could not create the session lock directory.');
        }

        if (file_put_contents(self::getLockFile($customerId), session_id(), LOCK_EX) === false) {
            throw new RuntimeException('Could not write the session lock.');
        }
    }

    /**
     * Checks if another session is already active for this customer.
     * If so, the current session will be destroyed.
     */
    private static function checkSessionLock($customerId)
    {
        $lockFile = self::getLockFile($customerId);
        if (file_exists($lockFile)) {
            $storedSession = file_get_contents($lockFile);
            if (!is_string($storedSession) || !hash_equals($storedSession, session_id())) {
                self::invalidateAuthenticatedSession();
            }
        } else {
            self::storeSessionLock($customerId);
        }
    }

    /**
     * Returns a cryptographically secure token for state-changing forms.
     */
    public static function getCsrfToken()
    {
        self::init();

        if (empty($_SESSION[self::CSRF_SESSION_KEY])) {
            $_SESSION[self::CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CSRF_SESSION_KEY];
    }

    /**
     * Rejects non-POST requests and POST requests without the session token.
     */
    public static function requireValidPostRequest($submittedToken)
    {
        self::init();

        $sessionToken = isset($_SESSION[self::CSRF_SESSION_KEY])
            ? $_SESSION[self::CSRF_SESSION_KEY]
            : '';

        if (
            ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
            || !is_string($submittedToken)
            || !is_string($sessionToken)
            || $sessionToken === ''
            || !hash_equals($sessionToken, $submittedToken)
        ) {
            self::clearSession();
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            exit('Invalid request.');
        }
    }

    /**
     * Clears the current session during an explicit logout.
     */
    public static function destroy()
    {
        self::clearSession();
    }

    private static function configureSessionCookie()
    {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');

        session_set_cookie_params(array(
            'lifetime' => 0,
            'path' => self::getPortalPath(),
            'domain' => '',
            'secure' => self::isHttpsRequest(),
            'httponly' => true,
            'samesite' => 'Strict',
        ));
    }

    private static function invalidateAuthenticatedSession()
    {
        self::clearSession();
        $portalPath = self::getPortalPath();
        $loginPath = ($portalPath === '/' ? '' : $portalPath) . '/login.php?session_invalid=1';
        header('Location: ' . $loginPath);
        exit;
    }

    private static function clearSession()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        if (isset($_SESSION['customer_id'])) {
            self::removeOwnSessionLock($_SESSION['customer_id']);
        }

        $_SESSION = array();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', array(
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => isset($params['samesite']) ? $params['samesite'] : 'Strict',
            ));
        }

        session_destroy();
    }

    private static function removeOwnSessionLock($customerId)
    {
        $lockFile = self::getLockFile($customerId);
        if (!is_file($lockFile)) {
            return;
        }

        $storedSession = file_get_contents($lockFile);
        if (is_string($storedSession) && hash_equals($storedSession, session_id())) {
            unlink($lockFile);
        }
    }

    private static function getLockFile($customerId)
    {
        return self::getLockDirectory() . 'sess_' . hash('sha256', (string) $customerId);
    }

    private static function getLockDirectory()
    {
        $applicationId = substr(hash('sha256', __DIR__), 0, 16);
        return rtrim(sys_get_temp_dir(), '/\\')
            . DIRECTORY_SEPARATOR
            . 'customerportal_session_locks_'
            . $applicationId
            . DIRECTORY_SEPARATOR;
    }

    private static function getUserAgentHash()
    {
        return hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }

    private static function isHttpsRequest()
    {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off' && $https !== '0') {
            return true;
        }

        if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
            return true;
        }

        $forwardedProtocol = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
        return $forwardedProtocol === 'https';
    }

    private static function getPortalPath()
    {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $portalDirectory = basename(__DIR__);
        $marker = '/' . $portalDirectory . '/';
        $position = strpos($scriptName, $marker);

        if ($position !== false) {
            return substr($scriptName, 0, $position + strlen('/' . $portalDirectory));
        }

        $directory = str_replace('\\', '/', dirname($scriptName));
        $directory = rtrim($directory, '/');
        return ($directory === '' || $directory === '.') ? '/' : $directory;
    }
}
