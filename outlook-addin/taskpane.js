const API_URL = new URL('rest/outlook.php', window.location.href).toString();
const SESSION_STORAGE_KEY = 'berliCRM.outlook.session';
const OUTLOOK_RECIPIENT_BATCH_SIZE = 100;

function initializeTaskpane() {
  const loginButton = document.getElementById('loginButton');
  const searchButton = document.getElementById('searchButton');
  const searchVerteilerButton = document.getElementById('searchVerteilerButton');
  const eventsButton = document.getElementById('eventsButton');

  loginButton.addEventListener('click', login);
  searchButton.addEventListener('click', searchContacts);
  searchVerteilerButton.addEventListener('click', searchVerteiler);
  eventsButton.addEventListener('click', loadEvents);
  restoreSession();
  if (typeof initializeMailReadPane === 'function') initializeMailReadPane();
  initializeCalendarPane();
}

let sessionName = null;
let crmUserId = null;
let crmUsername = '';
let recipientTransferInProgress = false;
let verteilerSearchGeneration = 0;

function restoreSession() {
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

    sessionName = saved.sessionName;
    crmUserId = typeof saved.userId === 'string' ? saved.userId : null;
    if (typeof saved.username === 'string') {
      crmUsername = saved.username;
      document.getElementById('username').value = saved.username;
    }
    loginStatus.textContent = 'Angemeldet als ' + (crmUsername || 'CRM-Nutzer') + '.';
  } catch (error) {
    loginStatus.textContent = 'Gespeicherte CRM-Anmeldung konnte nicht gelesen werden: ' + error.message;
  }
}

async function login() {
  const username = document.getElementById('username').value.trim();
  const accessKey = document.getElementById('accessKey').value.trim();
  const loginStatus = document.getElementById('loginStatus');

  if (!username || !accessKey) {
    loginStatus.textContent = 'Bitte Benutzername und Access Key eingeben.';
    return;
  }

  loginStatus.textContent = 'Anmeldung läuft...';
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'login', username, accessKey }),
    });

    const result = await response.json();
    if (result.success) {
      if (!result.payload || typeof result.payload.sessionName !== 'string' || !result.payload.sessionName) {
        loginStatus.textContent = 'Login fehlgeschlagen: Das CRM hat kein gültiges Sitzungstoken zurückgegeben.';
        return;
      }

      sessionName = result.payload.sessionName;
      crmUserId = result.payload.userId;
      crmUsername = username;
      document.getElementById('accessKey').value = '';
      try {
        window.localStorage.setItem(SESSION_STORAGE_KEY, JSON.stringify({
          username,
          sessionName,
          userId: crmUserId,
        }));
        loginStatus.textContent = 'Angemeldet. CRM-Sitzung wird beim erneuten Öffnen wiederverwendet.';
      } catch (error) {
        loginStatus.textContent = 'Angemeldet, aber die Sitzung konnte nicht dauerhaft gespeichert werden: ' + error.message;
      }
      refreshTaskpaneMode();
    } else {
      loginStatus.textContent = 'Login fehlgeschlagen: ' + result.payload;
    }
  } catch (error) {
    loginStatus.textContent = 'Login-Fehler: ' + error.message;
  }
}

async function searchContacts() {
  const query = document.getElementById('searchQuery').value.trim();
  const searchResult = document.getElementById('searchResult');

  if (!sessionName) {
    searchResult.textContent = 'Bitte zuerst anmelden.';
    return;
  }
  if (!query) {
    searchResult.textContent = 'Suchbegriff fehlt.';
    return;
  }

  searchResult.textContent = 'Suche läuft...';
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'searchContacts', query, sessionName }),
    });
    const result = await response.json();
    if (result.success) {
      renderContactSearchResults(result.payload.contacts);
    } else {
      searchResult.textContent = 'Suche fehlgeschlagen: ' + result.payload;
    }
  } catch (error) {
    searchResult.textContent = 'Fehler: ' + error.message;
  }
}

