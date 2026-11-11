const { test } = require('node:test');
const assert = require('node:assert/strict');
const { createContactsLayout } = require('../contacts-layout.js');

function setup(wide = false) {
  const elements = new Map(); let focused, resized;
  function element(id) {
    if (!elements.has(id)) elements.set(id, {
      id, hidden: false, textContent: '', open: true, attributes: {}, events: {},
      addEventListener(name, handler) { this.events[name] = handler; },
      setAttribute(name, value) { this.attributes[name] = value; },
      removeAttribute(name) { delete this.attributes[name]; },
      focus() { focused = id; },
    });
    return elements.get(id);
  }
  const media = { matches: wide, addEventListener(name, handler) { assert.equal(name, 'change'); resized = handler; } };
  const api = createContactsLayout({ document: { getElementById: element }, matchMedia: query => {
    assert.equal(query, '(min-width: 880px)'); return media;
  } });
  return { api, element, focused: () => focused, resize(matches) { media.matches = matches; resized(); } };
}

test('narrow pane starts with Outlook contacts and preserves selection when switching views', () => {
  const env = setup();
  assert.equal(env.element('contactOutlookSection').hidden, false);
  assert.equal(env.element('contactCrmSection').hidden, true);
  const selectedContact = { checked: true };
  env.element('contactOutlookSection').selection = selectedContact;
  env.element('contactShowCrm').events.click();
  assert.equal(env.element('contactOutlookSection').hidden, true);
  assert.equal(env.element('contactCrmSection').hidden, false);
  assert.equal(env.element('contactShowCrm').attributes['aria-selected'], 'true');
  assert.equal(env.element('contactShowOutlook').tabIndex, -1);
  env.element('contactShowOutlook').events.click();
  assert.equal(env.element('contactOutlookSection').selection, selectedContact);
  assert.equal(selectedContact.checked, true);
});

test('tab keys use roving focus and support arrows, Home and End', () => {
  const env = setup(); let prevented = 0;
  const key = (id, value) => env.element(id).events.keydown({ key: value, preventDefault() { prevented++; } });
  key('contactShowOutlook', 'ArrowRight'); assert.equal(env.focused(), 'contactShowCrm');
  key('contactShowCrm', 'Home'); assert.equal(env.focused(), 'contactShowOutlook');
  key('contactShowOutlook', 'End'); assert.equal(env.focused(), 'contactShowCrm');
  key('contactShowCrm', 'ArrowLeft'); assert.equal(env.focused(), 'contactShowOutlook');
  key('contactShowOutlook', 'Tab'); assert.equal(prevented, 4);
});

test('wide view shows both panels and returns to the selected tab when narrowed', () => {
  const env = setup(); env.api.select('crm'); env.resize(true);
  assert.equal(env.element('contactViewTabs').hidden, true);
  for (const id of ['contactOutlookSection', 'contactCrmSection']) {
    assert.equal(env.element(id).hidden, false);
    assert.equal(env.element(id).attributes.role, undefined);
  }
  env.resize(false);
  assert.equal(env.element('contactCrmSection').hidden, false);
  assert.equal(env.element('contactOutlookSection').hidden, true);
  assert.equal(env.element('contactCrmSection').attributes.role, 'tabpanel');
});

test('counts describe loaded results only; connection options reopen after disconnection', () => {
  const env = setup(); env.api.setCounts({ outlook: 50, crm: 0 });
  assert.equal(env.element('contactOutlookCount').textContent, '50');
  assert.equal(env.element('contactCrmCount').hidden, true);
  env.api.setCounts({ outlook: NaN, crm: 3 });
  assert.equal(env.element('contactOutlookCount').hidden, true);
  assert.equal(env.element('contactCrmCount').textContent, '3');
  env.api.setConnection(true); assert.equal(env.element('contactConnectionOptions').open, false);
  env.api.setConnection(false); assert.equal(env.element('contactConnectionOptions').open, true);
});
