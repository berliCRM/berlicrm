<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
$languageStrings = array(
	// General Data Protection Regulation (GDPR)
	'LBL_GDPR' => 'DSGVO',
	'LBL_GDPR_DESCRIPTION' => 'Einstellungen zur Datenschutzgrundverordnung (DSGVO)',
	'LBL_GDPR_SETTINGS' => 'Einstellungen zur Datenschutzgrundverordnung (DSGVO)',
	'LBL_GDPR_INSTRUCTIONS' => 'Hier können Sie Ihre Einstellungen zur DSGVO vornehmen. Alle Prozesse beziehen sich auf die Angaben, welche Sie im DSGVO Modul zu einer im CRM erfassten Person oder einem Lead gemacht haben. Generell wird von einem Löscherfordernis ausgegangen, wenn Sie keine Einwilligung zur Speicherung von personenbezogenen Daten eingeben haben. Ist eine Einwilligung vorhanden, werden eventuelle Befristungen beachtet. Für ein Speichern von personenbezogenen Daten auf Grund von gesetzlichen Vorgaben ohne ausdrückliche Einwilligung der betreffenden Person, können Sie in diesem Menü entsprechende Einstellungen vornehmen.',
	'LBL_CONTACT_RELATED' => 'personenbezogene Daten',
	'LBL_OPERATION_MODE' => 'Löschen personenbezogener Daten',
	'LBL_SELECT_MODE' => 'wähle Betriebsart',
	'LBL_OPERATION_AUTO' => 'automatisch',
	'LBL_OPERATION_MANUAL' => 'manuell',
	'LBL_OPERATION_DEACTIVATED' => 'deaktiviert',
	'LBL_NOTIFICATION_TIME' => 'Wann wollen Sie über anstehende Löschungen informiert werden?',
	'LBL_SELECT_NOTIFICATION_TIME' => 'wähle Benachrichtigungszeit',
	'LBL_ONE_DAY' => 'einen Tag vorher',
	'LBL_ONE_WEEK' => '1 Woche vorher',
	'LBL_TWO_WEEKS' => '2 Wochen vorher',
	'LBL_THREE_WEEKS' => '3 Wochen vorher',
	'LBL_FOUR_WEEKS' => '4 Wochen vorher',
	'LBL_DELETE_WHAT' => 'was soll gelöscht werden',
	
	'LBL_MODULES_RELATED' => 'Liste der aktiven CRM Module',
	'LBL_GDPR_RELEVANT' => 'enthält personenbezogene Daten?',
	'LBL_ACTIVATED_DELETE_ACTION' => 'automatisches Löschen von',
	'LBL_GDPR_RELEVANT_FIELDS' => 'Felder mit personenbezogenen Daten',
    
	// 'LBL_ACTIVATED' => 'automatisches Löschen aktivieren?',
	// 'LBL_DELETE_ACTION' => 'Löschen von',

	'LBL_DELETE_OPERATION' => 'automatische Löschoperationen',
	'LBL_DELETE_TRAY' => 'in Papierkorb verschieben',
	'LBL_DELETE_FOREVER' => 'aus dem Datenbestand löschen',
    
	'LBL_NO_AUTO_DELETE' => 'nicht automatisch löschen',
	'LBL_AUTO_DELETE_FIELD' => 'gesamter Datensatz',
	'LBL_AUTO_DELETE_MODULE' => 'personenbezogene Daten im Datensatz',

	'LBL_GDPR_HINT' => 'Hinweise:',
	'LBL_GDPR_HINT1' => 'Wenn sie die automatische Betriebsart auswählen, werden die Daten jeden Tag durchgesehen und bei Bedarf entsprechend der unten stehenden Anweisungen gelöscht. Bei der manuellen Betriebsart erhalten Sie eine E-Mail Benachrichtigung mit einer Liste der zu löschenden Daten.',
	'LBL_GDPR_HINT2' => 'Sie können sich im vorraus über bevorstehende Löschungen informieren lassen. Bitte bachten Sie, dass es möglich ist, dass weniger oder ggf. auch mehr gelöscht wird, wenn sich zwischen der Benachrichtigungszeit und der Ausführungszeit die Bedingungen für Datensätze geändert haben.',
	'LBL_GDPR_HINT3' => 'Wählen Sie aus der Liste die Module Ihrem CRMs jene aus, die in personenbezogene Daten enthalten. Sie können pro Module eine Auswahl von Feldern bestimmen, ansonsten wird der ganze Datensatz als personenbezogen behandelt. Wenn automatisches Löschen aktiviert ist können Sie dies für jedes Modul einschränken. Sichern Sie Ihre Auswahl mit dem Speichern-Button.',
	'LBL_GDPR_HINT4' => 'Wenn Sie die Daten nur in den Papierkorb verschieben, so müssen Sie diese danach noch selbst aus dem Papierkorb löschen, damit diese endgültig gelöscht sind. Eine Liste der endgültig gelöchten Daten (nur die Identifikationsnummern) wird gesichert und kann herangezogen werden, wenn Daten aus einem Backup wieder hergesellt werden müssen.',
	'LBL_GDPR_IMPORTANT' => 'Bitte beachten:',
	'LBL_GDPR_IMPORTANT1' => 'Lt. DSGVO müssen Daten für die kein Grund zur Speicherung vorliegt, spätestens nach 6 Monaten gelöscht werden. Das CRM zieht für die Zeitbestimmung den Inhalt des Feldes "erstellt" aus den entsprechenden Modulen heran.',
	'LBL_GDPR_IMPORTANT2' => 'Was Walter weiß .....',
	'LBL_SELECT_FIELD' => 'auswählen',
	
	
);
$jsLanguageStrings = array(
	'LBL_GDPR_CONFIGURATION' => 'Modulauswahl',
	'LBL_CONFIG_SAVED'=>'Ihre Moduleinstellung wurde gespeichert.',
	'LBL_CONFIG_CANCEL'=>'Die automatische Datenverarbeitung wurde deaktiviert.',
	'LBL_BIG_PROBLEM' => 'Die Einstellung konnte nicht gespeichert werden.',
    'LBL_CUSTOM_REQUIRED_ERROR' => 'Dieses Feld darf nicht leer sein, wenn rechts "Personenbezogene Daten im Datensatz" gewählt ist',
);

