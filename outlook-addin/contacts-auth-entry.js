import { PublicClientApplication } from '@azure/msal-browser';
import { broadcastResponseToMainFrame } from '@azure/msal-browser/redirect-bridge';

// Runs in an Office authentication dialog (or a same-origin browser popup).
// MSAL owns state, PKCE and token exchange; no client secret is used.
async function main() {
  const status = document.getElementById('authStatus');
  const retry = document.getElementById('authRetry');
  const params = new URLSearchParams(location.search);
  const channelKey = 'berliCRM.contacts.authChannel';
  let channel;
  let officeReady;
  const step = async (message, work, milliseconds = 20000) => {
    status.textContent = message;
    let timer;
    try {
      return await Promise.race([Promise.resolve().then(work), new Promise((resolve, reject) => {
        timer = setTimeout(() => reject(new Error(message + ' Zeitüberschreitung. Bitte erneut versuchen.')), milliseconds);
      })]);
    } finally { clearTimeout(timer); }
  };
  const showState = (state, title, message, detail = '') => {
    document.getElementById('authCard').dataset.state = state;
    document.getElementById('authTitle').textContent = title;
    status.textContent = message;
    document.getElementById('authHelp').hidden = state !== 'error';
    document.getElementById('authDetails').hidden = !detail;
    document.getElementById('authErrorDetails').textContent = detail;
    document.getElementById('authActions').hidden = state !== 'error' || !['office', 'popup'].includes(channel);
  };
  const prepareOffice = () => {
    if (!officeReady) {
      officeReady = (async () => {
        if (typeof Office === 'undefined') {
          await step('Office-Bibliothek für das Anmeldefenster wird geladen …', () => new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://appsforoffice.microsoft.com/lib/1/hosted/office.js';
            script.onload = resolve;
            script.onerror = () => reject(new Error('Die Office-Bibliothek konnte nicht geladen werden. Bitte die Verbindung zu appsforoffice.microsoft.com und mögliche Browser-Blockierungen prüfen.'));
            document.head.appendChild(script);
          }));
        }
        if (typeof Office === 'undefined' || typeof Office.onReady !== 'function') {
          throw new Error('Die Office-Bibliothek hat keine gültige Dialogschnittstelle bereitgestellt.');
        }
        // Call onReady immediately after loading Office.js. Waiting until after
        // MSAL redirects can miss Office's limited initialization window.
        return step('Verbindung zum Outlook-Anmeldefenster wird hergestellt …', () => Office.onReady());
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
    const hostInfo = params.get('_host_Info');
    if (hostInfo) restart.searchParams.set('_host_Info', hostInfo);
    location.replace(restart.href);
  });
  const send = async message => {
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
    throw new Error('Bitte die Microsoft-Anmeldung aus der berliCRM-Kontakteansicht starten.');
  };
  try {
    if (params.get('start') === '1') sessionStorage.setItem(channelKey, params.get('channel') || 'popup');
    channel = sessionStorage.getItem(channelKey);
    // Initialize the Office dialog before starting or processing authentication.
    // Plain browser popups and the silent MSAL bridge do not need Office.js.
    if (channel === 'office') await prepareOffice();
    const { response, envelope } = await step('Microsoft-Konfiguration wird vom CRM geladen …', async () => {
      const response = await fetch(new URL('rest/outlook.php', location.href), {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getContactsMicrosoftConfig' }),
      });
      return { response, envelope: await response.json() };
    });
    if (!response.ok || !envelope.success) throw new Error(envelope.payload || 'Microsoft-Konfiguration konnte nicht geladen werden.');
    const config = envelope.payload;
    if (!config.configured) throw new Error('Die Microsoft-Kontakte-Anbindung ist noch nicht eingerichtet.');
    const redirect = new URL(config.redirectUri);
    if (redirect.origin !== location.origin || redirect.pathname !== location.pathname) {
      throw new Error('Die konfigurierte Microsoft-Redirect-URI passt nicht zu dieser Seite.');
    }
    const client = new PublicClientApplication({
      auth: { clientId: config.clientId, authority: 'https://login.microsoftonline.com/' + config.tenantId,
        redirectUri: config.redirectUri, navigateToLoginRequestUrl: false },
      cache: { cacheLocation: 'sessionStorage' },
    });
    await step('Microsoft-Anmeldung wird initialisiert …', () => client.initialize());
    const result = await step('Rückmeldung von Microsoft wird verarbeitet …', () => client.handleRedirectPromise(), 60000);
    if (result) {
      if (!result.accessToken || !result.account?.homeAccountId || !result.expiresOn) throw new Error('Microsoft hat keine vollständige Kontaktanmeldung geliefert.');
      const message = { type: 'berliCRM.contacts.connected', token: result.accessToken,
        account: result.account.homeAccountId, username: result.account.username,
        expiresAt: result.expiresOn.getTime() };
      // Clear cached bearer tokens before the parent closes this dialog.
      await step('Microsoft-Anmeldung wird abgeschlossen …', () => client.clearCache());
      sessionStorage.removeItem(channelKey);
      await send(message);
      showState('success', 'Microsoft-Konto verbunden', 'Die Anmeldung war erfolgreich. Dieses Fenster kann geschlossen werden.');
    } else if (params.get('start') === '1') {
      await step('Weiterleitung zur Microsoft-Anmeldung …', () => client.loginRedirect({ scopes: ['https://graph.microsoft.com/Contacts.ReadWrite'], prompt: 'select_account' }), 60000);
    } else {
      throw new Error('Die Anmeldung ist abgelaufen. Bitte die Verbindung in der Kontakteansicht erneut starten.');
    }
  } catch (error) {
    const denied = /\baccess_denied\b/i.test(error.errorCode || error.message || '');
    showState('error', denied ? 'Zugriff nicht freigegeben' : 'Anmeldung nicht abgeschlossen',
      denied ? 'Microsoft hat den Zugriff auf deine Outlook-Kontakte nicht freigegeben. Möglicherweise wurde die Anmeldung abgebrochen oder die erforderliche Zustimmung fehlt.'
        : 'Die Verbindung mit deinem Microsoft-Konto konnte nicht hergestellt werden. Bitte versuche es erneut oder starte die Anmeldung aus der berliCRM-Kontakteansicht.',
      error.message || 'Unbekannter Anmeldefehler.');
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
