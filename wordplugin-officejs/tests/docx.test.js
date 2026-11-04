import { test } from "node:test";
import assert from "node:assert/strict";
import { readFile, writeFile } from "node:fs/promises";
import path from "node:path";
import { DOMParser, XMLSerializer } from "@xmldom/xmldom";
import { unzipSync, strFromU8, strToU8 } from "../src/vendor/fflate-0.8.3.js";
import { prepareDocxTemplate, readWordDocx, DOCX_MIME } from "../src/docx-engine.js";
import { runDocxMerge } from "../src/docx-export.js";
import { W, R, REL, makeTemplate, multiPageTemplate, templateParts, recordsFixture, moduleFixture } from "./docx-fixture.js";
globalThis.DOMParser = DOMParser; globalThis.XMLSerializer = XMLSerializer;
const xml = bytes => new DOMParser().parseFromString(strFromU8(bytes), "application/xml");
const nodes = (doc, name, ns = W) => Array.from(doc.getElementsByTagNameNS(ns, name));
const text = doc => nodes(doc, "t").map(node => node.textContent).join("");

test("native DOCX output is a complete OOXML ZIP with original images, styles, margins, headers and footer fields", () => {
  const source = makeTemplate(), original = templateParts();
  const before = source.slice(), engine = prepareDocxTemplate(source, moduleFixture);
  assert.deepEqual(engine.fields.map(field => field.name).sort(), ["firstname", "lastname"]);
  const output = engine.single(recordsFixture[0]), files = unzipSync(output), main = xml(files["word/document.xml"]);
  assert.equal(output[0], 0x50); assert.equal(output[1], 0x4b);
  assert.deepEqual(source, before);
  for (const name of ["word/media/logo.png", "word/styles.xml", "customXml/item1.xml", "word/_rels/document.xml.rels"]) assert.deepEqual(files[name], original[name]);
  assert.match(text(main), /Dear Ada & <Test>/);
  assert.match(text(main), /Before Lovelace after/);
  assert.match(text(xml(files["word/header1.xml"])), /Header Lovelace/);
  assert.equal(nodes(main, "pgMar")[0].getAttributeNS(W, "left"), "1200");
  assert.equal(nodes(main, "fldChar").length, 0);
  assert.equal(nodes(main, "instrText").length, 0);
  assert.equal(nodes(main, "b").length > 0, true);
  assert.equal(nodes(main, "i").length > 0, true);
  assert.equal(nodes(xml(files["word/footer1.xml"]), "fldSimple")[0].getAttributeNS(W, "instr"), "PAGE");
  assert.equal(nodes(xml(files["word/settings.xml"]), "mailMerge").length, 0);
});

test("values containing XML, another placeholder, tabs and line breaks remain literal CRM text", () => {
  const value = "Müller «CONTACT_LAST»\nLine\t& <tag>";
  const files = unzipSync(prepareDocxTemplate(makeTemplate(), moduleFixture).single({ fields: { firstname: value, lastname: "LAST" } }));
  const main = xml(files["word/document.xml"]);
  assert.match(text(main), /Müller «CONTACT_LAST»/);
  assert.match(text(main), /& <tag>/);
  assert.equal(nodes(main, "br").length, 2);
  assert.equal(nodes(main, "tab").length, 2);
});

