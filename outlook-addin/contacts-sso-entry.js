import { PublicClientApplication } from '@azure/msal-browser';

// Automatic attempts never open a window. Interactive authentication stays in contacts.js.
async function connectSilently(config, loginHint = '') {
  const redirect = new URL(config.redirectUri);
  const expected = new URL('contacts-auth.html', location.href);
  if (redirect.origin !== expected.origin || redirect.pathname !== expected.pathname || redirect.search || redirect.hash) {
    throw new Error(globalThis.BerliI18n.t("THE_CONFIGURED_MICROSOFT_REDIRECT_URI_DOES_NOT_MATCH_THE_CONTACTS_VIEW"));
  }
  const msalConfig = {
    auth: { clientId: config.clientId, authority: 'https://login.microsoftonline.com/' + config.tenantId,
      redirectUri: redirect.href, navigateToLoginRequestUrl: false },
    cache: { cacheLocation: 'memoryStorage' },
    system: { iframeBridgeTimeout: 8000 },
  };
  const client = new PublicClientApplication(msalConfig);
  await client.initialize();
  try {
    const request = { scopes: ['https://graph.microsoft.com/Contacts.ReadWrite'], ...(loginHint ? { loginHint } : {}) };
    const result = await client.ssoSilent(request);
    if (!result.accessToken || !result.account?.homeAccountId || !result.expiresOn || result.expiresOn.getTime() <= Date.now()) {
      throw new Error(globalThis.BerliI18n.t("MICROSOFT_DID_NOT_RETURN_A_COMPLETE_CONTACTS_SIGN_IN"));
    }
    return { type: 'berliCRM.contacts.connected', token: result.accessToken,
      account: result.account.homeAccountId, username: result.account.username,
      expiresAt: result.expiresOn.getTime() };
  } finally {
    await client.clearCache();
  }
}

window.BerliContactsAuth = { connectSilently };
