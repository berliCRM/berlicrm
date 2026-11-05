<?php
/**
 * German labels, diagnostics and deployment instructions for Word add-in settings.
 *
 * @package berliCRM.WordAddin
 */

/** @var array<string, string> Server-rendered settings-page translations. */
$languageStrings = array(
    'LBL_WORD_ADDIN' => 'Word Add-in',
    'LBL_INTRO' => 'Richten Sie das berliCRM Word Add-in ein, laden Sie das Manifest herunter und stellen Sie es Ihren Benutzern in Word bereit.',
    'LBL_SERVER_CHECK' => 'Serverinstallation prüfen',
    'LBL_CRM_URL' => 'Konfigurierte CRM-Adresse',
    'LBL_MANIFEST' => 'Manifest erstellen',
    'LBL_SERVER_FILES' => 'Add-in-Dateien und Webservice-Handler',
    'LBL_OK' => 'In Ordnung',
    'LBL_FILES_MISSING' => 'Diese Dateien fehlen oder sind nicht lesbar. Bitte auf den CRM-Server kopieren:',
    'LBL_REGISTERED' => 'Korrekt registriert',
    'LBL_NOT_REGISTERED' => 'Fehlt oder muss aktualisiert werden',
    'LBL_REGISTER_SERVICES' => 'Webservices einrichten / reparieren',
    'LBL_REFRESH' => 'Erneut prüfen',
    'LBL_SETUP_SUCCESS' => 'Die Word-Webservices und die Sprachabfrage wurden eingerichtet.',
    'LBL_CHECK_HINT' => 'Die Prüfung kontrolliert lokale Dateien und die Webservice-Registrierung. Öffnen Sie die folgenden Links zusätzlich auf einem Word-Arbeitsplatz: HTTPS-Zertifikat und Erreichbarkeit müssen dort funktionieren.',
    'LBL_OPEN_TASKPANE' => 'Add-in-Seite öffnen',
    'LBL_MANIFEST_HINT' => 'Das Manifest wird beim Download für diese CRM-Adresse erzeugt. CRM-Unterverzeichnisse und Ports werden übernommen. Die mitgelieferte Manifest-Datei bleibt unverändert; Zugangsdaten sind nicht enthalten.',
    'LBL_DOWNLOAD_MANIFEST' => 'Manifest herunterladen (.xml)',
    'LBL_SETUP_INCOMPLETE' => 'Die Einrichtung ist noch nicht vollständig. Beheben Sie die oben angezeigten Punkte vor der Verwendung in Word.',
    'LBL_WORD_INSTALL' => 'In Word bereitstellen',
    'LBL_SERVICE_READ' => 'Kundendaten für den Seriendruck abrufen',
    'LBL_SERVICE_WRITE' => 'Erstellte Briefe im CRM speichern',
    'LBL_HTTPS_MANIFEST' => 'HTTPS-Adresse und Manifest-Vorlage',
    'LBL_ASSET_LINKS' => 'Erreichbarkeit am Arbeitsplatz prüfen',
    'LBL_MANIFEST_FOR' => 'Manifest für diese CRM-Installation',
    'LBL_CENTRAL_TAB' => 'Microsoft 365',
    'LBL_WEB_TAB' => 'Word im Web',
    'LBL_WINDOWS_TAB' => 'Windows / ohne Exchange',
    'LBL_CENTRAL_INSTALL' => 'Zentral über Microsoft 365',
    'LBL_CENTRAL_STEP_1' => 'Melden Sie sich als zuständiger Microsoft-365-Administrator im Admin Center an. Öffnen Sie Einstellungen > Integrierte Apps und laden Sie eine benutzerdefinierte Office-App hoch.',
    'LBL_CENTRAL_STEP_2' => 'Wählen Sie das heruntergeladene XML-Manifest aus. Alternativ nutzen Sie dort Add-Ins > Add-In bereitstellen > Benutzerdefiniertes Add-In hochladen, falls dieser Weg angeboten wird.',
    'LBL_CENTRAL_STEP_3' => 'Weisen Sie Benutzer oder Gruppen zu und schließen Sie die Bereitstellung ab. Nach der Verteilung finden Benutzer das Add-in in Word unter Start/Einfügen > Add-Ins. Die Verteilung kann zeitversetzt erfolgen.',
    'LBL_WEB_INSTALL' => 'Einzeltest in Word im Web',
    'LBL_WEB_STEPS' => 'Öffnen Sie ein Dokument in Word im Web. Wählen Sie Start/Einfügen > Add-Ins > Weitere Optionen/Meine Add-Ins > Eigenes Add-In hochladen und dann das heruntergeladene Manifest. Die verfügbaren Menüs hängen von Ihrer Word-Version und den Organisationsrichtlinien ab.',
    'LBL_WINDOWS_INSTALL' => 'Ohne Exchange: Testkatalog für Word unter Windows',
    'LBL_WINDOWS_STEP_1' => 'Legen Sie das heruntergeladene XML-Manifest in einen freigegebenen Netzwerkordner, auf den die Testbenutzer lesend zugreifen können. Dieser Weg ist laut Microsoft für Tests vorgesehen.',
    'LBL_WINDOWS_STEP_2' => 'Öffnen Sie in Word Datei > Optionen > Trust Center > Einstellungen für das Trust Center > Vertrauenswürdige Add-in-Kataloge. Fügen Sie den UNC-Netzwerkpfad hinzu und aktivieren Sie Im Menü anzeigen.',
    'LBL_WINDOWS_STEP_3' => 'Starten Sie Word neu. Wählen Sie Start/Einfügen > Add-Ins > Weitere Optionen/Meine Add-Ins > Freigegebener Ordner und fügen Sie berliCRM Word Plugin hinzu.',
    'LBL_MICROSOFT_GUIDE' => 'Microsoft-Anleitung öffnen',
    'LBL_FIRST_LOGIN' => 'Erste Anmeldung im Add-in',
    'LBL_LOGIN_HINT' => 'Öffnen Sie berliCRM Word Plugin und melden Sie sich mit Ihrer CRM-Adresse, Ihrem CRM-Benutzernamen und Ihrem persönlichen API-Schlüssel aus Meine Einstellungen an. Der Benutzer benötigt Lesezugriff auf die gewünschten Module und Listenansichten; zum Ablegen von Briefen zusätzlich Rechte zum Erstellen von Dokumenten.',
    'LBL_UPDATE_HINT' => 'Bei Änderungen der CRM-Adresse oder der Manifest-Metadaten das Manifest erneut herunterladen und in Word bzw. Microsoft 365 aktualisieren. Bei reinen HTML-/JavaScript-Updates genügt normalerweise das Aktualisieren der Serverdateien mit angepasster Cache-Version.',
    'LBL_WORD_ADDIN_INVALID_URL' => 'Für das Manifest muss $site_URL in der CRM-Konfiguration eine gültige HTTPS-Adresse ohne Zugangsdaten, Abfrageparameter oder Fragment enthalten.',
    'LBL_WORD_ADDIN_MANIFEST_ERROR' => 'Die mitgelieferte Manifest-Vorlage fehlt oder ist ungültig.',
    'LBL_WORD_ADDIN_DATABASE_ERROR' => 'Die Webservice- oder Menüregistrierung konnte nicht gelesen bzw. gespeichert werden. Bitte das CRM-Serverprotokoll prüfen.',
    'LBL_WORD_ADDIN_FILES_MISSING' => 'Die Word-Webservice-Handler fehlen oder sind nicht lesbar.'
);
/** @var array<string, string> Reserved client-side translations; currently empty. */
$jsLanguageStrings = array();
