const API_URL = new URL('rest/outlook.php', window.location.href).toString();
const SESSION_STORAGE_KEY = 'berliCRM.outlook.session';
const OUTLOOK_RECIPIENT_BATCH_SIZE = 100;

async function initializeTaskpane() {
  globalThis.BerliI18n.initialize();
  const loginButton = document.getElementById('loginButton');
  const searchButton = document.getElementById('searchButton');
  const searchVerteilerButton = document.getElementById('searchVerteilerButton');
  const eventsButton = document.getElementById('eventsButton');

  loginButton.addEventListener('click', login);
  searchButton.addEventListener('click', searchContacts);
  searchVerteilerButton.addEventListener('click', searchVerteiler);
  eventsButton.addEventListener('click', loadEvents);
  document.getElementById('contactsManagerAnchor')?.addEventListener('click', launchContactsManager);
  // Register Outlook handlers immediately, as before the language update.
  const restoring = restoreSession();
  const restoringGeneration = crmSessionGeneration;
  if (typeof initializeMailReadPane === 'function') initializeMailReadPane();
  initializeCalendarPane();
  await restoring;
  if (restoringGeneration === crmSessionGeneration) refreshTaskpaneMode();
}

let sessionName = null;
let crmUserId = null;
let crmUsername = '';
let recipientTransferInProgress = false;
let verteilerSearchGeneration = 0;
let crmSessionGeneration = 0;

function launchContactsManager(event) {
  if (event.defaultPrevented || event.button > 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey
      || !sessionName || typeof Office === 'undefined' || Office.context?.platform !== 'OfficeOnline') return;
  const address = new URL(event.currentTarget.href, window.location.href);
  const expected = new URL('contacts.html', window.location.href);
  if (address.origin !== expected.origin || address.pathname !== expected.pathname) return;
  try {
    const cached = JSON.parse(window.sessionStorage.getItem('berliCRM.outlook.contacts.microsoft:' + API_URL));
    const scope = cached && JSON.parse(cached.scope);
    if (scope?.[0] === API_URL && scope[1] === sessionName && scope[2] === crmUserId
        && cached.connection?.token && cached.connection.expiresAt > Date.now() + 30000) return;
  } catch { /* No usable tab cache; the contacts view validates the actual connection. */ }
  let popup;
  try {
    // Reserve the authentication window inside this real user click, before any fetch/navigation.
    popup = window.open('about:blank', 'berliCRMContactsAuth', 'width=600,height=720');
    if (!popup) return;
    if (popup.document?.body) popup.document.body.textContent = globalThis.BerliI18n.t('PREPARING_SIGN_IN');
    address.searchParams.set('preparedAuth', String(Date.now()));
    address.searchParams.set('outlookPlatform', 'OfficeOnline');
    event.preventDefault();
    window.location.assign(address.href);
  } catch {
    if (popup) try { popup.close(); } catch { /* Normal link navigation still works. */ }
    if (event.defaultPrevented) window.location.assign(expected.href);
  }
}

async function restoreSession() {
  const generation = ++crmSessionGeneration;
  const loginStatus = document.getElementById('loginStatus');

  try {
    const storedSession = window.localStorage.getItem(SESSION_STORAGE_KEY);
    if (!storedSession) {
      return;
    }

    const saved = JSON.parse(storedSession);
    if (!saved || typeof saved.sessionName !== 'string' || !saved.sessionName) {
      window.localStorage.removeItem(SESSION_STORAGE_KEY);
      return;
    }
    if (saved.language) globalThis.BerliI18n.setLanguage(saved.language);

    const response = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ language: globalThis.BerliI18n.language, action: 'getUserPreferences', sessionName: saved.sessionName }) });
    if (response.status === 401) {
      const error = new Error('CRM HTTP status 401'); error.crmSessionExpired = true; throw error;
    }
    const preferences = await response.json();
    if (generation !== crmSessionGeneration) return;
    if (!response.ok || !preferences.success || !preferences.payload?.language) throw new Error(preferences.payload || 'INVALID_SESSIONID');
    globalThis.BerliI18n.setLanguage(preferences.payload.language);

    sessionName = saved.sessionName;
    crmUserId = typeof saved.userId === 'string' ? saved.userId : null;
    if (typeof saved.username === 'string') {
      crmUsername = saved.username;
      document.getElementById('username').value = saved.username;
    }
    loginStatus.textContent = globalThis.BerliI18n.t("SIGNED_IN_AS") + (crmUsername || globalThis.BerliI18n.t("CRM_USER")) + '.';
  } catch (error) {
    if (generation !== crmSessionGeneration) return;
    if (error.crmSessionExpired || /INVALID_SESSIONID|Authencation required|Authentication required|HTTP(?:-Status| status) 401/i.test(error.message)) {
      sessionName = null; crmUserId = null;
      try { window.localStorage.removeItem(SESSION_STORAGE_KEY); } catch { /* The login form remains usable. */ }
      loginStatus.textContent = globalThis.BerliI18n.t('CONTACT_CRM_SESSION_EXPIRED');
      return;
    }
    loginStatus.textContent = globalThis.BerliI18n.t("COULD_NOT_READ_THE_SAVED_CRM_SIGN_IN") + error.message;
  }
}

