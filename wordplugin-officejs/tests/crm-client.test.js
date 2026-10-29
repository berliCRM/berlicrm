import test from "node:test";
import assert from "node:assert/strict";
import { createHash } from "node:crypto";
import {
  BerliCrmClient,
  MERGE_MODULES,
  md5,
  mergeFieldName,
  mergeFieldOoxml,
  normalizeCrmBaseUrl,
} from "../src/crm-client.js";
import { createZip } from "../src/zip.js";

test("MD5 matches standard and UTF-8 vectors", () => {
  for (const value of ["", "abc", "message digest", "äöü"]) {
    assert.equal(md5(value), createHash("md5").update(value, "utf8").digest("hex"));
  }
});

test("CRM URL requires HTTPS and same origin", () => {
  assert.equal(
    normalizeCrmBaseUrl("https://crm.example.test/berliCRM/", "https://crm.example.test"),
    "https://crm.example.test/berliCRM",
  );
  assert.throws(
    () => normalizeCrmBaseUrl("http://crm.example.test", "http://crm.example.test"),
    /HTTPS/,
  );
  assert.throws(
    () => normalizeCrmBaseUrl("https://other.example.test", "https://crm.example.test"),
    /derselben CRM-Adresse/,
  );
});

test("legacy merge field prefixes are preserved", () => {
  assert.equal(
    mergeFieldName(MERGE_MODULES[0], { name: "firstname", label: "First Name" }),
    "CONTACT_FIRSTNAME",
  );
  assert.equal(
    mergeFieldName(MERGE_MODULES[3], { name: "ticket_no", label: "Ticket No" }),
    "TICKET_TICKETNO",
  );
  assert.equal(mergeFieldName(MERGE_MODULES[4], { name: "phone_work" }), "USER_OFFICEPHONE");
  assert.throws(() => mergeFieldName(MERGE_MODULES[0], { name: "" }), /Ungültiger/);
});

test("Word OOXML contains actual merge fields and rejects unsafe names", () => {
  const xml = mergeFieldOoxml(["CONTACT_FIRMA&CO"]);
  assert.match(xml, /<w:fldChar w:fldCharType="begin"\/>/);
  assert.match(xml, /<w:instrText xml:space="preserve"> MERGEFIELD CONTACT_FIRMA&amp;CO <\/w:instrText>/);
  assert.match(xml, /<w:fldChar w:fldCharType="separate"\/>/);
  assert.match(xml, /«CONTACT_FIRMA&amp;CO»/);
  assert.match(xml, /<w:fldChar w:fldCharType="end"\/>/);
  assert.match(xml, /pkg:name="\/word\/document\.xml"/);
  assert.match(xml, /<w:document/);
  assert.match(xml, /<w:body>/);
  assert.throws(() => mergeFieldOoxml(["INVALID NAME"]), /Ungültiger/);
});

test("REST login and module describe use berliCRM challenge flow", async () => {
  const requests = [];
  const fetchImpl = async (url, options) => {
    requests.push({ url: new URL(url), options });
    const operation = options.method === "GET"
      ? new URL(url).searchParams.get("operation")
      : new URLSearchParams(options.body).get("operation");
    const responses = {
      getchallenge: { token: "challenge", serverTime: 123, expireTime: 456 },
      login: { sessionName: "session-1" },
      listtypes: { types: ["Contacts", "HelpDesk"] },
      describe: {
        fields: [{ name: "firstname", label: "Vorname" }, { label: "Ohne API-Namen" }],
      },
    };
    return {
      ok: true,
      status: 200,
      json: async () => ({ success: true, result: responses[operation] }),
    };
  };

  const client = new BerliCrmClient("https://crm.example.test/berliCRM", fetchImpl);
  const modules = await client.connect("user1", "access-key");

  assert.deepEqual(modules.map((module) => module.apiName), ["Contacts", "HelpDesk"]);
  assert.deepEqual(modules[0].fields, [{ name: "firstname", label: "Vorname" }]);
  assert.equal(mergeFieldName(modules[0], modules[0].fields[0]), "CONTACT_VORNAME");
  assert.equal(requests[0].url.searchParams.get("operation"), "getchallenge");
  assert.equal(requests[0].url.searchParams.get("format"), "json");
  assert.equal(requests[0].url.searchParams.get("sessionName"), "");
  assert.equal(requests[1].options.method, "POST");
  assert.equal(new URLSearchParams(requests[1].options.body).get("format"), "json");
  assert.equal(new URLSearchParams(requests[1].options.body).get("sessionName"), "");
  assert.equal(
    new URLSearchParams(requests[1].options.body).get("accessKey"),
    createHash("md5").update("challengeaccess-key").digest("hex"),
  );
  assert.equal(requests[2].url.searchParams.get("sessionName"), "session-1");
  assert.equal(requests[2].url.searchParams.get("format"), "json");
  assert.equal(requests[3].url.searchParams.get("elementType"), "Contacts");
});

