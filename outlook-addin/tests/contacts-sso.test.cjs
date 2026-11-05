const { localizedContext } = require('./language-fixture.cjs');
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../contacts-sso-entry.js'), 'utf8').replace(/^import .*;\r?\n/, '');
function setup(options = {}) {
  const events = [];
  class PublicClientApplication {
    constructor(config) { events.push(['config', config]); }
    async initialize() { events.push(['initialize']); }
    async ssoSilent(request) {
      events.push(['silent', request]);
      if (options.error) throw options.error;
      return options.incomplete ? {} : { accessToken: 'memory-only', account: { homeAccountId: 'account', username: 'user@example.test' }, expiresOn: new Date(Date.now() + 60000) };
    }
    async clearCache() { events.push(['clear']); }
  }
  const context = localizedContext({ PublicClientApplication, URL, Date, window: {}, location: new URL('https://crm.example.test/outlook-addin/contacts.html') });
  vm.runInContext(source, context);
  const config = { configured: true, clientId: 'client', tenantId: 'tenant', redirectUri: 'https://crm.example.test/outlook-addin/contacts-auth.html' };
  return { events, connect: (overrides = {}, hint = '') => context.window.BerliContactsAuth.connectSilently({ ...config, ...overrides }, hint) };
}
test('silent authentication requests only contact access, uses memory storage and clears its cache', async () => {
  const env = setup();
  const result = await env.connect({}, 'owner@example.test');
  assert.equal(result.type, 'berliCRM.contacts.connected');
  assert.equal(result.username, 'user@example.test');
  assert.equal(env.events[0][1].cache.cacheLocation, 'memoryStorage');
  assert.equal(env.events[0][1].system.iframeHashTimeout, 8000);
  const request = env.events.find(entry => entry[0] === 'silent')[1];
  assert.deepEqual(Array.from(request.scopes), ['https://graph.microsoft.com/Contacts.ReadWrite']);
  assert.equal(request.loginHint, 'owner@example.test');
  assert.equal(env.events.at(-1)[0], 'clear');
});
test('consent/login errors are returned to the UI without interactive APIs and with cache cleanup', async () => {
  const env = setup({ error: new Error('interaction_required') });
  await assert.rejects(env.connect(), /interaction_required/);
  assert.equal(env.events.at(-1)[0], 'clear');
  const incomplete = setup({ incomplete: true });
  await assert.rejects(incomplete.connect(), /keine vollständige/);
  assert.equal(incomplete.events.at(-1)[0], 'clear');
});
test('silent SSO cannot send a response to an external or unexpected callback URI', async () => {
  for (const redirectUri of ['https://other.example.test/outlook-addin/contacts-auth.html', 'https://crm.example.test/wrong.html', 'https://crm.example.test/outlook-addin/contacts-auth.html?other=1']) {
    const env = setup();
    await assert.rejects(env.connect({ redirectUri }), /Redirect-URI/);
    assert.equal(env.events.length, 0);
  }
});
