<?php
/**
 * Class Settings_Vtiger_ConfigTicketEmailAddress
 *
 * Stores global ticket email address configuration in:
 *  - vtiger_settings_config (config_key = 'ticket_email_address')
 *  - vtiger_settings_config_param (param_key = 'enabled', 'sender_email', 'sender_name', 'reply_to_email', 'reply_to_name')
 *
 * Tracks last editor in vtiger_settings_config.updated_by_user_id (global).
 *
 * Backward compatible output keys:
 *  - sender_email_preview (alias of sender_email)
 */
class Settings_Vtiger_ConfigTicketEmailAddress extends Vtiger_Base_Model {

	/** @var string */
	const CONFIG_KEY = 'ticket_email_address';

	/** @var string */
	const PARAM_ENABLED = 'enabled';

	/** @var string */
	const PARAM_SENDER_EMAIL = 'sender_email';
	const PARAM_SENDER_NAME  = 'sender_name';
	const PARAM_REPLYTO_EMAIL = 'reply_to_email';
	const PARAM_REPLYTO_NAME  = 'reply_to_name';

	/**
	 * Get singleton-like instance of the ticket email configuration model.
	 *
	 * @return self
	 */
	public static function getInstance() {
		$instance = new self();
		$instance->load();
		return $instance;
	}

	/**
	 * Get current vtiger user id via Users_Record_Model.
	 *
	 * @return int|null
	 */
	protected function getCurrentUserId() {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		if ($currentUserModel) {
			$userId = (int)$currentUserModel->getId();
			return $userId > 0 ? $userId : null;
		}
		return null;
	}

	/**
	 * Ensure config row exists in vtiger_settings_config and return its id.
	 *
	 * @param PearDatabase $db
	 * @return int
	 */
	protected function ensureConfigId(PearDatabase $db) {
		$result = $db->pquery(
			"SELECT id FROM vtiger_settings_config WHERE config_key = ? LIMIT 1",
			[self::CONFIG_KEY]
		);

		if ($result && $db->num_rows($result)) {
			$row = $db->fetchByAssoc($result, 0);
			return (int)$row['id'];
		}

		$now = gmdate('Y-m-d H:i:s');
		$userId = $this->getCurrentUserId(); // may be null

		$db->pquery(
			"INSERT INTO vtiger_settings_config
			 (config_key, label, description, is_active, updated_at, updated_by_user_id)
			 VALUES (?, ?, ?, ?, ?, ?)",
			[
				self::CONFIG_KEY,
				'ticket email address',
				'global sender / reply-to email address for ticket emails',
				1,
				$now,
				$userId
			]
		);

		return (int)$db->getLastInsertID();
	}

	/**
	 * Ensure a param row exists (no overwrite) for a given config_id/param_key.
	 *
	 * @param PearDatabase $db
	 * @param int $configId
	 * @param string $paramKey
	 * @param string $valueType
	 * @param int $sortOrder
	 * @return void
	 */
	protected function ensureParamExists(PearDatabase $db, $configId, $paramKey, $valueType = 'string', $sortOrder = 0) {
		$result = $db->pquery(
			"SELECT id FROM vtiger_settings_config_param WHERE config_id = ? AND param_key = ? LIMIT 1",
			[$configId, $paramKey]
		);

		if ($result && $db->num_rows($result)) {
			return;
		}

		$now = gmdate('Y-m-d H:i:s');
		$db->pquery(
			"INSERT INTO vtiger_settings_config_param
			 (config_id, param_key, param_value, value_type, sort_order, updated_at)
			 VALUES (?, ?, NULL, ?, ?, ?)",
			[$configId, $paramKey, $valueType, (int)$sortOrder, $now]
		);
	}

