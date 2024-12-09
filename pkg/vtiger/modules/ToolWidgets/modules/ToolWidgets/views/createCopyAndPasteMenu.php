<?php

/**
 * Class ToolWidgets_createCopyAndPasteMenu_View
 * Handles the creation of a copy-and-paste data menu in the edit view.
 */
class ToolWidgets_createCopyAndPasteMenu_View extends Vtiger_Detail_View {
    /**
     * Checks user permissions for accessing this view.
     * 
     * @param Vtiger_Request $request The request object containing parameters.
     * @return void
     */
    public function checkPermission(Vtiger_Request $request): void  {
        // No specific permissions required for this view.
        return;
    }

    /**
     * Processes the request and generates the copy-paste data menu.
     * 
     * @param Vtiger_Request $request The request object containing parameters.
     * @return void
     */
    public function process(Vtiger_Request $request): void {
        $moduleName = $request->getModule();
        $sourceModule = strval($request->get('sourcemodule'));
        $recordId = $request->get('recordid');
        $copypastestring = "";

        if ($sourceModule === 'Contacts') {
            $copypastestring = $this->generateContactCopyPasteString($recordId);
        } 
		elseif ($sourceModule === 'Accounts') {
            $copypastestring = $this->generateAccountCopyPasteString($recordId);
        }

        $viewer = $this->getViewer($request);
        $viewer->assign('COPYPASTESTRING', $copypastestring);
        $viewer->assign('SOURCEMODULE', $recordId);
        $viewer->assign('MODULE', $moduleName);
        $viewer->view('createCopyPasteDataMenue.tpl', 'ToolWidgets');
    }

    /**
     * Generates the copy-paste string for a Contact record.
     * 
     * @param int $recordId The ID of the Contact record.
     * @return string The generated copy-paste string.
     */
    private function generateContactCopyPasteString(int $recordId): string {
        $focus = Vtiger_DetailView_Model::getInstance('Contacts', $recordId)->getRecord();
        $fields = [
            "salutationtype" => "salutationtype",
            "firstname" => "firstname",
            "lastname" => "lastname",
            "department" => "department",
            "phone" => "phone",
            "email" => "email",
            "title" => "title",
            "mailingstreet" => "mailingstreet",
            "mailingzip" => "mailingzip",
            "mailingcity" => "mailingcity",
            "mailingcountry" => "mailingcountry"
        ];

        $info = ["accountname" => getAccountName($focus->get('account_id'))];
        foreach ($fields as $fieldName => $colName) {
            $info[$fieldName] = $focus->get($colName);
        }

        return $this->generateCopyPasteString($info, ['salutationtype', 'firstname', 'lastname', 'department', 'phone', 'email', 'title', 'mailingstreet', 'mailingzip', 'mailingcity', 'mailingcountry']);
    }

    /**
     * Generates the copy-paste string for an Account record.
     * 
     * @param int $recordId The ID of the Account record.
     * @return string The generated copy-paste string.
     */
    private function generateAccountCopyPasteString(int $recordId): string {
        $focus = Vtiger_DetailView_Model::getInstance('Accounts', $recordId)->getRecord();
        $fields = [
            "accountname" => "accountname",
            "phone" => "phone",
            "website" => "website",
            "email" => "email1",
            "bill_street" => "bill_street",
            "bill_code" => "bill_code",
            "bill_city" => "bill_city",
            "bill_state" => "bill_state",
            "bill_country" => "bill_country"
        ];

        $info = ["accountname" => getAccountName($focus->get('account_id'))];
        foreach ($fields as $fieldName => $colName) {
            $info[$fieldName] = $focus->get($colName);
        }

        return $this->generateCopyPasteString($info, ['accountname', 'phone', 'website', 'email', 'bill_street', 'bill_code', 'bill_city', 'bill_country']);
    }

    /**
     * Generates a formatted copy-paste string based on the provided information.
     * 
     * @param array $info The data to include in the string.
     * @param array $fieldOrder The order of fields to include in the string.
     * @return string The generated copy-paste string.
     */
    private function generateCopyPasteString(array $info, array $fieldOrder): string {
        $copypastestring = "";

        foreach ($fieldOrder as $colName) {
            $separator = in_array($colName, ['firstname', 'mailingzip', 'bill_code']) ? " " : "\n";
            if (!empty($info[$colName])) {
                $copypastestring .= $info[$colName] . $separator;
            }
        }

        return $copypastestring;
    }
}