const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../contacts-auth-entry.js'), 'utf8').replace(/^import .*;\r?\n/, '');
function setup(options = {}) {
  const events = [], stored = new Map(options.savedChannel ? [['berliCRM.contacts.authChannel', options.savedChannel]] : []);
  const origin = 'https://crm.example.test';
  const location = new URL(origin + '/outlook-addin/contacts-auth.html' + (options.start ? '?start=1&channel=office' : '#code=example'));
  const config = { configured: true, clientId: 'client', tenantId: 'tenant', redirectUri: options.badRedirect || origin + '/outlook-addin/contacts-auth.html' };
  class PublicClientApplication {
    constructor(config) { events.push(['config', config]); }
    async initialize() {}
    async handleRedirectPromise() {
      if (options.error) throw options.error;
      return options.start ? null : { accessToken: 'token', account: { homeAccountId: 'account', username: 'user@example.test' }, expiresOn: new Date(Date.now() + 60000) };
    }
    async clearCache() { events.push(['clear']); }
    async loginRedirect(request) { events.push(['login', request]); }
  }
  const elements = new Map();
  const element = id => {
    if (!elements.has(id)) elements.set(id, { textContent: '', hidden: false, disabled: false, dataset: {}, events: {}, addEventListener(name, handler) { this.events[name] = handler; } });
    return elements.get(id);
  };
  const status = element('authStatus');
  location.replace = address => events.push(['navigate', address]);
  const context = vm.createContext({ PublicClientApplication, URL, URLSearchParams, location,
    sessionStorage: { getItem: key => stored.get(key), setItem: (key, value) => stored.set(key, value), removeItem: key => stored.delete(key) },
    document: { getElementById: element },
    fetch: async url => {
      assert.equal(String(url), origin + '/outlook-addin/rest/outlook.php');
      return { ok: true, json: async () => ({ success: true, payload: config }) };
    },
    Office: { onReady: async () => ({ host: null }), context: { ui: { messageParent: (message, settings) => events.push(['send', JSON.parse(message), settings]) } } },
    window: { opener: { postMessage: (message, targetOrigin) => events.push(['popup', message, targetOrigin]) }, close() {} },
  });
  if (options.iframe) context.window.parent = {};
  return { events, stored, status, element, location, run: () => vm.runInContext(source, context) };
}
test('OAuth start uses MSAL redirect and delegated contacts scope without a secret', async () => {
  const env = setup({ start: true }); await env.run();
  assert.equal(env.stored.get('berliCRM.contacts.authChannel'), 'office');
  assert.deepEqual(Array.from(env.events.find(e => e[0] === 'login')[1].scopes), ['https://graph.microsoft.com/Contacts.ReadWrite']);
  assert.doesNotMatch(JSON.stringify(env.events), /clientSecret/);
});
test('redirect clears token cache before delivering to exact Office parent origin even with host null', async () => {
  const env = setup({ savedChannel: 'office' }); await env.run();
  assert.deepEqual(env.events.map(e => e[0]), ['config', 'clear', 'send']);
  const sent = env.events[2]; assert.equal(sent[1].token, 'token'); assert.equal(sent[2].targetOrigin, 'https://crm.example.test');
  assert.equal(env.stored.size, 0);
});
test('browser popup callback is addressed only to same-origin opener', async () => {
  const env = setup({ savedChannel: 'popup' }); await env.run();
  assert.equal(env.events.at(-1)[0], 'popup'); assert.equal(env.events.at(-1)[2], 'https://crm.example.test');
});
test('mismatched redirect configuration cannot start Microsoft authentication or send a token', async () => {
  const env = setup({ start: true, badRedirect: 'https://evil.example/contacts-auth.html' }); await env.run();
  assert.equal(env.events.some(e => e[0] === 'config' || e[0] === 'login'), false);
  assert.equal(env.events.at(-1)[1].type, 'berliCRM.contacts.authError');
});

test('access denial shows a readable explanation with technical details as text and preserves the parent error notification', async () => {
  const env = setup({ start: true, error: new Error('access_denied: <img src=x onerror=alert(1)>') });
  await env.run();
  assert.equal(env.element('authCard').dataset.state, 'error');
  assert.equal(env.element('authTitle').textContent, 'Zugriff nicht freigegeben');
  assert.match(env.status.textContent, /Zustimmung fehlt/);
  assert.doesNotMatch(env.status.textContent, /<img|msal/);
  assert.equal(env.element('authErrorDetails').textContent, 'access_denied: <img src=x onerror=alert(1)>');
  assert.equal(env.element('authHelp').hidden, false);
  assert.equal(env.element('authDetails').hidden, false);
  assert.equal(env.element('authActions').hidden, false);
  assert.equal(env.events.at(-1)[1].type, 'berliCRM.contacts.authError');
});

test('retry starts a fresh local flow retaining only its channel and Office host information', async () => {
  const env = setup({ savedChannel: 'office', error: new Error('access_denied') });
  env.location.search = '?code=old-code&state=old-state&_host_Info=outlook%24web';
  await env.run();
  env.element('authRetry').events.click();
  const restart = new URL(env.events.at(-1)[1]);
  assert.equal(restart.origin + restart.pathname, 'https://crm.example.test/outlook-addin/contacts-auth.html');
  assert.deepEqual([...restart.searchParams], [['start', '1'], ['channel', 'office'], ['_host_Info', 'outlook$web']]);
  assert.equal(restart.hash, '');
  assert.equal(env.element('authRetry').disabled, true);
});

test('successful connection shows confirmation without error details or retry', async () => {
  const env = setup({ savedChannel: 'office' }); await env.run();
  assert.equal(env.element('authCard').dataset.state, 'success');
  assert.equal(env.element('authTitle').textContent, 'Microsoft-Konto verbunden');
  assert.equal(env.element('authDetails').hidden, true);
  assert.equal(env.element('authHelp').hidden, true);
  assert.equal(env.element('authActions').hidden, true);
});

test('silent SSO callback iframe leaves the response hash and authentication storage untouched', async () => {
  const env = setup({ iframe: true, savedChannel: 'office' });
  const hash = env.location.hash;
  await env.run();
  assert.deepEqual(env.events, []);
  assert.equal(env.location.hash, hash);
  assert.equal(env.stored.get('berliCRM.contacts.authChannel'), 'office');
});
