import { prepareDocxTemplate, readWordDocx, DOCX_MIME } from "./docx-engine.js?v=1.0.2";
import { createZip } from "./zip.js?v=1.2.0";

export function binaryBase64(bytes) {
  let binary = "";
  for (let offset = 0; offset < bytes.length; offset += 0x8000) binary += String.fromCharCode(...bytes.subarray(offset, offset + 0x8000));
  return btoa(binary);
}

/** New export path. The legacy HTML/.doc implementation is intentionally separate. */
export async function runDocxMerge({ office, module, records, crmClient, viewId, page, outputMode, saveToCrm, onProgress, nextFrame, addDownload, fileNames, createTitle }) {
  onProgress("Vollständige Word-Vorlage wird als DOCX gelesen …");
  await nextFrame();
  const template = prepareDocxTemplate(await readWordDocx(office), module);
  const merged = [];
  for (let offset = 0; offset < records.length; offset += 100) {
    const batch = records.slice(offset, offset + 100);
    onProgress(`CRM-Datensätze werden geladen: ${Math.floor(offset / 100) + 1} von ${Math.ceil(records.length / 100)} …`);
    const result = await crmClient.getMailMergeData(module.apiName, viewId, page, batch.map(record => record.id), template.fields.map(field => field.name));
    if (saveToCrm && !result?.documentFormats?.includes('docx')) throw new Error("Der CRM-Server unterstützt die DOCX-Ablage noch nicht. Bitte die Word-Webservice-Dateien samt DOCX-Prüfung aktualisieren und PHP ZIP aktivieren, oder die CRM-Ablage ausschalten.");
    const returned = new Map((result?.records || []).map(record => [String(record.id), record]));
    if (returned.size !== batch.length || result.records.length !== batch.length || batch.some(record => !returned.has(String(record.id)))) throw new Error("Nicht alle ausgewählten CRM-Datensätze konnten eindeutig geladen werden.");
    merged.push(...batch.map(record => returned.get(String(record.id))));
  }
  if (!merged.length) throw new Error("Bitte mindestens einen CRM-Datensatz auswählen.");
  let combined;
  if (outputMode === "combined") {
    onProgress(`Gemeinsamer DOCX-Serienbrief für ${merged.length} Datensätze wird erstellt …`);
    await nextFrame();
    combined = template.combined(merged);
  }
  const documents = [];
  if (outputMode === "individual-zip" || saveToCrm) {
    let total = 0;
    for (const [index, record] of merged.entries()) {
      onProgress(`DOCX-Einzelbriefe werden erstellt: ${index + 1} von ${merged.length} …`);
      if (index % 5 === 0) await nextFrame();
      const bytes = template.single(record);
      total += bytes.length;
      if (total > 128 * 1024 * 1024) throw new Error("Die DOCX-Einzelbriefe überschreiten zusammen 128 MiB. Bitte weniger Datensätze auswählen.");
      documents.push({ record, bytes });
    }
  }
  if (outputMode === "individual-zip") {
    const names = fileNames(merged).map(name => name.replace(/\.doc$/i, ".docx"));
    const zip = createZip(documents.map((item, index) => ({ name: names[index], content: item.bytes })));
    addDownload(zip, "berliCRM-Einzelbriefe-DOCX.zip", "ZIP-Datei mit echten DOCX-Einzelbriefen herunterladen");
  } else addDownload(new Blob([combined], { type: DOCX_MIME }), "berliCRM-Serienbrief.docx", "Gemeinsamen DOCX-Serienbrief herunterladen");
  let message = outputMode === "individual-zip" ? `${merged.length} echte DOCX-Einzelbriefe als ZIP vorbereitet.` : `Ein echter DOCX-Serienbrief mit ${merged.length} Datensätzen wurde vorbereitet.`;
  const failures = [], saved = [];
  if (saveToCrm) {
    const date = new Date();
    for (const [index, item] of documents.entries()) {
      onProgress(`DOCX-Dokumente werden im CRM gespeichert: ${index + 1} von ${documents.length} …`);
      await nextFrame();
      try {
        if (item.bytes.length > 5 * 1024 * 1024) throw new Error("Die DOCX-Datei überschreitet das CRM-Dateilimit von 5 MiB.");
        const result = await crmClient.saveMailMergeDocument(module.apiName, viewId, item.record.id, createTitle(template.fields, item.record, date), binaryBase64(item.bytes));
        if (!result?.documentId || !/\.docx$/i.test(result.fileName || "")) throw new Error("Das CRM hat keine bestätigte DOCX-Ablage zurückgegeben. Bitte den aktualisierten Word-Webservice prüfen.");
        saved.push(result.documentId);
      } catch (error) { failures.push(`${item.record.name || item.record.id}: ${error.message}`); }
    }
    message += ` CRM-Dokumente gespeichert: ${saved.length} von ${documents.length}.`;
    if (saved.length) message += ` CRM-Dokument-ID(s): ${saved.slice(0, 3).join(", ")}${saved.length > 3 ? ` und ${saved.length - 3} weitere` : ""}.`;
    if (failures.length) message += ` Fehler bei ${failures.length}: ${failures.slice(0, 3).join(" | ")}${failures.length > 3 ? ` | sowie ${failures.length - 3} weitere.` : ""}`;
  }
  return { message, failed: failures.length > 0 };
}
