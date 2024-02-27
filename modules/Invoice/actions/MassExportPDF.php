<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
require_once('include/utils/utils.php');
require_once('modules/Invoice/pdfcreator.php');
require_once('modules/Documents/Documents.php');

class Invoice_MassExportPDF_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$selectedId = $request->get('selectedId');

		if(!Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $recordId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}
	}

	public function process(Vtiger_Request $request) {
		global $current_user;
		$db = PearDatabase::getInstance();
		$moduleName = $request->getModule();
		$selectedId = $request->get('selectedId');
		$savemode = $request->get('savemode');
		$pdffiles = $request->get('pdffiles');

		$response = new Vtiger_Response();
		if(isset($selectedId) AND $savemode == 'file') {
			// print all pdf records  to file
			$filepath = decideFilePath();
			$filename = createpdffile ($selectedId,'save',$filepath,$selectedId);
			$full_filesname = $selectedId.'_'.$filename;
			if (!empty($filename)) {
					
				$response->setResult(array('success' => true, 'filename' => $full_filesname));
			}
			else {
				$response->setResult(array('success' => false, 'error.message' => 'PDF ERROR FOR ID: '.$selectedId));
			}
		}
		else if ($savemode == 'masspdf') {
				// Create document record
				// check whether folder exists
				$foldername = getTranslatedString('LBL_PDF_FOLDERNAME', 'Invoice');
				$dbQuery="select * from vtiger_attachmentsfolder";
				$result1=$db->pquery($dbQuery,array());
				$flag=0;
				for($i=0;$i<$db->num_rows($result1);$i++) {
					$dbfldrname=$db->query_result($result1,$i,'foldername');
					if($dbfldrname == $foldername) {
						$flag = 1;
						$fid = $db->query_result($result1,$i,'folderid');
					}
				}			
				if ($flag==0) {
					// create Folder is not exists
					$folderdesc = getTranslatedString('LBL_PDF_FOLDERDES', 'Invoice');
					$sqlfid="select max(folderid) from vtiger_attachmentsfolder";
					$fid=$db->query_result($db->pquery($sqlfid,array()),0,'max(folderid)')+1;
					$sqlseq="select max(sequence) from vtiger_attachmentsfolder";
					$sequence=$db->query_result($db->pquery($sqlseq,array()),0,'max(sequence)')+1;
					$sql="insert into vtiger_attachmentsfolder (folderid,foldername,description,createdby,sequence)values ('".$fid."','".$foldername."','".$folderdesc."',".$current_user->id.",'".$sequence."')";
					$result=$db->pquery($sql,array());
				}
				
				$dateTime = new DateTimeField(date("Y-m-d H:i:s"));
				$dateTimeInOwnerFormat = $dateTime->getDisplayDateTimeValue($current_user);
				$doc_name = 'PDF EXPORT ('.count($pdffiles).') '.$dateTimeInOwnerFormat;
				$file_name = 'PDF EXPORT '.$dateTimeInOwnerFormat.'.pdf';
				$file_name =  str_replace (' ','_',$file_name);
				$file_name =  str_replace (':','-',$file_name);
				$document = new Documents();
				$document->column_fields['notes_title']		 = $doc_name;
				$document->column_fields['filename']		 = $file_name;
				$document->column_fields['filesize']		 = 0;
				$document->column_fields['filestatus']		 = 1;
				$document->column_fields['filetype']		 = 'application/pdf';
				$document->column_fields['filelocationtype'] = 'I';
				$document->column_fields['folderid']         = $fid;
				$document->column_fields['assigned_user_id'] = $current_user->id;
				$document->save('Documents');
				
				//merge PDFs
				$attachid = $db->getUniqueID("vtiger_crmentity");
				$datadir = decideFilePath();
				$outputName = $datadir.$attachid."_".$file_name;

				$cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=$outputName ";
				//Add each pdf file to the end of the command
				foreach($pdffiles as $file) {
					$cmd .= $datadir.$file." ";
				}
				$result = shell_exec($cmd);
				//delete no longer needed files
				foreach($pdffiles as $file) {
					unlink($datadir.$file);
				}
				$filesize = filesize($outputName);
				//set files size in document
				$sql0 = "UPDATE `vtiger_notes` SET `filesize` = ? WHERE `notesid` =?";
				$params1 = array($filesize, $document->id);
				$db->pquery($sql0, $params1);
				//make crmentity entry for this file
				$date_var = date("Y-m-d H:i:s");
				$created_date_var = $db->formatDate($date_var, true);
				$modified_date_var = $db->formatDate($date_var, true);
				//This is  for creating a download link
				$sql1 = "insert into vtiger_crmentity (crmid,smcreatorid,smownerid,setype,description,createdtime,modifiedtime) values(?, ?, ?, ?, ?, ?, ?)";
				$params1 = array($attachid, $current_user->id, $current_user->id,"Documents Attachment", 'MassPDF Invoice', $db->formatDate($date_var, true), $db->formatDate($date_var, true));
				$db->pquery($sql1, $params1);
				$sql2 = "insert into vtiger_attachments(attachmentsid, name, description, type, path) values(?, ?, ?, ?, ?)";
				$params2 = array($attachid,$file_name, 'MassPDF Invoice','application/pdf', $datadir);
				$result = $db->pquery($sql2, $params2);

				// Link file attached to document
				$db->pquery("INSERT INTO vtiger_seattachmentsrel(crmid, attachmentsid) VALUES(?,?)",Array($document->id, $attachid));

				$response->setResult(array('success' => true, 'fileid' =>$attachid, 'record' => $document->id, 'filename'=> $file_name ));
		}
		$response->emit();
	}

}