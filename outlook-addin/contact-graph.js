if (typeof module !== 'undefined' && module.exports && !globalThis.BerliI18n) require('./language.js');
(function (root) {
  'use strict';
  const ORIGIN = 'https://graph.microsoft.com';
  const FIELDS = 'id,displayName,givenName,surname,emailAddresses,businessPhones,mobilePhone,jobTitle,department,personalNotes,businessAddress,homeAddress';
  class GraphError extends Error {
    constructor(message, status = 0) { super(message); this.status = status; }
  }
  function literal(value) { return String(value).replace(/'/g, "''"); }
  class GraphClient {
    constructor(tokenProvider, propertyId, transport = root.fetch.bind(root)) {
      this.tokenProvider = tokenProvider; this.propertyId = propertyId; this.transport = transport;
    }
    async request(path, options = {}) {
      const url = new URL(path, ORIGIN + '/v1.0/');
      if (url.origin !== ORIGIN || !url.pathname.startsWith('/v1.0/') || url.username || url.password || url.hash) {
        throw new GraphError(globalThis.BerliI18n.t("INVALID_MICROSOFT_GRAPH_ADDRESS"));
      }
      const token = this.tokenProvider();
      const response = await this.transport(url.href, { method: options.method || 'GET',
        headers: { Authorization: 'Bearer ' + token, Accept: 'application/json',
          'Content-Type': 'application/json', Prefer: 'IdType="ImmutableId"' },
        ...(options.body ? { body: JSON.stringify(options.body) } : {}) });
      let result;
      try { result = await response.json(); } catch (error) { throw new GraphError(globalThis.BerliI18n.t("MICROSOFT_DID_NOT_RETURN_A_VALID_RESPONSE"), response.status); }
      if (!response.ok) throw new GraphError(result.error?.message || globalThis.BerliI18n.t("MICROSOFT_REQUEST_FAILED"), response.status);
      return result;
    }
    folderPath(folder = '') {
      return folder ? '/v1.0/me/contactFolders/' + encodeURIComponent(folder) + '/contacts' : '/v1.0/me/contacts';
    }
    contactQuery() {
      return new URLSearchParams({ $select: FIELDS, $expand: "singleValueExtendedProperties($filter=id eq '" + literal(this.propertyId) + "')" });
    }
    listPage(folder = '', nextLink = '') {
      const params = this.contactQuery(); params.set('$top', '100');
      return this.request(nextLink || this.folderPath(folder) + '?' + params);
    }
    getContact(id, folder = '') {
      return this.request(this.folderPath(folder) + '/' + encodeURIComponent(id) + '?' + this.contactQuery());
    }
    async folders() {
      const folders = [], visited = new Set();
      const queue = [{ path: '/v1.0/me/contactFolders?$select=id,displayName&$top=100', prefix: '' }];
      while (queue.length) {
        const { path, prefix } = queue.shift();
        let next = path;
        const pages = new Set();
        while (next) {
          if (pages.has(next)) throw new GraphError(globalThis.BerliI18n.t("MICROSOFT_RETURNED_A_REPEATED_FOLDER_PAGE"));
          pages.add(next);
          const result = await this.request(next);
          if (!Array.isArray(result.value)) throw new GraphError(globalThis.BerliI18n.t("INVALID_MICROSOFT_FOLDER_LIST"));
          for (const folder of result.value) {
            if (!folder.id || visited.has(folder.id)) continue;
            visited.add(folder.id);
            const label = prefix + folder.displayName;
            folders.push({ id: folder.id, name: label });
            queue.push({ path: '/v1.0/me/contactFolders/' + encodeURIComponent(folder.id) + '/childFolders?$select=id,displayName&$top=100', prefix: label + ' / ' });
          }
          next = result['@odata.nextLink'] || '';
        }
      }
      return folders;
    }
    async existing(exported, folder = '') {
      const filters = ["singleValueExtendedProperties/Any(ep: ep/id eq '" + literal(this.propertyId) + "' and ep/value eq '" + literal(exported.key) + "')"];
      for (const email of exported.contact.emailAddresses || []) {
        if (email.address) filters.push("emailAddresses/any(a:a/address eq '" + literal(email.address) + "')");
      }
      for (const filter of filters) {
        const params = new URLSearchParams({ $filter: filter, $select: 'id', $top: '2' });
        const result = await this.request(this.folderPath(folder) + '?' + params);
        if (!Array.isArray(result.value)) throw new GraphError(globalThis.BerliI18n.t("INVALID_RESPONSE_WHEN_CHECKING_FOR_DUPLICATES"));
        if (result.value.length) return result.value[0];
      }
      return null;
    }
    async create(exported, folder = '') {
      const result = await this.request(this.folderPath(folder), { method: 'POST', body: exported.contact });
      if (!result || typeof result.id !== 'string' || !result.id) throw new GraphError(globalThis.BerliI18n.t("MICROSOFT_DID_NOT_UNAMBIGUOUSLY_CONFIRM_THAT_THE_CONTACT_WAS_CREATED"));
      return result;
    }
  }
  root.BerliContactGraph = { GraphClient, GraphError };
  if (typeof module !== 'undefined' && module.exports) module.exports = root.BerliContactGraph;
})(globalThis);
