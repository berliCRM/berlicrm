# berliCRM Word-Add-in – Installations- und Benutzerhandbuch

Dieses Dokument beschreibt die Bereitstellung des Office.js-Add-ins auf dem
berliCRM-Server, die Installation in Word ohne Exchange und die zentrale
Bereitstellung über Microsoft 365/Exchange sowie die tägliche Nutzung.

## Einrichtung über die berliCRM-v8x-Administration

Die in v8x integrierte Installationshilfe ist unter **CRM-Einstellungen >
Integration > Word Add-in** erreichbar. Nach dem Übertragen der
Dateien registriert das reguläre `db_update.php` den Menüeintrag und beide
Word-Webservices samt Parametern. Ein zusätzlicher CLI-Aufruf ist anschließend
nicht erforderlich. Alternativ
richtet `php wordplugin-officejs/build/registerWordMailMergeOperation.php`
gezielt Menü und beide Webservices ein.

1. Öffnen Sie die Admin-Seite und prüfen Sie die angezeigten Serverdateien.
2. Prüfen Sie die Webservice-Registrierungen. Bei Bedarf wählen Sie
   **Webservices einrichten / reparieren**. Dies registriert sowohl
   `getWordMailMergeData` als auch `saveWordMailMergeDocument` mit ihren Parametern
   erneut; das reguläre Datenbankupdate hat diesen Schritt bereits ausgeführt.
3. Prüfen Sie die verlinkten Add-in- und Icon-Adressen auf einem Word-Arbeitsplatz.
4. Laden Sie das Manifest über **Manifest herunterladen (.xml)** herunter.
   Es übernimmt die konfigurierte HTTPS-Adresse `$site_URL` einschließlich
   Unterverzeichnis und Port; manuelle URL-Ersetzungen sind damit unnötig.
5. Folgen Sie der Installationshilfe auf der Admin-Seite für Ihre Word-Umgebung.

Der direkte Upload eines eigenen Manifests ist ein Testweg für Word im Web und
nicht in jeder Word-Desktop-Version verfügbar. Der Netzwerkfreigabekatalog ist
laut Microsoft ein Testweg für Windows. Für die zentrale Bereitstellung verwenden
Sie das Microsoft-365-Admin-Center. Die Admin-Seite verlinkt die aktuellen
Microsoft-Anleitungen. Die folgenden manuellen Schritte beschreiben die
Dateibereitstellung; in v8x ersetzt der Manifest-Download die manuelle Bearbeitung
der Beispieladressen und die Admin-Schaltfläche die CLI-Webservice-Registrierung.

## 1. Installation auf dem berliCRM-Server

Die Installation besteht aus zwei Teilen:

1. Die Add-in-Dateien werden über HTTPS bereitgestellt.
2. Eine zusätzliche REST-Operation wird in berliCRM registriert, damit das
   Add-in Listenansichten und Datensätze abrufen kann.

### Voraussetzungen

- Administrativer Zugriff auf den berliCRM-Webserver und die berliCRM-Dateien.
- PHP-CLI in einer zur berliCRM-Installation passenden Version.
- Ein gültiges HTTPS-Zertifikat. Word lädt Add-in-Inhalte aus Sicherheitsgründen
  über HTTPS.
- Eine URL, unter der das Add-in dauerhaft erreichbar ist. Add-in und berliCRM
  müssen denselben Ursprung (Protokoll, Hostname und Port) verwenden. Beispiel:
  `https://crm.example.org/berliCRM/` und
  `https://crm.example.org/berliCRM/wordplugin-officejs/`.
- Der verwendete CRM-Benutzer benötigt Zugriff auf die berliCRM-REST-Webservices
  sowie Lesezugriff auf die gewünschten Module, Listenansichten und Datensätze.

### Schritt 1: Add-in-Dateien bereitstellen

1. Sichern Sie eine eventuell bereits vorhandene Installation des Ordners
   `wordplugin-officejs`.
2. Kopieren Sie den gesamten Ordner `wordplugin-officejs` aus diesem Paket in
   einen öffentlich erreichbaren Pfad innerhalb derselben berliCRM-Webadresse,
   zum Beispiel:

   ```text
   <CRM-Webroot>/wordplugin-officejs/
   ```

   Daraus muss beispielsweise diese URL erreichbar sein:
   `https://crm.example.org/berliCRM/wordplugin-officejs/taskpane.html`.
