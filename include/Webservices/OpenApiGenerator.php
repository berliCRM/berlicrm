<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License.
 *************************************************************************************/

/**
 * Build an OpenAPI document from the registered webservice operations and the
 * structured metadata attached to their handler functions.
 */
class BerliCRM_OpenApiGenerator
{
    private $db;
    private $rootPath;
    private $warnings = array();

    public function __construct($db, $rootPath)
    {
        $this->db = $db;
        $this->rootPath = rtrim((string)realpath($rootPath), DIRECTORY_SEPARATOR);
    }

    public function generate()
    {
        $operations = $this->loadOperations();
        $paths = array();
        $operationIds = array();

        foreach ($operations as $operation) {
            $metadata = $this->readMetadata($operation);
            $complete = $this->validateMetadata($operation, $metadata);
            if (!$complete) {
                $metadata = $this->fallbackMetadata($operation, $metadata);
            }

            $operationId = $this->operationId($operation['name']);
            if (isset($operationIds[$operationId])) {
                $operationId .= '_' . (int)$operation['operationid'];
            }
            $operationIds[$operationId] = true;

            $path = '/webservice.php/operations/' . rawurlencode($operation['name']);
            $method = strtolower($operation['type']);
            $paths[$path] = array($method => $this->buildOperation($operation, $metadata, $operationId, $complete));
        }

        $document = array(
            'openapi' => '3.0.3',
            'info' => array(
                'title' => 'berliCRM REST Webservices',
                'version' => isset($GLOBALS['API_VERSION']) ? (string)$GLOBALS['API_VERSION'] : '0.22',
                'description' => 'Public contract for the REST operations exposed through webservice.php. Paths are documentation-only; use x-berlicrm-real-request for the real request target.',
                'license' => array(
                    'name' => 'Vtiger Public License 1.2',
                    'url' => 'https://github.com/berliCRM/berlicrm/blob/master/LICENSE.txt'
                )
            ),
            'servers' => array(array('url' => '/')),
            'paths' => $paths,
            'components' => $this->components()
        );
        if (!empty($this->warnings)) {
            $document['x-berlicrm-documentation-warnings'] = $this->warnings;
        }
        return $document;
    }

    private function loadOperations()
    {
        $sql = 'SELECT o.operationid, o.name, o.handler_path, o.handler_method, o.type, o.prelogin, '
            . 'p.name AS parameter_name, p.type AS parameter_type, p.sequence AS parameter_sequence '
            . 'FROM vtiger_ws_operation o '
            . 'LEFT JOIN vtiger_ws_operation_parameters p ON p.operationid = o.operationid '
            . 'ORDER BY LOWER(o.name), o.operationid, p.sequence';
        $result = $this->db->pquery($sql, array());
        if ($result === false) {
            throw new RuntimeException('Unable to read the webservice registry.');
        }

        $operations = array();
        while (($row = $result->FetchRow()) !== false) {
            $row = array_change_key_case($row, CASE_LOWER);
            $id = (string)$row['operationid'];
            if (!isset($operations[$id])) {
                $operations[$id] = array(
                    'operationid' => (int)$row['operationid'],
                    'name' => $row['name'],
                    'handler_path' => $row['handler_path'],
                    'handler_method' => $row['handler_method'],
                    'type' => strtoupper($row['type']),
                    'prelogin' => (int)$row['prelogin'],
                    'parameters' => array()
                );
            }
            if ($row['parameter_name'] !== null && $row['parameter_name'] !== '') {
                $operations[$id]['parameters'][] = array(
                    'name' => $row['parameter_name'],
                    'type' => $row['parameter_type'],
                    'sequence' => (int)$row['parameter_sequence']
                );
            }
        }
        return array_values($operations);
    }

    private function readMetadata($operation)
    {
        $relativePath = ltrim(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $operation['handler_path']), DIRECTORY_SEPARATOR);
        $filePath = realpath($this->rootPath . DIRECTORY_SEPARATOR . $relativePath);
        if ($filePath === false || !is_file($filePath) || !$this->isWithinRoot($filePath)) {
            $this->warn($operation['name'], 'Handler file is unavailable or outside the CRM directory.');
            return null;
        }

