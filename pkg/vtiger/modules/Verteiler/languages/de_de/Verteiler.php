<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Reviewed by crm-now GmbH
 *************************************************************************************/
$languageStrings = array(
    'SINGLE_Verteiler' => 'Verteiler',
    'LBL_VERTEILER_INFORMATION' => 'Verteiler Information',
	'AddedBy'=>'Hinzugefügt von',
    'LBL_SELECT_TO_LOAD_VERTEILER'=>'wähle zu ladenden Verteiler',
    'LBL_PARENT'=>'aus Liste/Verteiler',
    'LBL_EXCEL_EXPORT' => 'Excel Export',
    'LBL_DELETE_SELECTED' => 'ausgewählte Einträge aus Verteiler löschen',
    'LBL_ADD_TO_TREE' => 'Zu Verteiler hinzufügen',
    'LBL_SELECT_VERTEILER' => 'Verteiler auswählen',
    'LBL_ADD_CONTACTS' => 'Person(en) hinzufügen',
	
    'LBL_DISTRIBUTION_NAME' => 'Verteilername',
    'LBL_DSITRIBUTION_TYPE' => 'Verteilertyp',
    'LBL_DISTRIBITON_STATUS' => 'Verteilerstatus',
    'LBL_DISTRIBUTION_NO' => 'Verteiler Nr.',
    'LBL_DISTRIBUTION_USAGE' => 'Verteilerverwendung',
	
	//pick lists
    'Post Distribution' => 'Postverteiler',
    'Mailing' => 'E-Mail Verteiler',
    'other Distribution' => 'anderer Verteiler',

    'planning' => 'in Planung',
    'active' => 'aktiv',
    'old' => 'veraltet',
	
    'LBL_ACTIONS' => 'Aktionen',
    'LBL_FIND_DUPLICATES' => 'Duplikate suchen',
    'LBL_PLACEHOLDER_SEARCH' => 'diesen Verteiler durchsuchen',
    'LBL_EMAILS' => 'E-Mails',
    'LBL_DUPLICATE_SEARCH' => 'Duplikatssuche',
    'LBL_NO_DUPLICATES' => 'Dieser Verteiler enthält keine Duplikate.',
    'LBL_DUPLICATES_COMMENT' => 'Hier werden Ihnen die Kontakte angezeigt, welche in dem gesamten Verteiler mehrfach vorhanden sind. Sie können diese automatisch entfernen lassen oder individuell auswählen.<br>
Wenn Sie alle automatisch entfernen, bleibt der zuletzt hinzugefügte Kontakt aus einem Duplikat erhalten.',
    'LBL_FIRSTNAME' => 'Vorname',
    'LBL_LASTNAME' => 'Nachname',
    'LBL_ORG' => 'Organisation',
    'LBL_EMAIL' => 'E-Mail',
    'LBL_ADDEDBY' => 'Hinzugefügt von',
    'LBL_FROM_LIST' => 'aus Liste/Verteiler',
    'LBL_DELETE' => 'löschen',
    'LBL_CANCEL' => 'Abbrechen',
	'LBL_MANUAL_DELETE' => 'Ausgewählte Duplikate löschen',
	'LBL_AUTOMATIC_DELETE' => 'Alle Duplikate automatisch löschen',
    'LBL_ENTRIES' => 'Einträge',
    'LBL_FILTERED' => 'gefiltert',

    'LBL_EXPORT_OPTION' => 'Export Optionen',
    'LBL_EXPORT_DESCRIPTION' => 'Bitte wählen Sie ein CRM Modul aus, zu welchem die Kontakte aus diesem Verteiler exportiert werden sollen.',
    'LBL_EXPORT_BUTTON' => 'Exportieren',

    'LBL_CHECK_E-MAIL_HEADER' => 'E-Mail Überprüfung',
    'LBL_CHECK_E-MAIL_DESCRIPTION' => 'Es wurden fehlerhafte E-Mail Adressen gefunden. Der Verteiler kann nur verwendet werden, wenn die E-Mail Adessen korrekt sind.',
    'LBL_CHECK_E-MAIL_FALSE' => 'Fehlerhafte E-Mail Adressen:',
    'LBL_CHECK_E-MAIL_CLOSE' => 'Schließen',
	'LBL_Check_E-MAIL' => 'Check E-Mail',
    'LBL_CHECK_E-MAIL_TRUE' => 'Alle enthaltende E-Mail Adressen weisen keine Fehler auf.',
    'LBL_CHECK_E-MAIL_ALL-DETAIL' => 'Alle Details',

    'LBL_MODULE_EDIT_PERMISSION_DENIED' => 'Sie haben für dieses CRM Modul nicht die Berechtigung, Daten zu verändern.',
	'LBL_SPECIAL_EXPORT' => 'Spezielle Exporte',
	'LBL_EXPORT_STEP1' => '1. Modul auswählen',
	'LBL_EXPORT_STEP2' => '2. Moduleintrag auswählen',
	'LBL_TRACKERINFO' => 'Personen wurden exportiert zu: ',
 	'LBL_CHOSEN_MODUL' => 'Modul wählen',
	'LBL_CHOSEN_ENTRY' => 'Eintrag wählen',
    );
    
$jsLanguageStrings = array(   
    'JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_ADD_THIS_FILTER' => 'Sind Sie sicher, dass Sie diese Liste hinzufügen wollen? Der Vorgang läßt sich nicht mehr rückgängig machen.',
    'JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_ADD_THIS_VERTEILER' => 'Sind Sie sicher, dass Sie diesen Verteiler hinzufügen wollen? Der Vorgang läßt sich nicht mehr rückgängig machen.',
    'JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_REMOVE_ENTRIES' => 'Sind Sie sicher, dass Sie diese Einträge aus dem Verteiler entfernen wollen?',
    'JS_LBL_PLEASE_SELECT_ENTRIES' => 'Es sind keine Listeneinträge zum Löschen ausgewählt.',
    'JS_LBL_NO_RECORDS' => 'Die zu ladende Liste enthält keine Daten',
    'JS_LBL_MODULE_ERROR' => 'kein Export möglich',
    'JS_LBL_NO_EXPORT' => 'Sie könnten die Liste zu Kampagnen, Mailchimp oder CleverReach exportieren, aber keines dieser Module ist bei Ihnen aktiviert.',
	'JS_LBL_NO_EXPORT_SELECTION' => 'Sie haben keine Liste ausgewählt.',
	'JS_LBL_EXPORT_TITLE' => 'Export abgeschlossen',
	'JS_LBL_EXPORT_FINISHED' => 'Die Daten wurden exportiert.',
	'JS_INTERNAL_ERROR' => 'Interner Fehler',
	'JS_INTERNAL_ERROR_MESSAGE1' => 'der Auftrag konnte nicht bearbeitet werden',
	'JS_INTERNAL_ERROR_MESSAGE2' => 'Interner Fehler, bitte den CRM Administrator informieren',
    'JS_LBL_EMAIL_ERROR' => 'Kein E-Mail Check möglich',
    'JS_LBL_NO_EMAIL' => 'Es sind keine E-Mails vorhanden',
  );