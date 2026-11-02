const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const model = require('../contact-model.js');
const graph = require('../contact-graph.js');
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
  const elements = new Map(), stored = new Map(), listeners = {}, requests = [];
  const element = id => { if (!elements.has(id)) elements.set(id, new Element()); return elements.get(id); };
  element('contactWorkspace').hidden = true;
  if (options.saved) stored.set(sessionKey, JSON.stringify({ username: 'CRM User', sessionName: 'session', userId: '19x3' }));
  const context = vm.createContext({
    URL, URLSearchParams, Set, Date, clearInterval, setInterval, clearTimeout, setTimeout: options.setTimeout || setTimeout,
    location: new URL('https://crm.example.test/outlook-addin/contacts.html'),
    localStorage: { getItem: key => stored.get(key) || null, setItem: (key, value) => stored.set(key, value), removeItem: key => stored.delete(key) },
    window: { addEventListener: (name, handler) => { listeners[name] = handler; },
      ...(options.silent ? { BerliContactsAuth: { connectSilently: options.silent } } : {}) },
    ...(options.office ? { Office: options.office } : {}),
    document: { getElementById: element, createElement: () => new Element() },
    Option: function(label, value) { const option = new Element(); option.textContent = label; option.value = value; return option; },
    BerliContactModel: model, BerliContactGraph: graph,
    fetch: async (url, init) => {
      assert.equal(String(url), 'https://crm.example.test/outlook-addin/rest/outlook.php');
      const input = JSON.parse(init.body); requests.push(input);
      let payload;
      if (options.api) payload = await options.api(input);
      if (payload === undefined) {
        if (input.action === 'login') payload = { sessionName: 'session', userId: '19x3' };
        else if (input.action === 'getContactsMicrosoftConfig') payload = { configured: true };
        else if (input.action === 'searchContacts') payload = { contacts: [{ id: '4x1', firstname: '<img onerror=evil()>', lastname: 'Test', email: 'test@example.test' }] };
        else if (input.transfer.mode === 'describe') payload = metadata;
        else if (input.transfer.mode === 'export') payload = { key: 'crm:4x1', contact: { surname: 'Test' } };
        else payload = { status: 'created', recordId: '4x2' };
      }
      return { ok: true, json: async () => ({ success: true, payload }) };
    },
  });
  vm.runInContext(fs.readFileSync(path.join(__dirname, '../contacts.js'), 'utf8'), context);
  const state = () => vm.runInContext('contactState', context);
  function connect(client = {}) { state().graph = { account: 'account' }; state().client = client; context.contactControls(); }
  return { context, element, stored, listeners, requests, state, connect };
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
