<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_DetailView_Model extends Vtiger_Base_Model {

	protected $module = false;
	protected $record = false;

	/**
	 * Function to get Module instance
	 * @return <Vtiger_Module_Model>
	 */
	public function getModule() {
		return $this->module;
	}

	/**
	 * Function to set the module instance
	 * @param <Vtiger_Module_Model> $moduleInstance - module model
	 * @return Vtiger_DetailView_Model>
	 */
	public function setModule($moduleInstance) {
		$this->module = $moduleInstance;
		return $this;
	}

	/**
	 * Function to get the Record model
	 * @return <Vtiger_Record_Model>
	 */
	public function getRecord() {
		return $this->record;
	}

	/**
	 * Function to set the record instance3
	 * @param <type> $recordModuleInstance - record model
	 * @return Vtiger_DetailView_Model
	 */
	public function setRecord($recordModuleInstance) {
		$this->record = $recordModuleInstance;
		return $this;
	}

	/**
	 * Function to get the detail view links (links and widgets)
	 * @param <array> $linkParams - parameters which will be used to calicaulate the params
	 * @return <array> - array of link models in the format as below
	 *                   array('linktype'=>list of link models);
	 */
	public function getDetailViewLinks($linkParams) {
		$linkTypes = array('DETAILVIEWBASIC','DETAILVIEW','DETAILVIEWTAB');
		$moduleModel = $this->getModule();
		$recordModel = $this->getRecord();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		$moduleName = $moduleModel->getName();
		$recordId = $recordModel->getId();

		$detailViewLink = array();

		if(Users_Privileges_Model::isPermitted($moduleName, 'EditView', $recordId)) {
			$detailViewLinks[] = array(
					'linktype' => 'DETAILVIEWBASIC',
					'linklabel' => 'LBL_EDIT',
					'linkurl' => $recordModel->getEditViewUrl(),
					'linkicon' => ''
			);

			foreach ($detailViewLinks as $detailViewLink) {
				$linkModelList['DETAILVIEWBASIC'][] = Vtiger_Link_Model::getInstanceFromValues($detailViewLink);
			}
		}

		$linkModelListDetails = Vtiger_Link_Model::getAllByType($moduleModel->getId(),$linkTypes,$linkParams);
		//Mark all detail view basic links as detail view links.
		//Since ui will be look ugly if you need many basic links
		$detailViewBasiclinks = $linkModelListDetails['DETAILVIEWBASIC'];
		unset($linkModelListDetails['DETAILVIEWBASIC']);

		if(Users_Privileges_Model::isPermitted($moduleName, 'Delete', $recordId)) {
			if ($currentUserModel->get('language') == 'de_de') {
				$str_delete = sprintf("%s %s", vtranslate('SINGLE_'. $moduleName, $moduleName), strtolower(getTranslatedString('LBL_DELETE', $moduleName)));
			} else {
				$str_delete = sprintf("%s %s", getTranslatedString('LBL_DELETE', $moduleName), vtranslate('SINGLE_'. $moduleName, $moduleName));
			}
			$deletelinkModel = array(
					'linktype' => 'DETAILVIEW',
					'linklabel' => $str_delete,
					'linkurl' => 'javascript:Vtiger_Detail_Js.deleteRecord("'.$recordModel->getDeleteUrl().'")',
					'linkicon' => ''
			);
			$linkModelList['DETAILVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($deletelinkModel);
		}

		if(Users_Privileges_Model::isPermitted($moduleName, 'CreateView', $recordId)) {
			$duplicateLinkModel = array(
						'linktype' => 'DETAILVIEWBASIC',
						'linklabel' => 'LBL_DUPLICATE',
						'linkurl' => $recordModel->getDuplicateRecordUrl(),
						'linkicon' => ''
				);
			$linkModelList['DETAILVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($duplicateLinkModel);
		}

		if(!empty($detailViewBasiclinks)) {
			foreach($detailViewBasiclinks as $linkModel) {
				// Remove view history, needed in vtiger5 to see history but not in vtiger6
				if($linkModel->linklabel == 'View History') {
					continue;
				}
				$linkModelList['DETAILVIEW'][] = $linkModel;
			}
		}

		$relatedLinks = $this->getDetailViewRelatedLinks();

		foreach($relatedLinks as $relatedLinkEntry) {
			$relatedLink = Vtiger_Link_Model::getInstanceFromValues($relatedLinkEntry);
			$linkModelList[$relatedLink->getType()][] = $relatedLink;
		}

                $detailViewBasicTablinks = $linkModelListDetails['DETAILVIEWTAB'];
                if(!empty($detailViewBasicTablinks)) {
                    foreach($detailViewBasicTablinks as $linkModel) {
                        $linkModelList['DETAILVIEWTAB'][] = $linkModel;
                    }
                }
                
		$widgets = $this->getWidgets();
		foreach($widgets as $widgetLinkModel) {
			$linkModelList['DETAILVIEWWIDGET'][] = $widgetLinkModel;
		}

		if($currentUserModel->isAdminUser()) {
			$settingsLinks = $moduleModel->getSettingLinks();
			foreach($settingsLinks as $settingsLink) {
				$linkModelList['DETAILVIEWSETTING'][] = Vtiger_Link_Model::getInstanceFromValues($settingsLink);
			}
		}

		return $linkModelList;
	}

	/**
	 * Function to get the detail view related links
	 * @return <array> - list of links parameters
	 */
	public function getDetailViewRelatedLinks() {
		$recordModel = $this->getRecord();
		$moduleName = $recordModel->getModuleName();
		$parentModuleModel = $this->getModule();
		$relatedLinks = array();
		// Defer the expensive badge queries until after the detail view has
		// rendered. Enabling the preference retains the synchronous legacy path.
		$computeRelatedRecordCount = PerformancePrefs::getBoolean(
				'LISTVIEW_COMPUTE_PAGE_COUNT', false);

		if($parentModuleModel->isSummaryViewSupported()) {
			$relatedLinks = array(array(
				'linktype' => 'DETAILVIEWTAB',
				'linklabel' => vtranslate('SINGLE_' . $moduleName, $moduleName) . ' ' . vtranslate('LBL_SUMMARY', $moduleName),
				'linkKey' => 'LBL_RECORD_SUMMARY',
				'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showDetailViewByMode&requestMode=summary',
				'linkicon' => ''
			));
		}
		//link which shows the summary information(generally detail of record)
		$relatedLinks[] = array(
				'linktype' => 'DETAILVIEWTAB',
				'linklabel' => vtranslate('SINGLE_'.$moduleName, $moduleName).' '. vtranslate('LBL_DETAILS', $moduleName),
                                'linkKey' => 'LBL_RECORD_DETAILS',
				'linkurl' => $recordModel->getDetailViewUrl().'&mode=showDetailViewByMode&requestMode=full',
				'linkicon' => ''
		);

		$modCommentsModel = Vtiger_Module_Model::getInstance('ModComments');
		if($parentModuleModel->isCommentEnabled() && $modCommentsModel->isPermitted('DetailView')) {
			$commentLink = array(
					'linktype' => 'DETAILVIEWTAB',
					'linklabel' => 'ModComments',
					'linkurl' => $recordModel->getDetailViewUrl().'&mode=showAllComments',
					'linkicon' => '',
					'relatedModuleName' => $modCommentsModel->get('name'),
					'countKind' => 'comments',
					'countDeferred' => !$computeRelatedRecordCount
			);
			if ($computeRelatedRecordCount) {
				$commentLink['recordcount'] = ModComments_Record_Model::getAllCommentRecordsCount($recordModel->get('id'));
			}
			$relatedLinks[] = $commentLink;
		}

		if($parentModuleModel->isTrackingEnabled()) {
			$relatedLinks[] = array(
					'linktype' => 'DETAILVIEWTAB',
					'linklabel' => 'LBL_UPDATES',
					'linkurl' => $recordModel->getDetailViewUrl().'&mode=showRecentActivities&page=1',
					'linkicon' => ''
			);
		}


		$relationModels = $parentModuleModel->getRelations();

		if ($computeRelatedRecordCount) {
			// The current record model is already fully loaded and is exactly the
			// parent model required by the relation list. Reusing it avoids another
			// complete record query while building the detail-view tabs.
			$parentRecordModel = $recordModel;
			$relationListViewClassName = Vtiger_Loader::getComponentClassName(
					'Model', 'RelationListView', $parentModuleModel->get('name'));
		}

		foreach($relationModels as $relation) {
			$link = array(
					'linktype' => 'DETAILVIEWRELATED',
					'linklabel' => $relation->get('label'),
					'linkurl' => $relation->getListUrl($recordModel),
					'linkicon' => '',
					'relatedModuleName' => $relation->get('relatedModuleName'),
					'relationId' => (int) $relation->getId(),
					'countKind' => 'relation',
					'countDeferred' => !$computeRelatedRecordCount
			);

			if ($computeRelatedRecordCount) {
				// getRelations() already returned the exact relation model. Passing its
				// module and relation directly avoids looking it up again for every tab.
				$relationListView = new $relationListViewClassName();
				$relationListView->setRelatedModuleModel($relation->getRelationModuleModel())
						->setParentRecordModel($parentRecordModel)
						->setRelationModel($relation);
				$link['recordcount'] = $relationListView->getRelatedEntriesCount();
			}

            // crm-now: default sort order "newest first" for related emails and activities
            if ($relation->get('relatedModuleName') == "Emails" || $relation->get('relatedModuleName') == "Calendar") {
                $link['linkurl'] .= "&orderby=date_start&sortorder=DESC";
            }

			$relatedLinks[] = $link;
		}

		return $relatedLinks;
	}

	/**
	 * Compute the requested detail-view badge counts for the asynchronous
	 * follow-up request. Relation ids are used as stable keys because module
	 * names and labels are not guaranteed to be unique.
	 *
	 * @param array $relationIds ids of related tabs rendered for this request
	 * @param bool $includeComments whether the comments badge was rendered
	 * @return array
	 */
	public function getDeferredRelatedRecordCounts($relationIds = array(), $includeComments = false) {
		$recordModel = $this->getRecord();
		$parentModuleModel = $this->getModule();
		$result = array(
				'module' => $parentModuleModel->get('name'),
				'record' => (int) $recordModel->getId(),
				'comments' => null,
				'relations' => array()
		);

		if ($includeComments) {
			$modCommentsModel = Vtiger_Module_Model::getInstance('ModComments');
			if ($modCommentsModel && $parentModuleModel->isCommentEnabled() &&
					$modCommentsModel->isPermitted('DetailView')) {
				try {
					$result['comments'] = (int) ModComments_Record_Model::getAllCommentRecordsCount(
							$recordModel->getId());
				} catch (Throwable $error) {
					// A failed optional badge must not prevent the relation counts.
				}
			}
		}

		if (!is_array($relationIds)) {
			$relationIds = array($relationIds);
		}
		$requestedRelationIds = array();
		foreach ($relationIds as $relationId) {
			if (!is_scalar($relationId)) {
				continue;
			}
			$relationId = (int) $relationId;
			if ($relationId > 0) {
				$requestedRelationIds[$relationId] = true;
			}
		}

		if (empty($requestedRelationIds)) {
			return $result;
		}

		try {
			$relationListViewClassName = Vtiger_Loader::getComponentClassName(
					'Model', 'RelationListView', $parentModuleModel->get('name'));
		} catch (Throwable $error) {
			return $result;
		}

		// getRelations() is the authoritative, permission-filtered list. Client
		// ids are only used to select the links that were actually rendered.
		foreach ($parentModuleModel->getRelations() as $relation) {
			$relationId = (int) $relation->getId();
			if (!$relationId || !isset($requestedRelationIds[$relationId])) {
				continue;
			}

			try {
				$relationListView = new $relationListViewClassName();
				$relationListView->setRelatedModuleModel($relation->getRelationModuleModel())
						->setParentRecordModel($recordModel)
						->setRelationModel($relation);
				$count = $relationListView->getRelatedEntriesCount();

				if (is_numeric($count)) {
					$result['relations'][] = array(
							'relationId' => $relationId,
							'count' => (int) $count
					);
				}
			} catch (Throwable $error) {
				// Keep delivering other badges if one custom relation query fails.
			}
		}

		return $result;
	}

	/**
	 * Function to get the detail view widgets
	 * @return <Array> - List of widgets , where each widget is an Vtiger_Link_Model
	 */
	public function getWidgets() {
		$moduleModel = $this->getModule();
		$widgets = array();

		$modCommentsModel = Vtiger_Module_Model::getInstance('ModComments');
		if($moduleModel->isCommentEnabled() && $modCommentsModel->isPermitted('DetailView')) {
			$widgets[] = array(
					'linktype' => 'DETAILVIEWWIDGET',
					'linklabel' => 'ModComments',
					'linkurl' => 'module='.$this->getModuleName().'&view=Detail&record='.$this->getRecord()->getId().
							'&mode=showRecentComments&page=1&limit=5'
			);
		}

		if($moduleModel->isTrackingEnabled()) {
			$widgets[] = array(
					'linktype' => 'DETAILVIEWWIDGET',
					'linklabel' => 'LBL_UPDATES',
					'linkurl' => 'module='.$this->getModuleName().'&view=Detail&record='.$this->getRecord()->getId().
							'&mode=showRecentActivities&page=1&limit=5&pagin=1',
			);
		}

		$widgetLinks = array();
		foreach ($widgets as $widgetDetails) {
			$widgetLinks[] = Vtiger_Link_Model::getInstanceFromValues($widgetDetails);
		}
		return $widgetLinks;
	}

	/**
	 * Function to get the Quick Links for the Detail view of the module
	 * @param <Array> $linkParams
	 * @return <Array> List of Vtiger_Link_Model instances
	 */
	public function getSideBarLinks($linkParams) {
		$currentUser = Users_Record_Model::getCurrentUserModel();

		$linkTypes = array('SIDEBARLINK', 'SIDEBARWIDGET');
		$moduleLinks = $this->getModule()->getSideBarLinks($linkTypes);

		$listLinkTypes = array('DETAILVIEWSIDEBARLINK', 'DETAILVIEWSIDEBARWIDGET');
		$listLinks = Vtiger_Link_Model::getAllByType($this->getModule()->getId(), $listLinkTypes);

		if($listLinks['DETAILVIEWSIDEBARLINK']) {
			foreach($listLinks['DETAILVIEWSIDEBARLINK'] as $link) {
				$link->linkurl = $link->linkurl.'&record='.$this->getRecord()->getId().'&source_module='.$this->getModule()->getName();
				$moduleLinks['SIDEBARLINK'][] = $link;
			}
		}

		if($currentUser->getTagCloudStatus()) {
			$tagWidget = array(
				'linktype' => 'DETAILVIEWSIDEBARWIDGET',
				'linklabel' => 'LBL_TAG_CLOUD',
				'linkurl' => 'module='.$this->getModule()->getName().'&view=ShowTagCloud&mode=showTags',
				'linkicon' => '',
			);
			$linkModel = Vtiger_Link_Model::getInstanceFromValues($tagWidget);
			if($listLinks['DETAILVIEWSIDEBARWIDGET']) array_push($listLinks['DETAILVIEWSIDEBARWIDGET'], $linkModel);
			else $listLinks['DETAILVIEWSIDEBARWIDGET'][] = $linkModel;
		}

		if($listLinks['DETAILVIEWSIDEBARWIDGET']) {
			foreach($listLinks['DETAILVIEWSIDEBARWIDGET'] as $link) {
				$link->linkurl = $link->linkurl.'&record='.$this->getRecord()->getId().'&source_module='.$this->getModule()->getName();
				$moduleLinks['SIDEBARWIDGET'][] = $link;
			}
		}

		return $moduleLinks;
	}

	/**
	 * Function to get the module label
	 * @return <String> - label
	 */
	public function getModuleLabel() {
		return $this->getModule()->get('label');
	}

	/**
	 *  Function to get the module name
	 *  @return <String> - name of the module
	 */
	public function getModuleName() {
		return $this->getModule()->get('name');
	}

	/**
	 * Function to get the instance
	 * @param <String> $moduleName - module name
	 * @param <String> $recordId - record id
	 * @return <Vtiger_DetailView_Model>
	 */
	public static function getInstance($moduleName,$recordId) {
		$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'DetailView', $moduleName);
		$instance = new $modelClassName();

		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);

		return $instance->setModule($moduleModel)->setRecord($recordModel);
	}
}