async function login() {
  const username = document.getElementById('username').value.trim();
  const accessKey = document.getElementById('accessKey').value.trim();
  const loginStatus = document.getElementById('loginStatus');

  if (!username || !accessKey) {
    loginStatus.textContent = globalThis.BerliI18n.t("PLEASE_ENTER_YOUR_USERNAME_AND_ACCESS_KEY");
    return;
  }

  const generation = ++crmSessionGeneration;
  loginStatus.textContent = globalThis.BerliI18n.t("SIGNING_IN");
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ language: globalThis.BerliI18n.language, action: 'login', username, accessKey }),
    });

    const result = await response.json();
    if (generation !== crmSessionGeneration) return;
    if (result.success) {
      if (!result.payload || typeof result.payload.sessionName !== 'string' || !result.payload.sessionName) {
        loginStatus.textContent = globalThis.BerliI18n.t("SIGN_IN_FAILED_THE_CRM_DID_NOT_RETURN_A_VALID_SESSION_TOKEN");
        return;
      }

      sessionName = result.payload.sessionName;
      if (result.payload.language) globalThis.BerliI18n.setLanguage(result.payload.language);
      crmUserId = result.payload.userId;
      crmUsername = username;
      document.getElementById('accessKey').value = '';
      try {
        window.localStorage.setItem(SESSION_STORAGE_KEY, JSON.stringify({
          username,
          sessionName,
          userId: crmUserId,
          language: globalThis.BerliI18n.language,
        }));
        loginStatus.textContent = globalThis.BerliI18n.t("SIGNED_IN_YOUR_CRM_SESSION_WILL_BE_REUSED_WHEN_YOU_REOPEN_THE_ADD_IN");
      } catch (error) {
        loginStatus.textContent = globalThis.BerliI18n.t("SIGNED_IN_BUT_THE_SESSION_COULD_NOT_BE_SAVED") + error.message;
      }
      refreshTaskpaneMode();
    } else {
      loginStatus.textContent = globalThis.BerliI18n.t("SIGN_IN_FAILED") + result.payload;
    }
  } catch (error) {
    if (generation !== crmSessionGeneration) return;
    loginStatus.textContent = globalThis.BerliI18n.t("SIGN_IN_ERROR") + error.message;
  }
}

async function searchContacts() {
  const query = document.getElementById('searchQuery').value.trim();
  const searchResult = document.getElementById('searchResult');

  if (!sessionName) {
    searchResult.textContent = globalThis.BerliI18n.t("PLEASE_SIGN_IN_FIRST");
    return;
  }
  if (!query) {
    searchResult.textContent = globalThis.BerliI18n.t("SEARCH_TERM_IS_MISSING");
    return;
  }

  searchResult.textContent = globalThis.BerliI18n.t("SEARCHING");
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ language: globalThis.BerliI18n.language, action: 'searchContacts', query, sessionName }),
    });
    const result = await response.json();
    if (result.success) {
      renderContactSearchResults(result.payload.contacts);
    } else {
      searchResult.textContent = globalThis.BerliI18n.t("SEARCH_FAILED") + result.payload;
    }
  } catch (error) {
    searchResult.textContent = globalThis.BerliI18n.t("ERROR") + error.message;
  }
}