3. Öffnen Sie `wordplugin-officejs/manifest.xml` und ersetzen Sie die
   Beispiel-/bisherigen Serveradressen durch die tatsächliche Installation:
   - `SourceLocation` muss auf `taskpane.html` zeigen.
   - `IconUrl` und `HighResolutionIconUrl` müssen auf die beiden Icon-Dateien
     unter `assets/` zeigen.
   - `AppDomain` muss den Host des berliCRM-Servers enthalten. Dort wird nur der
     Ursprung eingetragen, kein Unterverzeichnis.
4. Rufen Sie `taskpane.html` und beide Icon-URLs über HTTPS im Browser auf.
   Prüfen Sie außerdem, dass berliCRM unter derselben Domain erreichbar ist.

Beispiel für eine Installation unter
`https://crm.example.org/berliCRM/`:

```text
SourceLocation:
https://crm.example.org/berliCRM/wordplugin-officejs/taskpane.html

AppDomain:
https://crm.example.org
```

Wird berliCRM unter einer abweichenden URL oder einem anderen Port betrieben,
muss der Ursprung entsprechend angepasst werden. Add-in und CRM dürfen nicht
auf unterschiedliche Hosts verteilt werden, sofern die CRM-Installation
Cross-Origin-Anfragen nicht ausdrücklich und sicher konfiguriert.

### Schritt 2: REST-Operationen auf dem Server installieren

1. Kopieren Sie
   `include/Webservices/Custom/getWordMailMergeData.php` und
   `include/Webservices/Custom/saveWordMailMergeDocument.php` aus diesem
   Paket in genau diese Pfade relativ zum berliCRM-Webroot:

   ```text
   include/Webservices/Custom/getWordMailMergeData.php
   include/Webservices/Custom/saveWordMailMergeDocument.php
   ```

   Der erste Handler liest die für den Benutzer sichtbaren Listenansichten,
   Datensätze und ausgewählten Feldwerte. Der zweite legt individuelle
   Serienbrief-Dokumente im CRM ab und verknüpft sie mit den jeweiligen
   Datensätzen.
2. Wechseln Sie auf dem Server in das berliCRM-Webroot und führen Sie dort das
   Registrierungsskript einmal mit PHP-CLI aus:

   ```sh
   cd /pfad/zum/berliCRM
   php wordplugin-officejs/build/registerWordMailMergeOperation.php
   ```

   Unter Windows kann das beispielsweise so aussehen:

   ```powershell
   Set-Location C:\Pfad\zum\berliCRM
   php .\wordplugin-officejs\build\registerWordMailMergeOperation.php
   ```

   Das Skript muss aus dem berliCRM-Webroot gestartet werden, damit es
   `config.inc.php` und die Datenbankverbindung findet. Bei Erfolg erscheint:
   `Word-Seriendruck-Webservices wurden registriert.`

Das Registrierungsskript ist wiederholbar: Es aktualisiert die Operationen
`getWordMailMergeData` und `saveWordMailMergeDocument` und schreibt deren
Parameter neu. Führen Sie es erneut aus, wenn sich ein Handler oder registrierte
Parameter ändern. Für reine HTML-, CSS- oder JavaScript-Änderungen am Add-in ist
keine erneute Registrierung erforderlich.

### Schritt 3: Installation prüfen

- `taskpane.html` und die Icons sind über die im Manifest eingetragenen
  HTTPS-Adressen erreichbar.
- Die REST-Operation wurde erfolgreich registriert.
- Ein CRM-Benutzer kann sich mit Benutzername und API-Schlüssel anmelden.
- Der Benutzer kann in berliCRM die vorgesehenen Module und gespeicherten
  Listenansichten sehen.

Das Add-in zeigt nur Module und Ansichten an, auf die der angemeldete
CRM-Benutzer Zugriff hat. Die Seriendruck-Datensatzquelle unterstützt
Kontakte, Firmen, Leads und Tickets. Benutzerfelder können als Felder in eine
Vorlage eingefügt werden, Benutzer sind aber keine Datensatzquelle für den
Seriendruck.

### Updates und Cache

- Bei Änderungen an JavaScript, CSS oder HTML kopieren Sie die geänderten
  Add-in-Dateien auf den Server. Eine erneute Registrierung ist dafür nicht
  nötig.
- Wenn sich eine lokale JavaScript-Datei ändert, muss deren Cache-Buster in
  `taskpane.html` angepasst werden, damit Word/Browser nicht eine alte Version
  weiterverwendet.
