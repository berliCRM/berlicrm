<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;
use PHPMailer\PHPMailer\SMTP;
use TheNetworg\OAuth2\Client\Provider\Azure;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Client;

vimport ('~modules/MailManager/models/IMAP_Message.php');

class MailManager_Connector_IMAPConnector {

	/*
	 * Cache interval time
	*/
	static $DB_CACHE_CLEAR_INTERVAL = "-1 day"; // strtotime

	/*
	 * Mail Box URL
	*/
	public $mBoxUrl;

	/*
	 * Mail Box connection instance
	*/
	public $mBox;

	/*
	 * Last imap error
	*/
	protected $mError;

	/*
	 * Mail Box folders
	*/
	protected $mFolders = false;

	/**
	 * Modified Time of the mail
	 */
	protected $mModified = false;

	/*
	 * Base URL of the Mail Box excluding folder name
	*/
	protected $mBoxBaseUrl;
	
	private $currentFolderName;

	/**
	 * Connects to the Imap server with the given parameters
	 * @param $model MailManager_Model_Mailbox Instance
	 * $param $folder String optional - mail box folder name
	 * @returns MailManager_Connector Object
	 */
	public static function connectorWithModel($model, $folder='') {
		$url = $model->server();
		$connector = new self($url, $model->username(), $model->MailId(), $model->password());
		$connector->currentFolderName = $folder;
		return $connector;
	}