test("Users are limited to the exact legacy merge-field set", async () => {
  const fetchImpl = async (url) => {
    const operation = new URL(url).searchParams.get("operation");
    const result = operation === "listtypes"
      ? { types: ["Users"] }
      : {
          fields: [
            { name: "first_name", label: "First Name" },
            { name: "address_street", label: "Street" },
            { name: "accesskey", label: "Access Key" },
          ],
        };
    return {
      ok: true,
      status: 200,
      json: async () => ({ success: true, result }),
    };
  };
  const client = new BerliCrmClient("https://crm.example.test/berliCRM", fetchImpl);
  client.sessionName = "session-1";
  const [users] = await client.getMergeModules();

  assert.deepEqual(users.fields.map((field) => field.name), ["first_name", "address_street"]);
  assert.equal(
    mergeFieldName(users, users.fields.find((field) => field.name === "address_street")),
    "USER_PRIMARYADDRESS",
  );
});

test("mail merge requests send module, view, paging and selected ids in POST body", async () => {
  let request;
  const client = new BerliCrmClient("https://crm.example.test/berliCRM", async (url, options) => {
    request = { url: new URL(url), options };
    return {
      ok: true,
      status: 200,
      json: async () => ({ success: true, result: { records: [], nextPage: false } }),
    };
  });
  client.sessionName = "session-1";

  await client.getMailMergeData("Contacts", "12", 2, ["1001", "1002"], ["firstname", "lastname"]);

  const body = new URLSearchParams(request.options.body);
  assert.equal(request.options.method, "POST");
  assert.equal(request.url.search, "");
  assert.equal(body.get("operation"), "getWordMailMergeData");
  assert.equal(body.get("moduleName"), "Contacts");
  assert.equal(body.get("listId"), "12");
  assert.equal(body.get("page"), "2");
  assert.equal(body.get("recordIds"), "1001,1002");
  assert.equal(body.get("fieldNames"), "firstname,lastname");
  assert.equal(body.get("sessionName"), "session-1");
  assert.equal(body.get("format"), "json");
});

test("mail merge document requests send the target record, title and encoded content", async () => {
  let request;
  const client = new BerliCrmClient("https://crm.example.test/berliCRM", async (url, options) => {
    request = { url: new URL(url), options };
    return {
      ok: true,
      status: 200,
      json: async () => ({ success: true, result: { documentId: "8x1234" } }),
    };
  });
  client.sessionName = "session-1";

  const result = await client.saveMailMergeDocument(
    "Contacts",
    "12",
    "1001",
    "Serienbrief 27.10.2026: Vorname, Nachname",
    "PGh0bWw+PC9odG1sPg==",
  );
  const body = new URLSearchParams(request.options.body);

  assert.deepEqual(result, { documentId: "8x1234" });
  assert.equal(body.get("operation"), "saveWordMailMergeDocument");
  assert.equal(body.get("moduleName"), "Contacts");
  assert.equal(body.get("listId"), "12");
  assert.equal(body.get("recordId"), "1001");
  assert.equal(body.get("notesTitle"), "Serienbrief 27.10.2026: Vorname, Nachname");
  assert.equal(body.get("fileContent"), "PGh0bWw+PC9odG1sPg==");
  assert.equal(body.get("sessionName"), "session-1");
});

test("ZIP output contains separate Word files and safe UTF-8 filenames", async () => {
  const zip = createZip([
    { name: "Brief-Müller.doc", content: "<html>eins</html>" },
    { name: "Brief-Zwei.doc", content: "<html>zwei</html>" },
  ]);
  const bytes = Buffer.from(await zip.arrayBuffer());
  const contents = bytes.toString("utf8");

  assert.equal(zip.type, "application/zip");
  assert.equal(bytes.readUInt32LE(0), 0x04034b50);
  assert.equal(bytes.readUInt32LE(bytes.length - 22), 0x06054b50);
  assert.match(contents, /Brief-Müller\.doc/);
  assert.match(contents, /Brief-Zwei\.doc/);
  assert.throws(() => createZip([]), /mindestens eine Datei/);
  assert.throws(
    () => createZip(Array.from({ length: 1001 }, (_, index) => ({ name: `Brief-${index}.doc`, content: "x" }))),
    /höchstens 1\.000 Briefe/,
  );
  assert.throws(() => createZip([{ name: "../Brief.doc", content: "x" }]), /Ungültiger Dateiname/);
});
