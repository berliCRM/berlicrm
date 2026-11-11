const { localizedContext } = require('./language-fixture.cjs');
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const model = require('../contact-model.js');
const graph = require('../contact-graph.js');
const relayTools = require('../contacts-auth-relay.js');
const tick = () => new Promise(resolve => setImmediate(resolve));
const sessionKey = 'berliCRM.outlook.session';
const metadata = { ownerId: '19x3', canCreate: true, propertyId: 'test-property', fields: [
  { name: 'lastname', label: 'Nachname', mandatory: true, editable: true },
] };
class Element {
  constructor() { this.value = ''; this.text = ''; this.children = []; this.events = {}; this.disabled = false; this.hidden = false; this.checked = false; this.classList = { add() {} }; }
  set textContent(text) { this.text = text; this.children = []; }
  get textContent() { return this.text + this.children.map(child => child.textContent).join(''); }
  set innerHTML(value) { throw new Error('Unsafe HTML rendering'); }
  append(...nodes) { this.children.push(...nodes); }
  replaceChildren(...nodes) { this.text = ''; this.children = nodes; }
  addEventListener(name, handler) { this.events[name] = handler; }
  querySelector(selector) { if (!this.button) this.button = new Element(); return this.button; }
}
function setup(options = {}) {
  const elements = new Map(), stored = options.stored || new Map(), sessionStored = options.sessionStored || new Map(), listeners = {}, documentEvents = {}, requests = [];
  const element = id => { if (!elements.has(id)) elements.set(id, new Element()); return elements.get(id); };
  element('contactWorkspace').hidden = true;
  if (options.saved) stored.set(sessionKey, JSON.stringify({ username: 'CRM User', sessionName: 'session', userId: '19x3', ...(options.savedLanguage ? { language: options.savedLanguage } : {}) }));
  const context = localizedContext({
    URL, URLSearchParams, Set, Date, clearInterval, setInterval, clearTimeout,
    setTimeout: (callback, milliseconds) => {
      const timer = (options.setTimeout || setTimeout)(callback, milliseconds);
      timer?.unref?.(); return timer;
    },
    location: new URL('https://crm.example.test/outlook-addin/contacts.html' + (options.preparedPopup ? '?preparedAuth=' + Date.now() + '&outlookPlatform=OfficeOnline' : '')),
    localStorage: { getItem: key => stored.get(key) || null, setItem: (key, value) => stored.set(key, value), removeItem: key => stored.delete(key) },
    sessionStorage: options.blockSessionStorage ? {
      getItem() { throw new Error('Storage blocked'); }, setItem() { throw new Error('Storage blocked'); }, removeItem() { throw new Error('Storage blocked'); },
    } : { getItem: key => sessionStored.get(key) || null, setItem: (key, value) => sessionStored.set(key, value), removeItem: key => sessionStored.delete(key) },
    window: { addEventListener: (name, handler) => { listeners[name] = handler; },
      ...(options.layout ? { BerliContactsLayout: options.layout } : {}),
      ...(options.preparedPopup ? { open: (address, name) => {
        assert.equal(address, ''); assert.equal(name, 'berliCRMContactsAuth'); return options.preparedPopup;
      } } : {}),
      ...(options.silent ? { BerliContactsAuth: { connectSilently: options.silent } } : {}) },
    ...(options.office ? { Office: options.office } : {}),
    document: { getElementById: element, createElement: () => new Element(),
      addEventListener: (name, handler) => { documentEvents[name] = handler; } },
    Option: function(label, value) { const option = new Element(); option.textContent = label; option.value = value; return option; },
    BerliContactModel: model, BerliContactGraph: graph, BerliAuthRelay: relayTools,
    fetch: async (url, init) => {
      assert.equal(String(url), 'https://crm.example.test/outlook-addin/rest/outlook.php');
      const input = JSON.parse(init.body); requests.push(input);
      let payload;
      if (options.api) payload = await options.api(input);
      if (payload === undefined) {
        if (input.action === 'login') payload = { sessionName: 'session', userId: '19x3' };
        else if (input.action === 'beginContactsAuthRelay') payload = { flowId: 'a'.repeat(64), writeKey: 'b'.repeat(64), expiresAt: Date.now() + 300000 };
        else if (input.action === 'pollContactsAuthRelay') payload = { state: 'pending', payload: null };
        else if (input.action === 'closeContactsAuthRelay') payload = { closed: true };
        else if (input.action === 'getContactsMicrosoftConfig') payload = { configured: true, clientId: 'client', tenantId: 'tenant', redirectUri: 'https://crm.example.test/outlook-addin/contacts-auth.html' };
        else if (input.action === 'searchContacts') payload = { contacts: [{ id: '4x1', firstname: '<img onerror=evil()>', lastname: 'Test', email: 'test@example.test' }], page: input.page || 1, pageSize: 50, hasMore: false, total: 1 };
        else if (input.transfer.mode === 'describe') payload = metadata;
        else if (input.transfer.mode === 'export') payload = { key: 'crm:4x1', contact: { surname: 'Test' } };
        else payload = { status: 'created', recordId: '4x2' };
      }
      return { ok: true, json: async () => ({ success: true, payload }) };
    },
  });
  if (options.preferences) {
    const localizedFetch = context.fetch;
    context.fetch = (url, init) => JSON.parse(init.body).action === 'getUserPreferences'
      ? options.preferences(JSON.parse(init.body)) : localizedFetch(url, init);
  }
  if (options.browserLanguage) context.navigator.language = options.browserLanguage;
  vm.runInContext(fs.readFileSync(path.join(__dirname, '../contacts.js'), 'utf8'), context);
  const state = () => vm.runInContext('contactState', context);
  function connect(client = {}) { state().graph = { account: 'account' }; state().client = client; context.contactControls(); }
  return { context, element, stored, sessionStored, listeners, documentEvents, requests, state, connect };
}

test('signed-out contacts view offers CRM login, clears Access Key and reuses validated saved sessions', async () => {
  const env = setup(); assert.equal(env.element('contactWorkspace').hidden, true);
  env.element('contactUsername').value = 'CRM User'; env.element('contactAccessKey').value = 'not-stored';
  await env.context.contactLogin({ preventDefault() {}, currentTarget: env.element('contactLoginForm') });
  assert.equal(env.element('contactLoginForm').hidden, true); assert.equal(env.element('contactWorkspace').hidden, false);
  assert.match(env.element('contactLoginStatus').textContent, /CRM User/);
  assert.equal(env.element('contactAccessKey').value, ''); assert.doesNotMatch([...env.stored.values()].join(''), /not-stored/);
  const restored = setup({ saved: true }); await tick();
  assert.equal(restored.element('contactLoginForm').hidden, true);
  assert.equal(restored.requests[0].transfer.mode, 'describe');
});

