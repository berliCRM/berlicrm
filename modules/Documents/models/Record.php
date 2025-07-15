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
				// Include the attachmentsid in the saved file name
				$savedFileName = $fileDetails['attachmentsid'] . "_" . $fileName; 
				// Use only $fileName for the download name
				$downloadFileName = $fileName; 

				$FN = $filePath . $savedFileName;
				if (!file_exists($FN)) {
					throw new Exception('Attachment not present!');
				}
				$size=filesize($FN);
				//Begin writing headers
				header("Cache-Control:");
				header("Cache-Control: public");
				header("Accept-Ranges: bytes");
				header('Content-Disposition: attachment; filename="'.basename($downloadFileName).'"');
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

	function previewFile() {
		$fileDetails = $this->getFileDetails();

		if (!empty($fileDetails)) {
			$filePath = $fileDetails['path'];
			$fileName = $fileDetails['name'];

			$contentType = pathinfo($fileName, PATHINFO_EXTENSION); 

			if ($this->get('filelocationtype') == 'I') {
				$fileName = html_entity_decode($fileName, ENT_QUOTES, vglobal('default_charset'));
				// Include the attachmentsid in the saved file name
				$savedFileName = $fileDetails['attachmentsid'] . "_" . $fileName; 
				// Use only $fileName for the download name
				$downloadFileName = $fileName; 

				$FN = $filePath . $savedFileName;
				if (!file_exists($FN)) {
					throw new Exception('Attachment not present!');
				}
				$size = filesize($FN);
				//Begin writing headers
				header("Cache-Control:");
				header("Cache-Control: public");
				header("Accept-Ranges: bytes");

					switch ($contentType) {
						case 'pdf':
							header('Content-Type: application/pdf');
							header('Content-Disposition: inline; filename="' . basename($downloadFileName) . '"');
							break;
						case 'txt':
						case 'csv':
							header('Content-Type: text/plain; charset=UTF-8');
							header('Content-Disposition: inline; filename="' . basename($downloadFileName) . '"');
							break;
						case 'jpg':
						case 'jpeg':
							header('Content-Type: image/jpeg');
							header('Content-Disposition: inline; filename="' . basename($downloadFileName) . '"');
							break;
						case 'png':
							header('Content-Type: image/png');
							header('Content-Disposition: inline; filename="' . basename($downloadFileName) . '"');
							break;
						default:
							header("HTTP/1.1 204 No Content");
							header("Content-Length: 0");
							break;
					}
					
				header('Content-Description: File Transfer');
				header('Content-Transfer-Encoding: binary');
				header('Accept-Ranges: bytes');
				header('Connection: close');
	
				$fd = fopen($FN, 'rb');
				$start = 0;
				$length = $size;
	
				fseek($fd, $start);
				$bufferSize = 8192;
				$bytesSent = 0;
	
				while (!feof($fd) && $bytesSent < $length) {
					$buffer = fread($fd, min($bufferSize, $length - $bytesSent));
					echo $buffer;
					flush();
				}
				fclose($fd);
				exit;
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
		$downloadCount = (int) $db->query_result($result, 0, 'filedownloadcount') + 1;

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
