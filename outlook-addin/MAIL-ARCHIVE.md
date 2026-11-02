# Empfangene Outlook-E-Mails im CRM ablegen

Beim Öffnen einer vorhandenen E-Mail zeigt das berliCRM-Add-in eine eigene
Leseansicht: CRM-Anmeldung beziehungsweise Anmeldestatus und **E-Mail im CRM
ablegen** sowie den Link **CRM- und Outlook-Kontakte verwalten**. Dieser Link
ist auch beim Lesen aus der Nachrichtenliste sichtbar. Die Empfängersuche, Verteiler und letzten Termine gehören weiterhin
zur Erstellung von E-Mails und sind in der Leseansicht ausgeblendet.

> **WICHTIG für Edge und Firefox:** Bei manueller Manifest-Installation muss
> in Outlook **„Optionale verbundene Erfahrungen“ eingeschaltet** sein.
> Andernfalls kann die App zwar zunächst erscheinen, ist aber nicht nutzbar.
> Den genauen Einstellungsweg und den Test für wiederholtes Öffnen beschreibt
> die [Installationsdokumentation](README.md#wichtig-optionale-verbundene-erfahrungen).

## Benutzung

1. Eine E-Mail in Outlook öffnen und das berliCRM-Add-in starten.
2. Falls erforderlich mit CRM-Benutzername und Access Key anmelden. Eine bereits
   gespeicherte CRM-Sitzung wird geprüft; bei Ablauf erscheint die Anmeldung wieder.
3. **E-Mail im CRM ablegen** wählen. Gesucht werden lesbare Contacts-Datensätze,
   deren primäres Feld `email` exakt zur Adresse im **Von**-Feld gehört.
   Die sichtbare Bezeichnung dieses Felds (beispielsweise „Primäre E-Mail“)
   wird für den angemeldeten Nutzer aus `describe('Contacts')` gelesen und
   beim Start sowie bei jeder Suche aktualisiert. Umbenannte Labels werden
   auch in der Meldung für eine ergebnislose Suche verwendet.
   Die abweichende technische Sender-Adresse bei delegiertem Versand wird dafür
   nicht verwendet. Sekundäradressen, Leads und Firmen sind keine Suchquelle.
4. In der Liste werden Vorname, Nachname und zugehöriger Firmenname angezeigt,
   soweit vorhanden und lesbar. Einen oder mehrere Kontakte markieren.
   Es wird kein Kontakt automatisch ausgewählt.
5. Bei vorhandenen Anhängen kann **Anhänge übernehmen** gewählt bleiben oder
   bewusst ausgeschaltet werden. Danach **E-Mail bei ausgewählten Kontakten
   ablegen** wählen. Das Ergebnis erscheint direkt unter der Liste.

Es entsteht ein CRM-Datensatz im Modul **Emails**, der mit allen ausgewählten
Contacts über die CRM-E-Mail-Beziehung verknüpft wird. Die E-Mail ist dadurch
bei diesen Kontakten in den zugehörigen E-Mails verfügbar. Sie wird im CRM mit
Status `SAVED` gespeichert. Es wird weder eine E-Mail gesendet noch die
Outlook-Nachricht verändert.

Übertragen werden Betreff, bereinigter HTML-Nachrichtentext, Von-Adresse,
die von Office.js gelieferten An-/Cc-Adressen und das Outlook-Erstellungsdatum
der Nachricht mit Uhrzeit in der CRM-Datenbankzeitzone. Ein leerer Betreff wird
als „(Ohne Betreff)“ gespeichert. Die Anhänge werden als CRM-E-Mail-Anhänge
gespeichert. Eine Originaldatei `.msg`/`.eml` der gesamten Nachricht wird nicht
zusätzlich erzeugt. Office.js kann je nach Outlook-Client und Ansicht nur einen
Teil sehr großer Empfängerlisten liefern; die Kopfzeilen sind daher kein
vollständiger MIME-Export.

## Update

Das Paket `outlook-addin/build/outlook-update-20261102.12.zip` im CRM-Hauptverzeichnis
entpacken. Es enthält die benötigten Laufzeitdateien für die vorhandene Mail-,
Kalender- und Kontakteansicht. Der HTTP-Endpunkt liegt jetzt unter
`outlook-addin/rest/outlook.php`. Die private Konfiguration wird nicht mitgeliefert:
**Die bestehende Serverdatei `rest/outlook.config.php` nach
`outlook-addin/rest/outlook.config.php` verschieben und ihren Inhalt sowie die
Dateirechte beibehalten.** Eine dort bereits vorhandene Konfiguration zuerst
vergleichen und nicht überschreiben. Alle Dateien des Pakets gemeinsam hochladen;
das Microsoft-Anmeldebundle und die Kontakteansicht verwenden ebenfalls den neuen
Pfad. Die bisherigen Outlook-PHP-Dateien unter `/rest/` können nach erfolgreichem
Test entfernt werden; den Ordner nur entfernen, wenn er leer ist.

Für diese Verschiebung ist keine neue Webservice-Registrierung nötig. Die CRM-URL
`webservice.php` und die Microsoft-Rückleitungsadresse bleiben gleich.
Bei einem CRM-Versionsupdate ruft `db_update.php` die gemeinsame Routine
`outlook-addin/update.php` automatisch auf und initialisiert die sechs
Webservices sowie fehlende Verknüpfungstabellen vor dem Fortschreiben der
CRM-Versionsnummer. Die fünf Outlook-Werkzeuge liegen jetzt unter
`outlook-addin/build/`. Die alte Ablage unter `/build/` wird nicht mehr benötigt.
Der Updateablauf registriert außerdem **Einstellungen → Integration → Outlook
Add-in**. Dort können Administratoren das Manifest und eine Konfiguration mit
verpflichtender Client-ID und Mandanten-ID herunterladen. Die neue Settings-Seite,
ihre Vorlage und Sprachdateien müssen mit hochgeladen werden. Details stehen in
[CONTACTS.md](CONTACTS.md#einrichtung-über-das-crm-settings-menü).
Der zusätzliche Settings-Button **Microsoft-Verbindung prüfen** testet die
gespeicherte Konfiguration mit einer Anmeldung und einem lesenden Kontaktzugriff.
Die neue Datei `outlook-addin/settings-connection.js` gehört zu dieser Funktion.
Für eine separate Einrichtung oder Reparatur im CRM-Hauptverzeichnis ausführen:

```sh
php outlook-addin/build/registerOutlookAddinOperation.php
```

Zusätzlich registriert wird die POST-Operation `outlook_archive_email` mit dem
JSON-Parameter `input`. Die eigenen Tabellen `vtiger_outlook_mail_link` und
`vtiger_outlook_mail_file` speichern bestätigte und noch unbestätigte Ablagen.
Der Installer kann wiederholt ausgeführt werden. Insgesamt sind danach sechs
Outlook-Operationen registriert. Die Ablage verwendet intern die Standard-
Webservices `describe`, `query`, `retrieve` und `create`; nur die sichere
Zuordnung zu mehreren Kontakten und die Wiederholungskontrolle kommen hinzu.
Core-Dateien und Benutzer-/Profilberechtigungen werden nicht geändert.

Die bestehende Manifest-Aktivierung für gelesene E-Mails reicht aus. Ein neues
Manifest ist für diese Änderung nicht erforderlich. Nach dem Hochladen den
Aufgabenbereich neu laden; falls noch die bisherige Ansicht erscheint,
den Cache für die CRM-Add-in-Seite leeren. In deren HTML müssen
`mail-read.js?v=20261102.8`, `calendar.js?v=20261102.6` und
`taskpane.js?v=20261102.4` geladen werden. `contacts.js` und die beiden
Microsoft-Anmeldebundles verwenden die Cache-Version `20261102.10`; das eigene
Stylesheet `contacts-auth.css` verwendet weiterhin `20261102.9`.

Die Kontakteansicht versucht die Microsoft-Verbindung jetzt automatisch. Das
bisherige Anmeldemenü erscheint nur bei fehlgeschlagener automatischer Verbindung.
Dafür auch das neue `assets/contacts-sso.bundle.js` und die aktualisierte
Rückleitungsseite gemeinsam hochladen; die vollständige Dateiliste und der Ablauf
stehen in [CONTACTS.md](CONTACTS.md#automatische-microsoft-verbindung).

Der Kontakte-Link bleibt jetzt auch in der E-Mail-Leseansicht sichtbar. Für
diese Ergänzung `outlook-addin/mail-read.js` und `outlook-addin/taskpane.html`
gemeinsam aktualisieren. Eine Webservice-Registrierung oder Manifest-Installation
ist dafür nicht erforderlich.

Auch beim Erstellen einer E-Mail werden bei vorhandener CRM-Sitzung nur noch
der Loginstatus und **Anders anmelden** angezeigt. Für diese Anzeigekorrektur
genügt es, `outlook-addin/calendar.js` und `outlook-addin/taskpane.html` gemeinsam
zu aktualisieren; keine Webservice-Registrierung oder Manifest-Installation nötig.
Outlook verwaltet das Anpinnen für Lesen und Schreiben getrennt: Das Add-in muss
beim Schreiben einmal separat geöffnet und dort ebenfalls angepinnt werden.
Siehe [Microsoft-Dokumentation](https://learn.microsoft.com/en-us/office/dev/add-ins/outlook/pinnable-taskpane#supported-outlook-modes).

Das Paket enthält auch die Korrektur der E-Mail-Feldlängenprüfung. Eine erneute
Webservice-Registrierung oder Datenbankänderung ist dafür nicht nötig.
Negative `fieldsize`-Werte wie `-1`
bei TEXT-Spalten werden als nicht angegeben behandelt. Nur positive Werte
werden als Zeichenlimit geprüft; CC-/An-Adressen werden nicht gekürzt.

Diese Funktion liest die aktuelle E-Mail über Office.js und braucht keine
zusätzliche Entra-/Graph-Konfiguration. Die separate Outlook-Kontakteübernahme
verwendet weiterhin ihre in [CONTACTS.md](CONTACTS.md) beschriebene Graph-Anbindung.

## Berechtigungen und Verhalten bei Fehlern

- Der angemeldete Nutzer braucht Lesezugriff auf `Contacts.email`, die ausgewählten
  Kontakte und das Modul Emails sowie Erstellrechte für Emails. „Zuständig“ ist
  immer der angemeldete CRM-Nutzer. Vor dem Speichern werden Leserechte und
  Von-Adresse jedes ausgewählten Kontakts erneut geprüft. Ein zwischenzeitlich
  geänderter Kontakt führt zur erneuten Suche.
- Der Firmenname wird nur über eine erlaubte Accounts-Abfrage gelesen.
  Ohne Firmenleserecht bleibt die zusätzliche Firmenangabe leer.
- Die Suche lädt alle exakten Treffer in Seiten zu 100 Datensätzen. Pro Ablage
  können höchstens 1000 Kontakte ausgewählt werden. Die Kontakte erhalten
  Verknüpfungen zu derselben CRM-E-Mail; vorhandene Verknüpfungen bleiben bestehen.
- Ein Wechsel der E-Mail oder CRM-Anmeldung verwirft die Treffer und Auswahl.
  Verspätete Suchantworten werden ignoriert. Ein Wechsel während des Einlesens
  verhindert die anschließende CRM-Anfrage. Bereits abgeschickte Speicheranfragen
  können serverseitig zu Ende laufen und werden nicht einer neuen Auswahl zugeordnet.
- Wiederholte Ablagen desselben Nutzers verwenden die vorhandene CRM-E-Mail und
  ergänzen fehlende Verknüpfungen. Dazu werden Postfach und Internet-Message-ID
  verwendet; falls diese nicht verfügbar ist, die Outlook-Item-ID. Nach Verschieben
  einer E-Mail kann sich die Item-ID ändern. Ohne Internet-Message-ID ist dann
  keine sichere Erkennung über Ordnerwechsel hinweg zugesichert.
- Eine bereits abgelegte E-Mail wird beim erneuten Verknüpfen nicht inhaltlich
  überschrieben. Weitere Kontakte oder noch nicht übernommene Anhänge können durch
  erneutes Starten der Suche ergänzt werden. Für Änderungen braucht der Nutzer
  auch Schreibrechte auf der bestehenden CRM-E-Mail.
- Bei unklaren Anlagefehlern bleiben `pending`-Verknüpfungen stehen, damit ein
  erneuter Klick keine zweite E-Mail oder einen zweiten Anhang erzeugt.
  Ein Administrator muss dann den CRM-Datensatz beziehungsweise Anhang und die
  Verknüpfung prüfen. Solche Einträge erst nach gesicherter Prüfung korrigieren.
  Bereits bestätigte E-Mails mit fehlgeschlagener Kontaktverknüpfung werden beim
  erneuten Versuch wiederverwendet. Teilweise erfolgreiche Ablagen werden nicht
  automatisch gelöscht.

## Anhänge

Die Übernahme von Dateianhängen benötigt Office.js Mailbox 1.8 oder neuer und
`getAttachmentContentAsync`. Unterstützt sind Base64-Dateien sowie von Office.js
gelieferte angehängte E-Mails/Kalenderdateien. Einschränkungen von Outlook beim
Lesen per Drag-and-drop angehängter Elemente gelten weiterhin.

Cloud-Anhänge, die nur als URL geliefert werden, werden nicht automatisch
heruntergeladen. Kann ein angeforderter Anhang nicht gelesen werden, bricht die
Ansicht vor der CRM-Ablage mit einer Meldung ab. Wer bewusst nur Text und Betreff
speichern möchte, kann die Anhangoption ausschalten.

Grenzen: 100 Anhänge, 10 MiB je Datei, insgesamt 20 MiB und 2 MiB Nachrichtentext.
Für große Nachrichten müssen PHP `post_max_size` und die Webserver-/Proxy-Grenzen
den JSON-Request einschließlich Base64-Aufschlag zulassen, beispielsweise 40M.
Dateinamen werden mit der vorhandenen CRM-Dateinamenprüfung bereinigt; gesperrte
Dateiendungen werden nach CRM-Regeln entschärft. Inline-Bilder werden als Dateien
gespeichert; eingebettete `cid:`-Verweise werden derzeit nicht neu aufgelöst.

## Prüfung

```sh
node --test outlook-addin/tests/*.test.cjs
php outlook-addin/tests/mail-archive-server.php
php outlook-addin/tests/calendar-manifest.php
```

Die Tests verwenden simulierte CRM-/Office-Antworten und eine isolierte Datenbank-
Simulation. Nachrichtentext wird zusätzlich mit der echten CRM-HTMLPurifier-
Bibliothek geprüft. Echte CRM-Datensätze und Postfächer werden in diesen Tests
nicht verändert. Nach dem Deployment mit einer Testmail an zwei Kontakte gleicher
Von-Adresse prüfen: Firmennamen, Mehrfachauswahl, Anzeige bei beiden Kontakten,
erneuter Klick, Dateianhang und Wechsel zu einer anderen E-Mail.

Die Office.js-Schnittstellen sind in Microsofts
[MessageRead-Dokumentation](https://learn.microsoft.com/en-us/javascript/api/outlook/office.messageread?view=outlook-js-1.16)
und [AttachmentContent-Dokumentation](https://learn.microsoft.com/en-us/javascript/api/outlook/office.attachmentcontent?view=outlook-js-1.8)
beschrieben.
