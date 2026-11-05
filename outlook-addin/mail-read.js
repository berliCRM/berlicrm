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
    body: JSON.stringify({ language: globalThis.BerliI18n.language, action: 'archiveEmail', sessionName: session, archive }) });
  const result = await response.json();
  if (!response.ok || !result.success) throw new Error(typeof result.payload === 'string' ? result.payload : globalThis.BerliI18n.t("INVALID_CRM_RESPONSE"));
  return result.payload;
}
function mailReadContactFieldLabel(result) {
  if (typeof result?.contactEmailLabel !== 'string' || !result.contactEmailLabel.trim()) {
    throw new Error(globalThis.BerliI18n.t("THE_CRM_DID_NOT_RETURN_A_LABEL_FOR_THE_CONTACT_EMAIL_SEARCH_FIELD_PLEASE"));
  }
  const label = result.contactEmailLabel.trim();
  document.getElementById("mailArchiveMatchField").textContent = globalThis.BerliI18n.t("MATCH_AGAINST") + label + globalThis.BerliI18n.t("IN_CRM_CONTACTS");
  return label;
}
function mailReadError(error) {
  document.getElementById("mailArchiveStatus").textContent = error.message;
  if (/INVALID_SESSIONID|SESSION_EXPIRED|AUTHENTICATION_REQUIRED|Authencation required|Authentication required|HTTP(?:-Status| status) 401/i.test(error.message)) {
    sessionName = null; crmUserId = null; crmUsername = '';
    try { window.localStorage.removeItem(SESSION_STORAGE_KEY); } catch (storageError) { /* Sign-in remains available. */ }
    document.getElementById("loginStatus").textContent = globalThis.BerliI18n.t("YOUR_CRM_SESSION_HAS_EXPIRED_PLEASE_SIGN_IN_AGAIN");
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
  document.getElementById("mailArchiveMatchField").textContent = '';
  mailReadControls();
  if (!visible) return;
  const state = { item: Office.context.mailbox.item, session: sessionName, rows: [], canCreate: false };
  mailReadState = state;
  document.getElementById("mailArchiveSender").textContent = globalThis.BerliI18n.t("FROM") + (state.item.from?.emailAddress || globalThis.BerliI18n.t("NO_ADDRESS_AVAILABLE"));
  document.getElementById("mailArchiveStatus").textContent = globalThis.BerliI18n.t("CHECKING_CRM_SIGN_IN");
  try {
    const result = await mailReadRequest({ mode: 'check' }, state.session);
    if (!mailReadCurrent(state, generation)) return;
    state.contactEmailLabel = mailReadContactFieldLabel(result);
    state.canCreate = result.canCreate === true;
    document.getElementById("mailArchiveStatus").textContent = state.canCreate
      ? globalThis.BerliI18n.t("SELECT_SAVE_EMAIL_TO_CRM_TO_FIND_MATCHING_CONTACTS") : globalThis.BerliI18n.t("YOU_DO_NOT_HAVE_CRM_PERMISSION_TO_CREATE_EMAILS");
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
  document.getElementById("mailArchiveStatus").textContent = globalThis.BerliI18n.t("SEARCHING_FOR_CONTACTS_MATCHING_THE_FROM_ADDRESS");
  try {
    const from = state.item.from?.emailAddress;
    if (typeof from !== 'string' || !from.trim()) throw new Error(globalThis.BerliI18n.t("THE_EMAIL_ADDRESS_IN_THE_FROM_FIELD_IS_UNAVAILABLE"));
    const result = await mailReadRequest({ mode: 'find', from }, state.session);
    if (!mailReadCurrent(state, generation)) return;
    if (!Array.isArray(result.contacts)) throw new Error(globalThis.BerliI18n.t("THE_CRM_DID_NOT_RETURN_A_VALID_CONTACT_LIST"));
    state.contactEmailLabel = mailReadContactFieldLabel(result);
    state.from = from; state.canCreate = result.canCreate === true;
    for (const contact of result.contacts) {
      const label = document.createElement('label'); label.className = 'archive-contact';
      const checkbox = document.createElement('input'); checkbox.type = 'checkbox';
      const text = document.createElement('span');
      text.textContent = [contact.firstname, contact.lastname].filter(Boolean).join(' ').trim() || globalThis.BerliI18n.t("CONTACT_3") + contact.id;
      if (contact.company) { const company = document.createElement('small'); company.textContent = ' ' + contact.company; text.append(company); }
      label.append(checkbox, text); document.getElementById('mailArchiveContacts').append(label);
      const status = document.createElement('span'); status.className = 'notice'; text.append(status);
      checkbox.addEventListener('change', mailReadControls);
      state.rows.push({ contact, checkbox, status, done: false });
    }
    const count = state.item.attachments?.length || 0;
    document.getElementById('mailArchiveAttachmentOption').hidden = !count;
    document.getElementById('mailArchiveAttachments').checked = true;
    document.getElementById("mailArchiveAttachmentLabel").textContent = globalThis.BerliI18n.t("INCLUDE_ATTACHMENTS") + count + ')';
    document.getElementById('mailArchiveForm').hidden = !state.rows.length;
    document.getElementById("mailArchiveStatus").textContent = state.rows.length === 1
      ? globalThis.BerliI18n.t("ONE_MATCHING_CONTACT_FOUND_PLEASE_SELECT_THE_CONTACT_TO_SAVE_THE_EMAIL_T")
      : state.rows.length
        ? state.rows.length + globalThis.BerliI18n.t("MATCHING_CONTACTS_FOUND_PLEASE_SELECT_THE_CONTACTS_TO_SAVE_THE_EMAIL_TO")
        : globalThis.BerliI18n.t("NO_READABLE_CONTACTS_FOUND_WITH_THIS_ADDRESS_IN_THE_FIELD") + state.contactEmailLabel + globalThis.BerliI18n.t("CONTACT_LOOKUP_EMPTY_SUFFIX");
  } catch (error) { if (mailReadCurrent(state, generation)) mailReadError(error); }
  finally { if (generation === mailReadGeneration) { mailReadSearching = false; mailReadControls(); } }
}
function mailReadOfficeCall(target, method, args = []) {
  return new Promise((resolve, reject) => {
    if (typeof target?.[method] !== 'function') { reject(new Error(globalThis.BerliI18n.t("THIS_OUTLOOK_FEATURE_IS_NOT_AVAILABLE") + method)); return; }
    target[method](...args, result => result.status === Office.AsyncResultStatus.Succeeded
      ? resolve(result.value) : reject(new Error(result.error?.message || globalThis.BerliI18n.t("OUTLOOK_COULD_NOT_READ_THE_MESSAGE"))));
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
  if (sourceFiles.length > 100) throw new Error(globalThis.BerliI18n.t("AT_MOST_100_ATTACHMENTS_PER_EMAIL_CAN_BE_INCLUDED"));
  let total = 0;
  for (const file of sourceFiles) {
    check();
    const content = await mailReadOfficeCall(item, 'getAttachmentContentAsync', [file.id]); check();
    const format = String(content.format).toLowerCase();
    let encoded = content.content, name = file.name, type = file.contentType || 'application/octet-stream';
    if (format === 'eml') { encoded = mailReadBase64Text(content.content); if (!/\.eml$/i.test(name)) name += '.eml'; type = 'message/rfc822'; }
    else if (format === 'icalendar') { encoded = mailReadBase64Text(content.content); if (!/\.ics$/i.test(name)) name += '.ics'; type = 'text/calendar'; }
    else if (format !== 'base64') throw new Error(globalThis.BerliI18n.t("THE_ATTACHMENT") + name + globalThis.BerliI18n.t("CANNOT_BE_PROVIDED_BY_OUTLOOK_AS_A_FILE_THE_EMAIL_WAS_NOT_SAVED"));
    if (typeof encoded !== 'string' || encoded.length > 14 * 1024 * 1024) throw new Error(globalThis.BerliI18n.t("AN_ATTACHMENT_EXCEEDS_10_MB_OR_IS_INVALID"));
    total += encoded.length;
    if (total > 28 * 1024 * 1024) throw new Error(globalThis.BerliI18n.t("THE_ATTACHMENTS_EXCEED_20_MB_IN_TOTAL"));
    attachments.push({ name, type, content: encoded });
  }
  const date = item.dateTimeCreated;
  if (!date || typeof date.toISOString !== 'function') throw new Error(globalThis.BerliI18n.t("OUTLOOK_DID_NOT_RETURN_A_MESSAGE_DATE"));
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
    if (!mailReadCurrent(state, generation) || state.item.from?.emailAddress !== state.from) throw new Error(globalThis.BerliI18n.t("THE_EMAIL_OR_CRM_SESSION_HAS_CHANGED_PLEASE_START_THE_SEARCH_AGAIN"));
  };
  mailReadSaving = true; mailReadControls();
  document.getElementById("mailArchiveStatus").textContent = globalThis.BerliI18n.t("READING_EMAIL_FROM_OUTLOOK");
  try {
    check();
    const mail = await readMailForArchive(state.item, document.getElementById('mailArchiveAttachments').checked, check);
    check();
    document.getElementById("mailArchiveStatus").textContent = globalThis.BerliI18n.t("SAVING_EMAIL_TO_CRM");
    const result = await mailReadRequest({ mode: 'save', mail, contactIds: rows.map(row => row.contact.id) }, state.session);
    if (!mailReadCurrent(state, generation)) return;
    if (typeof result.emailId !== 'string' || !Array.isArray(result.contactIds)
      || rows.some(row => !result.contactIds.includes(row.contact.id))) throw new Error(globalThis.BerliI18n.t("THE_CRM_DID_NOT_FULLY_CONFIRM_THAT_THE_EMAIL_WAS_SAVED_PLEASE_CHECK_AGAI"));
    for (const row of rows) { row.done = true; row.checkbox.checked = false; row.status.textContent = globalThis.BerliI18n.t("LINKED_IN_CRM"); }
    document.getElementById("mailArchiveStatus").textContent = (result.created ? globalThis.BerliI18n.t("EMAIL_SAVED_TO_CRM") : globalThis.BerliI18n.t("EXISTING_CRM_EMAIL_REUSED"))
      + globalThis.BerliI18n.t("AND_LINKED_TO") + rows.length + (rows.length === 1 ? globalThis.BerliI18n.t("CONTACT_4") : globalThis.BerliI18n.t("ARCHIVE_CONTACT_COUNT_PLURAL"));
  } catch (error) { if (mailReadCurrent(state, generation)) mailReadError(error); }
  finally {
    mailReadSaving = false;
    if (generation === mailReadGeneration) mailReadControls();
    else if (isOutlookMessageRead() && sessionName) refreshMailReadPane();
  }
}