async function searchVerteiler() {
  const generation = ++verteilerSearchGeneration;
  const query = document.getElementById('verteilerQuery').value.trim();
  const searchResult = document.getElementById('verteilerResult');

  if (!sessionName || !crmUserId) {
    searchResult.textContent = 'Bitte erneut am CRM anmelden, um eigene Verteiler zu suchen.';
    return;
  }
  if (!query) {
    searchResult.textContent = 'Suchbegriff fehlt.';
    return;
  }

  searchResult.textContent = 'Suche läuft...';
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'searchVerteiler', query, sessionName, userId: crmUserId }),
    });
    const result = await response.json();
    if (generation !== verteilerSearchGeneration) {
      return;
    }
    if (result.success) {
      renderVerteilerSearchResults(result.payload.verteilers);
    } else {
      searchResult.textContent = 'Suche fehlgeschlagen: ' + result.payload;
    }
  } catch (error) {
    if (generation !== verteilerSearchGeneration) {
      return;
    }
    searchResult.textContent = 'Fehler: ' + error.message;
  }
}

async function addVerteilerToOutlook(verteiler, status = document.getElementById('recipientStatus')) {
  if (!sessionName) {
    status.textContent = 'Bitte zuerst anmelden.';
    return;
  }

  status.textContent = 'Verteilerkontakte werden geladen...';
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'getVerteilerContacts',
        verteilerId: verteiler.id,
        sessionName,
      }),
    });
    const result = await response.json();
    if (!result.success) {
      status.textContent = 'Verteiler konnte nicht geladen werden: ' + result.payload;
      return;
    }

    await addContactsToOutlook(result.payload.contacts, status);
  } catch (error) {
    status.textContent = 'Fehler: ' + error.message;
  }
}

async function addContactsToOutlook(records, status = document.getElementById('recipientStatus')) {
  if (recipientTransferInProgress) {
    status.textContent = 'Es werden bereits Empfänger hinzugefügt. Bitte warte, bis der Vorgang abgeschlossen ist.';
    return;
  }
  const item = Office.context && Office.context.mailbox && Office.context.mailbox.item;
  const field = document.getElementById('recipientField').value;
  const recipientCollection = getOutlookRecipientCollection();
  if (!recipientCollection) {
    status.textContent = 'Bitte öffne einen E-Mail-Entwurf in Outlook und versuche es dort erneut.';
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
    status.textContent = 'Keine E-Mail-Adressen im Kontakt oder Verteiler gefunden.';
    return;
  }

  recipientTransferInProgress = true;
  let addedCount = 0;
  let pendingCount = recipients.length;
  try {
    status.textContent = 'Empfängerzahl und Postfachlimit werden geprüft...';
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
      status.textContent = 'Empfängerlimit überschritten: Bereits ' + existingCount
        + ' Empfänger in An, Cc und Bcc; zusätzlich ' + newRecipients.length
        + ', insgesamt ' + (existingCount + newRecipients.length) + '. Erlaubt sind höchstens '
        + recipientLimit + '. Es wurden keine Empfänger hinzugefügt.';
      return;
    }
    if (newRecipients.length === 0) {
      status.textContent = 'Alle E-Mail-Adressen sind bereits im ausgewählten Outlook-Feld vorhanden.';
      return;
    }

    const fieldLabel = { to: 'An', cc: 'Cc', bcc: 'Bcc' }[field];
    for (let offset = 0; offset < newRecipients.length; offset += OUTLOOK_RECIPIENT_BATCH_SIZE) {
      if (offset > 0) {
        const currentFields = await Promise.all(['to', 'cc', 'bcc'].map(name => getOutlookRecipients(item[name])));
        const currentCount = currentFields.reduce((total, entries) => total + entries.length, 0);
        if (currentCount + newRecipients.length - offset > recipientLimit) {
          throw new Error('Empfängerlimit von ' + recipientLimit
            + ' würde durch zwischenzeitlich hinzugefügte Empfänger überschritten.');
        }
      }
      if (Office.context.mailbox.item !== item) {
        throw new Error('Der Outlook-Entwurf wurde gewechselt.');
      }
      const batch = newRecipients.slice(offset, offset + OUTLOOK_RECIPIENT_BATCH_SIZE);
      status.textContent = addedCount + ' von ' + newRecipients.length
        + ' Empfängern hinzugefügt. Nächste ' + batch.length + ' werden übertragen...';
      await addOutlookRecipients(recipientCollection, batch);
      addedCount += batch.length;
    }
    const duplicateCount = contacts.length - missingEmailCount - recipients.length;
    status.textContent = addedCount + ' Empfänger zum Outlook-Feld „' + fieldLabel + '“ hinzugefügt.'
      + (missingEmailCount > 0 ? ' ' + missingEmailCount + ' Kontakte ohne E-Mail-Adresse übersprungen.' : '')
      + (duplicateCount > 0 ? ' ' + duplicateCount + ' doppelte E-Mail-Adressen zusammengefasst.' : '')
      + (alreadyPresentCount > 0 ? ' ' + alreadyPresentCount + ' bereits vorhandene Adressen übersprungen.' : '');
  } catch (error) {
    status.textContent = 'Hinzufügen abgebrochen: ' + error.message
      + ' ' + addedCount + ' von ' + pendingCount + ' neuen Empfängern wurden bestätigt hinzugefügt.'
      + ' Bitte prüfe die Empfängerfelder vor einem erneuten Versuch.';
  } finally {
    recipientTransferInProgress = false;
  }
}

