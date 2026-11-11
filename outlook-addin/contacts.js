'use strict';
globalThis.BerliI18n.initialize();
const CONTACT_API = new URL('rest/outlook.php', location.href).href;
const CONTACT_SESSION_KEY = 'berliCRM.outlook.session';
const CONTACT_MICROSOFT_SESSION_KEY = 'berliCRM.outlook.contacts.microsoft:' + CONTACT_API;
const contactState = { session: null, metadata: null, config: null, graph: null, client: null,
  crmRows: [], outlookRows: [], nextLink: '', pages: new Set(), busy: false, generation: 0, authPending: false, showMicrosoftLogin: false, selectMicrosoftAccount: false };
let contactDialog = null;
let contactPopup = null;
let contactPopupTimer = null;
let contactRelay = null;
let contactOfficeInfo = null;
let contactMicrosoftExpiryTimer = null;
let contactSessionNoticeKind = '';
let contactPreparedPopup = null;
let contactPreparedWeb = false;
const contactLaunchParams = new URLSearchParams(location.search);
const contactPreparedAt = Number(contactLaunchParams.get('preparedAuth'));
if (contactPreparedAt > 0 && Date.now() - contactPreparedAt >= 0 && Date.now() - contactPreparedAt < 120000
    && contactLaunchParams.get('outlookPlatform') === 'OfficeOnline') {
  contactPreparedWeb = true;
  try { contactPreparedPopup = window.open('', 'berliCRMContactsAuth'); } catch { /* The manual connection remains available. */ }
}
const contactOfficeReady = typeof Office !== 'undefined' ? Office.onReady() : Promise.resolve({ host: null });
contactOfficeReady.then(info => {
  contactOfficeInfo = info;
  if (!contactState.session) globalThis.BerliI18n.initialize();
});
const contactElement = id => document.getElementById(id);

function contactClosePreparedPopup() {
  const popup = contactPreparedPopup; contactPreparedPopup = null;
  if (popup) try { if (!popup.closed) popup.close(); } catch { /* It may already have been closed. */ }
}
function contactPlatform() {
  return contactOfficeInfo?.platform || (typeof Office !== 'undefined' ? Office.context?.platform : null)
    || (contactPreparedWeb ? 'OfficeOnline' : null);
}

