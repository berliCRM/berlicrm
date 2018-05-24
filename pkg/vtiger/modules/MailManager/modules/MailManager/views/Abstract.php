<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

vimport('~~/modules/MailManager/runtime/Request.php');
vimport('modules/MailManager/MailManager.php');
//vimport('modules/MailManager/helpers/Utils.php');
//vimport('modules/MailManager/connectors/Connector.php');
vimport('modules/Settings/MailConverter/handlers/MailRecord.php');

abstract class MailManager_Abstract_View extends Vtiger_Index_View {

	public function preProcess (Vtiger_Request $request, $display = true) {
		if ($this->getOperationArg($request) === 'attachment_dld') {
			return true;
		} else {
			parent::preProcess($request, $display);
		}
	}

	public function postProcess(Vtiger_Request $request) {
		if ($this->getOperationArg($request) === 'attachment_dld') {
			return true;
		} else {
			parent::postProcess($request);
		}
	}

	/**
	 * Function which gets the template handler
	 * @global String $currentModule
	 * @return MailManager_Viewer
	 */
	public function getViewer(Vtiger_Request $request) {
		$viewer = parent::getViewer($request);
		$viewer->assign('MAILBOX', $this->getMailboxModel());
		$viewer->assign('QUALIFIED_MODULE', $request->get('module'));
		return $viewer;
	}

	/**
	 * Function to extract operation argument from request.
	 * @param Vtiger_Request $request
	 * @return type
	 */
	public function getOperationArg(Vtiger_Request $request) {
		return $request->get('_operationarg');
	}

	/**
	 * Mail Manager Connector
	 * @var MailManager_Connector
	 */
	protected $mConnector = false;

	/**
	 * MailBox folder name
	 * @var string
	 */
	protected $mFolder = false;

	/**
	 * Connector to the IMAP server
	 * @var MailManager_Mailbox_Model
	 */
	protected $mMailboxModel = false;

	/**
	 * Returns the active Instance of Current Users MailBox
	 * @return MailManager_Mailbox_Model
	 */
	protected function getMailboxModel() {
		if ($this->mMailboxModel === false) {
			$this->mMailboxModel = MailManager_Mailbox_Model::activeInstance();
		}
		return $this->mMailboxModel;
	}

	/**
	 * Checks if the current users has provided Mail Server details
	 * @return Boolean
	 */
	protected function hasMailboxModel() {
		$model = $this->getMailboxModel();
		return $model->exists();
	}

	/**
	 * Returns a Connector to either MailBox or Internal Drafts
	 * @param String $folder - Name of the folder
	 * @return MailManager_Connector
	 */
	protected function getConnector($folder='') {
		if (!$this->mConnector || ($this->mFolder != $folder)) {
			if($folder == "__vt_drafts") {
				$draftController = new MailManager_Draft_View();
				$this->mConnector = $draftController->connectorWithModel();
			} else {
				if ($this->mConnector) $this->mConnector->close();

				$model = $this->getMailboxModel();
				$this->mConnector = MailManager_Connector_Connector::connectorWithModel($model, $folder);
			}
			$this->mFolder = $folder;
		}
		return $this->mConnector;
	}

	/**
	 * Function that closes connection to IMAP server
	 */
	public function closeConnector() {
		if ($this->mConnector) {
			$this->mConnector->close();
			$this->mConnector = false;
		}
	}
}
?>