        $source = file_get_contents($filePath);
        if ($source === false) {
            $this->warn($operation['name'], 'Handler file could not be read.');
            return null;
        }
        $docComment = $this->findFunctionDocComment($source, $operation['handler_method']);
        if ($docComment === null) {
            $this->warn($operation['name'], 'Structured handler PHPDoc is missing.');
            return null;
        }
        if (!preg_match('/@berli-openapi-begin\s*(.*?)\s*@berli-openapi-end/s', $docComment, $matches)) {
            $this->warn($operation['name'], 'Structured handler PHPDoc markers are missing.');
            return null;
        }

        $json = preg_replace('/^\s*\*\s?/m', '', $matches[1]);
        $metadata = json_decode(trim($json), true);
        if (!is_array($metadata)) {
            $this->warn($operation['name'], 'Structured handler PHPDoc contains invalid JSON.');
            return null;
        }
        return $metadata;
    }

    private function findFunctionDocComment($source, $functionName)
    {
        $tokens = token_get_all($source);
        $lastDocComment = null;
        $count = count($tokens);
        for ($index = 0; $index < $count; ++$index) {
            $token = $tokens[$index];
            if (is_array($token) && $token[0] === T_DOC_COMMENT) {
                $lastDocComment = $token[1];
                continue;
            }
            if (!is_array($token) || $token[0] !== T_FUNCTION) {
                continue;
            }
            for (++$index; $index < $count; ++$index) {
                $candidate = $tokens[$index];
                if (is_array($candidate) && $candidate[0] === T_STRING) {
                    if (strcasecmp($candidate[1], $functionName) === 0) {
                        return $lastDocComment;
                    }
                    $lastDocComment = null;
                    break;
                }
            }
        }
        return null;
    }

    private function validateMetadata($operation, $metadata)
    {
        if (!is_array($metadata)) {
            return false;
        }
        foreach (array('summary', 'description', 'tags', 'parameters', 'result') as $key) {
            if (!array_key_exists($key, $metadata)) {
                $this->warn($operation['name'], "Metadata key '$key' is missing.");
                return false;
            }
        }
        if (!is_string($metadata['summary']) || $metadata['summary'] === '' || !is_string($metadata['description'])
            || !is_array($metadata['tags']) || !is_array($metadata['parameters']) || !is_array($metadata['result'])
            || !isset($metadata['result']['schema']) || !array_key_exists('example', $metadata['result'])) {
            $this->warn($operation['name'], 'Structured handler metadata has an invalid shape.');
            return false;
        }

        $registered = array();
        foreach ($operation['parameters'] as $parameter) {
            $registered[$parameter['name']] = true;
            if (!isset($metadata['parameters'][$parameter['name']])
                || !is_array($metadata['parameters'][$parameter['name']])
                || !array_key_exists('required', $metadata['parameters'][$parameter['name']])
                || !isset($metadata['parameters'][$parameter['name']]['description'])
                || !array_key_exists('example', $metadata['parameters'][$parameter['name']])) {
                $this->warn($operation['name'], "Metadata for parameter '{$parameter['name']}' is missing or incomplete.");
                return false;
            }
        }
        foreach ($metadata['parameters'] as $name => $details) {
            if (!isset($registered[$name])) {
                $this->warn($operation['name'], "Metadata contains unregistered parameter '$name'.");
                return false;
            }
        }
        return true;
    }

    private function fallbackMetadata($operation, $metadata)
    {
        $parameters = array();
        foreach ($operation['parameters'] as $parameter) {
            $parameters[$parameter['name']] = array(
                'required' => false,
                'description' => 'No complete handler documentation is available for this parameter.',
                'example' => null
            );
        }
        return array(
            'summary' => is_array($metadata) && !empty($metadata['summary']) ? $metadata['summary'] : 'Call ' . $operation['name'],
            'description' => 'The operation is registered, but its structured handler documentation is incomplete.',
            'tags' => array('Undocumented'),
            'parameters' => $parameters,
            'result' => array('schema' => array(), 'example' => new stdClass())
        );
    }

    private function buildOperation($operation, $metadata, $operationId, $complete)
    {
        $method = strtoupper($operation['type']);
        $documented = array(
            'tags' => array_values($metadata['tags']),
            'summary' => $metadata['summary'],
            'description' => $metadata['description'],
            'operationId' => $operationId,
            'x-berlicrm-documentation-status' => $complete ? 'complete' : 'incomplete',
            'x-berlicrm-real-request' => array(
                'path' => '/webservice.php',
                'method' => $method,
                'operationParameter' => $operation['name']
            ),
            'responses' => $this->responses($metadata['result'], strcasecmp($operation['name'], 'describeapi') === 0)
        );

        if (!$operation['prelogin']) {
            $documented['security'] = array(array('SessionName' => array()));
            $documented['x-berlicrm-authentication'] = array(
                'parameter' => 'sessionName',
                'acceptedIn' => $method === 'GET' ? 'query' : 'query or form body'
            );
        } else {
            $documented['security'] = array();
        }

        if ($method === 'GET') {
            $documented['parameters'] = array($this->operationParameter($operation['name']));
            foreach ($operation['parameters'] as $parameter) {
                $documented['parameters'][] = $this->queryParameter($parameter, $metadata['parameters'][$parameter['name']]);
            }
        } else {
            $documented['requestBody'] = $this->formRequestBody($operation, $metadata);
        }
        return $documented;
    }

    private function operationParameter($name)
    {
        return array(
            'name' => 'operation',
            'in' => 'query',
            'required' => true,
            'description' => 'Registered webservice operation.',
            'schema' => array('type' => 'string', 'enum' => array($name)),
            'example' => $name
        );
    }

    private function queryParameter($parameter, $metadata)
    {
        $item = array(
            'name' => $parameter['name'],
            'in' => 'query',
            'required' => (bool)$metadata['required'],
            'description' => $metadata['description'],
            'schema' => $this->parameterSchema($parameter['type'], $metadata),
            'example' => $metadata['example']
        );
        return $item;
    }

    private function formRequestBody($operation, $metadata)
    {
        $properties = array(
            'operation' => array('type' => 'string', 'enum' => array($operation['name']), 'example' => $operation['name'])
        );
        $required = array('operation');
        foreach ($operation['parameters'] as $parameter) {
            $details = $metadata['parameters'][$parameter['name']];
            $schema = $this->parameterSchema($parameter['type'], $details);
            $schema['description'] = $details['description'];
            $schema['example'] = $details['example'];
            $properties[$parameter['name']] = $schema;
            if ($details['required']) {
                $required[] = $parameter['name'];
            }
        }
        return array(
            'required' => true,
            'content' => array(
                'application/x-www-form-urlencoded' => array(
                    'schema' => array('type' => 'object', 'required' => $required, 'properties' => $properties)
                )
            )
        );
    }

    private function parameterSchema($type, $metadata)
    {
        if (isset($metadata['schema']) && is_array($metadata['schema'])) {
            $schema = $metadata['schema'];
        } else {
            switch (strtolower((string)$type)) {
                case 'datetime':
                    $schema = array('type' => 'string', 'format' => 'date-time');
                    break;
                case 'double':
                    $schema = array('type' => 'number', 'format' => 'double');
                    break;
                case 'boolean':
                    $schema = array('type' => 'boolean');
                    break;
                default:
                    $schema = array('type' => 'string');
            }
        }
        if (strtolower((string)$type) === 'encoded') {
            $schema['type'] = 'string';
            $schema['description'] = 'JSON-encoded value.';
            $schema['x-berlicrm-decoded-schema'] = isset($metadata['decodedSchema']) && is_array($metadata['decodedSchema'])
                ? $metadata['decodedSchema'] : array('type' => 'object', 'additionalProperties' => true);
        }
        if (array_key_exists('default', $metadata)) {
            $schema['default'] = $metadata['default'];
        }
        return $schema;
    }

    private function responses($result, $rawSuccess = false)
    {
        if ($rawSuccess) {
            $successSchema = $result['schema'];
            $successExample = $this->normalizeExample($result['example'], $successSchema);
        } else {
            $successSchema = array(
                'type' => 'object',
                'required' => array('success', 'result'),
                'properties' => array(
                    'success' => array('type' => 'boolean', 'enum' => array(true)),
                    'result' => $result['schema']
                )
            );
            $successExample = array('success' => true, 'result' => $this->normalizeExample($result['example'], $result['schema']));
        }
        $responses = array(
            '200' => array(
                'description' => 'Successful response.',
                'content' => array('application/json' => array('schema' => $successSchema, 'example' => $successExample))
            )
        );
        foreach (array('400', '401', '403', '404', '405', '500') as $status) {
            $responses[$status] = array('$ref' => '#/components/responses/Error' . $status);
        }
        return $responses;
    }

    private function components()
    {
        $errorSchema = array(
            'type' => 'object',
            'required' => array('success', 'error'),
            'properties' => array(
                'success' => array('type' => 'boolean', 'enum' => array(false)),
                'error' => array(
                    'type' => 'object',
                    'required' => array('code', 'message'),
                    'properties' => array(
                        'code' => array('type' => 'string'),
                        'message' => array('type' => 'string')
                    )
                )
            )
        );
        $responses = array();
        $labels = array('400' => 'Bad request.', '401' => 'Authentication required or failed.', '403' => 'Access denied.', '404' => 'Record not found.', '405' => 'HTTP method not allowed.', '500' => 'Internal server error.');
        foreach ($labels as $status => $description) {
            $responses['Error' . $status] = array(
                'description' => $description,
                'content' => array('application/json' => array('schema' => array('$ref' => '#/components/schemas/ErrorResponse')))
            );
        }
        return array(
            'securitySchemes' => array(
                'SessionName' => array(
                    'type' => 'apiKey',
                    'in' => 'query',
                    'name' => 'sessionName',
                    'description' => 'Session identifier returned by login. POST operations also accept it as a form field.'
                )
            ),
            'schemas' => array(
                'Entity' => array(
                    'type' => 'object',
                    'required' => array('id'),
                    'properties' => array('id' => array('type' => 'string', 'pattern' => '^[0-9]+x[0-9]+$')),
                    'additionalProperties' => true,
                    'description' => 'CRM entity. Available fields depend on the module and authenticated user; call describe for field metadata.'
                ),
                'ErrorResponse' => $errorSchema
            ),
            'responses' => $responses
        );
    }

    private function operationId($name)
    {
        return 'berlicrm_' . trim(preg_replace('/[^A-Za-z0-9_]+/', '_', strtolower($name)), '_');
    }

    private function normalizeExample($example, $schema)
    {
        if (!is_array($schema)) {
            return $example;
        }
        if (isset($schema['type']) && $schema['type'] === 'object' && is_array($example)) {
            if (empty($example)) {
                return new stdClass();
            }
            foreach ($example as $name => $value) {
                if (isset($schema['properties'][$name])) {
                    $example[$name] = $this->normalizeExample($value, $schema['properties'][$name]);
                } elseif (isset($schema['additionalProperties']) && is_array($schema['additionalProperties'])) {
                    $example[$name] = $this->normalizeExample($value, $schema['additionalProperties']);
                }
            }
        } elseif (isset($schema['type']) && $schema['type'] === 'array' && is_array($example) && isset($schema['items'])) {
            foreach ($example as $index => $value) {
                $example[$index] = $this->normalizeExample($value, $schema['items']);
            }
        }
        return $example;
    }

    private function isWithinRoot($path)
    {
        return $this->rootPath !== '' && strpos($path, $this->rootPath . DIRECTORY_SEPARATOR) === 0;
    }

    private function warn($operation, $message)
    {
        $this->warnings[] = array('operation' => $operation, 'message' => $message);
    }
}