	/**
	 * Opens up imap connection to the specified url
	 * @param $url String - mail server url
	 * @param $username String  - user name of the mail box
	 * @param $type String - type of connection
	 * @param $password Optional  - pass word of the mail box
	 *	This is used to fetch the folders of the mail box
	 */
	public function __construct($url, $username, $type, $password = '') {
		try {
			$includePath = 'vendor/autoload.php';
			if (file_exists($includePath)) {
				require_once($includePath);
				if ($type == 'office365') {
					require_once 'modules/Settings/Vtiger/models/ConfigoAuth.php';
					$settingsoAuth = Settings_Vtiger_oAuth::getInstance('mailmanager');
					$oAuthDetails = $settingsoAuth->getData();
					// $access_token = 'eyJ0eXAiOiJKV1QiLCJub25jZSI6Ik9DOVdGVjB6WDVucmlQUWFLdkJpUW5SeW5uYmVQV3FGVVlpNkN4ZXdMNGsiLCJhbGciOiJSUzI1NiIsIng1dCI6ImFGa21LVkZjLTRXVjZzWENCdk5aa1hJNTA1WSIsImtpZCI6ImFGa21LVkZjLTRXVjZzWENCdk5aa1hJNTA1WSJ9.eyJhdWQiOiJodHRwczovL291dGxvb2sub2ZmaWNlLmNvbSIsImlzcyI6Imh0dHBzOi8vc3RzLndpbmRvd3MubmV0L2M0OTNiZjAzLWNiNWMtNDhjMC04MTFmLThkMjExOTNkMzQ1NC8iLCJpYXQiOjE3ODUxNjI3ODcsIm5iZiI6MTc4NTE2Mjc4NywiZXhwIjoxNzg1MTY3NDkxLCJhY2N0IjowLCJhY3IiOiIxIiwiYWlvIjoiQVhRQWkvOGNBQUFBZ2VWcnpPbHQ0c0IrNXZ6bVQrNHYvMnVJcVkxekVieXY5dmZZY0FjN242djFrcUVXSlo5VDQ3dm0vQm1WWFFiMEtIajdadTk3dzdUeExjSjBSL01hL0R5VDJ1ekZJdjhCZWhSc05tZHo1Q29jOFVpMnBrVmh1ME1kVjRUZm9lSU9Xc2NHQUFtK3NYeWxCZlhBTUhQMGlRPT0iLCJhbXIiOlsicHdkIiwibWZhIl0sImFwcF9kaXNwbGF5bmFtZSI6Ik9BdXRoMiBNYWlsIEludGVncmF0aW9uIGJlcmxpQ1JNIiwiYXBwaWQiOiJiMjBmNTExZS1hMDA5LTQwZGQtYTAyMC0zNWFmMDhiNDZkNDAiLCJhcHBpZGFjciI6IjEiLCJlbmZwb2xpZHMiOltdLCJpZHR5cCI6InVzZXIiLCJpcGFkZHIiOiI5MS42NC4xNzkuMzIiLCJsb2dpbl9oaW50IjoiTy5DaVExTkdVNU5EZGtNUzAwT0dObUxUUTFOMkl0WWpFMU9DMWlPVGMwWTJaaVpHWmlaR1FTSkdNME9UTmlaakF6TFdOaU5XTXRORGhqTUMwNE1URm1MVGhrTWpFeE9UTmtNelExTkJvTlpYQkFZM0p0TFc1dmR5NWtaU0JLIiwibmFtZSI6ImVwIiwib2lkIjoiNTRlOTQ3ZDEtNDhjZi00NTdiLWIxNTgtYjk3NGNmYmRmYmRkIiwicHVpZCI6IjEwMDMyMDAwOEE4MjJCNDMiLCJyaCI6IjEuQVhRQUE3LVR4RnpMd0VpQkg0MGhHVDAwVkFJQUFBQUFBUEVQemdBQUFBQUFBQUFBQUx4MEFBLiIsInNjcCI6IklNQVAuQWNjZXNzQXNVc2VyLkFsbCBTTVRQLlNlbmQgVXNlci5SZWFkIiwic2lkIjoiMDA2MDI5NGEtN2Q4Yi1kZjI4LWViYWItMTU2NGNhNjRiZTYwIiwic2lnbmluX3N0YXRlIjpbImttc2kiXSwic3ViIjoiZXYtbklvN1A5YWdVRENvNmFCUEVWakVPdEtQeGlxT3BjRTl6Z0RVcVpfTSIsInRlbmFudF9yZWdpb25fc2NvcGUiOiJFVSIsInRpZCI6ImM0OTNiZjAzLWNiNWMtNDhjMC04MTFmLThkMjExOTNkMzQ1NCIsInVuaXF1ZV9uYW1lIjoiZXBAY3JtLW5vdy5kZSIsInVwbiI6ImVwQGNybS1ub3cuZGUiLCJ1dGkiOiI4NGd3bTZGN19FbVpJYnhlMjE0aEFBIiwidmVyIjoiMS4wIiwid2lkcyI6WyI2MmU5MDM5NC02OWY1LTQyMzctOTE5MC0wMTIxNzcxNDVlMTAiLCJiNzlmYmY0ZC0zZWY5LTQ2ODktODE0My03NmIxOTRlODU1MDkiXSwieG1zX2FjdF9mY3QiOiI5IDMiLCJ4bXNfYXVkX2d1aWQiOiIwMDAwMDAwMi0wMDAwLTBmZjEtY2UwMC0wMDAwMDAwMDAwMDAiLCJ4bXNfZnRkIjoiR3dwSFZTTUZMemtDMmtzMHVnaU50eWtabGh2N3hGZlFGX3NTRFY3bkNGOEJaWFZ5YjNCbGQyVnpkQzFrYzIxeiIsInhtc19pZHJlbCI6IjEgMzAiLCJ4bXNfc3ViX2ZjdCI6IjMgMjAiLCJ4bXNfdGRiciI6IkVVIiwieG1zX3RudF9mY3QiOiIzIDIifQ.AnIsOb109eYU_-Eq-1quJrD_wQmV_daLNCS_t9aAwRwBBM4t8ybL9XwnUnRwHeoYgIZs8GrcOGP2Z0RVDVUZgHxhmD_NGLTrbmCDCcZu_qJ6UyB9kzZv2Iaw3mPiwe6EKaj9H7XzM_t0zeb0aRKFFAzezJWywt1wPkxykzs1gBuwE9ebPPpX4PcxvG670ULrERFLo6FeHfLicGo6ivI10RLhbrhyxy_7zkLgZPVHEU2xoMJCH6V2cB4S1jAHf2e3BoCuIRUJBLe2Y39aOhZKbUO-6r26Rk_ouCsEj8K9bMxFcf0y12JDsTzZ1iPskITY3mQu_B2bEocRFiqwyI7reA';
					$access_token = $oAuthDetails['hidden_access_token'];
					$refresh_token = $oAuthDetails['hidden_refresh_token'];
					$access_token_expire = $oAuthDetails['hidden_access_token_expire'];
					// refresh access_token if necessary
					if (empty($access_token) || $access_token_expire <= time()) {
						if (!empty($refresh_token)) {
							require_once('modules/Settings/Vtiger/actions/RefreshoAuthToken.php');
							$action = new Settings_Vtiger_RefreshoAuthToken_Action();
							$request = new Vtiger_Request(array('provider' => 'AZURE', 'type' => 'mailmanager'));
							$tmp = ob_get_contents();
							$action->process($request);
							ob_end_clean();
							
							$settingsoAuth->load();
							$oAuthDetails = $settingsoAuth->getData();
							$access_token = $oAuthDetails['hidden_access_token'];
						} else {
							throw new Exception("Couldn't refresh access_token");
						}
					}
					$cm = new ClientManager($options = []);
					if (empty($url)) {
						$url = 'outlook.office365.com';
					}
					$client = $cm->make([
						'host'          => $url,
						'port'          => 993,
						'encryption'    => 'ssl',
						'validate_cert' => true,
						'username'      => $username,
						'password'      => $access_token,
						'protocol'      => 'imap',
						'authentication' => 'oauth'
					]);
					if ($client->connect()) {
						$this->mBox = $client;
					}
				} else {
					$cm = new ClientManager($options = []);
					$client = $cm->make([
						'host'          => $url,
						'port'          => 993,
						'encryption'    => 'ssl',
						'validate_cert' => true,
						'username'      => $username,
						'password'      => $password,
						'protocol'      => 'imap'
					]);
					if ($client->connect()) {
						$this->mBox = $client;
					}
				}
			}
		} catch (Exception $e) {
			$this->mBox = false;
			$this->mError = $e->getMessage();
		}
	}