async function searchVerteiler() {
  const generation = ++verteilerSearchGeneration;
  const query = document.getElementById('verteilerQuery').value.trim();
  const searchResult = document.getElementById('verteilerResult');

  if (!sessionName || !crmUserId) {
    searchResult.textContent = globalThis.BerliI18n.t("PLEASE_SIGN_IN_TO_CRM_AGAIN_TO_SEARCH_YOUR_DISTRIBUTION_LISTS");
    return;
  }
  if (!query) {
    searchResult.textContent = globalThis.BerliI18n.t("SEARCH_TERM_IS_MISSING");
    return;
  }

  searchResult.textContent = globalThis.BerliI18n.t("SEARCHING");
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ language: globalThis.BerliI18n.language, action: 'searchVerteiler', query, sessionName, userId: crmUserId }),
    });
    const result = await response.json();
    if (generation !== verteilerSearchGeneration) {
      return;
    }
    if (result.success) {
      renderVerteilerSearchResults(result.payload.verteilers);
    } else {
      searchResult.textContent = globalThis.BerliI18n.t("SEARCH_FAILED") + result.payload;
    }
  } catch (error) {
    if (generation !== verteilerSearchGeneration) {
      return;
    }
    searchResult.textContent = globalThis.BerliI18n.t("ERROR") + error.message;
  }
}

async function addVerteilerToOutlook(verteiler, status = document.getElementById('recipientStatus')) {
  if (!sessionName) {
    status.textContent = globalThis.BerliI18n.t("PLEASE_SIGN_IN_FIRST");
    return;
  }

  status.textContent = globalThis.BerliI18n.t("LOADING_DISTRIBUTION_LIST_CONTACTS");
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
          language: globalThis.BerliI18n.language,
          action: 'getVerteilerContacts',
        verteilerId: verteiler.id,
        sessionName,
      }),
    });
    const result = await response.json();
    if (!result.success) {
      status.textContent = globalThis.BerliI18n.t("COULD_NOT_LOAD_DISTRIBUTION_LIST") + result.payload;
      return;
    }

    await addContactsToOutlook(result.payload.contacts, status);
  } catch (error) {
    status.textContent = globalThis.BerliI18n.t("ERROR") + error.message;
  }
}

