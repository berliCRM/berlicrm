const { localizedContext } = require('./language-fixture.cjs');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');

const source = fs.readFileSync(path.join(__dirname, '..', 'taskpane.js'), 'utf8');
class Element {
  constructor(tag) { this.tagName = tag; this.children = []; this.events = {}; this.text = ''; }
  set textContent(value) { this.text = value; this.children = []; }
  get textContent() { return this.text + this.children.map(child => child.textContent).join(''); }
  set innerHTML(value) { this.text = ''; this.children = []; }
  appendChild(child) { this.children.push(child); }
  setAttribute(key, value) { this[key] = value; }
  addEventListener(event, handler) { this.events[event] = handler; }
}
function setup(fetcher) {
  const container = new Element('div');
  const query = { value: 'test' };
  const requests = [];
  const context = localizedContext({
    URL,
    window: { location: { href: 'https://example.test/outlook-addin/taskpane.html' } },
    document: {
      getElementById: id => ({ verteilerResult: container, verteilerQuery: query })[id],
      createElement: tag => new Element(tag),
    },
    Office: { onReady() {} },
    fetch: async (url, init) => {
      const request = JSON.parse(init.body);
      assert.equal(request.sessionName, 'fixture-session');
      requests.push(request);
      return fetcher(request);
    },
  });
  vm.runInContext(source, context);
  vm.runInContext("sessionName = 'fixture-session'; crmUserId = '19x3';", context);
  return { context, container, query, requests };
}
const records = count => Array.from({ length: count }, (_, index) => ({ id: '78x' + (index + 1), verteilername: 'List ' + index }));
const response = count => ({ ok: true, json: async () => ({ success: true, payload: { emailContactCount: count } }) });
const tick = () => new Promise(resolve => setImmediate(resolve));

test('zero, singular and plural counts appear beside the name, retaining the compact button before it', async () => {
  const counts = { '78x1': 0, '78x2': 1, '78x3': 42 };
  const env = setup(request => response(counts[request.verteilerId]));
  const pending = env.context.renderVerteilerSearchResults(records(3));
  assert.match(env.container.children[0].textContent, /Anzahl wird geladen/);
  await pending;
  assert.match(env.container.children[0].textContent, /List 0 \(0 Kontakte mit E-Mail\)/);
  assert.match(env.container.children[1].textContent, /List 1 \(1 Kontakt mit E-Mail\)/);
  assert.match(env.container.children[2].textContent, /List 2 \(42 Kontakte mit E-Mail\)/);
  const header = env.container.children[2].children[0];
  assert.equal(header.children[0].tagName, 'button');
  assert.match(header.children[0].className, /button-compact/);
  assert.equal(header.children[1].tagName, 'strong');
  assert.doesNotMatch(header.children[0]['aria-label'], /Anzahl/);
  assert.ok(env.requests.every(request => request.action === 'countVerteilerContacts'));
});

test('count requests run with at most two in flight', async () => {
  let active = 0;
  let maximum = 0;
  const releases = [];
  const env = setup(() => new Promise(resolve => {
    active++;
    maximum = Math.max(active, maximum);
    releases.push(() => { active--; resolve(response(12)); });
  }));
  const pending = env.context.renderVerteilerSearchResults(records(5));
  assert.equal(env.requests.length, 2);
  for (let index = 0; index < 5; index++) {
    releases[index]();
    await tick();
  }
  await pending;
  assert.equal(maximum, 2);
  assert.equal(env.requests.length, 5);
});

test('API, network and invalid count failures display unavailable rather than a false zero', async () => {
  for (const fetcher of [
    () => ({ ok: false, json: async () => ({ success: false, payload: 'Permission denied' }) }),
    () => { throw new Error('Network failure'); },
    ...[null, -1, '3', 1.5].map(value => () => response(value)),
  ]) {
    const env = setup(fetcher);
    await env.context.renderVerteilerSearchResults(records(1));
    const count = env.container.children[0].children[0].children[1].children[0];
    assert.equal(count.textContent, ' (Anzahl nicht verfügbar)');
    assert.ok(count.title);
  }
});

test('a superseded search stops queued counts and ignores late answers', async () => {
  const releases = [];
  const env = setup(request => {
    if (request.verteilerId === '78x99') return response(7);
    return new Promise(resolve => releases.push(() => resolve(response(99))));
  });
  const previous = env.context.renderVerteilerSearchResults(records(4));
  const oldRow = env.container.children[0];
  await env.context.renderVerteilerSearchResults([{ id: '78x99', verteilername: 'Latest' }]);
  releases.forEach(release => release());
  await previous;
  assert.equal(env.requests.length, 3, 'two queued old rows should never be requested');
  assert.match(env.container.textContent, /Latest \(7 Kontakte/);
  assert.match(oldRow.textContent, /Anzahl wird geladen/);
});

test('a slow old search result cannot overwrite a newer search', async () => {
  const searchReleases = {};
  const env = setup(request => request.action === 'searchVerteiler'
    ? new Promise(resolve => { searchReleases[request.query] = resolve; })
    : response(2));
  env.query.value = 'old';
  const old = env.context.searchVerteiler();
  env.query.value = 'new';
  const latest = env.context.searchVerteiler();
  const searchResponse = name => ({ ok: true, json: async () => ({ success: true, payload: { verteilers: [{ id: '78x99', verteilername: name }] } }) });
  searchReleases.new(searchResponse('New list'));
  await latest;
  await tick();
  searchReleases.old(searchResponse('Old list'));
  await old;
  assert.match(env.container.textContent, /New list/);
  assert.doesNotMatch(env.container.textContent, /Old list/);
});

test('list insertion remains clickable before the count finishes', async () => {
  let release;
  const env = setup(() => new Promise(resolve => { release = resolve; }));
  const pending = env.context.renderVerteilerSearchResults(records(1));
  let selected;
  env.context.addVerteilerToOutlook = async (record, status) => { selected = record.id; status.textContent = 'Added'; };
  const row = env.container.children[0];
  const button = row.children[0].children[0];
  await button.events.click();
  assert.equal(selected, '78x1');
  assert.equal(button.disabled, false);
  assert.equal(row.children[1].hidden, false);
  assert.equal(row.children[1].textContent, 'Added');
  release(response(3));
  await pending;
  assert.equal(row.children[1].textContent, 'Added');
});
