const { localizedContext } = require('./language-fixture.cjs');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');

class Element {
  constructor() { this.value = ''; this.textContent = ''; this.hidden = false; this.disabled = false; this.events = {}; }
  addEventListener(name, handler) { this.events[name] = handler; }
}

function setup(options = {}) {
  const elements = new Map();
  const element = id => { if (!elements.has(id)) elements.set(id, new Element()); return elements.get(id); };
  const mailSections = Array.from({ length: 4 }, () => new Element());
  const stored = new Map();
  if (options.savedLogin) stored.set('berliCRM.outlook.session', JSON.stringify({ username: 'CRM User', sessionName: 'fixture-session', userId: '19x3' }));
  const requests = [];
  let saves = 0;
  let changedHandler;
  const value = result => ({ getAsync(callback) { callback({ status: 'ok', value: result }); } });
  const item = {
    itemType: 'appointment', itemId: options.unsaved ? '' : 'outlook-item-1',
    subject: value('Outlook subject'), location: value('Berlin'),
    start: value(new Date('2026-04-09T08:00:00Z')), end: value(new Date('2026-04-09T09:00:00Z')),
    recurrence: value(options.recurrence || null),
    body: { getAsync(type, callback) { assert.equal(type, 'text'); callback({ status: 'ok', value: 'Outlook description' }); } },
    saveAsync(callback) {
      saves++;
      if (options.outlookSaveError) callback({ status: 'failed', error: { message: 'Outlook save failed' } });
      else { item.itemId = 'outlook-item-1'; callback({ status: 'ok', value: item.itemId }); }
    },
  };
  if (options.mail) item.itemType = 'message';
  const context = localizedContext({
    URL,
    window: { location: { href: 'https://example.test/outlook-addin/taskpane.html' }, localStorage: {
      getItem: key => stored.get(key) || null,
      setItem: (key, data) => stored.set(key, data), removeItem: key => stored.delete(key),
    } },
    document: { getElementById: element, querySelectorAll: selector => { assert.equal(selector, '.mail-section'); return mailSections; } },
    Office: {
      AsyncResultStatus: { Succeeded: 'ok' }, CoercionType: { Text: 'text' }, EventType: { ItemChanged: 'changed' },
      context: { mailbox: { item, userProfile: { emailAddress: 'mailbox@example.test' },
        addHandlerAsync(name, handler) { changedHandler = handler; },
      } },
      onReady(callback) { context.ready = callback; },
    },
    fetch: async (url, init) => {
      const request = JSON.parse(init.body);
      requests.push(request);
      if (request.action === 'login') return { ok: true, json: async () => ({ success: true, payload: { sessionName: 'fixture-session', userId: '19x3' } }) };
      assert.equal(request.action, 'calendarEvent');
      assert.equal(request.sessionName, 'fixture-session');
      const calendar = request.calendar;
      if (options.fetchCalendar) return options.fetchCalendar(calendar);
      if (calendar.mode === 'prepare') return { ok: true, json: async () => ({ success: true, payload: {
        eventId: options.linked ? '9x100' : null, timezone: 'Europe/Berlin', start: '09-04-2026 10:00', end: '09-04-2026 11:00',
        display: { dateFormat: 'dd-mm-yyyy', fieldLabels: { subject: 'Betreff', date_start: 'Beginn', due_date: 'Ende', location: 'Ort', description: 'Beschreibung' } },
      } }) };
      return { ok: true, json: async () => ({ success: true, payload: { eventId: '9x100', updated: options.linked || requests.filter(entry => entry.calendar && entry.calendar.mode === 'save').length > 1 } }) };
    },
  });
  vm.runInContext(fs.readFileSync(path.join(__dirname, '..', 'calendar.js'), 'utf8'), context);
  vm.runInContext(fs.readFileSync(path.join(__dirname, '..', 'taskpane.js'), 'utf8'), context);
  const start = async () => { context.ready(); await tick(); };
  return { context, element, item, mailSections, requests, stored, start, saves: () => saves, changed: () => changedHandler() };
}
const tick = () => new Promise(resolve => setImmediate(resolve));