test('a saved CRM session hides login while preferences are pending and uses its language hint', async () => {
  let complete;
  const pending = new Promise(resolve => { complete = resolve; });
  const env = setup({ saved: true, savedLanguage: 'de_de', browserLanguage: 'en-US', preferences: () => pending });
  assert.equal(env.element('contactLoginForm').hidden, true);
  assert.equal(env.context.BerliI18n.language, 'de_de');
  assert.equal(env.element('contactLoginStatus').textContent, 'CRM-Anmeldung wird geprüft …');
  complete({ ok: true, json: async () => ({ success: true, payload: { language: 'de_de' } }) });
  await tick();
  assert.equal(env.element('contactWorkspace').hidden, false);
});

test('a failed preference request retains the saved session and offers retry instead of another login', async () => {
  let attempts = 0;
  const env = setup({ saved: true, savedLanguage: 'de_de', preferences: async () => ({
    ok: true, json: async () => ++attempts === 1 ? { success: false, payload: 'Unbekannte Aktion: getUserPreferences' }
      : { success: true, payload: { language: 'de_de' } },
  }) });
  await tick();
  assert.equal(env.state().session.sessionName, 'session');
  assert.equal(env.element('contactLoginForm').hidden, true);
  assert.equal(env.element('contactWorkspace').hidden, true);
  assert.equal(env.element('contactRetry').hidden, false);
  assert.match(env.element('contactLoginStatus').textContent, /Kontakte konnten nicht vorbereitet werden/);
  await env.element('contactRetry').events.click(); await tick();
  assert.equal(attempts, 2);
  assert.equal(env.element('contactRetry').hidden, true);
  assert.equal(env.element('contactWorkspace').hidden, false);
  assert.equal(env.stored.has(sessionKey), true);
});

test('a successful preference response overrides the saved language hint', async () => {
  const env = setup({ saved: true, savedLanguage: 'de_de', preferences: async () => ({
    ok: true, json: async () => ({ success: true, payload: { language: 'en_us' } }),
  }) });
  await tick();
  assert.equal(env.context.BerliI18n.language, 'en_us');
  assert.equal(env.element('contactLoginStatus').textContent, 'Signed in as CRM User.');
});

test('outlook paging retains selection, disables incomplete contacts and renders names as text', async () => {
  const env = setup({ saved: true }); await tick(); let page = 0;
  env.connect({ listPage: async () => ++page === 1 ? { value: [{ id: 'one', surname: '<script>text</script>' }, { id: 'bad', givenName: 'No surname' }], '@odata.nextLink': 'next' }
    : { value: [{ id: 'one', surname: 'duplicate' }, { id: 'two', surname: 'Two' }] } });
  await env.context.contactLoadOutlook();
  assert.equal(env.state().outlookRows[1].checkbox.disabled, true);
  assert.match(env.element('contactOutlookResults').textContent, /<script>text<\/script>/);
  env.state().outlookRows[0].checkbox.checked = true;
  await env.context.contactLoadOutlook(true);
  assert.equal(env.state().outlookRows.length, 3); assert.equal(env.state().outlookRows[0].checkbox.checked, true);
  assert.equal(env.element('contactImport').disabled, false);
  assert.match(env.state().outlookRows[1].status.textContent, /Nachname/);
});

test('import refreshes selected contact and never submits unselected or incomplete rows', async () => {
  const env = setup({ saved: true }); await tick(); const refreshed = [];
  env.connect({ listPage: async () => ({ value: [{ id: 'one', surname: 'One' }, { id: 'two', surname: 'Two' }, { id: 'bad' }] }),
    getContact: async (id, folder) => { refreshed.push(id); return { id, surname: 'Fresh surname' }; } });
  await env.context.contactLoadOutlook(); env.state().outlookRows[0].checkbox.checked = true;
  await env.context.contactTransfer('import');
  const imports = env.requests.filter(req => req.transfer?.mode === 'import');
  assert.equal(imports.length, 1); assert.equal(imports[0].transfer.contact.surname, 'Fresh surname');
  assert.deepEqual(refreshed, ['one']); assert.equal(imports[0].transfer.account, 'account');
  assert.equal(env.state().outlookRows[0].done, true); assert.equal(env.state().outlookRows[2].invalid, true);
});

test('concurrent clicks cannot start two writes; failed lookup cannot cause a create', async () => {
  const env = setup({ saved: true }); await tick(); let release, creates = 0;
  const promise = new Promise(resolve => { release = resolve; });
  env.connect({ existing: () => promise, create: async () => { creates++; return { id: 'new' }; } });
  await env.context.contactSearch({ preventDefault() {} }); // Empty query intentionally does nothing.
  env.element('contactSearch').value = 'Test'; await env.context.contactSearch({ preventDefault() {} });
  env.state().crmRows[0].checkbox.checked = true;
  const first = env.context.contactTransfer('export'); await tick();
  await env.context.contactTransfer('export'); assert.equal(env.element('contactExport').disabled, true);
  release(null); await first; assert.equal(creates, 1);
  env.state().crmRows[0].done = false; env.state().crmRows[0].checkbox.checked = true;
  env.state().client.existing = async () => { throw new graph.GraphError('lookup failed', 403); };
  await env.context.contactTransfer('export'); assert.equal(creates, 1);
  assert.match(env.state().crmRows[0].status.textContent, /lookup failed/);
});

test('unconfirmed Outlook create is not repeated; confirmed duplicate resolves pending state', async () => {
  const env = setup({ saved: true }); await tick(); let creates = 0, found = null;
  env.connect({ existing: async () => found, create: async () => { creates++; throw new Error('Network interrupted'); } });
  env.element('contactSearch').value = 'Test'; await env.context.contactSearch({ preventDefault() {} });
  env.state().crmRows[0].checkbox.checked = true;
  await env.context.contactTransfer('export'); await env.context.contactTransfer('export');
  assert.equal(creates, 1); assert.match(env.state().crmRows[0].status.textContent, /nicht eindeutig/);
  found = { id: 'created-on-server' }; await env.context.contactTransfer('export');
  assert.equal(creates, 1); assert.equal(env.state().crmRows[0].done, true);
  assert.equal([...env.stored.keys()].some(key => key.includes('.pending.')), false);
});

test('changing CRM login elsewhere invalidates this view without deleting the new session', async () => {
  const env = setup({ saved: true }); await tick();
  env.stored.set(sessionKey, 'another-session'); env.listeners.storage({ key: sessionKey });
  assert.equal(env.state().session, null); assert.equal(env.element('contactLoginForm').hidden, false);
  assert.equal(env.stored.get(sessionKey), 'another-session');
});

test('account switch discards previous folder, selections and loaded Outlook data', async () => {
  const env = setup({ saved: true }); await tick();
  env.connect({ listPage: async () => ({ value: [{ id: 'old', surname: 'Old account' }] }) });
  await env.context.contactLoadOutlook(); env.element('contactFolder').value = 'old-folder';
  env.state().authPending = true;
  env.context.contactAcceptAuth({ type: 'berliCRM.contacts.connected', account: 'new', token: 'fake', expiresAt: Date.now() + 60000, username: 'new@example.test' }, env.state().generation);
  assert.equal(env.state().outlookRows.length, 0); assert.equal(env.state().graph.account, 'new');
  assert.equal(env.element('contactFolder').children[0].value, '');
});

