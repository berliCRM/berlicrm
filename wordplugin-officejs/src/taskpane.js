import {
  BerliCrmClient,
  mergeFieldName,
  mergeFieldOoxml,
  normalizeCrmBaseUrl,
} from "./crm-client.js?v=1.3.10";
import { createZip } from "./zip.js?v=1.2.0";

const elements = {
  crmUrl: document.querySelector("#crm-url"),
  username: document.querySelector("#username"),
  accessKey: document.querySelector("#access-key"),
  connectButton: document.querySelector("#connect-button"),
  disconnectButton: document.querySelector("#disconnect-button"),
  connectionPanel: document.querySelector("#connection-panel"),
  fieldsPanel: document.querySelector("#fields-panel"),
  recordsPanel: document.querySelector("#records-panel"),
  moduleSelect: document.querySelector("#module-select"),
  fieldSelect: document.querySelector("#field-select"),
  viewSelect: document.querySelector("#view-select"),
  recordSelect: document.querySelector("#record-select"),
  selectAllRecordsButton: document.querySelector("#select-all-records-button"),
  clearRecordSelectionButton: document.querySelector("#clear-record-selection-button"),
  previousPageButton: document.querySelector("#previous-page-button"),
  nextPageButton: document.querySelector("#next-page-button"),
  pageLabel: document.querySelector("#page-label"),
  selectionCount: document.querySelector("#selection-count"),
  downloadList: document.querySelector("#download-list"),
  insertButton: document.querySelector("#insert-button"),
  mergeButton: document.querySelector("#merge-button"),
  mergeProgress: document.querySelector("#merge-progress"),
  mergeProgressText: document.querySelector("#merge-progress-text"),
  saveToCrm: document.querySelector("#save-to-crm"),
  outputMode: document.querySelector("#output-mode"),
  status: document.querySelector("#status"),
};

let crmClient = null;
let modules = [];
let currentRecordsModule = null;
let currentPage = 1;
let selectedRecords = new Map();
let wordIsReady = false;
let downloadUrls = [];
let totalRecordCount = null;
let isBusy = false;
let activeLoadingButton = null;
let recordsRequestId = 0;
let viewsRequestId = 0;
const MAX_SELECTED_RECORDS = 1000;
const RECORDS_PER_PAGE = 100;
const MAX_RECORDS_PER_OPERATION = 100;

function setStatus(message, isError = false) {
  elements.status.textContent = message;
  elements.status.dataset.error = String(isError);
}

function waitForUiUpdate() {
  return new Promise((resolve) => requestAnimationFrame(resolve));
}

function encodeBase64(value) {
  const bytes = new TextEncoder().encode(value);
  let binary = "";
  const chunkSize = 0x8000;
  for (let offset = 0; offset < bytes.length; offset += chunkSize) {
    binary += String.fromCharCode(...bytes.subarray(offset, offset + chunkSize));
  }
  return btoa(binary);
}

function createMailMergeDocumentTitle(fields, record, date = new Date()) {
  const formattedDate = [
    String(date.getDate()).padStart(2, "0"),
    String(date.getMonth() + 1).padStart(2, "0"),
    date.getFullYear(),
  ].join(".");
  const prefix = `Serienbrief ${formattedDate}: `;
  const values = fields
    .map((field) => displayValue(record.fields?.[field.name]).trim())
    .filter(Boolean);
  let title = prefix;
  for (const value of values) {
    const separator = title === prefix ? "" : ", ";
    const addition = `${separator}${value}`;
    if (Array.from(title + addition).length <= 50) {
      title += addition;
      continue;
    }

    const remaining = 50 - Array.from(title + separator).length - 1;
    if (remaining > 0) {
      title += `${separator}${Array.from(value).slice(0, remaining).join("")}…`;
    } else {
      title = `${Array.from(title).slice(0, 49).join("")}…`;
    }
    break;
  }
  return title;
}