test("merged content controls cannot reload old placeholders from custom XML when Word opens the letters", () => {
  const binding = '<w:dataBinding w:xpath="/recipient/name" w:storeItemID="{00000000-0000-0000-0000-000000000001}"/>';
  const control = (id, content) => `<w:sdt><w:sdtPr><w:alias w:val="Keep control title"/><w:id w:val="${id}"/>${binding}<w:showingPlcHdr/></w:sdtPr><w:sdtContent>${content}</w:sdtContent></w:sdt>`;
  let main = strFromU8(templateParts()['word/document.xml']);
  const recipient = control(1, '<w:r><w:rPr><w:b/></w:rPr><w:t>«CONTACT_FIRST» «CONTACT_LAST»</w:t></w:r>');
  const sender = control(2, '<w:r><w:t>Unrelated sender property</w:t></w:r>');
  // A real field can also have no visible placeholder result at all.
  const field = control(3, '<w:fldSimple w:instr="MERGEFIELD CONTACT_LAST"><w:r><w:t/></w:r></w:fldSimple>');
  main = main.replace('<w:body>', `<w:body><w:p>${recipient}${sender}${field}</w:p>`);
  const header = `<w:hdr xmlns:w="${W}"><w:p>${recipient}</w:p></w:hdr>`;
  const custom = strToU8('<recipient><name>«CONTACT_FIRST» «CONTACT_LAST»</name></recipient>');
  const input = makeTemplate({ 'word/document.xml': strToU8(main), 'word/header1.xml': strToU8(header), 'customXml/item1.xml': custom });
  const engine = prepareDocxTemplate(input, moduleFixture);
  const output = unzipSync(engine.single(recordsFixture[0]));
  const controls = nodes(xml(output['word/document.xml']), 'sdt');
  assert.equal(nodes(controls[0], 'dataBinding').length, 0);
  assert.equal(nodes(controls[0], 'showingPlcHdr').length, 0);
  assert.match(text(controls[0]), /Ada & <Test> Lovelace/);
  assert.equal(nodes(controls[0], 'b').length, 1);
  assert.equal(nodes(controls[0], 'alias')[0].getAttributeNS(W, 'val'), 'Keep control title');
  assert.equal(nodes(controls[1], 'dataBinding').length, 1, 'unrelated binding remains intact');
  assert.equal(nodes(controls[2], 'dataBinding').length, 0);
  assert.equal(text(controls[2]), 'Lovelace');
  assert.equal(nodes(xml(output['word/header1.xml']), 'dataBinding').length, 0);
  assert.deepEqual(output['customXml/item1.xml'], custom, 'original XML data is not rewritten');
  const combined = unzipSync(engine.combined(recordsFixture));
  const ids = Object.entries(combined).filter(([name]) => /^word\/(document|bcrm\d+_header[^/]*)\.xml$/.test(name))
    .flatMap(([, bytes]) => nodes(xml(bytes), 'sdtPr').map(props => nodes(props, 'id')[0].getAttributeNS(W, 'val')));
  assert.equal(new Set(ids).size, ids.length, 'each copied content control has an independent ID');
  assert.doesNotMatch(text(xml(combined['word/document.xml'])), /«CONTACT_/);
});

test("combined DOCX uses independent personalized headers, section breaks, numbering and unique drawing/bookmark IDs", () => {
  const files = unzipSync(prepareDocxTemplate(makeTemplate(), moduleFixture).combined(recordsFixture));
  const main = xml(files["word/document.xml"]), content = text(main);
  assert.ok(content.indexOf("Ada") < content.indexOf("Grace"));
  assert.match(content, /Lovelace/); assert.match(content, /Hopper/);
  assert.equal(nodes(main, "sectPr").length, 2);
  assert.equal(nodes(main, "type").some(node => node.getAttributeNS(W, "val") === "nextPage"), true);
  const refs = nodes(main, "headerReference").map(node => node.getAttributeNS(R, "id"));
  assert.equal(new Set(refs).size, 2);
  const rels = nodes(xml(files["word/_rels/document.xml.rels"]), "Relationship", REL);
  assert.match(text(xml(files["word/" + rels.find(rel => rel.getAttribute("Id") === refs[0]).getAttribute("Target")])), /Lovelace/);
  assert.match(text(xml(files["word/" + rels.find(rel => rel.getAttribute("Id") === refs[1]).getAttribute("Target")])), /Hopper/);
  for (const tag of ["bookmarkStart", "bookmarkEnd"]) assert.equal(new Set(nodes(main, tag).map(node => node.getAttributeNS(W, "id"))).size, 2);
  assert.equal(new Set(nodes(main, "docPr", "http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing").map(node => node.getAttribute("id"))).size, 2);
  assert.equal(new Set(nodes(main, "numId").map(node => node.getAttributeNS(W, "val"))).size, 2);
  assert.deepEqual(files["word/media/logo.png"], templateParts()["word/media/logo.png"]);
});

test("template problems are rejected instead of producing disguised or corrupted Word files", () => {
  assert.throws(() => prepareDocxTemplate(strToU8("<html>not DOCX</html>"), moduleFixture));
  assert.throws(() => prepareDocxTemplate(makeTemplate({ "word/vbaProject.bin": new Uint8Array([1]) }), moduleFixture), /Makros/);
  assert.throws(() => prepareDocxTemplate(makeTemplate({ "_xmlsignatures/sig.xml": strToU8("<x/>") }), moduleFixture), /Paketsignatur/);
  assert.throws(() => prepareDocxTemplate(makeTemplate({ "../outside": new Uint8Array([1]) }), moduleFixture), /ungültige Teile/);
  assert.throws(() => prepareDocxTemplate(makeTemplate({ "word/document.xml": strToU8(`<w:document xmlns:w="${W}"><w:body><w:ins/><w:p/><w:sectPr/></w:body></w:document>`) }), moduleFixture), /nachverfolgte/);
  const original = strFromU8(templateParts()["word/document.xml"]);
  assert.throws(() => prepareDocxTemplate(makeTemplate({ "word/document.xml": strToU8(original.replace(/CONTACT_FIRST/g, "UNKNOWN_FIELD")) }), moduleFixture), /nicht verfügbar/);
});