test('expired saved CRM session restores the login form and removes only its invalid session', async () => {
  const env = setup({ saved: true, api: async request => { if (request.transfer?.mode === 'describe') throw new Error('INVALID_SESSIONID'); } });
  await tick();
  assert.equal(env.state().session, null); assert.equal(env.stored.has(sessionKey), false);
  assert.equal(env.element('contactLoginForm').hidden, false); assert.equal(env.element('contactWorkspace').hidden, true);
  assert.match(env.element('contactLoginStatus').textContent, /abgelaufen/);
});

test('authentication messages require both the expected popup and its exact origin', async () => {
  const env = setup({ saved: true }); await tick();
  const popup = { closed: false, close() { this.closed = true; } };
  env.context.window.open = () => popup;
  await env.context.contactConnect();
  const data = { type: 'berliCRM.contacts.connected', account: 'new', token: 'fake', expiresAt: Date.now() + 60000, username: 'new@example.test' };
  env.listeners.message({ origin: 'https://evil.example', source: popup, data });
  env.listeners.message({ origin: 'https://crm.example.test', source: {}, data });
  assert.equal(env.state().graph, null);
  env.listeners.message({ origin: 'https://crm.example.test', source: popup, data });
  assert.equal(env.state().graph.account, 'new'); assert.equal(popup.closed, true);
});

test('automatic connection hides the login menu and reuses the Microsoft account without opening a window', async () => {
  const env = setup({ saved: true, silent: async () => ({ type: 'berliCRM.contacts.connected', account: 'automatic', token: 'memory-only-token', expiresAt: Date.now() + 60000, username: 'user@example.test' }) });
  await tick();
  assert.equal(env.state().graph.account, 'automatic');
  assert.equal(env.element('contactMicrosoftLogin').hidden, true);
  assert.equal(env.element('contactDisconnect').hidden, false);
  assert.equal(env.element('contactLoadOutlook').disabled, false);
  assert.equal(env.state().authPending, false);
  assert.match(env.element('contactMicrosoftStatus').textContent, /user@example.test/);
  assert.doesNotMatch([...env.stored.values()].join(''), /memory-only-token/);
});

test('Outlook on the web opens Microsoft sign-in through the popup channel without an Office dialog', async () => {
  let dialogs = 0;
  const env = setup({ saved: true, office: {
    onReady: async () => ({ host: 'Outlook', platform: 'OfficeOnline' }),
    context: { platform: 'OfficeOnline', ui: { displayDialogAsync() { dialogs++; } } },
  } });
  await tick();
  const addresses = [];
  const popup = { closed: false, close() { this.closed = true; }, location: { replace(address) { addresses.push(new URL(address)); } } };
  env.context.window.open = address => { assert.equal(address, 'about:blank'); return popup; };
  try {
    await env.context.contactConnect();
    assert.equal(dialogs, 0);
    assert.equal(addresses.length, 1);
    assert.equal(addresses[0].searchParams.get('channel'), 'popup');
    assert.equal(addresses[0].searchParams.get('language'), 'de_de');
    assert.equal(addresses[0].searchParams.has('sessionName'), false);
    env.listeners.message({ origin: 'https://crm.example.test', source: popup, data: {
      type: 'berliCRM.contacts.connected', account: 'web-account', token: 'memory-only',
      expiresAt: Date.now() + 60000, username: 'web@example.test',
    } });
    assert.equal(env.state().graph.account, 'web-account');
    assert.equal(popup.closed, true);
  } finally { env.context.contactReset(); }
});

test('the browser popup opens synchronously on click even when Office readiness is stalled', async () => {
  const env = setup({ office: { onReady: () => new Promise(() => {}), context: { platform: 'OfficeOnline' } } });
  env.state().config = { configured: true };
  env.state().session = { sessionName: 'session' };
  let opened = 0;
  env.context.window.open = () => { opened++; return { closed: false, close() {}, location: { replace() {} } }; };
  const connecting = env.context.contactConnect();
  try {
    assert.equal(opened, 1, 'Open within the click handler, before awaiting any promise');
    await connecting;
  } finally { env.context.contactReset(); }
});

test('a blocked Outlook browser popup reports the blocker and leaves the connect button enabled', async () => {
  const env = setup({ saved: true, office: {
    onReady: async () => ({ host: 'Outlook', platform: 'OfficeOnline' }), context: { platform: 'OfficeOnline' },
  } });
  await tick();
  env.context.window.open = () => null;
  await env.context.contactConnect();
  assert.equal(env.state().authPending, false);
  assert.equal(env.element('contactConnect').disabled, false);
  assert.match(env.element('contactMicrosoftStatus').textContent, /Anmeldefenster.*Browser/);
});

test('a COOP-isolated Microsoft popup delivers its encrypted response without window.opener', async () => {
  let pollReply = { state: 'pending', payload: null };
  const timers = [];
  const env = setup({ saved: true, setTimeout: callback => { timers.push(callback); return timers.length; },
    office: { onReady: async () => ({ host: 'Outlook', platform: 'OfficeOnline' }), context: { platform: 'OfficeOnline' } },
    api: async input => input.action === 'pollContactsAuthRelay' ? pollReply : undefined,
  });
  await tick();
  const popup = { closed: false, close() {}, location: { replace() {} } };
  env.context.window.open = () => popup;
  try {
    await env.context.contactConnect(); await tick();
    const relay = vm.runInContext('contactRelay', env.context);
    popup.closed = true; // Browser severed the proxy; Microsoft may still be open.
    pollReply = { state: 'ready', payload: await relayTools.encrypt(relay, {
      type: 'berliCRM.contacts.connected', account: 'isolated', token: 'encrypted-token',
      expiresAt: Date.now() + 60000, username: 'user@example.test',
    }) };
    await timers.at(-1)();
    assert.equal(env.state().graph.account, 'isolated');
    assert.equal(env.state().authPending, false);
    assert.equal(env.requests.some(input => input.action === 'closeContactsAuthRelay'), true);
    assert.doesNotMatch(JSON.stringify(env.requests), /encrypted-token/);
  } finally { env.context.contactReset(); }
});