	/**
	 * Closes the connection
	 */
	public function __destruct() {
		$this->close();
	}


	/**
	 * Closes the imap connection
	 */
	public function close() {
		if (!empty($this->mBox)) {
			$this->mBox->disconnect();
			$this->mBox = null;
		}
	}


	/**
	 * Checks for the connection
	 */
	public function isConnected() {
		return (!empty($this->mBox) && $this->mBox->isConnected());
	}


	/**
	 * Returns the last imap error
	 */
	public function isError() {
		return $this->hasError();
	}


	/**
	 * Checks if the error exists
	 */
	public function hasError() {
		return !empty($this->mError);
	}


	/**
	 * Returns the error
	 */
	public function lastError() {
		return $this->mError;
	}


	/**
	 * Reads mail box folders
	 * @param string $ref Optional -
	 */
	public function folders($ref="{folder}") {
		if ($this->mFolders) return $this->mFolders;
		$folders = array();
		if ($this->isConnected()) {
			$result = $this->mBox->getFolders();
			if ($this->isError()) return false;

			$folders = array();
			foreach($result as $row) {
				$folderName = str_replace($ref, "", $row->name);
				$folders[] = $this->folderInstance($folderName);
			}
			$this->mFolders = $folders;
		}
		return $folders;
	}


	/**
	 * Used to update the folders optionus
	 * @param imap_stats flag $options
	 */
	public function updateFolders($options=SA_UNSEEN) {
		$this->folders(); // Initializes the folder Instance
		foreach($this->mFolders as $folder) {
			$this->updateFolder($folder, $options);
		}
	}


	/**
	 * Updates the mail box's folder
	 * @param MailManager_Model_Folder $folder - folder instance
	 * @param $options imap_status flags like SA_UNSEEN, SA_MESSAGES etc
	 */
	public function updateFolder($folder, $options) {
		$nFolder = $folder->getNativeFolder();
		if ($nFolder) {
			$folderCheck = $nFolder->examine();
			$folder->setUnreadCount((int) $folderCheck['unseen']);
			$folder->setCount((int) $folderCheck['exists']);
		}
	}


