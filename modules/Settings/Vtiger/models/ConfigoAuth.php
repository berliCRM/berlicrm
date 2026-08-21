<?php
/**
 * Class Settings_Vtiger_oAuth
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
class Settings_Vtiger_oAuth extends Vtiger_Base_Model {
	/** @var string */
	const CONFIG_KEY = 'oauth';

	/** @var string */
	const PARAM_ENABLED = 'enabled';

	/** @var string */
	// const PARAM_USER_NAME = 'user_name';
	const PARAM_PROVIDER = 'provider';
	const PARAM_TENANT_ID = 'tenant_id';
	const PARAM_CLIENT_ID  = 'client_id';
	const PARAM_CLIENT_SECRET = 'client_secret';
	const PARAM_REFRESH = 'hidden_refresh_token';
	const PARAM_REFRESH_EXPIRE = 'hidden_refresh_token_expire';

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
				'oAuth',
				'oAuth Parameter',
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
		
		$constants = $this->getClassConstants();
		if (isset($constants['CONFIG_KEY'])) {
			unset($constants['CONFIG_KEY']);
		}
		foreach ($constants AS $name => $value) {
			$type = 'string';
			if ($name == 'PARAM_ENABLED') {
				$type = 'bool';
			}
			// Ensure expected params exist
			$this->ensureParamExists($db, $configId, $value, $type);
		}

		$paramsResult = $db->pquery(
			"SELECT *
			 FROM vtiger_settings_config_param
			 WHERE config_id = ?;",
			[$configId]
		);

		if ($paramsResult && $db->num_rows($paramsResult) > 0) {
			while ($row = $db->getNextRow($paramsResult, false)) {
				$key = $row['param_key'];
				$value = $row['param_value'];
				$this->set($key, $value);
			}
		}

		return $this;
	}

	/**
	 * Return signature configuration as array for templates.
	 *
	 * @return array{
	 * }
	 */
	public function getData() {
		$retArr = [];
		$constants = $this->getClassConstants();
		if (isset($constants['CONFIG_KEY'])) {
			unset($constants['CONFIG_KEY']);
		}
		foreach ($constants AS $name => $value) {
			$retArr[$value] = $this->get($value);
		}

		return $retArr;
	}

	/**
	 * Save signature configuration to database (UPSERT params).
	 * Also updates vtiger_settings_config.updated_by_user_id.
	 *
	 * @param int|bool $enabled
	 * @param string $signatureHtml
	 * @return self
	 */
	public function save($request) {
		$db = PearDatabase::getInstance();

		$enabled = (int)$enabled;
		$signatureHtml = (string)$signatureHtml;

		$configId = (int)$this->get('config_id');
		if (!$configId) {
			$configId = $this->ensureConfigId($db);
			$this->set('config_id', $configId);
		}

		$now = gmdate('Y-m-d H:i:s');
		
		$constants = $this->getClassConstants();
		if (isset($constants['CONFIG_KEY'])) {
			unset($constants['CONFIG_KEY']);
		}
		$sort = 0;
		foreach ($constants AS $name => $value) {
			// only set values that are provided in request
			if (!$request->has($value)) {
				continue;
			}
			$requestValue = $request->get($value);
			$query = "INSERT INTO vtiger_settings_config_param
					 (config_id, param_key, param_value, sort_order, updated_at)
					 VALUES (?, ?, ?, ?, ?)
					 ON DUPLICATE KEY UPDATE
						param_value = ?,
						sort_order = ?,
						updated_at = ?;";
			$params = [$configId, $value, $requestValue, $sort, $now, $requestValue, $sort, $now];
			$tmp = $db->pquery($query, $params);
			$sort += 1;
			if ($tmp) {
				$this->set($value, $requestValue);
			} else {
				// echo "$query<br>";
				// echo"<pre>";
				// var_dump($params);
				// echo "</pre>";
				// var_dump($db->database->errorMsg());
				// echo "<hr>";
			}
		}

		// Touch parent config updated_at + updated_by_user_id
		$userId = $this->getCurrentUserId(); // may be null
		$db->pquery(
			"UPDATE vtiger_settings_config
			 SET updated_at = ?, updated_by_user_id = ?
			 WHERE id = ?",
			[$now, $userId, $configId]
		);

		return $this;
	}
	
	public function getClassConstants() {
		$reflect = new ReflectionClass(get_class($this));
		return $reflect->getConstants();
	}
}