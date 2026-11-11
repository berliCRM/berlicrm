/** Complete Outlook language repair: browser UI, gateway, handlers and installer. */
import fs from 'node:fs/promises';
import path from 'node:path';
import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { zipSync, unzipSync, strToU8 } from '../../wordplugin-officejs/src/vendor/fflate-0.8.3.js';

export const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const browserNames = ['taskpane.html', 'taskpane.js', 'mail-read.js', 'calendar.js', 'commands.html', 'commands.js',
  'contacts.html', 'contacts.js', 'contacts.css', 'contact-model.js', 'contact-graph.js',
  'contacts-auth.html', 'contacts-auth.css', 'contacts-auth-entry.js', 'contacts-auth-relay.js', 'contacts-sso-entry.js', 'settings-connection.js',
  'language.js', 'languages/de_de.php', 'languages/en_us.php', 'manifest.xml', 'manifest.template.xml',
  'assets/contacts-auth.bundle.js', 'assets/contacts-auth.bundle.js.LEGAL.txt',
  'assets/contacts-sso.bundle.js', 'assets/contacts-sso.bundle.js.LEGAL.txt',
  'assets/icon-16.png', 'assets/icon-32.png', 'assets/icon-80.png',
  'README.md', 'README.en.md', 'CONTACTS.md', 'CONTACTS.en.md', 'MAIL-ARCHIVE.md', 'MAIL-ARCHIVE.en.md',
  'rest/outlook.php', 'rest/OutlookWebserviceClient.php', 'rest/OutlookContactsConfig.php', 'rest/OutlookAuthRelay.php',
  'update.php', 'build/registerOutlookAddinOperation.php'];

async function tree(relative) {
  const names = [];
  for (const entry of await fs.readdir(path.join(root, relative), { withFileTypes: true })) {
    const name = relative + '/' + entry.name;
    if (entry.isDirectory()) names.push(...await tree(name));
    else names.push(name);
  }
  return names;
}

