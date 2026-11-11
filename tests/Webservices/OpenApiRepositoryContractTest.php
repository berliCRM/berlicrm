<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/include/Webservices/OpenApiGenerator.php';

/**
 * Guards the repository-wide REST contract. The fixture mirrors the default
 * registry, while optional add-in operations are kept separate so the
 * generator can be tested both before and after their registration.
 */
class OpenApiRepositoryContractTest extends TestCase
{
    private $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function testDefaultRegistryContainsOnlyCompleteRestOperations(): void
    {
        $definitions = $this->defaultDefinitions();
        $this->assertCount(45, $definitions);

        $document = $this->generate($definitions);

        $this->assertCount(45, $document['paths']);
        $this->assertArrayNotHasKey('x-berlicrm-documentation-warnings', $document);
        foreach ($document['paths'] as $path) {
            $operation = reset($path);
            $this->assertSame('complete', $operation['x-berlicrm-documentation-status']);
        }
        foreach ($this->optionalDefinitions() as $definition) {
            $this->assertArrayNotHasKey('/webservice.php/operations/' . rawurlencode($definition[0]), $document['paths']);
        }
    }

    public function testRegisteredAddInOperationsAppearComplete(): void
    {
        $definitions = array_merge($this->defaultDefinitions(), $this->optionalDefinitions());
        $this->assertCount(53, $definitions);

        $document = $this->generate($definitions);

        $this->assertCount(53, $document['paths']);
        $this->assertArrayNotHasKey('x-berlicrm-documentation-warnings', $document);
        foreach ($this->optionalDefinitions() as $definition) {
            $path = '/webservice.php/operations/' . rawurlencode($definition[0]);
            $this->assertArrayHasKey($path, $document['paths']);
            $operation = reset($document['paths'][$path]);
            $this->assertSame('complete', $operation['x-berlicrm-documentation-status']);
        }
    }

    public function testRegistryArgumentsCanInvokeEveryHandler(): void
    {
        foreach (array_merge($this->defaultDefinitions(), $this->optionalDefinitions()) as $definition) {
            list($name, $path, $method, , $prelogin, $parameters) = $definition;
            $signature = $this->functionSignature($this->root . '/' . $path, $method);
            $argumentCount = count($parameters) + ($prelogin ? 0 : 1);
            $this->assertLessThanOrEqual($argumentCount, $signature['required'], "$name requires too many handler arguments.");
        }
    }

    private function generate(array $definitions): array
    {
        $rows = array();
        foreach ($definitions as $index => $definition) {
            list($name, $path, $method, $type, $prelogin, $parameters) = $definition;
            if (empty($parameters)) {
                $rows[] = $this->row($index + 1, $name, $path, $method, $type, $prelogin);
                continue;
            }
            foreach ($parameters as $sequence => $parameter) {
                $rows[] = $this->row($index + 1, $name, $path, $method, $type, $prelogin, $parameter[0], $parameter[1], $sequence + 1);
            }
        }
        return (new BerliCRM_OpenApiGenerator(new OpenApiRepositoryFakeDb($rows), $this->root))->generate();
    }

    private function row($id, $name, $path, $method, $type, $prelogin, $parameterName = null, $parameterType = null, $sequence = null): array
    {
        return array('operationid' => $id, 'name' => $name, 'handler_path' => $path, 'handler_method' => $method,
            'type' => $type, 'prelogin' => $prelogin, 'parameter_name' => $parameterName,
            'parameter_type' => $parameterType, 'parameter_sequence' => $sequence);
    }

