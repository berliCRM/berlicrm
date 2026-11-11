const { localizedContext } = require('./language-fixture.cjs');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');

const source = fs.readFileSync(path.join(__dirname, '..', 'taskpane.js'), 'utf8');
const contacts = (count, prefix = 'new') => Array.from({ length: count }, (_, index) => ({
  firstname: 'Contact ' + index,
  email: prefix + index + '@example.test',
}));
const existing = (count, prefix) => contacts(count, prefix).map(record => ({ emailAddress: record.email }));

function setup(options = {}) {
  const status = { textContent: '' };
  const field = { value: options.field || 'to' };
  const fields = { to: [], cc: [], bcc: [], ...options.fields };
  const batches = [];
  const reads = [];
  const requests = [];
  const item = {};
  for (const name of ['to', 'cc', 'bcc']) {
    item[name] = {
      getAsync(callback) {
        reads.push(name);
        if (options.readError === name) {
          callback({ status: 'failed', error: { message: 'Cannot read ' + name } });
        } else {
          callback({ status: 'ok', value: fields[name].slice() });
        }
      },
      addAsync(recipients, callback) {
        batches.push({ field: name, recipients: Array.from(recipients) });
        assert.ok(recipients.length <= 100, 'Outlook accepts at most 100 per call');
        if (options.failBatch === batches.length) {
          callback({ status: 'failed', error: { message: 'Batch failed' } });
          return;
        }
        fields[name].push(...recipients);
        if (options.afterBatch) options.afterBatch({ fields, item, context, field, batches });
        callback({ status: 'ok' });
      },
    };
  }
  const context = localizedContext({
    URL,
    window: { location: { href: 'https://example.test/outlook-addin/taskpane.html' } },
    document: { getElementById: id => ({ recipientStatus: status, recipientField: field })[id] },
    Office: {
      onReady() {},
      AsyncResultStatus: { Succeeded: 'ok' },
      context: { mailbox: { item } },
    },
    fetch: async (url, init) => {
      assert.equal(String(url), 'https://example.test/outlook-addin/rest/outlook.php');
      const request = JSON.parse(init.body);
      requests.push(request);
      if (request.action === 'getVerteilerContacts') {
        return { ok: true, json: async () => ({ success: true, payload: { contacts: options.listContacts } }) };
      }
      assert.equal(request.action, 'getRecipientSettings');
      assert.equal(request.sessionName, 'test-session');
      if (options.fetchSettings) return options.fetchSettings();
      return { ok: true, json: async () => ({ success: true, payload: { recipientLimit: options.limit ?? 500 } }) };
    },
  });
  vm.runInContext(source, context);
  vm.runInContext("sessionName = 'test-session';", context);
  return { context, fields, field, item, status, batches, reads, requests };
}

test('500 list contacts are added to Bcc in five consecutive batches', async () => {
  const env = setup({ field: 'bcc', listContacts: contacts(500) });
  await env.context.addVerteilerToOutlook({ id: '78x500' });
  assert.deepEqual(env.batches.map(batch => batch.recipients.length), [100, 100, 100, 100, 100]);
  assert.equal(env.fields.bcc.length, 500);
  assert.equal(env.fields.to.length, 0);
  assert.equal(new Set(env.fields.bcc.map(recipient => recipient.emailAddress)).size, 500);
  assert.match(env.status.textContent, /500.*Bcc/);
});

test('501 new addresses are rejected before the first write', async () => {
  const env = setup();
  await env.context.addContactsToOutlook(contacts(501));
  assert.equal(env.batches.length, 0);
  assert.match(env.status.textContent, /Empfängerlimit überschritten/);
  assert.match(env.status.textContent, /501/);
});

test('existing To, Cc and Bcc recipients count together at the boundary', async () => {
  const env = setup({ fields: { to: existing(20, 'to'), cc: existing(30, 'cc'), bcc: existing(50, 'bcc') } });
  await env.context.addContactsToOutlook(contacts(400));
  assert.equal(env.fields.to.length + env.fields.cc.length + env.fields.bcc.length, 500);
  assert.equal(env.batches.length, 4);
  await env.context.addContactsToOutlook(contacts(1, 'extra'));
  assert.equal(env.batches.length, 4, 'overflow must not start another batch');
  assert.match(env.status.textContent, /insgesamt 501/);
});

test('a 500-address list is rejected if a different recipient is already present', async () => {
  const env = setup({ fields: { cc: existing(1, 'cc') } });
  await env.context.addContactsToOutlook(contacts(500));
  assert.equal(env.batches.length, 0);
  assert.match(env.status.textContent, /Es wurden keine Empfänger hinzugefügt/);
});

