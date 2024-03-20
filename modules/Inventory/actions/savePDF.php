<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  berliCRM Open Source
 * The Initial Developer of the Original Code is crm-now.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
require_once('modules/Documents/Documents.php');

class Inventory_savePDF_Action extends Vtiger_BasicAjax_Action{

	public function process(Vtiger_Request $request) {
		global $current_user;
		$db = PearDatabase::getInstance();
		$record = $request->get('record');
		$moduleName = $request->get('relmodule');
		require_once('modules/'.$moduleName.'/pdfcreator.php');
		$response = new Vtiger_Response();

		$recordModel = Inventory_Record_Model::getInstanceById($record);
		
		if ($recordModel AND Users_Privileges_Model::isPermitted('Documents', 'CreateView')) {
			$subject = $recordModel->get('subject');
			$upload_filepath = decideFilePath();
			$new_unique_id = $db->getUniqueID("vtiger_crmentity");
			if ($request->get('printmode')=='shipping') {
				$pdf_desc = 'LBL_PDFSUBJECT_SHN';
				$filename = createpdffile ($record,'savesn',$upload_filepath,$new_unique_id);
				
			}
			else {
				$pdf_desc = 'LBL_PDFSUBJECT';
				$filename = createpdffile ($record,'save',$upload_filepath,$new_unique_id);
			}
			$filetype= 'pdf';
			$filesize = filesize($upload_filepath.$new_unique_id.'_'.$filename);
			// set the description with reference to the quote
			$link_url = 'index.php?module='.$moduleName.'&view=Detail&record='.$record ;
			$desc = vtranslate($pdf_desc, $moduleName).' <a href='.$link_url.'>&quot;'. $recordModel->get('subject').'&quot;</a>&nbsp;';
			//get time
			$cur_datetime = new DateTime(null);
			$dateTimeField = new DateTimeField($cur_datetime->format('Y-m-d H:i:s'));
			$timeFormatedString = $dateTimeField->getDisplayTime();
			$dateFormatedString = $dateTimeField->getDisplayDate();
			// get user name
			$username = getUserName($current_user->id);
			//create subject
			$record_no_arr = Array ('Quotes'=>'quote_no','SalesOrder'=>'salesorder_no','Invoice'=>'invoice_no','PurchaseOrder'=>'purchaseorder_no',);
			if ($request->get('printmode')=='shipping') {
				$subject = vtranslate('Shippingnote', $moduleName);
			}
			else {
				$subject = vtranslate('SINGLE_'.$moduleName, $moduleName);
			}
			$subject .= ' '.$recordModel->get($record_no_arr[$moduleName]).' ('.decode_html($username).': '.'['.$dateFormatedString.' '.$timeFormatedString.'])';

			// check whether folder exists
			$foldername = vtranslate('LBL_PDF_FOLDERNAME', $moduleName);
			$attQuery="select * from vtiger_attachmentsfolder where foldername =?";
			$folderResult=$db->pquery($attQuery,array($foldername));
			if ( $db->num_rows($folderResult)==0) {
				// create Folder is not exists
				$folderdesc = vtranslate('LBL_PDF_FOLDERDES_DETAIL', $moduleName);
				$sqlfid="select max(folderid) from vtiger_attachmentsfolder";
				$fid=$db->query_result($db->pquery($sqlfid,$params),0,'max(folderid)')+1;
				$sqlseq="select max(sequence) from vtiger_attachmentsfolder";
				$sequence=$db->query_result($db->pquery($sqlseq,$params),0,'max(sequence)')+1;
				$sql="insert into vtiger_attachmentsfolder (folderid,foldername,description,createdby,sequence)values ($fid,'".$foldername."','".$folderdesc."',".$current_user->id.",$sequence)";
				$result=$db->pquery($sql,$params);
			}
			else {
				$fid = $db->query_result($folderResult,0,'folderid');
			}

			//save document
			$documents = new Documents();
			$documents->column_fields['parentid']	=	$record;
			$documents->column_fields['assigned_user_id'] = $current_user->id;
			$documents->column_fields['notes_title'] 	= 	$subject;
			$documents->column_fields['filename']	=	$filename;
			$documents->column_fields['filesize']	=	$filesize;
			$documents->column_fields['filetype']	=	$filetype;
			$documents->column_fields['smownerid']	=	$current_user->id;
			$documents->column_fields['filelocationtype'] =	'I';
			$documents->column_fields['description'] = $desc;
			$documents->column_fields['folderid'] = $fid;
			$documents->save("Documents");

			if (!empty ($documents->id)) {
				// create a new entry for attachment
				// crm entity
				$sql1 = "insert into vtiger_crmentity (crmid,smcreatorid,smownerid,setype,description,createdtime,modifiedtime) values(?, ?, ?, ?, ?, ?, ?)";
				$db->pquery($sql1, array($new_unique_id, $current_user->id, $current_user->id, $moduleName." Attachment", $desc, $cur_datetime->format('Y-m-d H:i:s'), $cur_datetime->format('Y-m-d H:i:s')));
				// attachment
				$sql2="insert into vtiger_attachments(attachmentsid, name, description, type, path) values(?, ?, ?, ?, ?)";
				$db->pquery($sql2, array($new_unique_id, $filename, $documents->column_fields['description'], $filetype, $upload_filepath));
				// relationship between attachment and document
				$sql3="insert into vtiger_seattachmentsrel values(?,?)";
				$db->pquery($sql3, array($documents->id,$new_unique_id));
				// relationship between quote and document
				$sql4="insert into vtiger_senotesrel values(?,?)";
				$db->pquery($sql4, array($record,$documents->id));
				// set file active
				$sql5 = "update vtiger_notes set filestatus = 1 where notesid= ?";
				$db->pquery($sql5,array($documents->id));
				// save description to be displayed in the documents detail view
				$sql6 = "update vtiger_notes set notecontent= ? where notesid= ?";
				$db->pquery($sql6,array($desc, $documents->id));
				
				$response->setResult(true);
			}
			else {
				//handle error
				$response->setResult(false);
			}

		}
		else {
			//handle error
			$response->setResult(false);
		}
		$response->emit();
	}

}
?>