    private function functionSignature($path, $functionName): array
    {
        $this->assertFileExists($path);
        $tokens = token_get_all(file_get_contents($path));
        $count = count($tokens);
        for ($i = 0; $i < $count; ++$i) {
            if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
            do { ++$i; } while ($i < $count && (!is_array($tokens[$i]) || $tokens[$i][0] !== T_STRING));
            if ($i >= $count || strcasecmp($tokens[$i][1], $functionName) !== 0) continue;
            do { ++$i; } while ($i < $count && $tokens[$i] !== '(');
            $depth = 1; $total = 0; $required = 0; $hasDefault = false; $hasVariable = false; $variadic = false;
            for (++$i; $i < $count && $depth > 0; ++$i) {
                $token = $tokens[$i];
                if ($token === '(' || $token === '[') { ++$depth; continue; }
                if ($token === ')' || $token === ']') {
                    --$depth;
                    if ($depth === 0 && $hasVariable) { ++$total; if (!$hasDefault) ++$required; }
                    continue;
                }
                if ($depth !== 1) continue;
                if (is_array($token) && $token[0] === T_VARIABLE) $hasVariable = true;
                if (is_array($token) && defined('T_ELLIPSIS') && $token[0] === T_ELLIPSIS) $variadic = true;
                if ($token === '=') $hasDefault = true;
                if ($token === ',' && $hasVariable) {
                    ++$total; if (!$hasDefault) ++$required;
                    $hasDefault = false; $hasVariable = false;
                }
            }
            return array('total' => $total, 'required' => $required, 'variadic' => $variadic);
        }
        $this->fail("Handler function $functionName was not found in $path.");
    }

    private function p($name, $type = 'String'): array { return array($name, $type); }

