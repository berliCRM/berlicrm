# Outlook-Einrichtung, Diagnose und Versionsupdates

Die Outlook-Werkzeuge liegen vollständig unter `outlook-addin/build/`.
Die folgenden Befehle werden im CRM-Hauptverzeichnis ausgeführt.

## Normaler CRM-Versionswechsel

Beim normalen CRM-Update `db_update.php` über den vorhandenen CRM-Updateablauf
ausführen. Dessen Outlook-Abschnitt lädt `outlook-addin/update.php` und
initialisiert alle sechs Outlook-Webservices und fehlende Verknüpfungstabellen.
Eine zusätzliche manuelle Webservice-Registrierung ist dann nicht notwendig.
Bestehende Kalender-, Kontakt- und E-Mail-Verknüpfungen bleiben erhalten.
Eine fehlgeschlagene Outlook-Initialisierung verhindert das anschließende
Fortschreiben der CRM-Versionsnummer. Die Registrierung kann wiederholt werden.

`db_update.php` führt auch die übrigen CRM-Updates aus. Für eine gezielte
Outlook-Einrichtung oder Reparatur steht deshalb der folgende Befehl bereit;
er verwendet dieselbe Outlook-Update-Routine:

```sh
php outlook-addin/build/registerOutlookAddinOperation.php
```

## Manifest erzeugen

```sh
php outlook-addin/build/generateOutlookManifest.php
```

Liest die HTTPS-Adresse `site_URL` aus der CRM-Datei `config.inc.php` und
erzeugt `outlook-addin/manifest.generated.xml` aus `outlook-addin/manifest.xml`.
Die Vorlage wird dabei nicht geändert. Erzeugen und Installieren eines
Manifests sind separate Schritte. Das Verschieben der Werkzeuge erfordert
keine neue Manifest-Version oder Neuinstallation des Add-ins.

## Diagnose

```sh
php outlook-addin/build/debugOutlookWebservice.php --username CRM-BENUTZER --search test
php outlook-addin/build/checkVerteilerWebservice.php --user-id 3
```

Das erste Werkzeug prüft die HTTP-Webservices. Die CRM-Adresse wird aus
`outlook-addin/rest/outlook.config.php` gelesen oder mit `--url` übergeben.
Der Access Key wird verdeckt abgefragt. Das zweite Werkzeug liest die lokale
Verteiler-Registrierung und die effektiven Benutzerrechte.

In einer bestehenden Exchange-Online-PowerShell-Sitzung mit Leserechten:

```powershell
.\outlook-addin\build\checkOutlookAddinRegistration.ps1 -MailboxIdentity 'user@example.org' |
    Format-List
```

Die Diagnosewerkzeuge ändern keine CRM-Datensätze oder Berechtigungen.
Die PHP-Werkzeuge finden die CRM-Installation relativ zu ihrer eigenen Datei.
Aus einem anderen Arbeitsverzeichnis daher den vollständigen Skriptpfad
verwenden; innerhalb von `outlook-addin/` genügt beispielsweise
`php build/registerOutlookAddinOperation.php`.

## Vorhandene Installation umstellen

1. Das vollständige Update-Paket im CRM-Hauptverzeichnis entpacken, damit auch
   `db_update.php` den neuen Outlook-Update-Einstieg verwendet.
2. Die private Konfiguration unter `outlook-addin/rest/outlook.config.php`
   beibehalten. Liegt sie noch unter `rest/outlook.config.php`, mit gleichem
   Inhalt und denselben Dateirechten an den neuen Ort verschieben. Eine bereits
   vorhandene Zieldatei zuerst vergleichen und nicht überschreiben.
3. Bei einem normalen CRM-Versionswechsel `db_update.php` ausführen; bei einer
   gezielten Outlook-Einrichtung den oben genannten Registrierungsbefehl nutzen.
4. Die alten fünf Outlook-Skripte unter dem bisherigen `/build/` können nach
   erfolgreichem Test entfernt werden. Andere CRM-Builddateien dort behalten.

Die Microsoft-Rückleitungsadresse bleibt `/outlook-addin/contacts-auth.html`.
Die Kontakteansicht versucht beim Öffnen automatisch eine Microsoft-Verbindung.
Das Anmeldemenü wird nur bei einem Fehlschlag eingeblendet. Dafür das neue
`outlook-addin/assets/contacts-sso.bundle.js` zusammen mit der Kontakteansicht
und dem aktualisierten Microsoft-Anmeldebundle ausliefern; die genaue Dateiliste
steht in [CONTACTS.md](../CONTACTS.md#automatische-microsoft-verbindung).
Für diese Änderung sind weder `db_update.php` noch eine Manifest-Neuinstallation
oder neue Entra-Rückleitungsadressen erforderlich.
Die vollständige Dokumentation steht in [README.md](../README.md),
[CONTACTS.md](../CONTACTS.md) und [MAIL-ARCHIVE.md](../MAIL-ARCHIVE.md).
