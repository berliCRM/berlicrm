const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { webcrypto } = require('node:crypto');
const source = fs.readFileSync(path.join(__dirname, '../settings-connection.js'), 'utf8');
function setup(options = {}) {
  const elements = new Map(), listeners = {}, requests = [], opened = [], timers = new Map(), channels = [];
  let now = Date.now();
  class TestChannel {
    constructor(name) { this.name = name; this.sent = []; this.closed = false; channels.push(this); }
    postMessage(message) { this.sent.push(message); }
    close() { this.closed = true; }
  }
  const element = id => {
    if (!elements.has(id)) elements.set(id, { dataset: {}, events: {}, textContent: '', disabled: false, hidden: true,
      addEventListener(name, callback) { this.events[name] = callback; },
      set innerHTML(value) { throw new Error('Unsafe HTML rendering'); } });
    return elements.get(id);
  };
  const root = element('outlookConnectionCheck');
  Object.assign(root.dataset, { authUrl: options.authUrl || 'https://crm.example.test/crm/outlook-addin/contacts-auth.html',
    signingIn: 'Signing in', reading: 'Checking read access', success: 'Read access succeeded; write access not tested',
    failed: 'Failed:', denied: 'Access denied', httpError: 'Request failed', badReply: 'Invalid reply', blocked: 'Popup blocked', closed: 'Closed', cancelled: 'Cancelled', timedOut: 'No Microsoft response' });
  const button = element('outlookCheckConnection'); button.disabled = !!options.disabled;
  const popup = { closed: false, close() { if (options.closeError) throw new Error('Cannot close'); this.closed = true; } };
  let timerId = 0;
  vm.runInNewContext(source, {
    URL, URLSearchParams, Date: { now: () => now }, AbortController,
    ...(options.broadcast ? { BroadcastChannel: TestChannel, crypto: webcrypto } : {}),
    location: new URL('https://crm.example.test/crm/index.php?module=OutlookAddin'),
    document: { getElementById: element },
    window: { addEventListener: (name, callback) => { listeners[name] = callback; }, open(url) { opened.push(url); popup.closed = false; return options.blocked ? null : popup; } },
    setInterval: callback => { timers.set(++timerId, callback); return timerId; }, clearInterval: id => timers.delete(id),
    fetch: async (url, init) => { requests.push({ url, init }); if (options.fetch) return options.fetch(url, init); return { ok: true, json: async () => ({ value: options.value || [] }) }; },
  });
  const message = (data, overrides = {}) => listeners.message({ origin: 'https://crm.example.test', source: popup, data, ...overrides });
  const success = () => message({ type: 'berliCRM.contacts.connected', token: 'memory-only-token', expiresAt: Date.now() + 60000 });
  return { root, element, popup, requests, opened, timers, channels, message, success,
    advance: milliseconds => { now += milliseconds; },
    broadcast: (result, overrides = {}) => {
      const id = new URLSearchParams(new URL(opened.at(-1)).hash.slice(1)).get('settingsChannel');
      return channels.at(-1).onmessage({ origin: 'https://crm.example.test', data: { type: 'berliCRM.settings.auth', id, result, ...overrides } });
    },
    start: () => button.events.click(), cancel: () => element('outlookCancelConnection').events.click() };
}
test('connection test uses the deployed auth page and only a minimal read-only Graph request', async () => {
  const env = setup({ value: [{ id: 'private-contact-id' }] }); env.start();
  const address = new URL(env.opened[0]);
  assert.equal(address.pathname, '/crm/outlook-addin/contacts-auth.html');
  assert.equal(address.searchParams.get('channel'), 'popup');
  assert.equal(address.searchParams.get('start'), '1');
  assert.equal(env.element('outlookCheckConnection').disabled, true);
  await env.success();
  assert.equal(env.requests.length, 1);
  const request = env.requests[0];
  assert.equal(request.url, 'https://graph.microsoft.com/v1.0/me/contacts?$top=1&$select=id');
  assert.equal(request.init.method, 'GET');
  assert.equal(request.init.credentials, 'omit');
  assert.equal(request.init.headers.Authorization, 'Bearer memory-only-token');
  assert.equal(env.element('outlookConnectionStatus').className, 'alert alert-success');
  assert.doesNotMatch(env.element('outlookConnectionStatus').textContent, /private-contact-id|memory-only-token/);
  assert.equal(env.element('outlookCheckConnection').disabled, false);
  assert.equal(env.popup.closed, true);
});
test('messages require both the exact callback origin and the active popup', async () => {
  const env = setup(); env.start();
  const data = { type: 'berliCRM.contacts.connected', token: 'token', expiresAt: Date.now() + 60000 };
  await env.message(data, { origin: 'https://evil.example' });
  await env.message(data, { source: {} });
  assert.equal(env.requests.length, 0);
  assert.equal(env.element('outlookCheckConnection').disabled, true);
  env.cancel();
  await env.message(data);
  assert.equal(env.requests.length, 0);
});
test('auth failure releases the button even when closing the popup throws', async () => {
  const env = setup({ closeError: true }); env.start();
  await env.message({ type: 'berliCRM.contacts.authError', message: 'access_denied: <script>bad()</script>' });
  assert.equal(env.element('outlookCheckConnection').disabled, false);
  assert.equal(env.requests.length, 0);
  assert.match(env.element('outlookConnectionStatus').textContent, /<script>bad\(\)<\/script>/);
  env.start(); assert.equal(env.opened.length, 2);
  env.cancel();
});
test('a closed or blocked sign-in window does not leave the test locked', () => {
  const blocked = setup({ blocked: true }); blocked.start();
  assert.equal(blocked.element('outlookCheckConnection').disabled, false);
  assert.match(blocked.element('outlookConnectionStatus').textContent, /Popup blocked/);
  const closed = setup(); closed.start(); closed.popup.closed = true;
  [...closed.timers.values()][0]();
  assert.equal(closed.element('outlookCheckConnection').disabled, false);
  assert.equal(closed.element('outlookConnectionStatus').textContent, 'Closed');
});
test('Graph failures, malformed responses and expired tokens cannot report a successful connection', async () => {
  for (const status of [401, 403, 500]) {
    const env = setup({ fetch: async () => ({ ok: false, status }) }); env.start(); await env.success();
    assert.equal(env.element('outlookConnectionStatus').className, 'alert alert-error');
    assert.match(env.element('outlookConnectionStatus').textContent, new RegExp('HTTP ' + status));
    assert.equal(env.element('outlookCheckConnection').disabled, false);
  }
  const malformed = setup({ fetch: async () => ({ ok: true, json: async () => ({ unexpected: 'reply' }) }) });
  malformed.start(); await malformed.success();
  assert.equal(malformed.element('outlookConnectionStatus').className, 'alert alert-error');
  const expired = setup(); expired.start();
  await expired.message({ type: 'berliCRM.contacts.connected', token: 'expired', expiresAt: 0 });
  assert.equal(expired.requests.length, 0);
  assert.equal(expired.element('outlookCheckConnection').disabled, false);
});
test('cancelling a pending read invalidates late results and permits a new test', async () => {
  let release;
  const pending = new Promise(resolve => { release = resolve; });
  const env = setup({ fetch: () => pending }); env.start();
  const first = env.success();
  env.cancel();
  assert.equal(env.requests[0].init.signal.aborted, true);
  env.start();
  release({ ok: true, json: async () => ({ value: [] }) }); await first;
  assert.equal(env.element('outlookConnectionStatus').textContent, 'Signing in');
  assert.equal(env.element('outlookCheckConnection').disabled, true);
  env.cancel();
});
test('unconfigured buttons and external callback URLs cannot initiate sign-in', () => {
  const disabled = setup({ disabled: true }); disabled.start(); assert.equal(disabled.opened.length, 0);
  const external = setup({ authUrl: 'https://evil.example/outlook-addin/contacts-auth.html' }); external.start();
  assert.equal(external.opened.length, 0);
  assert.equal(external.element('outlookCheckConnection').disabled, false);
});

