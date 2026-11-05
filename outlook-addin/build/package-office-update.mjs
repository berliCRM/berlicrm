/** Creates a deployment archive from versioned Office add-in files only. */
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
import { zipSync, unzipSync, strToU8 } from '../../wordplugin-officejs/src/vendor/fflate-0.8.3.js';
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const git = execFileSync('git', ['-c', 'safe.directory=' + root.replaceAll('\\', '/'), 'ls-files', '-z'], { cwd: root, encoding: 'utf8' });
const names = git.split('\0').filter(Boolean).filter(name => {
  const scoped = /^(?:wordplugin-officejs\/|outlook-addin\/|modules\/Settings\/(?:WordAddin|OutlookAddin)\/|layouts\/vlayout\/modules\/Settings\/(?:WordAddin|OutlookAddin)\/|languages\/(?:de_de|en_us)\/Settings\/(?:WordAddin|OutlookAddin)\.php$|include\/utils\/OfficeAddinI18n\.php$|include\/Webservices\/Custom\/(?:Outlook.*|WordMailMergeDocx|getWordMailMergeData|saveWordMailMergeDocument)\.php$)/.test(name);
  return scoped && !/node_modules\/|\/diagnostics\/|\/outlook\.config\.php$|\/manifest\.generated\.xml$|\/workingmanifest\.txt$|\.zip$/i.test(name)
    && (!name.endsWith('.xml') || /\/(?:manifest|manifest\.template)\.xml$/.test(name));
}).sort();
for (const name of ['include/utils/OfficeAddinI18n.php', 'wordplugin-officejs/languages/de_de.php', 'outlook-addin/languages/en_us.php']) {
  assert.ok(names.includes(name), 'Required file must be added to Git before packaging: ' + name);
}
const digest = bytes => createHash('sha256').update(bytes).digest('hex');
const files = Object.create(null);
for (const name of names) files[name] = new Uint8Array(await fs.readFile(path.join(root, name)));
files['SHA256SUMS.txt'] = strToU8(names.map(name => digest(files[name]) + '  ' + name).join('\n') + '\n');
files['UPDATE.txt'] = strToU8(`berliCRM Office add-ins: Word 1.4.0.0 / Outlook 1.1.0.0

DE: Serverdateien und eigene Manifeste zuerst sichern. Im CRM-Webroot entpacken.
Private outlook-addin/rest/outlook.config.php beibehalten; sie ist nicht enthalten.
Sprachdateien: PHP-Arrays in beiden Add-in-Verzeichnissen unter languages/.
Gemeinsame Hilfsdatei: include/utils/OfficeAddinI18n.php.
Die neue authentifizierte Sprachabfrage registrieren (oder Settings-Reparatur):
php wordplugin-officejs/build/registerWordMailMergeOperation.php
php outlook-addin/build/registerOutlookAddinOperation.php
Danach eigene Manifeste im CRM-Settingsmenue neu erzeugen und Add-ins aktualisieren.
Die XML-Dateien mit Beispieladressen nicht direkt installieren.
Anleitung: wordplugin-officejs/LANGUAGES.md

EN: Back up server files and your individual manifests, then extract into the CRM
webroot. Keep your private Outlook configuration; it is excluded from this archive.
Translations are keyed PHP arrays in each add-in's languages directory.
The shared utility is include/utils/OfficeAddinI18n.php.
Register the new authenticated language operation (or use Settings repair):
php wordplugin-officejs/build/registerWordMailMergeOperation.php
php outlook-addin/build/registerOutlookAddinOperation.php
Generate new individual manifests in CRM Settings and update the add-ins.
Do not install the example manifests directly.
See wordplugin-officejs/LANGUAGES.en.md for deployment and maintenance.
`);
const target = path.join(root, 'outlook-addin/build/office-addins-language-update-20261105.zip');
await fs.writeFile(target, zipSync(files, { level: 6 }));
const readBack = unzipSync(new Uint8Array(await fs.readFile(target)));
for (const name of names) assert.equal(digest(readBack[name]), digest(files[name]), 'Archive checksum: ' + name);
process.stdout.write('Created ' + path.basename(target) + ': ' + names.length + ' versioned files; checksums and ZIP read-back verified.\n');
