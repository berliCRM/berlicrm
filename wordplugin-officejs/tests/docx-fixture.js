import { zipSync, strToU8 } from "../src/vendor/fflate-0.8.3.js";

export const W = "http://schemas.openxmlformats.org/wordprocessingml/2006/main";
export const R = "http://schemas.openxmlformats.org/officeDocument/2006/relationships";
export const REL = "http://schemas.openxmlformats.org/package/2006/relationships";
export const moduleFixture = { apiName: "Contacts", prefix: "CONTACT", fields: [{ name: "firstname", label: "FIRST" }, { name: "lastname", label: "LAST" }] };
export const recordsFixture = [
  { id: "1", name: "Ada", fields: { firstname: "Ada & <Test>", lastname: "Lovelace" } },
  { id: "2", name: "Grace", fields: { firstname: "Grace", lastname: "Hopper" } },
];
export function templateParts() {
  const parts = {
    "[Content_Types].xml": `<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/><Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/><Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/><Override PartName="/word/header1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/><Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/></Types>`,
    "_rels/.rels": `<Relationships xmlns="${REL}"><Relationship Id="rId1" Type="${R}/officeDocument" Target="word/document.xml"/></Relationships>`,
    "word/document.xml": `<w:document xmlns:w="${W}" xmlns:r="${R}" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"><w:body><w:p><w:pPr><w:pStyle w:val="Letter"/><w:numPr><w:numId w:val="1"/></w:numPr></w:pPr><w:bookmarkStart w:id="4" w:name="Recipient"/><w:r><w:t>Dear </w:t></w:r><w:fldSimple w:instr=" MERGEFIELD CONTACT_FIRST "><w:r><w:rPr><w:b/></w:rPr><w:t>«CONTACT_FIRST»</w:t></w:r></w:fldSimple><w:bookmarkEnd w:id="4"/></w:p><w:p><w:r><w:t>Before </w:t><w:fldChar w:fldCharType="begin"/></w:r><w:r><w:instrText> MERGE</w:instrText></w:r><w:r><w:instrText>FIELD CONTACT_LAST </w:instrText></w:r><w:r><w:fldChar w:fldCharType="separate"/></w:r><w:r><w:rPr><w:i/></w:rPr><w:t>«CONTACT_LAST»</w:t></w:r><w:r><w:fldChar w:fldCharType="end"/><w:t> after</w:t></w:r></w:p><w:tbl><w:tr><w:tc><w:p><w:r><w:t>«CONTACT_</w:t></w:r><w:r><w:t>FIRST»</w:t></w:r></w:p></w:tc></w:tr></w:tbl><w:p><w:r><w:drawing><wp:inline><wp:docPr id="42" name="Logo"/></wp:inline></w:drawing></w:r></w:p><w:sectPr><w:headerReference w:type="default" r:id="rIdHeader"/><w:footerReference w:type="default" r:id="rIdFooter"/><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1440" w:bottom="1440" w:left="1200" w:right="1200"/></w:sectPr></w:body></w:document>`,
    "word/_rels/document.xml.rels": `<Relationships xmlns="${REL}"><Relationship Id="rIdHeader" Type="${R}/header" Target="header1.xml"/><Relationship Id="rIdFooter" Type="${R}/footer" Target="footer1.xml"/><Relationship Id="rIdImage" Type="${R}/image" Target="media/logo.png"/><Relationship Id="rIdStyles" Type="${R}/styles" Target="styles.xml"/><Relationship Id="rIdNumbering" Type="${R}/numbering" Target="numbering.xml"/><Relationship Id="rIdSettings" Type="${R}/settings" Target="settings.xml"/></Relationships>`,
    "word/header1.xml": `<w:hdr xmlns:w="${W}"><w:p><w:r><w:t>Header </w:t></w:r><w:fldSimple w:instr="MERGEFIELD CONTACT_LAST"><w:r><w:t>«CONTACT_LAST»</w:t></w:r></w:fldSimple></w:p></w:hdr>`,
    "word/_rels/header1.xml.rels": `<Relationships xmlns="${REL}"><Relationship Id="rLogo" Type="${R}/image" Target="media/logo.png"/></Relationships>`,
    "word/footer1.xml": `<w:ftr xmlns:w="${W}"><w:p><w:r><w:t>Footer </w:t></w:r><w:fldSimple w:instr="PAGE"><w:r><w:t>1</w:t></w:r></w:fldSimple></w:p></w:ftr>`,
    "word/styles.xml": `<w:styles xmlns:w="${W}"><w:style w:type="paragraph" w:styleId="Letter"><w:name w:val="Letter"/><w:rPr><w:rFonts w:ascii="Calibri"/><w:sz w:val="22"/></w:rPr></w:style></w:styles>`,
    "word/settings.xml": `<w:settings xmlns:w="${W}"><w:mailMerge><w:mainDocumentType w:val="formLetters"/></w:mailMerge><w:zoom w:percent="100"/></w:settings>`,
    "word/numbering.xml": `<w:numbering xmlns:w="${W}"><w:abstractNum w:abstractNumId="0"><w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="decimal"/><w:lvlText w:val="%1."/></w:lvl></w:abstractNum><w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num></w:numbering>`,
    "customXml/item1.xml": "<example>Preserve unchanged</example>",
  };
  const bytes = Object.fromEntries(Object.entries(parts).map(([name, xml]) => [name, strToU8(xml)]));
  bytes["word/media/logo.png"] = new Uint8Array(Buffer.from("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j7fQAAAAASUVORK5CYII=", "base64"));
  return bytes;
}
export function makeTemplate(overrides = {}) { return zipSync({ ...templateParts(), ...overrides }); }

/** Two-page source with an actual embedded image and a landscape second section. */
export function multiPageTemplate() {
  const parts = templateParts();
  let main = new TextDecoder().decode(parts['word/document.xml']);
  const drawing = `<wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="914400" cy="914400"/><wp:docPr id="42" name="Logo"/><wp:cNvGraphicFramePr/><a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:nvPicPr><pic:cNvPr id="0" name="logo.png"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="rIdImage"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="914400" cy="914400"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline>`;
  main = main.replace('<wp:inline><wp:docPr id="42" name="Logo"/></wp:inline>', drawing);
  main = main.replace('<w:tbl>', '<w:tbl><w:tblPr/><w:tblGrid><w:gridCol w:w="3000"/></w:tblGrid>');
  const finalSection = /<w:sectPr>.*?<\/w:sectPr>/.exec(main)[0];
  const landscape = finalSection.replace('<w:pgSz w:w="11906" w:h="16838"/>', '<w:type w:val="nextPage"/><w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/>');
  main = main.replace(finalSection, `<w:p><w:pPr>${finalSection}</w:pPr></w:p><w:p><w:r><w:t>SECOND PAGE: «CONTACT_FIRST»</w:t></w:r></w:p>${landscape}`);
  parts['word/document.xml'] = strToU8(main);
  return zipSync(parts);
}
