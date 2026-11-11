import { t } from "./i18n.js?v=20261105.1";
import { prepareDocxTemplate, readWordDocx, DOCX_MIME } from "./docx-engine.js?v=20261105.1";
import { createZip } from "./zip.js?v=20261105.1";

export function binaryBase64(bytes) {
  let binary = "";
  for (let offset = 0; offset < bytes.length; offset += 0x8000) binary += String.fromCharCode(...bytes.subarray(offset, offset + 0x8000));
  return btoa(binary);
}

/** New export path. The legacy HTML/.doc implementation is intentionally separate. */
export async function runDocxMerge({ office, module, records, crmClient, viewId, page, outputMode, saveToCrm, onProgress, nextFrame, addDownload, fileNames, createTitle }) {
  onProgress(t("READING_THE_COMPLETE_WORD_TEMPLATE_AS_DOCX"));
  await nextFrame();
  const template = prepareDocxTemplate(await readWordDocx(office), module);
  const merged = [];
  for (let offset = 0; offset < records.length; offset += 100) {
    const batch = records.slice(offset, offset + 100);
    onProgress(t("LOADING_CRM_RECORDS_OF", [Math.floor(offset / 100) + 1, Math.ceil(records.length / 100)]));
    const result = await crmClient.getMailMergeData(module.apiName, viewId, page, batch.map(record => record.id), template.fields.map(field => field.name));
    if (saveToCrm && !result?.documentFormats?.includes('docx')) throw new Error(t("THE_CRM_SERVER_DOES_NOT_YET_SUPPORT_SAVING_DOCX_FILES_PLEASE_UPDATE_THE"));
    const returned = new Map((result?.records || []).map(record => [String(record.id), record]));
    if (returned.size !== batch.length || result.records.length !== batch.length || batch.some(record => !returned.has(String(record.id)))) throw new Error(t("NOT_ALL_SELECTED_CRM_RECORDS_COULD_BE_LOADED_UNAMBIGUOUSLY"));
    merged.push(...batch.map(record => returned.get(String(record.id))));
  }
  if (!merged.length) throw new Error(t("PLEASE_SELECT_AT_LEAST_ONE_CRM_RECORD"));
  let combined;
  if (outputMode === "combined") {
    onProgress(t("CREATING_A_COMBINED_DOCX_DOCUMENT_FOR_RECORDS", [merged.length]));
    await nextFrame();
    combined = template.combined(merged);
  }
  const documents = [];
  if (outputMode === "individual-zip" || saveToCrm) {
    let total = 0;
    for (const [index, record] of merged.entries()) {
      onProgress(t("CREATING_INDIVIDUAL_DOCX_LETTERS_OF", [index + 1, merged.length]));
      if (index % 5 === 0) await nextFrame();
      const bytes = template.single(record);
      total += bytes.length;
      if (total > 128 * 1024 * 1024) throw new Error(t("THE_INDIVIDUAL_DOCX_LETTERS_EXCEED_128_MIB_IN_TOTAL_PLEASE_SELECT_FEWER"));
      documents.push({ record, bytes });
    }
  }
  if (outputMode === "individual-zip") {
    const names = fileNames(merged).map(name => name.replace(/\.doc$/i, ".docx"));
    const zip = createZip(documents.map((item, index) => ({ name: names[index], content: item.bytes })));
    addDownload(zip, "berliCRM-Einzelbriefe-DOCX.zip", t("DOWNLOAD_INDIVIDUAL_DOCX_LETTERS_AS_ZIP"));
  } else addDownload(new Blob([combined], { type: DOCX_MIME }), t("BERLICRM_MAIL_MERGE_DOCX"), t("DOWNLOAD_COMBINED_DOCX_MAIL_MERGE_DOCUMENT"));
  let message = outputMode === "individual-zip" ? t("PREPARED_INDIVIDUAL_DOCX_LETTERS_AS_ZIP", [merged.length]) : t("PREPARED_A_DOCX_MAIL_MERGE_DOCUMENT_WITH_RECORDS", [merged.length]);
  const failures = [], saved = [];
  if (saveToCrm) {
    const date = new Date();
    for (const [index, item] of documents.entries()) {
      onProgress(t("SAVING_DOCX_DOCUMENTS_TO_CRM_OF", [index + 1, documents.length]));
      await nextFrame();
      try {
        if (item.bytes.length > 5 * 1024 * 1024) throw new Error(t("THE_DOCX_FILE_EXCEEDS_THE_CRM_FILE_LIMIT_OF_5_MIB"));
        const result = await crmClient.saveMailMergeDocument(module.apiName, viewId, item.record.id, createTitle(template.fields, item.record, date), binaryBase64(item.bytes));
        if (!result?.documentId || !/\.docx$/i.test(result.fileName || "")) throw new Error(t("THE_CRM_DID_NOT_CONFIRM_THAT_THE_DOCX_FILE_WAS_SAVED_PLEASE_CHECK_THE_UP"));
        saved.push(result.documentId);
      } catch (error) { failures.push(`${item.record.name || item.record.id}: ${error.message}`); }
    }
    message += t("CRM_DOCUMENTS_SAVED_OF", [saved.length, documents.length]);
    if (saved.length) message += ` CRM-Dokument-ID(s): ${saved.slice(0, 3).join(", ")}${saved.length > 3 ? t("AND_MORE", [saved.length - 3]) : ""}.`;
    if (failures.length) message += t("ERROR_FOR_3", [failures.length, failures.slice(0, 3).join(" | "), failures.length > 3 ? ` | sowie ${failures.length - 3} weitere.` : ""]);
  }
  return { message, failed: failures.length > 0 };
}
