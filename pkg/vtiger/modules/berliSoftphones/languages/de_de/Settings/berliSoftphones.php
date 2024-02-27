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
    'LBL_SOFTPHONE_SETTINGS' => 'Softphone Einstellungen',
    'LBL_SOFTPHONE_SELECTION' => 'Softphone Auswahl',
	'LBL_SOFTPHONE_SERVER_SETTINGS_DESCRIPTION'=>'wählen Sie hier die Parameter für Ihr Softphone',
	'LBL_PHONE_CONFIGURATION' => 'Softphone Auswahl',
	'LBL_PHONE_INSTRUCTIONS' => 'Ein Softphone ist eine Computerprogramm mit dem Sie über Ihren Computer ggf. mit Kopfhörer und Mikrophone über das Internet telefonieren können. Ein Softphone setzt vorraus, dass Sie die Möglichkeit haben, Internettelefonie zu benutzen, wodurch Ihnen u.U. zusätzliche Telefonkosten entstehen können. Wenn Sie ein Softphone verwenden wollen, so können Sie hier die entsprechende Auswahl treffen. Es können nur Softphones genutzt werden, welche eine s.g. API für den Browser bereitstellen. Diese API entscheidet auch darüber, ob das Softphone nur für abgehende Anrufe (Outbound) oder auch für reinkommende Anrufe (Inbound) genutzt werden kann.',
	'LBL_PHONE_ID' => 'Lfd. Nr.',
	'LBL_PHONE_NAME' => 'Softphone Name',
	'LBL_PHONE_PREFIX' => 'zu verwendender Präfix',
	'LBL_PHONE_ACTIVE' => 'aktiv',
	'LBL_PHONE_DESCRIPTION' => 'Beschreibung der Quelle',
	'LBL_PHONE_INACTIVE_ALL' => 'Softphone Funktion für das CRM deaktivieren: ',
	'LBL_PHONE_SIP' => 'generisch - von den meisten Softphones verwendet',
	'LBL_PHONE_STARTCALL' => 'generisch - von einigen Softphones verwendet</a>',
	'LBL_PHONE_XLIGTH' => '<a href="http://www.counterpath.com/x-lite" target=_blank >www.counterpath.com/x-lite</a>',
	'LBL_PHONE_PHONER' => '<a href="http://www.phoner.de" target=_blank >www.phoner.de</a>',
	'LBL_PHONE_EFFTEL' => '<a href="http://www.efftel.com" target=_blank >www.efftel.com</a>',
	'LBL_PHONE_ZOIPER' => '<a href="www.zoiper.com" target=_blank >www.zoiper.com</a> für reinkommende und rausgehende Anrufe',
	'LBL_PHONE_NFON' => '<a href="http://www.nfon.com" target=_blank >www.nfon.com</a> (Nsoftphone premium für Windows oder iSoftPhone für Mac)',
	'LBL_PHONE_IMPORTANT' => 'Wichtig:',
	'LBL_PHONE_IMPORTANT1' => 'Aktivieren Sie hier Ihr Softphone erst, nachdem Sie dieses Programm auf Ihrem Computer installiert haben.',
	'LBL_PHONE_HINT' => 'Hinweise für die Softphone Einrichtung für Inbound Anrufe:',
	'LBL_PHONE_HINT1' => 'Um bei reinkommenden Anrufen die Daten anzeigen zu lassen, welche zu einer Telefonummer im CRM gespeichert worden sind, müssen Sie dem CRM die Telefonnummer mitteilen. Dazu wird in den Einstellungen des Softphones eine CRM URL eingetragen. Diese muss den folgende Inhalt haben:',
	'LBL_PHONE_INBOUND_URL' => '---CRM Mandanten URL---/index.php?module=berliSoftphones&action=ListPhone&phone&phonenumber=---Telefonummer des Anrufers---',
	'LBL_PHONE_HINT2' => 'Für die Einrichtung Ihres Softphones konsultieren Sie bitte das entsprechende Handbuch. Z.B. kann man für das Softphone Phoner (nicht Phoner Light) im Menü Options->external application die Telefonnummer zu der URL mit %CALLERID% hinzufügen.',
	'LBL_PHONE_HINT3' => 'Es werden nur die Telefonnummern erkannt, die im CRM in dem Format Ihres Softphones abgelegt wurden und mindestens 4 Ziffern lang sind. Beachten Sie dazu die Hinweise aus dem CRM Journal: <a href="https://blog.crm-now.de/2009/07/22/format-von-telefonnummern/"  target=_blank>Format von Telefonnummern</a>',
	'LBL_CONFIG_SAVED'=>'Ihre Einstellung wurde gespeichert.',

);

$jsLanguageStrings = array(
	'LBL_PHONE_CONFIGURATION' => 'Softphone Auswahl',
	'LBL_CONFIG_SAVED'=>'Ihre Softphone Auswahl wurde aktiviert.',
	'LBL_CONFIG_CANCEL'=>'Die CRM Softphone Funktion wurde deaktiviert.',
    
);