	/**
	 * Helper: upsert a config param.
	 *
	 * @param PearDatabase $db
	 * @param int $configId
	 * @param string $paramKey
	 * @param string|null $paramValue
	 * @param string $valueType
	 * @param int $sortOrder
	 * @param string $now
	 * @return void
	 */
	protected function upsertParam(PearDatabase $db, $configId, $paramKey, $paramValue, $valueType, $sortOrder, $now) {
		$db->pquery(
			"INSERT INTO vtiger_settings_config_param
			 (config_id, param_key, param_value, value_type, sort_order, updated_at)
			 VALUES (?, ?, ?, ?, ?, ?)
			 ON DUPLICATE KEY UPDATE
				param_value = VALUES(param_value),
				value_type = VALUES(value_type),
				sort_order = VALUES(sort_order),
				updated_at = VALUES(updated_at)",
			[$configId, $paramKey, $paramValue, $valueType, (int)$sortOrder, $now]
		);
	}

	/**
	 * Load ticket email configuration from database.
	 *
	 * @return self
	 */
	public function load() {
		$db = PearDatabase::getInstance();

		$configId = $this->ensureConfigId($db);

		$configRes = $db->pquery(
			"SELECT updated_at, updated_by_user_id
			 FROM vtiger_settings_config
			 WHERE id = ? LIMIT 1",
			[$configId]
		);

		$updatedAt = '';
		$updatedByUserId = null;

		if ($configRes && $db->num_rows($configRes)) {
			$row = $db->fetchByAssoc($configRes, 0);
			$updatedAt = (string)($row['updated_at'] ?? '');
			$updatedByUserId = !empty($row['updated_by_user_id'])
				? (int)$row['updated_by_user_id']
				: null;
		}
		// Ensure expected params exist
		$this->ensureParamExists($db, $configId, self::PARAM_ENABLED, 'bool', 0);
		$this->ensureParamExists($db, $configId, self::PARAM_SENDER_EMAIL, 'string', 1);
		$this->ensureParamExists($db, $configId, self::PARAM_SENDER_NAME, 'string', 2);
		$this->ensureParamExists($db, $configId, self::PARAM_REPLYTO_EMAIL, 'string', 3);
		$this->ensureParamExists($db, $configId, self::PARAM_REPLYTO_NAME, 'string', 4);

		$paramsResult = $db->pquery(
			"SELECT param_key, param_value
			 FROM vtiger_settings_config_param
			 WHERE config_id = ?
			   AND param_key IN (?, ?, ?, ?, ?)",
			[
				$configId,
				self::PARAM_ENABLED,
				self::PARAM_SENDER_EMAIL,
				self::PARAM_SENDER_NAME,
				self::PARAM_REPLYTO_EMAIL,
				self::PARAM_REPLYTO_NAME
			]
		);

		$data = [
			self::PARAM_ENABLED => 0,
			self::PARAM_SENDER_EMAIL => '',
			self::PARAM_SENDER_NAME => '',
			self::PARAM_REPLYTO_EMAIL => '',
			self::PARAM_REPLYTO_NAME => '',
		];

		if ($paramsResult && $db->num_rows($paramsResult)) {
			$rows = $db->num_rows($paramsResult);
			for ($i = 0; $i < $rows; $i++) {
				$row = $db->fetchByAssoc($paramsResult, $i);
				$key = (string)($row['param_key'] ?? '');
				if ($key !== '' && array_key_exists($key, $data)) {
					$data[$key] = (string)($row['param_value'] ?? '');
				}
			}
		}

		$this->set('config_id', $configId);
		$this->set('enabled', (int)$data[self::PARAM_ENABLED]);
		$this->set('sender_email', (string)$data[self::PARAM_SENDER_EMAIL]);
		$this->set('sender_name', (string)$data[self::PARAM_SENDER_NAME]);
		$this->set('reply_to_email', (string)$data[self::PARAM_REPLYTO_EMAIL]);
		$this->set('reply_to_name', (string)$data[self::PARAM_REPLYTO_NAME]);
		$this->set('updated_at', $updatedAt);
		$this->set('updated_by_user_id', $updatedByUserId);

		// Optional backward-compatible aliases
		$this->set('sender_email_preview', (string)$data[self::PARAM_SENDER_EMAIL]);

		return $this;
	}

