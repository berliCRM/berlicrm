'use strict';
let mailReadState = null;
let mailReadGeneration = 0;
let mailReadSaving = false;
let mailReadSearching = false;

function isOutlookMessageRead() {
  const item = Office.context?.mailbox?.item;
  return Boolean(item && item.itemType === 'message' && typeof item.subject === 'string');
}
function initializeMailReadPane() {
  document.getElementById('mailArchiveStart').addEventListener('click', findMailArchiveContacts);
  document.getElementById('mailArchiveForm').addEventListener('submit', event => { event.preventDefault(); saveMailToCrm(); });
}
function mailReadControls() {
  const busy = mailReadSaving || mailReadSearching;
  document.getElementById('mailArchiveStart').disabled = busy || !mailReadState?.canCreate;
  document.getElementById('mailArchiveSave').disabled = busy || !mailReadState?.canCreate
    || !mailReadState.rows.some(row => row.checkbox.checked && !row.done);
  document.getElementById('loginSwitchButton').disabled = mailReadSaving || (typeof calendarSaving !== 'undefined' && calendarSaving);
  document.getElementById('mailArchiveAttachments').disabled = busy;
  for (const row of mailReadState?.rows || []) row.checkbox.disabled = busy || row.done;
}
function mailReadCurrent(state, generation) {
  return generation === mailReadGeneration && sessionName === state.session
    && Office.context.mailbox.item === state.item && isOutlookMessageRead();
}
async function mailReadRequest(archive, session) {
  const response = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'archiveEmail', sessionName: session, archive }) });
  const result = await response.json();
  if (!response.ok || !result.success) throw new Error(typeof result.payload === 'string' ? result.payload : 'Ungültige CRM-Antwort.');
  return result.payload;
}
function mailReadContactFieldLabel(result) {
  if (typeof result?.contactEmailLabel !== 'string' || !result.contactEmailLabel.trim()) {
    throw new Error('Das CRM hat keine Feldbezeichnung für das E-Mail-Suchfeld der Kontakte geliefert. Bitte den aktualisierten E-Mail-Webservice hochladen.');
  }
  const label = result.contactEmailLabel.trim();
  document.getElementById('mailArchiveMatchField').textContent = 'Abgleich mit „' + label + '“ bei CRM-Kontakten.';
  return label;
}
function mailReadError(error) {
  document.getElementById('mailArchiveStatus').textContent = error.message;
  if (/INVALID_SESSIONID|SESSION_EXPIRED|AUTHENTICATION_REQUIRED|Authencation required|Authentication required|HTTP-Status 401/i.test(error.message)) {
    sessionName = null; crmUserId = null; crmUsername = '';
    try { window.localStorage.removeItem(SESSION_STORAGE_KEY); } catch (storageError) { /* Sign-in remains available. */ }
    document.getElementById('loginStatus').textContent = 'Die CRM-Anmeldung ist abgelaufen. Bitte erneut anmelden.';
    refreshTaskpaneMode();
  }
}
async function refreshMailReadPane() {
  const generation = ++mailReadGeneration;
  mailReadState = null; mailReadSearching = false;
  const visible = isOutlookMessageRead() && Boolean(sessionName);
  document.getElementById('mailReadSection').hidden = !visible;
  document.getElementById('mailArchiveForm').hidden = true;
  document.getElementById('mailArchiveContacts').replaceChildren();
  document.getElementById('mailArchiveMatchField').textContent = '';
  mailReadControls();
  if (!visible) return;
  const state = { item: Office.context.mailbox.item, session: sessionName, rows: [], canCreate: false };
  mailReadState = state;
  document.getElementById('mailArchiveSender').textContent = 'Von: ' + (state.item.from?.emailAddress || 'keine Adresse verfügbar');
  document.getElementById('mailArchiveStatus').textContent = 'CRM-Anmeldung wird geprüft …';
  try {
    const result = await mailReadRequest({ mode: 'check' }, state.session);
    if (!mailReadCurrent(state, generation)) return;
    state.contactEmailLabel = mailReadContactFieldLabel(result);
    state.canCreate = result.canCreate === true;
    document.getElementById('mailArchiveStatus').textContent = state.canCreate
      ? 'Mit „E-Mail im CRM ablegen“ passende Kontakte suchen.' : 'Du hast im CRM keine Berechtigung zum Anlegen von E-Mails.';
  } catch (error) { if (mailReadCurrent(state, generation)) mailReadError(error); }
  finally { if (generation === mailReadGeneration) mailReadControls(); }
}
async function findMailArchiveContacts() {
  if (!mailReadState?.canCreate || mailReadSaving || mailReadSearching) return;
  const state = mailReadState, generation = mailReadGeneration;
  if (!mailReadCurrent(state, generation)) return;
  mailReadSearching = true; state.rows = []; state.from = '';
  document.getElementById('mailArchiveContacts').replaceChildren();
  document.getElementById('mailArchiveForm').hidden = true;
  mailReadControls();
  document.getElementById('mailArchiveStatus').textContent = 'Kontakte zur Von-Adresse werden gesucht …';
  try {
    const from = state.item.from?.emailAddress;
    if (typeof from !== 'string' || !from.trim()) throw new Error('Die E-Mail-Adresse im Von-Feld ist nicht verfügbar.');
    const result = await mailReadRequest({ mode: 'find', from }, state.session);
    if (!mailReadCurrent(state, generation)) return;
    if (!Array.isArray(result.contacts)) throw new Error('Das CRM hat keine gültige Kontaktliste geliefert.');
    state.contactEmailLabel = mailReadContactFieldLabel(result);
    state.from = from; state.canCreate = result.canCreate === true;
    for (const contact of result.contacts) {
      const label = document.createElement('label'); label.className = 'archive-contact';
      const checkbox = document.createElement('input'); checkbox.type = 'checkbox';
      const text = document.createElement('span');
      text.textContent = [contact.firstname, contact.lastname].filter(Boolean).join(' ').trim() || 'Kontakt ' + contact.id;
      if (contact.company) { const company = document.createElement('small'); company.textContent = ' ' + contact.company; text.append(company); }
      label.append(checkbox, text); document.getElementById('mailArchiveContacts').append(label);
      const status = document.createElement('span'); status.className = 'notice'; text.append(status);
      checkbox.addEventListener('change', mailReadControls);
      state.rows.push({ contact, checkbox, status, done: false });
    }
    const count = state.item.attachments?.length || 0;
    document.getElementById('mailArchiveAttachmentOption').hidden = !count;
    document.getElementById('mailArchiveAttachments').checked = true;
    document.getElementById('mailArchiveAttachmentLabel').textContent = 'Anhänge übernehmen (' + count + ')';
    document.getElementById('mailArchiveForm').hidden = !state.rows.length;
    document.getElementById('mailArchiveStatus').textContent = state.rows.length === 1
      ? 'Ein passender Kontakt gefunden. Bitte den Kontakt auswählen, um die E-Mail dort abzulegen.'
      : state.rows.length
        ? state.rows.length + ' passende Kontakte gefunden. Bitte auswählen, bei welchen Kontakten die E-Mail abgelegt werden soll.'
        : 'Keine lesbaren Kontakte mit dieser Adresse im Feld „' + state.contactEmailLabel + '“ gefunden.';
  } catch (error) { if (mailReadCurrent(state, generation)) mailReadError(error); }
  finally { if (generation === mailReadGeneration) { mailReadSearching = false; mailReadControls(); } }
}
function mailReadOfficeCall(target, method, args = []) {
  return new Promise((resolve, reject) => {
    if (typeof target?.[method] !== 'function') { reject(new Error('Diese Outlook-Funktion ist nicht verfügbar: ' + method)); return; }
    target[method](...args, result => result.status === Office.AsyncResultStatus.Succeeded
      ? resolve(result.value) : reject(new Error(result.error?.message || 'Outlook konnte die Nachricht nicht lesen.')));
  });
}
function mailReadBase64Text(text) {
  const bytes = new TextEncoder().encode(text);
  let binary = '';
  for (let i = 0; i < bytes.length; i += 32768) binary += String.fromCharCode(...bytes.subarray(i, i + 32768));
  return btoa(binary);
}
async function readMailForArchive(item, includeAttachments, check) {
  const body = await mailReadOfficeCall(item.body, 'getAsync', [Office.CoercionType.Html]); check();
  const attachments = [];
  const sourceFiles = includeAttachments ? (item.attachments || []) : [];
  if (sourceFiles.length > 100) throw new Error('Es können höchstens 100 Anhänge pro E-Mail übernommen werden.');
  let total = 0;
  for (const file of sourceFiles) {
    check();
    const content = await mailReadOfficeCall(item, 'getAttachmentContentAsync', [file.id]); check();
    const format = String(content.format).toLowerCase();
    let encoded = content.content, name = file.name, type = file.contentType || 'application/octet-stream';
    if (format === 'eml') { encoded = mailReadBase64Text(content.content); if (!/\.eml$/i.test(name)) name += '.eml'; type = 'message/rfc822'; }
    else if (format === 'icalendar') { encoded = mailReadBase64Text(content.content); if (!/\.ics$/i.test(name)) name += '.ics'; type = 'text/calendar'; }
    else if (format !== 'base64') throw new Error('Der Anhang „' + name + '“ kann von Outlook nicht als Datei geliefert werden. Die E-Mail wurde nicht abgelegt.');
    if (typeof encoded !== 'string' || encoded.length > 14 * 1024 * 1024) throw new Error('Ein Anhang überschreitet 10 MB oder ist ungültig.');
    total += encoded.length;
    if (total > 28 * 1024 * 1024) throw new Error('Die Anhänge überschreiten zusammen 20 MB.');
    attachments.push({ name, type, content: encoded });
  }
  const date = item.dateTimeCreated;
  if (!date || typeof date.toISOString !== 'function') throw new Error('Outlook hat kein Nachrichtendatum geliefert.');
  return { from: item.from?.emailAddress, mailbox: Office.context.mailbox.userProfile?.emailAddress,
    itemId: item.itemId || '', internetMessageId: item.internetMessageId || '', subject: item.subject, body,
    to: (item.to || []).map(person => person.emailAddress), cc: (item.cc || []).map(person => person.emailAddress),
    dateTimeCreated: date.toISOString(), attachments };
}
async function saveMailToCrm() {
  if (mailReadSaving || mailReadSearching || !mailReadState?.canCreate) return;
  const state = mailReadState, generation = mailReadGeneration;
  const rows = state.rows.filter(row => row.checkbox.checked && !row.done);
  if (!rows.length) return;
  const check = () => {
    if (!mailReadCurrent(state, generation) || state.item.from?.emailAddress !== state.from) throw new Error('Die E-Mail oder CRM-Anmeldung wurde gewechselt. Bitte die Suche erneut starten.');
  };
  mailReadSaving = true; mailReadControls();
  document.getElementById('mailArchiveStatus').textContent = 'E-Mail wird aus Outlook gelesen …';
  try {
    check();
    const mail = await readMailForArchive(state.item, document.getElementById('mailArchiveAttachments').checked, check);
    check();
    document.getElementById('mailArchiveStatus').textContent = 'E-Mail wird im CRM abgelegt …';
    const result = await mailReadRequest({ mode: 'save', mail, contactIds: rows.map(row => row.contact.id) }, state.session);
    if (!mailReadCurrent(state, generation)) return;
    if (typeof result.emailId !== 'string' || !Array.isArray(result.contactIds)
      || rows.some(row => !result.contactIds.includes(row.contact.id))) throw new Error('Das CRM hat die Ablage nicht vollständig bestätigt. Bitte erneut prüfen.');
    for (const row of rows) { row.done = true; row.checkbox.checked = false; row.status.textContent = ' – im CRM verknüpft'; }
    document.getElementById('mailArchiveStatus').textContent = (result.created ? 'E-Mail im CRM abgelegt' : 'Vorhandene CRM-E-Mail verwendet')
      + ' und mit ' + rows.length + (rows.length === 1 ? ' Kontakt verknüpft.' : ' Kontakten verknüpft.');
  } catch (error) { if (mailReadCurrent(state, generation)) mailReadError(error); }
  finally {
    mailReadSaving = false;
    if (generation === mailReadGeneration) mailReadControls();
    else if (isOutlookMessageRead() && sessionName) refreshMailReadPane();
  }
}