function contactMicrosoftScope() {
  const { session, config } = contactState;
  return session && config ? JSON.stringify([CONTACT_API, session.sessionName, session.userId,
    config.clientId, config.tenantId, config.redirectUri]) : null;
}
function contactMicrosoftHintKey() {
  const { session, config } = contactState;
  return session && config ? 'berliCRM.outlook.contacts.account:' + JSON.stringify([CONTACT_API,
    session.userId, session.username, config.clientId, config.tenantId]) : null;
}
function contactForgetMicrosoft(forgetHint = false) {
  try { sessionStorage.removeItem(CONTACT_MICROSOFT_SESSION_KEY); } catch { /* Storage may be unavailable in embedded views. */ }
  const hintKey = contactMicrosoftHintKey();
  if (forgetHint && hintKey) try { localStorage.removeItem(hintKey); } catch { /* The current view still disconnects. */ }
}
function contactValidMicrosoft(message) {
  return message?.type === 'berliCRM.contacts.connected' && typeof message.token === 'string' && Boolean(message.token)
    && typeof message.account === 'string' && Boolean(message.account) && typeof message.username === 'string'
    && Number.isFinite(message.expiresAt) && message.expiresAt > Date.now() + 30000;
}
function contactCachedMicrosoft() {
  try {
    const cached = JSON.parse(sessionStorage.getItem(CONTACT_MICROSOFT_SESSION_KEY));
    if (cached && cached.scope === contactMicrosoftScope() && contactValidMicrosoft(cached.connection)) return cached.connection;
    contactForgetMicrosoft();
  } catch { contactForgetMicrosoft(); }
  return null;
}
function contactRememberMicrosoft(message) {
  const scope = contactMicrosoftScope();
  if (!scope) return;
  // Only the access token lives in this tab's session; never store it in localStorage or the CRM.
  const connection = { type: message.type, token: message.token, account: message.account,
    username: message.username, expiresAt: message.expiresAt };
  try { sessionStorage.setItem(CONTACT_MICROSOFT_SESSION_KEY, JSON.stringify({ scope, connection })); } catch { /* Keep the working in-memory connection. */ }
  const hintKey = contactMicrosoftHintKey();
  if (hintKey && message.username) try { localStorage.setItem(hintKey, message.username); } catch { /* A hint is optional. */ }
}
function contactMicrosoftLoginHint() {
  try {
    const hintKey = contactMicrosoftHintKey();
    const saved = hintKey && localStorage.getItem(hintKey);
    if (saved) return saved;
  } catch { /* Fall back to the available Outlook context. */ }
  try { return typeof Office !== 'undefined' ? Office.context?.mailbox?.userProfile?.emailAddress || '' : ''; }
  catch { return ''; }
}
function contactAutomaticErrorText(error) {
  const parts = [];
  for (const value of [error?.errorCode, error?.subError, typeof error === 'string' ? error : error?.message]) {
    if (typeof value === 'string' && value.trim() && !parts.some(part => part.includes(value.trim()))) parts.push(value.trim());
  }
  return parts.join('\n') || globalThis.BerliI18n.t('UNKNOWN_SIGN_IN_ERROR');
}
function contactSessionNotice(message = '', kind = '') {
  contactSessionNoticeKind = kind;
  contactElement('contactSessionNotice').textContent = message;
  contactElement('contactSessionNotice').hidden = !message;
}
function contactExpireCrm() {
  const message = globalThis.BerliI18n.t('CONTACT_CRM_SESSION_EXPIRED');
  contactReset(message);
  contactSessionNotice(message, 'crm');
}
function contactExpireMicrosoft(error = null) {
  const message = globalThis.BerliI18n.t('CONTACT_MICROSOFT_SESSION_EXPIRED');
  contactForgetMicrosoft();
  contactDisconnect(message);
  contactSessionNotice(message, 'microsoft');
  contactElement('contactTransferStatus').textContent = message;
  if (error?.message) {
    contactElement('contactAutoDetails').hidden = false;
    contactElement('contactAutoDetails').open = false;
    contactElement('contactAutoError').textContent = error.message;
  }
}
function contactCheckMicrosoftExpiry() {
  if (!contactState.busy && !contactState.authPending && contactState.graph
      && Number.isFinite(contactState.graph.expiresAt) && contactState.graph.expiresAt <= Date.now() + 30000) {
    contactExpireMicrosoft(); return true;
  }
  return false;
}
function contactWatchMicrosoftExpiry() {
  clearTimeout(contactMicrosoftExpiryTimer); contactMicrosoftExpiryTimer = null;
  const expiresAt = contactState.graph?.expiresAt;
  if (!Number.isFinite(expiresAt)) return;
  contactMicrosoftExpiryTimer = setTimeout(() => {
    contactMicrosoftExpiryTimer = null;
    if (!contactCheckMicrosoftExpiry() && contactState.graph) contactWatchMicrosoftExpiry();
  }, Math.min(2147483647, Math.max(1000, expiresAt - Date.now() - 30000)));
}

