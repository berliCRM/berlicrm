# berliCRM REST OpenAPI documentation

This document explains how the public OpenAPI endpoint is generated and how a
developer must document a REST webservice so that it appears correctly in the
generated specification.

## Public endpoint

The specification is available without authentication:

```text
GET /webservice.php?operation=describeapi
```

For a local installation:

```bash
curl -k 'https://localhost/webservice.php?operation=describeapi'
```

The response is a raw OpenAPI 3.0.3 document. Unlike normal berliCRM REST
responses, it is not wrapped in `{"success":true,"result":...}`. This is
intentional so that OpenAPI tools can consume the URL directly.

The paths shown in the specification, for example
`/webservice.php/operations/query`, are documentation-only paths. All actual
requests still go to `/webservice.php`. Each documented operation contains an
`x-berlicrm-real-request` extension with the real path, HTTP method and
`operation` parameter.

## How the document is generated

`include/Webservices/OpenApiGenerator.php` builds the document on every
request. There is no persistent cache.

The generator combines two sources:

1. The webservice registry in the database supplies the public operation
   name, HTTP method, authentication status, handler and ordered input
   parameters.
2. A structured JSON block in the PHPDoc of the registered handler function
   supplies descriptions, required/optional flags, examples and response
   schemas.

The relevant database columns are:

| Table | Column | Meaning |
| --- | --- | --- |
| `vtiger_ws_operation` | `name` | Public value of the `operation` request parameter. |
| `vtiger_ws_operation` | `type` | `GET` or `POST`. |
| `vtiger_ws_operation` | `prelogin` | `1` for a public/pre-login operation, otherwise `0`. |
| `vtiger_ws_operation` | `handler_path` | PHP file inspected by the generator. It is never published. |
| `vtiger_ws_operation` | `handler_method` | Function whose PHPDoc is inspected. It is never published. |
| `vtiger_ws_operation_parameters` | `name` | Public request parameter name. |
| `vtiger_ws_operation_parameters` | `type` | Coarse transport type. |
| `vtiger_ws_operation_parameters` | `sequence` | Argument order used when calling the handler. |

Handler files are parsed with `token_get_all`; they are not included or
executed by the documentation generator. The resolved handler file must be a
regular file below the CRM DocRoot. Symlinks or paths resolving outside the
DocRoot are rejected.

## Request and response conventions

The generated contract follows the behavior of `webservice.php`:

- GET operations receive `operation` and their registered parameters in the
  query string.
- POST operations use `application/x-www-form-urlencoded`. `operation` and
  the registered parameters are form fields.
- Protected operations require `sessionName`. It can be supplied in the query
  string, and POST operations also accept it as a form field.
- The dispatcher appends the authenticated `Users` object when invoking a
  protected handler. This internal argument must not be registered or added to
  the documentation metadata.
- Normal success responses use
  `{"success":true,"result":<handler result>}`.
- Normal error responses use
  `{"success":false,"error":{"code":"...","message":"..."}}`.
- Common responses for HTTP 400, 401, 403, 404, 405 and 500 are generated
  automatically. They do not belong in the handler metadata.
- `describeapi` is the only operation whose successful result is emitted
  without the normal success wrapper.

Entity fields can vary by module, installation and user permissions. Generic
entity results should therefore reference `#/components/schemas/Entity` and
tell clients to call the existing `describe` operation for the effective field
contract.

## Required handler PHPDoc

The structured metadata must be placed in a normal PHPDoc comment (`/** ... */`)
associated with the exact function named by `handler_method`. Put it directly
above the function. A normal block comment (`/* ... */`) is not sufficient.

The JSON must be enclosed by these markers:

```php
/**
 * Human-readable developer notes may appear here.
 *
 * @berli-openapi-begin
 * {
 *   "summary": "Short operation title",
 *   "description": "A complete explanation of the operation.",
 *   "tags": ["Records"],
 *   "parameters": {},
 *   "result": {
 *     "schema": {"type": "object"},
 *     "example": {}
 *   }
 * }
 * @berli-openapi-end
 */
function example_handler()
{
    // ...
}
```

The content between the markers must be valid JSON, not a PHP array and not a
JavaScript object literal.

