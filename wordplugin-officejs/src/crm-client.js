import { t, i18n } from "./i18n.js?v=20261105.1";
export const MERGE_MODULES = [
  { apiName: "Contacts", labelKey: "CONTACT_FIELDS", get label() { return t(this.labelKey); }, prefix: "CONTACT" },
  { apiName: "Accounts", labelKey: "ORGANIZATION_FIELDS", get label() { return t(this.labelKey); }, prefix: "ACCOUNT" },
  { apiName: "Leads", labelKey: "LEAD_FIELDS", get label() { return t(this.labelKey); }, prefix: "LEAD" },
  { apiName: "HelpDesk", labelKey: "TICKET_FIELDS", get label() { return t(this.labelKey); }, prefix: "TICKET" },
  {
    apiName: "Users",
    labelKey: "USER_FIELDS", get label() { return t(this.labelKey); },
    prefix: "USER",
    legacyNames: {
      first_name: "FIRSTNAME",
      last_name: "LASTNAME",
      user_name: "USERNAME",
      secondaryemail: "SECONDARYEMAIL",
      title: "TITLE",
      phone_work: "OFFICEPHONE",
      department: "DEPARTMENT",
      phone_mobile: "MOBILE",
      phone_other: "OTHERPHONE",
      phone_fax: "FAX",
      email1: "EMAIL",
      phone_home: "HOMEPHONE",
      email2: "OTHEREMAIL",
      address_street: "PRIMARYADDRESS",
      address_city: "CITY",
      address_state: "STATE",
      address_postalcode: "POSTALCODE",
      address_country: "COUNTRY",
    },
  },
];

const MD5_SHIFTS = [
  7, 12, 17, 22, 7, 12, 17, 22, 7, 12, 17, 22, 7, 12, 17, 22,
  5, 9, 14, 20, 5, 9, 14, 20, 5, 9, 14, 20, 5, 9, 14, 20,
  4, 11, 16, 23, 4, 11, 16, 23, 4, 11, 16, 23, 4, 11, 16, 23,
  6, 10, 15, 21, 6, 10, 15, 21, 6, 10, 15, 21, 6, 10, 15, 21,
];

const MD5_CONSTANTS = Array.from({ length: 64 }, (_, index) =>
  Math.floor(Math.abs(Math.sin(index + 1)) * 0x100000000) >>> 0,
);

function rotateLeft(value, shift) {
  return (value << shift) | (value >>> (32 - shift));
}

export function md5(value) {
  const input = new TextEncoder().encode(value);
  const paddedLength = Math.ceil((input.length + 9) / 64) * 64;
  const bytes = new Uint8Array(paddedLength);
  bytes.set(input);
  bytes[input.length] = 0x80;

  const bitLength = input.length * 8;
  const data = new DataView(bytes.buffer);
  data.setUint32(paddedLength - 8, bitLength >>> 0, true);
  data.setUint32(paddedLength - 4, Math.floor(bitLength / 0x100000000), true);

  let a0 = 0x67452301;
  let b0 = 0xefcdab89;
  let c0 = 0x98badcfe;
  let d0 = 0x10325476;

  for (let offset = 0; offset < paddedLength; offset += 64) {
    const words = Array.from({ length: 16 }, (_, index) =>
      data.getUint32(offset + index * 4, true),
    );
    let a = a0;
    let b = b0;
    let c = c0;
    let d = d0;

    for (let index = 0; index < 64; index += 1) {
      let value;
      let wordIndex;

      if (index < 16) {
        value = (b & c) | (~b & d);
        wordIndex = index;
      } else if (index < 32) {
        value = (d & b) | (~d & c);
        wordIndex = (5 * index + 1) % 16;
      } else if (index < 48) {
        value = b ^ c ^ d;
        wordIndex = (3 * index + 5) % 16;
      } else {
        value = c ^ (b | ~d);
        wordIndex = (7 * index) % 16;
      }

      const previousD = d;
      d = c;
      c = b;
      const sum = (a + value + MD5_CONSTANTS[index] + words[wordIndex]) >>> 0;
      b = (b + rotateLeft(sum, MD5_SHIFTS[index])) >>> 0;
      a = previousD;
    }

    a0 = (a0 + a) >>> 0;
    b0 = (b0 + b) >>> 0;
    c0 = (c0 + c) >>> 0;
    d0 = (d0 + d) >>> 0;
  }

  return [a0, b0, c0, d0]
    .map((word) =>
      [0, 8, 16, 24]
        .map((shift) => ((word >>> shift) & 0xff).toString(16).padStart(2, "0"))
        .join(""),
    )
    .join("");
}

