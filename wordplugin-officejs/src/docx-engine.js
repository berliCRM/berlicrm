import { t } from "./i18n.js?v=20261105.1";
import { unzipSync, zipSync, strFromU8, strToU8 } from "./vendor/fflate-0.8.3.js";
import { mergeFieldNames } from "./crm-client.js?v=20261105.1";

export const DOCX_MIME = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
const W = "http://schemas.openxmlformats.org/wordprocessingml/2006/main";
const R = "http://schemas.openxmlformats.org/officeDocument/2006/relationships";
const REL = "http://schemas.openxmlformats.org/package/2006/relationships";
const CT = "http://schemas.openxmlformats.org/package/2006/content-types";
const XML = "http://www.w3.org/XML/1998/namespace";
const MAIN = "word/document.xml";
const MAX_EXPANDED = 128 * 1024 * 1024;
const all = (node, local, ns = W) => Array.from(node.getElementsByTagNameNS(ns, local));
const is = (node, local, ns = W) => node?.namespaceURI === ns && node.localName === local;
const direct = (node, local) => Array.from(node.childNodes).find(child => is(child, local));
const serialize = node => new XMLSerializer().serializeToString(node);
const stories = name => /^word\/(document|header[^/]*|footer[^/]*|footnotes|endnotes|comments)\.xml$/.test(name);

function parse(bytes, name) {
  const source = typeof bytes === "string" ? bytes : strFromU8(bytes);
  if (/<!DOCTYPE|<!ENTITY/i.test(source)) throw new Error(t("DISALLOWED_XML_DECLARATION_IN", [name]));
  const doc = new DOMParser().parseFromString(source, "application/xml");
  if (!doc.documentElement || doc.getElementsByTagName("parsererror").length) throw new Error(t("INVALID_DOCX_XML", [name]));
  return doc;
}

function valueText(value) {
  if (value === null || value === undefined) return "";
  if (Array.isArray(value)) return value.map(valueText).filter(Boolean).join(", ");
  if (typeof value === "object") return valueText(value.label ?? value.name ?? value.value ?? "");
  return String(value).replace(/[\u0000-\u0008\u000b\u000c\u000e-\u001f]/g, "");
}

function fieldName(instruction) {
  return /^\s*MERGEFIELD\s+(?:"([^"]+)"|([^\s]+))/i.exec(instruction)?.slice(1).find(Boolean) || "";
}

function fieldValue(instruction, value) {
  let text = valueText(value);
  if (/\\\*\s+Upper\b/i.test(instruction)) text = text.toLocaleUpperCase("de-DE");
  if (/\\\*\s+Lower\b/i.test(instruction)) text = text.toLocaleLowerCase("de-DE");
  if (text) text = (/\\b\s+"([^"]*)"/i.exec(instruction)?.[1] || "") + text + (/\\f\s+"([^"]*)"/i.exec(instruction)?.[1] || "");
  return text;
}

function textChildren(doc, text) {
  return text.replace(/\r\n?/g, "\n").split(/([\n\t])/).map(part => {
    if (part === "\n" || part === "\t") return doc.createElementNS(W, part === "\n" ? "w:br" : "w:tab");
    const node = doc.createElementNS(W, "w:t");
    node.setAttributeNS(XML, "xml:space", "preserve");
    node.appendChild(doc.createTextNode(part));
    return node;
  });
}

function ancestor(node, local) {
  while (node && !is(node, local)) node = node.parentNode;
  return node;
}

function makeRun(doc, reference, text) {
  const run = doc.createElementNS(W, "w:r");
  const properties = reference && direct(reference, "rPr");
  if (properties) run.appendChild(properties.cloneNode(true));
  for (const child of textChildren(doc, text)) run.appendChild(child);
  return run;
}