async function contactApi(action, data = {}, session = contactState.session) {
  const response = await fetch(CONTACT_API, { method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action, language: globalThis.BerliI18n.language, ...data, ...(session ? { sessionName: session.sessionName } : {}) }) });
  if (session && response.status === 401) {
    const error = new Error('CRM HTTP status 401'); error.crmSessionExpired = true; throw error;
  }
  const result = await response.json();
  if (!response.ok || !result.success) {
    throw new Error(typeof result.payload === 'string' ? result.payload : globalThis.BerliI18n.t("INVALID_CRM_RESPONSE"));
  }
  return result.payload;
}
function contactError(error) {
  // A Graph 401 must never be mistaken for a CRM logout by its message text.
  if (error instanceof BerliContactGraph.GraphError && error.status === 401) {
    contactExpireMicrosoft(error);
    return;
  }
  if (!(error instanceof BerliContactGraph.GraphError) && (error.crmSessionExpired || BerliContactModel.isSessionError(error.message))) {
    contactExpireCrm(); return;
  }
  contactElement("contactTransferStatus").textContent = error.message;
}
function contactControls() {
  const state = contactState;
  const locked = state.busy || state.authPending;
  contactElement('contactLogout').disabled = locked;
  contactElement('contactRetry').disabled = locked;
  contactElement('contactCancelAuth').hidden = !contactRelay;
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
  const relay = contactRelay; contactRelay = null;
  if (relay) {
    clearTimeout(relay.timer);
    if (relay.flowId) contactApi('closeContactsAuthRelay', { flowId: relay.flowId }, relay.session).catch(() => {});
  }
  if (contactDialog) { const dialog = contactDialog; contactDialog = null; dialog.close(); }
  if (contactPopup && !contactPopup.closed) contactPopup.close();
  contactPopup = null;
  clearInterval(contactPopupTimer);
  contactState.authPending = false;
}
function contactDisconnect(message = globalThis.BerliI18n.t("MICROSOFT_ACCOUNT_IS_NOT_CONNECTED")) {
  clearTimeout(contactMicrosoftExpiryTimer); contactMicrosoftExpiryTimer = null;
  contactElement('contactAutoDetails').hidden = true;
  contactElement('contactAutoDetails').open = false;
  contactElement('contactAutoError').textContent = '';
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
  contactSessionNotice();
  contactState.generation++;
  contactAuthClose();
  contactClosePreparedPopup();
  contactForgetMicrosoft(true);
  contactState.session = null; contactState.metadata = null; contactState.config = null;
  contactState.selectMicrosoftAccount = false;
  contactState.crmRows = []; contactState.busy = false;
  if (clearStored) try { localStorage.removeItem(CONTACT_SESSION_KEY); } catch (error) { /* Reset still applies in memory. */ }
  contactElement('contactCrmResults').replaceChildren();
  contactElement('contactTransferStatus').textContent = '';
  contactDisconnect();
  contactElement('contactWorkspace').hidden = true;
  contactElement('contactLoginForm').hidden = false;
  contactElement('contactLogout').hidden = true;
  contactElement('contactRetry').hidden = true;
  contactElement("contactLoginStatus").textContent = message;
  contactElement('contactAccessKey').value = '';
}
async function contactPrepare(session) {
  const generation = ++contactState.generation;
  contactState.session = session;
  contactElement('contactLoginForm').hidden = true;
  contactElement('contactLogout').hidden = false;
  contactElement('contactRetry').hidden = true;
  contactElement("contactLoginStatus").textContent = globalThis.BerliI18n.t("CHECKING_CRM_SIGN_IN");
  try {
    const preferences = await contactApi('getUserPreferences', {}, session);
    if (generation !== contactState.generation) return;
    if (!preferences || typeof preferences.language !== 'string' || !preferences.language) {
      throw new Error(globalThis.BerliI18n.t("INVALID_CRM_RESPONSE"));
    }
    globalThis.BerliI18n.setLanguage(preferences.language);
    if (contactSessionNoticeKind === 'crm') contactSessionNotice();
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
      else contactClosePreparedPopup();
    } catch (error) {
      if (generation === contactState.generation) {
        contactClosePreparedPopup();
        contactState.showMicrosoftLogin = true;
        contactElement("contactMicrosoftStatus").textContent = error.message;
      }
    }
    contactControls();
  } catch (error) {
    if (generation !== contactState.generation) return;
    contactClosePreparedPopup();
    if (error.crmSessionExpired || BerliContactModel.isSessionError(error.message)) contactExpireCrm();
    else {
      contactState.metadata = null;
      contactElement('contactWorkspace').hidden = true;
      contactElement('contactRetry').hidden = false;
      contactElement("contactLoginStatus").textContent = globalThis.BerliI18n.t("COULD_NOT_PREPARE_CONTACTS") + error.message;
      contactControls();
    }
  }
}
async function contactConnectAutomatically(generation) {
  contactElement('contactAutoDetails').hidden = true;
  contactElement('contactAutoDetails').open = false;
  contactElement('contactAutoError').textContent = '';
  contactState.showMicrosoftLogin = false;
  contactState.authPending = true;
  contactElement("contactMicrosoftStatus").textContent = globalThis.BerliI18n.t("CONNECTING_TO_MICROSOFT_AUTOMATICALLY");
  contactControls();
  let timeout;
  try {
    const cached = contactCachedMicrosoft();
    if (cached) { contactAcceptAuth(cached, generation); return; }
    if (!window.BerliContactsAuth?.connectSilently) throw new Error(globalThis.BerliI18n.t("AUTOMATIC_SIGN_IN_IS_NOT_AVAILABLE"));
    // Browser SSO does not require Office.onReady, which can stall outside an email item.
    const automatic = window.BerliContactsAuth.connectSilently(contactState.config, contactMicrosoftLoginHint());
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
    contactElement('contactAutoDetails').hidden = false;
    contactElement('contactAutoDetails').open = false;
    contactElement('contactAutoError').textContent = contactAutomaticErrorText(error) + '\n\n'
      + globalThis.BerliI18n.t('AUTOMATIC_MICROSOFT_SIGN_IN_IFRAME_LIMIT');
    contactControls();
    const platform = contactPlatform();
    if (platform === 'OfficeOnline' && /\blogin_required\b|\bAADSTS50058\b/i.test(contactAutomaticErrorText(error))) {
      // One top-level attempt can use cookies unavailable to the hidden iframe.
      // Browsers may block this automatic popup; contactConnect then keeps the manual button usable.
      await contactConnect({ automatic: true });
    } else contactClosePreparedPopup();
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
    if (result.language) globalThis.BerliI18n.setLanguage(result.language);
    const session = { username, sessionName: result.sessionName, userId: result.userId, language: globalThis.BerliI18n.language };
    try { localStorage.setItem(CONTACT_SESSION_KEY, JSON.stringify(session)); } catch (error) { /* Session remains available in this view. */ }
    await contactPrepare(session);
  } catch (error) { contactElement("contactLoginStatus").textContent = globalThis.BerliI18n.t("CONTACT_LOGIN_FAILED") + error.message; }
  finally { button.disabled = false; }
}
function contactAcceptAuth(message, generation) {
  if (generation !== contactState.generation || !contactState.authPending) return;
  if (message?.type === 'berliCRM.contacts.authError') {
    contactAuthClose();
    contactState.showMicrosoftLogin = true;
    contactElement("contactMicrosoftStatus").textContent = globalThis.BerliI18n.t("SIGN_IN_NOT_COMPLETED");
    contactElement('contactAutoDetails').hidden = false;
    contactElement('contactAutoDetails').open = false;
    contactElement('contactAutoError').textContent = globalThis.BerliI18n.t('MICROSOFT_SIGN_IN_WINDOW_ERROR', [message.message || '']);
    contactControls(); return;
  }
  if (!contactValidMicrosoft(message)) return;
  contactClosePreparedPopup();
  contactAuthClose();
  contactDisconnect();
  contactState.graph = message;
  contactRememberMicrosoft(message);
  contactState.selectMicrosoftAccount = false;
  contactState.showMicrosoftLogin = false;
  contactState.client = new BerliContactGraph.GraphClient(() => {
    if (!contactState.graph || contactState.graph.expiresAt <= Date.now() + 30000) {
      throw new BerliContactGraph.GraphError(globalThis.BerliI18n.t("YOUR_MICROSOFT_SIGN_IN_HAS_EXPIRED_PLEASE_CONNECT_AGAIN"), 401);
    }
    return contactState.graph.token;
  }, contactState.metadata.propertyId);
  contactElement("contactMicrosoftStatus").textContent = globalThis.BerliI18n.t("MICROSOFT_ACCOUNT_CONNECTED") + message.username;
  if (contactSessionNoticeKind === 'microsoft') {
    contactSessionNotice(); contactElement('contactTransferStatus').textContent = '';
  }
  contactWatchMicrosoftExpiry();
  contactControls();
}
async function contactPollAuthRelay(relay) {
  if (contactRelay !== relay || contactState.generation !== relay.generation) return;
  try {
    if (Date.now() >= relay.expiresAt) throw new Error(globalThis.BerliI18n.t('THE_SIGN_IN_HAS_EXPIRED_PLEASE_START_THE_CONNECTION_AGAIN_FROM_THE_CONTA'));
    const reply = await contactApi('pollContactsAuthRelay', { flowId: relay.flowId }, relay.session);
    if (contactRelay !== relay || contactState.generation !== relay.generation) return;
    if (reply.state === 'ready') {
      const message = await BerliAuthRelay.decrypt(relay, reply.payload);
      if (contactRelay === relay && contactState.generation === relay.generation) {
        contactAcceptAuth(message, relay.generation);
        if (contactRelay === relay) throw new Error(globalThis.BerliI18n.t('INVALID_CRM_RESPONSE'));
      }
      return;
    }
    if (reply.state !== 'pending') throw new Error(globalThis.BerliI18n.t('MICROSOFT_SIGN_IN_RELAY_UNAVAILABLE'));
    relay.timer = setTimeout(() => contactPollAuthRelay(relay), 1500);
  } catch (error) {
    if (contactRelay !== relay) return;
    if (error.crmSessionExpired || BerliContactModel.isSessionError(error.message)) { contactExpireCrm(); return; }
    contactAuthClose(); contactState.showMicrosoftLogin = true;
    contactElement('contactMicrosoftStatus').textContent = error.message; contactControls();
  }
}
async function contactStartAuthPopup(address, useRelay = false) {
  address.searchParams.set('channel', 'popup');
  const prepared = contactPreparedPopup; contactPreparedPopup = null;
  contactPopup = prepared && !prepared.closed ? prepared
    : window.open(useRelay ? 'about:blank' : address.href, 'berliCRMContactsAuth', 'width=600,height=720');
  if (!contactPopup) throw new Error(globalThis.BerliI18n.t("PLEASE_ALLOW_THE_SIGN_IN_WINDOW_IN_YOUR_BROWSER"));
  if (useRelay) {
    if (typeof BerliAuthRelay === 'undefined' || !contactState.session) throw new Error(globalThis.BerliI18n.t('MICROSOFT_SIGN_IN_RELAY_UNAVAILABLE'));
    const popup = contactPopup;
    const relay = { session: contactState.session, generation: contactState.generation };
    contactRelay = relay; contactControls();
    if (popup.document?.body) popup.document.body.textContent = globalThis.BerliI18n.t('PREPARING_SIGN_IN');
    const [flow, key] = await Promise.all([contactApi('beginContactsAuthRelay', {}, relay.session), BerliAuthRelay.createKey()]);
    Object.assign(relay, flow, { key });
    if (contactRelay !== relay || contactState.generation !== relay.generation) {
      contactApi('closeContactsAuthRelay', { flowId: relay.flowId }, relay.session).catch(() => {});
      return;
    }
    if (!BerliAuthRelay.valid(relay) || !(relay.expiresAt > Date.now()) || popup.closed) {
      throw new Error(globalThis.BerliI18n.t('MICROSOFT_SIGN_IN_RELAY_UNAVAILABLE'));
    }
    address.hash = new URLSearchParams({ relayFlow: relay.flowId, relayWrite: relay.writeKey, relayKey: key }).toString();
    popup.location.replace(address.href);
    // COOP can report a closed popup while Microsoft is still open. Use completion/cancel instead.
    void contactPollAuthRelay(relay);
    return;
  }
  contactPopupTimer = setInterval(() => {
    if (!contactPopup || contactPopup.closed) { contactAuthClose(); contactControls(); }
  }, 800);
}
async function contactConnect(options = {}) {
  if (contactState.busy || contactState.authPending || !contactState.config?.configured) return;
  const generation = contactState.generation;
  if (options.automatic === true) contactState.showMicrosoftLogin = false;
  contactState.authPending = true; contactControls();
  try {
    const address = new URL('contacts-auth.html', location.href);
    address.searchParams.set('v', '20261111.3');
    address.searchParams.set('language', globalThis.BerliI18n.language);
    address.searchParams.set('start', '1');
    address.searchParams.set('mode', options.automatic === true ? 'silent' : contactState.selectMicrosoftAccount ? 'select' : 'reuse');
    const hint = contactMicrosoftLoginHint();
    if (hint && !contactState.selectMicrosoftAccount) address.searchParams.set('login_hint', hint);
    contactElement('contactMicrosoftStatus').textContent = globalThis.BerliI18n.t(options.automatic === true ? 'CONNECTING_TO_MICROSOFT_AUTOMATICALLY' : 'PREPARING_SIGN_IN');
    const platform = contactPlatform();
    // Open during the user's click, without awaiting the Office dialog handshake.
    // The auth page's popup channel does not load or initialize Office.js.
    if (typeof Office === 'undefined' || platform === 'OfficeOnline' || contactOfficeInfo?.host === null) {
      await contactStartAuthPopup(address, platform === 'OfficeOnline');
      return;
    }
    const info = await contactOfficeReady;
    if (generation !== contactState.generation) return;
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
      await contactStartAuthPopup(address);
    }
  } catch (error) {
    contactAuthClose(); contactState.showMicrosoftLogin = true;
    if (error.crmSessionExpired || BerliContactModel.isSessionError(error.message)) { contactExpireCrm(); return; }
    contactElement("contactMicrosoftStatus").textContent = error.message; contactControls();
  }
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
async function contactJob(work, needsMicrosoft = false) {
  if (contactState.busy || contactState.authPending || !contactState.session) return;
  const expired = contactCheckMicrosoftExpiry();
  if (expired && needsMicrosoft) return;
  contactState.busy = true; contactControls();
  const snapshot = { generation: contactState.generation, session: contactState.session, graph: contactState.graph,
    client: contactState.client, folder: contactElement('contactFolder').value };
  snapshot.check = () => {
    if (snapshot.generation !== contactState.generation || snapshot.session !== contactState.session || snapshot.graph !== contactState.graph) throw new Error(globalThis.BerliI18n.t("THE_SIGNED_IN_ACCOUNT_HAS_CHANGED_PLEASE_START_THE_OPERATION_AGAIN"));
  };
  try { await work(snapshot); }
  catch (error) { if (snapshot.generation === contactState.generation) contactError(error); }
  finally {
    if (snapshot.generation === contactState.generation) {
      contactState.busy = false; contactCheckMicrosoftExpiry(); contactControls();
    }
  }
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
  }, true);
}
async function contactLoadFolders() {
  await contactJob(async job => {
    const folders = await job.client.folders(); job.check();
    contactElement('contactFolder').replaceChildren(new Option(globalThis.BerliI18n.t("DEFAULT_CONTACTS"), ''), ...folders.map(folder => new Option(folder.name, folder.id)));
    contactElement('contactFolder').value = job.folder;
  }, true);
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
  }, true);
}
function initializeContacts() {
  contactElement('contactLoginForm').addEventListener('submit', contactLogin);
  contactElement('contactLogout').addEventListener('click', () => contactReset());
  contactElement('contactRetry').addEventListener('click', () => {
    if (contactState.session) contactPrepare(contactState.session);
  });
  contactElement('contactCancelAuth').addEventListener('click', () => {
    contactState.generation++;
    contactAuthClose(); contactState.showMicrosoftLogin = true;
    contactElement('contactMicrosoftStatus').textContent = globalThis.BerliI18n.t('MICROSOFT_ACCOUNT_IS_NOT_CONNECTED');
    contactControls();
  });
  contactElement('contactConnect').addEventListener('click', contactConnect);
  contactElement('contactDisconnect').addEventListener('click', () => {
    if (contactSessionNoticeKind === 'microsoft') contactSessionNotice();
    contactForgetMicrosoft(true); contactState.selectMicrosoftAccount = true; contactDisconnect();
  });
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
  window.addEventListener('focus', contactCheckMicrosoftExpiry);
  window.addEventListener('pageshow', contactCheckMicrosoftExpiry);
  document.addEventListener?.('visibilitychange', () => {
    if (!document.hidden) contactCheckMicrosoftExpiry();
  });
  contactControls();
  try {
    const saved = JSON.parse(localStorage.getItem(CONTACT_SESSION_KEY) || 'null');
    if (saved && typeof saved.sessionName === 'string' && saved.sessionName) {
      if (saved.language) globalThis.BerliI18n.setLanguage(saved.language);
      contactElement('contactUsername').value = saved.username || '';
      contactPrepare(saved);
    } else {
      contactClosePreparedPopup();
      contactElement("contactLoginStatus").textContent = globalThis.BerliI18n.t("PLEASE_SIGN_IN_TO_CRM");
    }
  } catch (error) {
    contactClosePreparedPopup();
    contactElement("contactLoginStatus").textContent = globalThis.BerliI18n.t("PLEASE_SIGN_IN_TO_CRM");
  }
}
initializeContacts();