test("a blank first section cannot inherit the preceding recipient's personalized header", () => {
  const parts = unzipSync(multiPageTemplate());
  let main = strFromU8(parts['word/document.xml']);
  main = main.replace('<w:headerReference w:type="default" r:id="rIdHeader"/>', '');
  const files = unzipSync(prepareDocxTemplate(makeTemplate({ ...parts, 'word/document.xml': strToU8(main) }), moduleFixture).combined(recordsFixture));
  const sections = nodes(xml(files['word/document.xml']), 'sectPr');
  for (const index of [0, 2]) assert.equal(nodes(sections[index], 'headerReference')[0].getAttributeNS(R, 'id'), 'bcrmEmpty_header_default');
  assert.ok(files['word/bcrm_empty_header_default.xml']);
  assert.doesNotMatch(text(xml(files['word/bcrm_empty_header_default.xml'])), /Lovelace|Hopper/);
});

test("multi-page letters preserve internal sections, page orientation and explicit page breaks for every recipient", () => {
  const source = multiPageTemplate(), engine = prepareDocxTemplate(source, moduleFixture);
  const single = xml(unzipSync(engine.single(recordsFixture[0]))['word/document.xml']);
  assert.equal(nodes(single, 'sectPr').length, 2);
  assert.equal(nodes(single, 'pgSz')[1].getAttributeNS(W, 'orient'), 'landscape');
  const combined = xml(unzipSync(engine.combined(recordsFixture))['word/document.xml']);
  assert.equal(nodes(combined, 'sectPr').length, 4);
  assert.equal(nodes(combined, 'pgSz').filter(node => node.getAttributeNS(W, 'orient') === 'landscape').length, 2);
  assert.equal(text(combined).match(/SECOND PAGE:/g).length, 2);
  // Inserting a section type after pgSz/pgMar violates Word's schema order.
  for (const section of nodes(combined, 'sectPr')) {
    const children = Array.from(section.childNodes).filter(node => node.nodeType === 1).map(node => node.localName);
    if (children.includes('type')) assert.ok(children.indexOf('type') < children.indexOf('pgSz'));
  }
  const plain = new TextDecoder().decode(templateParts()['word/document.xml']).replace('<w:tbl>', '<w:p><w:r><w:br w:type="page"/></w:r></w:p><w:tbl>');
  const explicit = prepareDocxTemplate(makeTemplate({ 'word/document.xml': strToU8(plain) }), moduleFixture);
  assert.equal(nodes(xml(unzipSync(explicit.combined(recordsFixture))['word/document.xml']), 'br').filter(node => node.getAttributeNS(W, 'type') === 'page').length, 2);
});

test("notes and comments are retained in individual DOCX and require ZIP output for multiple recipients", () => {
  const note = strToU8(`<w:footnotes xmlns:w="${W}"><w:footnote w:id="1"><w:p><w:r><w:t>Note</w:t></w:r></w:p></w:footnote></w:footnotes>`);
  const engine = prepareDocxTemplate(makeTemplate({ "word/footnotes.xml": note }), moduleFixture);
  assert.match(text(xml(unzipSync(engine.single(recordsFixture[0]))["word/footnotes.xml"])), /Note/);
  assert.throws(() => engine.combined(recordsFixture), /Einzelbriefe als ZIP/);
});

test("Word's empty note parts, separators and comment metadata do not block multi-page image letters", () => {
  const parts = unzipSync(multiPageTemplate());
  const emptyParts = {
    'word/footnotes.xml': strToU8(`<w:footnotes xmlns:w="${W}"><w:footnote w:type="separator" w:id="-1"><w:p><w:r><w:separator/></w:r></w:p></w:footnote><w:footnote w:type="continuationSeparator" w:id="0"><w:p><w:r><w:continuationSeparator/></w:r></w:p></w:footnote></w:footnotes>`),
    'word/endnotes.xml': strToU8(`<w:endnotes xmlns:w="${W}"><w:endnote w:type="separator" w:id="-1"><w:p><w:r><w:separator/></w:r></w:p></w:endnote><w:endnote w:type="continuationNotice" w:id="0"><w:p/></w:endnote></w:endnotes>`),
    'word/comments.xml': strToU8(`<w:comments xmlns:w="${W}"/>`),
    'word/commentsExtended.xml': strToU8('<w15:commentsEx xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml"/>'),
    'word/people.xml': strToU8('<w15:people xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml"/>'),
  };
  const engine = prepareDocxTemplate(makeTemplate({ ...parts, ...emptyParts }), moduleFixture);
  const output = unzipSync(engine.combined(recordsFixture));
  assert.equal(nodes(xml(output['word/document.xml']), 'sectPr').length, 4);
  assert.deepEqual(output['word/media/logo.png'], parts['word/media/logo.png']);
  assert.equal(nodes(xml(output['word/footnotes.xml']), 'footnote').length, 2);
  assert.equal(nodes(xml(output['word/comments.xml']), 'comment').length, 0);
  // The parts and their relationship metadata remain available for Word.
  assert.deepEqual(output['word/commentsExtended.xml'], emptyParts['word/commentsExtended.xml']);
});

