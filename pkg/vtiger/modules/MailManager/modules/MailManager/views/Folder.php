<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class MailManager_Folder_View extends MailManager_Abstract_View {

	/**
	 * Process the request for Folder opertions
	 * @global <type> $maxEntriesPerPage
	 * @param Vtiger_Request $request
	 * @return MailManager_Response
	 */
	public function process(Vtiger_Request $request) {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$maxEntriesPerPage = vglobal('list_max_entries_per_page');

		$response = new Vtiger_Response();
		$moduleName = $request->getModule();
		if ('open' == $this->getOperationArg($request)) {
			$q = $request->get('q');
			$foldername = $request->get('_folder');
			$type = $request->get('type');

			$connector = $this->getConnector($foldername);
			$folder = $connector->folderInstance($foldername);

			if (empty($q)) {
				$connector->folderMails($folder, intval($request->get('_page', 0)), $maxEntriesPerPage);
			} else {
				if(empty($type)) {
					$type='ALL';
				}
				if($type == 'ON') {
					$dateFormat = $currentUserModel->get('date_format');
					if ($dateFormat == 'mm-dd-yyyy') {
						$dateArray = explode('-', $q);
						$temp = $dateArray[0];
						$dateArray[0] = $dateArray[1];
						$dateArray[1] = $temp;
						$q = implode('-', $dateArray);
					}
					$query = date('d M Y',strtotime($q));
					$q = ''.$type.' "'.vtlib_purify($query).'"';
				} else {
					$q = ''.$type.' "'.vtlib_purify($q).'"';
				}
				$connector->searchMails($q, $folder, intval($request->get('_page', 0)), $maxEntriesPerPage);
			}

			$folderList = $connector->getFolderList();

			$viewer = $this->getViewer($request);

			$viewer->assign('TYPE', $type);
			$viewer->assign('QUERY', $request->get('q'));
			$viewer->assign('FOLDER', $folder);
			$viewer->assign('FOLDERLIST',  $folderList);
			$viewer->assign('SEARCHOPTIONS' ,self::getSearchOptions());
			$viewer->assign("JS_DATEFORMAT",parse_calendardate(getTranslatedString('NTC_DATE_FORMAT')));
			$viewer->assign('USER_DATE_FORMAT', $currentUserModel->get('date_format'));
			$viewer->assign('MODULE', $moduleName);
			$response->setResult( $viewer->view( 'FolderOpen.tpl', $moduleName, true ) );
		} elseif('drafts' == $this->getOperationArg($request)) {
			$q = $request->get('q');
			$type = $request->get('type');
			$page = intval($request->get('_page', 0));

			$connector = $this->getConnector('__vt_drafts');
			$folder = $connector->folderInstance();

			if(empty($q)) {
				$draftMails = $connector->getDrafts($page, $maxEntriesPerPage, $folder);
			} else {
				$draftMails = $connector->searchDraftMails($q, $type, $page, $maxEntriesPerPage, $folder);
			}

			$viewer = $this->getViewer($request);
			$viewer->assign('MAILS', $draftMails);
			$viewer->assign('FOLDER', $folder);
			$viewer->assign('SEARCHOPTIONS' ,MailManager_Draft_View::getSearchOptions());
			$viewer->assign('USER_DATE_FORMAT', $currentUserModel->get('date_format'));
			$viewer->assign('MODULE', $moduleName);
			$response->setResult($viewer->view('FolderDrafts.tpl', 'MailManager', true));
		} else if ('getFoldersList' == $this->getOperationArg($request)) {
			$viewer = $this->getViewer($request);
			if ($this->hasMailboxModel()) {
				$connector = $this->getConnector();

				if (!$connector->hasError()) {
					$folders = $connector->folders();
					$connector->updateFolders();
					$viewer->assign('FOLDERS', $folders);
				}
				$this->closeConnector();
			}
			$viewer->assign('MODULE', $request->getModule());
			$response->setResult($viewer->view('FolderList.tpl', $moduleName, true));
		}
		return $response;
	}

	/**
	 * Returns the List of search string on the MailBox
	 * @return string
	 */
	public static function getSearchOptions() {
		$options = array('SUBJECT'=>'SUBJECT','TO'=>'TO','BODY'=>'BODY','BCC'=>'BCC','CC'=>'CC','FROM'=>'FROM','DATE'=>'ON');
		return $options;
	}
        
        public function validateRequest(Vtiger_Request $request) { 
            return $request->validateWriteAccess(); 
        } 
}
?>