test('classic Outlook continues to authenticate through its Office dialog', async () => {
  const dialogEvents = {}, addresses = [];
  const dialog = { addEventHandler(name, handler) { dialogEvents[name] = handler; }, close() {} };
  const env = setup({ saved: true, office: {
    onReady: async () => ({ host: 'Outlook', platform: 'PC' }),
    AsyncResultStatus: { Succeeded: 'ok' }, EventType: { DialogMessageReceived: 'message', DialogEventReceived: 'close' },
    context: { platform: 'PC', ui: { displayDialogAsync(address, options, callback) {
      addresses.push(new URL(address)); assert.equal(options.displayInIframe, false);
      callback({ status: 'ok', value: dialog });
    } } },
  } });
  await tick();
  env.context.window.open = () => { throw new Error('Must use the Office dialog in classic Outlook'); };
  await env.context.contactConnect();
  assert.equal(addresses[0].searchParams.get('channel'), 'office');
  dialogEvents.message({ origin: 'https://crm.example.test', message: JSON.stringify({
    type: 'berliCRM.contacts.connected', account: 'desktop-account', token: 'memory-only',
    expiresAt: Date.now() + 60000, username: 'desktop@example.test',
  }) });
  assert.equal(env.state().graph.account, 'desktop-account');
});

test('manual login stays hidden during silent SSO and appears on failure, with its button enabled', async () => {
  let reject;
  const pending = new Promise((resolve, fail) => { reject = fail; });
  const env = setup({ saved: true, silent: () => pending });
  await tick();
  assert.equal(env.element('contactMicrosoftLogin').hidden, true);
  assert.equal(env.state().authPending, true);
  assert.match(env.element('contactMicrosoftStatus').textContent, /automatisch/);
  reject(new Error('interaction_required')); await tick();
  assert.equal(env.element('contactMicrosoftLogin').hidden, false);
  assert.equal(env.element('contactConnect').disabled, false);
  assert.equal(env.state().authPending, false);
  assert.match(env.element('contactMicrosoftStatus').textContent, /Mit Microsoft verbinden/);
  const popup = { closed: false, close() { this.closed = true; } };
  env.context.window.open = () => popup;
  await env.context.contactConnect();
  env.listeners.message({ origin: 'https://crm.example.test', source: popup, data: { type: 'berliCRM.contacts.connected', account: 'manual', token: 'token', expiresAt: Date.now() + 60000, username: 'manual@example.test' } });
  assert.equal(env.state().graph.account, 'manual');
  assert.equal(env.element('contactMicrosoftLogin').hidden, true);
});

test('late automatic responses cannot reconnect after changing the CRM login', async () => {
  let finish;
  const pending = new Promise(resolve => { finish = resolve; });
  const env = setup({ saved: true, silent: () => pending }); await tick();
  env.context.contactReset();
  finish({ type: 'berliCRM.contacts.connected', account: 'old', token: 'old', expiresAt: Date.now() + 60000 });
  await tick();
  assert.equal(env.state().graph, null);
  assert.equal(env.state().session, null);
  assert.equal(env.state().authPending, false);
  assert.equal(env.element('contactWorkspace').hidden, true);
});

test('automatic SSO uses the Outlook mailbox address only as a hint and disconnect reveals manual login', async () => {
  let hint;
  const env = setup({ saved: true,
    office: { onReady: async () => ({ host: 'Outlook' }), context: { mailbox: { userProfile: { emailAddress: 'owner@example.test' } } } },
    silent: async (config, value) => { hint = value; return { type: 'berliCRM.contacts.connected', account: 'owner', token: 'token', expiresAt: Date.now() + 60000, username: 'owner@example.test' }; } });
  await tick();
  assert.equal(hint, 'owner@example.test');
  env.context.contactDisconnect();
  assert.equal(env.element('contactMicrosoftLogin').hidden, false);
  assert.equal(env.element('contactConnect').disabled, false);
  assert.equal(env.state().graph, null);
});

test('automatic SSO starts without Office readiness or NAA capability checks', async () => {
  let called = false;
  const env = setup({ saved: true, office: {
    onReady: () => new Promise(() => {}),
    context: { mailbox: { userProfile: { emailAddress: 'owner@example.test' } }, requirements: {
      isSetSupported() { throw new Error('NAA must not be queried'); },
    } },
  }, silent: async (config, hint, ...methods) => {
    called = true;
    assert.equal(methods.length, 0);
    assert.equal(hint, 'owner@example.test');
    return { type: 'berliCRM.contacts.connected', account: 'owner', token: 'memory-only', expiresAt: Date.now() + 60000, username: 'owner@example.test' };
  } });
  await tick();
  assert.equal(called, true);
  assert.equal(env.state().graph.account, 'owner');
  assert.equal(env.element('contactAutoDetails').hidden, true);
});

test('silent errors retain the manual connection and show the actual failure without NAA setup', async () => {
  const env = setup({ saved: true, silent: async () => { throw new Error('login_required: Microsoft session missing'); } });
  await tick();
  assert.equal(env.element('contactConnect').disabled, false);
  assert.equal(env.element('contactMicrosoftLogin').hidden, false);
  assert.equal(env.element('contactAutoDetails').hidden, false);
  assert.match(env.element('contactAutoError').textContent, /login_required/);
  assert.doesNotMatch(env.element('contactAutoError').textContent, /NAA|brk-multihub|Entra/);
});

test('silent iframe failures expose cookie-related diagnostics', async () => {
  const env = setup({ saved: true, silent: async () => { throw new Error('iframe blocked'); } });
  await tick();
  assert.match(env.element('contactAutoError').textContent, /iframe blocked/);
  assert.match(env.element('contactAutoError').textContent, /Drittanbieter-Cookies/);
  assert.equal(env.element('contactConnect').disabled, false);
});

test('automatic failures expose Microsoft codes, suberrors and string failures without requiring an Error object', async () => {
  for (const [failure, expected] of [
    [{ errorCode: 'monitor_window_timeout', subError: 'iframe_timeout' }, /monitor_window_timeout\niframe_timeout/],
    ['Browser storage unavailable', /Browser storage unavailable/],
    [null, /Unbekannter Anmeldefehler/],
  ]) {
    const env = setup({ saved: true, silent: async () => { throw failure; } }); await tick();
    assert.equal(env.element('contactAutoDetails').hidden, false);
    assert.match(env.element('contactAutoError').textContent, expected);
    assert.equal(env.element('contactConnect').disabled, false);
    assert.equal(env.state().authPending, false);
  }
});

test('Outlook web tries one automatic encrypted popup after AADSTS50058 and accepts its completion', async () => {
  const env = setup({ saved: true, office: {
    onReady: async () => ({ host: 'Outlook', platform: 'OfficeOnline' }),
    context: { platform: 'OfficeOnline', mailbox: { userProfile: { emailAddress: 'owner@example.test' } } },
  }, silent: async () => { throw new Error('login_required: AADSTS50058'); } });
  const addresses = [];
  let opened = 0;
  const popup = { closed: false, close() { this.closed = true; }, location: { replace: address => addresses.push(new URL(address)) } };
  env.context.window.open = address => { opened++; assert.equal(address, 'about:blank'); return popup; };
  try {
    await tick();
    assert.equal(opened, 1);
    assert.equal(addresses[0].searchParams.get('mode'), 'silent');
    assert.equal(addresses[0].searchParams.get('login_hint'), 'owner@example.test');
    assert.equal(addresses[0].searchParams.has('sessionName'), false);
    assert.equal(addresses[0].hash.includes('relayKey='), true);
    env.listeners.message({ origin: 'https://crm.example.test', source: popup, data: {
      type: 'berliCRM.contacts.connected', account: 'reused', token: 'short-lived',
      expiresAt: Date.now() + 60000, username: 'owner@example.test',
    } });
    assert.equal(env.state().graph.account, 'reused');
    assert.equal(env.state().authPending, false);
  } finally { env.context.contactReset(); }
});

