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

	protected $fileDetails = array();
	protected $fileDetailsLoaded = false;
	protected $internalFileAvailable = null;

	/**
	 * Function to get the Display Name for the record
	 * @return <String> - Entity Display Name for the record
	 */
	function getDisplayName() {
		return Vtiger_Util_Helper::getLabel($this->getId());
	}

	function getDownloadFileURL() {
		if (!$this->isDownloadAvailable()) {
			return '';
		}

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

	/**
	 * Return the complete path of the internally stored attachment.
	 *
	 * The attachment name can contain HTML entities in the database, while the
	 * stored file name contains the decoded value.
	 *
	 * @return string|false
	 */
	function getInternalFilePath() {
		$fileDetails = $this->getFileDetails();
		if (empty($fileDetails)
				|| empty($fileDetails['path'])
				|| empty($fileDetails['attachmentsid'])
				|| empty($fileDetails['name'])) {
			return false;
		}

		$fileName = html_entity_decode($fileDetails['name'], ENT_QUOTES, vglobal('default_charset'));
		$filePath = rtrim($fileDetails['path'], '/\\');
		return $filePath . DIRECTORY_SEPARATOR . $fileDetails['attachmentsid'] . '_' . $fileName;
	}

	/**
	 * Check the actual storage file, independently of the persisted file status.
	 * The result is cached for the lifetime of this record model/request.
	 *
	 * @return bool
	 */
	function isInternalFileAvailable() {
		if ($this->internalFileAvailable !== null) {
			return $this->internalFileAvailable;
		}

		$filePath = $this->getInternalFilePath();
		$this->internalFileAvailable = $filePath !== false && is_file($filePath) && is_readable($filePath);
		return $this->internalFileAvailable;
	}

	/**
	 * Check whether the document may currently be offered as a download.
	 *
	 * @return bool
	 */
	function isDownloadAvailable() {
		if (!$this->get('filestatus') || !$this->get('filename')) {
			return false;
		}

		$fileLocationType = $this->get('filelocationtype');
		if ($fileLocationType === 'I') {
			return $this->isInternalFileAvailable();
		}

		return $fileLocationType === 'E';
	}

	function checkFileIntegrity() {
		return $this->get('filelocationtype') === 'I' && $this->isInternalFileAvailable();
	}

	function getFileDetails() {
		if ($this->fileDetailsLoaded) {
			return $this->fileDetails;
		}

		$db = PearDatabase::getInstance();
		$this->fileDetailsLoaded = true;

		$result = $db->pquery("SELECT * FROM vtiger_attachments
							INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
							WHERE crmid = ?", array($this->get('id')));

		if($db->num_rows($result)) {
			$this->fileDetails = $db->query_result_rowdata($result);
		}
		return $this->fileDetails;
	}

	function downloadFile() {
		$fileDetails = $this->getFileDetails();
		if (empty($fileDetails)
				|| $this->get('filelocationtype') !== 'I'
				|| !$this->isDownloadAvailable()) {
			return false;
		}

		$fileName = html_entity_decode($fileDetails['name'], ENT_QUOTES, vglobal('default_charset'));
		$filePath = $this->getInternalFilePath();
		$size = @filesize($filePath);
		$fd = @fopen($filePath, 'rb');
		if ($size === false || $fd === false) {
			if (is_resource($fd)) {
				fclose($fd);
			}
			$this->internalFileAvailable = false;
			return false;
		}

		//Begin writing headers
		header("Cache-Control:");
		header("Cache-Control: public");
		header("Accept-Ranges: bytes");
		header('Content-Disposition: attachment; filename="'.basename($fileName).'"');
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

		fseek($fd,$range);

		$bytes=0;
		while(!feof($fd)) {
			$fileContent = fread($fd, 4096);
			$bytes+=strlen($fileContent);
			print $fileContent;
			flush();
		}
		fclose($fd);
		return true;
	}

	function previewFile() {
		$fileDetails = $this->getFileDetails();
		if (empty($fileDetails)
				|| $this->get('filelocationtype') !== 'I'
				|| !$this->isDownloadAvailable()) {
			return false;
		}

		$fileName = html_entity_decode($fileDetails['name'], ENT_QUOTES, vglobal('default_charset'));
		$contentType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		$filePath = $this->getInternalFilePath();
		$size = @filesize($filePath);
		$fd = @fopen($filePath, 'rb');
		if ($size === false || $fd === false) {
			if (is_resource($fd)) {
				fclose($fd);
			}
			$this->internalFileAvailable = false;
			return false;
		}

		//Begin writing headers
		header("Cache-Control:");
		header("Cache-Control: public");
		header("Accept-Ranges: bytes");

		switch ($contentType) {
			case 'pdf':
				header('Content-Type: application/pdf');
				header('Content-Disposition: inline; filename="' . basename($fileName) . '"');
				break;
			case 'txt':
			case 'csv':
				header('Content-Type: text/plain; charset=UTF-8');
				header('Content-Disposition: inline; filename="' . basename($fileName) . '"');
				break;
			case 'jpg':
			case 'jpeg':
				header('Content-Type: image/jpeg');
				header('Content-Disposition: inline; filename="' . basename($fileName) . '"');
				break;
			case 'png':
				header('Content-Type: image/png');
				header('Content-Disposition: inline; filename="' . basename($fileName) . '"');
				break;
			default:
				header("HTTP/1.1 204 No Content");
				header("Content-Length: 0");
				fclose($fd);
				return true;
		}

		header('Content-Description: File Transfer');
		header('Content-Transfer-Encoding: binary');
		header('Accept-Ranges: bytes');
		header('Connection: close');

		$start = 0;
		$length = $size;

		fseek($fd, $start);
		$bufferSize = 8192;
		$bytesSent = 0;

		while (!feof($fd) && $bytesSent < $length) {
			$buffer = fread($fd, min($bufferSize, $length - $bytesSent));
			echo $buffer;
			$bytesSent += strlen($buffer);
			flush();
		}
		fclose($fd);
		return true;
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
