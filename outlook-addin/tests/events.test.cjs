const { localizedContext } = require('./language-fixture.cjs');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');

const source = fs.readFileSync(path.join(__dirname, '..', 'taskpane.js'), 'utf8');
class Element {
  constructor() { this.children = []; this.text = ''; }
  set textContent(value) { this.text = value; this.children = []; }
  get textContent() { return this.text + this.children.map(child => child.textContent).join(''); }
  set innerHTML(value) { this.text = ''; this.children = []; }
  appendChild(child) { this.children.push(child); }
}
function setup(payload) {
  const container = new Element();
  const context = localizedContext({
    URL,
    window: { location: { href: 'https://example.test/outlook-addin/taskpane.html' } },
    document: {
      getElementById: id => { assert.equal(id, 'eventsResult'); return container; },
      createElement: () => new Element(),
      createTextNode: text => { const element = new Element(); element.textContent = text; return element; },
    },
    Office: { onReady() {} },
    fetch: async (url, init) => {
      assert.deepEqual(JSON.parse(init.body), { language: 'de_de', action: 'listEvents', sessionName: 'fixture-session' });
      return { json: async () => ({ success: true, payload }) };
    },
  });
  vm.runInContext(source, context);
  vm.runInContext("sessionName = 'fixture-session';", context);
  return { context, container };
}
const display = dateFormat => ({ dateFormat, fieldLabels: { date_start: 'Beginn', due_date: 'Ende', location: 'Ort' } });

test('date values respect all CRM date orders and separators without timezone shifts', () => {
  const { context } = setup();
  for (const [format, expected] of [
    ['dd-mm-yyyy', '09-04-2026'], ['mm-dd-yyyy', '04-09-2026'], ['yyyy-mm-dd', '2026-04-09'],
    ['dd.mm.yyyy', '09.04.2026'], ['mm/dd/yyyy', '04/09/2026'], ['dd/mm/yyyy', '09/04/2026'],
  ]) assert.equal(context.formatCrmDate('2026-04-09', format), expected);
  assert.equal(context.formatCrmDate('2024-02-29', 'dd-mm-yyyy'), '29-02-2024');
});

test('invalid dates and unsupported settings produce errors; empty dates stay empty', () => {
  const { context } = setup();
  for (const date of ['2026-02-29', '2026-13-01', '2026-04-31', 'not-a-date']) {
    assert.throws(() => context.formatCrmDate(date, 'dd-mm-yyyy'), /ungültig/);
  }
  for (const format of ['dd-mm-mm', 'browser-locale', undefined]) {
    assert.throws(() => context.formatCrmDate('2026-04-09', format), /nicht unterstützt/);
  }
  for (const value of ['', null, '0000-00-00']) assert.equal(context.formatCrmDate(value, 'dd-mm-yyyy'), '');
});

test('event loading uses CRM labels and date preference while preserving the subject', async () => {
  const subject = 'Demo erlischt am 09.04.2026';
  const env = setup({ events: [{ subject, date_start: '2026-04-09', due_date: '2026-04-10', location: 'Berlin' }], display: display('dd.mm.yyyy') });
  await env.context.loadEvents();
  assert.match(env.container.textContent, /Beginn: 09\.04\.2026/);
  assert.match(env.container.textContent, /Ende: 10\.04\.2026/);
  assert.match(env.container.textContent, /Ort: Berlin/);
  assert.ok(env.container.textContent.includes(subject));
  assert.doesNotMatch(env.container.textContent, /date_start|due_date|2026-04-09/);
});

test('different users can see different CRM field labels and date formats', () => {
  const env = setup();
  const records = [{ subject: 'Meeting', date_start: '2026-04-09' }];
  env.context.renderEvents(records, display('dd-mm-yyyy'));
  assert.match(env.container.textContent, /Beginn: 09-04-2026/);
  env.context.renderEvents(records, { dateFormat: 'mm-dd-yyyy', fieldLabels: { date_start: 'Start Date & Time' } });
  assert.match(env.container.textContent, /Start Date & Time: 04-09-2026/);
  assert.doesNotMatch(env.container.textContent, /Beginn/);
});

test('missing metadata shows an error instead of falling back to raw field names', async () => {
  const env = setup({ events: [{ subject: 'Meeting', date_start: '2026-04-09' }] });
  await env.context.loadEvents();
  assert.match(env.container.textContent, /Fehler/);
  assert.doesNotMatch(env.container.textContent, /date_start:|2026-04-09/);
});

test('optional date fields and empty event results are handled', () => {
  const env = setup();
  env.context.renderEvents([{ subject: 'Meeting', due_date: '0000-00-00' }], display('dd-mm-yyyy'));
  assert.equal(env.container.textContent, 'Meeting');
  env.context.renderEvents([], display('dd-mm-yyyy'));
  assert.equal(env.container.textContent, 'Keine Daten gefunden.');
});