test('calendar shows only login while signed out; successful login loads the appointment form', async () => {
  const env = setup();
  await env.start();
  assert.ok(env.mailSections.every(section => section.hidden));
  assert.equal(env.element('calendarSection').hidden, true);
  assert.equal(env.element('loginFields').hidden, false);
  env.element('username').value = 'CRM User';
  env.element('accessKey').value = 'not-stored';
  await env.context.login();
  await tick();
  assert.equal(env.element('loginFields').hidden, true);
  assert.equal(env.element('calendarSection').hidden, false);
  assert.equal(env.element('calendarSubject').value, 'Outlook subject');
  assert.equal(env.element('calendarStart').value, '09-04-2026 10:00');
  assert.match(env.element('calendarTimezone').textContent, /Europe\/Berlin/);
  assert.equal(env.element('accessKey').value, '');
  assert.doesNotMatch([...env.stored.values()].join(''), /not-stored/);
});

test('restored session shows compact login status and prefilled calendar fields', async () => {
  const env = setup({ savedLogin: true, linked: true });
  await env.start();
  assert.equal(env.element('loginHeading').hidden, true);
  assert.match(env.element('loginStatus').textContent, /CRM User/);
  assert.equal(env.element('calendarSaveButton').textContent, 'CRM-Termin aktualisieren');
  assert.equal(env.element('calendarSaveButton').disabled, false);
  assert.equal(env.element('calendarDescription').value, 'Outlook description');
});

test('mail context retains existing sections and never reads calendar data', async () => {
  const env = setup({ savedLogin: true, mail: true });
  await env.start();
  assert.ok(env.mailSections.every(section => !section.hidden));
  assert.equal(env.element('calendarSection').hidden, true);
  assert.equal(env.requests.length, 0);
  assert.equal(env.element('loginFields').hidden, true);
  assert.equal(env.element('loginHeading').hidden, true);
  assert.equal(env.element('loginSwitchButton').hidden, false);
  assert.match(env.element('loginStatus').textContent, /Angemeldet als CRM User/);
});

test('compose login becomes compact and only an explicit account switch restores the login form', async () => {
  const env = setup({ mail: true });
  await env.start();
  assert.equal(env.element('loginFields').hidden, false);
  assert.equal(env.element('loginHeading').hidden, false);
  assert.equal(env.element('loginSwitchButton').hidden, true);
  env.element('username').value = 'CRM User';
  env.element('accessKey').value = 'not-stored';
  await env.context.login();
  assert.equal(env.element('loginFields').hidden, true);
  assert.equal(env.element('loginHeading').hidden, true);
  assert.equal(env.element('loginSwitchButton').hidden, false);
  assert.equal(env.element('accessKey').value, '');
  assert.doesNotMatch([...env.stored.values()].join(''), /not-stored/);
  assert.equal(env.requests.filter(request => request.action === 'login').length, 1);
  env.element('loginSwitchButton').events.click();
  assert.equal(env.element('loginFields').hidden, false);
  assert.equal(env.element('loginHeading').hidden, false);
  assert.equal(env.element('loginSwitchButton').hidden, true);
  assert.equal(env.stored.has('berliCRM.outlook.session'), false);
  assert.equal(vm.runInContext('sessionName', env.context), null);
});

test('an unsaved appointment is saved in Outlook before creating/updating its CRM counterpart', async () => {
  const env = setup({ savedLogin: true, unsaved: true });
  await env.start();
  env.element('calendarSubject').value = 'Edited CRM subject';
  env.element('calendarStart').value = '09-04-2026 10:15';
  await env.context.saveOutlookAppointmentToCrm();
  assert.equal(env.saves(), 1);
  const request = env.requests.find(entry => entry.calendar.mode === 'save').calendar;
  assert.equal(request.itemId, 'outlook-item-1');
  assert.equal(request.subject, 'Edited CRM subject');
  assert.equal(request.start, '09-04-2026 10:15');
  assert.equal(request.originalStart, '2026-04-09T08:00:00.000Z');
  assert.equal(request.mailbox, 'mailbox@example.test');
  assert.match(env.element('calendarStatus').textContent, /angelegt/);
  assert.equal(env.element('calendarSaveButton').textContent, 'CRM-Termin aktualisieren');
  await env.context.saveOutlookAppointmentToCrm();
  assert.match(env.element('calendarStatus').textContent, /aktualisiert/);
  assert.equal(env.element('calendarSaveButton').disabled, false);
});