async function addContactsToOutlook(records, status = document.getElementById('recipientStatus')) {
  if (recipientTransferInProgress) {
    status.textContent = globalThis.BerliI18n.t("RECIPIENTS_ARE_ALREADY_BEING_ADDED_PLEASE_WAIT_UNTIL_THE_OPERATION_FINIS");
    return;
  }
  const item = Office.context && Office.context.mailbox && Office.context.mailbox.item;
  const field = document.getElementById('recipientField').value;
  const recipientCollection = getOutlookRecipientCollection();
  if (!recipientCollection) {
    status.textContent = globalThis.BerliI18n.t("PLEASE_OPEN_AN_EMAIL_DRAFT_IN_OUTLOOK_AND_TRY_AGAIN_THERE");
    return;
  }

  const contacts = Array.isArray(records) ? records : [];
  const recipients = [];
  const seenAddresses = new Set();
  let missingEmailCount = 0;
  contacts.forEach(record => {
    const email = record && typeof record.email === 'string' ? record.email.trim() : '';
    if (!email) {
      missingEmailCount += 1;
      return;
    }

    const normalizedEmail = email.toLowerCase();
    if (seenAddresses.has(normalizedEmail)) {
      return;
    }
    seenAddresses.add(normalizedEmail);
    recipients.push({
      displayName: [record.firstname, record.lastname].filter(Boolean).join(' ').trim(),
      emailAddress: email,
    });
  });

  if (recipients.length === 0) {
    status.textContent = globalThis.BerliI18n.t("NO_EMAIL_ADDRESSES_FOUND_IN_THE_CONTACT_OR_DISTRIBUTION_LIST");
    return;
  }

  recipientTransferInProgress = true;
  let addedCount = 0;
  let pendingCount = recipients.length;
  try {
    status.textContent = globalThis.BerliI18n.t("CHECKING_RECIPIENT_COUNT_AND_MAILBOX_LIMIT");
    const [recipientLimit, existingFields] = await Promise.all([
      getConfiguredRecipientLimit(),
      Promise.all(['to', 'cc', 'bcc'].map(name => getOutlookRecipients(item[name]))),
    ]);
    const existingCount = existingFields.reduce((total, entries) => total + entries.length, 0);
    const targetIndex = ['to', 'cc', 'bcc'].indexOf(field);
    const existingAddresses = new Set(existingFields[targetIndex]
      .map(recipient => typeof recipient.emailAddress === 'string' ? recipient.emailAddress.trim().toLowerCase() : '')
      .filter(Boolean));
    const newRecipients = recipients.filter(recipient => !existingAddresses.has(recipient.emailAddress.toLowerCase()));
    const alreadyPresentCount = recipients.length - newRecipients.length;
    pendingCount = newRecipients.length;

    if (existingCount + newRecipients.length > recipientLimit) {
      status.textContent = globalThis.BerliI18n.t("RECIPIENT_LIMIT_EXCEEDED_ALREADY") + existingCount
        + globalThis.BerliI18n.t("RECIPIENTS_IN_TO_CC_AND_BCC_ADDING") + newRecipients.length
        + globalThis.BerliI18n.t("TOTAL") + (existingCount + newRecipients.length) + globalThis.BerliI18n.t("THE_MAXIMUM_ALLOWED_IS")
        + recipientLimit + globalThis.BerliI18n.t("NO_RECIPIENTS_WERE_ADDED");
      return;
    }
    if (newRecipients.length === 0) {
      status.textContent = globalThis.BerliI18n.t("ALL_EMAIL_ADDRESSES_ARE_ALREADY_PRESENT_IN_THE_SELECTED_OUTLOOK_FIELD");
      return;
    }

    const fieldLabel = globalThis.BerliI18n.t({ to: 'TO', cc: 'CC', bcc: 'BCC' }[field]);
    for (let offset = 0; offset < newRecipients.length; offset += OUTLOOK_RECIPIENT_BATCH_SIZE) {
      if (offset > 0) {
        const currentFields = await Promise.all(['to', 'cc', 'bcc'].map(name => getOutlookRecipients(item[name])));
        const currentCount = currentFields.reduce((total, entries) => total + entries.length, 0);
        if (currentCount + newRecipients.length - offset > recipientLimit) {
          throw new Error(globalThis.BerliI18n.t("THE_RECIPIENT_LIMIT_OF") + recipientLimit
            + globalThis.BerliI18n.t("WOULD_BE_EXCEEDED_BECAUSE_OTHER_RECIPIENTS_WERE_ADDED_IN_THE_MEANTIME"));
        }
      }
      if (Office.context.mailbox.item !== item) {
        throw new Error(globalThis.BerliI18n.t("THE_OUTLOOK_DRAFT_HAS_CHANGED"));
      }
      const batch = newRecipients.slice(offset, offset + OUTLOOK_RECIPIENT_BATCH_SIZE);
      status.textContent = addedCount + globalThis.BerliI18n.t("OF") + newRecipients.length
        + globalThis.BerliI18n.t("RECIPIENTS_ADDED_TRANSFERRING_THE_NEXT") + batch.length + globalThis.BerliI18n.t("TRANSFER_PENDING_SUFFIX");
      await addOutlookRecipients(recipientCollection, batch);
      addedCount += batch.length;
    }
    const duplicateCount = contacts.length - missingEmailCount - recipients.length;
    status.textContent = addedCount + globalThis.BerliI18n.t("RECIPIENTS_ADDED_TO_THE_OUTLOOK_FIELD") + fieldLabel + globalThis.BerliI18n.t("RECIPIENT_FIELD_ADDED_SUFFIX")
      + (missingEmailCount > 0 ? ' ' + missingEmailCount + globalThis.BerliI18n.t("CONTACTS_WITHOUT_AN_EMAIL_ADDRESS_SKIPPED") : '')
      + (duplicateCount > 0 ? ' ' + duplicateCount + globalThis.BerliI18n.t("DUPLICATE_EMAIL_ADDRESSES_COMBINED") : '')
      + (alreadyPresentCount > 0 ? ' ' + alreadyPresentCount + globalThis.BerliI18n.t("EXISTING_ADDRESSES_SKIPPED") : '');
  } catch (error) {
    status.textContent = globalThis.BerliI18n.t("ADDING_RECIPIENTS_STOPPED") + error.message
      + ' ' + addedCount + globalThis.BerliI18n.t("OF") + pendingCount + globalThis.BerliI18n.t("NEW_RECIPIENTS_WERE_CONFIRMED_AS_ADDED")
      + globalThis.BerliI18n.t("PLEASE_CHECK_THE_RECIPIENT_FIELDS_BEFORE_TRYING_AGAIN");
  } finally {
    recipientTransferInProgress = false;
  }
}

