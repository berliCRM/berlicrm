const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const net = require('node:net');
const { spawn } = require('node:child_process');
const { once } = require('node:events');

// Exercise PHP's actual relative includes and configuration lookup without CRM access.
test('nested REST endpoint loads its adjacent private configuration and public helpers', async () => {
  const root = fs.mkdtempSync(path.join(os.tmpdir(), 'berlicrm-rest-test-'));
  const endpointDir = path.join(root, 'outlook-addin', 'rest');
  const server = net.createServer();
  server.listen(0, '127.0.0.1');
  await once(server, 'listening');
  const port = server.address().port;
  await new Promise(resolve => server.close(resolve));
  fs.mkdirSync(endpointDir, { recursive: true });
  const sources = ['outlook.php', 'OutlookWebserviceClient.php', 'OutlookContactsConfig.php'];
  for (const name of sources) fs.copyFileSync(path.join(__dirname, '..', 'rest', name), path.join(endpointDir, name));
  fs.writeFileSync(path.join(endpointDir, 'outlook.config.php'), `<?php return [
    'crm_webservice_url' => 'https://crm.example.test/installation/webservice.php',
    'api_access_key' => 'private-test-sentinel',
    'contacts_microsoft' => [
      'client_id' => '11111111-1111-1111-1111-111111111111',
      'tenant_id' => '22222222-2222-2222-2222-222222222222'
    ]
  ];`);
  const child = spawn(process.env.PHP_BINARY || 'C:\\php\\php.exe', ['-S', '127.0.0.1:' + port, '-t', root], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
  let log = '';
  child.stderr.on('data', chunk => { log += chunk; });
  try {
    await new Promise((resolve, reject) => {
      const timeout = setTimeout(() => reject(new Error('PHP server did not start: ' + log)), 5000);
      child.once('error', error => { clearTimeout(timeout); reject(error); });
      child.once('exit', code => { clearTimeout(timeout); reject(new Error('PHP server exited: ' + code + ' ' + log)); });
      child.stderr.on('data', () => { if (log.includes('Development Server')) { clearTimeout(timeout); resolve(); } });
    });
    const base = 'http://127.0.0.1:' + port;
    assert.equal((await fetch(base + '/rest/outlook.php')).status, 404);
    const endpoint = base + '/outlook-addin/rest/outlook.php';
    assert.equal((await fetch(endpoint)).status, 405);
    const response = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'getContactsMicrosoftConfig' }) });
    assert.equal(response.status, 200);
    assert.equal(response.headers.get('cache-control'), 'no-store');
    const text = await response.text();
    assert.doesNotMatch(text, /private-test-sentinel|api_access_key/);
    assert.deepEqual(JSON.parse(text), { success: true, payload: {
      configured: true,
      clientId: '11111111-1111-1111-1111-111111111111',
      tenantId: '22222222-2222-2222-2222-222222222222',
      redirectUri: 'https://crm.example.test/installation/outlook-addin/contacts-auth.html'
    } });
  } finally {
    if (child.exitCode === null) { const closed = once(child, 'close'); child.kill(); await closed; }
    // Remove only files and directories created by this test; no recursive deletion.
    for (const name of [...sources, 'outlook.config.php']) fs.unlinkSync(path.join(endpointDir, name));
    fs.rmdirSync(endpointDir);
    fs.rmdirSync(path.dirname(endpointDir));
    fs.rmdirSync(root);
  }
});
