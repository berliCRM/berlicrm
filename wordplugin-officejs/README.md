# berliCRM Word Add-in

Dieses Office.js-Add-in ersetzt das frühere VB6/32-Bit-COM-Plugin. Es fügt
Word-`MERGEFIELD`s aus berliCRM-Feldern ein und erstellt Serienbriefe aus
gespeicherten CRM-Listenansichten.

## Einrichtung in berliCRM v8x

Nach dem regulären CRM-Datenbankupdate (`db_update.php`) finden Administratoren
die Seite unter **CRM-Einstellungen > Integration > Word Add-in**.
Das Datenbankupdate registriert den Menüeintrag und beide Word-Webservices
einschließlich ihrer Parameter automatisch. Die Seite prüft Serverdateien und
Webservice-Registrierungen, bietet eine Schaltfläche zum erneuten Einrichten
oder Reparieren und erzeugt ein XML-Manifest für die in
`$site_URL` konfigurierte HTTPS-Adresse. Die Vorlage `manifest.xml` enthält nur
Beispieladressen; verwenden Sie den Manifest-Download auf der Admin-Seite.

Für eine gezielte Ersteinrichtung ohne vollständiges Datenbankupdate kann der
Serveradministrator alternativ ausführen; nach einem regulären Datenbankupdate
ist dieser zusätzliche Aufruf nicht erforderlich:

```sh
php wordplugin-officejs/build/registerWordMailMergeOperation.php
```

Das wiederholbare Skript registriert das Admin-Menü und beide Webservices.
Ein bereits unter „andere Einstellungen“ angelegter Menüeintrag wird dabei
nach „Integration“ verschoben; seine ID und Favoritenmarkierung bleiben erhalten.
Direkter Seitenaufruf für Administratoren:
`index.php?module=WordAddin&parent=Settings&view=Index`.

Die Installationshilfe unterscheidet zentrale Microsoft-365-Bereitstellung,
Einzeltests in Word im Web und einen Windows-Testkatalog ohne Exchange. Sie
verlinkt die jeweiligen Microsoft-Anleitungen. Die lokale Dateiprüfung ersetzt
keine Prüfung von Zertifikat und Erreichbarkeit auf einem Word-Arbeitsplatz.

Tests der Admin-Integration ohne CRM-Datenbank:

```sh
php build/tests/word-addin-settings.php
```

Die vollständige Anleitung zur Serverinstallation, Word-Installation ohne
Exchange und zentralen Bereitstellung über Microsoft 365/Exchange sowie zur
Schritt-für-Schritt-Nutzung befindet sich in
[DOKUMENTATION.md](./DOKUMENTATION.md).

## Technische Hinweise

- Für Kontakte, Firmen, Leads und Tickets können gespeicherte Listenansichten
  als Datensatzquelle verwendet werden. Benutzerfelder können in Vorlagen
  eingefügt werden, Benutzer selbst sind keine Empfängerquelle.
- Pro Export können höchstens 1.000 Datensätze ausgewählt werden. Serverseitige
  und clientseitige Abrufe erfolgen in Seiten bzw. Batches von höchstens 100
  Datensätzen.
- Die Ausgabe kann ein gemeinsames `.doc`-Dokument mit einem Datensatz pro
  Seite oder einzelne Briefe in einer ZIP-Datei sein.
- Optional können individuelle Briefe zusätzlich als CRM-Dokumente beim
  jeweiligen Datensatz gespeichert werden. Dafür muss auch
  `saveWordMailMergeDocument.php` installiert und die Webservice-Operation
  registriert sein.
- Der CRM-Server prüft Modul-, Listenansichts- und Datensatzberechtigungen.
- Die Verbindung verwendet `getchallenge`, `login`, `listtypes` und `describe`
  am berliCRM-Webservice. API-Schlüssel und Sitzung werden nicht dauerhaft
  gespeichert.

## Entwicklung und Tests

Die Projektdateien sind statisch und benötigen keinen Build-Schritt. Für die
REST- und Feldnamen-Tests wird Node.js benötigt:

```powershell
npm test
```
