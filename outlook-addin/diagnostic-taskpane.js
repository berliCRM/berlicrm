/* global Office */
(function () {
  const t = globalThis.BerliI18n.t;
  const status = document.getElementById('officeStatus');
  document.getElementById('pageStatus').textContent = t('DIAGNOSTIC_HTML_LOADED', [new Date().toISOString()]);
  if (typeof Office === 'undefined') {
    status.textContent = t('DIAGNOSTIC_OFFICE_UNAVAILABLE');
    return;
  }
  try {
    Office.onReady(info => {
      globalThis.BerliI18n.initialize();
      status.textContent = info.host ? t('DIAGNOSTIC_OFFICE_READY', [info.host, info.platform]) : t('DIAGNOSTIC_OUTSIDE_OFFICE');
    }).catch(error => { status.textContent = t('DIAGNOSTIC_OFFICE_FAILED', [error.message]); });
  } catch (error) { status.textContent = t('DIAGNOSTIC_OFFICE_FAILED', [error.message]); }
}());
