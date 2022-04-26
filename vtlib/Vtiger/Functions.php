<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * TODO need to organize into classes based on functional grouping.
 */

class Vtiger_Functions {

	static function userIsAdministrator($user) {
		return (isset($user->is_admin) && $user->is_admin == 'on');
	}

	static function currentUserJSDateFormat($localformat) {
		global $current_user;
		if ($current_user->date_format == 'dd-mm-yyyy') {
			$dt_popup_fmt = "%d-%m-%Y";
		} elseif ($current_user->date_format == 'mm-dd-yyyy') {
			$dt_popup_fmt = "%m-%d-%Y";
		} elseif ($current_user->date_format == 'yyyy-mm-dd') {
			$dt_popup_fmt = "%Y-%m-%d";
		}
		return $dt_popup_fmt;
	}

	/**
	 * This function returns the date in user specified format.
	 * limitation is that mm-dd-yyyy and dd-mm-yyyy will be considered same by this API.
	 * As in the date value is on mm-dd-yyyy and user date format is dd-mm-yyyy then the mm-dd-yyyy
	 * value will be return as the API will be considered as considered as in same format.
	 * this due to the fact that this API tries to consider the where given date is in user date
	 * format. we need a better gauge for this case.
	 * @global Users $current_user
	 * @param Date $cur_date_val the date which should a changed to user date format.
	 * @return Date
	 */
	static function currentUserDisplayDate($value) {
		global $current_user;
		$dat_fmt = $current_user->date_format;
		if ($dat_fmt == '') {
			$dat_fmt = 'dd-mm-yyyy';
		}
		$date = new DateTimeField($value);
		return $date->getDisplayDate();
	}

	static function currentUserDisplayDateNew() {
		global $log, $current_user;
		$date = new DateTimeField(null);
		return $date->getDisplayDate($current_user);
	}

	// i18n
	static function getTranslatedString($str, $module = '') {
		return Vtiger_Language_Handler::getTranslatedString($str, $module);
	}

	// CURRENCY
	protected static $userIdCurrencyIdCache = array();

	static function userCurrencyId($userid) {
		global $adb;
		if (!isset(self::$userIdCurrencyIdCache[$userid])) {
			$result = $adb->pquery('SELECT id,currency_id FROM vtiger_users', array());
			while ($row = $adb->fetch_array($result)) {
				self::$userIdCurrencyIdCache[$row['id']] =
						$row['currency_id'];
			}
		}
		return self::$userIdCurrencyIdCache[$userid];
	}

	protected static $currencyInfoCache = array();

	protected static function getCurrencyInfo($currencyid) {
		global $adb;
		if (!isset(self::$currencyInfoCache[$currencyid])) {
			$result = $adb->pquery('SELECT * FROM vtiger_currency_info', array());
			while ($row = $adb->fetch_array($result)) {
				self::$currencyInfoCache[$row['id']] = $row;
			}
		}
		return self::$currencyInfoCache[$currencyid];
	}

	static function getCurrencyName($currencyid, $show_symbol = true) {
		$currencyInfo = self::getCurrencyInfo($currencyid);
		if ($show_symbol) {
			return sprintf("%s : %s", Vtiger_Deprecated::getTranslatedCurrencyString($currencyInfo['currency_name']), $currencyInfo['currency_symbol']);
		}
		return $currencyInfo['currency_name'];
	}

	static function getCurrencySymbolandRate($currencyid) {
		$currencyInfo = self::getCurrencyInfo($currencyid);
		$currencyRateSymbol = array(
			'rate' => $currencyInfo['conversion_rate'],
			'symbol'=>$currencyInfo['currency_symbol']
		);
		return $currencyRateSymbol;
	}

	// MODULE
	protected static $moduleIdNameCache = array();
	protected static $moduleNameIdCache = array();
	protected static $moduleIdDataCache = array();

	protected static function getBasicModuleInfo($mixed) {
		$id = $name = NULL;
		if (is_numeric($mixed)) $id = $mixed;
		else $name = $mixed;
		$reload = false;
		if ($name) {
			if (!isset(self::$moduleNameIdCache[$name])) {$reload = true;}
		} else if ($id) {
			if (!isset(self::$moduleIdNameCache[$id])) {$reload = true;}
		}
		if ($reload) {
			global $adb;
			$result = $adb->pquery('SELECT tabid, name, ownedby FROM vtiger_tab', array());
			while ($row = $adb->fetch_array($result)) {
				self::$moduleIdNameCache[$row['tabid']] = $row;
				self::$moduleNameIdCache[$row['name']]  = $row;
			}
		}
		return $id ? self::$moduleIdNameCache[$id] : self::$moduleNameIdCache[$name];
	}

	static function getModuleData($mixed) {
		$id = $name = NULL;
		if (is_numeric($mixed)) $id = $mixed;
		else $name = (string)$mixed;
		$reload = false;

		if ($name && !isset(self::$moduleNameIdCache[$name])) {$reload = true;}
		else if ($id && !isset(self::$moduleIdNameCache[$id])) {$reload = true;}
		else {
			if (!$id) $id = self::$moduleNameIdCache[$name]['tabid'];
			if (!isset(self::$moduleIdDataCache[$id])) { $reload = true; }
		}

		if ($reload) {
			global $adb;
			$result = $adb->pquery('SELECT * FROM vtiger_tab', array());
			while ($row = $adb->fetch_array($result)) {
				self::$moduleIdNameCache[$row['tabid']] = $row;
				self::$moduleNameIdCache[$row['name']]  = $row;
				self::$moduleIdDataCache[$row['tabid']] = $row;
			}
			if ($name && isset(self::$moduleNameIdCache[$name])) {
				$id = self::$moduleNameIdCache[$name]['tabid'];
			}
		}
		return $id ? self::$moduleIdDataCache[$id] : NULL;
	}

	static function getModuleId($name) {
		$moduleInfo = self::getBasicModuleInfo($name);
		return $moduleInfo ? $moduleInfo['tabid'] : NULL;
	}

	static function getModuleName($id) {
		$moduleInfo = self::getBasicModuleInfo($id);
		return $moduleInfo ? $moduleInfo['name'] : NULL;
	}

	static function getModuleOwner($name) {
		$moduleInfo = self::getBasicModuleInfo($name);
		return $moduleInfo ? $moduleInfo['ownedby'] : NULL;
	}

	protected static $moduleEntityCache = array();

	static function getEntityModuleInfo($mixed) {
		$name = NULL;
		if (is_numeric($mixed)) $name = self::getModuleName($mixed);
		else $name = $mixed;

		if (empty(self::$moduleEntityCache)) {
			global $adb;
			$result = $adb->pquery('SELECT fieldname,modulename,tablename,entityidfield,entityidcolumn from vtiger_entityname', array());
			while ($row = $adb->fetch_array($result)) {
				self::$moduleEntityCache[$row['modulename']] = $row;
			}
		}

		return isset(self::$moduleEntityCache[$name])?
			self::$moduleEntityCache[$name] : NULL;
	}

	static function getEntityModuleSQLColumnString($mixed) {
		$data = array();
		$info = self::getEntityModuleInfo($mixed);
		if ($info) {
			$data['tablename'] = $info['tablename'];
			$fieldnames = $info['fieldname'];
			if (strpos(',', $fieldnames) !== false) {
				$fieldnames = sprintf("concat(%s)", implode(",' ',", explode(',', $fieldnames)));
			}
			$data['fieldname'] = $fieldnames;
		}
		return $data;
	}

	static function getEntityModuleInfoFieldsFormatted($mixed) {
		$info = self::getEntityModuleInfo($mixed);
		$fieldnames = $info ? $info['fieldname'] : NULL;
		if ($fieldnames && stripos($fieldnames, ',') !== false) {
			$fieldnames = explode(',', $fieldnames);
		}
		$info['fieldname'] = $fieldnames;
		return $info;
	}

	// MODULE RECORD
	protected static $crmRecordIdMetadataCache = array();

	protected static function getCRMRecordMetadata($mixedid) {
		global $adb;

		$multimode = is_array($mixedid);

		$ids = $multimode ? $mixedid : array($mixedid);
		$missing = array();
		foreach ($ids as $id) {
			if ($id && !isset(self::$crmRecordIdMetadataCache[$id])) {
				$missing[] = $id;
			}
		}

		if ($missing) {
			$sql = sprintf("SELECT crmid, setype, label FROM vtiger_crmentity WHERE %s", implode(' OR ', array_fill(0, count($missing), 'crmid=?')));
			$result = $adb->pquery($sql, $missing);
			while ($row = $adb->fetch_array($result)) {
				self::$crmRecordIdMetadataCache[$row['crmid']] = $row;
			}
		}

		$result = array();
		foreach ($ids as $id) {
			if (isset(self::$crmRecordIdMetadataCache[$id])) {
				$result[$id] = self::$crmRecordIdMetadataCache[$id];
			} else {
				$result[$id] = NULL;
			}
		}

		return $multimode?  $result : array_shift($result);
	}

	static function getCRMRecordType($id) {
		$metadata = self::getCRMRecordMetadata($id);
		return $metadata ? $metadata['setype'] : NULL;
	}

	static function getCRMRecordLabel($id, $default='') {
		$metadata = self::getCRMRecordMetadata($id);
		return $metadata ? $metadata['label'] : $default;
	}

	static function getUserRecordLabel($id, $default='') {
		$labels = self::getCRMRecordLabels('Users', $id);
		return isset($labels[$id])? $labels[$id] : $default;
	}

	static function getGroupRecordLabel($id, $default='') {
		$labels = self::getCRMRecordLabels('Groups', $id);
		return isset($labels[$id])? $labels[$id] : $default;
	}

	static function getCRMRecordLabels($module, $ids) {
		if (!is_array($ids)) $ids = array($ids);

		if ($module == 'Users' || $module == 'Groups') {
			// TODO Cache separately?
			return self::computeCRMRecordLabels($module, $ids);
		} else {
			$metadatas = self::getCRMRecordMetadata($ids);
			$result = array();
			foreach ($metadatas as $data) {
				$result[$data['crmid']] = $data['label'];
			}
			return $result;
		}
	}

	static function updateCRMRecordLabel($module, $id, $label) {
		global $adb;
		$labelInfo = self::computeCRMRecordLabels($module, $id);
		if ($labelInfo) {
			$label = decode_html($labelInfo[$id]);
			$adb->pquery('UPDATE vtiger_crmentity SET label=? WHERE crmid=?', array($label, $id));
			self::$crmRecordIdMetadataCache[$id] = array(
				'setype' => $module,
				'crmid'  => $id,
				'label'  => $labelInfo[$id]
			);
		}
	}