	/**
	 * Returns MailManager_Model_Folder Instance
	 * @param String $name - folder name
	 */
	public function folderInstance($name) {
		vimport('modules/MailManager/models/Folder.php');
		$folder = new MailManager_Folder_Model($name);
		if ($this->isConnected()) {
			$nFolder = $this->mBox->getFolderByName($name);
			$folder->setNativeFolder($nFolder);
		}
		return $folder;
	}


	/**
	 * Sets a list of mails with paging
	 * @param String $folder - MailManager_Model_Folder Instance
	 * @param Integer $start  - Page number
	 * @param Integer $maxLimit - Number of mails
	 */
	public function folderMails($folder, $start, $maxLimit) {
		$nFolder = $folder->getNativeFolder();
		if ($nFolder) {
			$folderCheck = $nFolder->examine();
			$exists = (int) $folderCheck['exists'];
			if ($exists > 0) {
				$reverse_start = $exists - ($start*$maxLimit);
				$reverse_end = $reverse_start - $maxLimit + 1;

				if ($reverse_start < 1) $reverse_start = 1;
				if ($reverse_end < 1) $reverse_end = 1;

				// $sequence = sprintf("%s:%s", $reverse_start, $reverse_end);
				// $sequence = '1:*';
				
				// overview is very unreliable
				// $records = $nFolder->overview($sequence);
				$query = $nFolder->query();
				$query->setFetchOrderDesc();
				$records = $query->all()->limit($maxLimit, $start+1)->get();
				$mails = array();
				foreach($records AS $mId => $message) {
					$seen = $message->getFlags()->get("seen");
					if ($seen) {
						$message->seen = true;
					}
					array_unshift($mails, MailManager_IMAPMessage_Model::parseOverview($message));
				}
				$folder->setMails($mails);
				$folder->setPaging($reverse_end, $reverse_start, $maxLimit, $exists, $start);
			}
		}
	}


	/**
	 * Return the cache interval
	 */
	public function clearDBCacheInterval() {
		// TODO Provide configuration option.
		if (self::$DB_CACHE_CLEAR_INTERVAL) {
			return strtotime(self::$DB_CACHE_CLEAR_INTERVAL);
		}
		return false;
	}


	/**
	 * Clears the cache data
	 */
	public function clearDBCache() {
		// Trigger purne any older mail saved in DB first
		$interval = $this->clearDBCacheInterval();

		$timenow = strtotime("now");

		// Optimization to avoid trigger for ever mail open (with interval specified)
		$lastClearTimeFromSession = false;
		if ($interval && isset($_SESSION) && isset($_SESSION['mailmanager_clearDBCacheIntervalLast'])) {
			$lastClearTimeFromSession = intval($_SESSION['mailmanager_clearDBCacheIntervalLast']);
			if (($timenow - $lastClearTimeFromSession) < ($timenow - $interval)) {
				$interval = false;
			}
		}
		if ($interval) {
			MailManager_IMAPMessage_Model::pruneOlderInDB($interval);
			$_SESSION['mailmanager_clearDBCacheIntervalLast'] = $timenow;
		}
	}


	/**
	 * Function which deletes the mails
	 * @param String $msgno - List of message number seperated by commas.
	 */
	public function deleteMail($msgNos) {
		$folder = $this->mBox->getFolderByName($this->currentFolderName);
		$message = false;
		if ($folder) {
			foreach ($msgNos AS $msgNo) {
				$message = $folder->query()->getMessageByMsgn($msgNo);
				$message->delete(true);
			}
		}
	}


	/**
	 * Function which moves mail to another folder
	 * @param String $msgno - List of message number separated by commas
	 * @param String $folderName - folder name
	 */
	public function moveMail($msgNos, $folderName) {
		$msgNos = trim($msgNos,',');
		$msgNos = explode(',',$msgNos);
		$folder = $this->mBox->getFolderByName($this->currentFolderName);
		if ($folder) {
			foreach ($msgNos AS $msgNo) {
				$message = $folder->query()->getMessageByMsgn($msgNo);
				if ($message) {
					$message->move($folderName);
				}
			}
		}
	}


