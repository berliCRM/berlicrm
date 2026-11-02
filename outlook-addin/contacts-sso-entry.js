import { PublicClientApplication } from '@azure/msal-browser';

// Silent only: interactive authentication remains in the existing Office dialog.
async function connectSilently(config, loginHint = '') {
  const redirect = new URL(config.redirectUri);
  const expected = new URL('contacts-auth.html', location.href);
  if (redirect.origin !== expected.origin || redirect.pathname !== expected.pathname || redirect.search || redirect.hash) {
    throw new Error('Die konfigurierte Microsoft-Redirect-URI passt nicht zur Kontakteansicht.');
  }
  const client = new PublicClientApplication({
    auth: { clientId: config.clientId, authority: 'https://login.microsoftonline.com/' + config.tenantId,
      redirectUri: redirect.href, navigateToLoginRequestUrl: false },
    cache: { cacheLocation: 'memoryStorage' },
    system: { iframeHashTimeout: 8000 },
  });
  await client.initialize();
  try {
    const result = await client.ssoSilent({ scopes: ['https://graph.microsoft.com/Contacts.ReadWrite'],
      ...(loginHint ? { loginHint } : {}) });
    if (!result.accessToken || !result.account?.homeAccountId || !result.expiresOn || result.expiresOn.getTime() <= Date.now()) {
      throw new Error('Microsoft hat keine vollständige Kontaktanmeldung geliefert.');
    }
    return { type: 'berliCRM.contacts.connected', token: result.accessToken,
      account: result.account.homeAccountId, username: result.account.username,
      expiresAt: result.expiresOn.getTime() };
  } finally {
    // The contacts view keeps only the returned token in memory, as with dialog login.
    await client.clearCache();
  }
}

window.BerliContactsAuth = { connectSilently };