- Bei Änderungen an der Manifest-URL, den Icons oder den Manifest-Metadaten
  müssen Sie das aktualisierte Manifest erneut in Word bzw. im zentralen
  Verwaltungsportal bereitstellen.
- Bei Änderungen am PHP-Handler kopieren Sie die aktualisierte Datei auf den
  CRM-Server. Eine erneute Registrierung ist nur nötig, wenn sich auch die
  Operation oder ihre Parameter geändert haben.

## 2. Add-in in Word installieren

Es gibt zwei Bereitstellungswege. Verwenden Sie für einen Benutzer oder einen
kleinen Test die lokale Installation ohne Exchange. Für eine Organisation mit
zentraler Microsoft-365-/Exchange-Verwaltung empfiehlt sich die zentrale
Bereitstellung.

### A. Einzeltests ohne Exchange

Für Word im Web kann das Manifest zum Testen direkt hochgeladen werden. Der
genaue Wortlaut der Menüs hängt von Word-Version und Organisationsrichtlinien ab.

1. Stellen Sie sicher, dass die Serverinstallation aus Abschnitt 1 abgeschlossen
   ist und `manifest.xml` die richtigen URLs enthält.
2. Öffnen Sie Word im Web und ein Dokument.
3. Wählen Sie **Einfügen > Add-Ins** bzw. **Add-Ins abrufen**.
4. Öffnen Sie **Meine Add-Ins**.
5. Wählen Sie **Eigenes Add-In hochladen** (englische Oberfläche:
   **Upload My Add-in**).
6. Wählen Sie die bereitgestellte Datei `manifest.xml` aus und bestätigen Sie
   den Upload.
7. Öffnen Sie das Add-in über **Einfügen > Meine Add-Ins**. Es erscheint als
   **berliCRM Word Plugin**.

Word unter Windows kann für Tests einen freigegebenen Netzwerkordner als
Add-in-Katalog verwenden. Microsoft unterstützt diese Variante als Testweg:

1. Legen Sie die `manifest.xml` in einem für die Benutzer lesbaren
   Netzwerkordner ab.
2. Öffnen Sie in Word **Datei > Optionen > Trust Center > Einstellungen für
   das Trust Center > Vertrauenswürdige Add-in-Kataloge**.
3. Fügen Sie den Netzwerkpfad als Katalog hinzu und aktivieren Sie die Anzeige
   im Menü, falls diese Option angeboten wird.
4. Starten Sie Word neu.
5. Öffnen Sie **Einfügen > Meine Add-Ins > Freigegebener Ordner** und fügen Sie
   das berliCRM-Add-in hinzu.

Wenn eine aktualisierte Manifestversion nicht erscheint, entfernen Sie den
Add-in-Eintrag in Word, schließen Sie Word vollständig und laden Sie das
aktualisierte Manifest erneut. Änderungen an den ausgelieferten
JavaScript-/HTML-Dateien erfordern dagegen normalerweise kein erneutes
Hochladen des Manifests, solange die Manifest-URLs gleich bleiben.

### B. Mit Exchange/Microsoft 365: zentral bereitstellen

Für Organisationen mit Microsoft 365 ist die zentrale Bereitstellung über das
Microsoft-365-Verwaltungsportal der empfohlene Weg. Der Administrator weist das
Add-in einzelnen Benutzern oder Gruppen zu; diese müssen das Manifest nicht
selbst hochladen.

1. Prüfen Sie, dass die `manifest.xml` auf die produktiven HTTPS-Adressen zeigt
   und die Add-in-Dateien vom Microsoft-365-/Word-Client aus erreichbar sind.
2. Melden Sie sich mit einem Administratorkonto im Microsoft-365-Admin-Center
   an.
3. Öffnen Sie **Einstellungen > Integrierte Apps** (in manchen Portalen unter
   **Apps** oder **Bereitstellung zentraler Apps**).
4. Wählen Sie **Benutzerdefinierte Apps hochladen** bzw. **Upload custom apps**.
5. Wählen Sie **Office-Add-in** und laden Sie `manifest.xml` hoch.
6. Weisen Sie das Add-in den gewünschten Benutzern oder Gruppen zu und
   schließen Sie die Bereitstellung ab.
