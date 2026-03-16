<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License.
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger. All Rights Reserved.
 *************************************************************************************/

require_once('includes/main/WebUI.php');
require_once('include/utils/utils.php');
require_once('include/utils/UserInfoUtil.php');

if (!class_exists('HTMLPurifier_Bootstrap', false)) {
    require_once 'vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';
}

class Rss_RssHandler_Model{

    //--- RSS Fetch Properties ---//
    public $results;   // RSS feed body
    public $status;    // HTTP status code
    public $error;     // Error message if fetch fails
    public $headers;   // HTTP response headers
    public $url;       // Feed URL
    public $content;   // Parsed RSS content (optional)

    //--- RSS Cache Properties ---//
    private $cacheDir = 'storage/rss_cache'; // Cache directory
    private $expireTime = 3600;              // Cache TTL in seconds

    //--- Internal Properties ---//
    private $purifier;

    /**
     * Constructor
     * @param string|null $url
     * @param string|null $cacheDir
     * @param int|null $expireTime
     */
    public function __construct($url = null, $cacheDir = null, $expireTime = null) {
        if ($url) $this->url = $url;
        if ($cacheDir) $this->cacheDir = $cacheDir;
        if ($expireTime) $this->expireTime = $expireTime;
    }

    //=====================================
    // Fetch / Save / Load RSS / Show Entry
    //=====================================

    /**
     * Fetch RSS feed via cURL
     * @param string|null $url
     * @param array $headers
     * @return array
     */
    public function fetchRss($url = null, $headers = []) {
        if ($url) $this->url = $url;
        if (!$this->url) return $this->setError(400, "Keine URL angegeben");

        $ch = curl_init($this->url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'MagpieRSS/0.72 (+http://magpierss.sf.net)',
            CURLOPT_ENCODING       => '',
            CURLOPT_HEADER         => true,
        ]);

        if (!empty($headers)) {
            $headerArray = [];
            foreach ($headers as $k => $v) $headerArray[] = "$k: $v";
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        }

        $responseRaw = curl_exec($ch);
        $error       = curl_errno($ch) ? curl_error($ch) : '';
        $headerSize  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $status      = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $headerText = substr($responseRaw, 0, $headerSize);
        $body       = substr($responseRaw, $headerSize);
        $responseHeaders = array_filter(array_map('trim', explode("\r\n", $headerText)));

        curl_close($ch);

        $this->results = $body;
        $this->status  = $status;
        $this->error   = $error;
        $this->headers = $responseHeaders;

