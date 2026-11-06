<?php

/** Envelope sender templates, independent of the visible From and Reply-To. */
class Vtiger_EnvelopeFrom {

	public static function isValidTemplate($template) {
		if (!is_string($template) || preg_match('/[\r\n]/', $template)) {
			return false;
		}
		$address = strtr(trim($template), array('$instancename$' => 'crm', '$uniqmailid$' => '1'));
		return $address === '' || (!preg_match('/\$[^$]+\$/', $address)
			&& filter_var($address, FILTER_VALIDATE_EMAIL) !== false);
	}

	public static function resolve($template, $emailId) {
		if (!self::isValidTemplate($template)) {
			throw new InvalidArgumentException('Invalid Envelope-From address');
		}
		$instanceName = trim((string) ($GLOBALS['instancename'] ?? ''));
		if ($instanceName === '') {
			$instanceName = (string) ($GLOBALS['dbconfig']['db_name'] ?? '');
		}
		if (strpos($template, '$instancename$') !== false && $instanceName === '') {
			throw new InvalidArgumentException('Envelope-From requires a CRM instance name');
		}
		if (strpos($template, '$uniqmailid$') !== false && !preg_match('/^[1-9][0-9]*$/', (string) $emailId)) {
			throw new InvalidArgumentException('Envelope-From requires a stored email ID');
		}
		$address = strtr(trim($template), array('$instancename$' => $instanceName, '$uniqmailid$' => (string) $emailId));
		if ($address !== '' && (preg_match('/[\r\n]/', $address) || !filter_var($address, FILTER_VALIDATE_EMAIL))) {
			throw new InvalidArgumentException('Invalid resolved Envelope-From address');
		}
		return $address;
	}
}