    private function defaultDefinitions(): array
    {
        return array(
            array('bulkUpdate', 'include/Webservices/Custom/BulkUpdate.php', 'berli_bulkUpdate', 'POST', 0, array($this->p('elements', 'encoded'), $this->p('skipHandlers', 'Boolean'))),
            array('changePassword', 'include/Webservices/Custom/ChangePassword.php', 'vtws_changePassword', 'POST', 0, array($this->p('id'), $this->p('oldPassword'), $this->p('newPassword'), $this->p('confirmPassword'))),
            array('convertlead', 'include/Webservices/ConvertLead.php', 'vtws_convertlead', 'POST', 0, array($this->p('element', 'encoded'))),
            array('create', 'include/Webservices/Create.php', 'vtws_create', 'POST', 0, array($this->p('elementType'), $this->p('element', 'encoded'))),
            array('createinvoicepdf', 'include/Webservices/Custom/CreateInvoicePdf.php', 'berli_createInvoicePdf', 'POST', 0, array($this->p('id'), $this->p('output_format'))),
            array('delete', 'include/Webservices/Delete.php', 'vtws_delete', 'POST', 0, array($this->p('id'))),
            array('deleteUser', 'include/Webservices/DeleteUser.php', 'vtws_deleteUser', 'POST', 0, array($this->p('id'), $this->p('newOwnerId'))),
            array('describe', 'include/Webservices/DescribeObject.php', 'vtws_describe', 'GET', 0, array($this->p('elementType'))),
            array('describeapi', 'include/Webservices/DescribeApi.php', 'vtws_describeapi', 'GET', 1, array()),
            array('extendsession', 'include/Webservices/ExtendSession.php', 'vtws_extendSession', 'POST', 1, array()),
            array('get_campaign_entities', 'modules/CWC/getCampaignEntities.php', 'vtws_get_campaign_entities', 'GET', 0, array($this->p('campaignid'), $this->p('returnresults'))),
            array('get_deleted_entities', 'include/Webservices/Custom/GetDeletedEntities.php', 'berli_getDeletedEntities', 'GET', 0, array($this->p('modules', 'encoded'), $this->p('searchDate'))),
            array('get_document_relations', 'modules/CWC/getDocRel.php', 'vtws_get_document_relations', 'GET', 0, array($this->p('docids'))),
            array('get_multi_relations', 'include/Webservices/Custom/getMultiRelations.php', 'berli_get_multi_relations', 'GET', 0, array($this->p('id'))),
            array('get_new_multi_relations', 'include/Webservices/Custom/getNewMultiRelations.php', 'berli_get_new_multi_relations', 'GET', 0, array($this->p('id'), $this->p('relModule'))),
            array('getchallenge', 'include/Webservices/AuthToken.php', 'vtws_getchallenge', 'GET', 1, array($this->p('username'))),
            array('listtypes', 'include/Webservices/ModuleTypes.php', 'vtws_listtypes', 'GET', 0, array($this->p('fieldTypeList', 'encoded'))),
            array('login', 'include/Webservices/Login.php', 'vtws_login', 'POST', 1, array($this->p('username'), $this->p('accessKey'))),
            array('logout', 'include/Webservices/Logout.php', 'vtws_logout', 'POST', 0, array($this->p('sessionName'))),
            array('mobile.alertdetailswithmessage', 'modules/Mobile/api/wsapi.php', 'mobile_ws_alertDetailsWithMessage', 'POST', 0, array($this->p('alertid'))),
            array('mobile.fetchallalerts', 'modules/Mobile/api/wsapi.php', 'mobile_ws_fetchAllAlerts', 'POST', 0, array()),
            array('mobile.fetchmodulefilters', 'modules/Mobile/api/wsapi.php', 'mobile_ws_fetchModuleFilters', 'POST', 0, array($this->p('module'))),
            array('mobile.fetchrecord', 'modules/Mobile/api/wsapi.php', 'mobile_ws_fetchRecord', 'POST', 0, array($this->p('record'))),
            array('mobile.fetchrecordwithgrouping', 'modules/Mobile/api/wsapi.php', 'mobile_ws_fetchRecordWithGrouping', 'POST', 0, array($this->p('record'))),
            array('mobile.filterdetailswithcount', 'modules/Mobile/api/wsapi.php', 'mobile_ws_filterDetailsWithCount', 'POST', 0, array($this->p('filterid'))),
            array('mobile.listmodulerecords', 'modules/Mobile/api/wsapi.php', 'mobile_ws_listModuleRecords', 'POST', 0, array($this->p('elements', 'encoded'))),
            array('mobile.query', 'modules/Mobile/api/wsapi.php', 'mobile_ws_query', 'POST', 0, array($this->p('module'), $this->p('query'), $this->p('page'))),
            array('mobile.querywithgrouping', 'modules/Mobile/api/wsapi.php', 'mobile_ws_queryWithGrouping', 'POST', 0, array($this->p('module'), $this->p('query'), $this->p('page'))),
            array('mobile.saverecord', 'modules/Mobile/api/wsapi.php', 'mobile_ws_saveRecord', 'POST', 0, array($this->p('module'), $this->p('record'), $this->p('values', 'encoded'))),
            array('mobile.syncModuleRecords', 'modules/Mobile/api/wsapi.php', 'mobile_ws_syncModuleRecords', 'POST', 0, array($this->p('module'), $this->p('syncToken'), $this->p('page'))),
            array('query', 'include/Webservices/Query.php', 'vtws_query', 'GET', 0, array($this->p('query'))),
            array('retrieve', 'include/Webservices/Retrieve.php', 'vtws_retrieve', 'GET', 0, array($this->p('id'))),
            array('retrieve_inventory', 'include/Webservices/LineItem/RetrieveInventory.php', 'vtws_retrieve_inventory', 'GET', 0, array($this->p('id'))),
            array('retrievedocattachment', 'include/Webservices/RetrieveDocAttachment.php', 'berli_retrievedocattachment', 'GET', 0, array($this->p('id'), $this->p('returnfile'))),
            array('revise', 'include/Webservices/Revise.php', 'vtws_revise', 'POST', 0, array($this->p('element', 'Encoded'))),
            array('send_mail_with_attachments', 'include/Webservices/Custom/SendMailWithAttachments.php', 'berli_sendMailWithAttachments', 'POST', 0, array($this->p('receiverIds', 'encoded'), $this->p('templateId'), $this->p('documentIds', 'encoded'), $this->p('mailFieldToUse'), $this->p('fromId'), $this->p('fromName'))),
            array('sync', 'include/Webservices/GetUpdates.php', 'vtws_sync', 'GET', 0, array($this->p('modifiedTime', 'DateTime'), $this->p('elementType'))),
            array('update', 'include/Webservices/Update.php', 'vtws_update', 'POST', 0, array($this->p('element', 'encoded'))),
            array('update_document_relations', 'modules/CWC/UpdateDocRel.php', 'vtws_update_document_relations', 'POST', 0, array($this->p('docid'), $this->p('relids'), $this->p('preserve', 'Boolean'))),
            array('update_product_relations', 'include/Webservices/Custom/ProductRelation.php', 'vtws_update_product_relations', 'POST', 0, array($this->p('productid'), $this->p('relids'), $this->p('preserve'))),
            array('wsapp_deregister', 'modules/WSAPP/api/ws/DeRegister.php', 'wsapp_deregister', 'POST', 0, array($this->p('type'), $this->p('key'))),
            array('wsapp_get', 'modules/WSAPP/api/ws/Get.php', 'wsapp_get', 'POST', 0, array($this->p('key'), $this->p('module'), $this->p('token'))),
            array('wsapp_map', 'modules/WSAPP/api/ws/Map.php', 'wsapp_map', 'POST', 0, array($this->p('key'), $this->p('element', 'encoded'))),
            array('wsapp_put', 'modules/WSAPP/api/ws/Put.php', 'wsapp_put', 'POST', 0, array($this->p('key'), $this->p('element', 'encoded'))),
            array('wsapp_register', 'modules/WSAPP/api/ws/Register.php', 'wsapp_register', 'POST', 0, array($this->p('type'), $this->p('synctype')))
        );
    }

