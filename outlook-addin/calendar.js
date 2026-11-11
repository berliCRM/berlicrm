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
    crmSessionGeneration++;
    sessionName = null;
    crmUserId = null;
    crmUsername = '';
    verteilerSearchGeneration++;
    for (const id of ['searchResult', 'verteilerResult', 'eventsResult']) {
      document.getElementById(id).textContent = '';
    }
    try { window.localStorage.removeItem(SESSION_STORAGE_KEY); } catch (error) { /* Login still resets locally. */ }
    document.getElementById("loginStatus").textContent = globalThis.BerliI18n.t("PLEASE_SIGN_IN_TO_CRM");
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
  if (sessionName) document.getElementById("loginStatus").textContent = globalThis.BerliI18n.t("SIGNED_IN_AS") + (crmUsername || globalThis.BerliI18n.t("CRM_USER")) + '.';
  if (typeof refreshMailReadPane === 'function') refreshMailReadPane();
  if (appointment && sessionName) {
    document.getElementById("loginStatus").textContent = globalThis.BerliI18n.t("SIGNED_IN_AS") + (crmUsername || globalThis.BerliI18n.t("CRM_USER")) + '.';
    loadOutlookAppointment();
  }
}

function calendarOfficeCall(target, method, args = []) {
  return new Promise((resolve, reject) => {
    if (!target || typeof target[method] !== 'function') {
      reject(new Error(globalThis.BerliI18n.t("THIS_OUTLOOK_FEATURE_IS_NOT_AVAILABLE_IN_THIS_CALENDAR")));
      return;
    }
    target[method](...args, result => {
      if (result.status === Office.AsyncResultStatus.Succeeded) resolve(result.value);
      else reject(new Error(result.error ? result.error.message : globalThis.BerliI18n.t("UNKNOWN_OUTLOOK_ERROR")));
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
    throw new Error(globalThis.BerliI18n.t("CANNOT_CHECK_RECURRENCE_STATUS_IMPORTING_APPOINTMENTS_REQUIRES_OUTLOOK_W"));
  }
  const [subject, start, end, location, description, recurrence] = await Promise.all([
    calendarReadValue(item, 'subject'), calendarReadValue(item, 'start'), calendarReadValue(item, 'end'),
    calendarReadValue(item, 'location'), calendarOfficeCall(item.body, 'getAsync', [Office.CoercionType.Text]),
    calendarReadValue(item, 'recurrence'),
  ]);
  if (recurrence || item.seriesId) {
    throw new Error(globalThis.BerliI18n.t("RECURRING_APPOINTMENTS_AND_OCCURRENCES_ARE_NOT_YET_SUPPORTED_PLEASE_OPEN"));
  }
  if (!start || !end || typeof start.toISOString !== 'function' || typeof end.toISOString !== 'function'
      || !Number.isFinite(start.getTime()) || !Number.isFinite(end.getTime()) || end <= start) {
    throw new Error(globalThis.BerliI18n.t("OUTLOOK_DID_NOT_RETURN_A_VALID_START_AND_END_TIME"));
  }
  const mailbox = Office.context.mailbox.userProfile && Office.context.mailbox.userProfile.emailAddress;
  if (!mailbox) throw new Error(globalThis.BerliI18n.t("COULD_NOT_DETERMINE_THE_OUTLOOK_MAILBOX_ADDRESS"));
  return {
    mailbox, itemId: item.itemId || '', subject: subject || '', location: location || '', description: description || '',
    originalStart: start.toISOString(), originalEnd: end.toISOString(),
  };
}

async function calendarCrmRequest(calendar, session) {
  const response = await fetch(API_URL, {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ language: globalThis.BerliI18n.language, action: 'calendarEvent', sessionName: session, calendar }),
  });
  const result = await response.json();
  if (!response.ok || !result.success) {
    throw new Error(typeof result.payload === 'string' ? result.payload : globalThis.BerliI18n.t("INVALID_RESPONSE_FROM_CRM"));
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
  status.textContent = globalThis.BerliI18n.t("LOADING_OUTLOOK_APPOINTMENT_AND_CRM_SETTINGS");
  try {
    const values = await readOutlookAppointment(item);
    const result = await calendarCrmRequest({ mode: 'prepare', ...values }, session);
    if (generation !== calendarGeneration || Office.context.mailbox.item !== item || sessionName !== session) return;
    if (!result || !result.display || typeof result.start !== 'string' || typeof result.end !== 'string') {
      throw new Error(globalThis.BerliI18n.t("THE_CRM_DISPLAY_SETTINGS_ARE_INCOMPLETE"));
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
      if (labels[key]) document.getElementById("calendar" + id + "Label").textContent = labels[key];
    }
    document.getElementById("calendarTimezone").textContent = globalThis.BerliI18n.t("TIME_ZONE") + result.timezone + globalThis.BerliI18n.t("DATE_FORMAT") + result.display.dateFormat + '.';
    document.getElementById("calendarSaveButton").textContent = result.eventId ? globalThis.BerliI18n.t("UPDATE_CRM_APPOINTMENT") : globalThis.BerliI18n.t("SAVE_TO_CRM");
    status.textContent = result.eventId ? globalThis.BerliI18n.t("THIS_OUTLOOK_APPOINTMENT_IS_ALREADY_LINKED_TO_A_CRM_APPOINTMENT") : globalThis.BerliI18n.t("READY_TO_SAVE_TO_CRM");
    if (!canSave) status.textContent = globalThis.BerliI18n.t("YOU_DO_NOT_HAVE_CRM_PERMISSION_TO_CREATE_OR_EDIT_THIS_APPOINTMENT");
  } catch (error) {
    if (generation !== calendarGeneration) return;
    status.textContent = globalThis.BerliI18n.t("COULD_NOT_LOAD_APPOINTMENT") + error.message;
    if (/HTTP(?:-Status| status) 401|INVALID_SESSIONID|Authencation required|Authentication required/i.test(error.message)) {
      sessionName = null;
      try { window.localStorage.removeItem(SESSION_STORAGE_KEY); } catch (storageError) { /* Retry login without storage. */ }
      document.getElementById("loginStatus").textContent = globalThis.BerliI18n.t("YOUR_CRM_SESSION_HAS_EXPIRED_PLEASE_SIGN_IN_AGAIN");
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
    status.textContent = globalThis.BerliI18n.t("THE_APPOINTMENT_OR_CRM_SESSION_HAS_CHANGED_PLEASE_RELOAD");
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
    status.textContent = globalThis.BerliI18n.t("PLEASE_FILL_IN_SUBJECT_START_AND_END");
    return;
  }
  calendarSaving = true;
  calendarSetBusy(true);
  status.textContent = globalThis.BerliI18n.t("SAVING_OUTLOOK_APPOINTMENT");
  try {
    // Recheck recurrence in case the user changed it after loading the form.
    const current = await readOutlookAppointment(state.item);
    if (['subject', 'location', 'description', 'originalStart', 'originalEnd'].some(key => current[key] !== state.values[key])) {
      throw new Error(globalThis.BerliI18n.t("THE_APPOINTMENT_WAS_CHANGED_IN_OUTLOOK_IN_THE_MEANTIME_PLEASE_RELOAD_THE"));
    }
    if (typeof state.item.saveAsync === 'function') {
      values.itemId = await calendarOfficeCall(state.item, 'saveAsync');
    }
    if (!values.itemId || typeof values.itemId !== 'string') {
      throw new Error(globalThis.BerliI18n.t("PLEASE_SAVE_THE_APPOINTMENT_IN_OUTLOOK_FIRST_AND_RELOAD_THE_DATA"));
    }
    if (generation !== calendarGeneration || Office.context.mailbox.item !== state.item) {
      throw new Error(globalThis.BerliI18n.t("THE_OUTLOOK_APPOINTMENT_CHANGED_WHILE_SAVING_PLEASE_RELOAD"));
    }
    state.values.itemId = values.itemId;
    status.textContent = globalThis.BerliI18n.t("SAVING_APPOINTMENT_TO_CRM");
    const result = await calendarCrmRequest({ mode: 'save', ...values }, state.session);
    if (!result || typeof result.eventId !== 'string' || !result.eventId) throw new Error(globalThis.BerliI18n.t("THE_CRM_DID_NOT_UNAMBIGUOUSLY_CONFIRM_THAT_THE_DATA_WAS_SAVED"));
    if (generation !== calendarGeneration) return;
    state.eventId = result.eventId;
    document.getElementById("calendarSaveButton").textContent = globalThis.BerliI18n.t("UPDATE_CRM_APPOINTMENT");
    status.textContent = result.updated ? globalThis.BerliI18n.t("THE_CRM_APPOINTMENT_WAS_UPDATED") : globalThis.BerliI18n.t("THE_APPOINTMENT_WAS_CREATED_IN_CRM");
  } catch (error) {
    if (generation === calendarGeneration) status.textContent = globalThis.BerliI18n.t("COULD_NOT_SAVE_APPOINTMENT") + error.message;
  } finally {
    calendarSaving = false;
    if (generation === calendarGeneration) calendarSetBusy(false);
    else if (isOutlookAppointment() && sessionName) loadOutlookAppointment();
  }
}
