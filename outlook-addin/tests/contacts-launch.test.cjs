const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
function setup({ cached = false, platform = 'OfficeOnline', blocked = false } = {}) {
  const sequence = [];
  const popup = { closed: false, close() { this.closed = true; }, document: { body: {} } };
  const context = vm.createContext({ URL, Date,
    Office: { onReady() {}, context: { platform } }, BerliI18n: { t: () => 'Preparing sign-in' },
    window: { location: { href: 'https://crm.test/outlook-addin/taskpane.html', assign: address => sequence.push(['navigate', new URL(address)]) },
      open: () => { sequence.push(['open']); return blocked ? null : popup; },
      sessionStorage: { getItem: () => cached ? JSON.stringify({ scope: JSON.stringify([
        'https://crm.test/outlook-addin/rest/outlook.php', 'session', '19x3', 'client', 'tenant', 'redirect',
      ]), connection: { token: 'temporary', expiresAt: Date.now() + 60000 } }) : null },
    },
  });
  vm.runInContext(fs.readFileSync(path.join(__dirname, '../taskpane.js'), 'utf8'), context);
  vm.runInContext("sessionName = 'session'; crmUserId = '19x3';", context);
  const event = { currentTarget: { href: 'https://crm.test/outlook-addin/contacts.html' }, button: 0,
    preventDefault() { this.defaultPrevented = true; } };
  return { context, sequence, event, popup };
}
test('contacts launch reserves the sign-in window in the user click before navigating', () => {
  const env = setup(); env.context.launchContactsManager(env.event);
  assert.deepEqual(env.sequence.map(event => event[0]), ['open', 'navigate']);
  assert.equal(env.event.defaultPrevented, true);
  assert.equal(env.sequence[1][1].searchParams.get('outlookPlatform'), 'OfficeOnline');
  assert.ok(Number(env.sequence[1][1].searchParams.get('preparedAuth')) > 0);
  assert.equal(env.sequence[1][1].searchParams.has('sessionName'), false);
});
test('cached connections, classic Outlook and modified clicks retain ordinary navigation', () => {
  for (const options of [{ cached: true }, { platform: 'PC' }, { modified: true }]) {
    const env = setup(options); if (options.modified) env.event.ctrlKey = true;
    env.context.launchContactsManager(env.event);
    assert.equal(env.sequence.length, 0);
    assert.equal(Boolean(env.event.defaultPrevented), false);
  }
});
test('a blocked preopened window leaves the normal contacts link functional', () => {
  const env = setup({ blocked: true }); env.context.launchContactsManager(env.event);
  assert.deepEqual(env.sequence.map(event => event[0]), ['open']);
  assert.equal(Boolean(env.event.defaultPrevented), false);
});