test("actual endnotes, comments and dangling note/comment references still require individual files", () => {
  const cases = [
    { 'word/endnotes.xml': strToU8(`<w:endnotes xmlns:w="${W}"><w:endnote w:id="1"><w:p><w:r><w:t>Actual note</w:t></w:r></w:p></w:endnote></w:endnotes>`) },
    { 'word/comments.xml': strToU8(`<w:comments xmlns:w="${W}"><w:comment w:id="1"><w:p><w:r><w:t>Actual comment</w:t></w:r></w:p></w:comment></w:comments>`) },
    ...['footnoteReference', 'endnoteReference', 'commentReference', 'commentRangeStart', 'commentRangeEnd'].map(tag => ({
      'word/document.xml': strToU8(strFromU8(templateParts()['word/document.xml']).replace('<w:body>', `<w:body><w:p><w:r><w:${tag} w:id="1"/></w:r></w:p>`)),
    })),
  ];
  for (const extra of cases) {
    const engine = prepareDocxTemplate(makeTemplate(extra), moduleFixture);
    assert.throws(() => engine.combined(recordsFixture), /Einzelbriefe als ZIP/);
    assert.ok(unzipSync(engine.single(recordsFixture[0]))['word/document.xml']);
  }
});

function officeFile(bytes, options = {}) {
  const state = { closed: 0, slices: [] };
  const file = { size: bytes.length, sliceCount: 2,
    getSliceAsync(index, done) { state.slices.push(index); const chunk = index ? bytes.subarray(3) : bytes.subarray(0, 3); done(options.fail ? { status: "failed", error: { message: "slice failure" } } : { status: "ok", value: { index, data: [...chunk], size: chunk.length } }); },
    closeAsync(done) { state.closed++; done({ status: "ok" }); },
  };
  return { state, office: { FileType: { Compressed: "compressed" }, AsyncResultStatus: { Succeeded: "ok" }, context: { document: { getFileAsync(type, options, done) { assert.equal(type, "compressed"); done({ status: "ok", value: file }); } } } } };
}

test("the complete Word file is read in slices and the Office file handle is closed on success and failure", async () => {
  const bytes = new Uint8Array([1, 2, 3, 4, 5]);
  const env = officeFile(bytes); assert.deepEqual(await readWordDocx(env.office), bytes);
  assert.equal(env.state.closed, 1); assert.deepEqual(env.state.slices, [0, 1]);
  const failed = officeFile(bytes, { fail: true }); await assert.rejects(readWordDocx(failed.office), /slice failure/); assert.equal(failed.state.closed, 1);
  await assert.rejects(readWordDocx({}), /bisherige Ausgabe/);
});

function exportOptions(overrides = {}) {
  const env = officeFile(makeTemplate()), downloads = [], writes = [], requests = [];
  const options = { office: env.office, module: moduleFixture, records: recordsFixture, viewId: "1", page: 1,
    outputMode: "individual-zip", saveToCrm: true, onProgress() {}, nextFrame: async () => {},
    addDownload: (blob, name) => downloads.push({ blob, name }),
    fileNames: records => records.map(record => record.name + ".doc"), createTitle: () => "Serienbrief Test",
    crmClient: { async getMailMergeData(...args) { requests.push(args); return { records: recordsFixture.toReversed(), documentFormats: ["html-doc", "docx"] }; },
      async saveMailMergeDocument(...args) { writes.push(args); return { documentId: "8x1", fileName: "letter.docx" }; } },
    ...overrides,
  };
  return { options, downloads, writes, requests };
}

