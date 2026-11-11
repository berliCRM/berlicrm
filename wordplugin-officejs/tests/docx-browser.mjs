// Optional native-browser check. Runs entirely on loopback with synthetic data.
// node wordplugin-officejs/tests/docx-browser.mjs <Edge executable path>
import { createServer } from 'node:http';
import { readFile, mkdtemp } from 'node:fs/promises';
import { spawn } from 'node:child_process';
import { tmpdir } from 'node:os';
import path from 'node:path';
import assert from 'node:assert/strict';
import { multiPageTemplate, moduleFixture, recordsFixture } from './docx-fixture.js';
import { unzipSync, zipSync, strFromU8, strToU8 } from '../src/vendor/fflate-0.8.3.js';
const root = new URL('../src/', import.meta.url);
const templateParts = unzipSync(multiPageTemplate());
const boundControl = '<w:sdt><w:sdtPr><w:id w:val="1"/><w:dataBinding w:xpath="/name" w:storeItemID="{00000000-0000-0000-0000-000000000001}"/></w:sdtPr><w:sdtContent><w:r><w:t>«CONTACT_FIRST» «CONTACT_LAST»</w:t></w:r></w:sdtContent></w:sdt>';
templateParts['word/document.xml'] = strToU8(strFromU8(templateParts['word/document.xml']).replace('<w:body>', `<w:body><w:p>${boundControl}</w:p>`));
templateParts['word/header1.xml'] = strToU8(strFromU8(templateParts['word/header1.xml']).replace('<w:p>', `<w:p>${boundControl}`));
const template = zipSync(templateParts);
const html = `<!doctype html><html><body data-result="pending"><script type="module">
try {
  const {prepareDocxTemplate} = await import('/src/docx-engine.js');
  const {unzipSync,strFromU8} = await import('/src/vendor/fflate-0.8.3.js');
  const template = new Uint8Array(await (await fetch('/fixture.docx')).arrayBuffer());
  const engine = prepareDocxTemplate(template, ${JSON.stringify(moduleFixture)});
  const files = unzipSync(engine.combined(${JSON.stringify(recordsFixture)}));
  const doc = new DOMParser().parseFromString(strFromU8(files['word/document.xml']), 'application/xml');
  if (doc.querySelector('parsererror')) throw new Error('Browser XML parser rejected the result.');
  const ns = ${JSON.stringify('http://schemas.openxmlformats.org/wordprocessingml/2006/main')};
  const sections = doc.getElementsByTagNameNS(ns, 'sectPr').length;
  if (sections !== 4 || !doc.documentElement.textContent.includes('Grace') || !files['word/media/logo.png']) throw new Error('Incomplete browser output.');
  if (doc.getElementsByTagNameNS(ns, 'dataBinding').length) throw new Error('Recipient binding was retained.');
  const controls = [...doc.getElementsByTagNameNS(ns, 'sdtPr')];
  const ids = controls.map(node => node.getElementsByTagNameNS(ns, 'id')[0].getAttributeNS(ns, 'val'));
  if (new Set(ids).size !== ids.length) throw new Error('Duplicate recipient control IDs.');
  document.body.dataset.result = 'pass';
  document.body.textContent = 'Native browser DOM/ZIP: 4 sections, both recipients, image retained.';
} catch (error) { document.body.dataset.result = 'fail'; document.body.textContent = error.stack; }
</script></body></html>`;
const server = createServer(async (req, res) => {
  try {
    const url = new URL(req.url, 'http://localhost');
    if (url.pathname === '/') { res.setHeader('Content-Type', 'text/html; charset=utf-8'); res.end(html); return; }
    if (url.pathname === '/fixture.docx') { res.end(template); return; }
    const name = url.pathname.replace(/^\/src\//, '');
    if (!url.pathname.startsWith('/src/') || name.includes('..') || !name.endsWith('.js')) { res.writeHead(404); res.end(); return; }
    res.setHeader('Content-Type', 'text/javascript; charset=utf-8'); res.end(await readFile(new URL(name, root)));
  } catch { res.writeHead(500); res.end('Local fixture read failed.'); }
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const profile = await mkdtemp(path.join(tmpdir(), 'berlicrm-docx-browser-'));
try {
  const browser = spawn(process.argv[2], ['--headless=new', '--disable-gpu', '--disable-extensions', '--no-first-run', '--no-default-browser-check', '--disable-background-networking', `--user-data-dir=${profile}`, '--virtual-time-budget=10000', '--dump-dom', `http://127.0.0.1:${server.address().port}/`], { windowsHide: true });
  let output = '', errors = '';
  browser.stdout.on('data', chunk => output += chunk); browser.stderr.on('data', chunk => errors += chunk);
  const timeout = setTimeout(() => browser.kill(), 30000);
  const exitCode = await new Promise((resolve, reject) => { browser.on('error', reject); browser.on('close', resolve); });
  clearTimeout(timeout);
  assert.equal(exitCode, 0, errors.slice(-1500));
  assert.match(output, /data-result="pass"/, output.slice(-3000));
  process.stdout.write('PASS: native Edge DOM/XML and DOCX ZIP generation, recipient bindings detached, independent control IDs, four sections and embedded picture.\n');
} finally { await new Promise(resolve => server.close(resolve)); }
