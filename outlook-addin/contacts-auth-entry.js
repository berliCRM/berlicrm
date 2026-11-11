import { PublicClientApplication } from '@azure/msal-browser';
import { broadcastResponseToMainFrame } from '@azure/msal-browser/redirect-bridge';

// Runs in an Office authentication dialog (or a same-origin browser popup).
// MSAL owns state, PKCE and token exchange; no client secret is used.
async function main() {
  const status = document.getElementById('authStatus');
  const retry = document.getElementById('authRetry');
  const params = new URLSearchParams(location.search);
  const languageKey = 'berliCRM.contacts.authLanguage';
  let language = params.get('language');
  try {
    if (language) sessionStorage.setItem(languageKey, globalThis.BerliI18n.normalize(language));
    else language = sessionStorage.getItem(languageKey);
  } catch { /* Use the URL/Office/browser hint when storage is unavailable. */ }
  globalThis.BerliI18n.initialize(language);
  const channelKey = 'berliCRM.contacts.authChannel';
  const modeKey = 'berliCRM.contacts.authMode';
  const hintKey = 'berliCRM.contacts.authHint';
  let channel;
  let officeReady;
  let relay = null;
  const step = async (message, work, milliseconds = 20000) => {
    status.textContent = message;
    let timer;
    try {
      return await Promise.race([Promise.resolve().then(work), new Promise((resolve, reject) => {
        timer = setTimeout(() => reject(new Error(message + globalThis.BerliI18n.t("TIMED_OUT_PLEASE_TRY_AGAIN"))), milliseconds);
      })]);
    } finally { clearTimeout(timer); }
  };
  const showState = (state, title, message, detail = '') => {
    document.getElementById('authCard').dataset.state = state;
    document.getElementById("authTitle").textContent = title;
    status.textContent = message;
    document.getElementById('authHelp').hidden = state !== 'error';
    document.getElementById('authDetails').hidden = !detail;
    document.getElementById("authErrorDetails").textContent = detail;
    document.getElementById('authActions').hidden = state !== 'error' || !['office', 'popup'].includes(channel);
  };
  const prepareOffice = () => {
    if (!officeReady) {
      officeReady = (async () => {
        if (typeof Office === 'undefined') {
          await step(globalThis.BerliI18n.t("LOADING_THE_OFFICE_LIBRARY_FOR_THE_SIGN_IN_WINDOW"), () => new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://appsforoffice.microsoft.com/lib/1/hosted/office.js';
            script.onload = resolve;
            script.onerror = () => reject(new Error(globalThis.BerliI18n.t("COULD_NOT_LOAD_THE_OFFICE_LIBRARY_PLEASE_CHECK_ACCESS_TO_APPSFOROFFICE_M")));
            document.head.appendChild(script);
          }));
        }
        if (typeof Office === 'undefined' || typeof Office.onReady !== 'function') {
          throw new Error(globalThis.BerliI18n.t("THE_OFFICE_LIBRARY_DID_NOT_PROVIDE_A_VALID_DIALOG_INTERFACE"));
        }
        // Call onReady immediately after loading Office.js. Waiting until after
        // MSAL redirects can miss Office's limited initialization window.
        return step(globalThis.BerliI18n.t("CONNECTING_TO_THE_OUTLOOK_SIGN_IN_WINDOW"), () => Office.onReady());
      })();
    }
    return officeReady;
  };
  retry.addEventListener('click', () => {
    if (!['office', 'popup'].includes(channel)) return;
    retry.disabled = true;
    // Start a fresh flow on this same page; never replay callback codes or tokens.
    const restart = new URL(location.pathname, location.origin);
    restart.searchParams.set('start', '1');
    restart.searchParams.set('channel', channel);
    restart.searchParams.set('language', globalThis.BerliI18n.language);
    const hostInfo = params.get('_host_Info');
    if (hostInfo) restart.searchParams.set('_host_Info', hostInfo);
    location.replace(restart.href);
  });
  const send = async message => {
    if (channel === 'popup' && relay) {
      await step(globalThis.BerliI18n.t('COMPLETING_MICROSOFT_SIGN_IN'), async () => {
        const payload = await BerliAuthRelay.encrypt(relay, message);
        const response = await fetch(new URL('rest/outlook.php', location.href), {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'completeContactsAuthRelay', flowId: relay.flowId,
            writeKey: relay.writeKey, payload, language: globalThis.BerliI18n.language }),
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.payload || globalThis.BerliI18n.t('MICROSOFT_SIGN_IN_RELAY_UNAVAILABLE'));
      });
      sessionStorage.removeItem('berliCRM.contacts.authRelay');
      window.close();
      return;
    }
    if (channel === 'office') {
      await prepareOffice();
      if (Office.context.ui?.messageParent) {
        Office.context.ui.messageParent(JSON.stringify(message), { targetOrigin: location.origin });
        return;
      }
    }
    if (channel === 'popup' && window.opener) {
      window.opener.postMessage(message, location.origin);
      window.close();
      return;
    }
    throw new Error(globalThis.BerliI18n.t("PLEASE_START_MICROSOFT_SIGN_IN_FROM_THE_BERLICRM_CONTACTS_VIEW"));
  };
  try {
    if (typeof BerliAuthRelay !== 'undefined') relay = BerliAuthRelay.readContext(location, sessionStorage);
    if (params.get('start') === '1') {
      sessionStorage.setItem(channelKey, params.get('channel') || 'popup');
      sessionStorage.setItem(modeKey, params.get('mode') === 'silent' ? 'silent' : 'interactive');
      if (params.get('login_hint')) sessionStorage.setItem(hintKey, params.get('login_hint'));
      else sessionStorage.removeItem(hintKey);
    }
    channel = sessionStorage.getItem(channelKey) || (relay ? 'popup' : null);
    // Initialize the Office dialog before starting or processing authentication.
    // Plain browser popups and the silent MSAL bridge do not need Office.js.
    if (channel === 'office') await prepareOffice();
    const { response, envelope } = await step(globalThis.BerliI18n.t("LOADING_MICROSOFT_CONFIGURATION_FROM_CRM"), async () => {
      const response = await fetch(new URL('rest/outlook.php', location.href), {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getContactsMicrosoftConfig' }),
      });
      return { response, envelope: await response.json() };
    });
    if (!response.ok || !envelope.success) throw new Error(envelope.payload || globalThis.BerliI18n.t("COULD_NOT_LOAD_MICROSOFT_CONFIGURATION"));
    const config = envelope.payload;
    if (!config.configured) throw new Error(globalThis.BerliI18n.t("THE_MICROSOFT_CONTACTS_CONNECTION_HAS_NOT_YET_BEEN_CONFIGURED"));
    const redirect = new URL(config.redirectUri);
    if (redirect.origin !== location.origin || redirect.pathname !== location.pathname) {
      throw new Error(globalThis.BerliI18n.t("THE_CONFIGURED_MICROSOFT_REDIRECT_URI_DOES_NOT_MATCH_THIS_PAGE"));
    }
    const client = new PublicClientApplication({
      auth: { clientId: config.clientId, authority: 'https://login.microsoftonline.com/' + config.tenantId,
        redirectUri: config.redirectUri, navigateToLoginRequestUrl: false },
      cache: { cacheLocation: 'sessionStorage' },
    });
    await step(globalThis.BerliI18n.t("INITIALIZING_MICROSOFT_SIGN_IN"), () => client.initialize());
    let result;
    try {
      result = await step(globalThis.BerliI18n.t("PROCESSING_THE_RESPONSE_FROM_MICROSOFT"), () => client.handleRedirectPromise(), 60000);
    } catch (error) {
      if (channel !== 'popup' || sessionStorage.getItem(modeKey) !== 'silent'
          || !/\blogin_required\b|\binteraction_required\b|\bconsent_required\b|\bAADSTS50058\b/i.test(error.errorCode || error.message || '')) throw error;
      // One transition in the already open window; never retry prompt=none in a loop.
      sessionStorage.setItem(modeKey, 'interactive');
      const hint = sessionStorage.getItem(hintKey);
      await step(globalThis.BerliI18n.t('REDIRECTING_TO_MICROSOFT_SIGN_IN'), () => client.loginRedirect({
        scopes: ['https://graph.microsoft.com/Contacts.ReadWrite'], ...(hint ? { loginHint: hint } : {}),
      }), 60000);
      return;
    }
    if (result) {
      if (!result.accessToken || !result.account?.homeAccountId || !result.expiresOn) throw new Error(globalThis.BerliI18n.t("MICROSOFT_DID_NOT_RETURN_A_COMPLETE_CONTACTS_SIGN_IN"));
      const message = { type: 'berliCRM.contacts.connected', token: result.accessToken,
        account: result.account.homeAccountId, username: result.account.username,
        expiresAt: result.expiresOn.getTime() };
      // Clear cached bearer tokens before the parent closes this dialog.
      await step(globalThis.BerliI18n.t("COMPLETING_MICROSOFT_SIGN_IN"), () => client.clearCache());
      sessionStorage.removeItem(channelKey);
      sessionStorage.removeItem(modeKey);
      sessionStorage.removeItem(hintKey);
      await send(message);
      showState('success', globalThis.BerliI18n.t("MICROSOFT_ACCOUNT_CONNECTED_2"), globalThis.BerliI18n.t("SIGN_IN_WAS_SUCCESSFUL_YOU_CAN_CLOSE_THIS_WINDOW"));
    } else if (params.get('start') === '1') {
      const mode = params.get('mode');
      const hint = params.get('login_hint');
      const request = { scopes: ['https://graph.microsoft.com/Contacts.ReadWrite'],
        ...(mode === 'silent' ? { prompt: 'none' } : mode === 'reuse' ? {} : { prompt: 'select_account' }),
        ...(hint && mode !== 'select' ? { loginHint: hint } : {}) };
      await step(globalThis.BerliI18n.t("REDIRECTING_TO_MICROSOFT_SIGN_IN"), () => client.loginRedirect(request), 60000);
    } else {
      throw new Error(globalThis.BerliI18n.t("THE_SIGN_IN_HAS_EXPIRED_PLEASE_START_THE_CONNECTION_AGAIN_FROM_THE_CONTA"));
    }
  } catch (error) {
    const denied = /\baccess_denied\b/i.test(error.errorCode || error.message || '');
    showState('error', denied ? globalThis.BerliI18n.t("ACCESS_NOT_GRANTED") : globalThis.BerliI18n.t("SIGN_IN_NOT_COMPLETED"),
      denied ? globalThis.BerliI18n.t("MICROSOFT_DID_NOT_GRANT_ACCESS_TO_YOUR_OUTLOOK_CONTACTS_SIGN_IN_MAY_HAVE")
        : globalThis.BerliI18n.t("COULD_NOT_CONNECT_TO_YOUR_MICROSOFT_ACCOUNT_PLEASE_TRY_AGAIN_OR_START_SI"),
      error.message || globalThis.BerliI18n.t("UNKNOWN_SIGN_IN_ERROR"));
    // A failed Office handshake must not replace the original error on screen.
    try { await send({ type: 'berliCRM.contacts.authError', message: error.message }); } catch (sendError) { /* Error remains visible in the dialog. */ }
  }
}
// Office can host a dialog page in a frame. Only MSAL's own hidden iframe is a
// silent callback; other frames must process the interactive reply or show an
// error, including when browser storage has lost the saved dialog channel.
const explicitStart = new URLSearchParams(location.search).get('start') === '1';
let silentFrame = false;
try { silentFrame = Boolean(window.frameElement?.classList?.contains('msalSilentIframe')); } catch (error) { /* A cross-origin Office wrapper is not MSAL's same-origin hidden frame. */ }
if (!silentFrame || explicitStart) main();
else {
  // MSAL 5 requires its redirect bridge to deliver a silent iframe's response.
  // The bridge handles state and response cleanup; never run dialog login here.
  broadcastResponseToMainFrame().catch(() => { /* The requesting MSAL instance reports failure or timeout. */ });
}