function complexFields(doc) {
  const stack = [], fields = [];
  function visit(node) {
    if (is(node, "del")) return;
    if (is(node, "fldChar")) {
      const type = node.getAttributeNS(W, "fldCharType");
      if (type === "begin") stack.push({ begin: node, markers: [node], instructions: [], result: [], code: "", separated: false, nested: stack.length > 0 });
      else if (stack.length && type === "separate") { stack.at(-1).separated = true; stack.at(-1).markers.push(node); }
      else if (stack.length && type === "end") { const field = stack.pop(); field.markers.push(node); fields.push(field); }
    } else if (is(node, "instrText") && stack.length && !stack.at(-1).separated) {
      stack.at(-1).code += node.textContent; stack.at(-1).instructions.push(node);
    } else if (node.namespaceURI === W && ["t", "tab", "br", "drawing", "pict", "object"].includes(node.localName)) {
      for (const field of stack) if (field.separated) field.result.push(node);
    }
    for (const child of Array.from(node.childNodes || [])) visit(child);
  }
  visit(doc.documentElement);
  if (stack.some(field => /MERGEFIELD/i.test(field.code))) throw new Error(t("THE_TEMPLATE_CONTAINS_AN_UNCLOSED_MAIL_MERGE_FIELD"));
  return fields;
}