test('series are rejected and failure does not leave a previous appointment editable', async () => {
  const env = setup({ savedLogin: true });
  await env.start();
  env.item.recurrence = { getAsync(callback) { callback({ status: 'ok', value: { recurrenceType: 'daily' } }); } };
  await env.context.loadOutlookAppointment();
  assert.match(env.element('calendarStatus').textContent, /Terminserien/);
  assert.equal(env.element('calendarSaveButton').disabled, true);
  assert.equal(env.element('calendarSubject').value, '');
  assert.equal(env.saves(), 0);
});

test('an Outlook save failure prevents all CRM writes', async () => {
  const env = setup({ savedLogin: true, outlookSaveError: true });
  await env.start();
  await env.context.saveOutlookAppointmentToCrm();
  assert.match(env.element('calendarStatus').textContent, /Outlook save failed/);
  assert.equal(env.requests.filter(entry => entry.calendar.mode === 'save').length, 0);
});

test('changes made in Outlook after preview require a refresh before saving', async () => {
  const env = setup({ savedLogin: true });
  await env.start();
  env.item.subject = { getAsync(callback) { callback({ status: 'ok', value: 'Changed outside the pane' }); } };
  await env.context.saveOutlookAppointmentToCrm();
  assert.match(env.element('calendarStatus').textContent, /zwischenzeitlich/);
  assert.equal(env.saves(), 0);
  assert.equal(env.requests.filter(entry => entry.calendar.mode === 'save').length, 0);
});

test('changing appointment invalidates a late prepare response', async () => {
  let release;
  const env = setup({ savedLogin: true, fetchCalendar: () => new Promise(resolve => { release = resolve; }) });
  await env.start();
  env.context.Office.context.mailbox.item = { itemType: 'message' };
  env.changed();
  release({ ok: true, json: async () => ({ success: true, payload: { eventId: '9x100', display: {}, start: 'old', end: 'old' } }) });
  await tick();
  assert.equal(env.element('calendarSection').hidden, true);
  assert.equal(env.element('calendarSubject').value, '');
  assert.ok(env.mailSections.every(section => !section.hidden));
});

test('expired CRM session restores login instead of leaving an unusable form', async () => {
  const env = setup({ savedLogin: true, fetchCalendar: () => ({ ok: true, json: async () => ({ success: false, payload: 'HTTP-Status 401 Authentication required' }) }) });
  await env.start();
  assert.equal(env.element('calendarSection').hidden, true);
  assert.equal(env.element('loginFields').hidden, false);
  assert.match(env.element('loginStatus').textContent, /abgelaufen/);
  assert.equal(env.stored.size, 0);
});

test('concurrent save clicks only issue one request', async () => {
  const env = setup({ savedLogin: true });
  await env.start();
  await Promise.all([env.context.saveOutlookAppointmentToCrm(), env.context.saveOutlookAppointmentToCrm()]);
  assert.equal(env.saves(), 1);
  assert.equal(env.requests.filter(entry => entry.calendar.mode === 'save').length, 1);
});

test('attendee read context can import a saved single appointment', async () => {
  const env = setup({ savedLogin: true });
  env.item.subject = 'Read appointment';
  env.item.start = new Date('2026-04-09T08:00:00Z');
  env.item.end = new Date('2026-04-09T09:00:00Z');
  env.item.location = 'Berlin';
  env.item.recurrence = null;
  delete env.item.saveAsync;
  await env.start();
  await env.context.saveOutlookAppointmentToCrm();
  assert.equal(env.saves(), 0);
  assert.match(env.element('calendarStatus').textContent, /angelegt/);
});