	static function getOwnerRecordLabel($id) {
		$result = self::getOwnerRecordLabels($id);
		return $result ? array_shift($result) : NULL;
	}

	static function getOwnerRecordLabels($ids) {
		if (!is_array($ids)) $ids = array($ids);

		$nameList = array();
		if ($ids) {
			$nameList = self::getCRMRecordLabels('Users', $ids);
			$groups = array();
			$diffIds = array_diff($ids, array_keys($nameList));
			if ($diffIds) {
				$groups = self::getCRMRecordLabels('Groups', array_values($diffIds));
			}
			if ($groups) {
				foreach ($groups as $id => $label) {
					$nameList[$id] = $label;
				}
			}
		}

		return $nameList;
	}

	static function computeCRMRecordLabels($module, $ids) {
		global $adb;

		if (!is_array($ids)) $ids = array($ids);

		if ($module == 'Events') {
			$module = 'Calendar';
		}

		if ($module) {
			$entityDisplay = array();

			if ($ids) {

				if ($module == 'Groups') {
					$metainfo = array('tablename' => 'vtiger_groups','entityidfield' => 'groupid','fieldname' => 'groupname');
				} else if ($module == 'DocumentFolders') { 
                                        $metainfo = array('tablename' => 'vtiger_attachmentsfolder','entityidfield' => 'folderid','fieldname' => 'foldername'); 
                                } else {
					$metainfo = self::getEntityModuleInfo($module);
				}

				$table = $metainfo['tablename'];
				$idcolumn = $metainfo['entityidfield'];
				$columns  = explode(',', $metainfo['fieldname']);

				// NOTE: Ignore field-permission check for non-admin (to compute record label).
				$columnString = count($columns) < 2? $columns[0] :
					sprintf("concat(%s)", implode(",' ',", $columns));

                $sql = sprintf('SELECT '. implode(',',$columns).', %s AS id FROM %s WHERE %s IN (%s)',
						 $idcolumn, $table, $idcolumn, generateQuestionMarks($ids));

				$result = $adb->pquery($sql, $ids);

				while ($row = $adb->fetch_array($result)) {
                    $labelValues = array();
                    foreach($columns as $columnName) {
                        $labelValues[] = $row[$columnName];
                    }
					$entityDisplay[$row['id']] = implode(' ',$labelValues);
				}
			}

			return $entityDisplay;
		}
	}

	protected static $groupIdNameCache = array();

	static function getGroupName($id) {
		global $adb;
		if (!self::$groupIdNameCache[$id]) {
			$result = $adb->pquery('SELECT groupid, groupname FROM vtiger_groups');
			while ($row = $adb->fetch_array($result)) {
				self::$groupIdNameCache[$row['groupid']] = $row['groupname'];
			}
		}
		$result = array();
		if (isset(self::$groupIdNameCache[$id])) {
			$result[] = decode_html(self::$groupIdNameCache[$id]);
			$result[] = $id;
		}
		return $result;
	}

	protected static $userIdNameCache = array();

	static function getUserName($id) {
		global $adb;
		if (!self::$userIdNameCache[$id]) {
			$result = $adb->pquery('SELECT id, user_name FROM vtiger_users');
			while ($row = $adb->fetch_array($result)) {
				self::$userIdNameCache[$row['id']] = $row['user_name'];
			}
		}
		return (isset(self::$userIdNameCache[$id])) ? self::$userIdNameCache[$id] : NULL;
	}

	protected static $moduleFieldInfoByNameCache = array();

	static function getModuleFieldInfos($mixed) {
		global $adb;

		$moduleInfo = self::getBasicModuleInfo($mixed);
		$module = $moduleInfo['name'];

                $no_of_fields = $adb->pquery('SELECT COUNT(fieldname) FROM vtiger_field WHERE tabid=?',array(self::getModuleId($module)));
                $fields_count = $adb->query_result($no_of_fields,0,'COUNT(fieldname)');
                
                $cached_fields_count = isset(self::$moduleFieldInfoByNameCache[$module]) ? count(self::$moduleFieldInfoByNameCache[$module]) : NULL;
                
		if ($module && (!isset(self::$moduleFieldInfoByNameCache[$module]) || ((int)$fields_count != (int)$cached_fields_count))) {
			$result =
				($module == 'Calendar')?
				$adb->pquery('SELECT * FROM vtiger_field WHERE tabid=? OR tabid=?', array(9, 16)) :
				$adb->pquery('SELECT * FROM vtiger_field WHERE tabid=?', array(self::getModuleId($module)));

			self::$moduleFieldInfoByNameCache[$module] = array();
			while ($row = $adb->fetch_array($result)) {
				self::$moduleFieldInfoByNameCache[$module][$row['fieldname']] = $row;
			}
		}
		return isset(self::$moduleFieldInfoByNameCache[$module]) ? self::$moduleFieldInfoByNameCache[$module] : NULL;
	}

	static function getModuleFieldInfoWithId($fieldid) {
		global $adb;
		$result = $adb->pquery('SELECT * FROM vtiger_field WHERE fieldid=?', array($fieldid));
		return ($adb->num_rows($result))? $adb->fetch_array($result) : NULL;
	}

	static function getModuleFieldInfo($moduleid, $mixed) {
		$field = NULL;
		if (empty($moduleid) && is_numeric($mixed)) {
			$field = self::getModuleFieldInfoWithId($mixed);
		} else {
			$fieldsInfo = self::getModuleFieldInfos($moduleid);
			if ($fieldsInfo) {
				if (is_numeric($mixed)) {
					foreach ($fieldsInfo as $name => $row) {
						if ($row['fieldid'] == $mixed) {
							$field = $row;
							break;
						}
					}
				} else {
					$field = isset($fieldsInfo[$mixed]) ? $fieldsInfo[$mixed] : NULL;
				}
			}
		}
		return $field;
	}

	static function getModuleFieldId($moduleid, $mixed, $onlyactive=true) {
		$field = self::getModuleFieldInfo($moduleid, $mixed, $onlyactive);

		if ($field) {
			if ($onlyactive && ($field['presence'] != '0' && $field['presence'] != '2')) {
				$field = NULL;
			}
		}
		return $field ? $field['fieldid'] : false;
	}


	// Utility
	static function formatDecimal($value){
		$fld_value = explode('.', $value);
		if(isset ($fld_value[1]) && $fld_value[1] != ''){
			$fld_value = rtrim($value, '0');
			$value = rtrim($fld_value, '.');
		}
		return $value;
	}

	static function fromHTML($string, $encode=true) {
		if (is_string($string)) {
			if (preg_match('/(script).*(\/script)/i', $string)) {
				$string = preg_replace(array('/</', '/>/', '/"/'), array('&lt;', '&gt;', '&quot;'), $string);
			}
		}
		return $string;
	}

	static function fromHTML_FCK($string) {
		if (is_string($string)) {
			if (preg_match('/(script).*(\/script)/i', $string)) {
				$string = str_replace('script', '', $string);
			}
		}
		return $string;
	}

	static function fromHTML_Popup($string, $encode = true) {
		$popup_toHtml = array(
			'"' => '&quot;',
			"'" => '&#039;',
		);
		//if($encode && is_string($string))$string = html_entity_decode($string, ENT_QUOTES);
		if ($encode && is_string($string)) {
			$string = addslashes(str_replace(array_values($popup_toHtml), array_keys($popup_toHtml), $string));
		}
		return $string;
	}

	static function br2nl($str) {
		$str = preg_replace("/(\r\n)/", "\\r\\n", $str);
		$str = preg_replace("/'/", " ", $str);
		$str = preg_replace("/\"/", " ", $str);
		return $str;
	}

	static function suppressHTMLTags($string) {
		return preg_replace(array('/</', '/>/', '/"/'), array('&lt;', '&gt;', '&quot;'), $string);
	}

	static function getInventoryTermsAndCondition() {
		global $adb;
		$sql = "select tandc from vtiger_inventory_tandc";
		$result = $adb->pquery($sql, array());
		$tandc = $adb->query_result($result, 0, "tandc");
		return $tandc;
	}

	static function initStorageFileDirectory() {
		$filepath = 'storage/';

		$year  = date('Y');
		$month = date('F');
		$day   = date('j');
		$week  = '';

		if (!is_dir($filepath . $year)) {
			//create new folder
			mkdir($filepath . $year);
		}

		if (!is_dir($filepath . $year . "/" . $month)) {
			//create new folder
			mkdir($filepath . "$year/$month");
		}

		if ($day > 0 && $day <= 7)
			$week = 'week1';
		elseif ($day > 7 && $day <= 14)
			$week = 'week2';
		elseif ($day > 14 && $day <= 21)
			$week = 'week3';
		elseif ($day > 21 && $day <= 28)
			$week = 'week4';
		else
			$week = 'week5';

		if (!is_dir($filepath . $year . "/" . $month . "/" . $week)) {
			//create new folder
			mkdir($filepath . "$year/$month/$week");
		}

		$filepath = $filepath . $year . "/" . $month . "/" . $week . "/";

		return $filepath;
	}

