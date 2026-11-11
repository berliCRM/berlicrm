const { localizedContext } = require('./language-fixture.cjs');
const assert = require('node:assert/strict');
const { test } = require('node:test');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const tick = () => new Promise(resolve => setImmediate(resolve));
class Element {
  constructor() { this.value = ''; this.text = ''; this.children = []; this.events = {}; this.hidden = false; this.disabled = false; this.checked = false; }
  set textContent(text) { this.text = text; this.children = []; }
  get textContent() { return this.text + this.children.map(child => child.textContent).join(''); }
  set innerHTML(value) { throw new Error('Unsafe rendering'); }
  append(...children) { this.children.push(...children); }
  replaceChildren(...children) { this.text = ''; this.children = children; }
  addEventListener(name, callback) { this.events[name] = callback; }
}
function setup(options = {}) {
  const elements = new Map(), stored = new Map(), requests = [], sections = [new Element(),new Element()];
  const element = id => { if (!elements.has(id)) elements.set(id, new Element()); return elements.get(id); };
  if (options.saved !== false) stored.set('berliCRM.outlook.session', JSON.stringify({ sessionName: 'fixture', username: 'User', userId: '19x3' }));
  let changed;
  let bodyCalls = 0;
  const item = { itemType: 'message', subject: '<Test subject>', itemId: 'mail1', internetMessageId: '<mail1@example.test>',
    from: { emailAddress: "o'neil@example.test" }, sender: { emailAddress: 'different@example.test' },
    to: [{ emailAddress: 'owner@example.test' }], cc: [], dateTimeCreated: new Date('2026-11-02T10:00:00Z'), attachments: options.attachments || [],
    body: { getAsync(type, callback) { bodyCalls++; if (options.bodyError) callback({ status:'failed',error:{message:'body unavailable'} });
      else callback({ status:'ok',value:'<p>Mail body</p>' }); } },
    getAttachmentContentAsync(id, callback) { callback({status:'ok',value:options.fileContent || {format:'base64',content:'SGVsbG8='}}); },
  };
  const mailbox = { item, userProfile:{ emailAddress:'owner@example.test' }, addHandlerAsync(event, callback){changed=callback;} };
  const context = localizedContext({ URL, TextEncoder, btoa, window:{location:{href:'https://crm.example.test/outlook-addin/taskpane.html'},localStorage:{getItem:key=>stored.get(key)||null,removeItem:key=>stored.delete(key),setItem:(key,value)=>stored.set(key,value)}},
    document:{getElementById:element,createElement:()=>new Element(),querySelectorAll:()=>sections},
    Office:{onReady(callback){context.ready=callback;},context:{mailbox},AsyncResultStatus:{Succeeded:'ok'},CoercionType:{Html:'html'},EventType:{ItemChanged:'changed'}},
    fetch:async (url,init)=>{
      const request=JSON.parse(init.body);requests.push(request);
      if(options.api){const result=await options.api(request);if(result)return result;}
      let payload;
      if(request.action==='login')payload={sessionName:'fixture',userId:'19x3'};
      else {
        assert.equal(request.action,'archiveEmail');
        if(request.archive.mode==='check')payload={canCreate:options.canCreate!==false,contactEmailLabel:options.emailLabel || 'Primäre E-Mail'};
        if(request.archive.mode==='find')payload={canCreate:true,contactEmailLabel:options.emailLabel || 'Primäre E-Mail',contacts:options.contacts || [
          {id:'4x1',firstname:'Ada',lastname:'Lovelace',company:'A & B'},
          {id:'4x2',lastname:'<img src=x onerror=evil()>',company:''},
          {id:'4x3',firstname:'Third',lastname:'Contact',company:'Company'},
        ]};
        if(request.archive.mode==='save')payload={emailId:'10x100',created:true,contactIds:request.archive.contactIds};
      }
      return {ok:true,json:async()=>({success:true,payload})};
    },
  });
  for(const script of ['mail-read.js','calendar.js','taskpane.js'])vm.runInContext(fs.readFileSync(path.join(__dirname,'..',script),'utf8'),context);
  const state=()=>vm.runInContext('mailReadState',context);
  const start=async()=>{context.ready();await tick();};
  return {context,element,sections,requests,mailbox,item,stored,state,start,changed:async()=>{changed();await tick();},bodyCalls:()=>bodyCalls};
}