export async function createRepairPackage(target = path.join(root, 'outlook-addin/build/outlook-addin-1.1.0.2-settings-fix2.zip')) {
  const handlers = (await fs.readdir(path.join(root, 'include/Webservices/Custom')))
    .filter(name => /^Outlook.*\.php$/.test(name)).map(name => 'include/Webservices/Custom/' + name);
  const names = [...browserNames.map(name => 'outlook-addin/' + name), ...handlers,
    'include/utils/OfficeAddinI18n.php',
    ...await tree('modules/Settings/OutlookAddin'), ...await tree('layouts/vlayout/modules/Settings/OutlookAddin'),
    'languages/de_de/Settings/OutlookAddin.php', 'languages/en_us/Settings/OutlookAddin.php'].sort();
  assert.equal(new Set(names).size, names.length);
  assert.ok(names.every(name => !/outlook\.config\.php$|manifest\.generated\.xml$|node_modules|\.zip$/i.test(name)));
  const files = Object.create(null);
  const digest = bytes => createHash('sha256').update(bytes).digest('hex');
  for (const name of names) files[name] = new Uint8Array(await fs.readFile(path.join(root, name)));
  files['SHA256SUMS.txt'] = strToU8(names.map(name => digest(files[name]) + '  ' + name).join('\n') + '\n');
  files['UPDATE.txt'] = strToU8(`berliCRM Outlook Add-in 1.1.0.2, Settings-Fix 2, Cache 20261111.7, 11.11.2026

Die Kanal-Kennung bleibt jetzt auch erhalten, wenn MSAL auf die urspruengliche
Startadresse mit start=1 zurueckkehrt. Der vorherige Test bildete nur die reine
Callback-Adresse ab und hatte diesen Fall uebersehen. Regressionstest und
Firefox-Test decken nun den Ruecksprung zur Startadresse nach COOP-Trennung ab.
Pruefung abbrechen hat einen festen Abstand zum vorherigen Button.

NEU: Microsoft-Verbindung pruefen in den CRM-Einstellungen repariert.
Die Add-in-Version bleibt ausdruecklich 1.1.0.2. Das Manifest bleibt gleich.
Der Einstellungs-Test erhaelt die Antwort aus dem Microsoft-Anmeldefenster
ueber einen pro Versuch zufaellig erzeugten Browserkanal derselben CRM-Origin.
Der Empfang wird bestaetigt, bevor das Fenster schliesst. Getrenntes window.opener
und ein durch COOP irrefuehrendes popup.closed loesen keinen falschen Abbruch aus.
Es wird weiterhin genau ein lesender Graph-Aufruf ausgefuehrt; keine Kontakte
werden angelegt oder geaendert. Tokens werden dafuer nicht im CRM gespeichert.
Die Meldungen nennen die CRM-Einstellungen statt Word oder die Kontakteansicht.

Vollstaendig einspielen, insbesondere auch layouts/ und languages/ sowie die
aktualisierten Anmeldedateien/Bundles. Danach die CRM-Einstellungsseite mit
Strg+F5 neu laden und Microsoft-Verbindung pruefen erneut starten.
Lokaler Firefox-Test mit echter COOP-Trennung und simulierten Microsoft- und
Graph-Antworten bestanden. Der echte Microsoft-Login bleibt im Zielsystem zu pruefen.

Historie der Version 1.1.0.2:

NEU IN 1.1.0.2: Verstaendliche Hinweise bei abgelaufener Anmeldung.
In der Kontakteansicht wird eine abgelaufene Microsoft-Verbindung anhand der
Token-Gueltigkeit auch ohne weiteren Zugriff erkannt. Die Pruefung laeuft
ebenfalls bei Rueckkehr zur Ansicht und vor Kontaktaktionen. Die CRM-Anmeldung
bleibt bei einem Microsoft-Fehler erhalten. Mit Microsoft verbinden erneuert
die Verbindung; technische Fehlertexte bleiben eingeklappt.

Bei abgelaufener CRM-Sitzung erscheint ein sichtbarer Hinweis oberhalb des
CRM-Anmeldeformulars. Dort Benutzername und Access Key erneut eingeben.
Nach erfolgreicher Anmeldung verschwindet der jeweilige Hinweis. Es werden
keine Kontakte automatisch erneut uebertragen und keine Sitzungen kuenstlich
durch Hintergrund-Anfragen verlaengert.

Versionsanzeige und Manifestvorlage sind auf 1.1.0.2 erhoeht. Die Add-in-ID bleibt
gleich. Fuer die von Outlook verwaltete Versionsangabe das installationsspezifische
Manifest im CRM unter Einstellungen > Integration > Outlook Add-in neu erzeugen
und in der bestehenden Outlook-Bereitstellung aktualisieren. Die Beispieladressen
im mitgelieferten manifest.xml NICHT direkt installieren.

NEU IN 20261111.4: Technische Fehlerdetails sind standardmaessig eingeklappt.
Die vollstaendige Meldung wird erst nach Klick auf Technische Details sichtbar.
Ein neuer Fehler klappt den Bereich wieder zu. Die allgemeine Fehlermeldung
bleibt sichtbar. Der funktionierende Microsoft-Login in Firefox und Edge sowie
das Oeffnen/Anheften des Seitenfensters werden nicht geaendert.
Die aktuelle Buildkennung muss 20261111.4 lauten.

Historie 20261111.3:

NEU IN 20261111.3: Fenster-Vorbereitung fuer Firefox und verzogerter Office-Start.
Im angemeldeten E-Mail-Seitenfenster auf CRM- und Outlook-Kontakte verwalten
klicken. Falls keine gueltige Microsoft-Verbindung im Tab vorliegt, wird das
Anmeldefenster innerhalb dieses echten Klicks vorbereitet und nach dem Wechsel
von der Kontakteansicht uebernommen. Fuer diesen Ablauf ist keine Popup-Ausnahme
erforderlich. Bei erfolgreichem stillen Login wird ein vorbereitetes Fenster
wieder geschlossen. Der Outlook-Plattformhinweis wird beim Wechsel mitgegeben.

Scheitert prompt=none auch im Fenster, folgt einmalig eine normale Microsoft-
Anmeldung im selben Fenster ohne erzwungene Kontoauswahl. Microsoft kann dabei
eine bestehende Sitzung verwenden oder bei Bedarf Eingabe/Zustimmung verlangen.
Bei einem weiteren Fehler endet der Versuch. Kein zusaetzliches Kontaktefenster,
keine Browser-Erweiterung, keine NAA-/Entra-/Manifest-Aenderung.
Die neue Buildkennung muss 20261111.3 lauten.

Lokal mit Firefox 159 und aktivierter Popup-Sperre geprueft: echter Kontakte-
Klick, Seitenwechsel in einem fremden Frame, verzogerter Office-Start und
verschluesselte Rueckgabe. Dieser Browsertest verwendet simulierte CRM- und
Microsoft-Antworten; der reale Microsoft-Login bleibt abschliessend zu pruefen.
Das bereits funktionierende automatische Oeffnen/Anheften bleibt unveraendert.

Historie 20261111.2:

NEU IN 20261111.2: Ausweichweg fuer login_required / AADSTS50058 ohne NAA.
Wenn der stille Iframe-Versuch keine Microsoft-Sitzung findet, versucht Outlook
im Browser einmalig die bestehende Anmeldung in einem Microsoft-Anmeldefenster
mit prompt=none zu verwenden. Die Sitzungscookies koennen dort verfuegbar sein,
obwohl der Browser sie im eingebetteten Iframe nicht sendet.
Automatische Popups muessen fuer die beim Blockieren vom Browser angezeigte
Seite (Outlook beziehungsweise CRM) erlaubt sein. Andernfalls
bleibt der Klick auf Mit Microsoft verbinden notwendig. Es gibt keine Endlosschleife.
Auch eine abgelaufene Microsoft-Sitzung oder fehlende Zustimmung kann weiter
eine manuelle Anmeldung verlangen. Der echte Outlook-Test steht noch aus.

Mit Microsoft verbinden erzwingt keine Kontoauswahl mehr, sondern kann eine
bestehende Sitzung verwenden. Verbindung trennen bietet die Kontoauswahl an.
Die verschluesselte Rueckgabe aus dem Anmeldefenster und der Sitzungscache aus .7
bleiben bestehen. NAA wird nicht verwendet. Keine neuen Entra-Eintraege, keine
neue Webservice-Registrierung bei vorhandener .3-Installation, kein neues Manifest.
Die angezeigte Buildkennung muss 20261111.2 lauten.

Historie 20261111.1:

NEU AM 11.11.: Sichtbare Diagnose bei gescheiterter automatischer Erstanmeldung.
Die konkrete Fehlermeldung steht direkt unter dem Verbindungsstatus, ohne
aufzuklappenden Bereich. Microsoft-Fehlercode und Untercode werden ebenfalls
angezeigt, wenn vorhanden. Unten in der Kontakteansicht steht Build 20261111.1.
Der Kontakte-Link und die Skriptadressen tragen eine neue Cache-Version.
Die automatische Erstanmeldung war mit diesem Diagnoseupdate NICHT als
behoben bestaetigt. Bitte den sichtbaren Fehler vor der manuellen Verbindung
notieren. Erfolgreiche manuelle Anmeldung und Wiederverwendung im selben
Browsertab bleiben erhalten. NAA wird nicht verwendet; keine Entra-Aenderungen.

NEU IN .7: Wiederverwendung der Microsoft-Verbindung ohne NAA.
KEINE zusaetzlichen Entra-Eintraege erforderlich. Die funktionierende manuelle
Anmeldung bleibt erhalten. Nach erfolgreicher Anmeldung wird der noch gueltige
Graph-Zugriffstoken im sessionStorage desselben Outlook-Browsertabs gehalten.
Wird die Kontakteansicht geschlossen und erneut geoeffnet, wird er erst nach
Pruefung derselben CRM-Sitzung und Entra-Konfiguration wiederverwendet.
Tokens werden NICHT dauerhaft in localStorage oder im CRM gespeichert.
Abgelaufene und von Graph zurueckgewiesene Tokens werden verworfen. Verbindung
trennen und CRM-Abmeldung loeschen den Sitzungscache ebenfalls.

Ohne gueltigen Sitzungstoken startet der bisherige stille MSAL-Iframe-Versuch,
jetzt ohne auf die Office-Initialisierung zu warten. Der zuletzt verbundene
Microsoft-Benutzername wird als unverbindlicher, nicht geheimer Kontohinweis
behalten und gegenueber einer eventuell abweichenden Outlook-Postfachadresse
bevorzugt. Nach Tokenablauf koennen blockierte Drittanbieter-Cookies oder eine
erforderliche Microsoft-Anmeldung/Zustimmung weiterhin manuelles Verbinden
noetig machen. Die konkrete Fehlermeldung steht unter Technische Details.
Es gibt keine automatische Token-Erneuerung im Hintergrund.

NEU IN .5: Die Rueckgabe der Microsoft-Anmeldung in Outlook im Browser ist nicht
mehr von window.opener abhaengig. Browser koennen diese Verbindung bei der
Microsoft-Weiterleitung trennen (COOP). Das Popup verschluesselt seine Antwort
mit AES-GCM und hinterlegt nur den verschluesselten Inhalt fuer maximal fuenf
Minuten zur Abholung. Der Schluessel bleibt in den beteiligten Browserfenstern.
Die einmalige Schreibberechtigung wird nach der ersten Antwort ungueltig.
Nur die initiierende, erneut gepruefte CRM-Sitzung kann die Antwort abholen.
PHP muss in seinem temporaeren Verzeichnis einen privaten Unterordner anlegen
koennen. Die Datenablage erfolgt ausserhalb des CRM-Webverzeichnisses.
Alternativ kann die Serverkonfiguration contacts_auth_relay_directory auf ein
privates, beschreibbares Verzeichnis ausserhalb des Webroots setzen.

Auch beim Update von .4 unbedingt outlook-addin/rest/ und die Sprachdateien
gemeinsam mit den Browserdateien austauschen. Keine neue Datenbanktabelle,
CRM-Registrierung oder Browser-Erweiterung wird benoetigt, wenn die Sprachabfrage
aus .3 bereits registriert ist. Entra-Konfiguration und Manifest bleiben gleich.

NEU IN .4: Microsoft-Anmeldung in Outlook im Browser verwendet den vorhandenen
Popup-Kanal. Dieser startet direkt beim Klick auf "Mit Microsoft verbinden"
und wartet nicht mehr auf den haengenden Office-Dialog-Handshake.
Es ist nur das Microsoft-Anmeldefenster; die Kontakteverwaltung bleibt im Add-in.
Die Rueckmeldung wird nur vom geoeffneten Fenster und der exakten CRM-Origin
angenommen. Klassisches Outlook verwendet weiter seinen Office-Dialog.
Die MSAL-5-Option iframeBridgeTimeout ersetzt die veraltete iframeHashTimeout.

Wenn das vollstaendige .3-Paket samt Webservice-Registrierung bereits installiert
ist, genuegt fuer .4 der Dateiaustausch und ein vollstaendiges Neuladen von Outlook.
Die CRM-Registrierung muss dann nicht wiederholt werden. Das Manifest bleibt.
Ein eventuell blockiertes Microsoft-Anmeldefenster im Browser erlauben.
Wenn die automatische Anmeldung nicht gelingt, "Mit Microsoft verbinden" anklicken.

Dieses Paket ersetzt die bisherigen reinen Oberflaechenpakete.
Es behebt den Versionskonflikt "Unbekannte Aktion: getUserPreferences".

1. Die im Paket enthaltenen Serverdateien zuerst sichern.
2. Im CRM-Hauptverzeichnis entpacken, sodass outlook-addin/, include/,
   modules/, layouts/ und languages/ an den richtigen Stellen aktualisiert werden.
   Die private outlook-addin/rest/outlook.config.php bleibt bestehen und ist NICHT enthalten.
3. Auf dem CRM-Server im CRM-Hauptverzeichnis ausfuehren:

   php outlook-addin/build/registerOutlookAddinOperation.php

   Alternativ im CRM: Einstellungen > Integration > Outlook Add-in >
   Webservices einrichten / reparieren. Dieser Schritt registriert office_addin_user_preferences.
   Ohne diese Registrierung ist die Reparatur nicht vollstaendig.
4. Alle Outlook-Tabs schliessen, Outlook neu oeffnen und berliCRM erneut testen.
   Einmal manuell mit Microsoft verbinden, falls der stille Versuch scheitert.
   Danach zu Personen und zurueck wechseln, berliCRM/Kontakteansicht erneut
   oeffnen: Im selben Tab soll die noch gueltige Verbindung wieder bereitstehen.
   Dies aendert nicht die Sichtbarkeit des Add-in-Panels im nativen Personen-Modul.

Fuer die Oberflaechenaktualisierung kann das bestehende Outlook-Manifest bleiben.
Fuer die neue von Outlook verwaltete Versionsangabe das CRM-Manifest wie oben
beschrieben neu erzeugen und die bestehende Bereitstellung aktualisieren.
Die mitgelieferten manifest.xml/.template.xml enthalten Beispieladressen und
werden fuer diese Reparatur NICHT direkt in Outlook installiert.

Eine vorhandene CRM-Sitzung wird in der Kontakteansicht geprueft. Waehrenddessen
bleibt das Loginformular verborgen. Bei Verbindungs-/Versionsfehlern bleibt die
Sitzung erhalten und "Erneut versuchen" wiederholt die Pruefung.
Nur bei einer tatsaechlich abgelaufenen CRM-Sitzung ist eine Neuanmeldung erforderlich.
Die gespeicherte CRM-Sprache wird als Hinweis genutzt; die authentifizierte
Sprachabfrage bleibt massgeblich. Die Outlook-Ereignisse starten sofort.

Die verworfene Zusatzfenster-Ergaenzung ist nicht enthalten. Eine vorhandene
contacts-window.js wird nicht mehr eingebunden und kann liegen bleiben.
Keine Browser-Erweiterung und kein neues Kontaktfenster. Dieses Update
erfordert keine zusaetzliche Entra-Redirect-URI.
Die Korrektur umgeht die bestaetigte Zeitueberschreitung bei der Verbindung zum
Outlook-Anmeldefenster in Outlook im Browser. Der reale Microsoft-Login muss
abschliessend mit dem Benutzerkonto geprueft werden. Silent SSO kann weiterhin
an Browser-Cookie-Einstellungen oder erforderlicher Zustimmung scheitern.
Das Offenbleiben des nativen Outlook-Aufgabenbereichs beim Wechsel zu Personen
ist damit NICHT als behoben bestaetigt.
`);
  await fs.writeFile(target, zipSync(files, { level: 6 }));
  const restored = unzipSync(new Uint8Array(await fs.readFile(target)));
  assert.deepEqual(Object.keys(restored).sort(), Object.keys(files).sort());
  for (const name of Object.keys(files)) assert.equal(digest(restored[name]), digest(files[name]), name);
  return { target, files: names.length };
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const result = await createRepairPackage();
  process.stdout.write('Created ' + result.target + ': ' + result.files + ' deployment files; ZIP read-back and checksums verified.\n');
}
