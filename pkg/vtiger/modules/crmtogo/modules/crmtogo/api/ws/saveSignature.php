<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified by crm-now GmbH, www.crm-now.com
 ************************************************************************************/

class crmtogo_WS_saveSignature extends crmtogo_WS_Controller {
	
	function process(crmtogo_API_Request $request) {
		return $this->getContent($request);
	}
	
	function getContent(crmtogo_API_Request $request) {
		include_once 'include/Webservices/Create.php';
		include_once 'include/Webservices/Query.php';
		$signature = $request->get('signature');
		$parentid = $request->get('recordid');
		$parentrecordid = vtws_getIdComponents($parentid);
		$parentrecordid = $parentrecordid[1];
		global $adb,$log;

		$response = new crmtogo_API_Response();
		if (isset($signature) && !empty($signature)) {
			$parentmodule = crmtogo_WS_Utils::detectModulenameFromRecordId($parentid);
			$current_user = $this->getActiveUser();
			$hdresult = $adb->pquery("SELECT ticket_no FROM vtiger_troubletickets WHERE ticketid = ?", array($parentrecordid));
			$ticket_no = $adb->query_result($hdresult,0,'ticket_no');
			$upload_filepath = decideFilePath();
			$new_unique_id = $adb->getUniqueID("vtiger_crmentity");
			$image_name = "Signature_".$ticket_no.'.png';
			$file_name = $upload_filepath.$new_unique_id."_".$image_name;
			
			date_default_timezone_set($current_user->time_zone);
			$userid = crmtogo_WS_Utils::getEntityModuleWSId('Users')."x".$current_user->id;
			$model_filename = array(
				'name' => 'firma_'.$ticket_no.'.png',
				'size' => 0,
				'type' => "image/png",
				'content' => $signature
			);
			$module = 'Documents';
			$filetype = "image/png";
			$valuesmap = array(
				'assigned_user_id' => $userid,
				'notes_title' => 'Signatur '.$ticket_no,
				'notecontent' => 'Signatur '.$ticket_no,
				'filename' => $image_name,
				'filetype' => $filetype,
				'filesize' => strlen ($signature),
				'filelocationtype' => 'I',
				'filedownloadcount' => 0,
				'filestatus' => 1,
				'folderid'  => vtws_getWebserviceEntityId('DocumentFolders', '1'),
				'relations' => $parentid
			);
			//Create Document
			$new_document = vtws_create($module, $valuesmap, $current_user);
			$ws_doc_id = $new_document['id'];
			$doc_id = vtws_getIdComponents($ws_doc_id);
			$doc_id = $doc_id[1];

			//Upload file
			$encoded_image = explode(",", $signature)[1];
			$decoded_image = base64_decode($encoded_image);
			file_put_contents($file_name, $decoded_image);
			
			//create and link attachment to document
			//get time
			$cur_datetime = new DateTime(null);
			$dateTimeField = new DateTimeField($cur_datetime->format('Y-m-d H:i:s'));
			$timeFormatedString = $dateTimeField->getDisplayTime();
			$dateFormatedString = $dateTimeField->getDisplayDate();
			$link_url = 'index.php?module=HelpDesk&view=Detail&record='.$parentrecordid ;
			$desc = '<a href='.$link_url.'>&quot;'.vtranslate('LBL_SIGNATUR_SOURCE', 'crmtogo').'&quot;</a>&nbsp;';
			// crm entity
			$sql1 = "insert into vtiger_crmentity (crmid,smcreatorid,smownerid,setype,description,createdtime,modifiedtime) values(?, ?, ?, ?, ?, ?, ?)";
			$adb->pquery($sql1, array($new_unique_id, $current_user->id, $current_user->id, $moduleName." Attachment", $desc, $cur_datetime->format('Y-m-d H:i:s'), $cur_datetime->format('Y-m-d H:i:s')));
			// attachment
			$sql2="insert into vtiger_attachments(attachmentsid, name, description, type, path) values(?, ?, ?, ?, ?)";
			$adb->pquery($sql2, array($new_unique_id, $image_name, $documents->column_fields['description'], $filetype, $upload_filepath));
			// relationship between attachment and document
			$sql3="insert into vtiger_seattachmentsrel values(?,?)";
			$adb->pquery($sql3, array($doc_id,$new_unique_id));
			// relationship between quote and document
			$sql4="insert into vtiger_senotesrel values(?,?)";
			$adb->pquery($sql4, array($parentrecordid,$doc_id));
			// set file active
			$sql5 = "update vtiger_notes set filestatus = 1 where notesid= ?";
			$adb->pquery($sql5,array($doc_id));
			// save description to be displayed in the documents detail view
			$sql6 = "update vtiger_notes set notecontent= ? where notesid= ?";
			$adb->pquery($sql6,array($desc, $doc_id));


			//Update HelpDesk, close ticket
			$ticket_fields = Array (
				'id' => vtws_getWebserviceEntityId('HelpDesk', $parentrecordid),
				'ticketstatus' => 'Closed',
			);
			try {
				vtws_revise($ticket_fields, $current_user);
			}
			catch(Exception $e) {
				$response->setError($e->getCode(), $e->getMessage());
				return $response;
			}
			$response->setResult(array('signpath'=>$file_name));
			return $response;
		}
		else {
				$response->setError('Signature Error', 'No Signature');
				return $response;
		}
	}
}