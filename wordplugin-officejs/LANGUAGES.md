# Sprache der Office-Add-ins

[English](LANGUAGES.en.md)

Word ab **1.4.0.0** und Outlook ab **1.1.0.0** verwenden die Sprache des
angemeldeten **CRM-Benutzers**. Die Auswahl erfolgt im CRM-Benutzerprofil unter
**Meine Einstellungen / Sprache**, nicht im Add-in. Unterstützt sind `de_de`
und `en_us`; andere Sprachen fallen auf Englisch zurück. Nach einer Änderung
das Add-in erneut öffnen beziehungsweise erneut am CRM anmelden.

Vor der CRM-Anmeldung gilt die Office-Anzeigesprache, ersatzweise die
Browsersprache. Eine gespeicherte Outlook-Anmeldung wird erneut geprüft und
ihre aktuelle CRM-Sprache abgefragt. Die separate Kontakteansicht und das
Microsoft-Anmeldefenster übernehmen diese Sprache ebenfalls.

Die von Office selbst angezeigten Menütexte folgen der Office-Sprache.
Die Manifeste enthalten Deutsch als Standard und englische Übersetzungen.
CRM-Feldbezeichnungen kommen weiterhin aus den CRM-Metadaten; persönliche
Kalender-Datumsformate und Zeitzonen bleiben erhalten. Vorhandene Word-Vorlagen
werden über Feldaliase für beide Sprachen unterstützt.

## Update auf dem Server

Die neuen Sprachdateien, Browserdateien, aktualisierten Handler und
`include/utils/OfficeAddinI18n.php` zusammen übertragen. Private
`outlook-addin/rest/outlook.config.php` beibehalten.

**Wichtig:** Der neue, authentifizierte und ausschließlich lesende Webservice
`office_addin_user_preferences` muss registriert sein. Er liest nur die Sprache
des Benutzers der aktuellen CRM-Sitzung und nimmt keine Benutzer-ID entgegen.
Die vorhandenen Settings-Installationsfunktionen und `db_update.php` verwenden
die erweiterte Registrierung bereits. Bei einem separaten Add-in-Update im
CRM-Settings-Menü die Installation reparieren oder vom CRM-Webroot aus ausführen:

```sh
php wordplugin-officejs/build/registerWordMailMergeOperation.php
php outlook-addin/build/registerOutlookAddinOperation.php
```

Beide Befehle dürfen wiederholt werden. Alternativ registriert jeder der
Befehle den gemeinsamen Sprach-Webservice auch einzeln. Danach die jeweiligen
Manifeste im Settings-Menü neu erzeugen und die Add-ins aktualisieren.

## Übersetzungen pflegen

Die einzigen editierbaren Textquellen sind PHP-Arrays mit stabilen Schlüsseln:

- `wordplugin-officejs/languages/de_de.php` und `en_us.php`
- `outlook-addin/languages/de_de.php` und `en_us.php`

Beispiel:

```php
'USERNAME' => 'Benutzername', // de_de.php
'USERNAME' => 'Username',     // en_us.php
```

Neue Schlüssel immer in beiden Dateien ergänzen. `{0}`, `{1}` usw. sind
Positionsparameter und müssen in beiden Sprachen gleich vorkommen. Werte sind
Text, kein HTML. Schlüssel bei späteren Textänderungen beibehalten.

Nach Textänderungen aus dem Repository-Webroot mit Node.js und PHP CLI ausführen:

```sh
node wordplugin-officejs/build/build-languages.mjs
```

Falls PHP nicht im Suchpfad liegt, `PHP_BINARY` setzen. Unter Windows verwendet
der Builder sonst `C:/php/php.exe`. Der Builder prüft Schlüssel und Parameter
und erzeugt beide `language.js` sowie die Manifeste aus `manifest.template.xml`.
Diese erzeugten Dateien ebenfalls ausliefern und versionieren. Für Änderungen
an der Microsoft-Anmeldelogik zusätzlich `npm run build:auth --prefix outlook-addin`
ausführen. Produktionsserver benötigen keinen Node.js-Build.

Prüfungen:

```sh
node --test wordplugin-officejs/tests/*.test.js
node --test outlook-addin/tests/*.test.cjs
```
