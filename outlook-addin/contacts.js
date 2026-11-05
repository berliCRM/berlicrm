'use strict';
globalThis.BerliI18n.initialize();
const CONTACT_API = new URL('rest/outlook.php', location.href).href;
const CONTACT_SESSION_KEY = 'berliCRM.outlook.session';
const contactState = { session: null, metadata: null, config: null, graph: null, client: null,
  crmRows: [], outlookRows: [], nextLink: '', pages: new Set(), busy: false, generation: 0, authPending: false, showMicrosoftLogin: false };
let contactDialog = null;
let contactPopup = null;
let contactPopupTimer = null;
const contactOfficeReady = typeof Office !== 'undefined' ? Office.onReady() : Promise.resolve({ host: null });
contactOfficeReady.then(() => { if (!contactState.session) globalThis.BerliI18n.initialize(); });
const contactElement = id => document.getElementById(id);

async function contactApi(action, data = {}, session = contactState.session) {
  const response = await fetch(CONTACT_API, { method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action, language: globalThis.BerliI18n.language, ...data, ...(session ? { sessionName: session.sessionName } : {}) }) });
  const result = await response.json();
  if (!response.ok || !result.success) throw new Error(typeof result.payload === 'string' ? result.payload : globalThis.BerliI18n.t("INVALID_CRM_RESPONSE"));
  return result.payload;
}
function contactError(error) {
  if (BerliContactModel.isSessionError(error.message)) {
    contactReset(globalThis.BerliI18n.t("YOUR_CRM_SESSION_HAS_EXPIRED_PLEASE_SIGN_IN_AGAIN"));
    return;
  }
  if (error instanceof BerliContactGraph.GraphError && error.status === 401) contactDisconnect(globalThis.BerliI18n.t("YOUR_MICROSOFT_SIGN_IN_HAS_EXPIRED_PLEASE_CONNECT_AGAIN"));
  contactElement("contactTransferStatus").textContent = error.message;
}
function contactControls() {
  const state = contactState;
  const locked = state.busy || state.authPending;
  contactElement('contactLogout').disabled = locked;
  contactElement('contactConnect').disabled = locked || !state.config?.configured;
  contactElement('contactMicrosoftLogin').hidden = !state.showMicrosoftLogin || Boolean(state.graph);
  contactElement('contactDisconnect').hidden = !state.graph;
  contactElement('contactDisconnect').disabled = locked;
  for (const id of ['contactFolder', 'contactLoadFolders', 'contactLoadOutlook']) contactElement(id).disabled = locked || !state.graph;
  contactElement('contactMoreOutlook').hidden = !state.nextLink;
  contactElement('contactMoreOutlook').disabled = locked || !state.graph;
  contactElement('contactExport').disabled = locked || !state.graph || !state.crmRows.some(row => row.checkbox.checked && !row.invalid && !row.done);
  contactElement('contactImport').disabled = locked || !state.graph || !state.metadata?.canCreate || !state.outlookRows.some(row => row.checkbox.checked && !row.invalid && !row.done);
  for (const id of ['contactSelectCrm', 'contactSelectOutlook', 'contactSearch']) contactElement(id).disabled = locked;
  contactElement('contactSearchForm').querySelector('button').disabled = locked;
  for (const row of [...state.crmRows, ...state.outlookRows]) row.checkbox.disabled = locked || row.invalid || row.done;
}
function contactAuthClose() {
  if (contactDialog) { const dialog = contactDialog; contactDialog = null; dialog.close(); }
  if (contactPopup && !contactPopup.closed) contactPopup.close();
  contactPopup = null;
  clearInterval(contactPopupTimer);
  contactState.authPending = false;
}
function contactDisconnect(message = globalThis.BerliI18n.t("MICROSOFT_ACCOUNT_IS_NOT_CONNECTED")) {
  contactState.showMicrosoftLogin = true;
  contactState.graph = null; contactState.client = null;
  contactState.outlookRows = []; contactState.nextLink = ''; contactState.pages.clear();
  contactElement('contactOutlookResults').replaceChildren();
  contactElement('contactFolder').replaceChildren(new Option(globalThis.BerliI18n.t("DEFAULT_CONTACTS"), ''));
  for (const row of contactState.crmRows) { row.done = false; row.checkbox.checked = false; row.status.textContent = ''; }
  contactElement("contactMicrosoftStatus").textContent = message;
  contactControls();
}
function contactReset(message = globalThis.BerliI18n.t("PLEASE_SIGN_IN_TO_CRM"), clearStored = true) {
  contactState.generation++;
  contactAuthClose();
  contactState.session = null; contactState.metadata = null; contactState.config = null;
  contactState.crmRows = []; contactState.busy = false;
  if (clearStored) try { localStorage.removeItem(CONTACT_SESSION_KEY); } catch (error) { /* Reset still applies in memory. */ }
  contactElement('contactCrmResults').replaceChildren();
  contactDisconnect();
  contactElement('contactWorkspace').hidden = true;
  contactElement('contactLoginForm').hidden = false;
  contactElement('contactLogout').hidden = true;
  contactElement("contactLoginStatus").textContent = message;
  contactElement('contactAccessKey').value = '';
}
async function contactPrepare(session) {
  const generation = ++contactState.generation;
  contactState.session = session;
  contactElement("contactLoginStatus").textContent = globalThis.BerliI18n.t("CHECKING_CRM_SIGN_IN");
  try {
    const preferences = await contactApi('getUserPreferences', {}, session);
    if (generation !== contactState.generation) return;
    globalThis.BerliI18n.setLanguage(preferences.language);
    const metadata = await contactApi('contactTransfer', { transfer: { mode: 'describe' } }, session);
    if (generation !== contactState.generation) return;
    contactState.metadata = metadata;
    contactElement('contactLoginForm').hidden = true;
    contactElement('contactLogout').hidden = false;
    contactElement('contactWorkspace').hidden = false;
    contactElement("contactLoginStatus").textContent = globalThis.BerliI18n.t("SIGNED_IN_AS") + (session.username || globalThis.BerliI18n.t("CRM_USER")) + '.';
    const required = metadata.fields.filter(field => field.mandatory && field.name !== 'assigned_user_id').map(field => field.label);
    contactElement("contactRequiredFields").textContent = globalThis.BerliI18n.t("REQUIRED_CRM_FIELDS") + (required.join(', ') || globalThis.BerliI18n.t("NO_ADDITIONAL_FIELDS")) + globalThis.BerliI18n.t("THE_SIGNED_IN_CRM_USER_IS_ASSIGNED_AS_OWNER");
    if (!metadata.canCreate) contactElement("contactRequiredFields").textContent += globalThis.BerliI18n.t("YOU_DO_NOT_HAVE_PERMISSION_TO_CREATE_CRM_CONTACTS");
    try {
      const config = await contactApi('getContactsMicrosoftConfig', {}, null);
      if (generation !== contactState.generation) return;
      contactState.config = config;
      contactState.showMicrosoftLogin = !config.configured;
      contactElement("contactMicrosoftStatus").textContent = config.configured ? globalThis.BerliI18n.t("MICROSOFT_ACCOUNT_IS_NOT_CONNECTED")
        : globalThis.BerliI18n.t("THE_MICROSOFT_CONTACTS_CONNECTION_STILL_NEEDS_TO_BE_CONFIGURED_CLIENT_ID");
      if (config.configured) await contactConnectAutomatically(generation);
    } catch (error) {
      if (generation === contactState.generation) {
        contactState.showMicrosoftLogin = true;
        contactElement("contactMicrosoftStatus").textContent = error.message;
      }
    }
    contactControls();
  } catch (error) {
    if (generation !== contactState.generation) return;
    if (BerliContactModel.isSessionError(error.message)) contactReset(globalThis.BerliI18n.t("YOUR_CRM_SESSION_HAS_EXPIRED_PLEASE_SIGN_IN_AGAIN"));
    else {
      contactState.session = null;
      contactElement("contactLoginStatus").textContent = globalThis.BerliI18n.t("COULD_NOT_PREPARE_CONTACTS") + error.message;
    }
  }
}
async function contactConnectAutomatically(generation) {
  contactState.showMicrosoftLogin = false;
  contactState.authPending = true;
  contactElement("contactMicrosoftStatus").textContent = globalThis.BerliI18n.t("CONNECTING_TO_MICROSOFT_AUTOMATICALLY");
  contactControls();
  let timeout;
  try {
    const automatic = (async () => {
      const info = await contactOfficeReady;
      if (generation !== contactState.generation) return;
      if (!window.BerliContactsAuth?.connectSilently) throw new Error(globalThis.BerliI18n.t("AUTOMATIC_SIGN_IN_IS_NOT_AVAILABLE"));
      const hint = info.host ? Office.context.mailbox?.userProfile?.emailAddress || '' : '';
      return window.BerliContactsAuth.connectSilently(contactState.config, hint);
    })();
    const result = await Promise.race([automatic, new Promise((resolve, reject) => {
      timeout = setTimeout(() => reject(new Error(globalThis.BerliI18n.t("AUTOMATIC_SIGN_IN_TOOK_TOO_LONG"))), 12000);
    })]);
    if (generation !== contactState.generation) return;
    contactAcceptAuth(result, generation);
    if (contactState.authPending) throw new Error(globalThis.BerliI18n.t("INVALID_MICROSOFT_SIGN_IN_RESPONSE"));
  } catch (error) {
    if (generation !== contactState.generation) return;
    contactAuthClose();
    contactState.showMicrosoftLogin = true;
    contactElement("contactMicrosoftStatus").textContent = globalThis.BerliI18n.t("COULD_NOT_CONNECT_AUTOMATICALLY_PLEASE_SELECT_CONNECT_TO_MICROSOFT_TO_SI");
    contactControls();
  } finally { clearTimeout(timeout); }
}
async function contactLogin(event) {
  event.preventDefault();
  const button = event.currentTarget.querySelector('button'); button.disabled = true;
  try {
    const username = contactElement('contactUsername').value.trim();
    const result = await contactApi('login', { username, accessKey: contactElement('contactAccessKey').value }, null);
    if (!result?.sessionName) throw new Error(globalThis.BerliI18n.t("THE_CRM_DID_NOT_RETURN_A_VALID_SESSION"));
    contactElement('contactAccessKey').value = '';
    const session = { username, sessionName: result.sessionName, userId: result.userId };
    try { localStorage.setItem(CONTACT_SESSION_KEY, JSON.stringify(session)); } catch (error) { /* Session remains available in this view. */ }
    await contactPrepare(session);
  } catch (error) { contactElement("contactLoginStatus").textContent = globalThis.BerliI18n.t("CONTACT_LOGIN_FAILED") + error.message; }
  finally { button.disabled = false; }
}
function contactAcceptAuth(message, generation) {
  if (generation !== contactState.generation || !contactState.authPending) return;
  if (message.type === 'berliCRM.contacts.authError') {
    contactAuthClose();
    contactState.showMicrosoftLogin = true;
    contactElement("contactMicrosoftStatus").textContent = globalThis.BerliI18n.t("MICROSOFT_SIGN_IN_FAILED") + message.message;
    contactControls(); return;
  }
  if (message.type !== 'berliCRM.contacts.connected' || typeof message.token !== 'string' || !message.token
      || typeof message.account !== 'string' || !message.account || !(message.expiresAt > Date.now())) return;
  contactAuthClose();
  contactDisconnect();
  contactState.graph = message;
  contactState.showMicrosoftLogin = false;
  contactState.client = new BerliContactGraph.GraphClient(() => {
    if (!contactState.graph || contactState.graph.expiresAt <= Date.now() + 30000) {
      throw new BerliContactGraph.GraphError(globalThis.BerliI18n.t("YOUR_MICROSOFT_SIGN_IN_HAS_EXPIRED_PLEASE_CONNECT_AGAIN"), 401);
    }
    return contactState.graph.token;
  }, contactState.metadata.propertyId);
  contactElement("contactMicrosoftStatus").textContent = globalThis.BerliI18n.t("MICROSOFT_ACCOUNT_CONNECTED") + message.username;
  contactControls();
}
async function contactConnect() {
  if (contactState.busy || contactState.authPending || !contactState.config?.configured) return;
  const generation = contactState.generation;
  contactState.authPending = true; contactControls();
  try {
    const info = await contactOfficeReady;
    if (generation !== contactState.generation) return;
    const address = new URL('contacts-auth.html', location.href);
    address.searchParams.set('language', globalThis.BerliI18n.language);
    address.searchParams.set('start', '1');
    if (info.host && Office.context.ui?.displayDialogAsync) {
      address.searchParams.set('channel', 'office');
      Office.context.ui.displayDialogAsync(address.href, { height: 65, width: 45, displayInIframe: false }, result => {
        if (generation !== contactState.generation) { if (result.value) result.value.close(); return; }
        if (result.status !== Office.AsyncResultStatus.Succeeded) {
          contactState.authPending = false;
          contactElement("contactMicrosoftStatus").textContent = globalThis.BerliI18n.t("COULD_NOT_OPEN_SIGN_IN_WINDOW") + result.error.message;
          contactControls(); return;
        }
        const dialog = result.value;
        contactDialog = dialog;
        dialog.addEventHandler(Office.EventType.DialogMessageReceived, event => {
          if (contactDialog !== dialog) return;
          if (event.origin && event.origin !== location.origin) return;
          try { contactAcceptAuth(JSON.parse(event.message), generation); } catch (error) { contactElement("contactMicrosoftStatus").textContent = globalThis.BerliI18n.t("INVALID_RESPONSE_FROM_THE_SIGN_IN_WINDOW"); }
        });
        dialog.addEventHandler(Office.EventType.DialogEventReceived, () => {
          if (contactDialog !== dialog) return;
          contactDialog = null; contactState.authPending = false; contactControls();
        });
      });
    } else {
      address.searchParams.set('channel', 'popup');
      contactPopup = window.open(address.href, 'berliCRMContactsAuth', 'width=600,height=720');
      if (!contactPopup) throw new Error(globalThis.BerliI18n.t("PLEASE_ALLOW_THE_SIGN_IN_WINDOW_IN_YOUR_BROWSER"));
      contactPopupTimer = setInterval(() => {
        if (!contactPopup || contactPopup.closed) { contactAuthClose(); contactControls(); }
      }, 800);
    }
  } catch (error) { contactAuthClose(); contactElement("contactMicrosoftStatus").textContent = error.message; contactControls(); }
}
function contactRow(container, record, source, metadata) {
  const element = document.createElement('div'); element.className = 'record';
  const label = document.createElement('label');
  const checkbox = document.createElement('input'); checkbox.type = 'checkbox';
  const title = document.createElement('span');
  title.textContent = source === "outlook" ? BerliContactModel.name(record) : [record.firstname, record.lastname].filter(Boolean).join(' ');
  label.append(checkbox, title); element.append(label);
  const details = document.createElement('p'); details.textContent = source === "outlook"
    ? (record.emailAddresses || []).map(item => item.address).filter(Boolean).join(', ') : (record.email || globalThis.BerliI18n.t("NO_EMAIL_ADDRESS"));
  element.append(details);
  const status = document.createElement('p'); status.className = 'record-status'; element.append(status);
  const missing = source === 'outlook' ? BerliContactModel.missingFields(record, metadata) : [];
  const row = { record, checkbox, status, invalid: missing.length > 0, done: false };
  if (missing.length) { checkbox.disabled = true; status.classList.add('invalid'); status.textContent = globalThis.BerliI18n.t("NOT_IMPORTED_MISSING_REQUIRED_FIELDS") + missing.map(field => field.label).join(', '); }
  checkbox.addEventListener('change', contactControls);
  container.append(element);
  return row;
}
async function contactJob(work) {
  if (contactState.busy || contactState.authPending || !contactState.session) return;
  contactState.busy = true; contactControls();
  const snapshot = { generation: contactState.generation, session: contactState.session, graph: contactState.graph,
    client: contactState.client, folder: contactElement('contactFolder').value };
  snapshot.check = () => {
    if (snapshot.generation !== contactState.generation || snapshot.session !== contactState.session || snapshot.graph !== contactState.graph) throw new Error(globalThis.BerliI18n.t("THE_SIGNED_IN_ACCOUNT_HAS_CHANGED_PLEASE_START_THE_OPERATION_AGAIN"));
  };
  try { await work(snapshot); } catch (error) { contactError(error); }
  finally { if (snapshot.generation === contactState.generation) { contactState.busy = false; contactControls(); } }
}
async function contactSearch(event) {
  event.preventDefault();
  const query = contactElement('contactSearch').value.trim();
  if (!query) return;
  await contactJob(async job => {
    const result = await contactApi('searchContacts', { query }, job.session); job.check();
    contactElement('contactCrmResults').replaceChildren();
    contactState.crmRows = result.contacts.map(record => contactRow(contactElement('contactCrmResults'), record, 'crm'));
    contactElement("contactTransferStatus").textContent = result.contacts.length + globalThis.BerliI18n.t("CRM_MATCHES_AT_MOST_50_PER_SEARCH");
  });
}
async function contactLoadOutlook(more = false) {
  await contactJob(async job => {
    if (!job.client) throw new Error(globalThis.BerliI18n.t("PLEASE_CONNECT_TO_MICROSOFT_FIRST"));
    const next = more ? contactState.nextLink : '';
    if (more && (!next || contactState.pages.has(next))) throw new Error(globalThis.BerliI18n.t("THIS_CONTACT_PAGE_HAS_ALREADY_BEEN_LOADED"));
    const result = await job.client.listPage(job.folder, next); job.check();
    if (!Array.isArray(result.value)) throw new Error(globalThis.BerliI18n.t("MICROSOFT_DID_NOT_RETURN_A_CONTACT_LIST"));
    if (!more) { contactState.outlookRows = []; contactState.pages.clear(); contactElement('contactOutlookResults').replaceChildren(); }
    if (next) contactState.pages.add(next);
    const ids = new Set(contactState.outlookRows.map(row => row.record.id));
    for (const record of result.value) {
      if (!record.id || ids.has(record.id)) continue;
      ids.add(record.id);
      contactState.outlookRows.push(contactRow(contactElement('contactOutlookResults'), record, 'outlook', contactState.metadata));
    }
    contactState.nextLink = result['@odata.nextLink'] || '';
    contactElement("contactTransferStatus").textContent = contactState.outlookRows.length + globalThis.BerliI18n.t("OUTLOOK_CONTACTS_LOADED") + contactState.outlookRows.filter(row => row.invalid).length + globalThis.BerliI18n.t("WITH_MISSING_REQUIRED_CRM_FIELDS");
  });
}
async function contactLoadFolders() {
  await contactJob(async job => {
    const folders = await job.client.folders(); job.check();
    contactElement('contactFolder').replaceChildren(new Option(globalThis.BerliI18n.t("DEFAULT_CONTACTS"), ''), ...folders.map(folder => new Option(folder.name, folder.id)));
    contactElement('contactFolder').value = job.folder;
  });
}
async function contactTransfer(direction) {
  await contactJob(async job => {
    if (!job.client) throw new Error(globalThis.BerliI18n.t("PLEASE_CONNECT_TO_MICROSOFT_FIRST"));
    const rows = (direction === 'import' ? contactState.outlookRows : contactState.crmRows).filter(row => row.checkbox.checked && !row.invalid && !row.done);
    let created = 0, skipped = 0, failed = 0;
    for (let index = 0; index < rows.length; index++) {
      job.check();
      const row = rows[index];
      row.status.textContent = globalThis.BerliI18n.t("CHECKING");
      try {
        if (direction === 'import') {
          const contact = await job.client.getContact(row.record.id, job.folder); job.check();
          const result = await contactApi('contactTransfer', { transfer: { mode: 'import', contact, account: job.graph.account } }, job.session); job.check();
          if (result.status === 'created') { created++; row.status.textContent = globalThis.BerliI18n.t("CREATED_IN_CRM"); row.done = true; }
          else {
            skipped++;
            row.status.textContent = result.status === "skipped_missing_fields"
              ? globalThis.BerliI18n.t("SKIPPED_MISSING_REQUIRED_FIELDS") + result.missingFields.map(field => field.label).join(', ')
              : result.message || globalThis.BerliI18n.t("SKIPPED_CONTACT_ALREADY_EXISTS_IN_CRM");
            row.done = result.status === 'skipped_existing';
          }
        } else {
          const exported = await contactApi('contactTransfer', { transfer: { mode: 'export', recordId: row.record.id } }, job.session); job.check();
          const pendingKey = 'berliCRM.contacts.pending.' + job.graph.account + '.' + exported.key;
          const existing = await job.client.existing(exported, job.folder); job.check();
          if (existing) {
            localStorage.removeItem(pendingKey); skipped++; row.status.textContent = globalThis.BerliI18n.t("SKIPPED_CONTACT_ALREADY_EXISTS_IN_THE_OUTLOOK_FOLDER"); row.done = true;
          } else {
            if (localStorage.getItem(pendingKey)) throw new Error(globalThis.BerliI18n.t("AN_EARLIER_CONTACT_CREATION_IN_OUTLOOK_WAS_NOT_UNAMBIGUOUSLY_CONFIRMED_P"));
            localStorage.setItem(pendingKey, 'pending');
            try {
              await job.client.create(exported, job.folder);
              localStorage.removeItem(pendingKey);
            } catch (error) {
              if (error.status >= 400 && error.status < 500) localStorage.removeItem(pendingKey);
              throw error;
            }
            job.check(); created++; row.status.textContent = globalThis.BerliI18n.t("CREATED_AS_AN_OUTLOOK_CONTACT"); row.done = true;
          }
        }
        row.checkbox.checked = false;
      } catch (error) {
        failed++; row.status.textContent = globalThis.BerliI18n.t("ERROR") + error.message;
        if (BerliContactModel.isSessionError(error.message) || error.status === 401) throw error;
      }
      contactElement("contactTransferStatus").textContent = (index + 1) + '/' + rows.length + ': ' + created + globalThis.BerliI18n.t("CREATED") + skipped + globalThis.BerliI18n.t("SKIPPED") + failed + globalThis.BerliI18n.t("FAILED");
    }
  });
}
function initializeContacts() {
  contactElement('contactLoginForm').addEventListener('submit', contactLogin);
  contactElement('contactLogout').addEventListener('click', () => contactReset());
  contactElement('contactConnect').addEventListener('click', contactConnect);
  contactElement('contactDisconnect').addEventListener('click', () => contactDisconnect());
  contactElement('contactSearchForm').addEventListener('submit', contactSearch);
  contactElement('contactLoadOutlook').addEventListener('click', () => contactLoadOutlook());
  contactElement('contactMoreOutlook').addEventListener('click', () => contactLoadOutlook(true));
  contactElement('contactLoadFolders').addEventListener('click', contactLoadFolders);
  contactElement('contactExport').addEventListener('click', () => contactTransfer('export'));
  contactElement('contactImport').addEventListener('click', () => contactTransfer('import'));
  for (const [id, key] of [['contactSelectCrm', 'crmRows'], ['contactSelectOutlook', 'outlookRows']]) {
    contactElement(id).addEventListener('click', () => { for (const row of contactState[key]) if (!row.invalid && !row.done) row.checkbox.checked = true; contactControls(); });
  }
  contactElement('contactFolder').addEventListener('change', () => {
    contactState.outlookRows = []; contactState.nextLink = ''; contactState.pages.clear();
    contactElement('contactOutlookResults').replaceChildren();
    for (const row of contactState.crmRows) { row.done = false; row.checkbox.checked = false; row.status.textContent = ''; }
    contactControls();
  });
  window.addEventListener('message', event => {
    if (event.origin === location.origin && event.source === contactPopup && contactPopup) contactAcceptAuth(event.data || {}, contactState.generation);
  });
  window.addEventListener('storage', event => {
    if (event.key === CONTACT_SESSION_KEY && contactState.session) contactReset(globalThis.BerliI18n.t("THE_CRM_SIGN_IN_CHANGED_IN_ANOTHER_VIEW_PLEASE_SIGN_IN_AGAIN_HERE"), false);
  });
  contactControls();
  try {
    const saved = JSON.parse(localStorage.getItem(CONTACT_SESSION_KEY) || 'null');
    if (saved && typeof saved.sessionName === 'string' && saved.sessionName) {
      contactElement('contactUsername').value = saved.username || '';
      contactPrepare(saved);
    } else contactElement("contactLoginStatus").textContent = globalThis.BerliI18n.t("PLEASE_SIGN_IN_TO_CRM");
  } catch (error) { contactElement("contactLoginStatus").textContent = globalThis.BerliI18n.t("PLEASE_SIGN_IN_TO_CRM"); }
}
initializeContacts();