function setButtonLoading(button, isLoading, loadingLabel = "Bitte warten …") {
  if (!button) {
    return;
  }
  if (isLoading && activeLoadingButton && activeLoadingButton !== button) {
    setButtonLoading(activeLoadingButton, false);
  }
  if (!isLoading && activeLoadingButton && activeLoadingButton !== button) {
    return;
  }

  const textNode = Array.from(button.childNodes).find((node) => node.nodeType === Node.TEXT_NODE);
  const label = isLoading
    ? textNode?.nodeValue.trim() || button.dataset.idleLabel
    : button.dataset.idleLabel || textNode?.nodeValue.trim();
  if (!label) {
    return;
  }
  if (isLoading) {
    button.dataset.idleLabel = label;
    activeLoadingButton = button;
  } else if (activeLoadingButton === button) {
    activeLoadingButton = null;
  }

  let spinner = button.querySelector(".button-spinner");
  if (!spinner) {
    spinner = document.createElement("span");
    spinner.className = "button-spinner";
    spinner.setAttribute("aria-hidden", "true");
    button.insertBefore(spinner, button.firstChild);
  }
  spinner.hidden = !isLoading;
  button.setAttribute("aria-busy", String(isLoading));
  if (textNode) {
    textNode.nodeValue = isLoading ? ` ${loadingLabel}` : ` ${label}`;
  }
}

function setBusy(busy) {
  isBusy = busy;
  elements.connectButton.disabled = busy;
  elements.disconnectButton.disabled = busy;
  elements.insertButton.disabled = busy || elements.fieldSelect.selectedOptions.length === 0;
  elements.moduleSelect.disabled = busy;
  elements.fieldSelect.disabled = busy;
  elements.viewSelect.disabled = busy;
  elements.recordSelect.disabled = busy;
  elements.selectAllRecordsButton.disabled = busy
    || totalRecordCount === null
    || totalRecordCount === 0
    || totalRecordCount > MAX_SELECTED_RECORDS;
  elements.clearRecordSelectionButton.disabled = busy || selectedRecords.size === 0;
  elements.previousPageButton.disabled = busy || currentPage <= 1;
  elements.nextPageButton.disabled = busy || elements.nextPageButton.dataset.available !== "true";
  elements.mergeButton.disabled = busy || !wordIsReady || !crmClient || selectedRecords.size === 0;
}

function renderSelectionCount() {
  const count = selectedRecords.size;
  elements.selectionCount.textContent = `${count} Datensätze ausgewählt.`;
  elements.mergeButton.disabled = !wordIsReady || !crmClient || count === 0;
  elements.clearRecordSelectionButton.disabled = isBusy || count === 0;
}

function getSelectedFields() {
  if (!currentRecordsModule) {
    return [];
  }
  return Array.from(elements.fieldSelect.selectedOptions, (option) =>
    currentRecordsModule.fields.find((field) => field.name === option.value),
  ).filter(Boolean);
}

function renderFields() {
  const selectedModule = modules.find((module) => module.apiName === elements.moduleSelect.value);
  elements.fieldSelect.replaceChildren();

  if (!selectedModule) {
    elements.insertButton.disabled = true;
    return;
  }

  const sortedFields = [...selectedModule.fields].sort((left, right) =>
    (left.label || left.name).localeCompare(right.label || right.name, "de", { sensitivity: "base" }),
  );
  for (const field of sortedFields) {
    const option = document.createElement("option");
    option.value = field.name;
    option.textContent = field.label ? `${field.label} (${field.name})` : field.name;
    elements.fieldSelect.append(option);
  }

  elements.insertButton.disabled = !wordIsReady || selectedModule.fields.length === 0;
}

