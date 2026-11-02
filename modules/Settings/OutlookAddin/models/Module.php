<?php

/**
 * Registers the Outlook add-in web services without changing the existing
 * template-based send_mail_with_attachments service.
 */
class Settings_OutlookAddin_Module_Model {
    const OPERATION_NAME = 'outlook_send_mail';
    const HANDLER_PATH = 'include/Webservices/Custom/OutlookSendMail.php';
    const HANDLER_METHOD = 'berli_outlookSendMail';
    const VERTEILER_CONTACTS_OPERATION_NAME = 'outlook_get_verteiler_contacts';
    const VERTEILER_CONTACTS_HANDLER_PATH = 'include/Webservices/Custom/OutlookGetVerteilerContacts.php';
    const VERTEILER_CONTACTS_HANDLER_METHOD = 'berli_outlookGetVerteilerContacts';
    const EVENT_DISPLAY_OPERATION_NAME = 'outlook_get_event_display';
    const EVENT_DISPLAY_HANDLER_PATH = 'include/Webservices/Custom/OutlookGetEventDisplay.php';
    const EVENT_DISPLAY_HANDLER_METHOD = 'berli_outlookGetEventDisplay';

    public static function registerOperation() {
        // Add-in-owned link data; no existing CRM tables or privileges are altered.
        self::query('CREATE TABLE IF NOT EXISTS vtiger_outlook_calendar_link ('
            . 'userid INT NOT NULL, sourcehash CHAR(64) NOT NULL, eventid VARCHAR(64) NULL, '
            . 'state VARCHAR(16) NOT NULL, PRIMARY KEY (userid, sourcehash)) ENGINE=InnoDB', array());
        self::query('CREATE TABLE IF NOT EXISTS vtiger_outlook_contact_link ('
            . 'userid INT NOT NULL, sourcehash CHAR(64) NOT NULL, contactid VARCHAR(64) NULL, '
            . 'state VARCHAR(16) NOT NULL, PRIMARY KEY (userid, sourcehash)) ENGINE=InnoDB', array());
        self::query('CREATE TABLE IF NOT EXISTS vtiger_outlook_mail_link ('
            . 'userid INT NOT NULL, sourcehash CHAR(64) NOT NULL, emailid VARCHAR(64) NULL, '
            . 'state VARCHAR(16) NOT NULL, PRIMARY KEY (userid, sourcehash)) ENGINE=InnoDB', array());
        self::query('CREATE TABLE IF NOT EXISTS vtiger_outlook_mail_file ('
            . 'userid INT NOT NULL, sourcehash CHAR(64) NOT NULL, filehash CHAR(64) NOT NULL, '
            . 'attachmentid INT NULL, state VARCHAR(16) NOT NULL, '
            . 'PRIMARY KEY (userid, sourcehash, filehash)) ENGINE=InnoDB', array());
        $operations = array(
            array(
                'name' => self::OPERATION_NAME,
                'path' => self::HANDLER_PATH,
                'method' => self::HANDLER_METHOD,
                'params' => array(
                    array('receiverIds', 'encoded'),
                    array('templateId', 'string'),
                    array('subject', 'string'),
                    array('body', 'string'),
                    array('documentIds', 'encoded'),
                    array('mailFieldToUse', 'string'),
                    array('fromId', 'string'),
                    array('fromName', 'string'),
                    array('attachments', 'encoded')
                )
            ),
            array(
                'name' => self::VERTEILER_CONTACTS_OPERATION_NAME,
                'path' => self::VERTEILER_CONTACTS_HANDLER_PATH,
                'method' => self::VERTEILER_CONTACTS_HANDLER_METHOD,
                'params' => array(array('verteilerId', 'string'))
            ),
            array(
                'name' => self::EVENT_DISPLAY_OPERATION_NAME,
                'path' => self::EVENT_DISPLAY_HANDLER_PATH,
                'method' => self::EVENT_DISPLAY_HANDLER_METHOD,
                'params' => array()
            ),
            array(
                'name' => 'outlook_calendar_event',
                'path' => 'include/Webservices/Custom/OutlookCalendarEvent.php',
                'method' => 'berli_outlookCalendarEvent',
                'params' => array(array('input', 'encoded'))
            ),
            array(
                'name' => 'outlook_contact_transfer',
                'path' => 'include/Webservices/Custom/OutlookContactTransfer.php',
                'method' => 'berli_outlookContactTransfer',
                'params' => array(array('input', 'encoded'))
            ),
            array(
                'name' => 'outlook_archive_email',
                'path' => 'include/Webservices/Custom/OutlookArchiveEmail.php',
                'method' => 'berli_outlookArchiveEmail',
                'params' => array(array('input', 'encoded'))
            )
        );

        foreach ($operations as $operation) {
            self::registerWebserviceOperation($operation);
        }
    }

    private static function registerWebserviceOperation(array $operation) {
        if (!is_readable($operation['path'])) {
            throw new RuntimeException('Outlook add-in handler is missing: ' . $operation['path']);
        }

        $db = PearDatabase::getInstance();
        $db->startTransaction();
        try {
            $operationResult = self::query(
                'SELECT operationid FROM vtiger_ws_operation WHERE name = ?',
                array($operation['name'])
            );
            if ($db->num_rows($operationResult) > 1) {
                throw new RuntimeException('Multiple registrations exist for ' . $operation['name'] . '.');
            }

            if ($db->num_rows($operationResult)) {
                $operationId = $db->query_result($operationResult, 0, 'operationid');
                self::query(
                    'UPDATE vtiger_ws_operation SET handler_path = ?, handler_method = ?, type = ?, prelogin = 0 WHERE operationid = ?',
                    array($operation['path'], $operation['method'], 'POST', $operationId)
                );
                self::query(
                    'DELETE FROM vtiger_ws_operation_parameters WHERE operationid = ?',
                    array($operationId)
                );
            } else {
                $operationId = $db->getUniqueID('vtiger_ws_operation');
                self::query(
                    'INSERT INTO vtiger_ws_operation (operationid, name, handler_path, handler_method, type, prelogin) VALUES (?, ?, ?, ?, ?, 0)',
                    array($operationId, $operation['name'], $operation['path'], $operation['method'], 'POST')
                );
            }

            foreach ($operation['params'] as $index => $param) {
                self::query(
                    'INSERT INTO vtiger_ws_operation_parameters (operationid, name, type, sequence) VALUES (?, ?, ?, ?)',
                    array($operationId, $param[0], $param[1], $index + 1)
                );
            }

            if ($db->hasFailedTransaction()) {
                throw new RuntimeException('Outlook add-in web-service registration failed for ' . $operation['name'] . '.');
            }
        } catch (Throwable $error) {
            $db->database->FailTrans();
            $db->completeTransaction();
            throw $error;
        }

        $db->completeTransaction();
    }

    private static function query($sql, $params) {
        $result = PearDatabase::getInstance()->pquery($sql, $params);
        if ($result === false) {
            throw new RuntimeException('Outlook add-in web-service registration database query failed.');
        }
        return $result;
    }
}