### Top-level metadata fields

Every metadata block requires all of the following fields:

| Field | Required value |
| --- | --- |
| `summary` | Non-empty string suitable for an OpenAPI operation title. |
| `description` | String explaining behavior, important constraints and side effects. |
| `tags` | JSON array of logical OpenAPI group names. |
| `parameters` | JSON object keyed by every registered public parameter name. Use `{}` when there are none. |
| `result.schema` | OpenAPI 3.0 Schema Object for the value returned by the handler. Do not include the berliCRM success wrapper. |
| `result.example` | Concrete JSON example that conforms to `result.schema`. |

Do not use OpenAPI 3.1-only schema keywords such as `prefixItems`; the emitted
document is OpenAPI 3.0.3.

### Parameter metadata

Every parameter registered in `vtiger_ws_operation_parameters` must have one
entry in `parameters`, and no unregistered names may be added. The names are
case-sensitive and describe the external REST contract, even if a handler uses
a different local PHP variable name.

Each entry requires:

```json
{
  "required": true,
  "description": "What the caller must send.",
  "example": "example value"
}
```

Optional fields are:

| Field | Purpose |
| --- | --- |
| `schema` | Overrides the schema inferred from the database parameter type. Use this for enums, patterns, numeric timestamps or other constraints. |
| `default` | Documents the effective default used by the handler. |
| `decodedSchema` | For `Encoded` parameters only: describes the JSON value after decoding. |

`required` must reflect actual handler behavior. Do not mark a parameter
optional merely because missing request values are passed to PHP as an empty
value. Confirm that the handler supplies a meaningful default or accepts an
empty value.

### Database type mapping

Database types are matched case-insensitively:

| Database type | Default OpenAPI schema |
| --- | --- |
| `String` | `{"type":"string"}` |
| `DateTime` | `{"type":"string","format":"date-time"}` |
| `Double` | `{"type":"number","format":"double"}` |
| `Boolean` | `{"type":"boolean"}` |
| `Encoded` | A transport string containing JSON, plus `x-berlicrm-decoded-schema`. |

Use `schema` when the default is not accurate. For example, an operation whose
database type is `DateTime` but whose handler expects a Unix timestamp should
declare `{"type":"integer"}`.

An `Encoded` parameter is not a native JSON request body. It remains one query
or form string which the dispatcher decodes. Its example must therefore be a
JSON string, while `decodedSchema` describes the decoded data:

```json
"element": {
  "required": true,
  "description": "JSON object containing CRM field values.",
  "example": "{\"lastname\":\"Doe\",\"assigned_user_id\":\"19x1\"}",
  "decodedSchema": {
    "type": "object",
    "additionalProperties": true
  }
}
```

## Complete annotated handler example

The following example documents an authenticated POST operation with a
required record ID and an optional enum:

```php
/**
 * Generate an export and store it as a CRM Document.
 *
 * @berli-openapi-begin
 * {
 *   "summary": "Create an invoice export document",
 *   "description": "Generates an export and stores it as a Document linked to the Invoice.",
 *   "tags": ["Inventory"],
 *   "parameters": {
 *     "id": {
 *       "required": true,
 *       "description": "Invoice webservice ID or numeric CRM ID.",
 *       "example": "7x144420"
 *     },
 *     "output_format": {
 *       "required": false,
 *       "description": "Export format.",
 *       "example": "pdf",
 *       "default": "pdf",
 *       "schema": {
 *         "type": "string",
 *         "enum": ["pdf", "zugferd", "xrechnung"]
 *       }
 *     }
 *   },
 *   "result": {
 *     "schema": {
 *       "type": "object",
 *       "required": ["invoice_id", "document_id", "filename"],
 *       "properties": {
 *         "invoice_id": {"type": "string"},
 *         "document_id": {"type": "string"},
 *         "filename": {"type": "string"}
 *       }
 *     },
 *     "example": {
 *       "invoice_id": "7x144420",
 *       "document_id": "15x900",
 *       "filename": "INV-100.pdf"
 *     }
 *   }
 * }
 * @berli-openapi-end
 */
function berli_createInvoiceExport($id, $outputFormat, $user)
{
    // ...
}
```