export function normalizeCrmBaseUrl(value, currentOrigin) {
  let url;
  try {
    url = new URL(value.trim());
  } catch {
    throw new Error(t("PLEASE_ENTER_A_VALID_BERLICRM_URL"));
  }

  if (url.protocol !== "https:" && !(url.protocol === "http:" && /^(localhost|127\.0\.0\.1)$/.test(url.hostname))) {
    throw new Error(t("THE_CRM_CONNECTION_MUST_USE_HTTPS"));
  }
  if (url.username || url.password) {
    throw new Error(t("THE_CRM_URL_MUST_NOT_CONTAIN_EMBEDDED_CREDENTIALS"));
  }
  if (url.origin !== currentOrigin) {
    throw new Error(t("THE_ADD_IN_MUST_BE_HOSTED_AT_THE_SAME_CRM_ADDRESS_AS_THE_REST_API"));
  }

  url.search = "";
  url.hash = "";
  url.pathname = url.pathname.replace(/\/+$/, "");
  return url.href.replace(/\/+$/, "");
}

export function mergeFieldName(module, field) {
  if (!module || typeof field?.name !== "string" || !field.name.trim()) {
    throw new Error(t("INVALID_CRM_FIELD_NAME"));
  }
  const suffix = module.legacyNames?.[field.name] ?? field.label ?? field.name;
  if (typeof suffix !== "string") {
    throw new Error(t("INVALID_FIELD_LABEL_RETURNED_BY_BERLICRM"));
  }
  const normalizedSuffix = suffix.replace(/\s+/g, "");
  if (!normalizedSuffix) {
    throw new Error(t("INVALID_CRM_FIELD_NAME"));
  }
  return `${module.prefix}_${normalizedSuffix}`.toUpperCase();
}

/** Recognizes existing template names across CRM interface languages. */
export function mergeFieldNames(module, field) {
  return [...new Set([mergeFieldName(module, field), ...(field.aliases || []).map(label =>
    mergeFieldName(module, { ...field, label }))])];
}

function escapeXml(value, attribute = false) {
  return value.replace(/[&<>"']/g, (character) => {
    if (!attribute && (character === "'" || character === '"')) {
      return character;
    }

    const entities = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&apos;",
    };
    return entities[character];
  });
}

export function mergeFieldOoxml(fieldNames) {
  if (!Array.isArray(fieldNames) || fieldNames.length === 0) {
    throw new Error(t("PLEASE_SELECT_AT_LEAST_ONE_FIELD"));
  }

  const fields = fieldNames
    .map((name) => {
      if (
        typeof name !== "string" ||
        !name ||
        /\s/.test(name) ||
        /[\u0000-\u0008\u000B\u000C\u000E-\u001F]/.test(name)
      ) {
        throw new Error(t("INVALID_WORD_MERGE_FIELD_NAME"));
      }
      const escapedName = escapeXml(name);
      const escapedInstruction = escapeXml(` MERGEFIELD ${name} `, true);
      return `<w:r><w:fldChar w:fldCharType="begin"/></w:r>
        <w:r><w:instrText xml:space="preserve">${escapedInstruction}</w:instrText></w:r>
        <w:r><w:fldChar w:fldCharType="separate"/></w:r>
        <w:r><w:t>«${escapedName}»</w:t></w:r>
        <w:r><w:fldChar w:fldCharType="end"/></w:r>`;
    })
    .join("");

  return `<pkg:package xmlns:pkg="http://schemas.microsoft.com/office/2006/xmlPackage">
    <pkg:part pkg:name="/_rels/.rels" pkg:contentType="application/vnd.openxmlformats-package.relationships+xml">
      <pkg:xmlData>
        <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
          <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
        </Relationships>
      </pkg:xmlData>
    </pkg:part>
    <pkg:part pkg:name="/word/document.xml" pkg:contentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml">
      <pkg:xmlData>
        <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
          <w:body><w:p>${fields}</w:p></w:body>
        </w:document>
      </pkg:xmlData>
    </pkg:part>
  </pkg:package>`;
}

export class BerliCrmClient {
  constructor(baseUrl, fetchImpl = globalThis.fetch) {
    this.baseUrl = baseUrl;
    this.fetchImpl = fetchImpl;
    this.sessionName = null;
  }