test('a browser-blocked automatic popup leaves the manual connection available', async () => {
  const env = setup({ saved: true, office: {
    onReady: async () => ({ host: 'Outlook', platform: 'OfficeOnline' }), context: { platform: 'OfficeOnline' },
  }, silent: async () => { throw { errorCode: 'login_required', message: 'AADSTS50058' }; } });
  let opened = 0;
  env.context.window.open = () => { opened++; return null; };
  await tick();
  assert.equal(opened, 1);
  assert.equal(env.element('contactConnect').disabled, false);
  assert.equal(env.element('contactMicrosoftLogin').hidden, false);
  assert.equal(env.state().authPending, false);
  assert.match(env.element('contactMicrosoftStatus').textContent, /Anmeldefenster.*Browser/);
});

test('a click-prepared window is reused even while Office readiness and browser detection are stalled', async () => {
  const addresses = [];
  const popup = { closed: false, close() { this.closed = true; }, location: { replace: address => addresses.push(new URL(address)) } };
  const env = setup({ saved: true, preparedPopup: popup,
    office: { onReady: () => new Promise(() => {}), context: {} },
    silent: async () => { throw new Error('login_required: AADSTS50058'); },
  });
  env.context.window.open = () => { throw new Error('Must reuse the window reserved by the user click'); };
  try {
    await tick();
    assert.equal(addresses.length, 1);
    assert.equal(addresses[0].searchParams.get('mode'), 'silent');
    assert.equal(env.state().authPending, true);
  } finally { env.context.contactReset(); }
});

test('a prepared window is closed when silent SSO succeeds or CRM validation fails', async () => {
  for (const invalidSession of [false, true]) {
    const popup = { closed: false, close() { this.closed = true; } };
    const env = setup({ saved: true, preparedPopup: popup,
      silent: async () => ({ type: 'berliCRM.contacts.connected', account: 'account', token: 'token',
        username: 'user@example.test', expiresAt: Date.now() + 60000 }),
      ...(invalidSession ? { preferences: async () => ({ ok: true, json: async () => ({ success: false, payload: 'INVALID_SESSIONID' }) }) } : {}),
    });
    await tick();
    assert.equal(popup.closed, true);
    if (invalidSession) assert.equal(env.state().graph, null);
  }
});

test('configuration errors do not start an automatic popup', async () => {
  const env = setup({ saved: true, office: {
    onReady: async () => ({ host: 'Outlook', platform: 'OfficeOnline' }), context: { platform: 'OfficeOnline' },
  }, silent: async () => { throw new Error('AADSTS50011: redirect mismatch'); } });
  let opened = 0;
  env.context.window.open = () => { opened++; return null; };
  await tick();
  assert.equal(opened, 0);
  assert.match(env.element('contactAutoError').textContent, /AADSTS50011/);
});

test('failed automatic popup SSO stops and a subsequent click starts normal session reuse', async () => {
  const env = setup({ saved: true, office: {
    onReady: async () => ({ host: 'Outlook', platform: 'OfficeOnline' }), context: { platform: 'OfficeOnline' },
  }, silent: async () => { throw new Error('login_required: AADSTS50058'); } });
  const addresses = [], popups = [];
  env.context.window.open = () => {
    const popup = { closed: false, close() { this.closed = true; }, location: { replace: address => addresses.push(new URL(address)) } };
    popups.push(popup); return popup;
  };
  try {
    await tick();
    env.listeners.message({ origin: 'https://crm.example.test', source: popups[0], data: {
      type: 'berliCRM.contacts.authError', message: 'login_required: AADSTS50058',
    } });
    await tick();
    assert.equal(popups.length, 1, 'No automatic retry loop');
    assert.equal(env.element('contactConnect').disabled, false);
    await env.context.contactConnect();
    assert.equal(addresses[1].searchParams.get('mode'), 'reuse');
    assert.equal(popups.length, 2);
  } finally { env.context.contactReset(); }
});

test('missing Microsoft configuration reveals setup guidance without requesting SSO', async () => {
  let calls = 0;
  const env = setup({ saved: true, silent: async () => { calls++; }, api: async input => { if (input.action === 'getContactsMicrosoftConfig') return { configured: false }; } });
  await tick();
  assert.equal(calls, 0);
  assert.equal(env.element('contactMicrosoftLogin').hidden, false);
  assert.match(env.element('contactMicrosoftStatus').textContent, /outlook-addin\/rest\/outlook.config.php/);
});

test('a stalled automatic request releases the UI and a late token cannot replace a manual connection', async () => {
  let expire, finish;
  const pending = new Promise(resolve => { finish = resolve; });
  const env = setup({ saved: true, silent: () => pending, setTimeout: callback => { expire = callback; } });
  await tick();
  expire(); await tick();
  assert.equal(env.state().authPending, false);
  assert.equal(env.element('contactMicrosoftLogin').hidden, false);
  assert.equal(env.element('contactConnect').disabled, false);
  const popup = { closed: false, close() { this.closed = true; } };
  env.context.window.open = () => popup;
  await env.context.contactConnect();
  env.listeners.message({ origin: 'https://crm.example.test', source: popup, data: { type: 'berliCRM.contacts.connected', account: 'manual', token: 'token', expiresAt: Date.now() + 60000, username: 'manual@example.test' } });
  finish({ type: 'berliCRM.contacts.connected', account: 'late', token: 'late', expiresAt: Date.now() + 60000 });
  await tick();
  assert.equal(env.state().graph.account, 'manual');
});

async function manuallyConnected(options = {}) {
  const env = setup({ saved: true, ...options }); await tick();
  const popup = { closed: false, close() { this.closed = true; } };
  env.context.window.open = () => popup;
  await env.context.contactConnect();
  env.listeners.message({ origin: 'https://crm.example.test', source: popup, data: {
    type: 'berliCRM.contacts.connected', account: 'selected-account', token: 'short-lived-secret-token',
    username: 'selected@example.test', expiresAt: Date.now() + 3600000,
  } });
  assert.equal(env.state().graph.account, 'selected-account');
  return env;
}