	static function validateImageMetadata($data, $short=true) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$ok = self::validateImageMetadata($value);
				if (!$ok) return false;
			}
		} else {
			if (stripos($data, $short ? "<?" : "<?php") !== false) { // suspicious dynamic content 
				return false;
			}
		}
		return true;
	}

	static function validateImage($file_details) {
		global $app_strings;
		$allowedImageFormats = array('jpeg', 'png', 'jpg', 'pjpeg', 'x-png', 'gif', 'bmp', 'xcf');
		
		$mimeTypesList = array_merge($allowedImageFormats, array('x-ms-bmp'));//bmp another format
		$mimeTypesList = array_merge($mimeTypesList, array('x-xcf'));//xcf another GIMP format
		$file_type_details = explode("/", $file_details['type']);
		$filetype = $file_type_details['1'];
		if ($filetype) {
			$filetype = strtolower($filetype);
		}

		$saveimage = 'true';
		if (!in_array($filetype, $allowedImageFormats)) {
			$saveimage = 'false';
		}

		//mime type check
		$mimeType = self::mime_content_type($file_details['tmp_name']);
		$mimeTypeContents = explode('/', $mimeType);
		if (!$file_details['size'] || strtolower($mimeTypeContents[0]) !== 'image' || !in_array($mimeTypeContents[1], $mimeTypesList)) {
			$saveimage = 'false';
		}

		//metadata check
		$shortTagSupported = ini_get('short_open_tag') ? true : false;
		if ($saveimage == 'true' && in_array($filetype, array('jpeg', 'jpg', 'pjpeg', 'xcf'))) {
			$exifdata = exif_read_data($file_details['tmp_name']);
			if ($exifdata && !self::validateImageMetadata($exifdata, $shortTagSupported)) {
				$saveimage = 'false';
			}
		}

		// Check for php code injection
		//crm-now removed because "<?" can be part of image
		// if ($saveimage == 'true') {
			// $imageContents = file_get_contents($file_details['tmp_name']);
			// if (stripos($imageContents, $shortTagSupported ? "<?" : "<?php") !== false) { // suspicious dynamic content.
				// $saveimage = 'false';
			// }
		// }
		return $saveimage;
	}

	static function getMergedDescription($description, $id, $parent_type) {
		global $current_user;
		$token_data_pair = explode('$', $description);
		$emailTemplate = new EmailTemplate($parent_type, $description, $id, $current_user);
		$description = $emailTemplate->getProcessedDescription();
		$tokenDataPair = explode('$', $description);
		$fields = Array();
		for ($i = 1; $i < count($token_data_pair); $i+=2) {
			$module = explode('-', $tokenDataPair[$i]);
			$fields[$module[0]][] = $module[1];
		}
		if (is_array($fields['custom']) && count($fields['custom']) > 0) {
			$description = self::getMergedDescriptionCustomVars($fields, $description);
		}
		//crm-now: handle optional blocks, skip it for Users as email send routine will call this function several times, Users first, and the description block will be gone aftre that
		if ($parent_type != 'Users') {
			$description = self::getMergedDescriptionBlocks($description);
		}
		return $description;
	}

	static function getMergedDescriptionCustomVars($fields, $description) {
		global $current_user;
		date_default_timezone_set($current_user->time_zone);
		$user_lang_arr = explode("_",$current_user->language);
		$user_lang_arr[1] = strtoupper ($user_lang_arr[1]);
		$user_lang = implode("_", $user_lang_arr);
		setlocale(LC_TIME, $user_lang, $user_lang.'UTF-8');
		foreach ($fields['custom'] as $columnname) {
			$token_data = '$custom-' . $columnname . '$';
			$token_value = '';
			switch ($columnname) {
				case 'currentdate': $token_value = strftime("%d. %B %Y");
					break;
				case 'currenttime': $token_value = strftime("%T (%Z)");
					break;
			}
			$description = str_replace($token_data, $token_value, $description);
		}
		return $description;
	}
	
	//crm-now: function to evaluate block conditions and remove block content if necessary
	static function getMergedDescriptionBlocks($description) {
		$pattern = '#\$?\[\[BLOCK(.*?)\]\](.*?)\[\[/BLOCK\]\]#s';
		$matches = array();
		preg_match_all($pattern, $description, $matches);
		
		if (!empty($matches[1])) {
			foreach ($matches[1] AS $index => $eval) {
				//replace encoded and non-encoded non-breakable space
				$tmp = str_replace('&nbsp;', '', $eval);
				$tmp = str_replace("\xc2\xa0", '', $tmp);
				$comparator = (strpos($tmp, '!=') !== false || strpos($tmp, '<>') !== false) ? '!=' : '==';
				$tmp = explode($comparator, $tmp);
				$tmp = array_map('trim', $tmp);
				//remove block if criteria isn't matched
				if (count($tmp) != 2 || ($comparator == '==' && strtolower(trim($tmp[0])) != strtolower(trim($tmp[1]))) || ($comparator == '!=' && strtolower(trim($tmp[0])) == strtolower(trim($tmp[1])))) {
					$description = str_replace($matches[0][$index]."<br>", '', $description);
					$description = str_replace($matches[0][$index]."<br />", '', $description);
					$description = str_replace($matches[0][$index]."<br>\n", '', $description);
					$description = str_replace($matches[0][$index]."<br />\n", '', $description);
					$description = str_replace($matches[0][$index], '', $description);
				}
			}
			//remove block part, only keep content
			$pattern = array('#\$?\[\[BLOCK(.*?)\]\]#', '#\[\[/BLOCK\]\]#');
			$description = preg_replace($pattern, '', $description);
		}
		return $description;
	}

	static function getSingleFieldValue($tablename, $fieldname, $idname, $id) {
		global $adb;
		$fieldval = $adb->query_result($adb->pquery("select $fieldname from $tablename where $idname = ?", array($id)), 0, $fieldname);
		return $fieldval;
	}

	static function getRecurringObjValue() {
		$recurring_data = array();
		if (isset($_REQUEST['recurringtype']) && $_REQUEST['recurringtype'] != null && $_REQUEST['recurringtype'] != '--None--') {
			if (!empty($_REQUEST['date_start'])) {
				$startDate = $_REQUEST['date_start'];
			}
			if (!empty($_REQUEST['calendar_repeat_limit_date'])) {
				$endDate = $_REQUEST['calendar_repeat_limit_date'];
                $recurring_data['recurringenddate'] = $endDate;
			} elseif (isset($_REQUEST['due_date']) && $_REQUEST['due_date'] != null) {
				$endDate = $_REQUEST['due_date'];
			}
			if (!empty($_REQUEST['time_start'])) {
				$startTime = $_REQUEST['time_start'];
			}
			if (!empty($_REQUEST['time_end'])) {
				$endTime = $_REQUEST['time_end'];
			}

			$recurring_data['startdate'] = $startDate;
			$recurring_data['starttime'] = $startTime;
			$recurring_data['enddate'] = $endDate;
			$recurring_data['endtime'] = $endTime;

			$recurring_data['type'] = $_REQUEST['recurringtype'];
			if ($_REQUEST['recurringtype'] == 'Weekly') {
				if (isset($_REQUEST['sun_flag']) && $_REQUEST['sun_flag'] != null)
					$recurring_data['sun_flag'] = true;
				if (isset($_REQUEST['mon_flag']) && $_REQUEST['mon_flag'] != null)
					$recurring_data['mon_flag'] = true;
				if (isset($_REQUEST['tue_flag']) && $_REQUEST['tue_flag'] != null)
					$recurring_data['tue_flag'] = true;
				if (isset($_REQUEST['wed_flag']) && $_REQUEST['wed_flag'] != null)
					$recurring_data['wed_flag'] = true;
				if (isset($_REQUEST['thu_flag']) && $_REQUEST['thu_flag'] != null)
					$recurring_data['thu_flag'] = true;
				if (isset($_REQUEST['fri_flag']) && $_REQUEST['fri_flag'] != null)
					$recurring_data['fri_flag'] = true;
				if (isset($_REQUEST['sat_flag']) && $_REQUEST['sat_flag'] != null)
					$recurring_data['sat_flag'] = true;
			}
			elseif ($_REQUEST['recurringtype'] == 'Monthly') {
				if (isset($_REQUEST['repeatMonth']) && $_REQUEST['repeatMonth'] != null)
					$recurring_data['repeatmonth_type'] = $_REQUEST['repeatMonth'];
				if ($recurring_data['repeatmonth_type'] == 'date') {
					if (isset($_REQUEST['repeatMonth_date']) && $_REQUEST['repeatMonth_date'] != null)
						$recurring_data['repeatmonth_date'] = $_REQUEST['repeatMonth_date'];
					else
						$recurring_data['repeatmonth_date'] = 1;
				}
				elseif ($recurring_data['repeatmonth_type'] == 'day') {
					$recurring_data['repeatmonth_daytype'] = $_REQUEST['repeatMonth_daytype'];
					switch ($_REQUEST['repeatMonth_day']) {
						case 0 :
							$recurring_data['sun_flag'] = true;
							break;
						case 1 :
							$recurring_data['mon_flag'] = true;
							break;
						case 2 :
							$recurring_data['tue_flag'] = true;
							break;
						case 3 :
							$recurring_data['wed_flag'] = true;
							break;
						case 4 :
							$recurring_data['thu_flag'] = true;
							break;
						case 5 :
							$recurring_data['fri_flag'] = true;
							break;
						case 6 :
							$recurring_data['sat_flag'] = true;
							break;
					}
				}
			}
			if (isset($_REQUEST['repeat_frequency']) && $_REQUEST['repeat_frequency'] != null)
				$recurring_data['repeat_frequency'] = $_REQUEST['repeat_frequency'];

			$recurObj = RecurringType::fromUserRequest($recurring_data);
			return $recurObj;
		}
	}

	static function getTicketComments($ticketid) {
		global $adb;
		$moduleName = getSalesEntityType($ticketid);
		$commentlist = '';
		$sql = "SELECT commentcontent FROM vtiger_modcomments WHERE related_to = ?";
		$result = $adb->pquery($sql, array($ticketid));
		for ($i = 0; $i < $adb->num_rows($result); $i++) {
			$comment = $adb->query_result($result, $i, 'commentcontent');
			if ($comment != '') {
				$commentlist .= '<br><br>' . $comment;
			}
		}
		if ($commentlist != '')
			$commentlist = '<br><br>' . getTranslatedString("The comments are", $moduleName) . ' : ' . $commentlist;
		return $commentlist;
	}

	static function generateRandomPassword($length = 12) {
		$salt = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
		$sLength = strlen($salt) -1;
		$pass = '';
		for ($i = 0; $i < $length; $i++) {
			$pass .= substr($salt, rand(0, $sLength), 1);
		}
		return $pass;
	}

	static function getTagCloudView($id = "") {
		global $adb;
		if ($id == '') {
			$tag_cloud_status = 1;
		} else {
			$query = "select visible from vtiger_homestuff where userid=? and stufftype='Tag Cloud'";
			$res = $adb->pquery($query, array($id));
			$tag_cloud_status = $adb->query_result($res, 0, 'visible');
		}

		if ($tag_cloud_status == 0) {
			$tag_cloud_view = 'true';
		} else {
			$tag_cloud_view = 'false';
		}
		return $tag_cloud_view;
	}

	static function transformFieldTypeOfData($table_name, $column_name, $type_of_data) {
		$field = $table_name . ":" . $column_name;
		//Add the field details in this array if you want to change the advance filter field details

		static $new_field_details = Array(
			//Contacts Related Fields
			"vtiger_contactdetails:accountid" => "V",
			"vtiger_contactsubdetails:birthday" => "D",
			"vtiger_contactdetails:email" => "V",
			"vtiger_contactdetails:secondaryemail" => "V",
			//Potential Related Fields
			"vtiger_potential:campaignid" => "V",
			//Account Related Fields
			"vtiger_account:parentid" => "V",
			"vtiger_account:email1" => "V",
			"vtiger_account:email2" => "V",
			//Lead Related Fields
			"vtiger_leaddetails:email" => "V",
			"vtiger_leaddetails:secondaryemail" => "V",
			//Documents Related Fields
			"vtiger_senotesrel:crmid" => "V",
			//Calendar Related Fields
			"vtiger_seactivityrel:crmid" => "V",
			"vtiger_seactivityrel:contactid" => "V",
			"vtiger_recurringevents:recurringtype" => "V",
			//HelpDesk Related Fields
			"vtiger_troubletickets:parent_id" => "V",
			"vtiger_troubletickets:product_id" => "V",
			//Product Related Fields
			"vtiger_products:discontinued" => "C",
			"vtiger_products:vendor_id" => "V",
			"vtiger_products:parentid" => "V",
			//Faq Related Fields
			"vtiger_faq:product_id" => "V",
			//Vendor Related Fields
			"vtiger_vendor:email" => "V",
			//Quotes Related Fields
			"vtiger_quotes:potentialid" => "V",
			"vtiger_quotes:inventorymanager" => "V",
			"vtiger_quotes:accountid" => "V",
			//Purchase Order Related Fields
			"vtiger_purchaseorder:vendorid" => "V",
			"vtiger_purchaseorder:contactid" => "V",
			//SalesOrder Related Fields
			"vtiger_salesorder:potentialid" => "V",
			"vtiger_salesorder:quoteid" => "V",
			"vtiger_salesorder:contactid" => "V",
			"vtiger_salesorder:accountid" => "V",
			//Invoice Related Fields
			"vtiger_invoice:salesorderid" => "V",
			"vtiger_invoice:contactid" => "V",
			"vtiger_invoice:accountid" => "V",
			//Campaign Related Fields
			"vtiger_campaign:product_id" => "V",
			//Related List Entries(For Report Module)
			"vtiger_activityproductrel:activityid" => "V",
			"vtiger_activityproductrel:productid" => "V",
			"vtiger_campaigncontrel:campaignid" => "V",
			"vtiger_campaigncontrel:contactid" => "V",
			"vtiger_campaignleadrel:campaignid" => "V",
			"vtiger_campaignleadrel:leadid" => "V",
			"vtiger_cntactivityrel:contactid" => "V",
			"vtiger_cntactivityrel:activityid" => "V",
			"vtiger_contpotentialrel:contactid" => "V",
			"vtiger_contpotentialrel:potentialid" => "V",
			"vtiger_pricebookproductrel:pricebookid" => "V",
			"vtiger_pricebookproductrel:productid" => "V",
			"vtiger_seactivityrel:crmid" => "V",
			"vtiger_seactivityrel:activityid" => "V",
			"vtiger_senotesrel:crmid" => "V",
			"vtiger_senotesrel:notesid" => "V",
			"vtiger_seproductsrel:crmid" => "V",
			"vtiger_seproductsrel:productid" => "V",
			"vtiger_seticketsrel:crmid" => "V",
			"vtiger_seticketsrel:ticketid" => "V",
			"vtiger_vendorcontactrel:vendorid" => "V",
			"vtiger_vendorcontactrel:contactid" => "V",
			"vtiger_pricebook:currency_id" => "V",
		);

		//If the Fields details does not match with the array, then we return the same typeofdata
		if (isset($new_field_details[$field])) {
			$type_of_data = $new_field_details[$field];
		}
		return $type_of_data;
	}

	static function getPickListValuesFromTableForRole($tablename, $roleid) {
		global $adb;
		$query = "SELECT $tablename FROM vtiger_$tablename INNER JOIN vtiger_role2picklist ON vtiger_role2picklist.picklistvalueid = vtiger_$tablename.picklist_valueid WHERE roleid=? AND picklistid IN(SELECT picklistid FROM vtiger_picklist) ORDER BY ";
		$q1 = $query."vtiger_$tablename.sortorderid";
		$q2 = $query."vtiger_role2picklist.sortid";
		$result = $adb->pquery($q1, array($roleid));
		if (!$result) $result = $adb->pquery($q2, array($roleid));
		$fldVal = Array();
		while ($row = $adb->fetch_array($result)) {
			$fldVal [] = $row[$tablename];
		}
		return $fldVal;
	}

	static function getActivityType($id) {
		global $adb;
		$query = "select activitytype from vtiger_activity where activityid=?";
		$res = $adb->pquery($query, array($id));
		$activity_type = $adb->query_result($res, 0, "activitytype");
		return $activity_type;
	}

	static function getInvoiceStatus($id) {
		global $adb;
		$result = $adb->pquery("SELECT invoicestatus FROM vtiger_invoice where invoiceid=?", array($id));
		$invoiceStatus = $adb->query_result($result,0,'invoicestatus');
		return $invoiceStatus;
	}

	static function mkCountQuery($query) {
		// Remove all the \n, \r and white spaces to keep the space between the words consistent.
		// This is required for proper pattern matching for words like ' FROM ', 'ORDER BY', 'GROUP BY' as they depend on the spaces between the words.
		$query = preg_replace("/[\n\r\s]+/"," ",$query);

		//Strip of the current SELECT fields and replace them by "select count(*) as count"
		// Space across FROM has to be retained here so that we do not have a clash with string "from" found in select clause
		$query = "SELECT count(*) AS count ".substr($query, stripos($query,' FROM '),strlen($query));

		//Strip of any "GROUP BY" clause
		if(stripos($query,'GROUP BY') > 0)
		$query = substr($query, 0, stripos($query,'GROUP BY'));

		//Strip of any "ORDER BY" clause
		if(stripos($query,'ORDER BY') > 0)
		$query = substr($query, 0, stripos($query,'ORDER BY'));

		return $query;
	}

    /** Function to get unitprice for a given product id
    * @param $productid -- product id :: Type integer
    * @returns $up -- up :: Type string
    */
    static function getUnitPrice($productid, $module='Products') {
        $adb = PearDatabase::getInstance();
        if($module == 'Services') {
            $query = "select unit_price from vtiger_service where serviceid=?";
        } else {
            $query = "select unit_price from vtiger_products where productid=?";
        }
        $result = $adb->pquery($query, array($productid));
        $unitpice = $adb->query_result($result,0,'unit_price');
        return $unitpice;
    }


    /**
    * Function to fetch the list of vtiger_groups from group vtiger_table
    * Takes no value as input
    * returns the query result set object
    */
    static function get_group_options() {
        global $adb, $noof_group_rows;
        $sql = "select groupname,groupid from vtiger_groups";
        $result = $adb->pquery($sql, array());
        $noof_group_rows = $adb->num_rows($result);
        return $result;
    }

	/**
	* Function to determine mime type of file. 
	* Compatible with mime_magic or fileinfo php extension.
	*/
	static function mime_content_type($filename) {
		$type = null;
		if (function_exists('mime_content_type')) {
			$type = mime_content_type($filename);
		} else if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$type = finfo_file($finfo, $filename);
			finfo_close($finfo);
		} else {
			throw new Exception('mime_magic or fileinfo extension required.');
		}
		return $type;
	}
   
     /**
	 * Check the file MIME Type
	 * @param $targetFile  Filepath to validate
	 * @param  $claimedMime Array of bad file extenstions
	 */
    static function verifyClaimedMIME($targetFile,$claimedMime) {
    	$fileMimeContentType= self::mime_content_type($targetFile);
    	if (in_array(strtolower($fileMimeContentType), $claimedMime)) {
     		return false; 
   		}
    	return true;
	}

	/*
	 * Function to generate encrypted password.
	 */
	static function generateEncryptedPassword($password, $mode='CRYPT') {

		if ($mode == 'MD5') return md5($password);

		if ($mode == 'CRYPT') {
			$salt = null;
			if (function_exists('password_hash')) { // php 5.5+
				return password_hash($password,CRYPT_BLOWFISH); // CRYPT_BLOWFISH!
			} else {
				$salt = '$2y$11$'.str_replace("+",".",substr(base64_encode(openssl_random_pseudo_bytes(17)),0,22));
			}
			return crypt($password, $salt);
		}

		throw new Exception('Invalid encryption mode: '.$mode);
	}

	/*
	 * Function to compare encrypted password.
	 */
	static function compareEncryptedPassword($plainText, $encryptedPassword, $mode='CRYPT') {
		$reEncryptedPassword = null;
		switch ($mode) {
                        case 'CRYPT': {
                           if (function_exists('password_hash')) {
                           	return password_verify($plainText, $encryptedPassword);
                           } else {
                           	$reEncryptedPassword = crypt($plainText, $encryptedPassword);
                           }
                        } ; break;
			case 'MD5'  : $reEncryptedPassword = md5($plainText);	break;
			default     : $reEncryptedPassword = $plainText;		break;
		}
		return ($reEncryptedPassword == $encryptedPassword);
	}
	
	/*
	 * Function to sort arrays (mostly picklist) by translated label values
	 */
	static function sortByLabel(&$toSort, $moduleName = '') {
		uasort($toSort, function($a, $b) use ($moduleName) {
			if (method_exists($a, 'get')) {
				$aLabel = $a->get('label');
				$bLabel = $b->get('label');
			} elseif (property_exists($a, 'label')) {
				$aLabel = $a->label;
				$bLabel = $b->label;
			} else {
				$aLabel = $a;
				$bLabel = $b;
			}
			
			$aLabel = vtranslate($aLabel, $moduleName);
			$bLabel = vtranslate($bLabel, $moduleName);
			
			return strcasecmp($aLabel, $bLabel);
		});
	}
	
	/*
	 * Function to format phone numbers
	 */
	static function cleanupPhoneNumber($phoneNumber) {
		$internatPrefixes = array(1,7,20,27,30,31,32,33,34,36,39,40,41,42,43,44,45,46,47,48,49,51,52,53,54,55,56,57,58,60,61,62,63,64,65,66,81,82,84,86,90,91,92,93,94,95,98,
			212,213,216,218,220,221,222,223,224,226,227,228,229,230,231,232,233,234,235,236,237,238,239,240,241,244,245,246,247,248,249,250,251,252,253,254,255,256,257,258,
			260,261,262,263,264,265,266,267,268,269,290,291,297,298,299,350,351,352,353,354,355,356,357,358,359,370,371,372,373,374,375,376,377,378,379,380,381,385,386,387,
			389,420,421,423,500,501,502,503,504,505,506,507,508,509,590,591,592,593,594,595,596,597,598,599,670,673,674,675,676,677,678,679,680,681,682,683,684,685,686,687,
			688,689,690,691,692,850,852,853,855,856,880,886,960,961,962,963,964,965,966,967,968,970,971,972,973,974,975,976,977,992,993,994,995,996,998);
		$nationalPrefixes = array(700,800,900,201,202,203,2041,2043,2045,2051,2052,2053,2054,2056,2058,2064,2065,2066,208,209,2102,2103,2104,211,212,2129,2131,2132,2133,2137,214,2150,2151,2152,2153,2154,
			2156,2157,2158,2159,2161,2162,2163,2164,2165,2166,2171,2173,2174,2175,2181,2182,2183,2191,2192,2193,2195,2196,2202,2203,2204,2205,2206,2207,2208,221,2222,2223,2224,2225,2226,
			2227,2228,2232,2233,2234,2235,2236,2237,2238,2241,2242,2243,2244,2245,2246,2247,2248,2251,2252,2253,2254,2255,2256,2257,2261,2262,2263,2264,2265,2266,2267,2268,2269,2271,2272,
			2273,2274,2275,228,2291,2292,2293,2294,2295,2296,2297,2301,2302,2303,2304,2305,2306,2307,2308,2309,231,2323,2324,2325,2327,2330,2331,2332,2333,2334,2335,2336,2337,2338,2339,
			234,2351,2352,2353,2354,2355,2357,2358,2359,2360,2361,2362,2363,2364,2365,2366,2367,2368,2369,2371,2372,2373,2374,2375,2377,2378,2379,2381,2382,2383,2384,2385,2387,2388,2389,
			2391,2392,2393,2394,2395,2401,2402,2403,2404,2405,2406,2407,2408,2409,241,2421,2422,2423,2424,2425,2426,2427,2428,2429,2431,2432,2433,2434,2435,2436,2440,2441,2443,2444,2445,
			2446,2447,2448,2449,2451,2452,2453,2454,2455,2456,2461,2462,2463,2464,2465,2471,2472,2473,2474,2482,2484,2485,2486,2501,2502,2504,2505,2506,2507,2508,2509,251,2520,2521,2522,
			2523,2524,2525,2526,2527,2528,2529,2532,2533,2534,2535,2536,2538,2541,2542,2543,2545,2546,2547,2548,2551,2552,2553,2554,2555,2556,2557,2558,2561,2562,2563,2564,2565,2566,2567,
			2568,2571,2572,2573,2574,2575,2581,2582,2583,2584,2585,2586,2587,2588,2590,2591,2592,2593,2594,2595,2596,2597,2598,2599,2601,2602,2603,2604,2605,2606,2607,2608,261,2620,2621,
			2622,2623,2624,2625,2626,2627,2628,2630,2631,2632,2633,2634,2635,2636,2637,2638,2639,2641,2642,2643,2644,2645,2646,2647,2651,2652,2653,2654,2655,2656,2657,2661,2662,2663,2664,
			2666,2667,2671,2672,2673,2674,2675,2676,2677,2678,2680,2681,2682,2683,2684,2685,2686,2687,2688,2689,2691,2692,2693,2694,2695,2696,2697,271,2721,2722,2723,2724,2725,2732,2733,
			2734,2735,2736,2737,2738,2739,2741,2742,2743,2744,2745,2747,2750,2751,2752,2753,2754,2755,2758,2759,2761,2762,2763,2764,2770,2771,2772,2773,2774,2775,2776,2777,2778,2779,2801,
			2802,2803,2804,281,2821,2822,2823,2824,2825,2826,2827,2828,2831,2832,2833,2834,2835,2836,2837,2838,2839,2841,2842,2843,2844,2845,2850,2851,2852,2853,2855,2856,2857,2858,2859,
			2861,2862,2863,2864,2865,2866,2867,2871,2872,2873,2874,2902,2903,2904,2905,291,2921,2922,2923,2924,2925,2927,2928,2931,2932,2933,2934,2935,2937,2938,2941,2942,2943,2944,2945,
			2947,2948,2951,2952,2953,2954,2955,2957,2958,2961,2962,2963,2964,2971,2972,2973,2974,2975,2977,2981,2982,2983,2984,2985,2991,2992,2993,2994,30,3301,3302,3303,3304,33051,33052,
			33053,33054,33055,33056,3306,3307,33080,33082,33083,33084,33085,33086,33087,33088,33089,33093,33094,331,33200,33201,33202,33203,33204,33205,33206,33207,33208,33209,3321,3322,33230,33231,33232,33233,33234,
			33235,33237,33238,33239,3327,3328,3329,3331,3332,33331,33332,33333,33334,33335,33336,33337,33338,3334,3335,33361,33362,33363,33364,33365,33366,33367,33368,33369,3337,3338,33393,33394,33395,33396,33397,
			33398,3341,3342,33432,33433,33434,33435,33436,33437,33438,33439,3344,33451,33452,33454,33456,33457,33458,3346,33470,33472,33473,33474,33475,33476,33477,33478,33479,335,33601,33602,33603,33604,33605,33606,
			33607,33608,33609,3361,3362,33631,33632,33633,33634,33635,33636,33637,33638,3364,33652,33653,33654,33655,33656,33657,3366,33671,33672,33673,33674,33675,33676,33677,33678,33679,33701,33702,33703,33704,33708,
			3371,3372,33731,33732,33733,33734,33741,33742,33743,33744,33745,33746,33747,33748,3375,33760,33762,33763,33764,33765,33766,33767,33768,33769,3377,3378,3379,3381,3382,33830,33831,33832,33833,33834,33835,
			33836,33837,33838,33839,33841,33843,33844,33845,33846,33847,33848,33849,3385,3386,33870,33872,33873,33874,33875,33876,33877,33878,3391,33920,33921,33922,33923,33924,33925,33926,33927,33928,33929,33931,33932,
			33933,3394,3395,33962,33963,33964,33965,33966,33967,33968,33969,33970,33971,33972,33973,33974,33975,33976,33977,33978,33979,33981,33982,33983,33984,33986,33989,340,341,34202,34203,34204,34205,34206,34207,
			34208,3421,34221,34222,34223,34224,3423,34241,34242,34243,34244,3425,34261,34262,34263,34291,34292,34293,34294,34295,34296,34297,34298,34299,3431,34321,34322,34324,34325,34327,34328,3433,34341,34342,34343,
			34344,34345,34346,34347,34348,3435,34361,34362,34363,34364,3437,34381,34382,34383,34384,34385,34386,3441,34422,34423,34424,34425,34426,3443,34441,34443,34444,34445,34446,3445,34461,34462,34463,34464,34465,
			34466,34467,3447,3448,34491,34492,34493,34494,34495,34496,34497,34498,345,34600,34601,34602,34603,34604,34605,34606,34607,34609,3461,3462,34632,34633,34635,34636,34637,34638,34639,3464,34651,34652,34653,
			34654,34656,34658,34659,3466,34671,34672,34673,34691,34692,3471,34721,34722,3473,34741,34742,34743,34745,34746,3475,3476,34771,34772,34773,34774,34775,34776,34779,34781,34782,34783,34785,34901,34903,34904,
			34905,34906,34907,34909,3491,34920,34921,34922,34923,34924,34925,34926,34927,34928,34929,3493,3494,34953,34954,34955,34956,3496,34973,34975,34976,34977,34978,34979,3501,35020,35021,35022,35023,35024,35025,
			35026,35027,35028,35032,35033,3504,35052,35053,35054,35055,35056,35057,35058,351,35200,35201,35202,35203,35204,35205,35206,35207,35208,35209,3521,3522,3523,35240,35241,35242,35243,35244,35245,35246,35247,
			35248,35249,3525,35263,35264,35265,35266,35267,35268,3528,3529,3531,35322,35323,35324,35325,35326,35327,35329,3533,35341,35342,35343,3535,35361,35362,35363,35364,35365,3537,35383,35384,35385,35386,35387,
			35388,35389,3541,3542,35433,35434,35435,35436,35439,3544,35451,35452,35453,35454,35455,35456,3546,35471,35472,35473,35474,35475,35476,35477,35478,355,35600,35601,35602,35603,35604,35605,35606,35607,35608,
			35609,3561,3562,3563,3564,35691,35692,35693,35694,35695,35696,35697,35698,3571,35722,35723,35724,35725,35726,35727,35728,3573,3574,35751,35752,35753,35754,35755,35756,3576,35771,35772,35773,35774,35775,
			3578,35792,35793,35795,35796,35797,3581,35820,35822,35823,35825,35826,35827,35828,35829,3583,35841,35842,35843,35844,3585,3586,35872,35873,35874,35875,35876,35877,3588,35891,35892,35893,35894,35895,3591,
			3592,35930,35931,35932,35933,35934,35935,35936,35937,35938,35939,3594,35951,35952,35953,35954,35955,3596,35971,35973,35974,35975,3601,36020,36021,36022,36023,36024,36025,36026,36027,36028,36029,3603,36041,
			36042,36043,3605,3606,36071,36072,36074,36075,36076,36077,36081,36082,36083,36084,36085,36087,361,36200,36201,36202,36203,36204,36205,36206,36207,36208,36209,3621,3622,3623,3624,36252,36253,36254,36255,
			36256,36257,36258,36259,3628,3629,3631,3632,36330,36331,36332,36333,36334,36335,36336,36337,36338,3634,3635,3636,36370,36371,36372,36373,36374,36375,36376,36377,36378,36379,3641,36421,36422,36423,36424,
			36425,36426,36427,36428,3643,3644,36450,36451,36452,36453,36454,36458,36459,36461,36462,36463,36464,36465,3647,36481,36482,36483,36484,365,36601,36602,36603,36604,36605,36606,36607,36608,3661,36621,36622,
			36623,36624,36625,36626,36628,3663,36640,36642,36643,36644,36645,36646,36647,36648,36649,36651,36652,36653,36691,36692,36693,36694,36695,36701,36702,36703,36704,36705,3671,3672,36730,36731,36732,36733,36734,
			36735,36736,36737,36738,36739,36741,36742,36743,36744,3675,36761,36762,36764,36766,3677,36781,36782,36783,36784,36785,3679,3681,3682,3683,36840,36841,36842,36843,36844,36845,36846,36847,36848,36849,3685,
			3686,36870,36871,36873,36874,36875,36878,3691,36920,36921,36922,36923,36924,36925,36926,36927,36928,36929,3693,36940,36941,36943,36944,36945,36946,36947,36948,36949,3695,36961,36962,36963,36964,36965,36966,
			36967,36968,36969,371,37200,37202,37203,37204,37206,37207,37208,37209,3721,3722,3723,3724,3725,3726,3727,37291,37292,37293,37294,37295,37296,37297,37298,3731,37320,37321,37322,37323,37324,37325,37326,
			37327,37328,37329,3733,37341,37342,37343,37344,37346,37347,37348,37349,3735,37360,37361,37362,37363,37364,37365,37366,37367,37368,37369,3737,37381,37382,37383,37384,3741,37421,37422,37423,37430,37431,37432,
			37433,37434,37435,37436,37437,37438,37439,3744,3745,37462,37463,37464,37465,37467,37468,375,37600,37601,37602,37603,37604,37605,37606,37607,37608,37609,3761,3762,3763,3764,3765,3771,3772,3773,3774,
			37752,37754,37755,37756,37757,381,38201,38202,38203,38204,38205,38206,38207,38208,38209,3821,38220,38221,38222,38223,38224,38225,38226,38227,38228,38229,38231,38232,38233,38234,38292,38293,38294,38295,38296,
			38297,38300,38301,38302,38303,38304,38305,38306,38307,38308,38309,3831,38320,38321,38322,38323,38324,38325,38326,38327,38328,38331,38332,38333,38334,3834,38351,38352,38353,38354,38355,38356,3836,38370,38371,
			38372,38373,38374,38375,38376,38377,38378,38379,3838,38391,38392,38393,3841,38422,38423,38424,38425,38426,38427,38428,38429,3843,3844,38450,38451,38452,38453,38454,38455,38456,38457,38458,38459,38461,38462,
			38464,38466,3847,38481,38482,38483,38484,38485,38486,38488,385,3860,3861,3863,3865,3866,3867,3868,3869,3871,38720,38721,38722,38723,38724,38725,38726,38727,38728,38729,38731,38732,38733,38735,38736,
			38737,38738,3874,38750,38751,38752,38753,38754,38755,38756,38757,38758,38759,3876,3877,38780,38781,38782,38783,38784,38785,38787,38788,38789,38791,38792,38793,38794,38796,38797,3881,38821,38822,38823,38824,
			38825,38826,38827,38828,3883,38841,38842,38843,38844,38845,38847,38848,38850,38851,38852,38853,38854,38855,38856,38858,38859,3886,38871,38872,38873,38874,38875,38876,39000,39001,39002,39003,39004,39005,39006,
			39007,39008,39009,3901,3902,39030,39031,39032,39033,39034,39035,39036,39037,39038,39039,3904,39050,39051,39052,39053,39054,39055,39056,39057,39058,39059,39061,39062,3907,39080,39081,39082,39083,39084,39085,
			39086,39087,39088,39089,3909,391,39200,39201,39202,39203,39204,39205,39206,39207,39208,39209,3921,39221,39222,39223,39224,39225,39226,3923,39241,39242,39243,39244,39245,39246,39247,39248,3925,39262,39263,
			39264,39265,39266,39267,39268,3928,39291,39292,39293,39294,39295,39296,39297,39298,3931,39320,39321,39322,39323,39324,39325,39327,39328,39329,3933,39341,39342,39343,39344,39345,39346,39347,39348,39349,3935,
			39361,39362,39363,39364,39365,39366,3937,39382,39383,39384,39386,39387,39388,39389,39390,39391,39392,39393,39394,39395,39396,39397,39398,39399,39400,39401,39402,39403,39404,39405,39406,39407,39408,39409,3941,
			39421,39422,39423,39424,39425,39426,39427,39428,3943,3944,39451,39452,39453,39454,39455,39456,39457,39458,39459,3946,3947,39481,39482,39483,39484,39485,39487,39488,39489,3949,395,39600,39601,39602,39603,
			39604,39605,39606,39607,39608,3961,3962,3963,3964,3965,3966,3967,3968,3969,3971,39721,39722,39723,39724,39726,39727,39728,3973,39740,39741,39742,39743,39744,39745,39746,39747,39748,39749,39751,39752,
			39753,39754,3976,39771,39772,39773,39774,39775,39776,39777,39778,39779,3981,39820,39821,39822,39823,39824,39825,39826,39827,39828,39829,39831,39832,39833,3984,39851,39852,39853,39854,39855,39856,39857,39858,
			39859,39861,39862,39863,3987,39881,39882,39883,39884,39885,39886,39887,39888,39889,3991,39921,39922,39923,39924,39925,39926,39927,39928,39929,39931,39932,39933,39934,3994,39951,39952,39953,39954,39955,39956,
			39957,39959,3996,39971,39972,39973,39975,39976,39977,39978,3998,39991,39992,39993,39994,39995,39996,39997,39998,39999,40,4101,4102,4103,4104,4105,4106,4107,4108,4109,4120,4121,4122,4123,4124,
			4125,4126,4127,4128,4129,4131,4132,4133,4134,4135,4136,4137,4138,4139,4140,4141,4142,4143,4144,4146,4148,4149,4151,4152,4153,4154,4155,4156,4158,4159,4161,4162,4163,4164,4165,
			4166,4167,4168,4169,4171,4172,4173,4174,4175,4176,4177,4178,4179,4180,4181,4182,4183,4184,4185,4186,4187,4188,4189,4191,4192,4193,4194,4195,4202,4203,4204,4205,4206,4207,4208,
			4209,421,4221,4222,4223,4224,4230,4231,4232,4233,4234,4235,4236,4237,4238,4239,4240,4241,4242,4243,4244,4245,4246,4247,4248,4249,4251,4252,4253,4254,4255,4256,4257,4258,4260,
			4261,4262,4263,4264,4265,4266,4267,4268,4269,4271,4272,4273,4274,4275,4276,4277,4281,4282,4283,4284,4285,4286,4287,4288,4289,4292,4293,4294,4295,4296,4297,4298,4302,4303,4305,
			4307,4308,431,4320,4321,4322,4323,4324,4326,4327,4328,4329,4330,4331,4332,4333,4334,4335,4336,4337,4338,4339,4340,4342,4343,4344,4346,4347,4348,4349,4351,4352,4353,4354,4355,
			4356,4357,4358,4361,4362,4363,4364,4365,4366,4367,4371,4372,4381,4382,4383,4384,4385,4392,4393,4394,4401,4402,4403,4404,4405,4406,4407,4408,4409,441,4421,4422,4423,4425,4426,
			4431,4432,4433,4434,4435,4441,4442,4443,4444,4445,4446,4447,4451,4452,4453,4454,4455,4456,4458,4461,4462,4463,4464,4465,4466,4467,4468,4469,4471,4472,4473,4474,4475,4477,4478,
			4479,4480,4481,4482,4483,4484,4485,4486,4487,4488,4489,4491,4492,4493,4494,4495,4496,4497,4498,4499,4501,4502,4503,4504,4505,4506,4508,4509,451,4521,4522,4523,4524,4525,4526,
			4527,4528,4529,4531,4532,4533,4534,4535,4536,4537,4539,4541,4542,4543,4544,4545,4546,4547,4550,4551,4552,4553,4554,4555,4556,4557,4558,4559,4561,4562,4563,4564,4602,4603,4604,
			4605,4606,4607,4608,4609,461,4621,4622,4623,4624,4625,4626,4627,4630,4631,4632,4633,4634,4635,4636,4637,4638,4639,4641,4642,4643,4644,4646,4651,4661,4662,4663,4664,4665,4666,
			4667,4668,4671,4672,4673,4674,4681,4682,4683,4684,4702,4703,4704,4705,4706,4707,4708,471,4721,4722,4723,4724,4725,4731,4732,4733,4734,4735,4736,4737,4740,4741,4742,4743,4744,
			4745,4746,4747,4748,4749,4751,4752,4753,4754,4755,4756,4757,4758,4761,4762,4763,4764,4765,4766,4767,4768,4769,4770,4771,4772,4773,4774,4775,4776,4777,4778,4779,4791,4792,4793,
			4794,4795,4796,4802,4803,4804,4805,4806,481,4821,4822,4823,4824,4825,4826,4827,4828,4829,4830,4832,4833,4834,4835,4836,4837,4838,4839,4841,4842,4843,4844,4845,4846,4847,4848,
			4849,4851,4852,4853,4854,4855,4856,4857,4858,4859,4861,4862,4863,4864,4865,4871,4872,4873,4874,4875,4876,4877,4881,4882,4883,4884,4885,4892,4893,4902,4903,491,4920,4921,4922,
			4923,4924,4925,4926,4927,4928,4929,4931,4932,4933,4934,4935,4936,4938,4939,4941,4942,4943,4944,4945,4946,4947,4948,4950,4951,4952,4953,4954,4955,4956,4957,4958,4959,4961,4962,
			4963,4964,4965,4966,4967,4968,4971,4972,4973,4974,4975,4976,4977,5021,5022,5023,5024,5025,5026,5027,5028,5031,5032,5033,5034,5035,5036,5037,5041,5042,5043,5044,5045,5051,5052,
			5053,5054,5055,5056,5060,5062,5063,5064,5065,5066,5067,5068,5069,5071,5072,5073,5074,5082,5083,5084,5085,5086,5101,5102,5103,5105,5108,5109,511,5121,5123,5126,5127,5128,5129,
			5130,5131,5132,5135,5136,5137,5138,5139,5141,5142,5143,5144,5145,5146,5147,5148,5149,5151,5152,5153,5154,5155,5156,5157,5158,5159,5161,5162,5163,5164,5165,5166,5167,5168,5171,
			5172,5173,5174,5175,5176,5177,5181,5182,5183,5184,5185,5186,5187,5190,5191,5192,5193,5194,5195,5196,5197,5198,5199,5201,5202,5203,5204,5205,5206,5207,5208,5209,521,5221,5222,
			5223,5224,5225,5226,5228,5231,5232,5233,5234,5235,5236,5237,5238,5241,5242,5244,5245,5246,5247,5248,5250,5251,5252,5253,5254,5255,5257,5258,5259,5261,5262,5263,5264,5265,5266,
			5271,5272,5273,5274,5275,5276,5277,5278,5281,5282,5283,5284,5285,5286,5292,5293,5294,5295,5300,5301,5302,5303,5304,5305,5306,5307,5308,5309,531,5320,5321,5322,5323,5324,5325,
			5326,5327,5328,5329,5331,5332,5333,5334,5335,5336,5337,5339,5341,5344,5345,5346,5347,5351,5352,5353,5354,5355,5356,5357,5358,5361,5362,5363,5364,5365,5366,5367,5368,5371,5372,
			5373,5374,5375,5376,5377,5378,5379,5381,5382,5383,5384,5401,5402,5403,5404,5405,5406,5407,5409,541,5421,5422,5423,5424,5425,5426,5427,5428,5429,5431,5432,5433,5434,5435,5436,
			5437,5438,5439,5441,5442,5443,5444,5445,5446,5447,5448,5451,5452,5453,5454,5455,5456,5457,5458,5459,5461,5462,5464,5465,5466,5467,5468,5471,5472,5473,5474,5475,5476,5481,5482,
			5483,5484,5485,5491,5492,5493,5494,5495,5502,5503,5504,5505,5506,5507,5508,5509,551,5520,5521,5522,5523,5524,5525,5527,5528,5529,5531,5532,5533,5534,5535,5536,5541,5542,5543,
			5544,5545,5546,5551,5552,5553,5554,5555,5556,5561,5562,5563,5564,5565,5571,5572,5573,5574,5582,5583,5584,5585,5586,5592,5593,5594,5601,5602,5603,5604,5605,5606,5607,5608,5609,
			561,5621,5622,5623,5624,5625,5626,5631,5632,5633,5634,5635,5636,5641,5642,5643,5644,5645,5646,5647,5648,5650,5651,5652,5653,5654,5655,5656,5657,5658,5659,5661,5662,5663,5664,
			5665,5671,5672,5673,5674,5675,5676,5677,5681,5682,5683,5684,5685,5686,5691,5692,5693,5694,5695,5696,5702,5703,5704,5705,5706,5707,571,5721,5722,5723,5724,5725,5726,5731,5732,
			5733,5734,5741,5742,5743,5744,5745,5746,5751,5752,5753,5754,5755,5761,5763,5764,5765,5766,5767,5768,5769,5771,5772,5773,5774,5775,5776,5777,5802,5803,5804,5805,5806,5807,5808,
			581,5820,5821,5822,5823,5824,5825,5826,5827,5828,5829,5831,5832,5833,5834,5835,5836,5837,5838,5839,5840,5841,5842,5843,5844,5845,5846,5848,5849,5850,5851,5852,5853,5854,5855,
			5857,5858,5859,5861,5862,5863,5864,5865,5872,5873,5874,5875,5882,5883,5901,5902,5903,5904,5905,5906,5907,5908,5909,591,5921,5922,5923,5924,5925,5926,5931,5932,5933,5934,5935,
			5936,5937,5939,5941,5942,5943,5944,5945,5946,5947,5948,5951,5952,5953,5954,5955,5956,5957,5961,5962,5963,5964,5965,5966,5971,5973,5975,5976,5977,5978,6002,6003,6004,6007,6008,
			6020,6021,6022,6023,6024,6026,6027,6028,6029,6031,6032,6033,6034,6035,6036,6039,6041,6042,6043,6044,6045,6046,6047,6048,6049,6050,6051,6052,6053,6054,6055,6056,6057,6058,6059,
			6061,6062,6063,6066,6068,6071,6073,6074,6078,6081,6082,6083,6084,6085,6086,6087,6092,6093,6094,6095,6096,6101,6102,6103,6104,6105,6106,6107,6108,6109,611,6120,6122,6123,6124,
			6126,6127,6128,6129,6130,6131,6132,6133,6134,6135,6136,6138,6139,6142,6144,6145,6146,6147,6150,6151,6152,6154,6155,6157,6158,6159,6161,6162,6163,6164,6165,6166,6167,6171,6172,
			6173,6174,6175,6181,6182,6183,6184,6185,6186,6187,6188,6190,6192,6195,6196,6198,6201,6202,6203,6204,6205,6206,6207,6209,621,6220,6221,6222,6223,6224,6226,6227,6228,6229,6231,
			6232,6233,6234,6235,6236,6237,6238,6239,6241,6242,6243,6244,6245,6246,6247,6249,6251,6252,6253,6254,6255,6256,6257,6258,6261,6262,6263,6264,6265,6266,6267,6268,6269,6271,6272,
			6274,6275,6276,6281,6282,6283,6284,6285,6286,6287,6291,6292,6293,6294,6295,6296,6297,6298,6301,6302,6303,6304,6305,6306,6307,6308,631,6321,6322,6323,6324,6325,6326,6327,6328,
			6329,6331,6332,6333,6334,6335,6336,6337,6338,6339,6340,6341,6342,6343,6344,6345,6346,6347,6348,6349,6351,6352,6353,6355,6356,6357,6358,6359,6361,6362,6363,6364,6371,6372,6373,
			6374,6375,6381,6382,6383,6384,6385,6386,6387,6391,6392,6393,6394,6395,6396,6397,6398,6400,6401,6402,6403,6404,6405,6406,6407,6408,6409,641,6420,6421,6422,6423,6424,6425,6426,
			6427,6428,6429,6430,6431,6432,6433,6434,6435,6436,6438,6439,6440,6441,6442,6443,6444,6445,6446,6447,6449,6451,6452,6453,6454,6455,6456,6457,6458,6461,6462,6464,6465,6466,6467,
			6468,6471,6472,6473,6474,6475,6476,6477,6478,6479,6482,6483,6484,6485,6486,6500,6501,6502,6503,6504,6505,6506,6507,6508,6509,651,6522,6523,6524,6525,6526,6527,6531,6532,6533,
			6534,6535,6536,6541,6542,6543,6544,6545,6550,6551,6552,6553,6554,6555,6556,6557,6558,6559,6561,6562,6563,6564,6565,6566,6567,6568,6569,6571,6572,6573,6574,6575,6578,6580,6581,
			6582,6583,6584,6585,6586,6587,6588,6589,6591,6592,6593,6594,6595,6596,6597,6599,661,6620,6621,6622,6623,6624,6625,6626,6627,6628,6629,6630,6631,6633,6634,6635,6636,6637,6638,
			6639,6641,6642,6643,6644,6645,6646,6647,6648,6650,6651,6652,6653,6654,6655,6656,6657,6658,6659,6660,6661,6663,6664,6665,6666,6667,6668,6669,6670,6672,6673,6674,6675,6676,6677,
			6678,6681,6682,6683,6684,6691,6692,6693,6694,6695,6696,6697,6698,6701,6703,6704,6706,6707,6708,6709,671,6721,6722,6723,6724,6725,6726,6727,6728,6731,6732,6733,6734,6735,6736,
			6737,6741,6742,6743,6744,6745,6746,6747,6751,6752,6753,6754,6755,6756,6757,6758,6761,6762,6763,6764,6765,6766,6771,6772,6773,6774,6775,6776,6781,6782,6783,6784,6785,6786,6787,
			6788,6789,6802,6803,6804,6805,6806,6809,681,6821,6824,6825,6826,6827,6831,6832,6833,6834,6835,6836,6837,6838,6841,6842,6843,6844,6848,6849,6851,6852,6853,6854,6855,6856,6857,
			6858,6861,6864,6865,6866,6867,6868,6869,6871,6872,6873,6874,6875,6876,6881,6887,6888,6893,6894,6897,6898,69,7021,7022,7023,7024,7025,7026,7031,7032,7033,7034,7041,7042,7043,
			7044,7045,7046,7051,7052,7053,7054,7055,7056,7062,7063,7066,7071,7072,7073,7081,7082,7083,7084,7085,711,7121,7122,7123,7124,7125,7126,7127,7128,7129,7130,7131,7132,7133,7134,
			7135,7136,7138,7139,7141,7142,7143,7144,7145,7146,7147,7148,7150,7151,7152,7153,7154,7156,7157,7158,7159,7161,7162,7163,7164,7165,7166,7171,7172,7173,7174,7175,7176,7181,7182,
			7183,7184,7191,7192,7193,7194,7195,7202,7203,7204,721,7220,7221,7222,7223,7224,7225,7226,7227,7228,7229,7231,7232,7233,7234,7235,7236,7237,7240,7242,7243,7244,7245,7246,7247,
			7248,7249,7250,7251,7252,7253,7254,7255,7256,7257,7258,7259,7260,7261,7262,7263,7264,7265,7266,7267,7268,7269,7271,7272,7273,7274,7275,7276,7277,7300,7302,7303,7304,7305,7306,
			7307,7308,7309,731,7321,7322,7323,7324,7325,7326,7327,7328,7329,7331,7332,7333,7334,7335,7336,7337,7340,7343,7344,7345,7346,7347,7348,7351,7352,7353,7354,7355,7356,7357,7358,
			7361,7362,7363,7364,7365,7366,7367,7371,7373,7374,7375,7376,7381,7382,7383,7384,7385,7386,7387,7388,7389,7391,7392,7393,7394,7395,7402,7403,7404,741,7420,7422,7423,7424,7425,
			7426,7427,7428,7429,7431,7432,7433,7434,7435,7436,7440,7441,7442,7443,7444,7445,7446,7447,7448,7449,7451,7452,7453,7454,7455,7456,7457,7458,7459,7461,7462,7463,7464,7465,7466,
			7467,7471,7472,7473,7474,7475,7476,7477,7478,7482,7483,7484,7485,7486,7502,7503,7504,7505,7506,751,7520,7522,7524,7525,7527,7528,7529,7531,7532,7533,7534,7541,7542,7543,7544,
			7545,7546,7551,7552,7553,7554,7555,7556,7557,7558,7561,7562,7563,7564,7565,7566,7567,7568,7569,7570,7571,7572,7573,7574,7575,7576,7577,7578,7579,7581,7582,7583,7584,7585,7586,
			7587,7602,761,7620,7621,7622,7623,7624,7625,7626,7627,7628,7629,7631,7632,7633,7634,7635,7636,7641,7642,7643,7644,7645,7646,7651,7652,7653,7654,7655,7656,7657,7660,7661,7662,
			7663,7664,7665,7666,7667,7668,7669,7671,7672,7673,7674,7675,7676,7681,7682,7683,7684,7685,7702,7703,7704,7705,7706,7707,7708,7709,771,7720,7721,7722,7723,7724,7725,7726,7727,
			7728,7729,7731,7732,7733,7734,7735,7736,7738,7739,7741,7742,7743,7744,7745,7746,7747,7748,7751,7753,7754,7755,7761,7762,7763,7764,7765,7771,7773,7774,7775,7777,7802,7803,7804,
			7805,7806,7807,7808,781,7821,7822,7823,7824,7825,7826,7831,7832,7833,7834,7835,7836,7837,7838,7839,7841,7842,7843,7844,7851,7852,7853,7854,7903,7904,7905,7906,7907,791,7930,
			7931,7932,7933,7934,7935,7936,7937,7938,7939,7940,7941,7942,7943,7944,7945,7946,7947,7948,7949,7950,7951,7952,7953,7954,7955,7957,7958,7959,7961,7962,7963,7964,7965,7966,7967,
			7971,7972,7973,7974,7975,7976,7977,8020,8021,8022,8023,8024,8025,8026,8027,8028,8029,8031,8032,8033,8034,8035,8036,8038,8039,8041,8042,8043,8045,8046,8051,8052,8053,8054,8055,
			8056,8057,8061,8062,8063,8064,8065,8066,8067,8071,8072,8073,8074,8075,8076,8081,8082,8083,8084,8085,8086,8091,8092,8093,8094,8095,8102,8104,8105,8106,811,8121,8122,8123,8124,
			8131,8133,8134,8135,8136,8137,8138,8139,8141,8142,8143,8144,8145,8146,8151,8152,8153,8157,8158,8161,8165,8166,8167,8168,8170,8171,8176,8177,8178,8179,8191,8192,8193,8194,8195,
			8196,8202,8203,8204,8205,8206,8207,8208,821,8221,8222,8223,8224,8225,8226,8230,8231,8232,8233,8234,8236,8237,8238,8239,8241,8243,8245,8246,8247,8248,8249,8250,8251,8252,8253,
			8254,8257,8258,8259,8261,8262,8263,8265,8266,8267,8268,8269,8271,8272,8273,8274,8276,8281,8282,8283,8284,8285,8291,8292,8293,8294,8295,8296,8302,8303,8304,8306,831,8320,8321,
			8322,8323,8324,8325,8326,8327,8328,8330,8331,8332,8333,8334,8335,8336,8337,8338,8340,8341,8342,8343,8344,8345,8346,8347,8348,8349,8361,8362,8363,8364,8365,8366,8367,8368,8369,
			8370,8372,8373,8374,8375,8376,8377,8378,8379,8380,8381,8382,8383,8384,8385,8386,8387,8388,8389,8392,8393,8394,8395,8402,8403,8404,8405,8406,8407,841,8421,8422,8423,8424,8426,
			8427,8431,8432,8433,8434,8435,8441,8442,8443,8444,8445,8446,8450,8452,8453,8454,8456,8457,8458,8459,8460,8461,8462,8463,8464,8465,8466,8467,8468,8469,8501,8502,8503,8504,8505,
			8506,8507,8509,851,8531,8532,8533,8534,8535,8536,8537,8538,8541,8542,8543,8544,8545,8546,8547,8548,8549,8550,8551,8552,8553,8554,8555,8556,8557,8558,8561,8562,8563,8564,8565,
			8571,8572,8573,8574,8581,8582,8583,8584,8585,8586,8591,8592,8593,861,8621,8622,8623,8624,8628,8629,8630,8631,8633,8634,8635,8636,8637,8638,8639,8640,8641,8642,8649,8650,8651,
			8652,8654,8656,8657,8661,8662,8663,8664,8665,8666,8667,8669,8670,8671,8677,8678,8679,8681,8682,8683,8684,8685,8686,8687,8702,8703,8704,8705,8706,8707,8708,8709,871,8721,8722,
			8723,8724,8725,8726,8727,8728,8731,8732,8733,8734,8735,8741,8742,8743,8744,8745,8751,8752,8753,8754,8756,8761,8762,8764,8765,8766,8771,8772,8773,8774,8781,8782,8783,8784,8785,
			8801,8802,8803,8805,8806,8807,8808,8809,881,8821,8822,8823,8824,8825,8841,8845,8846,8847,8851,8856,8857,8858,8860,8861,8862,8867,8868,8869,89,906,9070,9071,9072,9073,9074,
			9075,9076,9077,9078,9080,9081,9082,9083,9084,9085,9086,9087,9088,9089,9090,9091,9092,9093,9094,9097,9099,9101,9102,9103,9104,9105,9106,9107,911,9120,9122,9123,9126,9127,9128,
			9129,9131,9132,9133,9134,9135,9141,9142,9143,9144,9145,9146,9147,9148,9149,9151,9152,9153,9154,9155,9156,9157,9158,9161,9162,9163,9164,9165,9166,9167,9170,9171,9172,9173,9174,
			9175,9176,9177,9178,9179,9180,9181,9182,9183,9184,9185,9186,9187,9188,9189,9190,9191,9192,9193,9194,9195,9196,9197,9198,9199,9201,9202,9203,9204,9205,9206,9207,9208,9209,921,
			9220,9221,9222,9223,9225,9227,9228,9229,9231,9232,9233,9234,9235,9236,9238,9241,9242,9243,9244,9245,9246,9251,9252,9253,9254,9255,9256,9257,9260,9261,9262,9263,9264,9265,9266,
			9267,9268,9269,9270,9271,9272,9273,9274,9275,9276,9277,9278,9279,9280,9281,9282,9283,9284,9285,9286,9287,9288,9289,9292,9293,9294,9295,9302,9303,9305,9306,9307,931,9321,9323,
			9324,9325,9326,9331,9332,9333,9334,9335,9336,9337,9338,9339,9340,9341,9342,9343,9344,9345,9346,9347,9348,9349,9350,9351,9352,9353,9354,9355,9356,9357,9358,9359,9360,9363,9364,
			9365,9366,9367,9369,9371,9372,9373,9374,9375,9376,9377,9378,9381,9382,9383,9384,9385,9386,9391,9392,9393,9394,9395,9396,9397,9398,9401,9402,9403,9404,9405,9406,9407,9408,9409,
			941,9420,9421,9422,9423,9424,9426,9427,9428,9429,9431,9433,9434,9435,9436,9438,9439,9441,9442,9443,9444,9445,9446,9447,9448,9451,9452,9453,9454,9461,9462,9463,9464,9465,9466,
			9467,9468,9469,9471,9472,9473,9474,9480,9481,9482,9484,9491,9492,9493,9495,9497,9498,9499,9502,9503,9504,9505,951,9521,9522,9523,9524,9525,9526,9527,9528,9529,9531,9532,9533,
			9534,9535,9536,9542,9543,9544,9545,9546,9547,9548,9549,9551,9552,9553,9554,9555,9556,9560,9561,9562,9563,9564,9565,9566,9567,9568,9569,9571,9572,9573,9574,9575,9576,9602,9603,
			9604,9605,9606,9607,9608,961,9621,9622,9624,9625,9626,9627,9628,9631,9632,9633,9634,9635,9636,9637,9638,9639,9641,9642,9643,9644,9645,9646,9647,9648,9651,9652,9653,9654,9655,
			9656,9657,9658,9659,9661,9662,9663,9664,9665,9666,9671,9672,9673,9674,9675,9676,9677,9681,9682,9683,9701,9704,9708,971,9720,9721,9722,9723,9724,9725,9726,9727,9728,9729,9732,
			9733,9734,9735,9736,9737,9738,9741,9742,9744,9745,9746,9747,9748,9749,9761,9762,9763,9764,9765,9766,9771,9772,9773,9774,9775,9776,9777,9778,9779,9802,9803,9804,9805,981,9820,
			9822,9823,9824,9825,9826,9827,9828,9829,9831,9832,9833,9834,9835,9836,9837,9841,9842,9843,9844,9845,9846,9847,9848,9851,9852,9853,9854,9855,9856,9857,9861,9865,9867,9868,9869,
			9871,9872,9873,9874,9875,9876,9901,9903,9904,9905,9906,9907,9908,991,9920,9921,9922,9923,9924,9925,9926,9927,9928,9929,9931,9932,9933,9935,9936,9937,9938,9941,9942,9943,9944,
			9945,9946,9947,9948,9951,9952,9953,9954,9955,9956,9961,9962,9963,9964,9965,9966,9971,9972,9973,9974,9975,9976,9977,9978);
		$mobilePrefixes = array(160,162,163,170,171,172,173,174,175,176,177,178,179,1511,1512,1514,1515,1516,1517,1520,1521,1522,1523,1525,1526,1529,1573,1575,1577,1578,1579,1590,1801,1802,1803,1804,1805,1806);
		
		$ret = $phoneNumber;
		$ret = preg_replace(",[^0-9+],", "", $ret);
		if ($ret == '') return $ret;
		
		$ret = preg_replace(",^000,", "00", $ret);
		$ret = preg_replace(",^00,", "+", $ret);
		$ret = preg_replace(",^\+\+,", "+", $ret);
		// if (preg_match (",^[0+],", $ret) != 1) $ret = "030" . $ret;
		$ret = preg_replace(",^0,", "+49", $ret);
		if ($ret[1] == "1") return self::tz_ins_sp($ret, 2, 5);
		
		$v2 = substr($ret, 1, 2);
		$v3 = substr($ret, 1, 3);
		if (in_array($v2, $internatPrefixes)) {
			$ret = preg_replace(",^(...)0,", "$1", $ret);
			if ($v2 == "49") return self::tz_natv($ret, $nationalPrefixes, $mobilePrefixes);
			return self::tz_ins_sp($ret, 3, 0);
		}
		if (in_array($v3, $intv)) {
			return self::tz_ins_sp($ret, 4, 0);
		}
		return $ret;
	}
	
	// helper functions
	static function tz_ins_sp($s, $a, $b) {
		$buf = $s;
		if ($b > 0) $buf = sprintf ("%s %s", substr ($buf, 0, $b), substr ($buf, $b));
		if ($a > 0) $buf = sprintf ("%s %s", substr ($buf, 0, $a), substr ($buf, $a));
		return $buf;
	}

	static function tz_natv($s, $nationalPrefixes, $mobilePrefixes) {
		$de = substr ($s, 3);
		$v2 = substr ($de, 0, 2);
		$v3 = substr ($de, 0, 3);
		$v4 = substr ($de, 0, 4);
		$v5 = substr ($de, 0, 5);
		$a = 3;
		$b = 0;
		if (in_array($v2, $nationalPrefixes)) $b = 5;
		if (in_array($v3, $nationalPrefixes)) $b = 6;
		if (in_array($v3, $mobilePrefixes)) $b = 6;
		if (in_array($v4, $nationalPrefixes)) $b = 7;
		if (in_array($v4, $mobilePrefixes)) $b = 7;
		if (in_array($v5, $nationalPrefixes)) $b = 8;
		return self::tz_ins_sp ($s, $a, $b);
	}



	/**
	 * To show the colors in list and related list
	 * include\ListView\ListViewController.php and modules/Vtiger/models/RelationListView.php used it.
	 */
	public static function getListViewColor($fieldName,$fieldValue, $moduleName, $moduleFieldInstances) {
		$db = PearDatabase::getInstance();
		// do it the same way as ListViewColor does to prevent ID issues with Calendar
		if (!isset($moduleFieldInstances)) {
			$moduleInstance = Vtiger_Module_Model::getInstance($moduleName);
			$moduleFieldInstances = $moduleInstance->getFields();
		}
		$field = $moduleFieldInstances[$fieldName];
		// Contacts got Account fields merged in... work around, uncached
		if (!isset($field) && strpos($fieldName, '.') !== false) {
			list($tmpModule, $tmpFieldName) = explode('.', $fieldName);
			if ($tmpModule != $moduleName) {
				$tmpModuleInstance = Vtiger_Module_Model::getInstance($tmpModule);
				$tmpModuleFieldInstances = $tmpModuleInstance->getFields();
				
				$field = $tmpModuleFieldInstances[$tmpFieldName];
			}
		}
		
		if (isset($field)) {
			$fieldId = $field->get('id');
		
			$query = 'SELECT listcolor FROM berli_listview_colors WHERE listfieldid = ? AND fieldcontent =?';
			$result = $db->pquery($query,array($fieldId, decode_html($fieldValue)));
			if ($result && $db->num_rows($result) > 0) {
				$rowListColor = $db->query_result($result,0,'listcolor');
			}
		}
		
		if (!isset($rowListColor)) $rowListColor = '';
		
		// $this->fieldColorMap[$fieldName][$fieldValue] = $rowListColor;

		return $rowListColor;
	}



}
