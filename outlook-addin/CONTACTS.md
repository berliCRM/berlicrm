# CRM- und Outlook-Kontakte übernehmen

## Sprachen ab Word 1.4.0.0 / Outlook 1.1.0.0

Die Add-ins verwenden nach der Anmeldung die Sprache des CRM-Benutzers (`de_de` oder `en_us`). Vorher gilt die Office-/Browsersprache. Texte werden in PHP-Spracharrays mit stabilen Schluesseln gepflegt. Fuer dieses Update muss `office_addin_user_preferences` registriert werden. Einrichtung und Pflege: [Sprachdokumentation](../wordplugin-officejs/LANGUAGES.md).

**Deutsch** | [English](CONTACTS.en.md)

Im berliCRM-Add-in öffnet der Link **CRM- und Outlook-Kontakte verwalten**
die neue Kontakteansicht. Der Link ist auch im Aufgabenbereich der
E-Mail-Leseansicht erreichbar, etwa beim Auswählen einer Mail in der Nachrichtenliste.
Dort ist eine eigene CRM-Anmeldung möglich; eine
vorhandene CRM-Sitzung wird geprüft und wiederverwendet. Der Access Key wird
nicht gespeichert. Eine abgelaufene Sitzung führt zurück zur Anmeldung.

> **WICHTIG beim Aufruf aus einem manuell installierten Outlook-Add-in:**
> In Outlook müssen **„Optionale verbundene Erfahrungen“ eingeschaltet** sein.
> Das gilt auch bei Verwendung von Firefox. Anleitung und Hinweise stehen
> in der [Installationsdokumentation](README.md#wichtig-optionale-verbundene-erfahrungen).

Die Ansicht ist auch direkt unter `/outlook-addin/contacts.html` erreichbar.
Outlook-Web-Add-ins aktivieren sich bei Nachrichten und Terminen;
die Auswahl im nativen Outlook-Kontakte-Menü ist für dieses Add-in nicht
zugänglich. Deshalb erfolgt die Auswahl hier mit Kontrollkästchen.

## Update einer vorhandenen Installation

Bei **„Unbekannte Aktion: getUserPreferences“** passen Oberfläche und PHP-Endpunkt
nicht zusammen. Das vollständige Outlook-Sprachupdate einschließlich
`outlook-addin/rest/`, `include/utils/OfficeAddinI18n.php`, Sprachdateien und
aktualisiertem Settings-Modell einspielen. Anschließend im CRM-Hauptverzeichnis
`php outlook-addin/build/registerOutlookAddinOperation.php` ausführen oder in den
Outlook-Add-in-Einstellungen **Webservices einrichten / reparieren** wählen.
Nur Browserdateien auszutauschen behebt diesen Versionskonflikt nicht.

Eine gespeicherte CRM-Sitzung wird beim Öffnen der Kontakteansicht geprüft;
währenddessen bleibt das Loginformular verborgen. Bei technischen Fehlern
erlaubt **Erneut versuchen** eine erneute Prüfung mit derselben Sitzung.
Bei einer tatsächlich abgelaufenen Sitzung erscheint die Anmeldung wieder.
Die gespeicherte Sprachvorgabe dient bis zur authentifizierten Sprachabfrage als
Hinweis. Dieses Update erfordert keine Neuinstallation des Outlook-Manifests.

1. Das Kontakte-Update-Paket im CRM-Hauptverzeichnis entpacken. Die vorhandene
   `outlook-addin/rest/outlook.config.php` beibehalten und um den unten gezeigten Block ergänzen.
   Die Beispiele enthalten keine produktiven Zugangsdaten.
   Bei älteren Installationen liegt die Datei noch unter `rest/outlook.config.php`:
   diese mit unverändertem Inhalt und denselben Dateirechten nach
   `outlook-addin/rest/outlook.config.php` verschieben. Eine dort bereits vorhandene
   Konfiguration zuerst vergleichen und nicht überschreiben. Alle aktualisierten
   Add-in-Dateien einschließlich des Microsoft-Anmeldebundles gemeinsam hochladen.
   Die reine Pfadänderung benötigt keine erneute Registrierung; die folgenden
   Einrichtungsschritte sind nur für eine noch nicht eingerichtete Kontakteübernahme.
2. Beim normalen CRM-Versionsupdate initialisiert `db_update.php` die Webservices
   über `outlook-addin/update.php` automatisch. Eine separate Registrierung ist
   dann nicht erforderlich. Für eine gezielte Einrichtung oder Reparatur auf
   dem CRM-Server im CRM-Hauptverzeichnis ausführen:

   ```sh
   php outlook-addin/build/registerOutlookAddinOperation.php
   ```

   Das registriert zusätzlich `outlook_contact_transfer` und legt die eigene
   Verknüpfungstabelle `vtiger_outlook_contact_link` an. Die übrigen
   Outlook-Operationen bleiben registriert. Keine Core-Datei und keine
   bestehenden Benutzer-/Profilrechte müssen geändert werden.
3. Die Microsoft-Anbindung wie unten einrichten.
4. Die Add-in-Seite neu laden und **CRM- und Outlook-Kontakte verwalten** öffnen.
   Für diesen Link ist keine Manifest-Neuinstallation erforderlich.

## Microsoft Entra einmalig einrichten

### Einrichtung über das CRM-Settings-Menü

Nach dem Hochladen des Updates einmal `db_update.php` im üblichen CRM-Updateablauf
oder gezielt `php outlook-addin/build/registerOutlookAddinOperation.php` ausführen.
Dadurch erscheint für CRM-Administratoren **Einstellungen → Integration →
Outlook Add-in**. Direkter Aufruf:
`index.php?module=OutlookAddin&parent=Settings&view=Index`.

Die Seite enthält die folgenden Entra-Schritte, zeigt die genaue SPA-
Rückleitungsadresse dieser Installation und bietet Pflichtfelder für
**Anwendungs-ID (Client) / client_id** und **Verzeichnis-ID (Mandant) / tenant_id**.
Beide Werte müssen gültige GUIDs sein; leere oder ungültige Werte werden auch
serverseitig abgelehnt. Bereits in der Serverkonfiguration hinterlegte IDs werden
angezeigt. Ohne vorhandene Werte bleiben die Eingabefelder leer; nur die Platzhalter
zeigen das GUID-Format. Es werden keine Beispiel-IDs als echte Eingabewerte eingesetzt.
Ein Client Secret sowie CRM-Benutzername oder CRM-Access-Key werden nicht benötigt.

**outlook.config.php herunterladen** erzeugt eine Konfiguration mit den beiden
IDs, den passenden CRM-/Redirect-Adressen, 500 Empfängern und 30 Sekunden Zeitlimit.
Diese Datei auf dem Server unter `outlook-addin/rest/outlook.config.php` ablegen.
Eine vorhandene Konfiguration wird von der Settings-Seite nicht überschrieben;
eigene Einstellungen vor einem Ersetzen vergleichen. Das zweite Download-Angebot
erzeugt das installationsspezifische XML-Manifest, ohne dessen Vorlage zu ändern.

Die Settings-Seite prüft außerdem Dateien und Webservices und bietet eine
Reparaturregistrierung an. Menü und Webservices werden beim normalen CRM-Update
ebenfalls eingerichtet. Die folgenden Schritte dokumentieren die Entra-Seite
und die alternativ mögliche manuelle Konfiguration.

### Microsoft-Verbindung aus den Einstellungen prüfen

**Microsoft-Verbindung prüfen** verwendet die bereits auf dem Server gespeicherte
`outlook-addin/rest/outlook.config.php`. Änderungen in den Formularfeldern müssen
zuerst als Konfiguration heruntergeladen und auf den Server hochgeladen werden.
Ohne gültige gespeicherte IDs ist der Testbutton deaktiviert.

Der Klick öffnet die bestehende Microsoft-Anmeldung. Nach erfolgreicher Anmeldung
führt die Settings-Seite genau eine lesende Graph-Anfrage an
`/v1.0/me/contacts?$top=1&$select=id` aus. Ein leeres persönliches Adressbuch ist
ebenfalls ein erfolgreicher Test. Es werden weder Kontakte angelegt oder geändert
noch Kontakt-IDs oder Zugriffstokens im Ergebnis angezeigt. Das Token bleibt nur
während der Anfrage im Browser und wird nicht an das CRM gesendet.

Die Erfolgsmeldung bestätigt **Microsoft-Anmeldung und lesenden Kontaktzugriff**.
Ein Schreibzugriff wird dabei nicht durch Anlegen eines Probekontakts getestet.
Fehlende Zustimmung, ein blockiertes Anmeldefenster oder verweigerter Graph-Zugriff
werden als Fehler beziehungsweise Abbruch angezeigt. Danach kann die Prüfung
erneut gestartet werden; **Prüfung abbrechen** beendet einen laufenden Versuch.
Die Prüfung ist kein Test der separaten CRM-Benutzeranmeldung.

Die Korrektur mit Cache-Kennung **20261111.7** behält die Add-in-Version
**1.1.0.2** bei. Die Rückmeldung aus dem Anmeldefenster erfolgt über einen nur
für diesen Prüfversuch erzeugten Browserkanal derselben CRM-Origin. Eine
Empfangsbestätigung stellt sicher, dass das Ergebnis vor dem Schließen beim
Einstellungsfenster angekommen ist. Ein durch Microsofts COOP-Isolation getrenntes
`window.opener` oder ein irreführendes `popup.closed` beendet die Prüfung damit
nicht vorzeitig. Nach fünf Minuten ohne Antwort oder durch **Prüfung abbrechen**
wird der Versuch freigegeben. Tokens werden dafür weder im CRM noch dauerhaft
im Browser gespeichert. Meldungen und Rückkehrhinweise nennen die CRM-Einstellungen.
Auch beim MSAL-Rücksprung auf die ursprüngliche Startadresse bleibt die Kennung
bis zur bestätigten Rückgabe erhalten. **Prüfung abbrechen** hat einen festen
Abstand zum vorherigen Button.

Für dieses Update `outlook-addin/settings-connection.js`, die Outlook-Settings-
Vorlage, das Modulmodell und die beiden Settings-Sprachdateien gemeinsam hochladen.
Außerdem `contacts-auth.html`, `assets/contacts-auth.bundle.js`, dessen `.LEGAL.txt`
und die Outlook-Sprachdateien einschließlich `language.js` gemeinsam aktualisieren.
Die vorhandenen IDs bleiben in der Konfigurationsdatei erhalten. Ein erneuter
`db_update.php`-Lauf ist bei bereits vorhandenem Outlook-Settings-Menü nicht nötig.

### Appregistrierung und Microsoft-IDs

Die Entra-Appregistrierung wird aktuell für den Zugriff auf die persönlichen
Outlook-Kontakte über Microsoft Graph benötigt. Die E-Mail-Ablage und
Kalenderübernahme verwenden die vorhandene CRM-Anmeldung und benötigen diese
Graph-Anbindung nicht.

Diese Funktion benötigt ein Exchange-Online-Postfach. Lokale Exchange-Postfächer
ohne Microsoft-Graph-Kontaktzugriff werden nicht unterstützt.

Eine bestehende passende Registrierung kann verwendet werden. Ansonsten das
[Microsoft Entra Admin Center](https://entra.microsoft.com) öffnen und unter
**Entra ID → App-Registrierungen → Neue Registrierung** fortfahren:

1. Name beispielsweise `berliCRM Outlook`, Kontotyp **Nur Konten in diesem
   Organisationsverzeichnis**, anschließend **Registrieren**.
2. Unter **Authentifizierung → Plattform hinzufügen → Single-Page-Anwendung (SPA)**
   die genaue HTTPS-Adresse registrieren:
   `https://EURE-CRM-ADRESSE/outlook-addin/contacts-auth.html`.
   Für die Installation `unsercrm.i1.crm-now.de` lautet sie:
   `https://unsercrm.i1.crm-now.de/outlook-addin/contacts-auth.html`.
   Einen eventuellen CRM-Unterordner mit angeben. Nicht als Plattform „Web“
   registrieren. Implizite Tokenflüsse sind nicht erforderlich.
   Plattform und genaue Rückleitungsadresse müssen passen; siehe
   [Microsoft-Anleitung zur Redirect-URI](https://learn.microsoft.com/entra/identity-platform/how-to-add-redirect-uri).
3. Unter **API-Berechtigungen → Berechtigung hinzufügen → Microsoft Graph →
   Delegierte Berechtigungen** `Contacts.ReadWrite` hinzufügen. Je nach
   Mandantenrichtlinie muss ein Administrator die Zustimmung erteilen.
   Die Anwendung verwendet keine Anwendungsberechtigungen und kein Client Secret.
   Diese Berechtigung ermöglicht das Lesen und Anlegen persönlicher Outlook-Kontakte;
   siehe [Microsoft-Dokumentation zum Anlegen von Kontakten](https://learn.microsoft.com/en-us/graph/api/user-post-contacts?view=graph-rest-1.0).
4. Unter **Übersicht** die **Anwendungs-ID (Client)** und **Verzeichnis-ID (Mandant)**
   kopieren. Diese beiden IDs werden für die folgende Konfiguration benötigt und
   können der Person übergeben werden, die die Serverkonfiguration ergänzt.
5. In der vorhandenen `outlook-addin/rest/outlook.config.php` ergänzen:

   Dieser Block gehört **in das vorhandene `return array(...)`**, vor dessen
   abschließendes `);`. Bei älteren Installationen fehlt der Block zunächst:
   Updates überschreiben die lokale `outlook.config.php` nicht automatisch.
   Die übrigen Einstellungen wie CRM-Adresse und Zeitlimit beibehalten.
   `api_username` und `api_access_key` werden vom Add-in nicht benötigt und
   können entfallen. Die CRM-Anmeldung erfolgt mit dem Benutzer im Add-in;
   `client_id` und `tenant_id` sind die öffentlichen IDs der Microsoft-
   Appregistrierung und keine CRM-Zugangsdaten.

   ```php
   'contacts_microsoft' => array(
       'client_id' => 'ANWENDUNGS-ID-ALS-GUID',
       'tenant_id' => 'VERZEICHNIS-ID-ALS-GUID',
       'redirect_uri' => 'https://EURE-CRM-ADRESSE/outlook-addin/contacts-auth.html'
   ),
   ```

   Die beiden IDs müssen echte GUIDs sein. Eine leere `redirect_uri` wird aus
   der konfigurierten `crm_webservice_url` abgeleitet. Bei abweichenden internen
   und öffentlichen CRM-Adressen immer die öffentliche Adresse ausdrücklich setzen.

Der Dialog startet auf derselben Website wie das Add-in. MSAL 5.25.1 verwendet
den Authorization-Code-Flow mit PKCE und verarbeitet die Rückleitung direkt
mit `handleRedirectPromise`; es wird kein MSAL-Popup innerhalb des Office-Iframes
verwendet. Im Outlook-Add-in wird die Office-Dialog-API genutzt, außerhalb
Outlooks ein Browserfenster. Falls der Server eine CSP setzt, müssen die
Microsoft-Anmeldung, Microsoft Graph und das vorhandene Office.js zugelassen
sein. Die Rückleitungsseite `contacts-auth.html` darf keinen
`Cross-Origin-Opener-Policy`-Header erhalten, damit die MSAL-5-Redirect-Bridge
ihren Rückkanal behält. Das gilt auch für per Reverse Proxy ergänzte Header;
siehe [Microsofts Hinweise zur Redirect-Bridge](https://github.com/AzureAD/microsoft-authentication-library-for-js/blob/msal-browser-v5.25.1/lib/msal-browser/docs/redirect-bridge.md).

Ab **20261108.7** bleibt der Microsoft-Zugriffstoken zusätzlich im `sessionStorage`
des aktuellen Browsertabs, damit das Schließen und erneute Öffnen der Kontakteansicht
keine erneute Anmeldung verlangt, solange er gültig ist. Die Wiederverwendung ist
an dieselbe geprüfte CRM-Sitzung, denselben CRM-Benutzer und dieselbe Entra-Konfiguration
gebunden. Abgelaufene oder von Graph zurückgewiesene Tokens werden verworfen;
**Verbindung trennen** und CRM-Abmeldung löschen den Sitzungscache ebenfalls.
Es werden keine Refresh-Tokens gespeichert und keine Tokens dauerhaft in `localStorage`
oder im CRM abgelegt. Nur der zuletzt verwendete Microsoft-Benutzername wird als
unverbindlicher Kontohinweis in `localStorage` behalten. Der kurzzeitige MSAL-Cache
im Anmeldefenster wird vor der Rückgabe gelöscht; der stille Versuch verwendet
weiterhin einen MSAL-Cache im Arbeitsspeicher. Es gibt keine automatische
Token-Erneuerung im Hintergrund. Client-/Mandanten-ID und Redirect-URI sind
öffentliche Konfiguration; CRM-Zugangsdaten werden darüber nicht ausgegeben.

## Verwendung

### Automatische Microsoft-Verbindung

Nach der CRM-Anmeldung beziehungsweise der Prüfung einer gespeicherten
CRM-Sitzung versucht die Kontakteansicht automatisch, eine vorhandene
Microsoft-Anmeldung für `Contacts.ReadWrite` zu verwenden. Zunächst wird kein
Anmeldefenster geöffnet. Während der Prüfung bleibt das bisherige Anmeldemenü
verborgen; der Status lautet **Microsoft-Verbindung wird automatisch hergestellt …**.

Bei Erfolg steht dort **Microsoft-Konto verbunden: …**. Der Button
**Mit Microsoft verbinden** und der erläuternde Anmeldetext bleiben verborgen;
Kontaktordner und Übernahmefunktionen werden freigegeben. Das angezeigte Konto
bestimmt das persönliche Outlook-Adressbuch. Über **Verbindung trennen** lässt
sich das Menü bei Bedarf wieder einblenden und ein anderes Konto wählen.

Ab **Build 20261111.3** wird das Microsoft-Anmeldefenster bei Bedarf bereits beim
normalen Klick auf **CRM- und Outlook-Kontakte verwalten** aus dem angemeldeten
E-Mail-Seitenfenster geöffnet. Die Kontakteansicht übernimmt dieses Fenster nach
dem Seitenwechsel. Damit ist für diesen Ablauf keine Popup-Ausnahme erforderlich.
Bei einer noch gültigen zwischengespeicherten Verbindung entfällt die Vorbereitung;
gelingt die stille Anmeldung bereits, wird ein vorbereitetes Fenster wieder geschlossen.
Die Outlook-Plattform wird beim Übergang mitgegeben, damit eine verzögerte
`Office.onReady()`-Meldung den Ausweichweg nicht überspringt.

Auf `login_required` beziehungsweise
`AADSTS50058` folgt ein einmaliger Versuch über das bestehende Microsoft-Anmeldefenster.
Dabei wird `prompt=none` verwendet: Microsoft kann seine Sitzung im obersten Fenster
erkennen, auch wenn die Sitzungscookies im versteckten Iframe nicht verfügbar sind.
Wird die Kontakteansicht direkt ohne den ursprünglichen Klick geöffnet, kann der
Browser ein neu angefordertes automatisches Popup weiterhin blockieren. Ist ein Popup
blockiert oder fehlt auch dort eine nutzbare Microsoft-Sitzung, bleibt **Mit Microsoft
verbinden** verfügbar. Es gibt keine automatische Wiederholungsschleife.

Scheitert `prompt=none` auch im Anmeldefenster, wird dort einmalig eine normale
Microsoft-Anmeldung ohne erzwungene Kontoauswahl begonnen. Dafür öffnet sich kein
weiteres Fenster. Microsoft entscheidet, ob die bestehende Sitzung genügt oder
eine Eingabe/Zustimmung nötig ist. Nach einem weiteren Fehler endet der Versuch.
Fehler aus diesem Fenster werden unter **Technische Details** ausdrücklich als
**Microsoft-Anmeldefenster** gekennzeichnet.

Scheitert die automatische Verbindung oder dauert ihre Prüfung zu lange, wird das
bisherige Anmeldemenü eingeblendet. **Mit Microsoft verbinden** öffnet den Office-Dialog
beziehungsweise das Browserfenster und lässt Microsoft eine vorhandene Sitzung
ohne erzwungene Kontoauswahl verwenden. Nach **Verbindung trennen** wird die
Kontoauswahl ausdrücklich angeboten. Eine manuelle Anmeldung kann beispielsweise bei
fehlender Zustimmung, mehreren Microsoft-Konten oder blockierten Cookies nötig
sein. Der automatische Versuch allein erteilt keine Berechtigung. Siehe
[Microsofts Hinweise zu SSO](https://learn.microsoft.com/en-us/entra/msal/javascript/browser/single-sign-on)
und [Anmeldung in eingebetteten Anwendungen](https://learn.microsoft.com/en-us/entra/msal/javascript/browser/iframe-usage).
Die Ausweichmöglichkeit über ein Fenster mit `prompt=none` beschreibt Microsoft unter
[Prompt-Verhalten](https://learn.microsoft.com/en-us/entra/msal/javascript/browser/prompt-behavior#interactive-requests-with-promptnone).

Die vorhandene Entra-Appregistrierung und die SPA-Rückleitungsadresse
`https://EURE-CRM-ADRESSE/outlook-addin/contacts-auth.html` werden weiterverwendet.
Für dieses Update sind keine weiteren Entra-Einträge erforderlich. NAA wird nicht
verwendet. Manifest und Webservice-Registrierung bleiben bestehen. Nach der
CRM-Prüfung wird zuerst ein noch gültiger Token aus dem Browsertab verwendet.
Fehlt er, startet der stille MSAL-Iframe-Versuch ohne auf `Office.onReady()` zu
warten. Als Kontohinweis dient bevorzugt der zuletzt erfolgreich verbundene
Microsoft-Benutzername, ansonsten die verfügbare Outlook-Postfachadresse.
Blockierte Drittanbieter-Cookies, fehlende Zustimmung oder eine abgelaufene
Microsoft-Sitzung können weiterhin eine manuelle Anmeldung erfordern.

Unter **Technische Details** erscheinen die konkrete Fehlermeldung und Hinweise
zu möglichen Cookie-/Anmeldebeschränkungen. Beim Aufruf in dem von MSAL
erzeugten versteckten SSO-Iframe führt die Rückleitungsseite keine eigene Anmeldung
aus. Sie verwendet die mitgelieferte MSAL-5-Redirect-Bridge zur Übergabe der Antwort.
Ein von Outlook eingerahmtes interaktives Anmeldefenster wird dagegen normal
verarbeitet; ein übergeordnetes Fenster allein kennzeichnet keine stille Anmeldung.

Ab **Build 20261111.4** erscheinen technische Fehlerangaben in einem zunächst
geschlossenen Bereich **Technische Details**. Erst ein Klick zeigt die vollständige
Microsoft-Meldung einschließlich Fehlercode und Untercode. Bei einem neuen Fehler
wird der Bereich wieder geschlossen; die allgemeine Fehlermeldung bleibt sichtbar.
Die Buildkennung steht unten in der Kontakteansicht. Der bestätigte Anmeldeablauf
für Firefox und Edge bleibt unverändert.

Ab **Version 1.1.0.2 / Build 20261111.5** zeigt die Kontakteansicht abgelaufene
Anmeldungen mit einem sichtbaren Hinweis oberhalb der CRM-Anmeldung an:

- **CRM-Anmeldung abgelaufen:** oben erneut mit CRM-Benutzername und Access Key
  anmelden. Der Hinweis erscheint, sobald das CRM eine abgelaufene Sitzung meldet.
- **Microsoft-Verbindung abgelaufen oder nicht mehr gültig:** **Mit Microsoft
  verbinden** wählen. Die CRM-Anmeldung bleibt dabei erhalten.

Das bekannte Ablaufdatum der Microsoft-Verbindung wird auch ohne weiteren Zugriff
geprüft sowie bei Rückkehr zur Ansicht und vor Kontaktaktionen. Während einer
laufenden Übertragung wird der Wechsel der Ansicht zurückgestellt. Eine ungültige
Verbindung wird nicht für eine neue Übertragung verwendet. Es gibt keine
automatische Wiederholung von Kontaktübernahmen oder Hintergrund-Anfragen zum
Verlängern der CRM-Sitzung. Nach erfolgreicher Anmeldung verschwindet der Hinweis;
technische Microsoft-Meldungen bleiben unter **Technische Details** eingeklappt.

**Update-Dateien gemeinsam hochladen:**

- `outlook-addin/contacts.html`
- `outlook-addin/contacts.js`
- `outlook-addin/contacts-auth.html`
- `outlook-addin/assets/contacts-auth.bundle.js` und die zugehörige `.LEGAL.txt`
- `outlook-addin/assets/contacts-sso.bundle.js` und die zugehörige `.LEGAL.txt`

Die neue SSO-Bundle-Datei ist erforderlich. Die bestehende
`outlook-addin/rest/outlook.config.php` beibehalten. Danach die Kontakteansicht
schließen und erneut öffnen. Die aktualisierten Skripte verwenden die
Cache-Version `20261108.4`. Bei alten Meldungen oder altem Verhalten den
Browsercache der CRM-Add-in-Seite leeren.

Für Entwickler erzeugt `npm run build:auth` im Verzeichnis `outlook-addin/`
beide Microsoft-Anmeldebundles aus `contacts-auth-entry.js` und
`contacts-sso-entry.js`. Die MSAL-Lizenzdateien bleiben Bestandteil der Auslieferung.

### Microsoft-Anmeldung und Fehlermeldungen

Bei **Bitte die Microsoft-Anmeldung aus der berliCRM-Kontakteansicht starten**
kann der Browser nach der Microsoft-Weiterleitung `window.opener` getrennt haben.
Outlook im Browser verwendet ab Cache-Version **20261108.5** eine davon unabhängige
Rückgabe: Die Browserfenster verschlüsseln die Antwort mit AES-GCM. Der Server
hält nur den verschlüsselten Inhalt kurzzeitig in einem privaten PHP-Tempordner
außerhalb des Webroots vor. Nur die initiierende, authentifizierte CRM-Sitzung
kann ihn abholen; der Entschlüsselungsschlüssel bleibt im Browser. Die Übergabe
läuft höchstens fünf Minuten und kann über **Microsoft-Anmeldung abbrechen**
beendet werden. Ein durch COOP als geschlossen gemeldetes Anmeldefenster beendet
die Rückgabe nicht mehr vorzeitig.
Abgelaufene Antworten sind nicht mehr abrufbar. Verwaiste verschlüsselte Dateien
werden bei späteren Zugriffen bereinigt; Abschluss und Abbruch entfernen sie direkt.

Für dieses Update auch `outlook-addin/rest/outlook.php`,
`outlook-addin/rest/OutlookAuthRelay.php`, `contacts-auth-relay.js`, die beiden
Sprachdateien und das erzeugte `language.js` mit den HTML-/Anmeldeskripten
gemeinsam einspielen. Es benötigt keine neue CRM-Operation oder Datenbanktabelle,
wenn das Sprachupdate bereits registriert ist. PHP benötigt einen beschreibbaren
privaten Tempordner; optional legt `contacts_auth_relay_directory` in der privaten
Serverkonfiguration einen anderen Speicherort außerhalb des Webroots fest.

Wenn nach erfolgreicher Microsoft-Anmeldung die Seite **Anmeldung wird vorbereitet**
stehen bleibt, gemeinsam `outlook-addin/contacts-auth.html`,
`outlook-addin/assets/contacts-auth.bundle.js` und dessen `.LEGAL.txt` aktualisieren.
Das Anmeldebundle verwendet jetzt die Cache-Version `20261108.4`. Die Korrektur unterscheidet den
interaktiven Rückruf von MSALs verstecktem SSO-Iframe. Die erforderliche
Administratorzustimmung ersetzt diese Verarbeitung nicht.

In **Outlook im Browser** verwendet die manuelle Microsoft-Anmeldung den
vorhandenen Popup-Kanal. Das Microsoft-Anmeldefenster öffnet sich direkt beim
Klick auf **Mit Microsoft verbinden**. Es lädt kein Office.js und wartet nicht
auf `Office.onReady()`. Die Kontakteverwaltung bleibt im vorhandenen Add-in.
Klassisches Outlook verwendet weiterhin den Office-Dialog und wartet dort auf
die Office-Initialisierung. Der stille SSO-Rückruf benötigt ebenfalls kein Office.js.

Bei **Verbindung zum Outlook-Anmeldefenster wird hergestellt … Zeitüberschreitung**
die oben genannten Dateien gemeinsam auf Cache-Version `20261108.4` aktualisieren.
Damit wird der hängende Office-Dialog-Handshake in Outlook im Browser umgangen.
Ein vom Browser blockiertes Microsoft-Anmeldefenster muss zugelassen werden.
Die Rückmeldung wird nur vom erwarteten Fenster und der exakten CRM-Adresse
angenommen; Microsoft-Tokens bleiben in der Kontakteansicht und ihrer Tabsitzung.
Eine weitere Administratorfreigabe ist für dieses Code-Update nicht erforderlich.

Die Seite zeigt nun den jeweiligen Schritt: Konfiguration laden, Microsoft
initialisieren, Rückmeldung verarbeiten und an Outlook übergeben. Vorbereitung
und Outlook-Übergabe haben je 20 Sekunden Zeitlimit, die Microsoft-Rückmeldung und
Weiterleitung je 60 Sekunden. Nach einer Zeitüberschreitung erscheint eine
Fehlermeldung mit dem betroffenen Schritt unter **Technische Details**; danach
die Verbindung erneut starten. Die Zeitlimits gelten für unsere lokale Seite,
nicht für die Zeit zur Eingabe von Zugangsdaten auf Microsofts Anmeldeseite.
Manifest, IDs, Redirect-URI und Webservice-Registrierung bleiben unverändert.

Das Anmeldefenster zeigt einen eigenen berliCRM-Status für Vorbereitung,
erfolgreiche Verbindung und Fehler. Bei `access_denied` erscheint eine
verständliche Erklärung; die Originalmeldung steht unter **Technische Details**.
**Erneut versuchen** startet einen neuen Anmeldevorgang. Alternativ das Fenster
schließen und in der Kontakteansicht **Mit Microsoft verbinden** erneut wählen.
Eine Ablehnung kann etwa durch einen Abbruch oder fehlende Zustimmung entstehen;
die Gestaltung ändert keine Microsoft-Berechtigungen.

Die vorgelagerte Rückfrage **„… möchte ein neues Fenster anzeigen“** mit
**Zulassen/Ignorieren** kommt von Outlook selbst und kann vom Add-in nicht
gestaltet werden. Sie bleibt aktiviert, damit der Office-Dialog auch bei
Pop-up-Blockern zuverlässig geöffnet werden kann. Siehe
[Microsofts Hinweise zum Öffnungsdialog](https://learn.microsoft.com/en-us/office/dev/add-ins/develop/dialog-best-practices#handle-pop-up-blockers-with-office-on-the-web).

Für das neue Layout gemeinsam `outlook-addin/contacts-auth.html`,
`outlook-addin/contacts-auth.css` und `outlook-addin/assets/contacts-auth.bundle.js`
hochladen. Manifest, Entra-Konfiguration und Webservice-Registrierung bleiben
gleich.

### Kontakte auswählen und übertragen

1. Am CRM anmelden und die automatische Microsoft-Verbindung abwarten. Nur wenn
   sie nicht gelingt, **Mit Microsoft verbinden** im eingeblendeten Menü wählen.
   Das angezeigte Microsoft-Konto bestimmt das persönliche Outlook-Adressbuch.
2. Als Ziel und Quelle dienen zunächst die Standardkontakte. **Weitere Ordner
   laden** bietet zusätzlich eigene Kontaktordner einschließlich Unterordnern.
3. **CRM → Outlook:** Namen/E-Mail suchen, Treffer markieren, **Ausgewählte als
   Outlook-Kontakte anlegen** drücken. Je Suche werden höchstens 50 CRM-Treffer
   angezeigt; die Suche gegebenenfalls eingrenzen.
4. **Outlook → CRM:** **Outlook-Kontakte laden**, bei Bedarf **Weitere Kontakte
   laden**, dann vollständige Kontakte markieren und **Ausgewählte ins CRM
   übernehmen** drücken. Weitere Seiten behalten die bereits gesetzte Auswahl.
   „Alle vollständigen Kontakte“ bezieht sich auf die bereits geladenen Seiten.

Die Ansicht lädt jeweils bis zu 100 Outlook-Kontakte pro Seite und verarbeitet
die Auswahl einzeln. Unter jedem Kontakt stehen Erfolg, Überspringen oder Fehler.
Es gibt keinen automatischen Abgleich, keine Löschung und keine Überschreibung
vorhandener Kontakte. Ein Ordner- oder Kontowechsel verwirft die bisherige Auswahl.
Organisationsverzeichnis/GAL, Verteilergruppen und freigegebene Postfächer sind
nicht Bestandteil dieser Kontakteansicht.

## Pflichtfelder und Feldzuordnung

Der neue Add-in-Handler verwendet für CRM-Daten die Standardoperationen
`vtws_describe`, `vtws_query`, `vtws_retrieve` und `vtws_create`. Er ergänzt die
serverseitige Feldzuordnung und die Verknüpfung gegen doppelte Übernahmen.

Die Standardoperation `describe('Contacts')` liefert die sichtbaren Felder,
Schreibrechte, übersetzten Bezeichnungen und Pflichtfelder des angemeldeten
CRM-Nutzers. Unvollständige Outlook-Kontakte sind bereits in der Auswahl gesperrt;
unmittelbar vor jeder CRM-Anlage wird serverseitig erneut geprüft. Auch zusätzliche
Pflichtfelder werden berücksichtigt. Ein nicht zuordenbares Pflichtfeld führt
zum Überspringen mit Feldbezeichnung. Nachnamen werden nicht aus Anzeigenamen
erfunden. „Zuständig“ ist immer der angemeldete CRM-Nutzer.

| CRM-Feld | Outlook-Kontaktfeld |
| --- | --- |
| `firstname`, `lastname` | `givenName`, `surname` |
| `email`, `secondaryemail` | erste und zweite E-Mail-Adresse |
| `phone`, `mobile` | erste geschäftliche Telefonnummer, Mobiltelefon |
| `title`, `department` | Position, Abteilung |
| `description` | persönliche Notizen |
| `mailingstreet/city/state/zip/country` | Geschäftsadresse |
| `otherstreet/city/state/zip/country` | Privatadresse |

Weitere Telefonnummern/E-Mail-Adressen, Geburtstage, Fotos, Kategorien und
eigene Felder werden nicht übertragen. Ein Firmenname wird nicht automatisch
in einen CRM-Firmenverweis umgewandelt. Ist beispielsweise die Firma Pflicht,
wird der Kontakt wegen des fehlenden Verweises übersprungen. Ungültige
E-Mail-Adressen und zu lange Feldwerte werden als Fehler gemeldet. Ob eine
E-Mail-Adresse Pflicht ist, entscheidet die CRM-Feldkonfiguration.

## Wiederholungen und Fehler

Version **1.0.15.3** behebt **Failed to execute 'fetch' on 'Window': Illegal
invocation** beim Lesen und Anlegen von Outlook-Kontakten. Der gemeinsame
Graph-Client bindet den nativen `fetch`-Aufruf jetzt an das Browserfenster.
Gemeinsam `outlook-addin/contact-graph.js` und `outlook-addin/contacts.html`
hochladen; die Kontakteansicht lädt das Skript mit Cache-Version `20261103.3`.
Danach die Kontakteansicht schließen und neu öffnen. Die bestehenden Microsoft-
Berechtigungen und die Serverkonfiguration bleiben unverändert. Ein erneuter
Webservice-Registrierungslauf ist für diese Korrektur nicht nötig.

- Beim Export wird im ausgewählten Outlook-Ordner nach der stabilen CRM-Markierung
  und anschließend nach jeder übertragenen E-Mail-Adresse gesucht. Ein Treffer
  wird übersprungen. Die Markierung enthält einen Hash der CRM-Installationsadresse
  und die CRM-Kontakt-ID.
- Beim Import verhindert eine Verknüpfung aus angemeldetem CRM-Nutzer,
  Microsoft-Konto und unveränderlicher Outlook-Kontakt-ID wiederholte Anlagen.
  Ein zurückimportierter CRM-Export wird anhand seiner Markierung erkannt.
  Ohne Verknüpfung wird zusätzlich die erste Outlook-E-Mail mit dem primären
  `email`-Feld lesbarer CRM-Kontakte verglichen: ein Treffer wird übersprungen,
  mehrere Treffer werden als mehrdeutig gemeldet. Es werden keine Rechte umgangen.
- Kontakte ohne E-Mail werden anhand der Verknüpfung/Markierung erkannt;
  ein Namensvergleich allein gilt nicht als Dublettenbeweis.
- Ist nach einem Verbindungsabbruch unklar, ob eine Anlage erfolgreich war,
  wird nicht blind erneut angelegt. Outlook-Exporte halten dafür einen lokalen
  Pending-Eintrag vor; ein später bestätigter Treffer löst ihn auf. CRM-Importe
  behalten einen `pending`-Eintrag in `vtiger_outlook_contact_link`.
  Ein Administrator muss dann den Zieldatensatz prüfen und die Verknüpfung
  gezielt korrigieren. Ein `pending`-Eintrag darf erst entfernt werden, wenn
  sicher keine Anlage erfolgt ist. Keine pauschale Löschung dieser Tabelle.
- Kontaktanlagen erfolgen einzeln, nicht als gemeinsame Transaktion über beide
  Systeme. Bereits erfolgreiche Kontakte bleiben bei späteren Fehlern erhalten.
  Die Ansicht blockiert parallele Klicks; parallele Exporte derselben Kontakte
  aus mehreren Browsern sollten vermieden werden, da Graph keine eindeutige
  CRM-Schlüsselbeschränkung für Kontakte erzwingt.

## Entwicklung und geprüfter Umfang

Die Microsoft-Bibliothek wird lokal ausgeliefert, einschließlich Lizenztexten.
Auf dem Server ist kein Node.js nötig. Zum Neubauen im Ordner `outlook-addin`:

```sh
npm ci --ignore-scripts --no-audit --no-fund
npm run build:auth
```

Tests vom CRM-Hauptverzeichnis aus:

```sh
node --test outlook-addin/tests/*.test.cjs
php outlook-addin/tests/contacts-server.php
php outlook-addin/tests/contacts-config.php
php outlook-addin/tests/calendar-server.php
php outlook-addin/tests/calendar-manifest.php
```

Die Tests verwenden simulierte CRM-/Graph-Antworten und Datenbanken. Der echte
Entra-Login, die Zustimmung und Kontaktanlagen in einem echten Postfach müssen
nach Konfiguration mit Testkontakten geprüft werden. Dabei auch Rückimport,
erneuten Klick, einen Kontakt ohne Nachnamen und ein eigenes CRM-Pflichtfeld prüfen.

## Microsoft-Dokumentation

- [Unterstützte Outlook-Add-in-Kontexte](https://learn.microsoft.com/en-us/office/dev/add-ins/outlook/outlook-add-ins-overview)
- [Microsoft-Entra-SPA konfigurieren](https://learn.microsoft.com/en-us/entra/identity-platform/scenario-spa-app-configuration)
- [Outlook-Kontakte lesen und nach E-Mail filtern](https://learn.microsoft.com/en-us/graph/api/user-list-contacts?view=graph-rest-1.0)
- [Outlook-Kontakte anlegen und erforderliche Berechtigung](https://learn.microsoft.com/en-us/graph/api/user-post-contacts?view=graph-rest-1.0)
- [Unveränderliche Outlook-IDs](https://learn.microsoft.com/en-us/graph/outlook-immutable-id)
- [MSAL 5: direkte Redirect-Verarbeitung](https://github.com/AzureAD/microsoft-authentication-library-for-js/blob/msal-browser-v5.25.1/lib/msal-browser/docs/redirect-bridge.md)