test('reopening contacts in the same tab reuses a manual Microsoft connection after validating CRM', async () => {
  const first = await manuallyConnected();
  let silentCalls = 0, validate;
  const pending = new Promise(resolve => { validate = resolve; });
  const reopened = setup({ stored: first.stored, sessionStored: first.sessionStored,
    preferences: () => pending, silent: async () => { silentCalls++; throw new Error('Must reuse valid token'); } });
  assert.equal(reopened.state().graph, null, 'Do not restore before CRM validation');
  validate({ ok: true, json: async () => ({ success: true, payload: { language: 'de_de' } }) });
  await tick();
  assert.equal(reopened.state().graph.account, 'selected-account');
  assert.equal(reopened.element('contactMicrosoftLogin').hidden, true);
  assert.equal(reopened.element('contactLoadOutlook').disabled, false);
  assert.equal(silentCalls, 0);
  assert.doesNotMatch([...first.stored.values()].join(''), /short-lived-secret-token/);
  assert.doesNotMatch(JSON.stringify([...first.requests, ...reopened.requests]), /short-lived-secret-token/);
});

test('expired tab tokens are discarded and SSO prefers the last selected Microsoft account to a mailbox alias', async () => {
  const first = await manuallyConnected();
  for (const [key, value] of first.sessionStored) {
    const cached = JSON.parse(value); cached.connection.expiresAt = Date.now() + 29000;
    first.sessionStored.set(key, JSON.stringify(cached));
  }
  let hint;
  const reopened = setup({ stored: first.stored, sessionStored: first.sessionStored,
    office: { onReady: async () => ({ host: 'Outlook' }), context: { mailbox: { userProfile: { emailAddress: 'alias@example.test' } } } },
    silent: async (config, value) => { hint = value; throw new Error('interaction_required'); } });
  await tick();
  assert.equal(hint, 'selected@example.test');
  assert.equal(reopened.state().graph, null);
  assert.equal(first.sessionStored.size, 0);
  assert.equal(reopened.element('contactConnect').disabled, false);
});

test('tab tokens cannot be reused for a different CRM login, user, Entra app, tenant or redirect URI', async () => {
  const first = await manuallyConnected();
  for (const change of ['sessionName', 'userId', 'clientId', 'tenantId', 'redirectUri']) {
    const stored = new Map(first.stored), sessionStored = new Map(first.sessionStored);
    const session = JSON.parse(stored.get(sessionKey));
    if (change === 'sessionName' || change === 'userId') session[change] = 'different';
    stored.set(sessionKey, JSON.stringify(session));
    let calls = 0;
    const reopened = setup({ stored, sessionStored, api: async input => {
      if (input.action === 'getContactsMicrosoftConfig') return { ...first.state().config,
        ...(['clientId', 'tenantId', 'redirectUri'].includes(change) ? { [change]: 'different' } : {}) };
    }, silent: async () => { calls++; throw new Error('New sign-in needed'); } });
    await tick();
    assert.equal(reopened.state().graph, null, change);
    assert.equal(calls, 1, change);
    assert.equal(sessionStored.size, 0, change);
  }
});

test('explicit Microsoft disconnect clears the tab token and account hint', async () => {
  const first = await manuallyConnected();
  first.element('contactDisconnect').events.click();
  assert.equal(first.sessionStored.size, 0);
  assert.equal(first.stored.size, 1, 'Only the CRM login remains');
  let address;
  first.context.window.open = value => { address = new URL(value); return { closed: false, close() {} }; };
  await first.context.contactConnect();
  assert.equal(address.searchParams.get('mode'), 'select');
  assert.equal(address.searchParams.has('login_hint'), false);
  first.context.contactAuthClose();
  const reopened = setup({ stored: first.stored, sessionStored: first.sessionStored }); await tick();
  assert.equal(reopened.state().graph, null);
});

test('CRM logout and rejected Graph tokens clear cached Microsoft access', async () => {
  const loggedOut = await manuallyConnected();
  loggedOut.context.contactReset();
  assert.equal(loggedOut.sessionStored.size, 0);
  assert.equal(loggedOut.stored.size, 0);
  const rejected = await manuallyConnected();
  rejected.context.contactError(new graph.GraphError('Microsoft rejected the token', 401));
  assert.equal(rejected.sessionStored.size, 0);
  assert.equal(rejected.state().graph, null);
  assert.equal(rejected.element('contactMicrosoftLogin').hidden, false);
});

test('a new browser tab has no access token and uses the nonsecret account hint for silent SSO', async () => {
  const first = await manuallyConnected();
  let hint;
  const newTab = setup({ stored: first.stored, silent: async (config, value) => { hint = value; throw new Error('interaction_required'); } });
  await tick();
  assert.equal(newTab.state().graph, null);
  assert.equal(hint, 'selected@example.test');
});

test('unavailable session storage still allows silent or manual Microsoft connection', async () => {
  const env = setup({ saved: true, blockSessionStorage: true, silent: async () => ({
    type: 'berliCRM.contacts.connected', account: 'automatic', token: 'temporary',
    expiresAt: Date.now() + 60000, username: 'user@example.test',
  }) });
  await tick();
  assert.equal(env.state().graph.account, 'automatic');
  assert.equal(env.state().authPending, false);
  env.context.contactReset();
  const manual = await manuallyConnected({ blockSessionStorage: true });
  assert.equal(manual.state().graph.account, 'selected-account');
});

test('an idle Microsoft token expiry shows a recovery notice without logging out of CRM or opening a window', async () => {
  const timers = [];
  const env = await manuallyConnected({ setTimeout: (callback, milliseconds) => { timers.push({ callback, milliseconds }); return timers.length; } });
  assert.ok(timers.at(-1).milliseconds > 30000);
  env.context.window.open = () => { throw new Error('Expiry notices must not open a sign-in window automatically'); };
  env.state().graph.expiresAt = Date.now() + 29000;
  timers.at(-1).callback();
  assert.equal(env.state().graph, null);
  assert.equal(env.state().session.sessionName, 'session');
  assert.equal(env.stored.has(sessionKey), true);
  assert.equal(env.sessionStored.size, 0);
  assert.equal(env.element('contactSessionNotice').hidden, false);
  assert.match(env.element('contactSessionNotice').textContent, /Microsoft.*abgelaufen.*Mit Microsoft verbinden/);
  assert.equal(env.element('contactMicrosoftLogin').hidden, false);
  assert.equal(env.element('contactConnect').disabled, false);
  assert.equal(env.element('contactLoadOutlook').disabled, true);
});

test('returning to a suspended pane detects expiry on focus, pageshow and visibility changes', async () => {
  for (const event of ['focus', 'pageshow', 'visibilitychange']) {
    const env = await manuallyConnected();
    env.state().graph.expiresAt = Date.now() - 1;
    if (event === 'visibilitychange') {
      env.context.document.hidden = true; env.documentEvents[event]();
      assert.notEqual(env.state().graph, null);
      env.context.document.hidden = false; env.documentEvents[event]();
    } else env.listeners[event]();
    assert.equal(env.state().graph, null, event);
    assert.match(env.element('contactSessionNotice').textContent, /Microsoft/);
  }
});

