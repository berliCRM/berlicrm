const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function setup() {
  const elements = new Map();
  const element = id => {
    if (!elements.has(id)) elements.set(id, { value: '', textContent: '', hidden: false,
      events: {}, addEventListener(name, handler) { this.events[name] = handler; } });
    return elements.get(id);
  };
  let completePreferences, changedHandler, mailInitializations = 0;
  const requests = [];
  const preferences = new Promise(resolve => { completePreferences = resolve; });
  const context = vm.createContext({ URL, navigator: { language: 'de-DE' },
    window: { location: { href: 'https://crm.example.test/outlook-addin/taskpane.html' }, localStorage: {
      getItem: () => JSON.stringify({ username: 'Saved user', sessionName: 'saved-session', userId: '19x3' }),
      setItem() {}, removeItem() {},
    } },
    document: { getElementById: element, querySelectorAll: () => [] },
    Office: { onReady() {}, EventType: { ItemChanged: 'changed' }, context: { mailbox: {
      item: { itemType: 'message' },
      addHandlerAsync(name, handler) { assert.equal(name, 'changed'); changedHandler = handler; },
    } } },
    initializeMailReadPane() { mailInitializations++; },
    fetch: async (url, init) => {
      const input = JSON.parse(init.body); requests.push(input);
      if (input.action === 'getUserPreferences') return preferences;
      assert.equal(input.action, 'login');
      return { ok: true, json: async () => ({ success: true, payload: {
        sessionName: 'new-session', userId: '19x4', language: 'en_us',
      } }) };
    },
  });
  for (const name of ['language.js', 'calendar.js', 'taskpane.js']) {
    vm.runInContext(fs.readFileSync(path.join(__dirname, '..', name), 'utf8'), context);
  }
  return { context, element, requests, changed: () => changedHandler, initializedMail: () => mailInitializations,
    complete(payload = { success: true, payload: { language: 'en_us' } }) {
      completePreferences({ ok: true, json: async () => payload });
    } };
}

test('Outlook events and mail controls initialize while the CRM language request is pending', async () => {
  const env = setup();
  const initializing = env.context.initializeTaskpane();
  const changedBeforeResponse = env.changed();
  const mailBeforeResponse = env.initializedMail();
  const sessionBeforeResponse = vm.runInContext('sessionName', env.context);
  env.complete(); await initializing;
  assert.equal(typeof changedBeforeResponse, 'function');
  assert.equal(mailBeforeResponse, 1);
  assert.equal(sessionBeforeResponse, null, 'The saved session still needs validation');
  assert.equal(env.context.BerliI18n.language, 'en_us');
  assert.equal(env.element('loginFields').hidden, true);
  assert.equal(env.element('loginStatus').textContent, 'Signed in as Saved user.');
  assert.equal(env.requests.length, 1);
});

test('a failed preference lookup keeps Outlook events available and leaves sign-in visible', async () => {
  const env = setup();
  const initializing = env.context.initializeTaskpane();
  const changedBeforeResponse = env.changed();
  env.complete({ success: false, payload: 'INVALID_SESSIONID' }); await initializing;
  assert.equal(typeof changedBeforeResponse, 'function');
  assert.equal(vm.runInContext('sessionName', env.context), null);
  assert.equal(env.element('loginFields').hidden, false);
  env.changed()();
  assert.equal(env.element('loginFields').hidden, false);
});

test('a late preference response cannot refresh a newer login or overwrite its language', async () => {
  const env = setup();
  const initializing = env.context.initializeTaskpane();
  env.element('username').value = 'New user'; env.element('accessKey').value = 'not-stored';
  await env.context.login();
  let lateRefreshes = 0;
  env.context.refreshTaskpaneMode = () => { lateRefreshes++; };
  env.complete({ success: true, payload: { language: 'de_de' } }); await initializing;
  assert.equal(lateRefreshes, 0);
  assert.equal(env.context.BerliI18n.language, 'en_us');
  assert.equal(vm.runInContext('sessionName', env.context), 'new-session');
});
