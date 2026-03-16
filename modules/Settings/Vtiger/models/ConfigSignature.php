<?php
/**
 * Class Settings_Vtiger_ConfigSignature
 *
 * Stores global HTML email signature in:
 *  - vtiger_settings_config (config_key = 'signature')
 *  - vtiger_settings_config_param (param_key = 'enabled', 'signature_html')
 *
 * Tracks last editor in vtiger_settings_config.updated_by_user_id (global).
 *
 * Backward compatible output keys:
 *  - signature_preview (alias of signature_html)
 */
class Settings_Vtiger_ConfigSignature extends Vtiger_Base_Model {
	/** @var string */
	const CONFIG_KEY = 'signature';

	/** @var string */
	const PARAM_ENABLED = 'enabled';

	/** @var string */
	const PARAM_SIGNATURE_HTML = 'signature_html';

	/**
	 * Get singleton-like instance of the signature configuration model.
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
				'email signature',
				'global HTML signature for outgoing emails',
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
	 * @return void
	 */
	protected function ensureParamExists(PearDatabase $db, $configId, $paramKey, $valueType = 'string') {
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
			 VALUES (?, ?, NULL, ?, 0, ?)",
			[$configId, $paramKey, $valueType, $now]
		);
	}

	/**
	 * Load signature configuration from database.
	 *
	 * @return self
	 */
	public function load() {
		$db = PearDatabase::getInstance();

		$configId = $this->ensureConfigId($db);

		// Read updated_at + updated_by_user_id from parent config table
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
			$updatedByUserId = !empty($row['updated_by_user_id']) ? (int)$row['updated_by_user_id'] : null;
		}

		// Ensure expected params exist
		$this->ensureParamExists($db, $configId, self::PARAM_ENABLED, 'bool');
		$this->ensureParamExists($db, $configId, self::PARAM_SIGNATURE_HTML, 'html');

		$paramsResult = $db->pquery(
			"SELECT param_key, param_value
			 FROM vtiger_settings_config_param
			 WHERE config_id = ? AND param_key IN (?, ?)",
			[$configId, self::PARAM_ENABLED, self::PARAM_SIGNATURE_HTML]
		);

		$enabled = 0;
		$signatureHtml = '';

		if ($paramsResult && $db->num_rows($paramsResult)) {
			$rows = $db->num_rows($paramsResult);
			for ($i = 0; $i < $rows; $i++) {
				$row = $db->fetchByAssoc($paramsResult, $i);
				$key = (string)($row['param_key'] ?? '');
				$val = $row['param_value'];

				if ($key === self::PARAM_ENABLED) {
					$enabled = (int)$val;
				} elseif ($key === self::PARAM_SIGNATURE_HTML) {
					$signatureHtml = (string)$val;
				}
			}
		}

		$this->set('config_id', $configId);
		$this->set('enabled', (int)$enabled);
		$this->set('signature_html', (string)$signatureHtml);

		$this->set('updated_at', $updatedAt);
		$this->set('updated_by_user_id', $updatedByUserId);

		// Backward-compatible alias
		$this->set('signature_preview', (string)$signatureHtml);

		return $this;
	}

	/**
	 * Return signature configuration as array for templates.
	 *
	 * @return array{
	 *   enabled:int,
	 *   signature_html:string,
	 *   signature_preview:string,
	 *   updated_at:string,
	 *   updated_by:string
	 * }
	 */
	public function getData() {
		$updatedAtRaw = (string)$this->get('updated_at');
		$updatedAtUser = '';

		if (!empty($updatedAtRaw)) {
			$dateTime = new DateTimeField($updatedAtRaw);
			$updatedAtUser = $dateTime->getDisplayDateTimeValue();
		}

		$updatedByName = '';
		$updatedByUserId = (int)$this->get('updated_by_user_id');
		if ($updatedByUserId > 0) {
			$userModel = Users_Record_Model::getInstanceById($updatedByUserId, 'Users');
			if ($userModel) {
				$updatedByName = $userModel->getName();
			}
		}

		return [
			'enabled' => (int)$this->get('enabled'),
			'signature_html' => (string)$this->get('signature_html'),
			'signature_preview' => (string)$this->get('signature_html'),
			'updated_at' => $updatedAtUser,
			'updated_by' => $updatedByName,
		];
	}

	/**
	 * Save signature configuration to database (UPSERT params).
	 * Also updates vtiger_settings_config.updated_by_user_id.
	 *
	 * @param int|bool $enabled
	 * @param string $signatureHtml
	 * @return self
	 */
	public function save($enabled, $signatureHtml) {
		$db = PearDatabase::getInstance();

		$enabled = (int)$enabled;
		$signatureHtml = (string)$signatureHtml;

		$configId = (int)$this->get('config_id');
		if (!$configId) {
			$configId = $this->ensureConfigId($db);
			$this->set('config_id', $configId);
		}

		$now = gmdate('Y-m-d H:i:s');

		// Upsert enabled
		$db->pquery(
			"INSERT INTO vtiger_settings_config_param
			 (config_id, param_key, param_value, value_type, sort_order, updated_at)
			 VALUES (?, ?, ?, 'bool', 0, ?)
			 ON DUPLICATE KEY UPDATE
				param_value = VALUES(param_value),
				updated_at = VALUES(updated_at)",
			[$configId, self::PARAM_ENABLED, (string)$enabled, $now]
		);

		// Upsert signature html
		$db->pquery(
			"INSERT INTO vtiger_settings_config_param
			 (config_id, param_key, param_value, value_type, sort_order, updated_at)
			 VALUES (?, ?, ?, 'html', 1, ?)
			 ON DUPLICATE KEY UPDATE
				param_value = VALUES(param_value),
				updated_at = VALUES(updated_at)",
			[$configId, self::PARAM_SIGNATURE_HTML, $signatureHtml, $now]
		);

		// Touch parent config updated_at + updated_by_user_id
		$userId = $this->getCurrentUserId(); // may be null
		$db->pquery(
			"UPDATE vtiger_settings_config
			 SET updated_at = ?, updated_by_user_id = ?
			 WHERE id = ?",
			[$now, $userId, $configId]
		);

		$this->set('enabled', $enabled);
		$this->set('signature_html', $signatureHtml);
		$this->set('signature_preview', $signatureHtml);

		$this->set('updated_at', $now);
		$this->set('updated_by_user_id', $userId);

		return $this;
	}
}