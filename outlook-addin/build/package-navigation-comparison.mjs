/** Packages an unchanged historical browser UI and the corrected current UI for comparison. */
import fs from 'node:fs/promises';
import path from 'node:path';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { zipSync, unzipSync, strToU8 } from '../../wordplugin-officejs/src/vendor/fflate-0.8.3.js';
import { createRepairPackage } from './package-outlook-repair.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const legacyRoot = process.argv[2];
if (!legacyRoot) throw new Error('Usage: node package-navigation-comparison.mjs PATH_TO_OLD_CHECKOUT');
const legacySha = execFileSync('git', ['-c', 'safe.directory=' + path.resolve(legacyRoot).replaceAll('\\', '/'),
  '-C', legacyRoot, 'rev-parse', 'HEAD'], { encoding: 'utf8' }).trim();
assert.match(legacySha, /^fb98a482/, 'The comparison must use the inspected historical revision');
const browserFiles = ['taskpane.html', 'taskpane.js', 'mail-read.js', 'calendar.js', 'commands.html', 'commands.js',
  'contacts.html', 'contacts.js', 'contacts.css', 'contact-model.js', 'contact-graph.js',
  'contacts-auth.html', 'contacts-auth.css', 'assets/contacts-auth.bundle.js', 'assets/contacts-auth.bundle.js.LEGAL.txt',
  'assets/contacts-sso.bundle.js', 'assets/contacts-sso.bundle.js.LEGAL.txt'];
const digest = bytes => createHash('sha256').update(bytes).digest('hex');

async function pack(sourceRoot, names, filename, instructions, refreshCache = false) {
  const files = Object.create(null);
  for (const name of names) {
    let bytes = new Uint8Array(await fs.readFile(path.join(sourceRoot, 'outlook-addin', name)));
    // Force browsers to fetch the historical assets rather than reuse a newer cached response.
    if (refreshCache && name.endsWith('.html')) {
      const html = new TextDecoder().decode(bytes).replace(/((?:src|href)="[^"?]+\.(?:js|css))\?v=[^"\s]+/g,
        '$1?v=legacy-fb98a482-20261108');
      bytes = strToU8(html);
    }
    files['outlook-addin/' + name] = bytes;
  }
  files['SHA256SUMS.txt'] = strToU8(Object.keys(files).map(name => digest(files[name]) + '  ' + name).join('\n') + '\n');
  files['UPDATE.txt'] = strToU8(instructions);
  const target = path.join(root, 'outlook-addin/build', filename);
  await fs.writeFile(target, zipSync(files, { level: 6 }));
  const restored = unzipSync(new Uint8Array(await fs.readFile(target)));
  assert.deepEqual(Object.keys(restored).sort(), Object.keys(files).sort());
  for (const name of Object.keys(files)) assert.equal(digest(restored[name]), digest(files[name]), name);
  process.stdout.write(filename + ': ' + names.length + ' browser files; ZIP contents and checksums verified.\n');
}

const repair = await createRepairPackage();
await pack(legacyRoot, browserFiles, 'outlook-navigation-baseline-fb98a482.zip',
  `Diagnose: historische Outlook-Oberflaeche 1.0.15.3 aus ${legacySha}

Noch kein bestaetigter Fix fuer den nativen Outlook-Kontaktewechsel.
Die hier enthaltenen Serverdateien vorab sichern, dann im CRM-Webroot entpacken.
Outlook vollstaendig neu laden und dasselbe installierte Add-in verwenden.
Keine Manifest-Neuinstallation, PHP-Aenderung oder Entra-Aenderung erforderlich.
Die Dateien stammen aus dem alten Git-Stand; nur HTML-Cacheparameter sind erneuert.
Die alte Oberflaeche verwendet deutsche Texte.

Test: Eine E-Mail in derselben Ansicht wie frueher oeffnen, berliCRM oeffnen,
anmelden und "CRM- und Outlook-Kontakte verwalten" anklicken.
Beobachten, ob links in Outlook wirklich Personen/Kontakte ausgewaehlt wird,
ob der Aufgabenbereich offen bleibt und was beim Zurueckwechseln passiert.
Das Ergebnis beschreibt das heutige Verhalten dieses historischen Codes.

Anschliessend ${path.basename(repair.target)} vollstaendig einspielen
und die dort beschriebene Webservice-Registrierung ausfuehren.
Das fruehere reine Oberflaechenpaket ist durch diese vollstaendige Reparatur ersetzt.
Keines der Pakete enthaelt private Konfigurationen oder Zugangsdaten.
`, true);

process.stdout.write('Created complete repair: ' + repair.target + '\n');
