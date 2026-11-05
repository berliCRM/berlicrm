import { t, i18n } from "./i18n.js?v=20261105.1";
i18n.initialize();
import {
  BerliCrmClient,
  mergeFieldName,
  mergeFieldNames,
  mergeFieldOoxml,
  normalizeCrmBaseUrl,
} from "./crm-client.js?v=20261105.2";
import { createZip } from "./zip.js?v=20261105.1";

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
  outputFormat: document.querySelector("#output-format"),
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

function setStatus(message, isError = false, isLoading = false) {
  elements.status.textContent = message;
  if (isLoading) {
    const spinner = document.createElement("span");
    spinner.className = "button-spinner";
    spinner.setAttribute("aria-hidden", "true");
    elements.status.prepend(spinner);
  }
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
  const prefix = t("DOCUMENT_TITLE_PREFIX", [formattedDate]);
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

function setButtonLoading(button, isLoading, loadingLabel = t("PLEASE_WAIT")) {
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
  if (elements.outputFormat) elements.outputFormat.disabled = busy;
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
  elements.selectionCount.textContent = t("RECORDS_SELECTED", [count]);
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
    (left.label || left.name).localeCompare(right.label || right.name, i18n.locale, { sensitivity: "base" }),
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
  setButtonLoading(elements.connectButton, true, t('CONNECTING'));
  setStatus(t("CONNECTING"));

  try {
    const crmBaseUrl = normalizeCrmBaseUrl(elements.crmUrl.value, window.location.origin);
    crmClient = new BerliCrmClient(crmBaseUrl);
    modules = await crmClient.connect(elements.username.value, elements.accessKey.value);
    elements.accessKey.value = "";

    if (modules.length === 0) {
      throw new Error(t("NO_SUPPORTED_MODULES_ARE_AVAILABLE_FOR_THIS_CRM_USER"));
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
    setStatus(t("CONNECTED_THE_FIELDS_AVAILABLE_TO_THIS_USER_ARE_DISPLAYED"));
    await loadViews();
  } catch (error) {
    let message = error.message;
    if (crmClient?.sessionName) {
      try {
        await crmClient.disconnect();
      } catch (logoutError) {
        message += t("SIGNING_OUT_AFTER_THE_ERROR_ALSO_FAILED", [logoutError.message]);
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
  const disconnectedClient = crmClient;
  crmClient = null;
  modules = [];
  currentRecordsModule = null;
  // Late list responses must not restore data from the disconnected session.
  recordsRequestId += 1;
  viewsRequestId += 1;
  currentPage = 1;
  totalRecordCount = null;
  elements.fieldsPanel.hidden = true;
  elements.recordsPanel.hidden = true;
  elements.connectionPanel.hidden = false;
  elements.accessKey.value = "";
  elements.moduleSelect.replaceChildren();
  elements.fieldSelect.replaceChildren();
  elements.viewSelect.replaceChildren();
  elements.recordSelect.replaceChildren();
  elements.nextPageButton.dataset.available = "false";
  elements.pageLabel.textContent = t("PAGE_1");
  selectedRecords.clear();
  clearDownloads();
  renderSelectionCount();
  setBusy(false);
  setStatus(t("DISCONNECTED_FROM_CRM"));

  // Local sign-out is complete; an expired or unreachable server cannot block
  // the login form, and a late logout response must not affect a new connection.
  if (disconnectedClient) {
    try {
      await disconnectedClient.disconnect();
    } catch {
      // The server may already have expired this session.
    }
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
  elements.pageLabel.textContent = t("PAGE_1");
  elements.previousPageButton.disabled = true;
  elements.nextPageButton.disabled = true;
  elements.nextPageButton.dataset.available = "false";
  elements.selectAllRecordsButton.disabled = true;
  renderSelectionCount();
  if (!currentRecordsModule || !crmClient) {
    return;
  }
  if (!["Contacts", "Accounts", "Leads", "HelpDesk"].includes(currentRecordsModule.apiName)) {
    setStatus(t("USER_FIELDS_CAN_BE_INSERTED_A_LIST_VIEW_RECORD_SOURCE_IS_NOT_AVAILABLE_F"));
    return;
  }
  setStatus(t("LOADING_SAVED_LIST_VIEWS"));
  try {
    const result = await crmClient.getMailMergeData(currentRecordsModule.apiName);
    if (requestId !== viewsRequestId) {
      return;
    }
    for (const field of currentRecordsModule.fields) field.aliases = result?.fieldAliases?.[field.name] || [];
    if (!Array.isArray(result?.views)) {
      throw new Error(t("BERLICRM_DID_NOT_RETURN_A_VALID_LIST_OF_VIEWS"));
    }
    for (const view of result.views) {
      const option = document.createElement("option");
      option.value = String(view.id);
      option.textContent = view.name;
      elements.viewSelect.append(option);
    }
    if (!elements.viewSelect.options.length) {
      setStatus(t("THIS_MODULE_HAS_NO_LIST_VIEWS_VISIBLE_TO_YOU"));
      return;
    }
    elements.viewSelect.value = String(result.views[0].id);
    await loadRecords(1);
  } catch (error) {
    if (requestId !== viewsRequestId) {
      return;
    }
    setStatus(t("COULD_NOT_LOAD_LIST_VIEWS", [error.message]), true);
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
  setStatus(t("LOADING_CRM_RECORDS"), false, true);
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
      throw new Error(t("BERLICRM_DID_NOT_RETURN_A_VALID_RECORD_LIST"));
    }
    if (result.records.length === 0 && result.totalCount > 0) {
      throw new Error(
        t("BERLICRM_REPORTS_RECORDS_BUT_RETURNED_NO_ENTRIES_FOR_PAGE", [result.totalCount, page]),
      );
    }
    currentPage = page;
    elements.recordSelect.replaceChildren();
    if (Number.isInteger(result.totalCount)) {
      totalRecordCount = result.totalCount;
      elements.selectAllRecordsButton.disabled = isBusy || totalRecordCount === 0 || totalRecordCount > MAX_SELECTED_RECORDS;
      elements.selectAllRecordsButton.textContent = totalRecordCount > MAX_SELECTED_RECORDS
        ? `Limit ${MAX_SELECTED_RECORDS.toLocaleString(globalThis.BerliI18n.locale)} (${totalRecordCount.toLocaleString(globalThis.BerliI18n.locale)} gesamt)`
        : t("SELECT_ALL_RECORDS_TOTAL", [totalRecordCount.toLocaleString(globalThis.BerliI18n.locale)]);
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
    elements.pageLabel.textContent = t("PAGE", [page]);
    setStatus(totalRecordCount > MAX_SELECTED_RECORDS
      ? t("RECORDS_LOADED_ON_THIS_PAGE_THE_EXPORT_LIMIT_IS_RECORDS", [result.records.length, MAX_SELECTED_RECORDS.toLocaleString(globalThis.BerliI18n.locale)])
      : t("RECORDS_LOADED_ON_THIS_PAGE", [result.records.length]));
  } catch (error) {
    if (requestId !== recordsRequestId) {
      return;
    }
    elements.previousPageButton.disabled = currentPage <= 1;
    elements.nextPageButton.disabled = elements.nextPageButton.dataset.available !== "true";
    setStatus(t("COULD_NOT_LOAD_RECORDS", [error.message]), true);
  }
}

async function selectAllRecords() {
  if (!crmClient || !currentRecordsModule || !elements.viewSelect.value || totalRecordCount === null) {
    return;
  }
  if (totalRecordCount > MAX_SELECTED_RECORDS) {
    setStatus(
      t("THIS_LIST_VIEW_CONTAINS_RECORDS_THE_EXPORT_LIMIT_IS_FILTER_THE_LIST_VIEW", [totalRecordCount.toLocaleString(globalThis.BerliI18n.locale), MAX_SELECTED_RECORDS.toLocaleString(globalThis.BerliI18n.locale)]),
      true,
    );
    return;
  }

  const selectedFields = getSelectedFields();
  selectedRecords.clear();
  setBusy(true);
  setButtonLoading(elements.selectAllRecordsButton, true, t("LOADING_ALL"));
  try {
    const totalPages = Math.ceil(totalRecordCount / RECORDS_PER_PAGE);
    for (let page = 1; page <= totalPages; page += 1) {
      setStatus(t("LOADING_ALL_RECORDS_PAGE_OF", [page, totalPages]));
      const result = await crmClient.getMailMergeData(
        currentRecordsModule.apiName,
        elements.viewSelect.value,
        page,
        [],
        selectedFields.map((field) => field.name),
      );
      if (!Array.isArray(result?.records) || result.records.length === 0) {
        throw new Error(t("THE_CRM_LIST_VIEW_DID_NOT_RETURN_THE_EXPECTED_RECORDS_ON_PAGE", [page]));
      }
      for (const record of result.records) {
        selectedRecords.set(String(record.id), {
          id: String(record.id),
          name: record.name || t("RECORD"),
        });
      }
    }
    if (selectedRecords.size !== totalRecordCount) {
      throw new Error(t("EXPECTED_RECORDS_LOADED", [totalRecordCount, selectedRecords.size]));
    }
    setStatus(t("RECORDS_SELECTED_ACROSS_ALL_PAGES", [selectedRecords.size]));
  } catch (error) {
    selectedRecords.clear();
    setStatus(t("COULD_NOT_SELECT_ALL_RECORDS", [error.message]), true);
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
        setStatus(t("YOU_CAN_SELECT_AT_MOST_RECORDS_AT_A_TIME", [MAX_SELECTED_RECORDS]), true);
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
    mergeFieldNames(module, field).some(name => templateText.includes(`«${name}»`)),
  );
}

function mergeDocumentHtml(html, module, record, selectedFields) {
  const parsed = new DOMParser().parseFromString(html, "text/html");
  setUtf8HtmlEncoding(parsed);
  const replacements = new Map();
  for (const field of selectedFields) {
    for (const name of mergeFieldNames(module, field)) replacements.set(`«${name}»`, displayValue(record.fields?.[field.name]));
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
    throw new Error(t("THE_TEMPLATE_CONTAINS_NO_RECOGNIZABLE_MAIL_MERGE_FIELDS_FOR_THE_SELECTED"));
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
    .slice(0, 100) || t("RECORD");
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
    const baseName = `berliCRM-${safeFileName(record.name || record.id || t("RECORD"))}`;
    const count = (counts.get(baseName) || 0) + 1;
    counts.set(baseName, count);
    return `${baseName}${count > 1 ? `-${count}` : ""}.doc`;
  });
}

async function createMergeDocuments() {
  if (elements.outputFormat?.value !== "docx") return createLegacyMergeDocuments();
  if (isBusy || !currentRecordsModule || !selectedRecords.size || !wordIsReady || !crmClient) return;
  setBusy(true);
  setButtonLoading(elements.mergeButton, true, t("CREATING_DOCX"));
  elements.mergeProgress.hidden = false;
  try {
    // Load the DOCX implementation only when explicitly selected. Its dependencies
    // and failures cannot prevent the established legacy export from starting.
    const { runDocxMerge } = await import("./docx-export.js?v=20261105.1");
    clearDownloads();
    const result = await runDocxMerge({ office: Office, module: currentRecordsModule,
      records: Array.from(selectedRecords.values()), crmClient, viewId: elements.viewSelect.value,
      page: currentPage, outputMode: elements.outputMode.querySelector('input[name="output-mode"]:checked').value,
      saveToCrm: elements.saveToCrm.checked, nextFrame: waitForUiUpdate, addDownload,
      fileNames: createUniqueFileNames, createTitle: createMailMergeDocumentTitle,
      onProgress: message => { elements.mergeProgressText.textContent = message; setStatus(message); } });
    setStatus(result.message, result.failed);
  } catch (error) {
    setStatus(t("DOCX_MAIL_MERGE_FAILED_YOU_CAN_SWITCH_BACK_TO_THE_LEGACY_OUTPUT_DOC_HTML", [error.message]), true);
  } finally {
    elements.mergeProgress.hidden = true;
    setButtonLoading(elements.mergeButton, false);
    setBusy(false);
    renderSelectionCount();
  }
}

async function createLegacyMergeDocuments() {
  if (!currentRecordsModule || selectedRecords.size === 0 || !wordIsReady) {
    return;
  }
  const records = Array.from(selectedRecords.values());
  setBusy(true);
  setButtonLoading(elements.mergeButton, true, t('CREATING'));
  elements.mergeProgress.hidden = false;
  elements.mergeProgressText.textContent = t("READING_WORD_TEMPLATE");
  setStatus(t("READING_WORD_TEMPLATE"));
  try {
    await waitForUiUpdate();
    const template = await Word.run(async (context) => {
      const html = context.document.body.getRange().getHtml();
      await context.sync();
      return html.value;
    });
    const templateFields = getTemplateFields(template, currentRecordsModule);
    if (templateFields.length === 0) {
      throw new Error(t("THE_WORD_TEMPLATE_CONTAINS_NO_RECOGNIZABLE_MAIL_MERGE_FIELDS_FOR_THE_SEL"));
    }
    clearDownloads();
    const mergedRecords = [];
    for (let offset = 0; offset < records.length; offset += MAX_RECORDS_PER_OPERATION) {
      const batch = records.slice(offset, offset + MAX_RECORDS_PER_OPERATION);
      const progress = t("LOADING_CRM_RECORDS_OF", [Math.floor(offset / MAX_RECORDS_PER_OPERATION) + 1, Math.ceil(records.length / MAX_RECORDS_PER_OPERATION)]);
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
        throw new Error(t("NOT_ALL_SELECTED_CRM_RECORDS_COULD_BE_LOADED"));
      }
      mergedRecords.push(...result.records);
    }
    const documents = [];
    for (const [index, record] of mergedRecords.entries()) {
      if (index % 20 === 0) {
        const progress = t("CREATING_DOCUMENTS_OF", [index + 1, mergedRecords.length]);
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
      addDownload(zip, "berliCRM-Einzelbriefe.zip", t("DOWNLOAD_INDIVIDUAL_LETTERS_AS_A_ZIP_FILE"));
    } else {
      const combinedHtml = createCombinedDocument(documents);
      const blob = new Blob(["\ufeff", combinedHtml], { type: "application/msword;charset=utf-8" });
      addDownload(blob, t("BERLICRM_MAIL_MERGE_DOC"), t("DOWNLOAD_COMBINED_MAIL_MERGE_DOCUMENT"));
    }
    let statusMessage = outputMode === "individual-zip"
      ? t("PREPARED_INDIVIDUAL_LETTER_S_IN_A_ZIP_FILE", [mergedRecords.length])
      : t("PREPARED_A_COMBINED_MAIL_MERGE_DOCUMENT_WITH_RECORDS", [mergedRecords.length]);

    if (elements.saveToCrm.checked) {
      const savedAt = new Date();
      let savedCount = 0;
      const savedDocumentIds = [];
      const failures = [];
      for (const [index, documentFile] of documents.entries()) {
        const progress = t("SAVING_CRM_DOCUMENTS_OF", [index + 1, documents.length]);
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
            throw new Error(t("BERLICRM_DID_NOT_RETURN_A_CONFIRMED_DOCUMENT_ID"));
          }
          savedCount += 1;
          savedDocumentIds.push(savedDocument.documentId);
        } catch (error) {
          failures.push(`${documentFile.record.name || documentFile.record.id}: ${error.message}`);
        }
      }

      statusMessage += t("CRM_DOCUMENTS_SAVED_OF", [savedCount, documents.length]);
      if (savedDocumentIds.length > 0) {
        statusMessage += ` CRM-Dokument-ID(s): ${savedDocumentIds.slice(0, 3).join(", ")}`;
        if (savedDocumentIds.length > 3) {
          statusMessage += t("AND_MORE", [savedDocumentIds.length - 3]);
        }
        statusMessage += ".";
      }
      if (failures.length > 0) {
        statusMessage += t("ERROR_FOR", [failures.length, failures.slice(0, 3).join(" | ")]);
        if (failures.length > 3) {
          statusMessage += t("ADDITIONAL_ERRORS_SUFFIX", [failures.length - 3]);
        }
      }
    }
    setStatus(statusMessage, elements.saveToCrm.checked && statusMessage.includes(t("ERROR_MARKER")));
  } catch (error) {
    setStatus(t("MAIL_MERGE_FAILED", [error.message]), true);
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
    setStatus(t("PLEASE_SELECT_AT_LEAST_ONE_FIELD"), true);
    return;
  }
  if (!wordIsReady) {
    setStatus(t("THE_ADD_IN_IS_NOT_OPEN_IN_WORD"), true);
    return;
  }

  setBusy(true);
  setButtonLoading(elements.insertButton, true, t("INSERTING"));
  try {
    const ooxml = mergeFieldOoxml(selectedNames);
    await Word.run(async (context) => {
      context.document.getSelection().insertOoxml(ooxml, Word.InsertLocation.replace);
      await context.sync();
    });
    setStatus(t("INSERTED_WORD_MAIL_MERGE_FIELD_S", [selectedNames.length]));
  } catch (error) {
    setStatus(t("COULD_NOT_INSERT_FIELDS", [error.message]), true);
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
  elements.pageLabel.textContent = t("PAGE_1");
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
    if (!crmClient?.sessionName) i18n.initialize();
    wordIsReady = info.host === Office.HostType.Word;
    if (!wordIsReady) {
      setStatus(t("THIS_ADD_IN_MUST_BE_OPENED_IN_MICROSOFT_WORD"), true);
      elements.insertButton.disabled = true;
    }
    renderSelectionCount();
  });
} else {
  setStatus(t("THIS_ADD_IN_MUST_BE_OPENED_IN_MICROSOFT_WORD"), true);
}

elements.crmUrl.value = new URL("../", window.location.href).href;
