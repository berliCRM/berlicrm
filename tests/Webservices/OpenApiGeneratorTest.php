<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/include/Webservices/OpenApiGenerator.php';

class OpenApiGeneratorTest extends TestCase
{
    private $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/berlicrm-openapi-' . uniqid('', true);
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->root)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->root);
    }

    public function testBuildsGetAndPostOperationsFromFunctionsInOneFile(): void
    {
        $source = <<<'PHP'
<?php
/**
 * @berli-openapi-begin
 * {"summary":"Alpha","description":"Alpha operation.","tags":["Test"],"parameters":{"value":{"required":true,"description":"Value.","example":"hello"}},"result":{"schema":{"type":"string"},"example":"ok"}}
 * @berli-openapi-end
 */
function handler_alpha($value) {}
/**
 * @berli-openapi-begin
 * {"summary":"Beta","description":"Beta operation.","tags":["Test"],"parameters":{"payload":{"required":true,"description":"JSON payload.","example":"{\"id\":1}","decodedSchema":{"type":"object"}}},"result":{"schema":{"type":"object"},"example":{"saved":true}}}
 * @berli-openapi-end
 */
function handler_beta($payload) {}
PHP;
        file_put_contents($this->root . '/handlers.php', $source);
        $rows = array(
            $this->row(1, 'alpha', 'handlers.php', 'handler_alpha', 'GET', 1, 'value', 'String', 1),
            $this->row(2, 'beta', 'handlers.php', 'handler_beta', 'POST', 0, 'payload', 'Encoded', 1)
        );

        $document = (new BerliCRM_OpenApiGenerator(new OpenApiGeneratorFakeDb($rows), $this->root))->generate();

        $this->assertSame('3.0.3', $document['openapi']);
        $this->assertCount(2, $document['paths']);
        $this->assertArrayNotHasKey('x-berlicrm-documentation-warnings', $document);
        $get = $document['paths']['/webservice.php/operations/alpha']['get'];
        $this->assertSame('query', $get['parameters'][1]['in']);
        $this->assertTrue($get['parameters'][1]['required']);
        $post = $document['paths']['/webservice.php/operations/beta']['post'];
        $this->assertSame('POST', $post['x-berlicrm-real-request']['method']);
        $payload = $post['requestBody']['content']['application/x-www-form-urlencoded']['schema']['properties']['payload'];
        $this->assertSame('string', $payload['type']);
        $this->assertSame('object', $payload['x-berlicrm-decoded-schema']['type']);
        $this->assertSame(array(array('SessionName' => array())), $post['security']);
    }

    public function testInvalidMissingMismatchedAndOutsideMetadataUseSafeFallbacks(): void
    {
        file_put_contents($this->root . '/invalid.php', '<?php /** @berli-openapi-begin {bad json} @berli-openapi-end */ function invalid_handler() {}');
        file_put_contents($this->root . '/mismatch.php', '<?php /** @berli-openapi-begin {"summary":"Mismatch","description":"Mismatch.","tags":["Test"],"parameters":{},"result":{"schema":{},"example":{}}} @berli-openapi-end */ function mismatch_handler($value) {}');
        $outside = dirname($this->root) . '/berlicrm-openapi-outside-' . uniqid() . '.php';
        file_put_contents($outside, '<?php function outside_handler() {}');
        $rows = array(
            $this->row(1, 'invalid', 'invalid.php', 'invalid_handler', 'GET', 1),
            $this->row(2, 'missing', 'invalid.php', 'missing_handler', 'GET', 1),
            $this->row(3, 'mismatch', 'mismatch.php', 'mismatch_handler', 'GET', 1, 'value', 'String', 1),
            $this->row(4, 'outside', '../' . basename($outside), 'outside_handler', 'GET', 1)
        );

        try {
            $document = (new BerliCRM_OpenApiGenerator(new OpenApiGeneratorFakeDb($rows), $this->root))->generate();
        } finally {
            unlink($outside);
        }

        $this->assertCount(4, $document['paths']);
        $this->assertCount(4, $document['x-berlicrm-documentation-warnings']);
        foreach ($document['paths'] as $path) {
            $this->assertSame('incomplete', $path['get']['x-berlicrm-documentation-status']);
        }
        $serialized = json_encode($document);
        $this->assertStringNotContainsString('handler_path', $serialized);
        $this->assertStringNotContainsString('handler_method', $serialized);
        $this->assertStringNotContainsString($outside, $serialized);
    }

    public function testOperationIdsRemainUniqueAfterSanitizing(): void
    {
        $metadata = '/** @berli-openapi-begin {"summary":"Test","description":"Test.","tags":["Test"],"parameters":{},"result":{"schema":{},"example":{}}} @berli-openapi-end */';
        file_put_contents($this->root . '/ids.php', "<?php $metadata function first_handler() {} $metadata function second_handler() {}");
        $rows = array(
            $this->row(1, 'a-b', 'ids.php', 'first_handler', 'GET', 1),
            $this->row(2, 'a_b', 'ids.php', 'second_handler', 'GET', 1)
        );
        $document = (new BerliCRM_OpenApiGenerator(new OpenApiGeneratorFakeDb($rows), $this->root))->generate();
        $ids = array();
        foreach ($document['paths'] as $path) {
            $ids[] = $path['get']['operationId'];
        }
        $this->assertCount(2, array_unique($ids));
    }

    public function testDescribeApiDocumentsItsRawSuccessResponse(): void
    {
        $metadata = '/** @berli-openapi-begin {"summary":"Describe","description":"Describe.","tags":["Documentation"],"parameters":{},"result":{"schema":{"type":"object","required":["openapi"]},"example":{"openapi":"3.0.3"}}} @berli-openapi-end */';
        file_put_contents($this->root . '/describe.php', "<?php $metadata function describe_handler() {}");
        $rows = array($this->row(1, 'describeapi', 'describe.php', 'describe_handler', 'GET', 1));
        $document = (new BerliCRM_OpenApiGenerator(new OpenApiGeneratorFakeDb($rows), $this->root))->generate();
        $success = $document['paths']['/webservice.php/operations/describeapi']['get']['responses']['200']['content']['application/json'];

        $this->assertSame(array('openapi'), $success['schema']['required']);
        $this->assertArrayNotHasKey('success', $success['example']);
    }

    private function row($id, $name, $path, $method, $type, $prelogin, $parameterName = null, $parameterType = null, $sequence = null): array
    {
        return array(
            'operationid' => $id,
            'name' => $name,
            'handler_path' => $path,
            'handler_method' => $method,
            'type' => $type,
            'prelogin' => $prelogin,
            'parameter_name' => $parameterName,
            'parameter_type' => $parameterType,
            'parameter_sequence' => $sequence
        );
    }
}

class OpenApiGeneratorFakeDb
{
    private $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function pquery($sql, $params)
    {
        return new OpenApiGeneratorFakeRecordSet($this->rows);
    }
}

class OpenApiGeneratorFakeRecordSet
{
    private $rows;
    private $index = 0;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function FetchRow()
    {
        if (!isset($this->rows[$this->index])) {
            return false;
        }
        return $this->rows[$this->index++];
    }
}

