<?php


trait ModComments_TicketStatusChangeTrait
{
    protected function normalizeCommentHelpDeskFields(Vtiger_Request $request, Vtiger_Record_Model $recordModel): void
    {
        $relatedRecordId = $recordModel->get('related_to');
        if (empty($relatedRecordId)) {
            $relatedRecordId = $request->get('related_to');
        }

        $isHelpDeskComment = !empty($relatedRecordId) && getSalesEntityType($relatedRecordId) === 'HelpDesk';
        $previousTimeNeeded = $this->getPreviousCommentTimeNeeded($recordModel);

        if (!$isHelpDeskComment) {
            $recordModel->set('timeneeded', $previousTimeNeeded);
            return;
        }

        if ($request->has('timeneeded')) {
            $recordModel->set('timeneeded', $this->normalizeCommentDuration($request->get('timeneeded')));
        } else {
            $recordModel->set('timeneeded', $previousTimeNeeded);
        }
    }

    protected function getPreviousCommentTimeNeeded(Vtiger_Record_Model $recordModel): string
    {
        $recordId = $recordModel->getId();
        if (empty($recordId)) {
            return '';
        }

        $previousRecordModel = ModComments_Record_Model::getInstanceById($recordId, 'ModComments');
        if (!$previousRecordModel) {
            return '';
        }

        return (string)$previousRecordModel->get('timeneeded');
    }

    protected function normalizeCommentDuration($value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        if (!preg_match('/^(\d{1,3}):([0-5]\d)(?::([0-5]\d))?$/', $value, $matches)) {
            return '';
        }

        $hours = (int)$matches[1];
        $minutes = (int)$matches[2];
        $seconds = isset($matches[3]) ? (int)$matches[3] : 0;

        if ($hours === 0 && $minutes === 0 && $seconds === 0) {
            return '';
        }

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

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