test('expiry before a transfer stops before exporting or writing selected contacts', async () => {
  const env = await manuallyConnected();
  env.element('contactSearch').value = 'Test'; await env.context.contactSearch({ preventDefault() {} });
  env.state().crmRows[0].checkbox.checked = true;
  let writes = 0;
  env.state().client = { existing: async () => null, create: async () => { writes++; } };
  env.state().graph.expiresAt = Date.now() - 1;
  await env.context.contactTransfer('export');
  assert.equal(writes, 0);
  assert.equal(env.requests.filter(input => input.transfer?.mode === 'export').length, 0);
  assert.equal(env.state().session.sessionName, 'session');
  assert.match(env.element('contactTransferStatus').textContent, /Mit Microsoft verbinden/);
});

test('a Graph HTTP 401 is not mistaken for an expired CRM session and its raw message stays collapsed', async () => {
  const env = await manuallyConnected();
  env.context.contactError(new graph.GraphError('HTTP status 401: InvalidAuthenticationToken, Trace ID private-diagnostic', 401));
  assert.equal(env.state().session.sessionName, 'session');
  assert.equal(env.element('contactLoginForm').hidden, true);
  assert.match(env.element('contactMicrosoftStatus').textContent, /Mit Microsoft verbinden/);
  assert.doesNotMatch(env.element('contactTransferStatus').textContent, /Trace ID|InvalidAuthenticationToken/);
  assert.equal(env.element('contactAutoDetails').open, false);
  assert.match(env.element('contactAutoError').textContent, /Trace ID/);
});

test('an expired CRM session during contact use shows the CRM login and a clear notice outside the hidden workspace', async () => {
  const env = await manuallyConnected({ api: async input => {
    if (input.action === 'searchContacts') throw new Error('INVALID_SESSIONID');
  } });
  env.element('contactSearch').value = 'Test'; await env.context.contactSearch({ preventDefault() {} });
  assert.equal(env.state().session, null);
  assert.equal(env.element('contactWorkspace').hidden, true);
  assert.equal(env.element('contactLoginForm').hidden, false);
  assert.equal(env.element('contactSessionNotice').hidden, false);
  assert.match(env.element('contactLoginStatus').textContent, /CRM-Anmeldung.*abgelaufen.*Access Key/);
  assert.doesNotMatch(env.element('contactLoginStatus').textContent, /INVALID_SESSIONID/);
});

test('a CRM HTTP 401 without JSON still gives a readable CRM session expiry message', async () => {
  const env = setup({ saved: true, preferences: async () => ({ status: 401, ok: false,
    json: async () => { throw new Error('Do not parse an expired-session HTML response'); },
  }) }); await tick();
  assert.equal(env.state().session, null);
  assert.equal(env.element('contactSessionNotice').hidden, false);
  assert.match(env.element('contactSessionNotice').textContent, /CRM-Anmeldung.*Access Key/);
});

test('reconnecting Microsoft clears the expired-session notice and the stale transfer message', async () => {
  const env = await manuallyConnected();
  env.state().graph.expiresAt = Date.now() - 1; env.listeners.focus();
  const popup = { closed: false, close() { this.closed = true; } };
  env.context.window.open = () => popup;
  await env.context.contactConnect();
  env.listeners.message({ origin: 'https://crm.example.test', source: popup, data: {
    type: 'berliCRM.contacts.connected', account: 'renewed', token: 'new-temporary-token',
    expiresAt: Date.now() + 3600000, username: 'selected@example.test',
  } });
  assert.equal(env.element('contactSessionNotice').hidden, true);
  assert.equal(env.element('contactTransferStatus').textContent, '');
  assert.equal(env.state().session.sessionName, 'session');
  assert.equal(env.state().graph.account, 'renewed');
  env.context.contactReset();
});

test('Microsoft expiry is deferred during an active operation', async () => {
  const env = await manuallyConnected();
  const currentGraph = env.state().graph;
  env.state().busy = true; env.state().graph.expiresAt = Date.now() - 1;
  assert.equal(env.context.contactCheckMicrosoftExpiry(), false);
  assert.equal(env.state().graph, currentGraph);
  env.state().busy = false; env.listeners.focus();
  assert.equal(env.state().graph, null);
});

test('expiry notices respect an English CRM language preference', async () => {
  const env = await manuallyConnected({ preferences: async () => ({ ok: true,
    json: async () => ({ success: true, payload: { language: 'en_us' } }),
  }) });
  env.state().graph.expiresAt = Date.now() - 1; env.listeners.focus();
  assert.match(env.element('contactSessionNotice').textContent, /Your Microsoft connection.*Connect to Microsoft/);
  env.context.contactError(new Error('INVALID_SESSIONID'));
  assert.match(env.element('contactSessionNotice').textContent, /Your CRM sign-in.*CRM username and Access Key/);
});

test('signing back into CRM clears its expiry notice after validation', async () => {
  const env = await manuallyConnected();
  env.context.contactError(new Error('INVALID_SESSIONID'));
  env.element('contactUsername').value = 'CRM User'; env.element('contactAccessKey').value = 'temporary-key';
  await env.context.contactLogin({ preventDefault() {}, currentTarget: env.element('contactLoginForm') });
  assert.equal(env.element('contactSessionNotice').hidden, true);
  assert.equal(env.element('contactLoginForm').hidden, true);
  assert.equal(env.element('contactWorkspace').hidden, false);
  assert.equal(env.element('contactTransferStatus').textContent, '');
});

test('a late expired-session response cannot delete a newer CRM login from another view', async () => {
  let fail;
  const pending = new Promise((resolve, reject) => { fail = reject; });
  const env = await manuallyConnected({ api: input => input.action === 'searchContacts' ? pending : undefined });
  env.element('contactSearch').value = 'Test';
  const searching = env.context.contactSearch({ preventDefault() {} }); await tick();
  const newer = JSON.stringify({ username: 'Other user', sessionName: 'new-session', userId: '19x4' });
  env.stored.set(sessionKey, newer); env.listeners.storage({ key: sessionKey });
  fail(new Error('INVALID_SESSIONID')); await searching;
  assert.equal(env.stored.get(sessionKey), newer);
  assert.equal(env.element('contactSessionNotice').hidden, true);
});