test('a COOP-isolated settings popup returns through its unique channel without a false closed-window result', async () => {
  const env = setup({ broadcast: true }); env.start();
  const address = new URL(env.opened[0]);
  assert.equal(address.searchParams.get('source'), 'settings');
  assert.match(new URLSearchParams(address.hash.slice(1)).get('settingsChannel'), /^[a-f0-9]{64}$/);
  assert.equal(address.searchParams.has('settingsChannel'), false);
  env.popup.closed = true; // COOP severs the proxy while authentication continues.
  [...env.timers.values()][0]();
  assert.equal(env.element('outlookCheckConnection').disabled, true);
  assert.equal(env.element('outlookConnectionStatus').textContent, 'Signing in');
  await env.broadcast({ type: 'berliCRM.contacts.connected', token: 'memory-only-token', expiresAt: Date.now() + 60000 });
  assert.equal(env.requests.length, 1);
  assert.equal(env.requests[0].init.method, 'GET');
  assert.equal(env.element('outlookConnectionStatus').className, 'alert alert-success');
  assert.equal(env.channels[0].sent[0].type, 'berliCRM.settings.auth.received');
  assert.equal(env.channels[0].closed, true);
});

test('settings channels reject the wrong attempt and ignore replies after cancellation', async () => {
  const env = setup({ broadcast: true }); env.start();
  const result = { type: 'berliCRM.contacts.connected', token: 'temporary', expiresAt: Date.now() + 60000 };
  await env.broadcast(result, { id: 'wrong-attempt' });
  assert.equal(env.requests.length, 0);
  const oldChannel = env.channels[0];
  const oldReply = oldChannel.onmessage;
  env.cancel(); env.start();
  await oldReply({ origin: 'https://crm.example.test', data: { type: 'berliCRM.settings.auth',
    id: oldChannel.name.split('.').at(-1), result } });
  assert.equal(env.requests.length, 0);
  assert.equal(env.channels[1].closed, false);
  env.cancel();
});

test('a settings channel times out cleanly without relying on popup.closed', () => {
  const env = setup({ broadcast: true }); env.start();
  env.advance(300001); [...env.timers.values()][0]();
  assert.equal(env.element('outlookConnectionStatus').textContent, 'No Microsoft response');
  assert.equal(env.element('outlookCheckConnection').disabled, false);
  assert.equal(env.channels[0].closed, true);
});
