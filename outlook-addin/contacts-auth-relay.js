/** Browser-only encryption for a short-lived sign-in response; the server never gets the key. */
(function (root) {
  'use strict';
  const encode = bytes => root.btoa(String.fromCharCode(...new Uint8Array(bytes)))
    .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  const decode = value => {
    if (typeof value !== 'string' || !/^[A-Za-z0-9_-]+$/.test(value)) throw new Error('Invalid sign-in data');
    return Uint8Array.from(root.atob(value.replace(/-/g, '+').replace(/_/g, '/')), character => character.charCodeAt(0));
  };
  const importKey = key => root.crypto.subtle.importKey('raw', decode(key), 'AES-GCM', false, ['encrypt', 'decrypt']);
  const api = {
    async createKey() { return encode(await root.crypto.subtle.exportKey('raw',
      await root.crypto.subtle.generateKey({ name: 'AES-GCM', length: 256 }, true, ['encrypt', 'decrypt']))); },
    valid(context) { return Boolean(context && /^[a-f0-9]{64}$/.test(context.flowId)
      && /^[a-f0-9]{64}$/.test(context.writeKey) && /^[A-Za-z0-9_-]{43}$/.test(context.key)); },
    async encrypt(context, message) {
      if (!api.valid(context)) throw new Error('Invalid sign-in data');
      const iv = root.crypto.getRandomValues(new Uint8Array(12));
      const data = new TextEncoder().encode(JSON.stringify(message));
      if (data.length > 32768) throw new Error('Sign-in response is too large');
      return { iv: encode(iv), data: encode(await root.crypto.subtle.encrypt({ name: 'AES-GCM', iv,
        additionalData: new TextEncoder().encode(context.flowId) }, await importKey(context.key), data)) };
    },
    async decrypt(context, payload) {
      if (!api.valid(context) || typeof payload?.data !== 'string' || payload.data.length > 65536) throw new Error('Invalid sign-in data');
      const data = await root.crypto.subtle.decrypt({ name: 'AES-GCM', iv: decode(payload.iv),
        additionalData: new TextEncoder().encode(context.flowId) }, await importKey(context.key), decode(payload.data));
      return JSON.parse(new TextDecoder().decode(data));
    },
    readContext(location, storage) {
      const name = 'berliCRM.contacts.authRelay';
      const params = new URLSearchParams(location.hash.slice(1));
      if (params.has('relayFlow')) {
        const context = { flowId: params.get('relayFlow'), writeKey: params.get('relayWrite'), key: params.get('relayKey') };
        if (!api.valid(context)) throw new Error('Invalid sign-in data');
        storage.setItem(name, JSON.stringify(context));
        // Fragments aren't sent to the server. Remove these one-time handover keys before OAuth starts.
        try {
          if (root.history && typeof root.history.replaceState === 'function') {
            root.history.replaceState(null, '', location.pathname + location.search);
          } else location.hash = '';
        } catch { location.hash = ''; }
        return context;
      }
      const stored = storage.getItem(name);
      if (!stored) return null;
      const context = JSON.parse(stored);
      if (!api.valid(context)) throw new Error('Invalid sign-in data');
      return context;
    },
  };
  root.BerliAuthRelay = api;
  if (typeof module !== 'undefined' && module.exports) module.exports = api;
})(globalThis);