test('read mode shows login and archive only, validates session without reading message body',async()=>{
  const env=setup();await env.start();
  assert.ok(env.sections.every(section=>section.hidden));assert.equal(env.element('calendarSection').hidden,true);
  assert.equal(env.element('loginFields').hidden,true);assert.equal(env.element('contactsManagerLink').hidden,false);
  assert.equal(env.element('mailReadSection').hidden,false);assert.equal(env.bodyCalls(),0);
  assert.deepEqual(env.requests.map(r=>r.archive.mode),['check']);
});
test('signed-out read view offers login; successful login enables archive',async()=>{
  const env=setup({saved:false});await env.start();assert.equal(env.element('loginFields').hidden,false);
  assert.equal(env.element('mailReadSection').hidden,true);
  env.element('username').value='User';env.element('accessKey').value='secret';await env.context.login();await tick();
  assert.equal(env.element('mailArchiveStart').disabled,false);assert.equal(env.element('accessKey').value,'');
});
test('sender lookup uses From, renders optional company as text, and leaves selection explicit',async()=>{
  const env=setup();await env.start();await env.context.findMailArchiveContacts();
  assert.equal(env.requests.at(-1).archive.from,"o'neil@example.test");
  assert.match(env.element('mailArchiveContacts').textContent,/AdaLovelace|Ada Lovelace/);
  assert.match(env.element('mailArchiveContacts').textContent,/A & B/);
  assert.match(env.element('mailArchiveContacts').textContent,/<img src=x onerror=evil\(\)>/);
  assert.equal(env.element('mailArchiveSave').disabled,true);assert.equal(env.bodyCalls(),0);
});
test('one message write links every selected contact, excludes unselected contacts, and prevents double clicks',async()=>{
  let release;const gate=new Promise(resolve=>{release=resolve;});
  const env=setup({api:async request=>{if(request.archive?.mode==='save')await gate;}});
  await env.start();await env.context.findMailArchiveContacts();
  env.state().rows[0].checkbox.checked=true;env.state().rows[1].checkbox.checked=true;
  const first=env.context.saveMailToCrm();await tick();await env.context.saveMailToCrm();release();await first;
  const writes=env.requests.filter(r=>r.archive?.mode==='save');assert.equal(writes.length,1);
  assert.deepEqual(writes[0].archive.contactIds,['4x1','4x2']);assert.equal(writes[0].archive.mail.body,'<p>Mail body</p>');
  assert.equal(env.state().rows[0].done,true);assert.equal(env.state().rows[2].done,false);
  assert.match(env.element('mailArchiveStatus').textContent,/2 Kontakten/);
});
test('empty search, denied create, expired sessions and failed body reads perform no writes',async()=>{
  const empty=setup({contacts:[]});await empty.start();await empty.context.findMailArchiveContacts();assert.match(empty.element('mailArchiveStatus').textContent,/Keine lesbaren Kontakte/);
  const denied=setup({canCreate:false});await denied.start();assert.equal(denied.element('mailArchiveStart').disabled,true);
  const expired=setup({api:async()=>({ok:true,json:async()=>({success:false,payload:'INVALID_SESSIONID'})})});await expired.start();
  assert.equal(expired.element('loginFields').hidden,false);assert.equal(expired.stored.size,0);
  const broken=setup({bodyError:true});await broken.start();await broken.context.findMailArchiveContacts();broken.state().rows[0].checkbox.checked=true;
  await broken.context.saveMailToCrm();assert.equal(broken.requests.some(r=>r.archive?.mode==='save'),false);
  assert.match(broken.element('mailArchiveStatus').textContent,/body unavailable/);
});
test('changing the selected message ignores a late lookup and clears former selections',async()=>{
  let finish;const pending=new Promise(resolve=>{finish=resolve;});
  const env=setup({api:async request=>{if(request.archive?.mode==='find')await pending;}});await env.start();
  const search=env.context.findMailArchiveContacts();await tick();env.mailbox.item={...env.item,itemId:'second',from:{emailAddress:'second@example.test'}};
  await env.changed();finish();await search;
  assert.equal(env.state().rows.length,0);assert.match(env.element('mailArchiveSender').textContent,/second@example.test/);
});
test('switching to compose restores compose sections and hides archive; calendar remains separate',async()=>{
  const env=setup();await env.start();env.mailbox.item={itemType:'message',subject:{getAsync(){}}};await env.changed();
  assert.ok(env.sections.every(section=>!section.hidden));assert.equal(env.element('mailReadSection').hidden,true);
  assert.equal(env.element('contactsManagerLink').hidden,false);
  assert.equal(env.element('loginFields').hidden,true);assert.equal(env.element('loginHeading').hidden,true);
  assert.equal(env.element('loginSwitchButton').hidden,false);
  assert.match(env.element('loginStatus').textContent,/Angemeldet als/);
  assert.equal(env.requests.some(request=>request.action==='login'),false);
  env.mailbox.item={itemType:'appointment'};vm.runInContext('sessionName=null',env.context);await env.changed();
  assert.ok(env.sections.every(section=>section.hidden));assert.equal(env.element('mailReadSection').hidden,true);
});
test('attachments are read before saving; unsupported cloud attachment prevents incomplete archive',async()=>{
  const env=setup({attachments:[{id:'file',name:'hello.txt',contentType:'text/plain'}]});await env.start();await env.context.findMailArchiveContacts();
  env.state().rows[0].checkbox.checked=true;await env.context.saveMailToCrm();
  assert.equal(env.requests.at(-1).archive.mail.attachments[0].content,'SGVsbG8=');
  const cloud=setup({attachments:[{id:'cloud',name:'cloud.docx'}],fileContent:{format:'url',content:'https://example.test/private'}});
  await cloud.start();await cloud.context.findMailArchiveContacts();cloud.state().rows[0].checkbox.checked=true;await cloud.context.saveMailToCrm();
  assert.equal(cloud.requests.some(r=>r.archive?.mode==='save'),false);
  assert.match(cloud.element('mailArchiveStatus').textContent,/nicht als Datei/);
  cloud.element('mailArchiveAttachments').checked=false;await cloud.context.saveMailToCrm();assert.equal(cloud.requests.at(-1).archive.mail.attachments.length,0);
});