	/**
	 * Creates an instance of Message
	 * @param String $msgno - Message number
	 * @return MailManager_Model_Message
	 */
	public function openMail($msgNo) {
		// $this->clearDBCache($folderName);
		$folder = $this->mBox->getFolderByName($this->currentFolderName);
		$message = false;
		if ($folder) {
			$message = $folder->query()->getMessageByMsgn($msgNo);
		}
		$model = new MailManager_IMAPMessage_Model($message, true);
		if ($message) {
			$model->setMsgNo($msgNo);
			$message->setFlag('Seen');
		}
		return $model;
	}


	/**
	 * Marks the mail as Unread
	 * @param <String> $msgno - Message Number
	 */
	public function markMailUnread($msgNo) {
		$folder = $this->mBox->getFolderByName($this->currentFolderName);
		$message = false;
		if ($folder) {
			$message = $folder->query()->getMessageByMsgn($msgNo);
			if ($message) {
				$message->unsetFlag('Seen');
				$this->mModified = true;
			}
		}
	}


	/**
	 * Marks the mail as Read
	 * @param String $msgno - Message Number
	 */
	public function markMailRead($msgNo) {
		$folder = $this->mBox->getFolderByName($this->currentFolderName);
		$message = false;
		if ($folder) {
			$message = $folder->query()->getMessageByMsgn($msgNo);
			if ($message) {
				$message->setFlag('Seen');
				$this->mModified = true;
			}
		}
	}


	/**
	 * Searches the Mail Box with the query
	 * @param String $query - imap search format
	 * @param MailManager_Model_Folder $folder - folder instance
	 * @param Integer $start - Page number
	 * @param Integer $maxLimit - Number of mails
	 */
	public function searchMails($query, $folder, $start, $maxLimit) {
		$nFolder = $folder->getNativeFolder();
		if ($nFolder) {
			$tmp = explode(' ', $query);
			$type = array_shift($tmp);
			$search = implode(' ', $tmp);
			// remove leading and trailing "
			$search = substr($search, 1);
			$search = substr($search, 0, -1);
			
			$reverse_start = $exists - ($start*$maxLimit);
			$reverse_end = $reverse_start - $maxLimit + 1;

			if ($reverse_start < 1) $reverse_start = 1;
			if ($reverse_end < 1) $reverse_end = 1;

			$query = $nFolder->query();
			$query->setFetchOrderDesc();
			$records = $query->$type($search)->limit($maxLimit, $start+1)->get();
			$mails = array();
			foreach($records AS $mId => $message) {
				$seen = $message->getFlags()->get("seen");
				if ($seen) {
					$message->seen = true;
				}
				array_unshift($mails, MailManager_IMAPMessage_Model::parseOverview($message));
			}
			$folder->setMails($mails);
			$folder->setPaging($reverse_end, $reverse_start, $maxLimit, count($mails), $start);
		}
	}


	/**
	 * Returns list of Folder for the Mail Box
	 * @return Array folder list
	 */
	public function getFolderList() {
		// not loaded here?
		$folders = $this->folders();
		$folderList = array();
		foreach ($folders AS $folder) {
			$folderList[] = $folder->name();
		}
		return $folderList;
	}

	public function convertCharacterEncoding($value, $toCharset, $fromCharset) {
		if (function_exists('mb_convert_encoding')) {
			$value = mb_convert_encoding($value, $toCharset, $fromCharset);
		} else {
			$value = iconv($toCharset, $fromCharset, $value);
		}
		return $value;
	}
	
	public function getNewMailsCount() {
		// $nos = imap_search($this->mBox, 'NEW');
		// $found = 0;
		// if ($nos !== false) $found = count($nos);
		// return $found;
		return "NOT IMPLEMENTED YET";
	}
}