        return $this->handleStatusCode($status, $error) ? $this->setResults($this) : $this->handleStatusCode($status, $error);
    }

    /**
     * Save RSS feed to cache
     * @return array
     */
    public function saveRss() {
        if (!is_dir($this->cacheDir) && !mkdir($this->cacheDir, 0777, true)) {
            return $this->setError(500, "Konnte Cache-Ordner nicht erstellen: $this->cacheDir");
        }

        $savePath = $this->fileName($this->url);
        if (file_put_contents($savePath, $this->results) === false) {
            return $this->setError(500, "Konnte RSS nicht speichern: $savePath");
        }

        return $this->setResults(true);
    }

    /**
     * Load RSS/Atom from cache and convert to array
     * @param string|null $url
     * @return array
     */
    public function loadRss($url = null) {
        if ($url) $this->url = $url;
        if (!$this->url) return $this->setError(400, "Keine URL angegeben");

        $fileName = $this->fileName($this->url);
        if (!file_exists($fileName)) return $this->setError(404, "Cache-Datei nicht gefunden");

        $xmlContent = file_get_contents($fileName);
        if ($xmlContent === false || trim($xmlContent) === '') {
            return $this->setError(500, "Konnte RSS-Datei nicht lesen oder Datei leer");
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $messages = array_map(fn($e) => trim($e->message), $errors);
            return $this->setError(500, "Ungültige XML in Cache-Datei: " . implode("; ", $messages));
        }

        $rootName   = strtolower($xml->getName());
        $namespaces = $xml->getNamespaces(true);
        $items      = [];

        if ($rootName === 'rss' && isset($xml->channel->item)) {
            // RSS 2.0
            foreach ($xml->channel->item as $item) {
                $itemArray = json_decode(json_encode($item), true);
                if (isset($item->description)) $itemArray['description'] = $this->purifyHtml((string)$item->description);
                if (isset($namespaces['content'])) {
                    $contentEncoded = $item->children($namespaces['content'])->encoded;
                    if ($contentEncoded) $itemArray['content_encoded'] = $this->purifyHtml((string)$contentEncoded);
                }
                $items[] = $itemArray;
            }
            $resultArray = json_decode(json_encode($xml), true);
            $resultArray['channel']['item'] = $items;

        } elseif ($rootName === 'feed' && isset($xml->entry)) {
            // Atom
            foreach ($xml->entry as $entry) {
                $entryArray = json_decode(json_encode($entry), true);
                $entryArray['summary'] = isset($entry->summary) ? $this->purifyHtml((string)$entry->summary) : '';
                $entryArray['content'] = isset($entry->content) ? $this->purifyHtml((string)$entry->content) : '';
                if (isset($entry->link)) {
                    $linkAttrs = $entry->link->attributes();
                    if (isset($linkAttrs['href'])) $entryArray['link'] = (string)$linkAttrs['href'];
                }
                $items[] = $entryArray;
            }

            $resultArray = json_decode(json_encode($xml), true);
            $resultArray['channel'] = [
                'title' => $resultArray['title'] ?? '',
                'link'  => '',
                'item'  => $items
            ];

            if (isset($xml->link)) {
                foreach ($xml->link as $link) {
                    $attrs = $link->attributes();
                    if ((string)($attrs['rel'] ?? '') === 'alternate') {
                        $resultArray['channel']['link'] = (string)$attrs['href'];
                        break;
                    }
                }
            }

        } else {
            return $this->setError(500, "Unbekanntes Feed-Format (weder RSS noch Atom)");
        }

        return $this->setResults($resultArray);
    }

    /**
     * Handle RSS fetch & cache automatically
     * @param string|null $url
     * @return array
     */
    public function handleRss($url = null) {
        if (!$url) $url = $this->url;
        if (!$this->lookRssUpToDate($url)) {
            $response = $this->fetchRss($url);
            if ($this->isSuccess($response)) $this->saveRss();
            else return $response;
        }
        return $this->loadRss($url);
    }

    /**
     * Check cache file and update if expired
     * @param string|null $url
     * @return bool
     */
    public function lookRssUpToDate($url = null) {
        if ($url) $this->url = $url;
        if (!$this->existsFileCache()) return false;

        $fileName = $this->fileName($this->url);
        if ((time() - filectime($fileName)) <= $this->expireTime) return true;

        $response = $this->fetchRss($this->url);
        if ($this->isSuccess($response)) {
            $this->saveRss();
            return true;
        }

        return false;
    }

    public function showRssEntry($entryUrl) {
        require_once('/var/www/fr2/www/berliCRM/include/database/PearDatabase.php');
        $adb = PearDatabase::getInstance();

        if (!filter_var($entryUrl, FILTER_VALIDATE_URL)) {
            return false; 
        }

        $host = "%".parse_url($entryUrl, PHP_URL_HOST)."%";
        $sql = "SELECT * FROM vtiger_rss WHERE rssurl LIKE ?";
        $res = $adb->pquery($sql, array($host));
        $num_rows = $adb->num_rows($res);

        if ($num_rows < 1) {
            return false;
        }

        $matchedFeed = null;
        $entryBase = $this->normalizeBaseUrl($entryUrl);

        while ($row = $adb->fetch_array($res)) {
            $feedBase = $this->normalizeBaseUrl($row['rssurl']);
            if ($feedBase && stripos($entryBase, $feedBase) === 0) {
                $matchedFeed = $row;
                break;
            }
        }

        if (!$matchedFeed) {
            $matchedFeed = $adb->fetchByAssoc($res, 0);
        }

        $rss = $this->loadRss($matchedFeed['rssurl']);
        if (!$this->isSuccess($rss)) {
            return false;
        }

        $entries = $this->normalizeFeedEntries($rss);
        foreach ($entries as $entry) {
            if (isset($entry['link']) && trim($entry['link']) === trim($entryUrl)) {
                $entry['content'] = $this->purifyHtml($entry['content']);
                return $entry;
            }
        }

        return false;
    }

    /**
     * Normalize feed entries (RSS or Atom) into a consistent array structure.
     * @param array $rss The result array returned by loadRss()
     * @return array|false Normalized entries array or false on error
     */
    public function normalizeFeedEntries($rss) {
        if (!$this->isSuccess($rss)) return false;
        $results = $this->getResult($rss);
        $normalized = [];

        $extractText = fn($v) => is_string($v) ? $v : (is_array($v) ? ($v[0] ?? $v['#text'] ?? '') : '');
        $normalizeDate = fn($d) => ($ts = @strtotime($d)) ? date('Y-m-d H:i:s', $ts) : '';

        // --- Atom ---
        if (isset($results['entry'])) {
            $entries = isset($results['entry'][0]) ? $results['entry'] : [$results['entry']];
            foreach ($entries as $entry) {
                $link = $entry['link']['@attributes']['href'] ?? ($entry['link'][0]['@attributes']['href'] ?? '');
                $author = $entry['author']['name'] ?? '';
                $categories = array_map(fn($c) => $c['@attributes']['term'] ?? $c, (array)($entry['category'] ?? []));
                $normalized[] = [
                    'title' => $entry['title'] ?? '',
                    'link' => $link,
                    'date' => $normalizeDate($entry['updated'] ?? $entry['published'] ?? ''),
                    'author' => $author,
                    'summary' => $entry['summary'] ?? '',
                    'content' => $entry['content'] ?? $entry['summary'] ?? '',
                    'guid' => $entry['id'] ?? '',
                    'categories' => $categories
                ];
            }
            return $normalized;
        }

        // --- RSS 2.0 ---
        if (isset($results['channel']['item'])) {
            $items = isset($results['channel']['item'][0]) ? $results['channel']['item'] : [$results['channel']['item']];
            foreach ($items as $item) {
                $link = is_array($item['link'] ?? null) ? $extractText($item['link']) : ($item['link'] ?? '');
                $author = is_array($item['author'] ?? null) ? ($item['author']['name'] ?? '') : ($item['author'] ?? '');
                $categories = [];
                if (isset($item['category'])) {
                    $cats = $item['category'];
                    $cats = is_array($cats) ? $cats : [$cats];
                    $categories = array_map($extractText, $cats);
                }
                $normalized[] = [
                    'title' => $extractText($item['title'] ?? ''),
                    'link' => $link,
                    'date' => $normalizeDate($item['pubDate'] ?? ''),
                    'author' => $author,
                    'summary' => $extractText($item['description'] ?? ''),
                    'content' => $extractText($item['content_encoded'] ?? $item['description'] ?? ''),
                    'guid' => $extractText($item['guid'] ?? $item['link'] ?? ''),
                    'categories' => $categories
                ];
            }
            return $normalized;
        }

        return false;
    }


    //=====================================
    // HTML Purifier
    //=====================================
    private function getPurifier() {
        if (!$this->purifier) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'p,b,a[href],i,em,strong,ul,ol,li,img[src|alt|title|width|height],br,span,item,div,h1,h2,h3,h4,h5,h6,blockquote,pre');
            $this->purifier = new HTMLPurifier($config);
        }
        return $this->purifier;
    }

    public function purifyHtml($html) {
        return $this->getPurifier()->purify($html);
    }

    //=====================================
    // Cache Management
    //=====================================
    public function fileName($url = null) {
        $url = $url ?? $this->url;
        return $this->cacheDir . DIRECTORY_SEPARATOR . md5($url);
    }

    public function existsFileCache() {
        return $this->url ? file_exists($this->fileName($this->url)) : false;
    }

    public function deleteFileCache($url = null) {
        if ($url) $this->url = $url;
        if (!$this->url) return $this->setError(400, "Keine URL angegeben");

        $file = realpath($this->fileName($this->url));
        if ($file && str_starts_with($file, realpath($this->cacheDir))) {
            return unlink($file) ? $this->setResults(true) : $this->setError(500, "Konnte Cache-Datei nicht löschen");
        }
        return $this->setError(404, "Cache-Datei nicht gefunden");
    }

    public function clearCache() {
        if (!is_dir($this->cacheDir)) return $this->setError(500, "Cache-Ordner existiert nicht: $this->cacheDir");

        $files = glob($this->cacheDir . DIRECTORY_SEPARATOR . '*');
        if ($files === false) return $this->setError(500, "Fehler beim Lesen des Cache-Ordners: $this->cacheDir");

        $deleted = 0;
        foreach ($files as $file) {
            if (is_file($file) && unlink($file)) $deleted++;
        }

        return $this->setResults($deleted);
    }

    //=====================================
    // Response & Helpers
    //=====================================
    public function setResults($results) {
        return ['success' => true, 'results' => $results];
    }

       public function setError($code, $message) {
        return [
            'success' => false,
            'error'   => [
                'code'    => $code,
                'message' => $message
            ]
        ];
    }

    /**
     * Prüft, ob ein Response-Array Erfolg signalisiert
     * @param array $response
     * @return bool
     */
    public function isSuccess($response) {
        return isset($response['success']) && $response['success'] === true;
    }

    /**
     * Liefert die eigentlichen Ergebnisse oder den Fehler aus einem Response-Array.
     * @param array $response
     * @return mixed
     */
    public function getResult($response = []) {
        if (!isset($response['success'])) return false;
        return $response['success'] ? $response['results'] : $response['error'];
    }

    /**
     * Handle HTTP Status Codes
     * @param int $status
     * @param string $error
     * @return bool|array
     */
    public function handleStatusCode($status, $error) {
        if ($status >= 200 && $status < 300) return true;
        if ($status >= 300 && $status < 400) {
            if ($status === 304) return true; // Not Modified
            // TODO: Handle redirects if nötig
        }
        if (($status >= 400 && $status < 600) || ($status == 0 && str_contains($error, 'Could not resolve host'))) {
            return $this->setError($status ?: 500, $error);
        }
        return false;
    }

    /** 
     * Getter Methods
     */  
    public function getRssStatus() {
        return $this->status;
    }
    public function getRssErrorCode() {
        return $this->error;
    }
    public function getRssResults() {
        return $this->results;
    }
    public function getRssHeaders() {
        return $this->headers;
    }
    public function getRssContent() {return $this->content;
    }
    public function getRssUrl() {
        return $this->url;
    }

    private function normalizeBaseUrl($url) {
        $parts = parse_url(trim($url));
        if (!$parts || empty($parts['host'])) {
            return false;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = strtolower($parts['host']);

        // Entferne evtl. "www."
        $host = preg_replace('/^www\./', '', $host);

        // Basis-URL zusammenbauen
        return "{$scheme}://{$host}";
    }

    public function getFeedCssStyle() {
        $style = 
        '<style>
            .feedFrame img,
            .feedListContainer img {
                max-width: 1px;
                max-height: 1px;
                vertical-align: middle;
            }
        </style>';
        return $style;
    }

    /**
     * Return Last-Modified header
     * @return string|false
     */
    public function getRssLastModifiedTime() {
        foreach ($this->headers ?? [] as $header) {
            if (stripos($header, 'Last-Modified:') === 0) return trim(substr($header, 14));
        }
        return false;
    }

    /**
     * Return Date header
     * @return string|false
     */
    public function getRssNewestModifiedTime() {
        foreach ($this->headers ?? [] as $header) {
            if (stripos($header, 'Date:') === 0) return trim(substr($header, 6));
        }
        return false;
    }

    /**
     * Return charset from Content-Type
     * @return string|false
     */
    public function getRssCharset() {
        foreach ($this->headers ?? [] as $h) {
            if (stripos($h, 'Content-Type:') === 0) {
                foreach (explode(';', $h) as $part) {
                    $part = trim($part);
                    if (stripos($part, 'charset=') === 0) return trim(substr($part, 8));
                }
            }
        }
        return false;
    }
}
