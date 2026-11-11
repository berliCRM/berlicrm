const { test } = require('node:test');
const assert = require('node:assert/strict');
const relay = require('../contacts-auth-relay.js');

test('sign-in responses round trip with authenticated encryption and fresh nonces', async () => {
  const context = { flowId: 'a'.repeat(64), writeKey: 'b'.repeat(64), key: await relay.createKey() };
  const message = { type: 'berliCRM.contacts.connected', token: 'not-on-server', account: 'account' };
  const first = await relay.encrypt(context, message), second = await relay.encrypt(context, message);
  assert.notEqual(first.iv, second.iv);
  assert.doesNotMatch(JSON.stringify(first), /not-on-server|account|token/);
  assert.deepEqual(await relay.decrypt(context, first), message);
  await assert.rejects(relay.decrypt({ ...context, key: await relay.createKey() }, first));
  await assert.rejects(relay.decrypt({ ...context, flowId: 'c'.repeat(64) }, first));
  const tampered = { ...first, data: (first.data[0] === 'A' ? 'B' : 'A') + first.data.slice(1) };
  await assert.rejects(relay.decrypt(context, tampered));
});

test('handover keys leave the URL fragment and survive the OAuth return in popup session storage', async () => {
  const context = { flowId: 'a'.repeat(64), writeKey: 'b'.repeat(64), key: await relay.createKey() };
  const stored = new Map(), storage = { getItem: key => stored.get(key), setItem: (key, value) => stored.set(key, value) };
  const url = new URL('https://crm.example.test/outlook-addin/contacts-auth.html?start=1&channel=popup');
  url.hash = new URLSearchParams({ relayFlow: context.flowId, relayWrite: context.writeKey, relayKey: context.key });
  assert.deepEqual(relay.readContext(url, storage), context);
  assert.equal(url.hash, '');
  assert.doesNotMatch(url.search, /relayFlow|relayWrite|relayKey/);
  url.hash = 'code=oauth-response&state=msal-state';
  assert.deepEqual(relay.readContext(url, storage), context);
  assert.equal(url.hash, '#code=oauth-response&state=msal-state', 'MSAL still owns and validates the OAuth response');
});

test('malformed handover data and oversized replies are rejected', async () => {
  assert.equal(relay.valid({ flowId: '../file', writeKey: 'b'.repeat(64), key: 'a'.repeat(43) }), false);
  const context = { flowId: 'a'.repeat(64), writeKey: 'b'.repeat(64), key: await relay.createKey() };
  await assert.rejects(relay.encrypt(context, { message: 'x'.repeat(32769) }), /too large/);
});
