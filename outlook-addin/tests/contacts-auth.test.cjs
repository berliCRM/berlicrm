const { localizedContext } = require('./language-fixture.cjs');
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const relayTools = require('../contacts-auth-relay.js');
const source = fs.readFileSync(path.join(__dirname, '../contacts-auth-entry.js'), 'utf8').replace(/^import .*;\r?\n/gm, '');
function setup(options = {}) {
  const events = [], stored = new Map(options.savedChannel ? [['berliCRM.contacts.authChannel', options.savedChannel]] : []);
  if (options.relay) stored.set('berliCRM.contacts.authRelay', JSON.stringify(options.relay));
  const timers = new Map(); let timerId = 0;
  const origin = 'https://crm.example.test';
  const location = new URL(origin + '/outlook-addin/contacts-auth.html' + (options.start ? '?start=1&channel=' + (options.startChannel || 'office') : '#code=example'));
  if (options.mode) location.searchParams.set('mode', options.mode);
  if (options.hint) location.searchParams.set('login_hint', options.hint);
  const config = { configured: true, clientId: 'client', tenantId: 'tenant', redirectUri: options.badRedirect || origin + '/outlook-addin/contacts-auth.html' };
  class PublicClientApplication {
    constructor(config) { events.push(['config', config]); }
    async initialize() { if (options.initialize) return options.initialize(); }
    async handleRedirectPromise() {
      if (options.redirect) return options.redirect();
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
  const scripts = [];
  const context = localizedContext({ PublicClientApplication, URL, URLSearchParams, location,
    setTimeout: options.fakeTimers ? callback => { timers.set(++timerId, callback); return timerId; } : setTimeout,
    clearTimeout: options.fakeTimers ? id => timers.delete(id) : clearTimeout,
    broadcastResponseToMainFrame: async () => { events.push(['bridge']); },
    sessionStorage: { getItem: key => stored.get(key), setItem: (key, value) => stored.set(key, value), removeItem: key => stored.delete(key) },
    document: { getElementById: element, createElement: () => ({}), head: { appendChild: script => scripts.push(script) } },
    ...(options.relay ? { BerliAuthRelay: relayTools } : {}),
    fetch: async (url, init) => {
      assert.equal(String(url), origin + '/outlook-addin/rest/outlook.php');
      const input = JSON.parse(init.body);
      if (input.action === 'completeContactsAuthRelay') {
        events.push(['relay', input]);
        return { ok: true, json: async () => ({ success: true, payload: { accepted: true } }) };
      }
      if (options.fetch) return options.fetch();
      return { ok: true, json: async () => ({ success: true, payload: config }) };
    },
    ...(options.omitOffice ? {} : { Office: { onReady: options.officeReady || (async () => ({ host: null })), context: { ui: { messageParent: (message, settings) => events.push(['send', JSON.parse(message), settings]) } } } }),
    window: { opener: options.noOpener ? null : { postMessage: (message, targetOrigin) => events.push(['popup', message, targetOrigin]) }, close() { this.closed = true; } },
  });
  if (options.iframe) context.window.parent = {};
  if (options.silentFrame) context.window.frameElement = { classList: { contains: name => name === 'msalSilentIframe' } };
  return { events, stored, status, element, location, timers, scripts, context, run: () => vm.runInContext(source, context) };
}
test('OAuth start uses MSAL redirect and delegated contacts scope without a secret', async () => {
  const env = setup({ start: true }); await env.run();
  assert.equal(env.stored.get('berliCRM.contacts.authChannel'), 'office');
  assert.deepEqual(Array.from(env.events.find(e => e[0] === 'login')[1].scopes), ['https://graph.microsoft.com/Contacts.ReadWrite']);
  assert.doesNotMatch(JSON.stringify(env.events), /clientSecret/);
});

test('automatic popup SSO requests no prompt and passes the optional Microsoft account hint', async () => {
  const env = setup({ start: true, startChannel: 'popup', mode: 'silent', hint: 'owner@example.test', omitOffice: true });
  await env.run();
  const request = env.events.find(event => event[0] === 'login')[1];
  assert.equal(request.prompt, 'none');
  assert.equal(request.loginHint, 'owner@example.test');
  assert.equal(env.scripts.length, 0);
});

test('manual reconnection lets Microsoft reuse its session without forcing account selection', async () => {
  const env = setup({ start: true, startChannel: 'popup', mode: 'reuse', hint: 'owner@example.test', omitOffice: true });
  await env.run();
  const request = env.events.find(event => event[0] === 'login')[1];
  assert.equal(Object.hasOwn(request, 'prompt'), false);
  assert.equal(request.loginHint, 'owner@example.test');
});

test('explicit account switching keeps account selection and does not bias it with the old hint', async () => {
  const env = setup({ start: true, mode: 'select', hint: 'old@example.test' }); await env.run();
  const request = env.events.find(event => event[0] === 'login')[1];
  assert.equal(request.prompt, 'select_account');
  assert.equal(Object.hasOwn(request, 'loginHint'), false);
});

test('a rejected no-prompt popup returns its error without restarting authentication in a loop', async () => {
  const env = setup({ savedChannel: 'popup', error: new Error('login_required: AADSTS50058') }); await env.run();
  assert.equal(env.events.some(event => event[0] === 'login'), false);
  assert.equal(env.events.at(-1)[1].type, 'berliCRM.contacts.authError');
});

test('a rejected automatic popup switches once to normal sign-in in the same window', async () => {
  const env = setup({ savedChannel: 'popup', error: Object.assign(new Error('AADSTS50058'), { errorCode: 'login_required' }) });
  env.stored.set('berliCRM.contacts.authMode', 'silent');
  env.stored.set('berliCRM.contacts.authHint', 'owner@example.test');
  await env.run();
  const request = env.events.find(event => event[0] === 'login')[1];
  assert.equal(Object.hasOwn(request, 'prompt'), false);
  assert.equal(request.loginHint, 'owner@example.test');
  assert.equal(env.stored.get('berliCRM.contacts.authMode'), 'interactive');
  assert.equal(env.events.some(event => event[0] === 'popup'), false);
  env.events.length = 0;
  await vm.runInContext('main()', env.context);
  assert.equal(env.events.some(event => event[0] === 'login'), false, 'A second failure must not restart sign-in');
  assert.equal(env.events.at(-1)[1].type, 'berliCRM.contacts.authError');
});

test('access denial in an automatic popup is not converted to another sign-in attempt', async () => {
  const env = setup({ savedChannel: 'popup', error: new Error('access_denied') });
  env.stored.set('berliCRM.contacts.authMode', 'silent');
  await env.run();
  assert.equal(env.events.some(event => event[0] === 'login'), false);
  assert.equal(env.events.at(-1)[1].type, 'berliCRM.contacts.authError');
});

test('a callback retains the CRM language for its error message and retry link', async () => {
  const env = setup({ savedChannel: 'office', error: new Error('access_denied') });
  env.stored.set('berliCRM.contacts.authLanguage', 'en_us');
  await env.run();
  assert.equal(env.element('authTitle').textContent, 'Access not granted');
  assert.match(env.status.textContent, /Microsoft did not grant access/);
  env.element('authRetry').events.click();
  const retry = new URL(env.events.find(event => event[0] === 'navigate')[1]);
  assert.equal(retry.searchParams.get('language'), 'en_us');
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

test('a callback without an opener returns only ciphertext through its saved one-time relay', async () => {
  const relay = { flowId: 'a'.repeat(64), writeKey: 'b'.repeat(64), key: await relayTools.createKey() };
  const env = setup({ relay, noOpener: true, omitOffice: true });
  await env.run();
  const input = env.events.find(event => event[0] === 'relay')[1];
  assert.equal(input.flowId, relay.flowId);
  assert.equal(input.writeKey, relay.writeKey);
  assert.doesNotMatch(JSON.stringify(input), /"token"|"key"|user@example/);
  assert.equal((await relayTools.decrypt(relay, input.payload)).token, 'token');
  assert.equal(env.context.window.closed, true);
  assert.equal(env.stored.has('berliCRM.contacts.authRelay'), false);
  assert.equal(env.scripts.length, 0);
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
  assert.deepEqual([...restart.searchParams], [['start', '1'], ['channel', 'office'], ['language', 'de_de'], ['_host_Info', 'outlook$web']]);
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

test('silent SSO callback invokes the MSAL 5 bridge without starting interactive login, even with a stale channel', async () => {
  const env = setup({ iframe: true, silentFrame: true, savedChannel: 'office' });
  const hash = env.location.hash;
  await env.run();
  assert.deepEqual(env.events, [['bridge']]);
  assert.equal(env.location.hash, hash);
  assert.equal(env.stored.get('berliCRM.contacts.authChannel'), 'office');
});

test('an interactive Office callback in a frame completes instead of remaining on the loading screen', async () => {
  const env = setup({ iframe: true, savedChannel: 'office' });
  await env.run();
  assert.deepEqual(env.events.map(event => event[0]), ['config', 'clear', 'send']);
  assert.equal(env.element('authCard').dataset.state, 'success');
});

test('an explicit Office start in a frame is not silently ignored', async () => {
  const env = setup({ iframe: true, start: true });
  await env.run();
  assert.equal(env.events.some(event => event[0] === 'login'), true);
});

test('a silent iframe without an interactive channel only invokes the redirect bridge', async () => {
  const env = setup({ iframe: true, silentFrame: true });
  await env.run();
  assert.deepEqual(env.events, [['bridge']]);
  assert.equal(env.stored.size, 0);
});

test('a framed callback with missing channel reports an error instead of silently skipping initialization', async () => {
  const env = setup({ iframe: true });
  await env.run();
  assert.equal(env.element('authCard').dataset.state, 'error');
  assert.match(env.element('authErrorDetails').textContent, /aus der berliCRM-Kontakteansicht starten/);
  assert.equal(env.events.some(event => event[0] === 'bridge'), false);
  assert.equal(env.events.some(event => event[1]?.type === 'berliCRM.contacts.connected'), false);
});

test('stalled configuration, MSAL initialization and callback processing report their stage and cannot deliver a late token', async () => {
  for (const [stage, option] of [['Konfiguration', 'fetch'], ['initialisiert', 'initialize'], ['Rückmeldung', 'redirect']]) {
    let finish;
    const pending = new Promise(resolve => { finish = resolve; });
    const env = setup({ savedChannel: 'popup', fakeTimers: true, [option]: () => pending });
    const running = env.run();
    await new Promise(resolve => setImmediate(resolve));
    assert.match(env.status.textContent, new RegExp(stage));
    [...env.timers.values()][0]();
    await running;
    assert.equal(env.element('authCard').dataset.state, 'error');
    assert.match(env.element('authErrorDetails').textContent, /Zeitüberschreitung/);
    assert.match(env.element('authErrorDetails').textContent, new RegExp(stage));
    assert.equal(env.events.at(-1)[1].type, 'berliCRM.contacts.authError');
    finish({ accessToken: 'late-token', account: { homeAccountId: 'old' }, expiresOn: new Date(Date.now() + 60000) });
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(env.events.some(event => event[1]?.type === 'berliCRM.contacts.connected'), false);
    assert.equal(env.timers.size, 0);
  }
});

test('a stalled Outlook handshake shows a bounded error and never reports a successful connection', async () => {
  const env = setup({ savedChannel: 'office', fakeTimers: true, officeReady: () => new Promise(() => {}) });
  const running = env.run();
  await new Promise(resolve => setImmediate(resolve));
  assert.match(env.status.textContent, /Outlook-Anmeldefenster/);
  [...env.timers.values()][0]();
  await new Promise(resolve => setImmediate(resolve));
  assert.equal(env.element('authCard').dataset.state, 'error');
  assert.match(env.element('authErrorDetails').textContent, /Outlook-Anmeldefenster.*Zeitüberschreitung/);
  assert.match(env.status.textContent, /konnte nicht hergestellt werden/);
  await running;
  assert.equal(env.events.some(event => event[0] === 'send'), false);
  assert.equal(env.timers.size, 0);
});

test('Office dialog readiness is awaited before any Microsoft configuration or login request', async () => {
  let ready;
  const env = setup({ start: true, officeReady: () => new Promise(resolve => { ready = resolve; }) });
  const running = env.run();
  await new Promise(resolve => setImmediate(resolve));
  assert.deepEqual(env.events, []);
  ready({ host: null });
  await running;
  assert.equal(env.events.some(event => event[0] === 'login'), true);
});

test('the dialog loads Office.js on demand and calls onReady immediately before authentication', async () => {
  const env = setup({ start: true, omitOffice: true });
  const running = env.run();
  await new Promise(resolve => setImmediate(resolve));
  assert.equal(env.scripts.length, 1);
  assert.equal(env.scripts[0].src, 'https://appsforoffice.microsoft.com/lib/1/hosted/office.js');
  assert.deepEqual(env.events, []);
  env.context.Office = { onReady: async () => { env.events.push(['ready']); return { host: null }; } };
  env.scripts[0].onload();
  await running;
  assert.deepEqual(env.events.map(event => event[0]), ['ready', 'config', 'login']);
});

test('a failed Office.js download gives a specific error without starting Microsoft sign-in', async () => {
  const env = setup({ start: true, omitOffice: true });
  const running = env.run();
  await new Promise(resolve => setImmediate(resolve));
  env.scripts[0].onerror();
  await running;
  assert.equal(env.element('authCard').dataset.state, 'error');
  assert.match(env.element('authErrorDetails').textContent, /Office-Bibliothek konnte nicht geladen werden/);
  assert.deepEqual(env.events, []);
  assert.equal(env.scripts.length, 1);
});

test('browser popup sign-in and callbacks do not initialize Office.js', async () => {
  for (const options of [{ start: true, startChannel: 'popup' }, { savedChannel: 'popup' }]) {
    const env = setup({ ...options, omitOffice: true });
    await env.run();
    assert.equal(env.scripts.length, 0);
    assert.equal(env.element('authCard').dataset.state === 'error', false);
  }
});

test('Office readiness is initialized once and reused when returning the token', async () => {
  let readyCalls = 0;
  const env = setup({ savedChannel: 'office', officeReady: async () => { readyCalls++; return { host: null }; } });
  await env.run();
  assert.equal(readyCalls, 1);
  assert.equal(env.events.at(-1)[1].type, 'berliCRM.contacts.connected');
});
