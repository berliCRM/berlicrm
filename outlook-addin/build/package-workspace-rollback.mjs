/** Packages the exact pre-workspace runtime from Git, without touching the checkout. */
import fs from 'node:fs/promises';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { zipSync, unzipSync } from '../../wordplugin-officejs/src/vendor/fflate-0.8.3.js';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
export const baseline = '0cfdaee0a0fdfcf90a87eff4048db6dfcf2683fc';
function git(args) {
  return execFileSync('git', ['-c', 'safe.directory=' + root.replaceAll('\\', '/'), ...args],
    { cwd: root, windowsHide: true, maxBuffer: 8 * 1024 * 1024 });
}
export function baselineFiles() {
  const source = git(['show', baseline + ':outlook-addin/build/package-outlook-repair.mjs']).toString('utf8');
  const browserList = source.match(/const browserNames = \[([\s\S]*?)\];/);
  if (!browserList) throw new Error('Missing baseline deployment list');
  const names = [...browserList[1].matchAll(/'([^']+)'/g)].map(match => 'outlook-addin/' + match[1]);
  const tree = git(['ls-tree', '-r', '--name-only', baseline]).toString('utf8').trim().split('\n');
  names.push(...tree.filter(name => /^include\/Webservices\/Custom\/Outlook[^/]*\.php$/.test(name)
    || name.startsWith('modules/Settings/OutlookAddin/') || name.startsWith('layouts/vlayout/modules/Settings/OutlookAddin/')),
  'include/utils/OfficeAddinI18n.php', 'languages/de_de/Settings/OutlookAddin.php', 'languages/en_us/Settings/OutlookAddin.php');
  if (new Set(names).size !== names.length || names.some(name => /outlook\.config\.php$|manifest\.generated\.xml$|^\/|\.\./.test(name))) {
    throw new Error('Unsafe baseline deployment list');
  }
  return names.sort();
}
export async function createRollbackPackage(target = path.join(root, 'outlook-addin/build/outlook-addin-1.1.0.2-before-workspace.zip')) {
  const names = baselineFiles(), files = {};
  const digest = bytes => createHash('sha256').update(bytes).digest('hex');
  for (const name of names) files[name] = git(['show', baseline + ':' + name]);
  const manifest = files['outlook-addin/manifest.xml'].toString('utf8');
  if (!manifest.includes('<Version>1.1.0.2</Version>') || manifest.includes('SupportsNoItemContext')) throw new Error('Unexpected rollback manifest');
  files['RESTORE-MAIL-MANIFEST.xml'] = Buffer.from(manifest.replaceAll('https://crm.example.org', 'https://unsercrm.i1.crm-now.de'));
  files['SHA256SUMS.txt'] = Buffer.from(names.map(name => digest(files[name]) + '  ' + name).join('\n') + '\n');
  files['ROLLBACK.txt'] = Buffer.from(`berliCRM Outlook 1.1.0.2 - Rueckkehr vor dem Kontakte-Arbeitsbereich

Quelle: Git ${baseline}
Alle ${names.length} Laufzeitdateien wurden unveraendert aus diesem Commit gelesen.
Die ZIP-Inhalte und Pruefsummen wurden nach dem Schreiben erneut verglichen.
Das separate RESTORE-MAIL-MANIFEST.xml hat nur die Beispieladresse durch
https://unsercrm.i1.crm-now.de ersetzt und kann dort installiert werden.

1. Nur falls die verworfene App installiert wurde: "berliCRM Kontakte"
   mit ID ef755171-58ae-4c84-86b1-c1fe2d955570
   im Microsoft-365-Admincenter unter Einstellungen > Integrierte Apps entfernen
   bzw. die Bereitstellung zuruecknehmen. Bei persoenlicher Installation ueber
   Teams die App in Teams/Outlook entfernen.
   Das bisherige Mail-/Termin-Add-in mit ID 4fbaf5e4-1f93-4c85-9cbf-128c7d8a1909
   beibehalten.
2. Dieses Paket im CRM-Hauptverzeichnis entpacken und seine Dateien auf den
   Server uebertragen. outlook-addin/rest/outlook.config.php unveraendert lassen.
3. Falls das Mail-Manifest aktualisiert wurde oder noch das erfolglose
   No-Item-Testmanifest installiert ist, RESTORE-MAIL-MANIFEST.xml installieren.
4. Outlook vollstaendig neu laden. Die bisherigen Mail-, Kontakte- und
   Microsoft-Anmelde-Seiten sind damit wieder auf Version 1.1.0.2.

Es gab fuer den neuen Arbeitsbereich keine Datenbankmigration. Kontakte, die
du waehrend des Tests bewusst uebernommen hast, werden nicht geloescht.
Neue contacts-workspace*-Dateien und contacts-layout.js duerfen liegen bleiben;
die wiederhergestellte bisherige Loesung bindet sie nicht ein.
Die Ruecksetzung erfordert weder git reset noch Aenderungen an der privaten
Serverkonfiguration, Entra-Registrierung oder Microsoft-Zugangsdaten.
`);
  await fs.mkdir(path.dirname(target), { recursive: true });
  await fs.writeFile(target, zipSync(files, { level: 6 }));
  const restored = unzipSync(new Uint8Array(await fs.readFile(target)));
  if (Object.keys(restored).length !== Object.keys(files).length) throw new Error('Rollback ZIP entry mismatch');
  for (const [name, bytes] of Object.entries(files)) if (digest(restored[name]) !== digest(bytes)) throw new Error('Rollback ZIP mismatch: ' + name);
  return { target, files: names.length, baseline };
}
if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const result = await createRollbackPackage();
  process.stdout.write('Created ' + result.target + ': ' + result.files + ' unchanged Git baseline files; ZIP checksums verified.\n');
}
