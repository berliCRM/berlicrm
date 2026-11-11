const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs/promises');
const path = require('node:path');
const os = require('node:os');
const net = require('node:net');
const { spawn, execFileSync } = require('node:child_process');
const { once } = require('node:events');
const { pathToFileURL } = require('node:url');
const php = process.env.PHP_BINARY || 'C:/php/php.exe';

async function freePort() {
  const server = net.createServer(); server.listen(0, '127.0.0.1');
  await once(server, 'listening');
  const port = server.address().port;
  await new Promise(resolve => server.close(resolve));
  return port;
}

function startPhp(root, port) {
  const process = spawn(php, ['-d', 'extension=curl', '-S', '127.0.0.1:' + port, '-t', root],
    { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
  let log = '';
  const ready = new Promise((resolve, reject) => {
    const timer = setTimeout(() => reject(new Error('PHP server startup: ' + log)), 5000);
    process.once('error', error => { clearTimeout(timer); reject(error); });
    process.once('exit', code => { clearTimeout(timer); reject(new Error('PHP exited: ' + code + ' ' + log)); });
    process.stderr.on('data', bytes => {
      log += bytes;
      if (log.includes('Development Server')) { clearTimeout(timer); resolve(); }
    });
  });
  return { process, ready };
}

test('the extracted repair ZIP serves login, preferences and contact metadata with all language dependencies', { timeout: 30000 }, async () => {
  const { createRepairPackage } = await import(pathToFileURL(path.join(__dirname, '../build/package-outlook-repair.mjs')));
  const { unzipSync } = await import(pathToFileURL(path.join(__dirname, '../../wordplugin-officejs/src/vendor/fflate-0.8.3.js')));
  const root = await fs.mkdtemp(path.join(os.tmpdir(), 'berlicrm-repair-'));
  const files = new Set(), directories = new Set(), servers = [];
  async function write(name, bytes) {
    const target = path.resolve(root, name);
    assert.ok(target.startsWith(root + path.sep), 'Only write within this test fixture');
    let directory = path.dirname(target);
    while (directory !== root) { directories.add(directory); directory = path.dirname(directory); }
    await fs.mkdir(path.dirname(target), { recursive: true });
    await fs.writeFile(target, bytes); files.add(target);
  }
  try {
    const archive = path.join(root, 'repair.zip'); files.add(archive);
    await createRepairPackage(archive);
    const contents = unzipSync(new Uint8Array(await fs.readFile(archive)));
    assert.ok(!Object.keys(contents).some(name => /outlook\.config\.php$|manifest\.generated\.xml$/.test(name)));
    for (const [name, bytes] of Object.entries(contents)) await write(name, bytes);
    const [gatewayPort, crmPort] = await Promise.all([freePort(), freePort()]);
    await write('outlook-addin/rest/outlook.config.php', `<?php return [
      'crm_webservice_url' => 'http://127.0.0.1:${crmPort}/webservice.php',
      'contacts_auth_relay_directory' => dirname(__DIR__, 2) . '/relay',
      'default_request_timeout' => 3, 'api_access_key' => 'private-sentinel'
    ];`);
    await write('webservice.php', `<?php
      header('Content-Type: application/json');
      $input = array_merge($_GET, $_POST); $operation = $input['operation'] ?? '';
      if ($operation === 'getchallenge') $result = ['token' => 'fixture-challenge'];
      elseif ($operation === 'login') $result = ['sessionName' => 'fixture-session', 'userId' => '19x3'];
      elseif (!in_array($input['sessionName'] ?? '', ['fixture-session', 'other-session'], true)) {
        echo json_encode(['success' => false, 'error' => ['message' => 'INVALID_SESSIONID']]); exit;
      }
      elseif ($operation === 'office_addin_user_preferences') {
        if (isset($input['userId'])) throw new RuntimeException('Client-supplied user ID');
        $result = ['language' => 'de_de'];
      }
      elseif ($operation === 'outlook_contact_transfer') $result = ['canCreate' => true, 'fields' => [], 'ownerId' => '19x3'];
      else throw new RuntimeException('Unexpected operation: ' . $operation);
      echo json_encode(['success' => true, 'result' => $result]);
    `);
    await write('registry-check.php', `<?php
      class Settings_Vtiger_Module_Model {}
      chdir(__DIR__);
      require 'modules/Settings/OutlookAddin/models/Module.php';
      foreach (Settings_OutlookAddin_Module_Model::getOperations() as $operation) {
        if (!is_file($operation['path'])) throw new RuntimeException('Missing handler: ' . $operation['path']);
        if ($operation['name'] === 'office_addin_user_preferences') {
          if ($operation['method'] !== 'berli_officeAddinUserPreferences' || $operation['params'] !== []) throw new RuntimeException('Invalid preferences registration');
          $found = true;
        }
      }
      if (empty($found)) throw new RuntimeException('Missing language registration');
      echo 'Registration dependencies verified';
    `);
    assert.match(execFileSync(php, [path.join(root, 'registry-check.php')], { encoding: 'utf8' }), /Registration dependencies verified/);
    servers.push(startPhp(root, gatewayPort), startPhp(root, crmPort));
    await Promise.all(servers.map(server => server.ready));
    const endpoint = 'http://127.0.0.1:' + gatewayPort + '/outlook-addin/rest/outlook.php';
    const request = async input => {
      const response = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(input), signal: AbortSignal.timeout(5000) });
      assert.equal(response.headers.get('cache-control'), 'no-store');
      const text = await response.text();
      assert.doesNotMatch(text, /private-sentinel|Unbekannte Aktion|Unknown action/);
      return JSON.parse(text);
    };
    const login = await request({ action: 'login', username: 'Fixture user', accessKey: 'not-stored', language: 'en_us' });
    assert.equal(login.success, true);
    assert.equal(login.payload.language, 'de_de');
    const preferences = await request({ action: 'getUserPreferences', sessionName: login.payload.sessionName, language: 'en_us' });
    assert.deepEqual(preferences, { success: true, payload: { language: 'de_de' } });
    const contact = await request({ action: 'contactTransfer', sessionName: login.payload.sessionName, transfer: { mode: 'describe' } });
    assert.equal(contact.success, true); assert.equal(contact.payload.canCreate, true);
    const relayTools = require(path.join(root, 'outlook-addin/contacts-auth-relay.js'));
    const flow = await request({ action: 'beginContactsAuthRelay', sessionName: login.payload.sessionName });
    assert.equal(flow.success, true);
    const relay = { ...flow.payload, key: await relayTools.createKey() };
    const encrypted = await relayTools.encrypt(relay, { type: 'berliCRM.contacts.connected', token: 'graph-token-in-browser-only' });
    const completed = await request({ action: 'completeContactsAuthRelay', flowId: relay.flowId, writeKey: relay.writeKey, payload: encrypted });
    assert.equal(completed.success, true);
    const duplicate = await request({ action: 'completeContactsAuthRelay', flowId: relay.flowId, writeKey: relay.writeKey, payload: encrypted });
    assert.equal(duplicate.success, false, 'A completed response cannot be overwritten');
    const anotherSession = await request({ action: 'pollContactsAuthRelay', flowId: relay.flowId, sessionName: 'other-session' });
    assert.equal(anotherSession.success, false);
    const collected = await request({ action: 'pollContactsAuthRelay', flowId: relay.flowId, sessionName: login.payload.sessionName });
    assert.equal(collected.success, true);
    assert.equal((await relayTools.decrypt(relay, collected.payload.payload)).token, 'graph-token-in-browser-only');
    assert.doesNotMatch(JSON.stringify(collected), /graph-token-in-browser-only/);
    assert.equal((await request({ action: 'closeContactsAuthRelay', flowId: relay.flowId, sessionName: login.payload.sessionName })).success, true);
    const invalid = await request({ action: 'getUserPreferences', sessionName: 'expired-session' });
    assert.equal(invalid.success, false); assert.match(invalid.payload, /INVALID_SESSIONID/);
  } finally {
    for (const { process } of servers) {
      if (process.exitCode === null) { const closed = once(process, 'close'); process.kill(); await closed; }
    }
    const relayRoot = path.join(root, 'relay');
    for (const scope of await fs.readdir(relayRoot).catch(error => { if (error.code === 'ENOENT') return []; throw error; })) {
      assert.match(scope, /^berlicrm-auth-[a-f0-9]{64}$/);
      const directory = path.join(relayRoot, scope); directories.add(directory); directories.add(relayRoot);
      for (const name of await fs.readdir(directory)) {
        assert.match(name, /^[a-f0-9]{64}\.json$/); files.add(path.join(directory, name));
      }
    }
    for (const name of files) await fs.unlink(name).catch(error => { if (error.code !== 'ENOENT') throw error; });
    for (const name of [...directories].sort((a, b) => b.length - a.length)) await fs.rmdir(name);
    await fs.rmdir(root);
  }
});
