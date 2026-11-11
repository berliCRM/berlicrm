// Builds an update with relative CRM-root paths; never includes configuration or node_modules.
// node wordplugin-officejs/build/package-word-update.mjs
import { readFile, writeFile, readdir } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
import { zipSync, unzipSync, strToU8, strFromU8 } from '../src/vendor/fflate-0.8.3.js';
const root = new URL('../../', import.meta.url);
const read = name => readFile(new URL(name, root));
const digest = bytes => createHash('sha256').update(bytes).digest('hex');
async function tree(name) {
  const entries = await readdir(new URL(name + '/', root), { withFileTypes: true });
  return (await Promise.all(entries.map(entry => entry.isDirectory() ? tree(name + '/' + entry.name) : [name + '/' + entry.name]))).flat();
}
try {
  const baseline = unzipSync(await read('wordplugin-officejs/build/word-rollback-1.2.3.0-before-docx.zip'));
  let checked = 0;
  for (const line of strFromU8(baseline['SHA256SUMS.txt']).trim().split(/\r?\n/)) {
    const [, hash, name] = /^([a-f0-9]{64})  (.+)$/.exec(line);
    assert.equal(digest(baseline[name]), hash, `Rollback checksum: ${name}`); checked++;
  }
  // Localization intentionally changes status/error strings in the legacy path.
  // Preserve and verify the immutable rollback archive; runtime regressions are
  // covered by the Word export tests rather than byte equality with old sources.
  process.stdout.write(`Verified ${checked} immutable rollback checksums.\n`);
} catch (error) {
  if (error.code !== 'ENOENT') throw error;
  process.stdout.write('No local rollback archive found; package build does not create or overwrite a baseline.\n');
}
const names = [
  'include/utils/OfficeAddinI18n.php',
  'wordplugin-officejs/taskpane.html', 'wordplugin-officejs/manifest.xml', 'wordplugin-officejs/manifest.template.xml', 'wordplugin-officejs/language.js',
  'wordplugin-officejs/package.json', 'wordplugin-officejs/package-lock.json',
  ...['README.md', 'README.en.md', 'DOKUMENTATION.md', 'DOKUMENTATION.en.md', 'LANGUAGES.md', 'LANGUAGES.en.md'].map(name => 'wordplugin-officejs/' + name),
  ...await tree('wordplugin-officejs/languages'),
  ...await tree('wordplugin-officejs/src'), ...await tree('wordplugin-officejs/assets'), ...await tree('wordplugin-officejs/tests'),
  'wordplugin-officejs/build/registerWordMailMergeOperation.php', 'wordplugin-officejs/build/package-word-update.mjs',
  'wordplugin-officejs/build/build-languages.mjs',
  ...await tree('modules/Settings/WordAddin'),
  'layouts/vlayout/modules/Settings/WordAddin/Index.tpl',
  'languages/de_de/Settings/WordAddin.php', 'languages/en_us/Settings/WordAddin.php',
  ...['getWordMailMergeData.php', 'saveWordMailMergeDocument.php', 'WordMailMergeDocx.php'].map(name => 'include/Webservices/Custom/' + name),
].sort();
const files = Object.create(null);
for (const name of names) files[name] = new Uint8Array(await read(name));
assert.match(strFromU8(files['wordplugin-officejs/manifest.xml']), /<Version>1\.4\.0\.0<\/Version>/);
assert.match(strFromU8(files['wordplugin-officejs/languages/de_de.php']), /Version 1\.4\.0\.0/);
assert.match(strFromU8(files['wordplugin-officejs/taskpane.html']), /value="docx" selected/);
files['SHA256SUMS.txt'] = strToU8(names.map(name => digest(files[name]) + '  ' + name).join('\n') + '\n');
files['UPDATE.txt'] = strToU8(`berliCRM Word Add-in 1.4.0.0

DE: Serverdateien und individuelles Manifest zuerst sichern. Archiv im CRM-Webroot
entpacken. Private Outlook-Konfiguration beibehalten. Die Sprachabfrage registrieren:
php wordplugin-officejs/build/registerWordMailMergeOperation.php
Danach das Manifest im CRM-Settingsmenue neu erzeugen und das Add-in aktualisieren.
Die Textquellen sind languages/de_de.php und languages/en_us.php (Schluessel => Text).
Die Oberflaeche folgt dem angemeldeten CRM-Benutzer. DOCX bleibt voreingestellt,
HTML/.doc bleibt als Rueckfallweg verfuegbar. Anleitung: wordplugin-officejs/LANGUAGES.md

EN: Back up server files and your individual manifest first. Extract into the CRM
webroot. Keep private Outlook configuration. Register the language operation:
php wordplugin-officejs/build/registerWordMailMergeOperation.php
Generate a fresh manifest in CRM Settings and update the add-in afterwards.
Translations use keyed PHP arrays in languages/de_de.php and languages/en_us.php.
The UI follows the signed-in CRM user. DOCX remains the default; HTML/.doc remains
available. See wordplugin-officejs/LANGUAGES.en.md for deployment and maintenance.
`);
const target = new URL('wordplugin-officejs/build/word-update-1.4.0.0.zip', root);
await writeFile(target, zipSync(files, { level: 6 }));
const packaged = unzipSync(await readFile(target));
for (const name of names) assert.equal(digest(packaged[name]), digest(files[name]), `Update checksum: ${name}`);
process.stdout.write(`Created word-update-1.4.0.0.zip: ${names.length} files plus update instructions/checksums; ZIP read-back verified.\n`);
