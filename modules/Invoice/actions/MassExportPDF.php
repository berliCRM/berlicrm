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

/**
 * Generates invoice PDFs and merges only files registered in the current session.
 */
class Invoice_MassExportPDF_Action extends Vtiger_Action_Controller {
	/** @var bool Set to true temporarily to record failed PDF merge diagnostics. */
	const DIAGNOSTICS_ENABLED = false;

	/**
	 * Requires POST, a valid CSRF token, and the standard request access checks.
	 *
	 * @param Vtiger_Request $request Incoming export request.
	 * @return bool True when request validation succeeds.
	 * @throws Exception When request validation fails.
	 */
	public function validateRequest(Vtiger_Request $request): bool {
		return $request->validateWriteAccess();
	}

	/**
	 * Checks module access and record access for individual PDF generation.
	 * Merge inputs receive additional record checks in resolvePdfFiles().
	 *
	 * @param Vtiger_Request $request Incoming export request.
	 * @return void
	 * @throws AppException When the invoice ID is invalid or access is denied.
	 */
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = null;
		if ($request->get('savemode') === 'file') {
			$selectedId = $request->get('selectedId');
			if (!is_scalar($selectedId) || !preg_match('/\A[1-9][0-9]*\z/', (string) $selectedId)) {
				throw new AppException('Invalid invoice ID');
			}
			$recordId = (int) $selectedId;
		}

