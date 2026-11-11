/** Complete Outlook language repair: browser UI, gateway, handlers and installer. */
import fs from 'node:fs/promises';
import path from 'node:path';
import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { zipSync, unzipSync, strToU8 } from '../../wordplugin-officejs/src/vendor/fflate-0.8.3.js';

export const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const browserNames = ['taskpane.html', 'taskpane.js', 'mail-read.js', 'calendar.js', 'commands.html', 'commands.js',
  'contacts.html', 'contacts.js', 'contacts.css', 'contacts-layout.js', 'contact-model.js', 'contact-graph.js',
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

export async function createRepairPackage(target = path.join(root, 'outlook-addin/build/outlook-addin-1.2.0.1-taskpane-contacts.zip')) {
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
  files['UPDATE.txt'] = strToU8(`berliCRM Outlook 1.2.0.1 - Kontakte im vorhandenen Seitenfenster
Build 20261111.11, 11.11.2026

Die zusaetzliche Kontakte-App wurde verworfen. Keine neue App, kein Teams,
kein Teams-SDK, kein NAA und keine neue Entra-Rueckleitungsadresse.
Die Microsoft-Anmelde-Dateien entsprechen wieder dem Git-Ausgangsstand 1.1.0.2.

Dieses vollstaendige Paket im CRM-Hauptverzeichnis einspielen.
Die vorhandene outlook-addin/rest/outlook.config.php beibehalten.
Danach Outlook neu laden, berliCRM bei einer E-Mail oeffnen und
CRM- und Outlook-Kontakte verwalten waehlen.

Neu im vorhandenen Seitenfenster:
- Reiter Outlook-Kontakte und CRM-Kontakte fuer die schmale Ansicht.
- In breiter Ansicht beide Listen nebeneinander.
- Erste Outlook-Kontaktseite und gewaehlten Ordner automatisch lesend laden.
- Auswahl bleibt beim Reiterwechsel erhalten. Uebernahmen nur nach Klick.
- CRM-Suche: Zurueck/Weiter mit 50 Treffern je Seite. Eine neue Suche startet
  auf Seite 1. Beim Blaettern wird die Auswahl der bisherigen Seite verworfen.
- Seitenanzeige mit Gesamtzahl der passenden lesbaren CRM-Kontakte,
  z.B. Seite 12 - Treffer 551-600 von 25616. Zaehlen erfolgt im CRM mit
  identischer Suche und Benutzerberechtigung, ohne alle Kontakte zu laden.
- Konto und Kontaktordner kompakt aufklappbar. Technische Fehler bleiben verdeckt.

Falls die verworfene Kontakte-App bereits installiert wurde: nur diese neue
App entfernen (Admincenter > Einstellungen > Integrierte Apps oder persoenliche
Installation in Outlook/Teams). Das vorhandene Mail-/Termin-Add-in beibehalten.
Die alten contacts-workspace*-Dateien und deren Buendel werden nicht mehr
eingebunden und koennen auf dem Server entfernt werden; siehe CONTACTS.md.

Das native Outlook-Personenmodul wird nicht gesteuert. Die Kontaktverwaltung
findet im bestehenden Seitenfenster statt. Kein neuer Datenbankstand.

Vollstaendiger Ruecksetzstand: outlook-addin-1.1.0.2-before-workspace.zip.
Private Konfiguration unveraendert lassen. Bewusst uebernommene Kontakte werden
nicht geloescht. ROLLBACK.txt im Ruecksetzpaket beschreibt die Rueckkehr.
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