Register it with the same public parameter names and the same argument order:

```php
$operationId = vtws_addWebserviceOperation(
    'createinvoiceexport',
    'include/Webservices/Custom/CreateInvoiceExport.php',
    'berli_createInvoiceExport',
    'POST',
    0 // Authentication required.
);

vtws_addWebserviceOperationParam($operationId, 'id', 'String', 1);
vtws_addWebserviceOperationParam($operationId, 'output_format', 'String', 2);
```

The dispatcher will call the handler with the two registered values in
`sequence` order and append `$user` as the final argument.

## Adding or changing an operation

Use this checklist whenever a REST operation is introduced or changed:

1. Implement the handler and decide its real request method, public parameter
   names, argument order, defaults and result structure.
2. Add a structured PHPDoc block directly above the registered handler
   function.
3. Register the operation in `vtiger_ws_operation` through
   `vtws_addWebserviceOperation`. Use `prelogin = 1` only when the operation is
   intentionally public.
4. Register every public handler argument, except the dispatcher-supplied
   `$user`, with `vtws_addWebserviceOperationParam` in exact call order.
5. Add the same idempotent registration or repair logic to the installation and
   update path. Re-running it must not create duplicate operations or stale
   parameter rows.
6. If an existing parameter is renamed, added, removed, reordered or changes
   type, update both the database registration and PHPDoc metadata in the same
   change.
7. Call `describeapi` and confirm that the operation has
   `x-berlicrm-documentation-status: complete` and that no top-level warning
   names the operation.
8. Validate that request and response examples conform to the generated
   schemas.

The database registry is authoritative for whether an operation exists. A
documented PHP function which is not registered will not appear. Conversely, a
registered function with missing or invalid metadata remains visible, but is
marked incomplete.

## Incomplete metadata and warnings

Documentation problems do not make the entire endpoint unavailable. The
affected operation receives a generic fallback with:

```json
"x-berlicrm-documentation-status": "incomplete"
```

The document also receives:

```json
"x-berlicrm-documentation-warnings": [
  {
    "operation": "example",
    "message": "Metadata for parameter 'id' is missing or incomplete."
  }
]
```

Warnings are generated for conditions including:

- missing or unreadable handler files;
- handler paths outside the CRM DocRoot;
- a missing registered handler function;
- missing PHPDoc markers;
- invalid JSON;
- missing required metadata fields;
- missing parameter metadata; and
- metadata for parameters which are not registered.

Do not accept an incomplete status as finished documentation. The fallback is
only intended to keep the complete API catalog available while highlighting
the defect.

## Validation commands

Run the focused unit tests:

```bash
vendor/bin/phpunit --colors=never tests/Webservices/OpenApiGeneratorTest.php
```

Check the live endpoint and list documentation warnings:

```bash
curl -ks 'https://localhost/webservice.php?operation=describeapi' \
  | jq '."x-berlicrm-documentation-warnings" // []'
```

Confirm that a particular operation is complete:

```bash
curl -ks 'https://localhost/webservice.php?operation=describeapi' \
  | jq '.paths["/webservice.php/operations/create"].post
        | {operationId, status:."x-berlicrm-documentation-status"}'
```

Validate the complete OpenAPI document with Redocly:

```bash
curl -ks -o /tmp/berlicrm-openapi.json \
  'https://localhost/webservice.php?operation=describeapi'
npx --yes @redocly/cli lint /tmp/berlicrm-openapi.json
```

Also run `php -l` for every changed handler file. A successful implementation
has no OpenAPI validation errors, no invalid examples and no documentation
warnings for registered operations.

## Main implementation files

- `include/Webservices/OpenApiGenerator.php` reads, validates and converts the
  registry and PHPDoc metadata.
- `include/Webservices/DescribeApi.php` implements the public `describeapi`
  handler and documents the endpoint itself.
- `webservice.php` emits the successful `describeapi` result without the normal
  response wrapper.
- `modules/Install/models/InitSchema.php` registers or repairs `describeapi`
  during installation and updates.
- `tests/Webservices/OpenApiGeneratorTest.php` covers parsing, type conversion,
  safe fallbacks, path protection, unique operation IDs and the raw
  `describeapi` response schema.
