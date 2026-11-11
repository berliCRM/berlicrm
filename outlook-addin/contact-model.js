if (typeof module !== 'undefined' && module.exports && !globalThis.BerliI18n) require('./language.js');
(function (root) {
  'use strict';
  function name(contact) {
    return contact.displayName || [contact.givenName, contact.surname].filter(Boolean).join(' ') || globalThis.BerliI18n.t("UNNAMED_CONTACT");
  }
  function crmValues(contact, ownerId) {
    const values = { assigned_user_id: ownerId, firstname: contact.givenName || '', lastname: contact.surname || '',
      email: contact.emailAddresses?.[0]?.address || '', secondaryemail: contact.emailAddresses?.[1]?.address || '',
      phone: contact.businessPhones?.[0] || '', mobile: contact.mobilePhone || '', title: contact.jobTitle || '',
      department: contact.department || '', description: contact.personalNotes || '' };
    for (const [key, prefix] of [['businessAddress', 'mailing'], ['homeAddress', 'other']]) {
      for (const [graph, crm] of [['street', 'street'], ['city', 'city'], ['state', 'state'], ['postalCode', 'zip'], ['countryOrRegion', 'country']]) {
        values[prefix + crm] = contact[key]?.[graph] || '';
      }
    }
    return values;
  }
  function missingFields(contact, metadata) {
    const values = crmValues(contact, metadata.ownerId);
    return metadata.fields.filter(field => field.mandatory &&
      (!(field.editable || field.name === 'assigned_user_id') || typeof values[field.name] !== 'string' || values[field.name].trim() === ''));
  }
  function isSessionError(message) {
    return /INVALID_SESSIONID|Authencation required|Authentication required|HTTP(?:-Status| status) 401/i.test(message);
  }
  const api = { name, crmValues, missingFields, isSessionError };
  root.BerliContactModel = api;
  if (typeof module !== 'undefined' && module.exports) module.exports = api;
})(globalThis);