    private function optionalDefinitions(): array
    {
        return array(
            array('getWordMailMergeData', 'include/Webservices/Custom/getWordMailMergeData.php', 'berli_getWordMailMergeData', 'POST', 0, array($this->p('moduleName'), $this->p('listId'), $this->p('page'), $this->p('recordIds'), $this->p('fieldNames'))),
            array('saveWordMailMergeDocument', 'include/Webservices/Custom/saveWordMailMergeDocument.php', 'berli_saveWordMailMergeDocument', 'POST', 0, array($this->p('moduleName'), $this->p('listId'), $this->p('recordId'), $this->p('notesTitle'), $this->p('fileContent'))),
            array('outlook_send_mail', 'include/Webservices/Custom/OutlookSendMail.php', 'berli_outlookSendMail', 'POST', 0, array($this->p('receiverIds', 'encoded'), $this->p('templateId'), $this->p('subject'), $this->p('body'), $this->p('documentIds', 'encoded'), $this->p('mailFieldToUse'), $this->p('fromId'), $this->p('fromName'), $this->p('attachments', 'encoded'))),
            array('outlook_get_verteiler_contacts', 'include/Webservices/Custom/OutlookGetVerteilerContacts.php', 'berli_outlookGetVerteilerContacts', 'POST', 0, array($this->p('verteilerId'))),
            array('outlook_get_event_display', 'include/Webservices/Custom/OutlookGetEventDisplay.php', 'berli_outlookGetEventDisplay', 'POST', 0, array()),
            array('outlook_calendar_event', 'include/Webservices/Custom/OutlookCalendarEvent.php', 'berli_outlookCalendarEvent', 'POST', 0, array($this->p('input', 'encoded'))),
            array('outlook_contact_transfer', 'include/Webservices/Custom/OutlookContactTransfer.php', 'berli_outlookContactTransfer', 'POST', 0, array($this->p('input', 'encoded'))),
            array('outlook_archive_email', 'include/Webservices/Custom/OutlookArchiveEmail.php', 'berli_outlookArchiveEmail', 'POST', 0, array($this->p('input', 'encoded')))
        );
    }
}

class OpenApiRepositoryFakeDb
{
    private $rows;
    public function __construct(array $rows) { $this->rows = $rows; }
    public function pquery($sql, $params) { return new OpenApiRepositoryFakeRecordSet($this->rows); }
}

class OpenApiRepositoryFakeRecordSet
{
    private $rows;
    private $index = 0;
    public function __construct(array $rows) { $this->rows = $rows; }
    public function FetchRow() { return isset($this->rows[$this->index]) ? $this->rows[$this->index++] : false; }
}
