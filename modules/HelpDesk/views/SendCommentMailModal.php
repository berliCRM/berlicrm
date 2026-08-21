<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class HelpDesk_SendCommentMailModal_View extends Vtiger_IndexAjax_View
{
    public function process(Vtiger_Request $request): void
    {
        $viewer = $this->getViewer($request);
        $viewer->assign('MODULE', $request->getModule());
        $viewer->assign('DEFAULT_CC', implode(', ', $this->getAdditionalParticipantEmails((int) $request->get('record'))));
        $viewer->view('SendCommentMailModal.tpl', $request->getModule());
    }

    /**
     * Return mail converter senders involved in this ticket, except for the
     * contact/account that is already the ticket's primary recipient.
     */
    protected function getAdditionalParticipantEmails(int $ticketId): array
    {
        if ($ticketId <= 0 || getSalesEntityType($ticketId) !== 'HelpDesk'
            || isPermitted('HelpDesk', 'DetailView', $ticketId) !== 'yes') {
            return array();
        }

        $db = PearDatabase::getInstance();
        $primaryResult = $db->pquery(
            'SELECT ticket.contact_id AS primary_contact_id,
                    contact.email AS contact_email, contact.secondaryemail AS contact_secondary_email,
                    account.email1 AS account_email1, account.email2 AS account_email2
             FROM vtiger_troubletickets ticket
             LEFT JOIN vtiger_contactdetails contact ON contact.contactid = ticket.contact_id
             LEFT JOIN vtiger_account account ON account.accountid = ticket.parent_id
             WHERE ticket.ticketid = ?',
            array($ticketId)
        );

        $excludedEmails = array();
        $primaryContactId = 0;
        if ($primaryResult && $db->num_rows($primaryResult)) {
            $primaryContactId = (int) $db->query_result($primaryResult, 0, 'primary_contact_id');
            foreach (array('contact_email', 'contact_secondary_email', 'account_email1', 'account_email2') as $columnName) {
                $email = $this->normalizeEmail($db->query_result($primaryResult, 0, $columnName));
                if ($email !== '') {
                    $excludedEmails[$email] = true;
                }
            }
        }

        $participantResult = $db->pquery(
            'SELECT comments.mailfrom, contact.contactid, contact.email, contact.secondaryemail
             FROM vtiger_modcomments comments
             INNER JOIN vtiger_crmentity entity ON entity.crmid = comments.modcommentsid AND entity.deleted = 0
             INNER JOIN vtiger_contactdetails contact ON contact.contactid = comments.customer
             INNER JOIN vtiger_crmentity contact_entity ON contact_entity.crmid = contact.contactid
                AND contact_entity.deleted = 0
             WHERE comments.related_to = ?
             ORDER BY entity.createdtime ASC, comments.modcommentsid ASC',
            array($ticketId)
        );

        $participantEmails = array();
        if (!$participantResult) {
            return $participantEmails;
        }

        $rowCount = $db->num_rows($participantResult);
        for ($i = 0; $i < $rowCount; $i++) {
            if ((int) $db->query_result($participantResult, $i, 'contactid') === $primaryContactId) {
                continue;
            }
            $email = $this->normalizeEmail($db->query_result($participantResult, $i, 'mailfrom'));
            if ($email === '') {
                $email = $this->normalizeEmail($db->query_result($participantResult, $i, 'email'));
            }
            if ($email === '') {
                $email = $this->normalizeEmail($db->query_result($participantResult, $i, 'secondaryemail'));
            }
            if ($email !== '' && empty($excludedEmails[$email]) && empty($participantEmails[$email])) {
                $participantEmails[$email] = $email;
            }
        }

        return array_values($participantEmails);
    }

    protected function normalizeEmail($email): string
    {
        $email = strtolower(trim((string) $email));
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : '';
    }
}