function replacePlainPlaceholders(doc, values) {
  const escaped = [...values.keys()].map(name => `«${name}»`).sort((a, b) => b.length - a.length).map(s => s.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"));
  if (!escaped.length) return 0;
  const pattern = new RegExp(escaped.join("|"), "g");
  let count = 0;
  for (const paragraph of all(doc, "p")) {
    const nodes = all(paragraph, "t").filter(node => ancestor(node, "p") === paragraph);
    let text = "";
    const ranges = nodes.map(node => { const start = text.length; text += node.textContent; return { node, start, end: text.length }; });
    for (const match of [...text.matchAll(pattern)].reverse()) {
      const start = ranges.find(item => match.index >= item.start && match.index < item.end);
      const endAt = match.index + match[0].length;
      const end = ranges.find(item => endAt > item.start && endAt <= item.end);
      if (!start || !end) continue;
      const replacement = values.get(match[0].slice(1, -1));
      if (start === end) start.node.textContent = start.node.textContent.slice(0, match.index - start.start) + replacement + start.node.textContent.slice(endAt - start.start);
      else {
        start.node.textContent = start.node.textContent.slice(0, match.index - start.start) + replacement;
        end.node.textContent = end.node.textContent.slice(endAt - end.start);
        for (const item of ranges) if (item.start >= start.end && item.end <= end.start) item.node.textContent = "";
      }
      count++;
    }
  }
  for (const node of all(doc, "t")) {
    if (/[\n\r\t]/.test(node.textContent)) {
      for (const replacement of textChildren(doc, node.textContent)) node.parentNode.insertBefore(replacement, node);
      node.parentNode.removeChild(node);
    } else node.setAttributeNS(XML, "xml:space", "preserve");
  }
  return count;
}

function unbindMergeControls(doc, values) {
  const affected = new Set();
  const markAncestors = node => {
    for (let current = node; current; current = current.parentNode) if (is(current, "sdt")) affected.add(current);
  };
  const placeholders = [...values.keys()].map(name => `«${name}»`);
  for (const control of all(doc, "sdt")) {
    const content = direct(control, "sdtContent");
    const text = content ? all(content, "t").map(node => node.textContent).join("") : "";
    if (placeholders.some(placeholder => text.includes(placeholder))) markAncestors(control);
  }
  for (const field of all(doc, "fldSimple")) if (values.has(fieldName(field.getAttributeNS(W, "instr")))) markAncestors(field);
  for (const field of complexFields(doc)) if (values.has(fieldName(field.code))) markAncestors(field.begin);
  for (const control of affected) {
    const properties = direct(control, "sdtPr");
    if (!properties) continue;
    // A bound control otherwise reloads its original custom-XML value when
    // Word opens the finished letter, undoing the successful field replacement.
    for (const child of Array.from(properties.childNodes)) if (is(child, "dataBinding") || is(child, "showingPlcHdr")) properties.removeChild(child);
  }
}

function mergeStory(doc, values) {
  unbindMergeControls(doc, values);
  // Replace visible placeholders before inserting field values, so CRM text is
  // never interpreted as another placeholder in a second replacement pass.
  let count = replacePlainPlaceholders(doc, values);
  for (const field of all(doc, "fldSimple")) {
    const code = field.getAttributeNS(W, "instr"), name = fieldName(code);
    if (!name || !values.has(name)) continue;
    field.parentNode.replaceChild(makeRun(doc, all(field, "r")[0], fieldValue(code, values.get(name))), field);
    count++;
  }
  for (const field of complexFields(doc)) {
    const name = fieldName(field.code);
    if (!name || !values.has(name)) continue;
    if (field.nested) throw new Error(t("NESTED_MERGE_FIELD_FOR_DOCX_PLEASE_USE_A_TEMPLATE_WITHOUT_NESTED_IF_FIEL", [name]));
    const startRun = ancestor(field.begin, "r");
    if (!startRun) throw new Error(t("INVALID_FIELD_START_FOR", [name]));
    const before = startRun.cloneNode(false), properties = direct(startRun, "rPr");
    if (properties) before.appendChild(properties.cloneNode(true));
    for (const child of Array.from(startRun.childNodes)) {
      if (child === field.begin) break;
      if (!is(child, "rPr")) before.appendChild(child);
    }
    if (Array.from(before.childNodes).some(child => !is(child, "rPr"))) startRun.parentNode.insertBefore(before, startRun);
    const run = makeRun(doc, ancestor(field.result[0], "r") || startRun, fieldValue(field.code, values.get(name)));
    startRun.parentNode.insertBefore(run, startRun);
    for (const node of [...field.markers, ...field.instructions, ...field.result]) if (node.parentNode) node.parentNode.removeChild(node);
    count++;
  }
  return count;
}

function storyFieldNames(doc) {
  const names = all(doc, "fldSimple").map(node => fieldName(node.getAttributeNS(W, "instr")));
  names.push(...complexFields(doc).map(field => fieldName(field.code)));
  return names.filter(Boolean);
}

function pack(files) {
  const entries = Object.create(null);
  for (const [name, content] of Object.entries(files)) entries[name] = [content, { level: /\.(xml|rels)$/.test(name) ? 6 : 0 }];
  return zipSync(entries, { level: 6 });
}

function combinedNoteContent(files, storyNames) {
  const found = new Set();
  for (const name of storyNames) {
    const doc = parse(files[name], name);
    // Word can keep these parts even when the document has no actual notes.
    // Separator/continuation records are layout definitions, not user notes.
    for (const [kind, label] of [["footnote", t("FOOTNOTES")], ["endnote", t("ENDNOTES")]]) {
      if (all(doc, kind + "Reference").length || all(doc, kind).some(node => !["separator", "continuationSeparator", "continuationNotice"].includes(node.getAttributeNS(W, "type")))) found.add(label);
    }
    if (["comment", "commentReference", "commentRangeStart", "commentRangeEnd"].some(tag => all(doc, tag).length)) found.add(t("COMMENTS"));
  }
  return [...found];
}

function newIds(node, state, prefix) {
  const bookmarks = new Map();
  for (const mark of all(node, "bookmarkStart")) {
    const old = mark.getAttributeNS(W, "id"), name = mark.getAttributeNS(W, "name");
    const id = String(++state.bookmark);
    bookmarks.set(old, id); mark.setAttributeNS(W, "w:id", id);
    if (name) mark.setAttributeNS(W, "w:name", prefix + name);
  }
  for (const mark of all(node, "bookmarkEnd")) if (bookmarks.has(mark.getAttributeNS(W, "id"))) mark.setAttributeNS(W, "w:id", bookmarks.get(mark.getAttributeNS(W, "id")));
  for (const link of all(node, "hyperlink")) {
    const anchor = link.getAttributeNS(W, "anchor");
    if (anchor) link.setAttributeNS(W, "w:anchor", prefix + anchor);
  }
  for (const code of all(node, "instrText")) code.textContent = code.textContent.replace(/\b(REF|PAGEREF)\s+("?)([^\s"\\]+)\2/g, (match, kind, quote, name) => `${kind} ${quote}${prefix}${name}${quote}`);
  for (const drawing of all(node, "docPr", "http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing")) drawing.setAttribute("id", String(++state.drawing));
  for (const properties of all(node, "sdtPr")) {
    const id = direct(properties, "id");
    if (!id) continue;
    do { state.control++; } while (state.controlIds.has(String(state.control)));
    id.setAttributeNS(W, "w:val", String(state.control)); state.controlIds.add(String(state.control));
  }
}