async function getConfiguredRecipientLimit() {
  const response = await fetch(API_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'getRecipientSettings', sessionName }),
  });
  const result = await response.json();
  if (!response.ok || !result.success) {
    throw new Error('Das Empfängerlimit konnte nicht geladen werden. '
      + (typeof result.payload === 'string' ? result.payload : 'HTTP ' + response.status));
  }
  const limit = result.payload && result.payload.recipientLimit;
  if (!Number.isInteger(limit) || limit < 1 || limit > 1000) {
    throw new Error('Das konfigurierte Empfängerlimit ist ungültig.');
  }
  return limit;
}

function getOutlookRecipients(collection) {
  return new Promise((resolve, reject) => {
    if (!collection || typeof collection.getAsync !== 'function') {
      reject(new Error('Die vorhandenen Outlook-Empfänger können nicht gezählt werden.'));
      return;
    }
    collection.getAsync(result => {
      if (result.status === Office.AsyncResultStatus.Succeeded && Array.isArray(result.value)) {
        resolve(result.value);
      } else {
        reject(new Error('Die vorhandenen Outlook-Empfänger können nicht gezählt werden: '
          + (result.error ? result.error.message : 'Ungültige Outlook-Antwort.')));
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
        reject(new Error(result.error ? result.error.message : 'Unbekannter Outlook-Fehler.'));
      }
    });
  });
}

async function loadEvents() {
  const eventsResult = document.getElementById('eventsResult');

  if (!sessionName) {
    eventsResult.textContent = 'Bitte zuerst anmelden.';
    return;
  }
  eventsResult.textContent = 'Termine laden...';

  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'listEvents', sessionName }),
    });
    const result = await response.json();
    if (result.success) {
      renderEvents(result.payload.events, result.payload.display);
    } else {
      eventsResult.textContent = 'Fehler beim Laden: ' + result.payload;
    }
  } catch (error) {
    eventsResult.textContent = 'Fehler: ' + error.message;
  }
}

function renderContactSearchResults(records) {
  const container = document.getElementById('searchResult');
  if (!Array.isArray(records) || records.length === 0) {
    container.textContent = 'Keine Kontakte gefunden.';
    return;
  }
  container.innerHTML = '';

  records.forEach(record => {
    const item = document.createElement('div');
    item.className = 'record';

    const title = document.createElement('strong');
    const name = [record.firstname, record.lastname].filter(Boolean).join(' ').trim();
    title.textContent = name || record.accountname || record.email || record.id || 'Kontakt';
    const header = document.createElement('div');
    header.className = 'record-header';
    if (record.email) {
      const addButton = document.createElement('button');
      addButton.type = 'button';
      addButton.textContent = 'Zu Outlook hinzufügen';
      addButton.className = 'button button-compact';
      addButton.setAttribute('aria-label', title.textContent + ' zu Outlook hinzufügen');
      addButton.addEventListener('click', () => addContactsToOutlook([record]));
      header.appendChild(addButton);
    }
    header.appendChild(title);
    item.appendChild(header);

    const lines = [];
    if (record.email) {
      lines.push('E-Mail: ' + record.email);
    }
    if (record.phone) {
      lines.push('Tel: ' + record.phone);
    }
    if (record.mobile) {
      lines.push('Mobil: ' + record.mobile);
    }

    const details = document.createElement('div');
    details.className = 'record-details';
    details.textContent = lines.join(' | ');
    item.appendChild(details);

    if (!record.email) {
      const noEmail = document.createElement('div');
      noEmail.className = 'notice';
      noEmail.textContent = 'Keine E-Mail-Adresse hinterlegt.';
      item.appendChild(noEmail);
    }

    container.appendChild(item);
  });
}

