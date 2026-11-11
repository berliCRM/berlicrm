import { t } from "./i18n.js?v=20261105.1";
const CRC_TABLE = Uint32Array.from({ length: 256 }, (_, value) => {
  let crc = value;
  for (let bit = 0; bit < 8; bit += 1) {
    crc = (crc & 1) ? (0xedb88320 ^ (crc >>> 1)) : (crc >>> 1);
  }
  return crc >>> 0;
});

function crc32(bytes) {
  let crc = 0xffffffff;
  for (const byte of bytes) {
    crc = CRC_TABLE[(crc ^ byte) & 0xff] ^ (crc >>> 8);
  }
  return (crc ^ 0xffffffff) >>> 0;
}

function write16(view, offset, value) {
  view.setUint16(offset, value, true);
}

function write32(view, offset, value) {
  view.setUint32(offset, value, true);
}

function dosDateTime(date) {
  const year = Math.max(1980, date.getFullYear());
  const time = (date.getHours() << 11) | (date.getMinutes() << 5) | Math.floor(date.getSeconds() / 2);
  const day = ((year - 1980) << 9) | ((date.getMonth() + 1) << 5) | date.getDate();
  return { time, day };
}

function concatBytes(parts) {
  const length = parts.reduce((total, part) => total + part.length, 0);
  const result = new Uint8Array(length);
  let offset = 0;
  for (const part of parts) {
    result.set(part, offset);
    offset += part.length;
  }
  return result;
}

export function createZip(files) {
  if (!Array.isArray(files) || files.length === 0) {
    throw new Error(t("AT_LEAST_ONE_FILE_IS_REQUIRED_TO_CREATE_A_ZIP_FILE"));
  }
  if (files.length > 1000) {
    throw new Error(t("A_ZIP_FILE_MAY_CONTAIN_AT_MOST_1_000_LETTERS"));
  }

  const encoder = new TextEncoder();
  const localParts = [];
  const centralParts = [];
  const timestamp = dosDateTime(new Date());
  let localOffset = 0;

  for (const file of files) {
    if (typeof file?.name !== "string" || !file.name || file.name.includes("/") || file.name.includes("\\")) {
      throw new Error(t("INVALID_FILENAME_FOR_THE_ZIP_FILE"));
    }

    const nameBytes = encoder.encode(file.name);
    const contentBytes = typeof file.content === "string"
      ? encoder.encode(file.content)
      : new Uint8Array(file.content);
    if (nameBytes.length > 0xffff || contentBytes.length > 0xffffffff) {
      throw new Error(t("A_FILE_IS_TOO_LARGE_FOR_THE_ZIP_FORMAT"));
    }
    const checksum = crc32(contentBytes);
    const localHeader = new Uint8Array(30 + nameBytes.length);
    const localView = new DataView(localHeader.buffer);
    write32(localView, 0, 0x04034b50);
    write16(localView, 4, 20);
    write16(localView, 6, 0x0800);
    write16(localView, 8, 0);
    write16(localView, 10, timestamp.time);
    write16(localView, 12, timestamp.day);
    write32(localView, 14, checksum);
    write32(localView, 18, contentBytes.length);
    write32(localView, 22, contentBytes.length);
    write16(localView, 26, nameBytes.length);
    write16(localView, 28, 0);
    localHeader.set(nameBytes, 30);
    localParts.push(localHeader, contentBytes);

    const centralHeader = new Uint8Array(46 + nameBytes.length);
    const centralView = new DataView(centralHeader.buffer);
    write32(centralView, 0, 0x02014b50);
    write16(centralView, 4, 20);
    write16(centralView, 6, 20);
    write16(centralView, 8, 0x0800);
    write16(centralView, 10, 0);
    write16(centralView, 12, timestamp.time);
    write16(centralView, 14, timestamp.day);
    write32(centralView, 16, checksum);
    write32(centralView, 20, contentBytes.length);
    write32(centralView, 24, contentBytes.length);
    write16(centralView, 28, nameBytes.length);
    write16(centralView, 30, 0);
    write16(centralView, 32, 0);
    write16(centralView, 34, 0);
    write16(centralView, 36, 0);
    write32(centralView, 38, 0);
    write32(centralView, 42, localOffset);
    centralHeader.set(nameBytes, 46);
    centralParts.push(centralHeader);
    localOffset += localHeader.length + contentBytes.length;
  }

  const centralDirectory = concatBytes(centralParts);
  const endRecord = new Uint8Array(22);
  const endView = new DataView(endRecord.buffer);
  write32(endView, 0, 0x06054b50);
  write16(endView, 4, 0);
  write16(endView, 6, 0);
  write16(endView, 8, files.length);
  write16(endView, 10, files.length);
  write32(endView, 12, centralDirectory.length);
  write32(endView, 16, localOffset);
  write16(endView, 20, 0);

  return new Blob([...localParts, centralDirectory, endRecord], { type: "application/zip" });
}