async function connect() {
  setBusy(true);
  setButtonLoading(elements.connectButton, true, "Verbinde …");
  setStatus("Verbindung wird hergestellt …");

  try {
    const crmBaseUrl = normalizeCrmBaseUrl(elements.crmUrl.value, window.location.origin);
    crmClient = new BerliCrmClient(crmBaseUrl);
    modules = await crmClient.connect(elements.username.value, elements.accessKey.value);
    elements.accessKey.value = "";

    if (modules.length === 0) {
      throw new Error("Für diesen CRM-Benutzer sind keine unterstützten Module verfügbar.");
    }

    elements.moduleSelect.replaceChildren();
    for (const module of modules) {
      const option = document.createElement("option");
      option.value = module.apiName;
      option.textContent = module.label;
      elements.moduleSelect.append(option);
    }

    elements.connectionPanel.hidden = true;
    elements.fieldsPanel.hidden = false;
    elements.recordsPanel.hidden = false;
    renderFields();
    setStatus("Verbunden. Angezeigt werden die für diesen Benutzer verfügbaren Felder.");
    await loadViews();
  } catch (error) {
    let message = error.message;
    if (crmClient?.sessionName) {
      try {
        await crmClient.disconnect();
      } catch (logoutError) {
        message += ` Die Abmeldung nach dem Fehler ist ebenfalls fehlgeschlagen: ${logoutError.message}`;
      }
    }
    crmClient = null;
    modules = [];
    elements.accessKey.value = "";
    setStatus(message, true);
  } finally {
    setButtonLoading(elements.connectButton, false);
    setBusy(false);
    renderFields();
  }
}

async function disconnect() {
  setBusy(true);
  setButtonLoading(elements.disconnectButton, true, "Trenne …");
  try {
    if (crmClient) {
      await crmClient.disconnect();
    }
    crmClient = null;
    modules = [];
    elements.fieldsPanel.hidden = true;
    elements.recordsPanel.hidden = true;
    elements.connectionPanel.hidden = false;
    currentRecordsModule = null;
    elements.fieldSelect.replaceChildren();
    elements.recordSelect.replaceChildren();
    selectedRecords.clear();
    clearDownloads();
    setStatus("CRM-Verbindung getrennt.");
  } catch (error) {
    setStatus(`Abmeldung fehlgeschlagen: ${error.message}`, true);
  } finally {
    setButtonLoading(elements.disconnectButton, false);
    setBusy(false);
  }
}

async function loadViews() {
  const requestId = ++viewsRequestId;
  recordsRequestId += 1;
  currentRecordsModule = modules.find((item) => item.apiName === elements.moduleSelect.value);
  currentPage = 1;
  totalRecordCount = null;
  selectedRecords.clear();
  clearDownloads();
  elements.recordSelect.replaceChildren();
  elements.viewSelect.replaceChildren();
  elements.pageLabel.textContent = "Seite 1";
  elements.previousPageButton.disabled = true;
  elements.nextPageButton.disabled = true;
  elements.nextPageButton.dataset.available = "false";
  elements.selectAllRecordsButton.disabled = true;
  renderSelectionCount();
  if (!currentRecordsModule || !crmClient) {
    return;
  }
  if (!["Contacts", "Accounts", "Leads", "HelpDesk"].includes(currentRecordsModule.apiName)) {
    setStatus("Benutzerfelder können eingefügt werden; für Benutzer gibt es hier keine unterstützte Listenansichts-Datensatzquelle.");
    return;
  }
  setStatus("Gespeicherte Listenansichten werden geladen …");
  try {
    const result = await crmClient.getMailMergeData(currentRecordsModule.apiName);
    if (requestId !== viewsRequestId) {
      return;
    }
    if (!Array.isArray(result?.views)) {
      throw new Error("berliCRM hat keine gültige Listenansichtsliste zurückgegeben.");
    }
    for (const view of result.views) {
      const option = document.createElement("option");
      option.value = String(view.id);
      option.textContent = view.name;
      elements.viewSelect.append(option);
    }
    if (!elements.viewSelect.options.length) {
      setStatus("Für dieses Modul sind keine für Sie sichtbaren Listenansichten vorhanden.");
      return;
    }
    elements.viewSelect.value = String(result.views[0].id);
    await loadRecords(1);
  } catch (error) {
    if (requestId !== viewsRequestId) {
      return;
    }
    setStatus(`Listenansichten konnten nicht geladen werden: ${error.message}`, true);
  }
}

