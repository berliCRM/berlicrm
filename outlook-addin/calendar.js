let calendarState = null;
let calendarGeneration = 0;
let calendarSaving = false;

function isOutlookAppointment() {
  const item = Office.context && Office.context.mailbox && Office.context.mailbox.item;
  return Boolean(item && item.itemType === 'appointment');
}

function initializeCalendarPane() {
  document.getElementById('calendarRefreshButton').addEventListener('click', loadOutlookAppointment);
  document.getElementById('calendarForm').addEventListener('submit', event => {
    event.preventDefault();
    saveOutlookAppointmentToCrm();
  });
  document.getElementById('loginSwitchButton').addEventListener('click', () => {
    if (calendarSaving || (typeof mailReadSaving !== 'undefined' && mailReadSaving)) return;
    sessionName = null;
    crmUserId = null;
    crmUsername = '';
    verteilerSearchGeneration++;
    for (const id of ['searchResult', 'verteilerResult', 'eventsResult']) {
      document.getElementById(id).textContent = '';
    }
    try { window.localStorage.removeItem(SESSION_STORAGE_KEY); } catch (error) { /* Login still resets locally. */ }
    document.getElementById('loginStatus').textContent = 'Bitte am CRM anmelden.';
    refreshTaskpaneMode();
  });
  const mailbox = Office.context.mailbox;
  if (typeof mailbox.addHandlerAsync === 'function' && Office.EventType && Office.EventType.ItemChanged) {
    mailbox.addHandlerAsync(Office.EventType.ItemChanged, refreshTaskpaneMode);
  }
  refreshTaskpaneMode();
}

function refreshTaskpaneMode() {
  const appointment = isOutlookAppointment();
  const read = typeof isOutlookMessageRead === 'function' && isOutlookMessageRead();
  if (appointment || read) verteilerSearchGeneration++;
  calendarGeneration++;
  calendarState = null;
  document.querySelectorAll('.mail-section').forEach(section => { section.hidden = appointment || read; });
  document.getElementById('calendarSection').hidden = !appointment || !sessionName;
  document.getElementById('loginFields').hidden = Boolean(sessionName);
  document.getElementById('loginHeading').hidden = Boolean(sessionName);
  document.getElementById('loginSwitchButton').hidden = !sessionName;
  if (sessionName) document.getElementById('loginStatus').textContent = 'Angemeldet als ' + (crmUsername || 'CRM-Nutzer') + '.';
  if (typeof refreshMailReadPane === 'function') refreshMailReadPane();
  if (appointment && sessionName) {
    document.getElementById('loginStatus').textContent = 'Angemeldet als ' + (crmUsername || 'CRM-Nutzer') + '.';
    loadOutlookAppointment();
  }
}

function calendarOfficeCall(target, method, args = []) {
  return new Promise((resolve, reject) => {
    if (!target || typeof target[method] !== 'function') {
      reject(new Error('Diese Outlook-Funktion ist in diesem Kalender nicht verfügbar.'));
      return;
    }
    target[method](...args, result => {
      if (result.status === Office.AsyncResultStatus.Succeeded) resolve(result.value);
      else reject(new Error(result.error ? result.error.message : 'Unbekannter Outlook-Fehler.'));
    });
  });
}

function calendarReadValue(item, name) {
  const value = item[name];
  return value && typeof value.getAsync === 'function' ? calendarOfficeCall(value, 'getAsync') : Promise.resolve(value);
}