async function getConfiguredRecipientLimit() {
  const response = await fetch(API_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ language: globalThis.BerliI18n.language, action: 'getRecipientSettings', sessionName }),
  });
  const result = await response.json();
  if (!response.ok || !result.success) {
    throw new Error(globalThis.BerliI18n.t("COULD_NOT_LOAD_THE_RECIPIENT_LIMIT")
      + (typeof result.payload === 'string' ? result.payload : 'HTTP ' + response.status));
  }
  const limit = result.payload && result.payload.recipientLimit;
  if (!Number.isInteger(limit) || limit < 1 || limit > 1000) {
    throw new Error(globalThis.BerliI18n.t("THE_CONFIGURED_RECIPIENT_LIMIT_IS_INVALID"));
  }
  return limit;
}

function getOutlookRecipients(collection) {
  return new Promise((resolve, reject) => {
    if (!collection || typeof collection.getAsync !== 'function') {
      reject(new Error(globalThis.BerliI18n.t("THE_EXISTING_OUTLOOK_RECIPIENTS_CANNOT_BE_COUNTED")));
      return;
    }
    collection.getAsync(result => {
      if (result.status === Office.AsyncResultStatus.Succeeded && Array.isArray(result.value)) {
        resolve(result.value);
      } else {
        reject(new Error(globalThis.BerliI18n.t("THE_EXISTING_OUTLOOK_RECIPIENTS_CANNOT_BE_COUNTED_2")
          + (result.error ? result.error.message : globalThis.BerliI18n.t("INVALID_OUTLOOK_RESPONSE"))));
      }
    });
  });
}

function getOutlookRecipientCollection() {
  const item = Office.context && Office.context.mailbox && Office.context.mailbox.item;
  if (!item) {
    return null;
  }
  const field = document.getElementById('recipientField').value;
  const collection = item[field];
  return collection && typeof collection.addAsync === 'function' ? collection : null;
}

function addOutlookRecipients(collection, recipients) {
  return new Promise((resolve, reject) => {
    collection.addAsync(recipients, result => {
      if (result.status === Office.AsyncResultStatus.Succeeded) {
        resolve();
      } else {
        reject(new Error(result.error ? result.error.message : globalThis.BerliI18n.t("UNKNOWN_OUTLOOK_ERROR")));
      }
    });
  });
}

async function loadEvents() {
  const eventsResult = document.getElementById('eventsResult');

  if (!sessionName) {
    eventsResult.textContent = globalThis.BerliI18n.t("PLEASE_SIGN_IN_FIRST");
    return;
  }
  eventsResult.textContent = globalThis.BerliI18n.t("LOADING_APPOINTMENTS");

  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ language: globalThis.BerliI18n.language, action: 'listEvents', sessionName }),
    });
    const result = await response.json();
    if (result.success) {
      renderEvents(result.payload.events, result.payload.display);
    } else {
      eventsResult.textContent = globalThis.BerliI18n.t("ERROR_LOADING_DATA") + result.payload;
    }
  } catch (error) {
    eventsResult.textContent = globalThis.BerliI18n.t("ERROR") + error.message;
  }
}