function renderVerteilerSearchResults(records) {
  const generation = ++verteilerSearchGeneration;
  const container = document.getElementById('verteilerResult');
  if (!Array.isArray(records) || records.length === 0) {
    container.textContent = 'Keine Verteiler gefunden.';
    return;
  }
  container.innerHTML = '';
  const countEntries = [];

  records.forEach(record => {
    const item = document.createElement('div');
    item.className = 'record';
    const title = document.createElement('strong');
    const verteilerName = record.verteilername || record.id || 'Verteiler';
    title.textContent = verteilerName;
    const count = document.createElement('span');
    count.className = 'record-count';
    count.textContent = ' (Anzahl wird geladen...)';
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
    addButton.textContent = 'Zu Outlook hinzufügen';
    addButton.className = 'button button-compact';
    addButton.setAttribute('aria-label', 'Verteilerkontakte aus ' + verteilerName + ' zu Outlook hinzufügen');
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
          body: JSON.stringify({ action: 'countVerteilerContacts', verteilerId: entry.record.id, sessionName }),
        });
        const result = await response.json();
        if (generation !== verteilerSearchGeneration) {
          return;
        }
        const count = result.payload && result.payload.emailContactCount;
        if (!response.ok || !result.success || !Number.isSafeInteger(count) || count < 0) {
          throw new Error(typeof result.payload === 'string' ? result.payload : 'Ungültige Antwort beim Zählen.');
        }
        entry.element.textContent = ' (' + count + (count === 1 ? ' Kontakt' : ' Kontakte') + ' mit E-Mail)';
      } catch (error) {
        if (generation !== verteilerSearchGeneration) {
          return;
        }
        entry.element.textContent = ' (Anzahl nicht verfügbar)';
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
    container.textContent = 'Keine Daten gefunden.';
    return;
  }
  if (!display || !display.fieldLabels || typeof display.dateFormat !== 'string') {
    throw new Error('Die CRM-Feldbezeichnungen oder das persönliche Datumsformat fehlen.');
  }
  container.innerHTML = '';
  records.forEach(record => {
    const item = document.createElement('div');
    item.className = 'record';
    const title = document.createElement('strong');
    title.textContent = record.subject || record.id || 'Termin';
    item.appendChild(title);
    const lines = [];
    for (const key of ['location', 'date_start', 'due_date']) {
      if (record[key]) {
        const label = display.fieldLabels[key];
        if (typeof label !== 'string' || !label) {
          throw new Error('Die CRM-Feldbezeichnung für ein Terminfeld fehlt.');
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
    throw new Error('Ein Termindatum ist ungültig.');
  }
  const year = Number(match[1]);
  const month = Number(match[2]);
  const day = Number(match[3]);
  const leapYear = year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0);
  const monthDays = [31, leapYear ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
  if (year === 0 || month < 1 || month > 12 || day < 1 || day > monthDays[month - 1]) {
    throw new Error('Ein Termindatum ist ungültig.');
  }
  const tokens = typeof dateFormat === 'string' && dateFormat.match(/yyyy|mm|dd/g);
  if (!/^(yyyy|mm|dd)[./-](yyyy|mm|dd)[./-](yyyy|mm|dd)$/.test(dateFormat)
      || !tokens || new Set(tokens).size !== 3) {
    throw new Error('Das persönliche CRM-Datumsformat wird nicht unterstützt.');
  }
  const parts = { yyyy: match[1], mm: match[2], dd: match[3] };
  return dateFormat.replace(/yyyy|mm|dd/g, token => parts[token]);
}

Office.onReady(initializeTaskpane);
