<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 *************************************************************************************/

class Verteiler_DetailView_Model extends Vtiger_DetailView_Model {
	/**
	 * Function to get the detail view links (links and widgets)
	 * @param <array> $linkParams - parameters which will be used to calicaulate the params
	 * @return <array> - array of link models in the format as below
	 *                   array('linktype'=>list of link models);
	 */
	public function getDetailViewLinks($linkParams) {
		$recordModel = $this->getRecord();
		$linkModelList = parent::getDetailViewLinks($linkParams);

		// creates "send E-Mail" button on detail view
		$basicActionLink = array(
			'linktype' => 'DETAILVIEWBASIC',
			'linklabel' => 'LBL_SEND_EMAIL',
			'linkurl' => 'javascript:Verteiler_Detail_Js.sendemail('.$recordModel->getId().')',
			'linkicon' => ''
			);
		$linkModelList['DETAILVIEWBASIC'][] = Vtiger_Link_Model::getInstanceFromValues($basicActionLink);
		
		// creates "Excel Export" button in detail view if in action ExcelExport.php the report ID gets hard wired
		// $basicActionLink = array(
			// 'linktype' => 'DETAILVIEWBASIC',
			// 'linklabel' => 'LBL_EXCEL_EXPORT',
			// 'linkurl' => 'javascript:Verteiler_Detail_Js.exportexcel('.$recordModel->getId().')',
			// 'linkicon' => ''
			// );
		// $linkModelList['DETAILVIEWBASIC'][] = Vtiger_Link_Model::getInstanceFromValues($basicActionLink);
		
		// export Verteiler on detail view
		$basicActionLink = array(
			'linktype' => 'DETAILVIEWBASIC',
			'linklabel' => 'LBL_SPECIAL_EXPORT',
			'linkurl' => 'javascript:Verteiler_Detail_Js.showExportOptions('.$recordModel->getId().')',
			'linkicon' => ''
			);
		$linkModelList['DETAILVIEWBASIC'][] = Vtiger_Link_Model::getInstanceFromValues($basicActionLink);
	
		// check emails
		//$basicActionLink = array(
			//'linktype' => 'DETAILVIEWBASIC',
			//'linklabel' => 'LBL_Check_E-MAIL',
			//'linkurl' => 'javascript:Verteiler_Detail_Js.showEmailCheckResults('.$recordModel->getId().')',
			//'linkicon' => ''
			//);
		//$linkModelList['DETAILVIEWBASIC'][] = Vtiger_Link_Model::getInstanceFromValues($basicActionLink);
	
		return $linkModelList;
	}

}