function renderContactSearchResults(records) {
  const container = document.getElementById('searchResult');
  if (!Array.isArray(records) || records.length === 0) {
    container.textContent = globalThis.BerliI18n.t("NO_CONTACTS_FOUND");
    return;
  }
  container.innerHTML = '';

  records.forEach(record => {
    const item = document.createElement('div');
    item.className = 'record';

    const title = document.createElement('strong');
    const name = [record.firstname, record.lastname].filter(Boolean).join(' ').trim();
    title.textContent = name || record.accountname || record.email || record.id || globalThis.BerliI18n.t("CONTACT");
    const header = document.createElement('div');
    header.className = 'record-header';
    if (record.email) {
      const addButton = document.createElement('button');
      addButton.type = 'button';
      addButton.textContent = globalThis.BerliI18n.t("ADD_TO_OUTLOOK");
      addButton.className = 'button button-compact';
      addButton.setAttribute('aria-label', title.textContent + globalThis.BerliI18n.t("ADD_TO_OUTLOOK_ARIA_SUFFIX"));
      addButton.addEventListener('click', () => addContactsToOutlook([record]));
      header.appendChild(addButton);
    }
    header.appendChild(title);
    item.appendChild(header);

    const lines = [];
    if (record.email) {
      lines.push(globalThis.BerliI18n.t('CONTACT_EMAIL_LABEL') + record.email);
    }
    if (record.phone) {
      lines.push(globalThis.BerliI18n.t("PHONE") + record.phone);
    }
    if (record.mobile) {
      lines.push(globalThis.BerliI18n.t("MOBILE") + record.mobile);
    }

    const details = document.createElement('div');
    details.className = 'record-details';
    details.textContent = lines.join(' | ');
    item.appendChild(details);

    if (!record.email) {
      const noEmail = document.createElement('div');
      noEmail.className = 'notice';
      noEmail.textContent = globalThis.BerliI18n.t("NO_EMAIL_ADDRESS_IS_STORED");
      item.appendChild(noEmail);
    }

    container.appendChild(item);
  });
}

function renderVerteilerSearchResults(records) {
  const generation = ++verteilerSearchGeneration;
  const container = document.getElementById('verteilerResult');
  if (!Array.isArray(records) || records.length === 0) {
    container.textContent = globalThis.BerliI18n.t("NO_DISTRIBUTION_LISTS_FOUND");
    return;
  }
  container.innerHTML = '';
  const countEntries = [];

  records.forEach(record => {
    const item = document.createElement('div');
    item.className = 'record';
    const title = document.createElement('strong');
    const verteilerName = record.verteilername || record.id || globalThis.BerliI18n.t("DISTRIBUTION_LIST");
    title.textContent = verteilerName;
    const count = document.createElement('span');
    count.className = 'record-count';
    count.textContent = globalThis.BerliI18n.t("LOADING_COUNT");
    title.appendChild(count);
    countEntries.push({ record, element: count });
    const header = document.createElement('div');
    header.className = 'record-header';

    const status = document.createElement('div');
    status.className = 'notice';
    status.hidden = true;
    status.setAttribute('role', 'status');

    const addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.textContent = globalThis.BerliI18n.t("ADD_TO_OUTLOOK");
    addButton.className = 'button button-compact';
    addButton.setAttribute('aria-label', globalThis.BerliI18n.t("DISTRIBUTION_LIST_CONTACTS_FROM") + verteilerName + globalThis.BerliI18n.t("ADD_TO_OUTLOOK_ARIA_SUFFIX"));
    addButton.addEventListener('click', async () => {
      addButton.disabled = true;
      status.hidden = false;
      try {
        await addVerteilerToOutlook(record, status);
      } finally {
        addButton.disabled = false;
      }
    });
    header.appendChild(addButton);
    header.appendChild(title);
    item.appendChild(header);
    item.appendChild(status);
    container.appendChild(item);
  });
  return loadVerteilerContactCounts(countEntries, generation);
}

