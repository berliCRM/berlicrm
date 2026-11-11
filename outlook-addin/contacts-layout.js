/** Responsive contacts inside the existing Outlook task pane; no additional app or host SDK. */
(function (root) {
  'use strict';
  function createContactsLayout(view) {
    const element = id => view.document.getElementById(id);
    const tabs = [
      { key: 'outlook', button: element('contactShowOutlook'), panel: element('contactOutlookSection'), count: element('contactOutlookCount') },
      { key: 'crm', button: element('contactShowCrm'), panel: element('contactCrmSection'), count: element('contactCrmCount') },
    ];
    const media = view.matchMedia?.('(min-width: 880px)');
    let selected = 'outlook';
    function select(key, focus = false) {
      const current = tabs.find(tab => tab.key === key);
      if (!current) return;
      selected = key;
      const wide = Boolean(media?.matches);
      element('contactViewTabs').hidden = wide;
      for (const tab of tabs) {
        const active = tab.key === selected;
        tab.button.setAttribute('aria-selected', String(active));
        tab.button.tabIndex = active ? 0 : -1;
        tab.panel.hidden = !wide && !active;
        if (wide) {
          for (const attribute of ['role', 'aria-labelledby', 'tabindex']) tab.panel.removeAttribute(attribute);
        } else {
          tab.panel.setAttribute('role', 'tabpanel');
          tab.panel.setAttribute('aria-labelledby', tab.button.id);
          tab.panel.setAttribute('tabindex', '0');
        }
      }
      if (focus) current.button.focus();
    }
    for (const [index, tab] of tabs.entries()) {
      tab.button.addEventListener('click', () => select(tab.key));
      tab.button.addEventListener('keydown', event => {
        let next;
        if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') next = (index + 1) % tabs.length;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = tabs.length - 1;
        else return;
        event.preventDefault(); select(tabs[next].key, true);
      });
    }
    media?.addEventListener?.('change', () => select(selected));
    select(selected);
    return {
      select,
      setCounts(counts) {
        for (const tab of tabs) {
          const count = counts[tab.key];
          tab.count.hidden = !Number.isSafeInteger(count) || count <= 0;
          tab.count.textContent = tab.count.hidden ? '' : String(count);
        }
      },
      setConnection(connected) { element('contactConnectionOptions').open = !connected; },
    };
  }
  if (typeof module !== 'undefined' && module.exports) module.exports = { createContactsLayout };
  if (root.document?.getElementById('contactViewTabs')) root.BerliContactsLayout = createContactsLayout(root);
})(globalThis);