async function loadRecords(page) {
  if (!crmClient || !currentRecordsModule || !elements.viewSelect.value) {
    return;
  }
  const requestId = ++recordsRequestId;
  const module = currentRecordsModule;
  const listId = elements.viewSelect.value;
  const selectedFields = getSelectedFields();
  elements.previousPageButton.disabled = true;
  elements.nextPageButton.disabled = true;
  setStatus("CRM-Datensätze werden geladen …");
  try {
    const result = await crmClient.getMailMergeData(
      module.apiName,
      listId,
      page,
      [],
      selectedFields.map((field) => field.name),
    );
    if (requestId !== recordsRequestId) {
      return;
    }
    if (!Array.isArray(result?.records)) {
      throw new Error("berliCRM hat keine gültige Datensatzliste zurückgegeben.");
    }
    if (result.records.length === 0 && result.totalCount > 0) {
      throw new Error(
        `berliCRM meldet ${result.totalCount} Datensätze, lieferte aber für Seite ${page} keine Einträge zurück.`,
      );
    }
    currentPage = page;
    elements.recordSelect.replaceChildren();
    if (Number.isInteger(result.totalCount)) {
      totalRecordCount = result.totalCount;
      elements.selectAllRecordsButton.disabled = isBusy || totalRecordCount === 0 || totalRecordCount > MAX_SELECTED_RECORDS;
      elements.selectAllRecordsButton.textContent = totalRecordCount > MAX_SELECTED_RECORDS
        ? `Limit ${MAX_SELECTED_RECORDS.toLocaleString("de-DE")} (${totalRecordCount.toLocaleString("de-DE")} gesamt)`
        : `Alle Datensätze auswählen (${totalRecordCount.toLocaleString("de-DE")} gesamt)`;
    }
    for (const record of result.records) {
      const option = document.createElement("option");
      option.value = String(record.id);
      const fieldPreview = selectedFields
        .map((field) => {
          const value = displayValue(record.fields?.[field.name]);
          return value ? `${field.label || field.name}: ${value}` : "";
        })
        .filter(Boolean)
        .join(" | ");
      option.textContent = fieldPreview || record.name || record.id;
      option.selected = selectedRecords.has(option.value);
      elements.recordSelect.append(option);
      if (option.selected) {
        selectedRecords.set(option.value, { id: option.value, name: option.textContent });
      }
    }
    elements.previousPageButton.disabled = page <= 1;
    elements.nextPageButton.dataset.available = String(result.nextPage === true);
    elements.nextPageButton.disabled = result.nextPage !== true;
    elements.pageLabel.textContent = `Seite ${page}`;
    setStatus(totalRecordCount > MAX_SELECTED_RECORDS
      ? `${result.records.length} Datensätze auf dieser Seite geladen. Das Exportlimit beträgt ${MAX_SELECTED_RECORDS.toLocaleString("de-DE")} Datensätze.`
      : `${result.records.length} Datensätze auf dieser Seite geladen.`);
  } catch (error) {
    if (requestId !== recordsRequestId) {
      return;
    }
    elements.previousPageButton.disabled = currentPage <= 1;
    elements.nextPageButton.disabled = elements.nextPageButton.dataset.available !== "true";
    setStatus(`Datensätze konnten nicht geladen werden: ${error.message}`, true);
  }
}