test('switching messages while body is being read prevents the archive POST',async()=>{
  const env=setup();await env.start();await env.context.findMailArchiveContacts();env.state().rows[0].checkbox.checked=true;
  let finish;env.item.body.getAsync=(type,callback)=>{finish=callback;};
  const save=env.context.saveMailToCrm();await tick();
  env.mailbox.item={...env.item,itemId:'different',subject:'Another message'};await env.changed();
  finish({status:'ok',value:'old body'});await save;await tick();
  assert.equal(env.requests.some(request=>request.archive?.mode==='save'),false);
  assert.equal(env.state().rows.length,0);
});

test('text item attachments are UTF-8 encoded and never interpreted as HTML',async()=>{
  const env=setup({attachments:[{id:'item',name:'Termin'}],fileContent:{format:'iCalendar',content:'BEGIN:VCALENDAR\nSUMMARY:Grüße\nEND:VCALENDAR'}});
  await env.start();await env.context.findMailArchiveContacts();env.state().rows[0].checkbox.checked=true;await env.context.saveMailToCrm();
  const file=env.requests.at(-1).archive.mail.attachments[0];assert.equal(file.name,'Termin.ics');
  assert.equal(Buffer.from(file.content,'base64').toString('utf8'),'BEGIN:VCALENDAR\nSUMMARY:Grüße\nEND:VCALENDAR');
});

test('custom CRM email labels are shown on lookup and empty results using text only',async()=>{
  const env=setup({contacts:[],emailLabel:'Berufliche Adresse <intern>'});await env.start();
  assert.match(env.element('mailArchiveMatchField').textContent,/„Berufliche Adresse <intern>“/);
  await env.context.findMailArchiveContacts();
  assert.match(env.element('mailArchiveStatus').textContent,/im Feld „Berufliche Adresse <intern>“/);
  assert.doesNotMatch(env.element('mailArchiveStatus').textContent,/im Feld „E-Mail“/);
});

test('a renamed CRM email label is refreshed on the next search',async()=>{
  const env=setup({contacts:[],api:async request=>request.archive?.mode==='find'
    ?{ok:true,json:async()=>({success:true,payload:{canCreate:true,contactEmailLabel:'Neue Hauptadresse',contacts:[]}})}:null});
  await env.start();assert.match(env.element('mailArchiveMatchField').textContent,/Primäre E-Mail/);
  await env.context.findMailArchiveContacts();
  assert.match(env.element('mailArchiveMatchField').textContent,/Neue Hauptadresse/);
  assert.match(env.element('mailArchiveStatus').textContent,/Neue Hauptadresse/);
});

test('missing CRM email label reports an outdated handler instead of inventing a field label',async()=>{
  const env=setup({api:async request=>request.archive?.mode==='check'
    ?{ok:true,json:async()=>({success:true,payload:{canCreate:true}})}:null});
  await env.start();assert.equal(env.element('mailArchiveStart').disabled,true);
  assert.match(env.element('mailArchiveStatus').textContent,/keine Feldbezeichnung/);
});
