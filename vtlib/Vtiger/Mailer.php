<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
$includePath = 'vendor/autoload.php';
if (file_exists($includePath)) {
	require_once($includePath);
} else {
	require_once("modules/Emails/PHPMailer/src/PHPMailer.php");
	require_once("modules/Emails/PHPMailer/src/SMTP.php");
	require_once("modules/Emails/PHPMailer/src/Exception.php");
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;
use PHPMailer\PHPMailer\SMTP;
use TheNetworg\OAuth2\Client\Provider\Azure;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
include_once('include/utils/CommonUtils.php');
include_once('config.inc.php');
include_once('include/database/PearDatabase.php');
include_once('vtlib/Vtiger/Utils.php');
include_once('vtlib/Vtiger/Event.php');

/**
 * Provides API to work with PHPMailer & Email Templates
 * @package vtlib
 */
class Vtiger_Mailer extends PHPMailer {

	var $_serverConfigured = false;
	
	// save body that will be sent by queue here
	var $unalteredBody = '';

	/**
	 * Constructor
	 */
	function __construct() {
		$this->initialize();
	}

	/**
	 * Get the unique id for insertion
	 * @access private
	 */
	function __getUniqueId() {
		global $adb;
		return $adb->getUniqueID('vtiger_mailer_queue');
	}

	/**
	 * Initialize this instance
	 * @access private
	 */
	function initialize() {
		$this->isSMTP();

		global $adb;
		$result = $adb->pquery("SELECT * FROM vtiger_systems WHERE server_type=?", Array('email'));
		// check for save in Settings
		if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'Save' && isset($_REQUEST['server'])) {
			$this->Host = $_REQUEST['server'];
			$this->Username = $_REQUEST['server_username'];
			$this->Password = $_REQUEST['server_password'];
			$this->SMTPAuth = $_REQUEST['smtp_auth'];
			$fromValue = $_REQUEST['from_email_field'];
		} elseif($adb->num_rows($result)) {
			$this->Host = $adb->query_result($result, 0, 'server');
			$this->Username = decode_html($adb->query_result($result, 0, 'server_username'));
			$this->Password = decode_html($adb->query_result($result, 0, 'server_password'));
			$this->SMTPAuth = $adb->query_result($result, 0, 'smtp_auth');
			$fromValue = $adb->query_result($result, 0, 'from_email_field');
		}
        
		if (!empty($this->Host)) {
			// To support TLS
			$hostinfo = explode("://", $this->Host);
			$smtpsecure = $hostinfo[0];
			if($smtpsecure == 'tls'){
				$this->SMTPSecure = $smtpsecure;
				$this->Host = $hostinfo[1];
			}
			// End
			
			if(empty($this->SMTPAuth)) $this->SMTPAuth = false;

			$this->ConfigSenderInfo($fromValue);

			$this->_serverConfigured = true;
			// oAuth
			require_once 'modules/Settings/Vtiger/models/ConfigoAuth.php';
			$settingsoAuth = Settings_Vtiger_oAuth::getInstance();
			$oAuthDetails = $settingsoAuth->getData();
			// rewrite with $_REQUEST data
			if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'Save' && isset($_REQUEST['server'])) {
				$request = new Vtiger_Request($_REQUEST);
				$settingsoAuth->save($request);
				$oAuthDetails = $settingsoAuth->getData();
			}
			if ($oAuthDetails['provider'] == 'AZURE') {
				$constants = $settingsoAuth->getClassConstants();
				if (isset($constants['CONFIG_KEY'])) {
					unset($constants['CONFIG_KEY']);
				}
				if (isset($constants['PARAM_ENABLED'])) {
					unset($constants['PARAM_ENABLED']);
				}
				$present = true;
				foreach ($constants AS $name => $value) {
					if (empty($oAuthDetails[$value])) {
						$present = false;
						break;
					}
				}
				if ($present) {
					$this->Host = 'smtp.office365.com';
					$this->Port = 587;
					$this->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

					$this->SMTPAuth = true;
					$this->AuthType = 'XOAUTH2';
					
					// OAuth2 provider (Azure / Entra ID)
					// PHPMailer will use the refresh token to fetch/refresh access tokens automatically
					$scopes = ['offline_access',
							   'https://outlook.office.com/SMTP.Send'
							  ];
					$provider = new Azure([
						'clientId'               => $oAuthDetails['client_id'],
						'clientSecret'           => $oAuthDetails['client_secret'],
						'tenant'                 => $oAuthDetails['tenant_id'],
						'defaultEndPointVersion' => Azure::ENDPOINT_VERSION_2_0,
						'scopes'                 => $scopes
					]);

					$this->setOAuth(new OAuth([
						'provider'      => $provider,
						'clientId'      => $oAuthDetails['client_id'],
						'clientSecret'  => $oAuthDetails['client_secret'],
						'refreshToken'  => $oAuthDetails['refresh_token'],
						'userName'      => $oAuthDetails['user_name'],
					]));
				}
			}
		}
	}

	/**
	 * Reinitialize this instance for use
	 * @access private
	 */
	function reinitialize() {
		$this->ClearAllRecipients();
		$this->ClearReplyTos();
		$this->msgHTML('');
		$this->Subject ='';
		$this->ClearAttachments();
	}

	/**
	 * Initialize this instance using mail template
	 * @access private
	 */
	function initFromTemplate($emailtemplate) {
		global $adb;
		$result = $adb->pquery("SELECT * from vtiger_emailtemplates WHERE templatename=? AND foldername=?",
			Array($emailtemplate, 'Public'));
		if($adb->num_rows($result)) {
			$this->IsHTML(true);
			$usesubject = $adb->query_result($result, 0, 'subject');
			$usebody = $adb->query_result($result, 0, 'body');

			$this->Subject = $usesubject;
			$this->msgHTML($usebody);
			return true;
		}
		return false;
	}
	/**
	*Adding signature to mail
	*/
	function addSignature($userId) {
		global $adb;
		$sign = nl2br($adb->query_result($adb->pquery("select signature from vtiger_users where id=?", array($userId)),0,"signature"));
		$this->Signature = $sign;
	}


	/**
	 * Configure sender information
	 */
	function ConfigSenderInfo($fromemail, $fromname='', $replyto='') {
		if(empty($fromname)) $fromname = $fromemail;

		$this->From = $fromemail;
		//fix for (http://trac.vtiger.com/cgi-bin/trac.cgi/ticket/8001)
                $this->FromName = decode_html($fromname); 
		$this->AddReplyTo($replyto);
	}

	/**
	 * Overriding default send
	 */
	function Send($sync=false, $linktoid=false, $ignoreConfigCheck = false) {
		if(!$ignoreConfigCheck && !$this->_serverConfigured) return;
		// oAuth
		// require_once 'modules/Settings/Vtiger/models/ConfigoAuth.php';
		// $settingsoAuth = Settings_Vtiger_oAuth::getInstance();
		// $oAuthDetails = $settingsoAuth->getData();
		// if ($oAuthDetails['provider'] == 'AZURE' && !empty($oAuthDetails['user_name'])) {
			// // overwrite FROM for now
			// $this->From = $oAuthDetails['user_name'];
		// }

		if($sync) return parent::Send();

		$this->__AddToQueue($linktoid);
		return true;
	}

	/**
	 * Send mail using the email template
	 * @param String Recipient email
	 * @param String Recipient name
	 * @param String vtiger CRM Email template name to use
	 */
	function SendTo($toemail, $toname='', $emailtemplate=false, $linktoid=false, $sync=false) {
		if(empty($toname)) $toname = $toemail;
		$this->AddAddress($toemail, $toname);
		if($emailtemplate) $this->initFromTemplate($emailtemplate);
		return $this->Send($sync, $linktoid);
	}

	/** Mail Queue **/
	// Check if this instance is initialized.
	var $_queueinitialized = false;
	function __initializeQueue() {
		if(!$this->_queueinitialized) {
			if(!Vtiger_Utils::CheckTable('vtiger_mailer_queue')) {
				Vtiger_Utils::CreateTable('vtiger_mailer_queue',
					'(id INT NOT NULL PRIMARY KEY,
					fromname VARCHAR(100), fromemail VARCHAR(100),
					mailer VARCHAR(10), content_type VARCHAR(15), subject VARCHAR(999), body TEXT, relcrmid INT,
					failed INT(1) NOT NULL DEFAULT 0, failreason VARCHAR(255))',
					true);
			}
			if(!Vtiger_Utils::CheckTable('vtiger_mailer_queueinfo')) {
				Vtiger_Utils::CreateTable('vtiger_mailer_queueinfo',
					'(id INTEGER, name VARCHAR(100), email VARCHAR(100), type VARCHAR(7))',
					true);
			}
			if(!Vtiger_Utils::CheckTable('vtiger_mailer_queueattachments')) {
				Vtiger_Utils::CreateTable('vtiger_mailer_queueattachments',
					'(id INTEGER, path TEXT, name VARCHAR(100), encoding VARCHAR(50), type VARCHAR(100))',
					true);
			}
			if(!Vtiger_Utils::CheckTable('berlicrm_mailtracker')) {
				Vtiger_Utils::CreateTable('`berlicrm_mailtracker`',
					'(
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`subject` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
					`receiver` text COLLATE utf8_unicode_ci NOT NULL,
					`send_date` datetime NOT NULL,
					`send_user` int(11) NOT NULL,
					`crmid` int(11) DEFAULT NULL,
					`smtp_answer` text COLLATE utf8_unicode_ci NOT NULL,
					`messageid` text COLLATE utf8_unicode_ci,
					PRIMARY KEY (`id`)
					)',
					true);
			}
			$this->_queueinitialized = true;
		}
		return true;
	}

	/**
	 * Add this mail to queue
	 */
	function __AddToQueue($linktoid) {
		if($this->__initializeQueue()) {
			global $adb;
			$uniqueid = self::__getUniqueId();
			$adb->pquery('INSERT INTO vtiger_mailer_queue(id,fromname,fromemail,content_type,subject,body,mailer,relcrmid) VALUES(?,?,?,?,?,?,?,?)',
				Array($uniqueid, $this->FromName, $this->From, $this->ContentType, $this->Subject, $this->unalteredBody, $this->Mailer, $linktoid));
			$queueid = $uniqueid; //$adb->database->Insert_ID();
			foreach($this->to as $toinfo) {
				if(empty($toinfo[0])) continue;
				$adb->pquery('INSERT INTO vtiger_mailer_queueinfo(id, name, email, type) VALUES(?,?,?,?)',
					Array($queueid, $toinfo[1], $toinfo[0], 'TO'));
			}
			foreach($this->cc as $ccinfo) {
				if(empty($ccinfo[0])) continue;
				$adb->pquery('INSERT INTO vtiger_mailer_queueinfo(id, name, email, type) VALUES(?,?,?,?)',
					Array($queueid, $ccinfo[1], $ccinfo[0], 'CC'));
			}
			foreach($this->bcc as $bccinfo) {
				if(empty($bccinfo[0])) continue;
				$adb->pquery('INSERT INTO vtiger_mailer_queueinfo(id, name, email, type) VALUES(?,?,?,?)',
					Array($queueid, $bccinfo[1], $bccinfo[0], 'BCC'));
			}
			foreach($this->ReplyTo as $rtoinfo) {
				if(empty($rtoinfo[0])) continue;
				$adb->pquery('INSERT INTO vtiger_mailer_queueinfo(id, name, email, type) VALUES(?,?,?,?)',
					Array($queueid, $rtoinfo[1], $rtoinfo[0], 'RPLYTO'));
			}
			foreach($this->attachment as $attachmentinfo) {
				if(empty($attachmentinfo[0])) continue;
				$adb->pquery('INSERT INTO vtiger_mailer_queueattachments(id, path, name, encoding, type) VALUES(?,?,?,?,?)',
					Array($queueid, $attachmentinfo[0], $attachmentinfo[2], $attachmentinfo[3], $attachmentinfo[4]));
			}
		}
	}

    /**
     * Function to prepares email as string
     * @return type
     */
    public function getMailString() {
        return $this->MIMEHeader.$this->MIMEBody;
    }

	/**
	 * Dispatch (send) email that was queued.
	 */
	static function dispatchQueue(Vtiger_Mailer_Listener $listener=null) {
		global $adb, $mailerScheduleLimit;
		if(!Vtiger_Utils::CheckTable('vtiger_mailer_queue')) return;
		
		if (empty($mailerScheduleLimit)) {
			$mailerScheduleLimit = 10000;
		}

		$mailer = new self();
		$queue = $adb->pquery('SELECT * FROM vtiger_mailer_queue WHERE failed != ?', array(1));
		if($adb->num_rows($queue)) {
			$currentUserModel = Users_Record_Model::getCurrentUserModel();
			$counter = 0;
			$infos = '';
			while ($queue_record = $adb->getNextRow($queue, false)) {
				$mailer->reinitialize();

				$queueid = $queue_record['id'];
				$relcrmid= $queue_record['relcrmid'];

				$mailer->From = $queue_record['fromemail'];
				$mailer->FromName = ($queue_record['fromname']);
				$mailer->Subject = ($queue_record['subject']);
				$mailer->msgHTML($queue_record['body']);
				$mailer->Mailer = $queue_record['mailer'];
				if (!empty($queue_record['content_type'])) {
					$mailer->ContentType = $queue_record['content_type'];
				}
				
				$emails = $adb->pquery('SELECT * FROM vtiger_mailer_queueinfo WHERE id=?', Array($queueid));
				while ($email_record = $adb->getNextRow($emails, false)) {
					if($email_record['type'] == 'TO') $mailer->AddAddress($email_record['email'], ($email_record['name']));
					else if($email_record['type'] == 'CC') $mailer->AddCC($email_record['email'], ($email_record['name']));
					else if($email_record['type'] == 'BCC') $mailer->AddBCC($email_record['email'], ($email_record['name']));
					else if($email_record['type'] == 'RPLYTO') $mailer->AddReplyTo($email_record['email'], ($email_record['name']));
				}

				$attachments = $adb->pquery('SELECT * FROM vtiger_mailer_queueattachments WHERE id=?', Array($queueid));
				while ($attachment_record = $adb->getNextRow($attachments, false)) {
					if($attachment_record['path'] != '') {
						$mailer->AddAttachment($attachment_record['path'], $attachment_record['name'],
												$attachment_record['encoding'], $attachment_record['type']);
					}
				}

				try {
					$sent = $mailer->Send(true);
				} catch (Exception $e) {
					$sent = false;
					$mailer->ErrorInfo = $e->getMessage();
				}
				if($sent) {
					// Event doesn't exist for now in vtiger_eventhandlers, but an object is generated anyway so skip it for now to save time
					// Vtiger_Event::trigger('vtiger.mailer.mailsent', $relcrmid);
					if($listener) {
						$listener->mailsent($queueid);
					}
					$adb->pquery('DELETE FROM vtiger_mailer_queue WHERE id=?', Array($queueid));
					$adb->pquery('DELETE FROM vtiger_mailer_queueinfo WHERE id=?', Array($queueid));
					$adb->pquery('DELETE FROM vtiger_mailer_queueattachments WHERE id=?', Array($queueid));
				} else {
					if($listener) {
						$listener->mailerror($queueid);
					}
					$adb->pquery('UPDATE vtiger_mailer_queue SET failed=?, failreason=? WHERE id=?', Array(1, $mailer->ErrorInfo, $queueid));
				}
				
				// auto_inc ID, subject, receiver, send_date, send_user, crmid, smtp_answer, messageId
				$mtQuery = "INSERT INTO berlicrm_mailtracker VALUES(?,?,?,?,?,?,?,?);";
				$cDT = date('Y-m-d H:i:s');
				$allReveivers = json_encode($mailer->getAllRecipientAddresses());
				$messageId = '';
				if ($sent == 1) {
					$messageId = $mailer->getLastMessageID();
					$errorMsg = $sent;
				} else {
					$errorMsg = $mailer->ErrorInfo;
				}
				
				$adb->pquery($mtQuery, array(NULL, $queue_record['subject'], $allReveivers, $cDT, $currentUserModel->getId(), $relcrmid, $errorMsg, $messageId));
				
				$counter += 1;
				$infos .= "$counter: TO: '{$allReveivers}' SUBJECT: '{$queue_record['subject']}' ID: '$relcrmid' STATUS: '$errorMsg'<br>";
				if ($counter >= $mailerScheduleLimit) {
					break;
				}
				set_time_limit(0);
			}
			// send info mail
			global $HELPDESK_SUPPORT_EMAIL_ID, $HELPDESK_SUPPORT_NAME, $dbconfig, $current_user;
			$mailer->reinitialize();
			$mailer->From = $HELPDESK_SUPPORT_EMAIL_ID;
			$mailer->FromName = $HELPDESK_SUPPORT_NAME;
			$mailer->AddAddress($current_user->email1, 'Admin');
			$mailer->Subject = "Information Massenmailing {$dbconfig['db_name']}";
			$mailer->Mailer = 'smtp';
			$mailer->ContentType = 'text/html';
			
			$mailer->msgHTML($infos);
			try {
				$mailer->Send(true);
			} catch (Exception $e) {
				syslog(LOG_DEBUG, __FILE__);
				syslog(LOG_DEBUG, serialize($e->getMessage()));
			}
			// also create new Document
			include_once('modules/Documents/Documents.php');
			$date = date('Y-m-d H:i:s');
			if(function_exists('date_default_timezone_set')) {
				$default_timezone = 'Europe/Amsterdam';
				$oldTimezone = date_default_timezone_get();
				@date_default_timezone_set($default_timezone);
				$date = date('Y-m-d H:i:s');
				@date_default_timezone_set($oldTimezone);
			}
			$documents = new Documents();
			$documents->column_fields['assigned_user_id'] = $current_user->id;
			$documents->column_fields['notes_title'] 	= 	"Information Massenmailing {$date}";
			$documents->column_fields['smownerid']	=	$current_user->id;
			$documents->column_fields['notecontent'] = $infos;
			$documents->column_fields['folderid'] = 1;
			$documents->save("Documents");
		}
	}
}

/**
 * Provides API to act on the different events triggered by send email action.
 * @package vtlib
 */
abstract class Vtiger_Mailer_Listener {
	function mailsent($queueid) { }
	function mailerror($queueid) { }
}

?>