async function readOutlookAppointment(item) {
  // Recurrence is available from Mailbox 1.7. Never silently import a whole series as one event.
  if (typeof item.recurrence === 'undefined') {
    throw new Error('Der Serienstatus kann nicht geprüft werden. Für die Kalenderübernahme wird Outlook mit Mailbox 1.7 oder neuer benötigt.');
  }
  const [subject, start, end, location, description, recurrence] = await Promise.all([
    calendarReadValue(item, 'subject'), calendarReadValue(item, 'start'), calendarReadValue(item, 'end'),
    calendarReadValue(item, 'location'), calendarOfficeCall(item.body, 'getAsync', [Office.CoercionType.Text]),
    calendarReadValue(item, 'recurrence'),
  ]);
  if (recurrence || item.seriesId) {
    throw new Error('Terminserien und Serienvorkommen werden noch nicht übernommen. Bitte einen Einzeltermin öffnen.');
  }
  if (!start || !end || typeof start.toISOString !== 'function' || typeof end.toISOString !== 'function'
      || !Number.isFinite(start.getTime()) || !Number.isFinite(end.getTime()) || end <= start) {
    throw new Error('Outlook hat keinen gültigen Beginn und kein gültiges Ende geliefert.');
  }
  const mailbox = Office.context.mailbox.userProfile && Office.context.mailbox.userProfile.emailAddress;
  if (!mailbox) throw new Error('Die Outlook-Postfachadresse konnte nicht ermittelt werden.');
  return {
    mailbox, itemId: item.itemId || '', subject: subject || '', location: location || '', description: description || '',
    originalStart: start.toISOString(), originalEnd: end.toISOString(),
  };
}

async function calendarCrmRequest(calendar, session) {
  const response = await fetch(API_URL, {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'calendarEvent', sessionName: session, calendar }),
  });
  const result = await response.json();
  if (!response.ok || !result.success) {
    throw new Error(typeof result.payload === 'string' ? result.payload : 'Ungültige Antwort vom CRM.');
  }
  return result.payload;
}

function calendarSetBusy(busy) {
  document.getElementById('calendarFields').disabled = busy;
  document.getElementById('calendarSaveButton').disabled = busy || !calendarState || calendarState.canSave === false;
  document.getElementById('calendarRefreshButton').disabled = busy;
  document.getElementById('loginSwitchButton').disabled = busy;
}

async function loadOutlookAppointment() {
  if (!isOutlookAppointment() || !sessionName || calendarSaving) return;
  const generation = ++calendarGeneration;
  const item = Office.context.mailbox.item;
  const session = sessionName;
  const status = document.getElementById('calendarStatus');
  calendarState = null;
  calendarSetBusy(true);
  for (const name of ['Subject', 'Start', 'End', 'Location', 'Description']) {
    document.getElementById('calendar' + name).value = '';
  }
  status.textContent = 'Outlook-Termin und CRM-Einstellungen werden geladen...';
  try {
    const values = await readOutlookAppointment(item);
    const result = await calendarCrmRequest({ mode: 'prepare', ...values }, session);
    if (generation !== calendarGeneration || Office.context.mailbox.item !== item || sessionName !== session) return;
    if (!result || !result.display || typeof result.start !== 'string' || typeof result.end !== 'string') {
      throw new Error('Die CRM-Anzeigeeinstellungen sind unvollständig.');
    }
    const canSave = result.eventId ? result.display.canUpdate !== false : result.display.canCreate !== false;
    calendarState = { item, values, session, eventId: result.eventId || null, canSave };
    document.getElementById('calendarSubject').value = values.subject;
    document.getElementById('calendarStart').value = result.start;
    document.getElementById('calendarEnd').value = result.end;
    document.getElementById('calendarLocation').value = values.location;
    document.getElementById('calendarDescription').value = values.description;
    const labels = result.display.fieldLabels || {};
    for (const [id, key] of [['Subject', 'subject'], ['Start', 'date_start'], ['End', 'due_date'], ['Location', 'location'], ['Description', 'description']]) {
      if (labels[key]) document.getElementById('calendar' + id + 'Label').textContent = labels[key];
    }
    document.getElementById('calendarTimezone').textContent = 'Zeitzone: ' + result.timezone + '. Datumsformat: ' + result.display.dateFormat + '.';
    document.getElementById('calendarSaveButton').textContent = result.eventId ? 'CRM-Termin aktualisieren' : 'Im CRM ablegen';
    status.textContent = result.eventId ? 'Dieser Outlook-Termin ist bereits mit einem CRM-Termin verknüpft.' : 'Bereit zur Übernahme ins CRM.';
    if (!canSave) status.textContent = 'Du hast im CRM keine Berechtigung, diesen Termin anzulegen oder zu bearbeiten.';
  } catch (error) {
    if (generation !== calendarGeneration) return;
    status.textContent = 'Termin konnte nicht geladen werden: ' + error.message;
    if (/HTTP-Status 401|INVALID_SESSIONID|Authencation required|Authentication required/i.test(error.message)) {
      sessionName = null;
      try { window.localStorage.removeItem(SESSION_STORAGE_KEY); } catch (storageError) { /* Retry login without storage. */ }
      document.getElementById('loginStatus').textContent = 'Die CRM-Anmeldung ist abgelaufen. Bitte erneut anmelden.';
      refreshTaskpaneMode();
    }
  } finally {
    if (generation === calendarGeneration) calendarSetBusy(false);
  }
}