7. Warten Sie, bis die Verteilung abgeschlossen ist. Benutzer öffnen Word mit
   ihrem zugewiesenen Organisationskonto und starten das Add-in über
   **Einfügen > Meine Add-Ins** bzw. den Bereich für von der Organisation
   bereitgestellte Add-ins.

Die Exchange-Verwaltung für Outlook-Add-ins ersetzt die Bereitstellung dieses
Word-Add-ins nicht. Verwenden Sie hierfür das Microsoft-365-Admin-Center.

Bei zentraler Bereitstellung werden Updates des Manifests durch den
Administrator im Verwaltungsportal ausgerollt. Änderungen an den Dateien am
Server sind verfügbar, sobald die Clients die aktualisierte HTML-/JavaScript-
Version laden. Für eine schnelle Aktualisierung kann Word vollständig beendet
und erneut gestartet werden.

## 3. Benutzeranleitung: Seriendruck Schritt für Schritt

### Vor dem Start

- Sie benötigen ein Word-Dokument, das als Briefvorlage dienen soll.
- In berliCRM muss eine gespeicherte Listenansicht mit den gewünschten
  Empfängern vorhanden sein.
- Melden Sie sich mit einem berliCRM-Benutzer an, der die betreffenden
  Datensätze lesen darf.
- Pro Export können höchstens **1.000 Datensätze** verarbeitet werden. Wenn
  eine Ansicht mehr Datensätze enthält, grenzen Sie sie in berliCRM ein.

### Schritt 1: Add-in öffnen und mit berliCRM verbinden

1. Öffnen Sie die Word-Vorlage.
2. Starten Sie **berliCRM Word Plugin** über **Einfügen > Meine Add-Ins**.
3. Geben Sie im Feld **CRM-Adresse** die Basisadresse Ihrer berliCRM-Instanz
   ein, zum Beispiel `https://crm.example.org/berliCRM/`. Geben Sie nicht die
   URL des Unterordners `wordplugin-officejs` ein.
4. Geben Sie Ihren berliCRM-Benutzernamen und den zugehörigen API-Schlüssel
   ein. Der API-Schlüssel ist der Webservice-Schlüssel Ihres CRM-Benutzers,
   nicht das normale CRM-Passwort.
5. Klicken Sie auf **Verbinden**.

Nach erfolgreicher Anmeldung zeigt das Add-in die verfügbaren Module und
Seriendruckfelder. Zugangsschlüssel und Sitzung werden vom Add-in nicht
dauerhaft gespeichert. Mit **Trennen** wird die CRM-Sitzung beendet.

### Schritt 2: Seriendruckfelder in die Vorlage einfügen

1. Wählen Sie im Feld **Modul** das passende CRM-Modul aus, zum Beispiel
   Kontakte, Firmen, Leads oder Tickets.
2. Wählen Sie im Feld **Felder** die benötigten Einträge aus. Mit **Strg**
   (unter macOS **Cmd**) können Sie mehrere einzelne Felder auswählen.
   Die Feldliste ist alphabetisch nach Bezeichnung sortiert.
3. Setzen Sie in Word den Textcursor an die Stelle, an der der Wert erscheinen
   soll. Wenn das Feld einen vorhandenen Text ersetzen soll, markieren Sie
   diesen Text zuerst.
4. Klicken Sie im Add-in auf **In Word einfügen**.
5. Wiederholen Sie die Schritte für alle benötigten Felder und positionieren
   Sie diese an den passenden Stellen im Brief.

Fügen Sie Felder über die Schaltfläche ein; tippen Sie die sichtbaren
Feldcodes nicht von Hand. Das Add-in setzt echte Word-Seriendruckfelder ein.
Die beim Erstellen des Dokuments ausgewählten Felder müssen zu den Feldern in
der Vorlage passen.

### Schritt 3: Listenansicht und Empfänger auswählen

1. Wählen Sie unter **Gespeicherte Listenansicht** die gewünschte berliCRM-
   Ansicht aus. Es werden nur Ansichten angezeigt, die Ihr CRM-Benutzer sehen
   darf.
2. Wählen Sie in der Datensatzliste einzelne Empfänger aus. Mit **Strg**
   (unter macOS **Cmd**) wählen Sie mehrere einzelne Einträge; mit **Umschalt**
   wählen Sie je nach Word-/Betriebssystemverhalten einen zusammenhängenden
   Bereich.
3. Verwenden Sie **Zurück** und **Weiter**, um weitere Seiten der Ansicht
   anzuzeigen. Bereits ausgewählte Datensätze bleiben in der Auswahl erhalten.
