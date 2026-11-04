// Builds an update with relative CRM-root paths; never includes configuration or node_modules.
// node wordplugin-officejs/build/package-word-update.mjs
import { readFile, writeFile, readdir } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
import { zipSync, unzipSync, strToU8, strFromU8 } from '../src/vendor/fflate-0.8.3.js';
const root = new URL('../../', import.meta.url);
const read = name => readFile(new URL(name, root));
const digest = bytes => createHash('sha256').update(bytes).digest('hex');
const normalize = text => text.replace(/\r\n/g, '\n');
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
  const before = normalize(strFromU8(baseline['wordplugin-officejs/src/taskpane.js']));
  const after = normalize((await read('wordplugin-officejs/src/taskpane.js')).toString('utf8'));
  const original = before.slice(before.indexOf('async function createMergeDocuments()'), before.indexOf('\nasync function insertFields()'));
  const legacy = after.slice(after.indexOf('async function createLegacyMergeDocuments()'), after.indexOf('\nasync function insertFields()')).replace('createLegacyMergeDocuments()', 'createMergeDocuments()');
  assert.ok(original.length > 1000); assert.equal(legacy, original, 'Legacy export body changed');
  for (const name of ['src/crm-client.js', 'src/zip.js', 'src/taskpane.css']) assert.equal(digest(await read('wordplugin-officejs/' + name)), digest(baseline['wordplugin-officejs/' + name]), `Legacy dependency changed: ${name}`);
  process.stdout.write(`Verified ${checked} rollback checksums and unchanged legacy export implementation/dependencies.\n`);
} catch (error) {
  if (error.code !== 'ENOENT') throw error;
  process.stdout.write('No local rollback archive found; package build does not create or overwrite a baseline.\n');
}
const names = [
  'wordplugin-officejs/taskpane.html', 'wordplugin-officejs/manifest.xml',
  'wordplugin-officejs/package.json', 'wordplugin-officejs/package-lock.json',
  ...['README.md', 'README.en.md', 'DOKUMENTATION.md', 'DOKUMENTATION.en.md'].map(name => 'wordplugin-officejs/' + name),
  ...await tree('wordplugin-officejs/src'), ...await tree('wordplugin-officejs/assets'), ...await tree('wordplugin-officejs/tests'),
  'wordplugin-officejs/build/registerWordMailMergeOperation.php', 'wordplugin-officejs/build/package-word-update.mjs',
  ...await tree('modules/Settings/WordAddin'),
  'layouts/vlayout/modules/Settings/WordAddin/Index.tpl',
  'languages/de_de/Settings/WordAddin.php', 'languages/en_us/Settings/WordAddin.php',
  ...['getWordMailMergeData.php', 'saveWordMailMergeDocument.php', 'WordMailMergeDocx.php'].map(name => 'include/Webservices/Custom/' + name),
].sort();
const files = Object.create(null);
for (const name of names) files[name] = new Uint8Array(await read(name));
assert.match(strFromU8(files['wordplugin-officejs/manifest.xml']), /<Version>1\.3\.0\.4<\/Version>/);
assert.match(strFromU8(files['wordplugin-officejs/taskpane.html']), /Version 1\.3\.0\.4/);
assert.match(strFromU8(files['wordplugin-officejs/taskpane.html']), /value="docx" selected/);
files['SHA256SUMS.txt'] = strToU8(names.map(name => digest(files[name]) + '  ' + name).join('\n') + '\n');
files['UPDATE.txt'] = strToU8(`berliCRM Word Add-in 1.3.0.4

DE: Vorhandene Serverdateien und individuelles Manifest zuerst sichern.
Archiv im CRM-Webroot entpacken; Pfade beibehalten. Keine neue Webservice-
Registrierung und kein db_update fuer dieses Formatupdate erforderlich.
DOCX-Ablage im CRM benoetigt PHP ZIP; Downloads und Legacy benoetigen es nicht.
Das eigene Manifest im Word-Settingsmenue neu erzeugen (Version 1.3.0.4).
manifest.xml im Archiv enthaelt Beispieladressen: nicht direkt installieren.
Add-in neu laden: Fusszeile Version 1.3.0.4. Voreinstellung ist DOCX.
Bei Datensatzabrufen zeigt ein Spinner den laufenden Vorgang an.
Rueckfall: altes Word-Dokument (.doc/HTML) auswaehlen. Vollstaendig zurueck:
Serverbackup bzw. separates word-rollback-1.2.3.0-before-docx.zip einspielen.
Weitere Hinweise: wordplugin-officejs/DOKUMENTATION.md

EN: Back up existing server files and your own manifest before deployment.
Extract into the CRM webroot retaining paths. No new service registration or
db_update is needed for this format update. CRM DOCX storage requires PHP ZIP;
downloads and legacy output do not. Generate your own manifest in CRM Word
settings, version 1.3.0.4. Do not install the example manifest.xml directly.
Reload the add-in. DOCX is the default. A spinner indicates record-page loading.
Switch back using altes Word-Dokument (.doc/HTML).
For full rollback restore your server backup or the separate baseline archive.
See wordplugin-officejs/DOKUMENTATION.en.md for details.
`);
const target = new URL('wordplugin-officejs/build/word-update-1.3.0.4.zip', root);
await writeFile(target, zipSync(files, { level: 6 }));
const packaged = unzipSync(await readFile(target));
for (const name of names) assert.equal(digest(packaged[name]), digest(files[name]), `Update checksum: ${name}`);
process.stdout.write(`Created word-update-1.3.0.4.zip: ${names.length} files plus update instructions/checksums; ZIP read-back verified.\n`);
