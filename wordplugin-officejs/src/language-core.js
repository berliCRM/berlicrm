/** Shared text-only localization runtime. CRM preferences override Office/browser hints. */
function createOfficeLanguage(catalogs) {
  let language = 'de_de';
  const normalize = value => /^de(?:[-_]|$)/i.test(String(value || '')) ? 'de_de' : 'en_us';
  const t = (key, values = []) => {
    const text = catalogs[language][key] ?? catalogs.en_us[key];
    if (typeof text !== 'string') throw new Error('Unknown language key: ' + key);
    return text.replace(/\{(\d+)\}/g, (token, index) => index < values.length ? String(values[index] ?? '') : token);
  };
  function apply(document = globalThis.document, onlyEmpty = false) {
    if (!document?.querySelectorAll) return;
    if (document.documentElement) document.documentElement.lang = language === 'de_de' ? 'de' : 'en';
    for (const element of document.querySelectorAll('[data-i18n]')) {
      if (onlyEmpty && element.textContent) continue;
      const text = t(element.getAttribute('data-i18n'));
      if (element.dataset?.idleLabel) element.dataset.idleLabel = text;
      element.textContent = text;
    }
    for (const attribute of ['placeholder', 'title', 'aria-label']) {
      for (const element of document.querySelectorAll('[data-i18n-' + attribute + ']')) {
        element.setAttribute(attribute, t(element.getAttribute('data-i18n-' + attribute)));
      }
    }
  }
  function setLanguage(value) {
    language = normalize(value);
    apply();
    return language;
  }
  function initialize(value) {
    return setLanguage(value || globalThis.Office?.context?.displayLanguage || globalThis.navigator?.language || 'en_us');
  }
  return { t, apply, initialize, setLanguage, normalize, get language() { return language; },
    get locale() { return language === 'de_de' ? 'de-DE' : 'en-US'; } };
}