test('duplicates, missing email and existing target addresses do not consume new slots', async () => {
  const env = setup({ fields: { to: [{ emailAddress: 'NEW0@example.test' }] } });
  await env.context.addContactsToOutlook([...contacts(500), { email: ' new0@EXAMPLE.test ' }, { email: '' }, null]);
  assert.equal(env.fields.to.length, 500);
  assert.deepEqual(env.batches.map(batch => batch.recipients.length), [100, 100, 100, 100, 99]);
  assert.match(env.status.textContent, /499 Empfänger/);
  assert.match(env.status.textContent, /2 Kontakte ohne E-Mail/);
  assert.match(env.status.textContent, /1 bereits vorhandene/);
});

test('a later failed batch stops transfer and reports confirmed additions; retry skips them', async () => {
  const options = { failBatch: 2 };
  const env = setup(options);
  await env.context.addContactsToOutlook(contacts(250));
  assert.equal(env.batches.length, 2);
  assert.equal(env.fields.to.length, 100);
  assert.match(env.status.textContent, /100 von 250/);
  assert.match(env.status.textContent, /Batch failed/);
  options.failBatch = null;
  await env.context.addContactsToOutlook(contacts(250));
  assert.equal(env.fields.to.length, 250);
  assert.equal(new Set(env.fields.to.map(recipient => recipient.emailAddress)).size, 250);
});

test('recipient read failures prevent all writes', async () => {
  const env = setup({ readError: 'cc' });
  await env.context.addContactsToOutlook(contacts(100));
  assert.equal(env.batches.length, 0);
  assert.match(env.status.textContent, /Cannot read cc/);
});

test('missing, invalid or failed settings prevent writes instead of bypassing the limit', async () => {
  for (const fetchSettings of [
    async () => ({ ok: true, json: async () => ({ success: true, payload: {} }) }),
    async () => ({ ok: true, json: async () => ({ success: true, payload: { recipientLimit: 0 } }) }),
    async () => ({ ok: false, status: 500, json: async () => ({ success: false, payload: 'Settings failed' }) }),
    async () => { throw new Error('Network failure'); },
  ]) {
    const env = setup({ fetchSettings });
    await env.context.addContactsToOutlook(contacts(1));
    assert.equal(env.batches.length, 0);
    assert.match(env.status.textContent, /Hinzufügen abgebrochen/);
  }
});

test('a configured limit below 500 is enforced', async () => {
  const env = setup({ limit: 200 });
  await env.context.addContactsToOutlook(contacts(201));
  assert.equal(env.batches.length, 0);
  assert.match(env.status.textContent, /höchstens 200/);
});

test('parallel add-in requests cannot both pass the preflight count', async () => {
  let release;
  const env = setup({ fetchSettings: () => new Promise(resolve => { release = resolve; }) });
  const first = env.context.addContactsToOutlook(contacts(500));
  const secondStatus = { textContent: '' };
  await env.context.addContactsToOutlook(contacts(1, 'other'), secondStatus);
  assert.match(secondStatus.textContent, /bereits Empfänger/);
  release({ ok: true, json: async () => ({ success: true, payload: { recipientLimit: 500 } }) });
  await first;
  assert.equal(env.fields.to.length, 500);
  assert.equal(env.requests.length, 1);
});

test('manually added recipients between batches trigger another limit check', async () => {
  const env = setup({ afterBatch: ({ fields, batches }) => {
    if (batches.length === 1) fields.cc.push({ emailAddress: 'manual@example.test' });
  } });
  await env.context.addContactsToOutlook(contacts(500));
  assert.equal(env.batches.length, 1);
  assert.match(env.status.textContent, /Empfängerlimit von 500/);
  assert.match(env.status.textContent, /100 von 500/);
});

test('changing the draft stops later batches and changing the field does not redirect them', async () => {
  const changedDraft = setup({ afterBatch: ({ context }) => { context.Office.context.mailbox.item = {}; } });
  await changedDraft.context.addContactsToOutlook(contacts(250));
  assert.equal(changedDraft.batches.length, 1);
  assert.match(changedDraft.status.textContent, /Entwurf wurde gewechselt/);

  const changedField = setup({ afterBatch: ({ field }) => { field.value = 'bcc'; } });
  await changedField.context.addContactsToOutlook(contacts(250));
  assert.equal(changedField.fields.to.length, 250);
  assert.equal(changedField.fields.bcc.length, 0);
  assert.match(changedField.status.textContent, /„An“/);
});

test('an empty list causes no write and reading mode is rejected', async () => {
  const env = setup();
  await env.context.addContactsToOutlook([{ email: '' }]);
  assert.equal(env.batches.length, 0);
  assert.equal(env.requests.length, 0);
  env.context.Office.context.mailbox.item = {};
  await env.context.addContactsToOutlook(contacts(1));
  assert.equal(env.batches.length, 0);
  assert.match(env.status.textContent, /E-Mail-Entwurf/);
});
