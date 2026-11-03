const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { GraphClient, GraphError } = require('../contact-graph.js');
const model = require('../contact-model.js');
const propertyId = 'String {f9e177c7-325a-4d90-ade5-a45bd5922b2e} Name berliCRMContact';
const response = (value, status = 200) => ({ ok: status < 400, status, json: async () => value });
const metadata = { ownerId: '19x3', fields: [
  { name: 'lastname', label: 'Nachname', mandatory: true, editable: true },
  { name: 'assigned_user_id', label: 'Zuständig', mandatory: true, editable: false },
  { name: 'email', label: 'E-Mail', mandatory: false, editable: true },
] };

test('default browser transport keeps the Window receiver when reading and creating contacts', async () => {
  let browserGlobal;
  const calls = [];
  const context = vm.createContext({ URL, URLSearchParams,
    fetch: function (url, options) {
      // Native browser fetch rejects a GraphClient instance as its receiver.
      if (this !== browserGlobal) throw new TypeError("Failed to execute 'fetch' on 'Window': Illegal invocation");
      calls.push({ url, options });
      return Promise.resolve(response(options.method === 'POST' ? { id: 'created-contact' } : { value: [] }));
    },
  });
  browserGlobal = vm.runInContext('globalThis', context);
  vm.runInContext(fs.readFileSync(path.join(__dirname, '../contact-graph.js'), 'utf8'), context);
  const client = new context.BerliContactGraph.GraphClient(() => 'test-token', propertyId);
  await client.listPage();
  const created = await client.create({ contact: { surname: 'Test' } });
  assert.equal(created.id, 'created-contact');
  assert.deepEqual(calls.map(call => call.options.method), ['GET', 'POST']);
  assert.equal(JSON.parse(calls[1].options.body).surname, 'Test');
  for (const call of calls) assert.equal(call.options.headers.Authorization, 'Bearer test-token');
});

test('mandatory CRM fields, including custom fields, control import eligibility', () => {
  assert.deepEqual(model.missingFields({ givenName: 'Ada' }, metadata).map(f => f.name), ['lastname']);
  assert.deepEqual(model.missingFields({ surname: '   ' }, metadata).map(f => f.name), ['lastname']);
  assert.deepEqual(model.missingFields({ surname: '0' }, metadata), []);
  const custom = { ...metadata, fields: [...metadata.fields, { name: 'cf_123', label: 'Kundennummer', mandatory: true, editable: true }] };
  assert.deepEqual(model.missingFields({ surname: 'Lovelace', cf_123: 'injected' }, custom).map(f => f.label), ['Kundennummer']);
  assert.equal(model.crmValues({ surname: 'Lovelace', assigned_user_id: '19x999' }, metadata.ownerId).assigned_user_id, '19x3');
});

test('contact mapping keeps optional email optional and maps both addresses', () => {
  const values = model.crmValues({ surname: 'Müller', businessAddress: { postalCode: '01234', city: 'Berlin' },
    homeAddress: { street: 'Privat 1' }, emailAddresses: [{ address: 'first@example.test' }, { address: 'second@example.test' }] }, '19x3');
  assert.equal(values.mailingzip, '01234'); assert.equal(values.otherstreet, 'Privat 1');
  assert.equal(values.secondaryemail, 'second@example.test');
  assert.deepEqual(model.missingFields({ surname: 'Müller' }, metadata), []);
});

test('Graph paging cannot send credentials to another origin or API version', async () => {
  let tokens = 0, requests = 0;
  const client = new GraphClient(() => { tokens++; return 'test-token'; }, propertyId, async () => { requests++; return response({}); });
  for (const address of ['https://evil.example/v1.0/me/contacts', '//evil.example/v1.0/me', 'https://graph.microsoft.com/beta/me',
    'https://user@graph.microsoft.com/v1.0/me', 'https://graph.microsoft.com/v1.0/me#token']) {
    await assert.rejects(client.listPage('', address), GraphError);
  }
  assert.equal(tokens, 0); assert.equal(requests, 0);
});

test('folder paging and fresh contact reads use immutable IDs and encoded folder/contact IDs', async () => {
  const calls = [];
  const client = new GraphClient(() => 'test-token', propertyId, async (url, options) => { calls.push({ url: new URL(url), options }); return response({ value: [] }); });
  await client.listPage('folder/a');
  const page = 'https://graph.microsoft.com/v1.0/me/contactFolders/f/contacts?$skiptoken=next';
  await client.listPage('folder/a', page);
  await client.getContact('contact/b', 'folder/a');
  assert.equal(calls[0].url.pathname, '/v1.0/me/contactFolders/folder%2Fa/contacts');
  assert.equal(calls[0].url.searchParams.get('$top'), '100');
  assert.match(calls[0].url.searchParams.get('$expand'), /berliCRMContact/);
  assert.equal(calls[1].url.href, page);
  assert.equal(calls[2].url.pathname, '/v1.0/me/contactFolders/folder%2Fa/contacts/contact%2Fb');
  for (const { options } of calls) {
    assert.equal(options.headers.Prefer, 'IdType="ImmutableId"');
    assert.equal(options.headers.Authorization, 'Bearer test-token');
  }
});

test('folder listing follows pagination and nested folders without duplicates', async () => {
  const queue = [
    { value: [{ id: 'a', displayName: 'A' }], '@odata.nextLink': 'https://graph.microsoft.com/v1.0/me/contactFolders?$skip=1' },
    { value: [{ id: 'b', displayName: 'B' }] },
    { value: [{ id: 'c', displayName: 'C' }] },
    { value: [] }, { value: [{ id: 'a', displayName: 'A' }] },
  ];
  const client = new GraphClient(() => 'token', propertyId, async () => response(queue.shift()));
  assert.deepEqual(await client.folders(), [{ id: 'a', name: 'A' }, { id: 'b', name: 'B' }, { id: 'c', name: 'A / C' }]);
  assert.equal(queue.length, 0);
});

test('folder listing refuses repeating next links', async () => {
  const client = new GraphClient(() => 'token', propertyId, async () => response({ value: [], '@odata.nextLink': 'https://graph.microsoft.com/v1.0/repeated' }));
  await assert.rejects(client.folders(), /wiederholte/);
});

test('export first checks stable CRM marker then every email and escapes OData literals', async () => {
  const filters = [];
  const exported = { key: "instance:4x1'", contact: { emailAddresses: [{ address: "o'neil@example.test" }, { address: 'second@example.test' }] } };
  const client = new GraphClient(() => 'token', propertyId, async (url, options) => {
    assert.equal(options.method, 'GET'); filters.push(new URL(url).searchParams.get('$filter'));
    return response({ value: filters.length === 3 ? [{ id: 'existing' }] : [] });
  });
  assert.deepEqual(await client.existing(exported), { id: 'existing' });
  assert.match(filters[0], /instance:4x1''/); assert.match(filters[1], /o''neil/); assert.match(filters[2], /second@example.test/);
});

test('failed lookup and unconfirmed writes are errors; Graph never retries POST', async () => {
  let calls = 0;
  const client = new GraphClient(() => 'token', propertyId, async () => { calls++; return response({ error: { message: 'Denied' } }, 403); });
  await assert.rejects(client.existing({ key: 'key', contact: {} }), error => error.status === 403);
  assert.equal(calls, 1);
  await assert.rejects(client.create({ contact: {} }), error => error.status === 403);
  assert.equal(calls, 2);
  const uncertain = new GraphClient(() => 'token', propertyId, async () => response({}));
  await assert.rejects(uncertain.create({ contact: {} }), /nicht eindeutig/);
});
