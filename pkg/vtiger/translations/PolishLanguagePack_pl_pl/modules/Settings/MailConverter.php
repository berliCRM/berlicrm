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
	'MailConverter' => 'Skaner mailowy',
	'MailConverter_Description' => 'Konwertuj maile do określonych rekordów',
	'MAILBOX' => 'Skrzynka mailowa',
	'RULE' => 'Reguły',
	'LBL_ADD_RECORD' => 'Dodaj skrzynkę mailową',
	'ALL' => 'Wszystkie',
	'UNSEEN' => 'Nieprzeczytane',
	'LBL_MARK_READ' => 'Przeczytane',
	'SEEN' => 'Przeczytane',
	'LBL_EDIT_MAILBOX' => 'Edytuj skrzynkę mailową',
    'LBL_CREATE_MAILBOX' => 'Stwórz skrzynkę mailową',
	'LBL_BACK_TO_MAILBOXES' => 'Cofnij do skrzynki mailowej',
	'LBL_MARK_MESSAGE_AS' => 'Oznacz wiadomość jako',
	
	//Server Messages
	'LBL_MAX_LIMIT_EXCEEDED' => 'Przekroczono dodatkowy limit skrzynki pocztowej !',
	'LBL_IS_IN_RUNNING_STATE' => 'Czy uruchomiony',
	'LBL_SAVED_SUCCESSFULLY' => 'Zapisano poprawnie',
	'LBL_CONNECTION_TO_MAILBOX_FAILED' => 'Połączenie ze skrzynką mailową jest niepoprawne!',
	'LBL_DELETED_SUCCESSFULLY' => 'Usunięto poprawnie',
	'LBL_RULE_DELETION_FAILED' => 'Błąd podczas usuwania',
	'LBL_RULES_SEQUENCE_INFO_IS_EMPTY' => 'Kolejnośc jest pusta',
	'LBL_SEQUENCE_UPDATED_SUCCESSFULLY' => 'Kolejność zapisano poprawnie',
	'LBL_SCANNED_SUCCESSFULLY' => 'Skanowanie skrzynki wykonano poprawnie',

	//Field Names
	'scannername' => 'Nazwa skanowania',
	'server' => 'Nazwa serwera',
	'protocol' => 'Protokół',
	'username' => 'Nazwa użytkownika',
	'password' => 'Hasło',
	'ssltype' =>  'SSL Typ',
	'sslmethod' => 'SSL Metoda',
	'connecturl' => 'Adres podłączeni (url)',
	'searchfor' => 'Szukaj dla',
	'markas' => 'Po skanowaniu',

	//Field values & Messages
	'LBL_ENABLE' => 'Włącz',
	'LBL_DISABLE' =>'Wyłącz',
	'LBL_STATUS_MESSAGE' => 'Zaznacz by aktywować',
	'LBL_VALIDATE_SSL_CERTIFICATE' => 'Weryfikuj poprawność certyfikatu SSL',
	'LBL_DO_NOT_VALIDATE_SSL_CERTIFICATE' => 'Nie weryfikuj poprawności certyfikatu SSL',
	'LBL_ALL_MESSAGES_FROM_LAST_SCAN' => 'Wszystkie wiadomości z ostatniego skanowania',
	'LBL_UNREAD_MESSAGES_FROM_LAST_SCAN' => 'Nieprzeczytane wiadomości z ostatniego skanowania',
	'LBL_MARK_MESSAGES_AS_READ' => 'Oznacz wiadomość jako przeczytaną',
	'LBL_I_DONT_KNOW' => "Nie wiem",

	//Mailbox Actions
	'LBL_SCAN_NOW' => 'Skanuj ręcznie',
	'LBL_RULES_LIST' => 'Lista warunków',
	'LBL_SELECT_FOLDERS' => 'Zaznacz foldery',

	//Action Messages
	'LBL_DELETED_SUCCESSFULLY' => 'Usunięto poprawnie',
	'LBL_RULE_DELETION_FAILED' => 'Warunek usunięto niepoprawnie',
	'LBL_SAVED_SUCCESSFULLY' => 'Zapisano poprawnie',
	'LBL_SCANED_SUCCESSFULLY' => 'Skan wykonano poprawnie',
	'LBL_IS_IN_RUNNING_STATE' => 'stan skanera',
	'LBL_FOLDERS_INFO_IS_EMPTY' => 'Foldery są puste',
	'LBL_RULES_SEQUENCE_INFO_IS_EMPTY' => 'Brak informacji o kolejności warunków',

	//Folder Actions
	'LBL_UPDATE_FOLDERS' => 'Aktualizuj foldery',

	//Rule Fields
	'fromaddress' => 'Od',
	'toaddress' => 'Do',
	'subject' => 'Temat',
	'body' => 'Treść',
	'matchusing' => 'Dopasowanie',
	'action' => 'Akcje',

	//Rules List View labels
	'LBL_PRIORITY' => 'Priorytet',
	'PRIORITISE_MESSAGE' => 'Przeciągnij i upuść bloku do w celu określenia priorytetu',

	//Rule Field values & Messages
	'LBL_ALL_CONDITIONS' => 'Wszystkie warunki',
	'LBL_ANY_CONDITIOn' => 'Dowolny warunek',

	//Rule Conditions
	'Contains' => 'Zawiera',
	'Not Contains' => 'Nie zawiera',
	'Equals' => 'Jest równe',
	'Not Equals' => 'Nie jest równe',
	'Begins With' => 'Zaczyna się od',
	'Ends With' => 'Kończy się na',
	'Regex' => 'Wyrażenie regularne',

	//Rule Actions
	'CREATE_HelpDesk_FROM' => 'Utwórz zgłoszenie',
	'UPDATE_HelpDesk_SUBJECT' => 'Uaktualnij zgłoszenie',
	'LINK_Contacts_FROM' => 'Dodaj do kontaktów [Pole OD]',
	'LINK_Contacts_TO' => 'Dodaj do kontaktów [Pole DO]',
	'LINK_Accounts_FROM' => 'Dodaj do kontrhentów [Pole OD]',
	'LINK_Accounts_TO' => 'Dodaj do kontrhentów [Pole DO]',
    
    //Select Folder
    'LBL_UPDATE_FOLDERS' => 'Aktualizuj foldery',
    'LBL_UNSELECT_ALL' => 'Odznacz wszystko',
	
	//Setup Rules
	'LBL_CONVERT_EMAILS_TO_RESPECTIVE_RECORDS' => 'Konwersja wiadomości e-mail do odpowiednich rekordów',
	'LBL_DRAG_AND_DROP_BLOCK_TO_PRIORITISE_THE_RULE' => 'Chwyć i przemieść bloki w celu ustawienia priorytetów',
	'LBL_ADD_RULE' => 'Dodaj role',
	'LBL_PRIORITY' => 'Priorytet',
	'LBL_DELETE_RULE' => 'Usuń rolę',
	'LBL_BODY' => 'Treść',
	'LBL_MATCH' => 'Dopasowanie',
	'LBL_ACTION' => 'Akcje',
	'LBL_FROM' => 'OD',
);
$jsLanguageStrings = array(
	'JS_MAILBOX_DELETED_SUCCESSFULLY' => 'Skrzynkę mailową usunięto poprawnie',
	'JS_MAILBOX_LOADED_SUCCESSFULLY' => 'Skrzynkę mailową załadowano poprawnie'
);