async function selectAllRecords() {
  if (!crmClient || !currentRecordsModule || !elements.viewSelect.value || totalRecordCount === null) {
    return;
  }
  if (totalRecordCount > MAX_SELECTED_RECORDS) {
    setStatus(
      `Diese Listenansicht enthält ${totalRecordCount.toLocaleString("de-DE")} Datensätze. Das Exportlimit beträgt ${MAX_SELECTED_RECORDS.toLocaleString("de-DE")}; filtern Sie die Listenansicht und versuchen Sie es erneut.`,
      true,
    );
    return;
  }

  const selectedFields = getSelectedFields();
  selectedRecords.clear();
  setBusy(true);
  setButtonLoading(elements.selectAllRecordsButton, true, "Lade alle …");
  try {
    const totalPages = Math.ceil(totalRecordCount / RECORDS_PER_PAGE);
    for (let page = 1; page <= totalPages; page += 1) {
      setStatus(`Alle Datensätze werden geladen: Seite ${page} von ${totalPages} …`);
      const result = await crmClient.getMailMergeData(
        currentRecordsModule.apiName,
        elements.viewSelect.value,
        page,
        [],
        selectedFields.map((field) => field.name),
      );
      if (!Array.isArray(result?.records) || result.records.length === 0) {
        throw new Error(`Die CRM-Listenansicht lieferte auf Seite ${page} keine erwarteten Datensätze.`);
      }
      for (const record of result.records) {
        selectedRecords.set(String(record.id), {
          id: String(record.id),
          name: record.name || "Datensatz",
        });
      }
    }
    if (selectedRecords.size !== totalRecordCount) {
      throw new Error(`Erwartet wurden ${totalRecordCount} Datensätze, geladen wurden ${selectedRecords.size}.`);
    }
    setStatus(`${selectedRecords.size} Datensätze aus allen Seiten ausgewählt.`);
  } catch (error) {
    selectedRecords.clear();
    setStatus(`Alle Datensätze konnten nicht ausgewählt werden: ${error.message}`, true);
  } finally {
    setButtonLoading(elements.selectAllRecordsButton, false);
    setBusy(false);
    for (const option of elements.recordSelect.options) {
      option.selected = selectedRecords.has(option.value);
    }
    renderSelectionCount();
  }
}

function clearRecordSelection() {
  selectedRecords.clear();
  for (const option of elements.recordSelect.options) {
    option.selected = false;
  }
  renderSelectionCount();
}

function updateSelectedRecords() {
  for (const option of elements.recordSelect.options) {
    const recordId = option.value;
    if (option.selected && !selectedRecords.has(recordId)) {
      if (selectedRecords.size >= MAX_SELECTED_RECORDS) {
        option.selected = false;
        setStatus(`Es können höchstens ${MAX_SELECTED_RECORDS} Datensätze gleichzeitig ausgewählt werden.`, true);
        continue;
      }
      const record = Array.from(elements.recordSelect.options).find((item) => item.value === recordId);
      selectedRecords.set(recordId, {
        id: recordId,
        name: record?.textContent || recordId,
      });
    } else if (!option.selected) {
      selectedRecords.delete(recordId);
    }
  }
  renderSelectionCount();
}

function displayValue(value) {
  if (value === null || value === undefined) {
    return "";
  }
  if (Array.isArray(value)) {
    return value.map(displayValue).filter(Boolean).join(", ");
  }
  if (typeof value === "object") {
    return displayValue(value.label ?? value.name ?? value.value ?? "");
  }
  return String(value);
}

function setUtf8HtmlEncoding(parsed) {
  let charsetMeta = parsed.head.querySelector("meta[charset]");
  if (!charsetMeta) {
    charsetMeta = parsed.createElement("meta");
    parsed.head.prepend(charsetMeta);
  }
  charsetMeta.setAttribute("charset", "UTF-8");
  for (const contentTypeMeta of parsed.head.querySelectorAll('meta[http-equiv="Content-Type" i]')) {
    contentTypeMeta.setAttribute("content", "text/html; charset=UTF-8");
  }
}

function getTemplateFields(template, module) {
  const parsed = new DOMParser().parseFromString(template, "text/html");
  const templateText = parsed.body.textContent || "";
  return module.fields.filter((field) =>
    templateText.includes(`«${mergeFieldName(module, field)}»`),
  );
}