	/**
	 * Return ticket email configuration as array for templates.
	 *
	 * @return array{
	 *   enabled:int,
	 *   sender_email:string,
	 *   sender_name:string,
	 *   reply_to_email:string,
	 *   reply_to_name:string,
	 *   sender_email_preview:string,
	 *   updated_at:string
	 *   updated_by:string
	 * }
	 */
	public function getData() {
		$updatedAtRaw = (string)$this->get('updated_at');
		$updatedAtUser = '';
		$updatedByName = '';

		if (!empty($updatedAtRaw)) {
			$dateTime = new DateTimeField($updatedAtRaw);
			$updatedAtUser = $dateTime->getDisplayDateTimeValue();
		}

		$updatedByUserId = (int)$this->get('updated_by_user_id');
		if ($updatedByUserId > 0) {
			$userModel = Users_Record_Model::getInstanceById($updatedByUserId, 'Users');
			if ($userModel) {
				$updatedByName = $userModel->getName();
			}
		}

		return [
			'enabled' => (int)$this->get('enabled'),
			'sender_email' => (string)$this->get('sender_email'),
			'sender_name' => (string)$this->get('sender_name'),
			'reply_to_email' => (string)$this->get('reply_to_email'),
			'reply_to_name' => (string)$this->get('reply_to_name'),
			'sender_email_preview' => (string)$this->get('sender_email'),
			'updated_at' => $updatedAtUser,
			'updated_by' => $updatedByName,
		];
	}

	/**
	 * Save ticket email configuration to database (UPSERT params).
	 * Also updates vtiger_settings_config.updated_by_user_id.
	 *
	 * @param int|bool $enabled
	 * @param string $senderEmail
	 * @param string $senderName
	 * @param string $replyToEmail
	 * @param string $replyToName
	 * @return self
	 */
	public function save($enabled, $senderEmail, $senderName = '', $replyToEmail = '', $replyToName = '') {
		$db = PearDatabase::getInstance();

		$enabled = (int)$enabled;
		$senderEmail = (string)$senderEmail;
		$senderName = (string)$senderName;
		$replyToEmail = (string)$replyToEmail;
		$replyToName = (string)$replyToName;

		$configId = (int)$this->get('config_id');
		if (!$configId) {
			$configId = $this->ensureConfigId($db);
			$this->set('config_id', $configId);
		}

		$now = gmdate('Y-m-d H:i:s');

		// Upsert params
		$this->upsertParam($db, $configId, self::PARAM_ENABLED, (string)$enabled, 'bool', 0, $now);
		$this->upsertParam($db, $configId, self::PARAM_SENDER_EMAIL, $senderEmail, 'string', 1, $now);
		$this->upsertParam($db, $configId, self::PARAM_SENDER_NAME, $senderName, 'string', 2, $now);
		$this->upsertParam($db, $configId, self::PARAM_REPLYTO_EMAIL, $replyToEmail, 'string', 3, $now);
		$this->upsertParam($db, $configId, self::PARAM_REPLYTO_NAME, $replyToName, 'string', 4, $now);

		// Touch parent config updated_at + updated_by_user_id
		$userId = $this->getCurrentUserId(); // may be null
		$db->pquery(
			"UPDATE vtiger_settings_config
			 SET updated_at = ?, updated_by_user_id = ?
			 WHERE id = ?",
			[$now, $userId, $configId]
		);

		$this->set('enabled', $enabled);
		$this->set('sender_email', $senderEmail);
		$this->set('sender_name', $senderName);
		$this->set('reply_to_email', $replyToEmail);
		$this->set('reply_to_name', $replyToName);
		$this->set('sender_email_preview', $senderEmail);
		$this->set('updated_at', $now);

		return $this;
	}
}