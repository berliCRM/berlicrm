<?php


trait ModComments_TicketStatusChangeTrait
{
    protected function getTicketStatusChangeFromRequest(Vtiger_Request $request): ?array
    {
        if (!$request->has('ticketstatus')) {
            return null;
        }

        $relatedRecordId = $request->get('related_to');
        if (empty($relatedRecordId) || getSalesEntityType($relatedRecordId) !== 'HelpDesk') {
            return null;
        }

        $ticketRecordModel = Vtiger_Record_Model::getInstanceById($relatedRecordId, 'HelpDesk');
        $currentTicketStatus = $ticketRecordModel->get('ticketstatus');
        $selectedTicketStatus = trim((string)$request->get('ticketstatus'));

        if ($selectedTicketStatus === '' || $selectedTicketStatus === $currentTicketStatus) {
            return null;
        }

        $ticketStatusField = $ticketRecordModel->getModule()->getField('ticketstatus');
        if (
            empty($ticketStatusField)
            || !$ticketStatusField->isEditable()
            || !Users_Privileges_Model::isPermitted('HelpDesk', 'Save', $relatedRecordId)
        ) {
            throw new AppException('LBL_PERMISSION_DENIED');
        }

        $allowedTicketStatuses = $ticketStatusField->getPicklistValues();
        if (empty($allowedTicketStatuses)) {
            $allowedTicketStatuses = array();
        }
        if (!array_key_exists($selectedTicketStatus, $allowedTicketStatuses)) {
            throw new AppException('LBL_PERMISSION_DENIED');
        }

        return array($ticketRecordModel, $selectedTicketStatus);
    }

    protected function saveRelatedTicketStatusChange(?array $ticketStatusChange): void
    {
        if ($ticketStatusChange === null) {
            return;
        }

        [$ticketRecordModel, $selectedTicketStatus] = $ticketStatusChange;
        $ticketRecordModel->set('mode', 'edit');
        $ticketRecordModel->set('ticketstatus', $selectedTicketStatus);
        $ticketRecordModel->save();
    }
}
