<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

require_once 'modules/Settings/CronTasks/models/Config.php';

class Settings_CronTasks_List_View extends Settings_Vtiger_List_View {

	public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$listViewModel = Settings_Vtiger_ListView_Model::getInstance($qualifiedModuleName);
		$listViewModel->set('orderby', 'sequence');		 

		$pagingModel = new Vtiger_Paging_Model();

		if(!$this->listViewHeaders){
			$this->listViewHeaders = $listViewModel->getListViewHeaders();
		}
		if(!$this->listViewEntries){
			$this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
		}

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE_MODEL', $listViewModel->getModule());
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER', 1);
		$viewer->assign('ORDER_BY', 'sequence');
		$viewer->assign('SORT_ORDER', 'ASC');
		$viewer->assign('NEXT_SORT_ORDER', 'DESC');
		$viewer->assign('SORT_IMAGE', 'icon-chevron-up');
		$viewer->assign('COLUMN_NAME', 'sequence');
		$viewer->assign('LISTVIEW_ENTRIES_COUNT', count($this->listViewEntries));
		$viewer->assign('LISTVIEW_COUNT', count($this->listViewEntries));
		$viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
		$viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('CRON_MAIL_CONFIG', Settings_CronTasks_Config_Model::getInstance()->getData());
		$viewer->assign('CRON_MAIL_SAVE_STATUS', $request->get('cronMailSaved'));
		$viewer->assign('SETTINGS_BLOCK', $request->get('block'));
		$viewer->assign('SETTINGS_FIELDID', $request->get('fieldid'));
	}

}