4. Alternativ wählen Sie **Alle Datensätze auswählen**, um alle Datensätze der
   Listenansicht auszuwählen. Diese Schaltfläche ist nur verfügbar, wenn die
   Gesamtzahl höchstens 1.000 beträgt.
5. Mit **Auswahl aufheben** entfernen Sie die komplette bisherige Auswahl.

Die Vorschau zeigt Werte der ausgewählten Felder und nicht die internen
berliCRM-Datensatz-IDs. Referenz- und Besitzerfelder werden als Name des
verknüpften CRM-Datensatzes ausgegeben, nicht als interne Referenz-ID. Ein
leeres Feld in der Vorschau bedeutet nicht
zwangsläufig, dass der Datensatz fehlt; der betreffende CRM-Feldwert kann leer
sein.

### Schritt 4: Ausgabeart wählen und Dokumente erstellen

Wählen Sie unter **Ausgabe** eine der beiden Varianten. Unabhängig davon können
Sie zusätzlich **Individuelle Briefe zusätzlich als Dokument beim jeweiligen
CRM-Datensatz speichern** aktivieren. Dann erstellt das Add-in für jeden
Empfänger ein eigenes Word-kompatibles Dokument im CRM und verknüpft es mit
dem Datensatz. Dies funktioniert sowohl mit einem gemeinsamen Download als
auch mit einer ZIP-Datei.

Der Dokumenttitel setzt sich aus `Serienbrief`, dem Erstellungsdatum und den
Werten der ausgewählten Felder dieses Datensatzes zusammen, zum Beispiel
`Serienbrief 29.10.2026: Hold, Trevor` (Nachname, Vorname). berliCRM begrenzt
den Dokumenttitel auf 50 Zeichen; bei langen Feldwerten wird der Titel daher am
Ende gekürzt.

Für die Ablage benötigt der Benutzer zusätzlich zum Lesezugriff die
Berechtigung, CRM-Dokumente anzulegen. Einzelne gespeicherte Dateien dürfen
maximal 5 MB groß sein. berliCRM legt Dateien in zeitbasierten Unterordnern
unter `storage/` ab; prüfen Sie deshalb auch die Unterordner nach Jahr, Monat
und Woche statt nur den Stammordner.

Wählen Sie danach die gewünschte Download-Variante:

#### Ein gemeinsamer Serienbrief

- Wählen Sie **Ein gemeinsamer Serienbrief (alle Datensätze in einem
  Dokument)**.
- Klicken Sie auf **Seriendruck-Dokumente erstellen**.
- Das Add-in erstellt eine `.doc`-Datei mit allen ausgewählten Empfängern.
  Jeder Datensatz beginnt auf einer neuen Seite.
- Klicken Sie nach der Erstellung auf den Download-Link
  **Gemeinsamen Serienbrief herunterladen**.

Diese Variante eignet sich, wenn alle Briefe gemeinsam in einem Dokument
geprüft, gedruckt oder weiterverarbeitet werden sollen.

#### Einzelne Briefe als ZIP-Datei

- Wählen Sie **Einzelne Briefe als ZIP-Datei**.
- Klicken Sie auf **Seriendruck-Dokumente erstellen**.
- Das Add-in erstellt für jeden ausgewählten Datensatz ein eigenes
  Word-kompatibles `.doc`-Dokument und bündelt die Dateien in einer ZIP-Datei.
- Klicken Sie auf **ZIP-Datei mit den Einzelbriefen herunterladen** und
  entpacken Sie das Archiv, um die Briefe einzeln zu öffnen.

Diese Variante eignet sich, wenn jeder Brief separat gespeichert oder verteilt
werden soll. Die Dateinamen werden aus dem Datensatznamen gebildet; doppelte
Namen erhalten eine laufende Nummer.

Die erzeugten Dokumente verwenden den HTML-Inhalt des geöffneten Word-
Dokuments als Vorlage. Prüfen Sie das Ergebnis vor dem Versand oder Druck,
insbesondere Seitenlayout, Kopf-/Fußzeilen, Bilder und Formatierungen.

### Verfügbare Alternativen und Grenzen

- **Empfänger:** einzelne Datensätze manuell auswählen oder alle Datensätze der
  aktuell gewählten Listenansicht auswählen.
- **Ausgabe:** ein gemeinsames Dokument mit einem Empfänger pro Seite oder
  einzelne Dateien in einem ZIP-Archiv.