async function loadVerteilerContactCounts(entries, generation) {
  let nextIndex = 0;
  async function worker() {
    while (nextIndex < entries.length && generation === verteilerSearchGeneration) {
      const entry = entries[nextIndex++];
      try {
        const response = await fetch(API_URL, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ language: globalThis.BerliI18n.language, action: 'countVerteilerContacts', verteilerId: entry.record.id, sessionName }),
        });
        const result = await response.json();
        if (generation !== verteilerSearchGeneration) {
          return;
        }
        const count = result.payload && result.payload.emailContactCount;
        if (!response.ok || !result.success || !Number.isSafeInteger(count) || count < 0) {
          throw new Error(typeof result.payload === 'string' ? result.payload : globalThis.BerliI18n.t("INVALID_RESPONSE_WHEN_COUNTING_CONTACTS"));
        }
        entry.element.textContent = ' (' + count + (count === 1 ? globalThis.BerliI18n.t("EMAIL_CONTACT_COUNT_SINGULAR") : globalThis.BerliI18n.t("CONTACTS")) + globalThis.BerliI18n.t("WITH_EMAIL");
      } catch (error) {
        if (generation !== verteilerSearchGeneration) {
          return;
        }
        entry.element.textContent = globalThis.BerliI18n.t("COUNT_UNAVAILABLE");
        entry.element.title = error.message;
      }
    }
  }
  // Keep the search usable while counting without starting a request for every row at once.
  await Promise.all(Array.from({ length: Math.min(2, entries.length) }, () => worker()));
}

function renderEvents(records, display) {
  const container = document.getElementById('eventsResult');
  if (!Array.isArray(records) || records.length === 0) {
    container.textContent = globalThis.BerliI18n.t("NO_DATA_FOUND");
    return;
  }
  if (!display || !display.fieldLabels || typeof display.dateFormat !== 'string') {
    throw new Error(globalThis.BerliI18n.t("THE_CRM_FIELD_LABELS_OR_YOUR_PREFERRED_DATE_FORMAT_ARE_MISSING"));
  }
  container.innerHTML = '';
  records.forEach(record => {
    const item = document.createElement('div');
    item.className = 'record';
    const title = document.createElement('strong');
    title.textContent = record.subject || record.id || globalThis.BerliI18n.t("APPOINTMENT");
    item.appendChild(title);
    const lines = [];
    for (const key of ['location', 'date_start', 'due_date']) {
      if (record[key]) {
        const label = display.fieldLabels[key];
        if (typeof label !== 'string' || !label) {
          throw new Error(globalThis.BerliI18n.t("A_CRM_FIELD_LABEL_FOR_AN_APPOINTMENT_FIELD_IS_MISSING"));
        }
        const value = key === 'date_start' || key === 'due_date'
          ? formatCrmDate(record[key], display.dateFormat) : record[key];
        if (value !== '') {
          lines.push(label + ': ' + value);
        }
      }
    }
    item.appendChild(document.createTextNode(lines.join(' | ')));
    container.appendChild(item);
  });
}

function formatCrmDate(value, dateFormat) {
  // Date-only CRM values must not pass through UTC/local-time conversion.
  if (!value || value === '0000-00-00') {
    return '';
  }
  const match = typeof value === 'string' && /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
  if (!match) {
    throw new Error(globalThis.BerliI18n.t("AN_APPOINTMENT_DATE_IS_INVALID"));
  }
  const year = Number(match[1]);
  const month = Number(match[2]);
  const day = Number(match[3]);
  const leapYear = year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0);
  const monthDays = [31, leapYear ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
  if (year === 0 || month < 1 || month > 12 || day < 1 || day > monthDays[month - 1]) {
    throw new Error(globalThis.BerliI18n.t("AN_APPOINTMENT_DATE_IS_INVALID"));
  }
  const tokens = typeof dateFormat === 'string' && dateFormat.match(/yyyy|mm|dd/g);
  if (!/^(yyyy|mm|dd)[./-](yyyy|mm|dd)[./-](yyyy|mm|dd)$/.test(dateFormat)
      || !tokens || new Set(tokens).size !== 3) {
    throw new Error(globalThis.BerliI18n.t("YOUR_PREFERRED_CRM_DATE_FORMAT_IS_NOT_SUPPORTED"));
  }
  const parts = { yyyy: match[1], mm: match[2], dd: match[3] };
  return dateFormat.replace(/yyyy|mm|dd/g, token => parts[token]);
}

Office.onReady(initializeTaskpane);
