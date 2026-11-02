(function () {
  'use strict';
  const root = document.getElementById('outlookConnectionCheck');
  if (!root || root.dataset.bound) return;
  root.dataset.bound = 'true';
  const button = document.getElementById('outlookCheckConnection');
  const cancel = document.getElementById('outlookCancelConnection');
  const status = document.getElementById('outlookConnectionStatus');
  const labels = root.dataset;
  const unavailable = button.disabled;
  let popup = null, poll = null, controller = null, attempt = 0;

  function show(message, kind) {
    status.textContent = message;
    status.className = 'alert alert-' + kind;
    status.hidden = false;
  }
  function finish(message, kind) {
    attempt++;
    const oldPopup = popup;
    popup = null;
    clearInterval(poll); poll = null;
    if (controller) { controller.abort(); controller = null; }
    button.disabled = unavailable;
    cancel.hidden = true;
    try { if (oldPopup && !oldPopup.closed) oldPopup.close(); } catch (error) { /* Always release local controls. */ }
    show(message, kind);
  }
  button.addEventListener('click', () => {
    if (button.disabled) return;
    const current = ++attempt;
    button.disabled = true;
    cancel.hidden = false;
    show(labels.signingIn, 'info');
    try {
      const address = new URL(labels.authUrl, location.href);
      if (address.origin !== location.origin || !address.pathname.endsWith('/outlook-addin/contacts-auth.html')) throw new Error(labels.badReply);
      address.searchParams.set('start', '1');
      address.searchParams.set('channel', 'popup');
      // Open synchronously from the user's click, before making asynchronous requests.
      popup = window.open(address.href, '_blank', 'width=640,height=780');
      if (!popup) throw new Error(labels.blocked);
      poll = setInterval(() => {
        if (current !== attempt) return;
        try { if (!popup || popup.closed) finish(labels.closed, 'info'); }
        catch (error) { finish(labels.closed, 'info'); }
      }, 600);
    } catch (error) { finish(labels.failed + ' ' + error.message, 'error'); }
  });
  cancel.addEventListener('click', () => finish(labels.cancelled, 'info'));
  window.addEventListener('message', async event => {
    if (!popup || event.source !== popup || event.origin !== location.origin) return;
    const current = attempt;
    try {
      const message = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
      if (message?.type === 'berliCRM.contacts.authError') throw new Error(message.message || labels.badReply);
      if (message?.type !== 'berliCRM.contacts.connected' || typeof message.token !== 'string' || !message.token
          || !Number.isFinite(message.expiresAt) || message.expiresAt <= Date.now()) throw new Error(labels.badReply);
      const authWindow = popup;
      popup = null;
      clearInterval(poll); poll = null;
      try { authWindow.close(); } catch (error) { /* Contact access can still be checked. */ }
      show(labels.reading, 'info');
      controller = new AbortController();
      const response = await fetch('https://graph.microsoft.com/v1.0/me/contacts?$top=1&$select=id', {
        method: 'GET', credentials: 'omit', cache: 'no-store', referrerPolicy: 'no-referrer', signal: controller.signal,
        headers: { Authorization: 'Bearer ' + message.token, Accept: 'application/json' },
      });
      if (current !== attempt) return;
      if (!response.ok) throw new Error((response.status === 401 || response.status === 403 ? labels.denied : labels.httpError) + ' (HTTP ' + response.status + ').');
      const result = await response.json();
      if (current !== attempt) return;
      if (!Array.isArray(result?.value)) throw new Error(labels.badReply);
      finish(labels.success, 'success');
    } catch (error) {
      if (current === attempt) finish(labels.failed + ' ' + error.message, 'error');
    }
  });
})();