test('task-pane connection loads Outlook contacts once, refreshes the selected folder, and never writes automatically', async () => {
  const connections = [], counts = [];
  const env = setup({ saved: true, silent: async () => { throw new Error('No cached account'); },
    layout: { setConnection: value => connections.push(value), setCounts: value => counts.push(value) },
  });
  await tick(); let reads = 0;
  env.context.contactLoadOutlook = async () => { reads++; };
  env.state().authPending = true;
  env.context.contactAcceptAuth({ type: 'berliCRM.contacts.connected', token: 'synthetic-token', account: 'fixture-account',
    username: 'user@example.test', expiresAt: Date.now() + 3600000 }, env.state().generation);
  assert.equal(reads, 1); assert.equal(connections.at(-1), true);
  env.element('contactFolder').value = 'another-folder';
  env.element('contactFolder').events.change();
  assert.equal(reads, 2);
  assert.ok(env.requests.every(input => input.action !== 'contactTransfer' || input.transfer.mode === 'describe'));
  env.context.contactDisconnect(); assert.equal(connections.at(-1), false);
  assert.equal(counts.at(-1).outlook, 0);
});

test('CRM paging loads only one page, uses the completed search, and clears selection on page changes', async () => {
  const env = setup({ saved: true, api: input => input.action === 'searchContacts' ? {
    contacts: Array.from({ length: input.page === 1 ? 50 : 1 }, (_, index) => ({ id: '4x' + ((input.page - 1) * 50 + index + 1), lastname: 'Contact' })),
    page: input.page, pageSize: 50, hasMore: input.page === 1, total: 51,
  } : undefined });
  await tick(); env.element('contactSearch').value = 'Initial search';
  await env.context.contactSearch({ preventDefault() {} });
  assert.equal(env.state().crmRows.length, 50);
  assert.equal(env.element('contactCrmPrevious').disabled, true);
  assert.equal(env.element('contactCrmNext').disabled, false);
  assert.equal(env.element('contactCrmPageStatus').textContent, 'Seite 1 · Treffer 1–50 von 51');
  env.state().crmRows[0].checkbox.checked = true;
  env.element('contactSearch').value = 'Not submitted';
  await env.context.contactChangeCrmPage(1);
  assert.equal(env.state().crmPage, 2); assert.equal(env.state().crmRows.length, 1);
  assert.equal(env.state().crmRows[0].record.id, '4x51');
  assert.equal(env.state().crmRows[0].checkbox.checked, false);
  assert.equal(env.element('contactCrmNext').disabled, true);
  assert.equal(env.element('contactCrmPageStatus').textContent, 'Seite 2 · Treffer 51–51 von 51');
  const requests = env.requests.filter(input => input.action === 'searchContacts');
  assert.deepEqual(requests.map(input => [input.query, input.page]), [['Initial search', 1], ['Initial search', 2]]);
  await env.context.contactChangeCrmPage(1);
  assert.equal(env.requests.filter(input => input.action === 'searchContacts').length, 2, 'No request beyond the last page');
  await env.context.contactChangeCrmPage(-1);
  assert.equal(env.state().crmPage, 1);
  await env.context.contactSearch({ preventDefault() {} });
  assert.equal(env.state().crmQuery, 'Not submitted'); assert.equal(env.state().crmPage, 1);
  assert.ok(env.requests.every(input => input.action !== 'contactTransfer' || input.transfer.mode === 'describe'));
});

test('CRM page failures preserve the displayed page and prevent duplicate navigation while pending', async () => {
  let fail;
  const pending = new Promise((resolve, reject) => { fail = reject; });
  const env = setup({ saved: true, api: input => input.action !== 'searchContacts' ? undefined : input.page === 2 ? pending
    : { contacts: [{ id: '4x1', lastname: 'Kept' }], page: 1, pageSize: 50, hasMore: true, total: 51 } });
  await tick(); env.element('contactSearch').value = 'Test';
  await env.context.contactSearch({ preventDefault() {} });
  env.state().crmRows[0].checkbox.checked = true;
  const next = env.context.contactChangeCrmPage(1); await tick();
  assert.equal(env.element('contactCrmPrevious').disabled, true); assert.equal(env.element('contactCrmNext').disabled, true);
  await env.context.contactChangeCrmPage(1);
  assert.equal(env.requests.filter(input => input.action === 'searchContacts').length, 2);
  fail(new Error('Temporary connection error')); await next;
  assert.equal(env.state().crmPage, 1); assert.equal(env.state().crmRows[0].checkbox.checked, true);
  assert.equal(env.element('contactCrmNext').disabled, false);
  assert.match(env.element('contactTransferStatus').textContent, /Temporary connection error/);
});

test('CRM paging handles empty results, English labels and session resets', async () => {
  const env = setup({ saved: true, preferences: async () => ({ ok: true, json: async () => ({ success: true, payload: { language: 'en_us' } }) }),
    api: input => input.action === 'searchContacts' ? { contacts: [], page: input.page, pageSize: 50, hasMore: false, total: 0 } : undefined });
  await tick(); env.element('contactSearch').value = 'Nobody';
  await env.context.contactSearch({ preventDefault() {} });
  assert.equal(env.element('contactCrmPageStatus').textContent, 'Page 1 · 0 results (total: 0)');
  assert.equal(env.element('contactCrmPrevious').disabled, true); assert.equal(env.element('contactCrmNext').disabled, true);
  env.context.contactReset();
  assert.equal(env.element('contactCrmPager').hidden, true);
  assert.equal(env.state().crmQuery, ''); assert.equal(env.state().crmPage, 1);
});

test('CRM page 12 displays its search total in German and English', async () => {
  for (const [language, expected] of [['de_de', 'Seite 12 · Treffer 551–600 von 25616'], ['en_us', 'Page 12 · results 551–600 of 25616']]) {
    const env = setup({ saved: true,
      preferences: async () => ({ ok: true, json: async () => ({ success: true, payload: { language } }) }),
      api: input => input.action === 'searchContacts' ? {
        contacts: Array.from({ length: 50 }, (_, index) => ({ id: '4x' + (551 + index), lastname: 'Contact' })),
        page: input.page, pageSize: 50, hasMore: true, total: 25616,
      } : undefined,
    });
    await tick();
    await env.context.contactSearchPage('a', 12);
    assert.equal(env.element('contactCrmPageStatus').textContent, expected);
    assert.equal(env.state().crmRows.length, 50, 'The total is metadata, not a full contact download');
  }
});

test('invalid or missing search totals do not replace the current page or become zero', async () => {
  for (const total of [undefined, null, -1, '51', 1.5]) {
    const env = setup({ saved: true, api: input => input.action === 'searchContacts' ? {
      contacts: [{ id: '4x1', lastname: 'Kept' }], page: input.page, pageSize: 50, hasMore: true,
      total: input.page === 1 ? 51 : total,
    } : undefined });
    await tick(); await env.context.contactSearchPage('Test', 1);
    env.state().crmRows[0].checkbox.checked = true;
    await env.context.contactChangeCrmPage(1);
    assert.equal(env.state().crmPage, 1); assert.equal(env.state().crmRows[0].checkbox.checked, true);
    assert.equal(env.element('contactCrmPageStatus').textContent, 'Seite 1 · Treffer 1–1 von 51');
    assert.equal(env.element('contactTransferStatus').textContent, env.context.BerliI18n.t('INVALID_CRM_RESPONSE'));
  }
});