function mergeDocumentHtml(html, module, record, selectedFields) {
  const parsed = new DOMParser().parseFromString(html, "text/html");
  setUtf8HtmlEncoding(parsed);
  const replacements = new Map();
  for (const field of selectedFields) {
    replacements.set(`«${mergeFieldName(module, field)}»`, displayValue(record.fields?.[field.name]));
  }

  const containers = parsed.body.querySelectorAll("p");
  const blocks = containers.length ? Array.from(containers) : [parsed.body];
  const placeholderPattern = new RegExp(
    Array.from(replacements.keys())
      .sort((left, right) => right.length - left.length)
      .map((placeholder) => placeholder.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"))
      .join("|"),
    "g",
  );
  let replacementCount = 0;

  for (const block of blocks) {
    const walker = parsed.createTreeWalker(block, NodeFilter.SHOW_TEXT);
    const textNodes = [];
    let text = "";
    while (walker.nextNode()) {
      const node = walker.currentNode;
      textNodes.push({ node, start: text.length, end: text.length + node.nodeValue.length });
      text += node.nodeValue;
    }

    const matches = Array.from(text.matchAll(placeholderPattern), (match) => ({
      start: match.index,
      end: match.index + match[0].length,
      value: replacements.get(match[0]),
    }));
    for (const match of matches.reverse()) {
      const startNode = textNodes.find((entry) => match.start < entry.end && match.start >= entry.start);
      const endNode = textNodes.find((entry) => match.end <= entry.end && match.end > entry.start);
      if (!startNode || !endNode) {
        continue;
      }
      const range = parsed.createRange();
      range.setStart(startNode.node, match.start - startNode.start);
      range.setEnd(endNode.node, match.end - endNode.start);
      range.deleteContents();
      range.insertNode(parsed.createTextNode(match.value));
      replacementCount += 1;
    }
  }
  if (replacementCount === 0) {
    throw new Error("Die Vorlage enthält keine erkennbaren Seriendruckfelder für das gewählte Modul.");
  }
  return `\uFEFF<!doctype html>${parsed.documentElement.outerHTML}`;
}

function clearDownloads() {
  for (const url of downloadUrls) {
    URL.revokeObjectURL(url);
  }
  downloadUrls = [];
  elements.downloadList.replaceChildren();
}

function safeFileName(value) {
  return value
    .replace(/[<>:"/\\|?*\u0000-\u001f]/g, "_")
    .replace(/\s+/g, " ")
    .trim()
    .slice(0, 100) || "Datensatz";
}

function addDownload(blob, fileName, label) {
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = fileName;
  link.textContent = label;
  downloadUrls.push(url);
  const item = document.createElement("li");
  item.append(link);
  elements.downloadList.append(item);
}

function createCombinedDocument(documents) {
  const combined = new DOMParser().parseFromString(documents[0].html, "text/html");
  setUtf8HtmlEncoding(combined);
  combined.body.replaceChildren();

  for (const [index, documentFile] of documents.entries()) {
    if (index > 0) {
      const pageBreak = combined.createElement("br");
      pageBreak.setAttribute(
        "style",
        "page-break-before:always; mso-break-type:section-break;",
      );
      pageBreak.setAttribute("clear", "all");
      combined.body.append(pageBreak);
    }
    const merged = new DOMParser().parseFromString(documentFile.html, "text/html");
    combined.body.append(...Array.from(merged.body.childNodes, (node) => node.cloneNode(true)));
  }
  return `\uFEFF<!doctype html>${combined.documentElement.outerHTML}`;
}

function createUniqueFileNames(records) {
  const counts = new Map();
  return records.map((record) => {
    const baseName = `berliCRM-${safeFileName(record.name || record.id || "Datensatz")}`;
    const count = (counts.get(baseName) || 0) + 1;
    counts.set(baseName, count);
    return `${baseName}${count > 1 ? `-${count}` : ""}.doc`;
  });
}

async function createMergeDocuments() {
  if (!currentRecordsModule || selectedRecords.size === 0 || !wordIsReady) {
    return;
  }
  const records = Array.from(selectedRecords.values());
  setBusy(true);
  setButtonLoading(elements.mergeButton, true, "Erstelle …");
  elements.mergeProgress.hidden = false;
  elements.mergeProgressText.textContent = "Word-Vorlage wird gelesen …";
  setStatus("Word-Vorlage wird gelesen …");
  try {
    await waitForUiUpdate();
    const template = await Word.run(async (context) => {
      const html = context.document.body.getRange().getHtml();
      await context.sync();
      return html.value;
    });
    const templateFields = getTemplateFields(template, currentRecordsModule);
    if (templateFields.length === 0) {
      throw new Error("Die Word-Vorlage enthält keine erkennbaren Seriendruckfelder für das gewählte Modul.");
    }
    clearDownloads();
    const mergedRecords = [];
    for (let offset = 0; offset < records.length; offset += MAX_RECORDS_PER_OPERATION) {
      const batch = records.slice(offset, offset + MAX_RECORDS_PER_OPERATION);
      const progress = `CRM-Datensätze werden geladen: ${Math.floor(offset / MAX_RECORDS_PER_OPERATION) + 1} von ${Math.ceil(records.length / MAX_RECORDS_PER_OPERATION)} …`;
      elements.mergeProgressText.textContent = progress;
      setStatus(progress);
      const result = await crmClient.getMailMergeData(
        currentRecordsModule.apiName,
        elements.viewSelect.value,
        currentPage,
        batch.map((record) => record.id),
        templateFields.map((field) => field.name),
      );
      if (!Array.isArray(result?.records) || result.records.length !== batch.length) {
        throw new Error("Nicht alle ausgewählten CRM-Datensätze konnten geladen werden.");
      }
      mergedRecords.push(...result.records);
    }
    const documents = [];
    for (const [index, record] of mergedRecords.entries()) {
      if (index % 20 === 0) {
        const progress = `Dokumente werden erstellt: ${index + 1} von ${mergedRecords.length} …`;
        elements.mergeProgressText.textContent = progress;
        setStatus(progress);
        await waitForUiUpdate();
      }
      documents.push({
        record,
        html: mergeDocumentHtml(template, currentRecordsModule, record, templateFields),
      });
    }
    const outputMode = elements.outputMode.querySelector('input[name="output-mode"]:checked').value;
    if (outputMode === "individual-zip") {
      const fileNames = createUniqueFileNames(documents.map((item) => item.record));
      const zip = createZip(documents.map((item, index) => ({
        name: fileNames[index],
        content: item.html,
      })));
      addDownload(zip, "berliCRM-Einzelbriefe.zip", "ZIP-Datei mit den Einzelbriefen herunterladen");
    } else {
      const combinedHtml = createCombinedDocument(documents);
      const blob = new Blob(["\ufeff", combinedHtml], { type: "application/msword;charset=utf-8" });
      addDownload(blob, "berliCRM-Serienbrief.doc", "Gemeinsamen Serienbrief herunterladen");
    }
    let statusMessage = outputMode === "individual-zip"
      ? `${mergedRecords.length} Einzelbrief(e) in einer ZIP-Datei vorbereitet.`
      : `Ein gemeinsamer Serienbrief mit ${mergedRecords.length} Datensätzen wurde vorbereitet.`;

    if (elements.saveToCrm.checked) {
      const savedAt = new Date();
      let savedCount = 0;
      const savedDocumentIds = [];
      const failures = [];
      for (const [index, documentFile] of documents.entries()) {
        const progress = `CRM-Dokumente werden gespeichert: ${index + 1} von ${documents.length} …`;
        elements.mergeProgressText.textContent = progress;
        setStatus(progress);
        await waitForUiUpdate();
        try {
          const savedDocument = await crmClient.saveMailMergeDocument(
            currentRecordsModule.apiName,
            elements.viewSelect.value,
            documentFile.record.id,
            createMailMergeDocumentTitle(templateFields, documentFile.record, savedAt),
            encodeBase64(documentFile.html),
          );
          if (typeof savedDocument?.documentId !== "string" || !savedDocument.documentId) {
            throw new Error("berliCRM hat keine bestätigte Dokument-ID zurückgegeben.");
          }
          savedCount += 1;
          savedDocumentIds.push(savedDocument.documentId);
        } catch (error) {
          failures.push(`${documentFile.record.name || documentFile.record.id}: ${error.message}`);
        }
      }

      statusMessage += ` CRM-Dokumente gespeichert: ${savedCount} von ${documents.length}.`;
      if (savedDocumentIds.length > 0) {
        statusMessage += ` CRM-Dokument-ID(s): ${savedDocumentIds.slice(0, 3).join(", ")}`;
        if (savedDocumentIds.length > 3) {
          statusMessage += ` und ${savedDocumentIds.length - 3} weitere`;
        }
        statusMessage += ".";
      }
      if (failures.length > 0) {
        statusMessage += ` Fehler bei ${failures.length}: ${failures.slice(0, 3).join(" | ")}`;
        if (failures.length > 3) {
          statusMessage += ` | sowie ${failures.length - 3} weitere.`;
        }
      }
    }
    setStatus(statusMessage, elements.saveToCrm.checked && statusMessage.includes("Fehler bei"));
  } catch (error) {
    setStatus(`Seriendruck fehlgeschlagen: ${error.message}`, true);
  } finally {
    elements.mergeProgress.hidden = true;
    setButtonLoading(elements.mergeButton, false);
    setBusy(false);
    renderSelectionCount();
  }
}

async function insertFields() {
  const module = modules.find((item) => item.apiName === elements.moduleSelect.value);
  const selectedFields = Array.from(elements.fieldSelect.selectedOptions, (option) =>
    module?.fields.find((field) => field.name === option.value),
  );
  const selectedNames = selectedFields
    .filter(Boolean)
    .map((field) => mergeFieldName(module, field));
  if (!selectedNames.length) {
    setStatus("Bitte mindestens ein Feld auswählen.", true);
    return;
  }
  if (!wordIsReady) {
    setStatus("Das Add-in ist nicht in Word geöffnet.", true);
    return;
  }

  setBusy(true);
  setButtonLoading(elements.insertButton, true, "Füge ein …");
  try {
    const ooxml = mergeFieldOoxml(selectedNames);
    await Word.run(async (context) => {
      context.document.getSelection().insertOoxml(ooxml, Word.InsertLocation.replace);
      await context.sync();
    });
    setStatus(`${selectedNames.length} Word-Seriendruckfeld(er) eingefügt.`);
  } catch (error) {
    setStatus(`Felder konnten nicht eingefügt werden: ${error.message}`, true);
  } finally {
    setButtonLoading(elements.insertButton, false);
    setBusy(false);
  }
}

elements.connectButton.addEventListener("click", connect);
elements.disconnectButton.addEventListener("click", disconnect);
elements.moduleSelect.addEventListener("change", () => {
  renderFields();
  loadViews();
});
elements.fieldSelect.addEventListener("change", () => {
  elements.insertButton.disabled = !wordIsReady || elements.fieldSelect.selectedOptions.length === 0;
  if (!elements.viewSelect.options.length) {
    loadViews();
    return;
  }
  loadRecords(1);
});
elements.viewSelect.addEventListener("change", () => {
  recordsRequestId += 1;
  currentPage = 1;
  totalRecordCount = null;
  elements.recordSelect.replaceChildren();
  elements.pageLabel.textContent = "Seite 1";
  elements.nextPageButton.dataset.available = "false";
  elements.previousPageButton.disabled = true;
  elements.selectAllRecordsButton.disabled = true;
  selectedRecords.clear();
  clearDownloads();
  renderSelectionCount();
  loadRecords(1);
});
elements.recordSelect.addEventListener("change", updateSelectedRecords);
elements.selectAllRecordsButton.addEventListener("click", selectAllRecords);
elements.clearRecordSelectionButton.addEventListener("click", clearRecordSelection);
elements.previousPageButton.addEventListener("click", () => loadRecords(Math.max(1, currentPage - 1)));
elements.nextPageButton.addEventListener("click", () => loadRecords(currentPage + 1));
elements.mergeButton.addEventListener("click", createMergeDocuments);
elements.insertButton.addEventListener("click", insertFields);

if (globalThis.Office) {
  Office.onReady((info) => {
    wordIsReady = info.host === Office.HostType.Word;
    if (!wordIsReady) {
      setStatus("Dieses Add-in muss in Microsoft Word geöffnet werden.", true);
      elements.insertButton.disabled = true;
    }
    renderSelectionCount();
  });
} else {
  setStatus("Dieses Add-in muss in Microsoft Word geöffnet werden.", true);
}

elements.crmUrl.value = new URL("../", window.location.href).href;
