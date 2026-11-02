import { PublicClientApplication } from '@azure/msal-browser';

// Runs in an Office authentication dialog (or a same-origin browser popup).
// MSAL owns state, PKCE and token exchange; no client secret is used.
async function main() {
  const status = document.getElementById('authStatus');
  const retry = document.getElementById('authRetry');
  const params = new URLSearchParams(location.search);
  const channelKey = 'berliCRM.contacts.authChannel';
  let channel;
  const showState = (state, title, message, detail = '') => {
    document.getElementById('authCard').dataset.state = state;
    document.getElementById('authTitle').textContent = title;
    status.textContent = message;
    document.getElementById('authHelp').hidden = state !== 'error';
    document.getElementById('authDetails').hidden = !detail;
    document.getElementById('authErrorDetails').textContent = detail;
    document.getElementById('authActions').hidden = state !== 'error' || !['office', 'popup'].includes(channel);
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
    if (channel === 'office' && typeof Office !== 'undefined') {
      await Office.onReady();
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
    const response = await fetch(new URL('rest/outlook.php', location.href), {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'getContactsMicrosoftConfig' }),
    });
    const envelope = await response.json();
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
    await client.initialize();
    const result = await client.handleRedirectPromise();
    if (result) {
      if (!result.accessToken || !result.account?.homeAccountId || !result.expiresOn) throw new Error('Microsoft hat keine vollständige Kontaktanmeldung geliefert.');
      const message = { type: 'berliCRM.contacts.connected', token: result.accessToken,
        account: result.account.homeAccountId, username: result.account.username,
        expiresAt: result.expiresOn.getTime() };
      // Clear cached bearer tokens before the parent closes this dialog.
      await client.clearCache();
      sessionStorage.removeItem(channelKey);
      await send(message);
      showState('success', 'Microsoft-Konto verbunden', 'Die Anmeldung war erfolgreich. Dieses Fenster kann geschlossen werden.');
    } else if (params.get('start') === '1') {
      await client.loginRedirect({ scopes: ['https://graph.microsoft.com/Contacts.ReadWrite'], prompt: 'select_account' });
    } else {
      throw new Error('Die Anmeldung ist abgelaufen. Bitte die Verbindung in der Kontakteansicht erneut starten.');
    }
  } catch (error) {
    const denied = /\baccess_denied\b/i.test(error.errorCode || error.message || '');
    showState('error', denied ? 'Zugriff nicht freigegeben' : 'Anmeldung nicht abgeschlossen',
      denied ? 'Microsoft hat den Zugriff auf deine Outlook-Kontakte nicht freigegeben. Möglicherweise wurde die Anmeldung abgebrochen oder die erforderliche Zustimmung fehlt.'
        : 'Die Verbindung mit deinem Microsoft-Konto konnte nicht hergestellt werden. Bitte versuche es erneut oder starte die Anmeldung aus der berliCRM-Kontakteansicht.',
      error.message || 'Unbekannter Anmeldefehler.');
    try { await send({ type: 'berliCRM.contacts.authError', message: error.message }); } catch (sendError) { /* Error remains visible in the dialog. */ }
  }
}
// In a silent SSO iframe the requesting MSAL instance reads the response hash.
// Leave it untouched; never start another login or send a dialog message here.
if (!window.parent || window.parent === window) main();