		if(!Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $recordId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}
	}

	/**
	 * Generates a session-registered PDF or merges registered PDFs into a document.
	 * Source PDFs are deleted only after a successful merge and document save.
	 * Set DIAGNOSTICS_ENABLED to true in this file to log merge failures.
	 *
	 * @param Vtiger_Request $request Request containing savemode and export inputs.
	 * @return void Emits the export result as a Vtiger response.
	 * @throws AppException When PDF paths, access checks, or the merge fail.
	 */
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
			$filepath = __DIR__.'/../../../'.$filepath;
			$filename = createpdffile ($selectedId,'save',$filepath,$selectedId);
			$full_filesname = $selectedId.'_'.$filename;
			if (!empty($filename)) {
				$pdfPath = realpath($filepath.$full_filesname);
				if ($pdfPath === false || is_link($filepath.$full_filesname)
					|| dirname($pdfPath) !== realpath($filepath) || !is_file($pdfPath)) {
					throw new AppException('Invalid generated PDF');
				}
				$_SESSION['Invoice_MassExportPDF'][$full_filesname] = array(
					'path' => $pdfPath, 'user' => $current_user->id, 'record' => (int) $selectedId
				);
				$response->setResult(array('success' => true, 'filename' => $full_filesname));
			}
			else {
				$response->setResult(array('success' => false, 'error.message' => 'PDF ERROR FOR ID: '.$selectedId));
			}
		}
		else if ($savemode == 'masspdf') {
				// Validate every input before creating records or running Ghostscript.
				$inputPaths = $this->resolvePdfFiles($pdffiles, $current_user->id, $moduleName);
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
				
				//merge PDFs
				$attachid = $db->getUniqueID("vtiger_crmentity");
				$datadir = decideFilePath();
				$outputName = $datadir.$attachid."_".$file_name;

				$outputDirectory = realpath($datadir);
				if ($outputDirectory === false) {
					throw new AppException('Invalid PDF output directory');
				}
				$outputPath = $outputDirectory.DIRECTORY_SEPARATOR.basename($outputName);
				$this->mergePdfFiles($inputPaths, $outputPath);
				$filesize = filesize($outputPath);
				$document->save('Documents');
				//delete no longer needed files
				foreach($inputPaths as $file => $path) {
					unlink($path);
					unset($_SESSION['Invoice_MassExportPDF'][$file]);
				}
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

	/**
	 * Resolves unique PDF filenames generated by this session and user.
	 * Rejects traversal, symlinks, missing files, and inaccessible invoice records.
	 *
	 * @param mixed $files Untrusted list of session-registered PDF filenames.
	 * @param int|string $userId Current authenticated user ID.
	 * @param string $moduleName Module used for invoice permission checks.
	 * @return array<string, string> Absolute PDF paths indexed by filename.
	 * @throws AppException When the list or any file fails validation.
	 */
	protected function resolvePdfFiles($files, $userId, $moduleName) {
		if (!is_array($files) || count($files) === 0 || count($files) > 1000) {
			throw new AppException('Invalid PDF file list');
		}
		$paths = array();
		$registered = $_SESSION['Invoice_MassExportPDF'] ?? array();
		foreach ($files as $file) {
			if (!is_string($file) || strpos($file, "\0") !== false
				|| strpos($file, '/') !== false || strpos($file, '\\') !== false
				|| strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'pdf'
				|| !isset($registered[$file]) || isset($paths[$file])) {
				throw new AppException('Invalid PDF file');
			}
			$entry = $registered[$file];
			$path = $entry['path'];
			if ((string) $entry['user'] !== (string) $userId || is_link($path)
				|| realpath($path) !== $path || !is_file($path) || !is_readable($path)
				|| !Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $entry['record'])) {
				throw new AppException('PDF file is unavailable or access is denied');
			}
			$paths[$file] = $path;
		}
		return $paths;
	}

	/**
	 * Runs Ghostscript with safe arguments and verifies that it produced a nonempty PDF.
	 * PHP before 7.4 uses a command string with every argument shell-escaped.
	 * Removes partial output on failure and leaves all source files intact.
	 * Writes failure diagnostics to test/MassExportPDF-diagnostics.php when enabled.
	 *
	 * @param array<string, string> $inputPaths Validated absolute input PDF paths.
	 * @param string $outputPath Absolute path to a new output PDF.
	 * @return void
	 * @throws AppException When output exists or Ghostscript fails to create it.
	 */
	protected function mergePdfFiles(array $inputPaths, $outputPath) {
		if (!function_exists('proc_open')) {
			$this->writeMergeDiagnostic('proc_open is disabled or unavailable');
			throw new AppException('PDF merge unavailable: process execution is disabled');
		}
		if (file_exists($outputPath) || is_link($outputPath)) {
			$this->writeMergeDiagnostic('PDF output already exists', array('output' => $outputPath));
			throw new AppException('PDF output already exists');
		}
		// An argument array bypasses the shell. Absolute paths cannot become options.
		$command = array('gs', '-q', '-dSAFER', '-dNOPAUSE', '-dBATCH',
			'-sDEVICE=pdfwrite', '-sOutputFile='.$outputPath);
		$command = array_merge($command, array_values($inputPaths));
		$processCommand = $this->buildProcessCommand($command);
		$nullDevice = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
		// A file captures both streams without blocking on a full pipe buffer.
		$diagnostics = static::DIAGNOSTICS_ENABLED ? tmpfile() : null;
		if (static::DIAGNOSTICS_ENABLED && $diagnostics === false) {
			$this->writeMergeDiagnostic('Cannot create temporary diagnostic file');
			throw new AppException('PDF merge unavailable: cannot create diagnostic file');
		}
		try {
			$process = proc_open($processCommand, array(
				0 => array('file', $nullDevice, 'r'),
				1 => $diagnostics !== null ? $diagnostics : array('file', $nullDevice, 'w'),
				2 => $diagnostics !== null ? $diagnostics : array('file', $nullDevice, 'w'),
			), $pipes, null, null, array('bypass_shell' => true));
			$status = is_resource($process) ? proc_close($process) : -1;
			$details = '';
			if (is_resource($diagnostics)) {
				rewind($diagnostics);
				$details = stream_get_contents($diagnostics, 8192);
			}
		} catch (\Throwable $exception) {
			$status = -1;
			$details = $exception->getMessage();
		} finally {
			if (is_resource($diagnostics)) {
				fclose($diagnostics);
			}
		}
		clearstatcache(true, $outputPath);
		if ($status !== 0 || !is_file($outputPath) || filesize($outputPath) === 0) {
			$this->writeMergeDiagnostic('Ghostscript merge failed', array(
				'exit_status' => $status,
				'command_arguments' => $command,
				'command_format' => is_array($processCommand) ? 'argument_array' : 'escaped_string',
				'output_created' => is_file($outputPath),
				'output_directory_writable' => is_writable(dirname($outputPath)),
				'ghostscript_output' => trim($details),
			));
			if (is_file($outputPath)) {
				unlink($outputPath);
			}
			$diagnosticHint = static::DIAGNOSTICS_ENABLED
				? ' See test/MassExportPDF-diagnostics.php.'
				: '';
			throw new AppException('PDF merge failed (Ghostscript exit status '.$status.').'.$diagnosticHint);
		}
	}

	/**
	 * Builds a command accepted by the target PHP version.
	 * PHP 7.4 and later execute an argument array directly. Earlier versions require
	 * a string; escaping each argument keeps shell syntax inside literal arguments.
	 *
	 * @param string[] $arguments Executable followed by individual arguments.
	 * @param int $phpVersionId Runtime version, overridable for regression checks.
	 * @return array|string Argument array or securely escaped command string.
	 */
	protected function buildProcessCommand(array $arguments, $phpVersionId = PHP_VERSION_ID) {
		if ($phpVersionId >= 70400) {
			return $arguments;
		}
		return implode(' ', array_map('escapeshellarg', $arguments));
	}

	/**
	 * Returns the diagnostic file within the CRM test directory.
	 *
	 * @return string Absolute diagnostic file path.
	 */
	protected function getMergeDiagnosticPath() {
		return dirname(__DIR__, 3).'/test/MassExportPDF-diagnostics.php';
	}

	/**
	 * Appends a timestamped failure record only when diagnostics are enabled.
	 * The PHP exit guard protects the file when served by the CRM PHP handler.
	 * Falls back to the PHP error log if the diagnostic file is not writable.
	 *
	 * @param string $message Failure description.
	 * @param array<string, mixed> $context Ghostscript arguments and diagnostics.
	 * @return void
	 */
	protected function writeMergeDiagnostic($message, array $context = array()) {
		if (!static::DIAGNOSTICS_ENABLED) {
			return;
		}
		$record = array(
			'time_utc' => gmdate('c'), 'message' => $message,
			'php_version' => PHP_VERSION, 'php_sapi' => PHP_SAPI,
			'working_directory' => getcwd(), 'context' => $context,
		);
		$entry = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
		$path = $this->getMergeDiagnosticPath();
		// Include the guard in every append so even the first concurrent writer is safe.
		if (is_link($path) || @file_put_contents($path, "<?php exit; ?>\n".$entry."\n", FILE_APPEND | LOCK_EX) === false) {
			error_log('Invoice MassExportPDF: cannot write diagnostic file; '.$entry);
			return;
		}
		@chmod($path, 0600);
	}
}
?>
