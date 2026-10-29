<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Additional recipients for cron error and timeout notifications.
 *
 * The active administrator and database-configured notification address are
 * notified separately. Add further email addresses as additional array entries.
 */
return array(
	'error_notification_recipients' => array(
		'',
	),
);
