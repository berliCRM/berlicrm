const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
/** Supplies the browser's shared language runtime to isolated legacy UI fixtures. */
exports.localizedContext = function (values) {
  values.navigator ||= { language: 'de-DE' };
  if (values.document?.querySelectorAll) {
    const select = values.document.querySelectorAll;
    values.document.querySelectorAll = selector => selector.startsWith('[data-i18n') ? [] : select(selector);
  }
  if (values.fetch) {
    const fetch = values.fetch;
    values.fetch = async function (url, init) {
      let input;
      try { input = JSON.parse(init?.body || '{}'); } catch {}
      if (input?.action === 'getUserPreferences') return { ok: true, json: async () => ({ success: true, payload: { language: 'de_de' } }) };
      const response = await fetch.call(this, url, init);
      if (input?.action === 'login' && response?.json) {
        const json = response.json;
        response.json = async () => {
          const result = await json();
          if (result.success && result.payload) result.payload.language ||= 'de_de';
          return result;
        };
      }
      return response;
    };
  }
  const context = vm.createContext(values);
  vm.runInContext(fs.readFileSync(path.join(__dirname, '../language.js'), 'utf8'), context);
  return context;
};