async function saveOutlookAppointmentToCrm() {
  if (calendarSaving || !calendarState || calendarState.canSave === false) return;
  const state = calendarState;
  const generation = calendarGeneration;
  const status = document.getElementById('calendarStatus');
  if (Office.context.mailbox.item !== state.item || sessionName !== state.session) {
    status.textContent = 'Der Termin oder die CRM-Anmeldung wurde gewechselt. Bitte neu laden.';
    return;
  }
  const values = {
    ...state.values,
    subject: document.getElementById('calendarSubject').value.trim(),
    start: document.getElementById('calendarStart').value.trim(),
    end: document.getElementById('calendarEnd').value.trim(),
    location: document.getElementById('calendarLocation').value,
    description: document.getElementById('calendarDescription').value,
  };
  if (!values.subject || !values.start || !values.end) {
    status.textContent = 'Bitte Betreff, Beginn und Ende ausfüllen.';
    return;
  }
  calendarSaving = true;
  calendarSetBusy(true);
  status.textContent = 'Outlook-Termin wird gespeichert...';
  try {
    // Recheck recurrence in case the user changed it after loading the form.
    const current = await readOutlookAppointment(state.item);
    if (['subject', 'location', 'description', 'originalStart', 'originalEnd'].some(key => current[key] !== state.values[key])) {
      throw new Error('Der Termin wurde zwischenzeitlich in Outlook geändert. Bitte die Daten aus Outlook neu laden.');
    }
    if (typeof state.item.saveAsync === 'function') {
      values.itemId = await calendarOfficeCall(state.item, 'saveAsync');
    }
    if (!values.itemId || typeof values.itemId !== 'string') {
      throw new Error('Bitte den Termin zunächst in Outlook speichern und die Daten erneut laden.');
    }
    if (generation !== calendarGeneration || Office.context.mailbox.item !== state.item) {
      throw new Error('Der Outlook-Termin wurde während des Speicherns gewechselt. Bitte neu laden.');
    }
    state.values.itemId = values.itemId;
    status.textContent = 'Termin wird im CRM gespeichert...';
    const result = await calendarCrmRequest({ mode: 'save', ...values }, state.session);
    if (!result || typeof result.eventId !== 'string' || !result.eventId) throw new Error('Das CRM hat die Speicherung nicht eindeutig bestätigt.');
    if (generation !== calendarGeneration) return;
    state.eventId = result.eventId;
    document.getElementById('calendarSaveButton').textContent = 'CRM-Termin aktualisieren';
    status.textContent = result.updated ? 'Der CRM-Termin wurde aktualisiert.' : 'Der Termin wurde im CRM angelegt.';
  } catch (error) {
    if (generation === calendarGeneration) status.textContent = 'Termin konnte nicht gespeichert werden: ' + error.message;
  } finally {
    calendarSaving = false;
    if (generation === calendarGeneration) calendarSetBusy(false);
    else if (isOutlookAppointment() && sessionName) loadOutlookAppointment();
  }
}