export function prepareDocxTemplate(bytes, module) {
  if (!(bytes instanceof Uint8Array) || bytes.length > 32 * 1024 * 1024) throw new Error(t("THE_WORD_TEMPLATE_IS_INVALID_OR_EXCEEDS_32_MIB"));
  let expanded = 0, entries = 0;
  const names = new Set();
  const files = unzipSync(bytes, { filter: entry => {
    expanded += entry.originalSize; entries++;
    if (expanded > MAX_EXPANDED || entries > 4096 || names.has(entry.name) || entry.name.includes("\\") || entry.name.startsWith("/") || entry.name.split("/").includes("..")) throw new Error(t("THE_DOCX_PACKAGE_IS_TOO_LARGE_OR_CONTAINS_INVALID_PARTS"));
    names.add(entry.name); return true;
  } });
  if (!files[MAIN] || !files["[Content_Types].xml"] || !files["_rels/.rels"]) throw new Error(t("THE_TEMPLATE_IS_NOT_A_COMPLETE_DOCX_FILE"));
  if (Object.keys(files).some(name => /vbaProject|^_xmlsignatures\//i.test(name))) throw new Error(t("FOR_DOCX_PLEASE_USE_A_TEMPLATE_WITHOUT_MACROS_OR_A_DIGITAL_PACKAGE_SIGNA"));
  const document = parse(files[MAIN], MAIN);
  if (!is(document.documentElement, "document") || !direct(document.documentElement, "body")) throw new Error(t("THIS_WORD_XML_FORMAT_IS_NOT_YET_SUPPORTED_FOR_DOCX"));
  const mainType = all(parse(files["[Content_Types].xml"], "[Content_Types].xml"), "Override", CT).find(node => node.getAttribute("PartName") === "/" + MAIN);
  if (mainType?.getAttribute("ContentType") !== "application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml") throw new Error(t("THE_TEMPLATE_MUST_BE_A_STANDARD_WORD_DOCUMENT_DOCX"));
  const available = new Map();
  for (const field of module.fields) {
    for (const name of mergeFieldNames(module, field)) {
      if (available.has(name) && available.get(name).name !== field.name) throw new Error(t("AMBIGUOUS_MERGE_FIELD_NAME", [name]));
      available.set(name, field);
    }
  }
  const used = new Set(), storyNames = Object.keys(files).filter(stories);
  for (const name of storyNames) {
    const doc = parse(files[name], name);
    if (["ins", "del", "moveFrom", "moveTo"].some(tag => all(doc, tag).length)) throw new Error(t("FOR_DOCX_OUTPUT_PLEASE_ACCEPT_OR_REJECT_TRACKED_CHANGES_IN_THE_TEMPLATE"));
    for (const field of storyFieldNames(doc)) {
      if (!available.has(field)) throw new Error(t("MERGE_FIELD_IS_NOT_AVAILABLE_FOR_THE_SELECTED_CRM_MODULE", [field]));
      used.add(field);
    }
    const text = all(doc, "t").map(node => node.textContent).join("");
    for (const field of available.keys()) if (text.includes(`«${field}»`)) used.add(field);
  }
  if (!used.size) throw new Error(t("THE_DOCX_TEMPLATE_CONTAINS_NO_RECOGNIZABLE_MERGE_FIELDS_FOR_THE_SELECTED"));

  function render(record) {
    const result = { ...files };
    const values = new Map([...used].map(name => [name, valueText(record.fields?.[available.get(name).name])]));
    let count = 0;
    for (const name of storyNames) {
      const doc = parse(files[name], name);
      count += mergeStory(doc, values); result[name] = strToU8(serialize(doc));
    }
    if (!count) throw new Error(t("NO_DOCX_MERGE_FIELDS_COULD_BE_REPLACED"));
    if (result["word/settings.xml"]) {
      const settings = parse(result["word/settings.xml"], "settings.xml");
      for (const merge of all(settings, "mailMerge")) merge.parentNode.removeChild(merge);
      result["word/settings.xml"] = strToU8(serialize(settings));
    }
    return result;
  }

  function combined(records) {
    if (!records.length || records.length > 1000) throw new Error(t("DOCX_REQUIRES_BETWEEN_1_AND_1_000_RECORDS"));
    if (records.length === 1) return pack(render(records[0]));
    const noteContent = combinedNoteContent(files, storyNames);
    if (noteContent.length) throw new Error(t("THE_TEMPLATE_CONTAINS_THESE_ELEMENTS_ARE_NOT_YET_SUPPORTED_IN_COMBINED_D", [noteContent.join(", ")]));
    const output = render(records[0]);
    const main = parse(output[MAIN], MAIN), body = direct(main.documentElement, "body");
    while (body.firstChild) body.removeChild(body.firstChild);
    const types = parse(output["[Content_Types].xml"], "[Content_Types].xml");
    const relPath = "word/_rels/document.xml.rels";
    const rels = parse(output[relPath] || `<Relationships xmlns="${REL}"/>`, relPath);
    const baseRels = all(rels, "Relationship", REL).map(node => node.cloneNode(true));
    const emptyReferences = [];
    const sourceFirstSection = all(document, "sectPr")[0];
    // A missing first-section header means blank in a separate source file.
    // Explicit blank references prevent it inheriting the previous letter's
    // last-section header/footer after concatenation.
    for (const kind of ["header", "footer"]) for (const variant of ["default", "first", "even"]) {
      const tag = kind + "Reference";
      if (!all(document, tag).some(node => node.getAttributeNS(W, "type") === variant) || all(sourceFirstSection || document, tag).some(node => node.getAttributeNS(W, "type") === variant)) continue;
      const name = `word/bcrm_empty_${kind}_${variant}.xml`, id = `bcrmEmpty_${kind}_${variant}`;
      output[name] = strToU8(`<w:${kind === "header" ? "hdr" : "ftr"} xmlns:w="${W}"><w:p/></w:${kind === "header" ? "hdr" : "ftr"}>`);
      const rel = rels.createElementNS(REL, "Relationship");
      rel.setAttribute("Id", id); rel.setAttribute("Type", `${R}/${kind}`); rel.setAttribute("Target", name.slice(5)); rels.documentElement.appendChild(rel);
      const type = types.createElementNS(CT, "Override");
      type.setAttribute("PartName", "/" + name); type.setAttribute("ContentType", `application/vnd.openxmlformats-officedocument.wordprocessingml.${kind}+xml`); types.documentElement.appendChild(type);
      emptyReferences.push({ tag, variant, id });
    }
    const originalControlIds = storyNames.flatMap(name => all(parse(files[name], name), "sdtPr").map(node => direct(node, "id")?.getAttributeNS(W, "val")).filter(Boolean));
    const state = { drawing: 0, bookmark: 0, control: 0, controlIds: new Set(originalControlIds) };
    const numbering = output["word/numbering.xml"] ? parse(output["word/numbering.xml"], "numbering.xml") : null;
    const originalNums = numbering ? all(numbering, "num").map(node => node.cloneNode(true)) : [];
    let nextNum = Math.max(0, ...originalNums.map(node => Number(node.getAttributeNS(W, "numId"))));
    let combinedLength = 0;
    for (const [index, record] of records.entries()) {
      const part = render(record), doc = parse(part[MAIN], MAIN), partBody = direct(doc.documentElement, "body");
      combinedLength += part[MAIN].length;
      if (combinedLength > 48 * 1024 * 1024) throw new Error(t("THE_COMBINED_DOCX_DOCUMENT_WOULD_BE_TOO_LARGE_PLEASE_SELECT_FEWER_RECORD"));
      const prefix = `bcrm${index + 1}_`, relMap = new Map(), numMap = new Map();
      if (index && numbering) for (const num of originalNums) {
        const copy = num.cloneNode(true), id = String(++nextNum);
        numMap.set(num.getAttributeNS(W, "numId"), id); copy.setAttributeNS(W, "w:numId", id); numbering.documentElement.appendChild(copy);
      }
      const remap = node => { newIds(node, state, prefix); for (const num of all(node, "numId")) if (numMap.has(num.getAttributeNS(W, "val"))) num.setAttributeNS(W, "w:val", numMap.get(num.getAttributeNS(W, "val"))); };
      for (const relation of baseRels.filter(node => /\/(header|footer)$/.test(node.getAttribute("Type")))) {
        const target = relation.getAttribute("Target");
        if (target.includes("/") || target.includes("..") || relation.getAttribute("TargetMode") === "External") throw new Error(t("UNUSUAL_HEADER_FOOTER_PATH_PLEASE_SELECT_INDIVIDUAL_DOCX_LETTERS"));
        const oldName = "word/" + target, newName = "word/" + prefix + target;
        if (!part[oldName]) throw new Error(t("A_HEADER_OR_FOOTER_IS_MISSING_FROM_THE_DOCX_TEMPLATE"));
        const header = parse(part[oldName], oldName); remap(header); output[newName] = strToU8(serialize(header));
        const oldRelName = "word/_rels/" + target + ".rels", newRelName = "word/_rels/" + prefix + target + ".rels";
        if (part[oldRelName]) output[newRelName] = part[oldRelName];
        const copy = relation.cloneNode(true), id = prefix + relation.getAttribute("Id");
        copy.setAttribute("Id", id); copy.setAttribute("Target", prefix + target); rels.documentElement.appendChild(copy);
        relMap.set(relation.getAttribute("Id"), id);
        const originalType = all(types, "Override", CT).find(node => node.getAttribute("PartName") === "/" + oldName);
        if (originalType) { const type = originalType.cloneNode(true); type.setAttribute("PartName", "/" + newName); types.documentElement.appendChild(type); }
      }
      remap(partBody);
      for (const tag of ["headerReference", "footerReference"]) for (const reference of all(partBody, tag)) {
        const oldId = reference.getAttributeNS(R, "id");
        if (relMap.has(oldId)) reference.setAttributeNS(R, "r:id", relMap.get(oldId));
      }
      const finalSection = direct(partBody, "sectPr");
      if (!finalSection) throw new Error(t("THE_DOCX_TEMPLATE_HAS_NO_FINAL_SECTION_SETTINGS"));
      const firstSection = all(partBody, "sectPr")[0];
      for (const empty of emptyReferences) {
        const reference = doc.createElementNS(W, "w:" + empty.tag);
        reference.setAttributeNS(W, "w:type", empty.variant); reference.setAttributeNS(R, "r:id", empty.id);
        const next = Array.from(firstSection.childNodes).find(child => child.namespaceURI === W && (empty.tag === "headerReference" ? child.localName !== "headerReference" : !["headerReference", "footerReference"].includes(child.localName)));
        firstSection.insertBefore(reference, next || null);
      }
      // Section type describes the start of that section. Only the first
      // section of subsequent letters must start on a new page; internal
      // continuous/landscape sections retain their original settings.
      if (index) {
        let type = direct(firstSection, "type");
        if (!type) {
          type = doc.createElementNS(W, "w:type");
          const afterType = Array.from(firstSection.childNodes).find(child => child.namespaceURI === W && !["headerReference", "footerReference", "footnotePr", "endnotePr"].includes(child.localName));
          firstSection.insertBefore(type, afterType || null);
        }
        type.setAttributeNS(W, "w:val", "nextPage");
      }
      partBody.removeChild(finalSection);
      for (const child of Array.from(partBody.childNodes)) body.appendChild(main.importNode(child, true));
      if (index < records.length - 1) {
        let paragraph = body.lastChild;
        while (paragraph && paragraph.nodeType !== 1) paragraph = paragraph.previousSibling;
        if (!is(paragraph, "p") || (direct(paragraph, "pPr") && direct(direct(paragraph, "pPr"), "sectPr"))) {
          paragraph = main.createElementNS(W, "w:p"); body.appendChild(paragraph);
        }
        let props = direct(paragraph, "pPr");
        if (!props) { props = main.createElementNS(W, "w:pPr"); paragraph.insertBefore(props, paragraph.firstChild); }
        props.insertBefore(main.importNode(finalSection, true), direct(props, "pPrChange") || null);
      } else body.appendChild(main.importNode(finalSection, true));
    }
    output[MAIN] = strToU8(serialize(main)); output[relPath] = strToU8(serialize(rels)); output["[Content_Types].xml"] = strToU8(serialize(types));
    if (numbering) output["word/numbering.xml"] = strToU8(serialize(numbering));
    return pack(output);
  }
  return { fields: [...new Set([...used].map(name => available.get(name)))], single: record => pack(render(record)), combined };
}

export async function readWordDocx(office) {
  if (!office?.context?.document?.getFileAsync || !office.FileType?.Compressed) throw new Error(t("THIS_WORD_VERSION_DOES_NOT_SUPPORT_FULL_DOCX_EXPORT_PLEASE_SELECT_THE_LE"));
  const ok = office.AsyncResultStatus.Succeeded;
  const file = await new Promise((resolve, reject) => office.context.document.getFileAsync(office.FileType.Compressed, { sliceSize: 65536 }, result => result.status === ok ? resolve(result.value) : reject(new Error(result.error?.message || t("COULD_NOT_READ_THE_WORD_FILE")))));
  try {
    if (!file || file.size > 32 * 1024 * 1024 || !Number.isInteger(file.sliceCount) || file.sliceCount < 1 || file.sliceCount > 8192) throw new Error(t("THE_WORD_FILE_IS_INVALID_OR_EXCEEDS_32_MIB"));
    const chunks = []; let length = 0;
    for (let index = 0; index < file.sliceCount; index++) {
      const slice = await new Promise((resolve, reject) => file.getSliceAsync(index, result => result.status === ok ? resolve(result.value) : reject(new Error(result.error?.message || t("COULD_NOT_READ_PART_OF_THE_WORD_FILE")))));
      if (slice.index !== index || !slice.data || slice.data.length !== slice.size) throw new Error(t("WORD_RETURNED_AN_INCOMPLETE_FILE_PART"));
      const bytes = new Uint8Array(slice.data); chunks.push(bytes); length += bytes.length;
      if (length > 32 * 1024 * 1024) throw new Error(t("THE_WORD_FILE_IS_TOO_LARGE"));
    }
    if (length !== file.size) throw new Error(t("COULD_NOT_READ_THE_COMPLETE_WORD_FILE"));
    const bytes = new Uint8Array(length); let offset = 0;
    for (const chunk of chunks) { bytes.set(chunk, offset); offset += chunk.length; }
    return bytes;
  } finally { if (file?.closeAsync) await new Promise(resolve => file.closeAsync(resolve)); }
}
