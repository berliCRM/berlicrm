<?php
/**
 * CLI generation of the installation-specific Outlook manifest from the configured CRM URL.
 *
 * @package berliCRM.OfficeAddins
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this manifest generator from the CRM server's command line.\n");
}

chdir(dirname(__DIR__, 2));
if (!is_readable('config.inc.php')) {
    fwrite(STDERR, "Please configure berliCRM first (config.inc.php is missing).\n");
    exit(1);
}

require_once 'config.inc.php';

if (!isset($site_URL) || !is_string($site_URL)) {
    fwrite(STDERR, "The configured CRM URL (site_URL) is missing.\n");
    exit(1);
}

$crmUrl = rtrim($site_URL, '/');
$urlParts = parse_url($crmUrl);
if (!$urlParts || empty($urlParts['scheme']) || strtolower($urlParts['scheme']) !== 'https'
    || empty($urlParts['host']) || isset($urlParts['user']) || isset($urlParts['pass'])
    || isset($urlParts['query']) || isset($urlParts['fragment'])
    || !filter_var($crmUrl, FILTER_VALIDATE_URL)) {
    fwrite(STDERR, "site_URL must be a valid HTTPS URL without credentials, query or fragment.\n");
    exit(1);
}

$templatePath = 'outlook-addin/manifest.xml';
$outputPath = 'outlook-addin/manifest.generated.xml';
$templateXml = file_get_contents($templatePath);
if ($templateXml === false) {
    fwrite(STDERR, "Outlook manifest template could not be read.\n");
    exit(1);
}

$document = new DOMDocument();
$previousErrors = libxml_use_internal_errors(true);
try {
    $loaded = $document->loadXML($templateXml, LIBXML_NONET);
} finally {
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrors);
}
if (!$loaded) {
    fwrite(STDERR, "Outlook manifest template is not valid XML.\n");
    exit(1);
}

$placeholder = 'https://crm.example.org';
$xpath = new DOMXPath($document);
foreach ($xpath->query('//*[@DefaultValue]') as $node) {
    $value = $node->getAttribute('DefaultValue');
    if (strpos($value, $placeholder) === 0) {
        $node->setAttribute('DefaultValue', $crmUrl . substr($value, strlen($placeholder)));
    }
}

$origin = 'https://' . $urlParts['host'] . (isset($urlParts['port']) ? ':' . $urlParts['port'] : '');
$domains = $xpath->query('//*[local-name()="AppDomains"]/*[local-name()="AppDomain"]');
if ($domains->length !== 1) {
    fwrite(STDERR, "Outlook manifest must contain exactly one AppDomain.\n");
    exit(1);
}
$domains->item(0)->textContent = $origin;

$generatedXml = $document->saveXML();
if ($generatedXml === false || strpos($generatedXml, $placeholder) !== false
    || file_put_contents($outputPath, $generatedXml, LOCK_EX) !== strlen($generatedXml)) {
    fwrite(STDERR, "The installation-specific manifest could not be generated.\n");
    exit(1);
}

echo "Generated $outputPath for $crmUrl.\n";