  async request(method, operation, params = {}, sessionName = this.sessionName) {
    const url = new URL("webservice.php", `${this.baseUrl}/`);
    const values = new URLSearchParams({ operation, ...params, format: "json" });
    values.set("sessionName", sessionName || "");

    const options = {
      method,
      cache: "no-store",
      credentials: "same-origin",
      headers: {},
    };

    if (method === "GET") {
      url.search = values.toString();
    } else {
      options.headers["Content-Type"] = "application/x-www-form-urlencoded;charset=UTF-8";
      options.body = values.toString();
    }

    const response = this.fetchImpl === globalThis.fetch
      ? await globalThis.fetch(url, options)
      : await this.fetchImpl.call(globalThis, url, options);
    let payload;
    try {
      payload = await response.json();
    } catch {
      throw new Error(t("BERLICRM_DID_NOT_RETURN_VALID_JSON_HTTP", [response.status]));
    }

    if (!response.ok || payload.success !== true) {
      const message = payload.error?.message || payload.error?.code;
      throw new Error(message || t("REST_REQUEST_FAILED_HTTP", [response.status]));
    }

    return payload.result;
  }

  async connect(username, accessKey) {
    if (!username.trim() || !accessKey) {
      throw new Error(t("PLEASE_ENTER_YOUR_CRM_USERNAME_AND_API_KEY"));
    }

    const challenge = await this.request("GET", "getchallenge", { username: username.trim() }, null);
    if (typeof challenge?.token !== "string" || !challenge.token) {
      throw new Error(t("BERLICRM_DID_NOT_RETURN_A_VALID_REST_CHALLENGE_TOKEN"));
    }

    const login = await this.request(
      "POST",
      "login",
      { username: username.trim(), accessKey: md5(challenge.token + accessKey) },
      null,
    );
    if (typeof login?.sessionName !== "string" || !login.sessionName) {
      throw new Error(t("BERLICRM_DID_NOT_RETURN_A_VALID_REST_SESSION"));
    }

    this.sessionName = login.sessionName;
    const preferences = await this.request("POST", "office_addin_user_preferences");
    if (!['de_de', 'en_us'].includes(preferences?.language)) throw new Error(t('PREFERENCES_INVALID'));
    i18n.setLanguage(preferences.language);
    return this.getMergeModules();
  }

  async getMergeModules() {
    if (!this.sessionName) {
      throw new Error(t("THERE_IS_NO_CRM_SESSION"));
    }

    const typeResult = await this.request("GET", "listtypes");
    if (!Array.isArray(typeResult?.types)) {
      throw new Error(t("BERLICRM_DID_NOT_RETURN_A_VALID_MODULE_LIST"));
    }
    const availableTypes = new Set(typeResult.types);

    const modules = [];
    for (const definition of MERGE_MODULES) {
      const module = { ...definition, label: t(definition.labelKey) };
      if (!availableTypes.has(module.apiName)) {
        continue;
      }

      const description = await this.request("GET", "describe", { elementType: module.apiName });
      if (!Array.isArray(description?.fields)) {
        throw new Error(t("BERLICRM_DID_NOT_RETURN_A_VALID_FIELD_LIST_FOR", [module.label]));
      }

      const describedFields = description.fields.filter(
        (field) => typeof field?.name === "string" && field.name.length > 0,
      );
      const fields = module.apiName === "Users"
        ? Object.keys(module.legacyNames)
            .map((name) => describedFields.find((field) => field.name === name))
            .filter(Boolean)
        : describedFields;

      modules.push({
        ...module,
        fields,
      });
    }

    return modules;
  }

  async getMailMergeData(moduleName, listId = "", page = 1, recordIds = [], fieldNames = []) {
    if (!this.sessionName) {
      throw new Error(t("THERE_IS_NO_CRM_SESSION"));
    }

    return this.request("POST", "getWordMailMergeData", {
      moduleName,
      listId,
      page: String(page),
      recordIds: recordIds.join(","),
      fieldNames: fieldNames.join(","),
    });
  }

  async saveMailMergeDocument(moduleName, listId, recordId, notesTitle, fileContent) {
    if (!this.sessionName) {
      throw new Error(t("THERE_IS_NO_CRM_SESSION"));
    }

    return this.request("POST", "saveWordMailMergeDocument", {
      moduleName,
      listId,
      recordId: String(recordId),
      notesTitle,
      fileContent,
    });
  }

  async disconnect() {
    const sessionName = this.sessionName;
    this.sessionName = null;
    if (!sessionName) {
      return;
    }

    await this.request("POST", "logout", {}, sessionName);
  }
}