test("DOCX ZIP and CRM uploads contain real binary packages in the selected record order", async () => {
  const env = exportOptions(); const result = await runDocxMerge(env.options);
  assert.equal(result.failed, false); assert.equal(env.downloads.length, 1); assert.equal(env.writes.length, 2);
  const letters = unzipSync(new Uint8Array(await env.downloads[0].blob.arrayBuffer()));
  assert.deepEqual(Object.keys(letters), ["Ada.docx", "Grace.docx"]);
  for (const [index, bytes] of Object.values(letters).entries()) {
    const docx = unzipSync(bytes), main = xml(docx['word/document.xml']);
    assert.match(text(main), new RegExp(index ? 'Grace' : 'Ada'));
    assert.match(text(main), new RegExp(index ? 'Hopper' : 'Lovelace'));
    assert.doesNotMatch(text(main), /«CONTACT_(FIRST|LAST)»/);
    assert.equal(nodes(main, 'fldChar').length, 0);
  }
  assert.equal(env.writes[0][2], "1"); assert.equal(env.writes[1][2], "2");
  for (const args of env.writes) assert.ok(unzipSync(new Uint8Array(Buffer.from(args[4], "base64")))["word/document.xml"]);
  const combined = exportOptions({ outputMode: "combined", saveToCrm: false });
  await runDocxMerge(combined.options); assert.equal(combined.downloads[0].blob.type, DOCX_MIME); assert.equal(combined.downloads[0].name.endsWith(".docx"), true);
});

test("older CRM servers cannot receive DOCX files mislabeled as .doc", async () => {
  const env = exportOptions(); env.options.crmClient.getMailMergeData = async () => ({ records: recordsFixture });
  await assert.rejects(runDocxMerge(env.options), /Server unterstützt die DOCX-Ablage noch nicht/);
  assert.equal(env.writes.length, 0); assert.equal(env.downloads.length, 0);
});

test("DOCX upload failures retain the prepared download and report partial CRM success without retrying", async () => {
  const env = exportOptions(); let count = 0;
  env.options.crmClient.saveMailMergeDocument = async () => { if (++count === 2) throw new Error("denied"); return { documentId: "8x1", fileName: "first.docx" }; };
  const result = await runDocxMerge(env.options);
  assert.equal(result.failed, true); assert.match(result.message, /1 von 2/); assert.equal(count, 2); assert.equal(env.downloads.length, 1);
});

test("DOCX is the default, legacy remains available and the DOCX implementation is loaded on demand", async () => {
  const html = await readFile(new URL("../taskpane.html", import.meta.url), "utf8");
  assert.match(html, /value="legacy"/);
  assert.match(html, /value="docx" selected/);
  assert.match(html, /Version 1\.3\.0\.4/);
  assert.match(await readFile(new URL('../manifest.xml', import.meta.url), 'utf8'), /<Version>1\.3\.0\.4<\/Version>/);
  const source = await readFile(new URL("../src/taskpane.js", import.meta.url), "utf8");
  assert.match(source, /outputFormat\?\.value !== "docx"\) return createLegacyMergeDocuments/);
  assert.doesNotMatch(source, /^import[^\n]*docx/m);
});

test("write review artifacts for independent package and server validation", async () => {
  if (!process.env.BERLICRM_DOCX_ARTIFACTS) return;
  const engine = prepareDocxTemplate(makeTemplate(), moduleFixture);
  await writeFile(path.join(process.env.BERLICRM_DOCX_ARTIFACTS, "template.docx"), makeTemplate());
  await writeFile(path.join(process.env.BERLICRM_DOCX_ARTIFACTS, "single.docx"), engine.single(recordsFixture[0]));
  await writeFile(path.join(process.env.BERLICRM_DOCX_ARTIFACTS, "combined.docx"), engine.combined(recordsFixture));
  const multi = prepareDocxTemplate(multiPageTemplate(), moduleFixture);
  await writeFile(path.join(process.env.BERLICRM_DOCX_ARTIFACTS, "multipage-template.docx"), multiPageTemplate());
  await writeFile(path.join(process.env.BERLICRM_DOCX_ARTIFACTS, "multipage-single.docx"), multi.single(recordsFixture[0]));
  await writeFile(path.join(process.env.BERLICRM_DOCX_ARTIFACTS, "multipage-combined.docx"), multi.combined(recordsFixture));
  const zipEnv = exportOptions({ saveToCrm: false, office: officeFile(multiPageTemplate()).office });
  await runDocxMerge(zipEnv.options);
  for (const [index, bytes] of Object.values(unzipSync(new Uint8Array(await zipEnv.downloads[0].blob.arrayBuffer()))).entries()) {
    await writeFile(path.join(process.env.BERLICRM_DOCX_ARTIFACTS, `zip-letter-${index + 1}.docx`), bytes);
  }
});