- **Datenquelle:** gespeicherte Listenansichten in Kontakte, Firmen, Leads und
  Tickets. Benutzer können als Feldmodul für Mergefelder verwendet werden,
  aber nicht als Empfängerquelle.
- **Umfang:** maximal 1.000 Empfänger pro Export; die Daten werden intern in
  kleineren Seiten und Abrufen verarbeitet.
- **Berechtigungen:** Modul-, Listenansichts- und Datensatzrechte des
  angemeldeten berliCRM-Benutzers gelten weiterhin.

## Fehlerbehebung

### Anmeldung schlägt fehl

- Prüfen Sie die CRM-Basisadresse (ohne `/wordplugin-officejs/`).
- Prüfen Sie Benutzername und API-Schlüssel. Der API-Schlüssel ist nicht das
  normale CRM-Passwort.
- Prüfen Sie, ob der CRM-Benutzer den berliCRM-Webservice verwenden darf und ob
  die CRM-Adresse über HTTPS erreichbar ist.

### Listenansichten oder Datensätze fehlen

- Prüfen Sie in berliCRM, ob die Ansicht für den angemeldeten Benutzer sichtbar
  ist und Datensätze enthält.
- Prüfen Sie, ob der Benutzer Leserechte für Modul und Datensätze hat.
- Wählen Sie zunächst mindestens ein Feld aus, wenn die Vorschau oder der
  Seriendruck die Feldwerte benötigt.

### HTTP 500 oder ungültige Antwort beim Laden von Datensätzen

- Prüfen Sie, ob die aktuelle Datei
  `include/Webservices/Custom/getWordMailMergeData.php` auf dem CRM-Server liegt.
- Prüfen Sie, ob das Registrierungsskript erfolgreich ausgeführt wurde. Wenn
  die REST-Parameter geändert wurden, führen Sie das Skript erneut aus.
- Prüfen Sie das PHP-/Webserver-Fehlerprotokoll unmittelbar nach dem Fehler.
  Zugangsschlüssel, Sitzungstoken und personenbezogene Daten sollten vor dem
  Weitergeben von Protokollauszügen entfernt werden.

### Individuelle Briefe werden nicht im CRM gespeichert

- Prüfen Sie, ob `include/Webservices/Custom/saveWordMailMergeDocument.php`
  auf dem CRM-Server liegt und das Registrierungsskript nach dem Kopieren
  erfolgreich ausgeführt wurde.
- Für Dateien und die Verknüpfung zum Datensatz muss der CRM-Server die
  Standard-Speicherfunktion für Dokumentanhänge und die Funktion
  `vtws_update_document_relations` bereitstellen.
- Der Benutzer benötigt das Recht, Dokumente anzulegen, und Zugriff auf den
  zugehörigen Datensatz.
- Eine einzelne Dokumentdatei darf höchstens 5 MB groß sein. Einzelne
  Speicherfehler erscheinen nach dem Erstellen der Download-Datei im
  Add-in-Status; erfolgreich gespeicherte Briefe bleiben erhalten.
- Prüfen Sie bei weiterhin auftretenden Fehlern das PHP-/Webserver-
  Fehlerprotokoll. Entfernen Sie Zugangsdaten, Sitzungstoken und
  personenbezogene Daten, bevor Sie Protokolle weitergeben.

### Das Add-in zeigt nach einem Update noch alte Inhalte

- Beenden Sie Word vollständig und starten Sie es erneut.
- Prüfen Sie, ob die neue `taskpane.html` und die referenzierten
  JavaScript-Dateien am Server liegen.
- Prüfen Sie, ob der Cache-Buster in `taskpane.html` für die geänderte
  JavaScript-Datei aktualisiert wurde.
- Bei zentraler Bereitstellung kann die Verteilung eines geänderten Manifests
  etwas Zeit benötigen.

## Datenschutz und Sicherheit

Der API-Schlüssel wird nur zur Anmeldung verwendet und nach erfolgreicher
Anmeldung aus dem Eingabefeld entfernt. Das Add-in speichert weder den
API-Schlüssel noch die Sitzung dauerhaft. Der CRM-Server prüft die verfügbaren
Module, Listenansichten und den Zugriff auf angeforderte Datensätze. Teilen Sie
den API-Schlüssel nicht und geben Sie Fehlerprotokolle nur nach Entfernung
vertraulicher Werte weiter.
