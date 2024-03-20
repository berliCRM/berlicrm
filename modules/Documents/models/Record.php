<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Documents_Record_Model extends Vtiger_Record_Model {

	/**
	 * Function to get the Display Name for the record
	 * @return <String> - Entity Display Name for the record
	 */
	function getDisplayName() {
		return Vtiger_Util_Helper::getLabel($this->getId());
	}

	function getDownloadFileURL() {
		if ($this->get('filelocationtype') == 'I') {
			$fileDetails = $this->getFileDetails();
			return 'index.php?module='. $this->getModuleName() .'&action=DownloadFile&record='. $this->getId() .'&fileid='. $fileDetails['attachmentsid'];
		} else {
			return $this->get('filename');
		}
	}

	function checkFileIntegrityURL() {
		return "javascript:Documents_Detail_Js.checkFileIntegrity('index.php?module=".$this->getModuleName()."&action=CheckFileIntegrity&record=".$this->getId()."')";
	}

	function checkFileIntegrity() {
		$recordId = $this->get('id');
		$downloadType = $this->get('filelocationtype');
		$returnValue = false;

		if ($downloadType == 'I') {
			$fileDetails = $this->getFileDetails();
			if (!empty ($fileDetails)) {
				$filePath = $fileDetails['path'];

				$savedFile = $fileDetails['attachmentsid']."_".$this->get('filename');

				if(fopen($filePath.$savedFile, "r")) {
					$returnValue = true;
				}
			}
		}
		return $returnValue;
	}

	function getFileDetails() {
		$db = PearDatabase::getInstance();
		$fileDetails = array();

		$result = $db->pquery("SELECT * FROM vtiger_attachments
							INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
							WHERE crmid = ?", array($this->get('id')));

		if($db->num_rows($result)) {
			$fileDetails = $db->query_result_rowdata($result);
		}
		return $fileDetails;
	}

	function downloadFile() {
		$fileDetails = $this->getFileDetails();
		$fileContent = false;

		if (!empty ($fileDetails)) {
			$filePath = $fileDetails['path'];
			$fileName = $fileDetails['name'];

			if ($this->get('filelocationtype') == 'I') {
				$fileName = html_entity_decode($fileName, ENT_QUOTES, vglobal('default_charset'));
				$savedFile = $fileDetails['attachmentsid']."_".$fileName;

				$FN = $filePath.$savedFile;
				if (!file_exists($FN)) {
					throw new Exception('Attachment not present!');
				}
				$size=filesize($FN);
				//Begin writing headers
				header("Cache-Control:");
				header("Cache-Control: public");
				header("Accept-Ranges: bytes");
				header('Content-Disposition: attachment; filename="'.basename($FN).'"');
				header("Content-type: application/octet-stream");
				header("Connection: close");

				//check if http_range is sent by browser (or download manager)
				if(isset($_SERVER['HTTP_RANGE'])) {
					list($a, $range)=explode("=",$_SERVER['HTTP_RANGE']);
					//if yes, download missing part
					str_replace($range, "-", $range);
					$size2=$size-1;
					$new_length=$size2-$range;
					header("HTTP/1.1 206 Partial Content");
					header("Content-Length: $new_length");
					header("Content-Range: bytes $range$size2/$size");
				} 
				else {
					$range=0;
					$size2=$size-1;
					header("Content-Range: bytes 0-$size2/$size");
					header("Content-Length: ".$size);
				}

				$fd=fopen($FN,"rb");
				fseek($fd,$range);

				$bytes=0;
				while(!feof($fd)) {
					$fileContent=fread($fd, 4096);
					$bytes+=strlen($fileContent);
					print $fileContent;
					flush();
				}
				fclose($fd);
			}
		}
	}

	function updateFileStatus($status) {
		$db = PearDatabase::getInstance();

                $db->pquery("UPDATE vtiger_notes SET filestatus = ? WHERE notesid= ?", array($status,$this->get('id')));
	}

	function updateDownloadCount() {
		$db = PearDatabase::getInstance();
		$notesId = $this->get('id');

		$result = $db->pquery("SELECT filedownloadcount FROM vtiger_notes WHERE notesid = ?", array($notesId));
		$downloadCount = $db->query_result($result, 0, 'filedownloadcount') + 1;

		$db->pquery("UPDATE vtiger_notes SET filedownloadcount = ? WHERE notesid = ?", array($downloadCount, $notesId));
	}

	function getDownloadCountUpdateUrl() {
		return "index.php?module=Documents&action=UpdateDownloadCount&record=".$this->getId();
	}
	
	function get($key) {
		$value = parent::get($key);
		if ($key === 'notecontent') {
			return decode_html($value);
		}
		return $value;
	}

}
