"use server";

import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";

type CsvRow = Record<string, string>;

type ImportedGuest = {
  firstName: string;
  lastName: string;
  fullName: string;
  email: string;
  phone: string | null;
  country: string | null;
  street: string | null;
  postalCode: string | null;
  city: string | null;
  fullAddress: string | null;
  pesel: string | null;
  documentNumber: string | null;
  nationality: string | null;
  birthDate: Date | null;
  isVip: boolean;
  externalGuestId: string | null;
  notes: string | null;
  source: string;
};

type ExistingGuest = {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string | null;
  country: string | null;
  street: string | null;
  postalCode: string | null;
  city: string | null;
  fullAddress: string | null;
  pesel: string | null;
  documentNumber: string | null;
  nationality: string | null;
  birthDate: Date | null;
  isVip: boolean;
  externalGuestId: string | null;
  notes: string | null;
  source: string;
};

function cleanText(value: string | undefined | null) {
  if (!value) {
    return "";
  }

  return value.replace(/^\uFEFF/, "").trim();
}

function cleanNullableText(value: string | undefined | null) {
  const cleanedValue = cleanText(value);

  return cleanedValue ? cleanedValue : null;
}

function cleanExcelText(value: string | undefined | null) {
  const cleanedValue = cleanText(value);

  if (cleanedValue.startsWith("'")) {
    return cleanedValue.slice(1).trim();
  }

  return cleanedValue;
}

function cleanNullableExcelText(value: string | undefined | null) {
  const cleanedValue = cleanExcelText(value);

  return cleanedValue ? cleanedValue : null;
}

function normalizeEmail(value: string | null) {
  if (!value) {
    return "";
  }

  return value.trim().toLowerCase();
}

function normalizePhone(value: string | null) {
  if (!value) {
    return "";
  }

  const withoutExcelApostrophe = value.trim().replace(/^'+/, "");
  const onlyPhoneCharacters = withoutExcelApostrophe.replace(/[^\d+]/g, "");

  if (!onlyPhoneCharacters) {
    return "";
  }

  if (onlyPhoneCharacters.startsWith("+")) {
    return `+${onlyPhoneCharacters.slice(1).replace(/\+/g, "")}`;
  }

  return onlyPhoneCharacters.replace(/\+/g, "");
}

function normalizeNullablePhone(value: string | null) {
  const normalizedPhone = normalizePhone(value);

  return normalizedPhone ? normalizedPhone : null;
}

function normalizePesel(value: string | null) {
  if (!value) {
    return null;
  }

  const normalizedPesel = value.replace(/\D/g, "");

  return normalizedPesel || null;
}

function splitFullName(fullName: string) {
  const parts = fullName.trim().split(/\s+/).filter(Boolean);

  if (parts.length === 0) {
    return {
      firstName: "",
      lastName: "",
    };
  }

  if (parts.length === 1) {
    return {
      firstName: parts[0],
      lastName: "",
    };
  }

  return {
    firstName: parts[0],
    lastName: parts.slice(1).join(" "),
  };
}

function countDelimiterOutsideQuotes(line: string, delimiter: "," | ";") {
  let count = 0;
  let insideQuotes = false;

  for (let index = 0; index < line.length; index += 1) {
    const character = line[index];
    const nextCharacter = line[index + 1];

    if (insideQuotes) {
      if (character === '"' && nextCharacter === '"') {
        index += 1;
      } else if (character === '"') {
        insideQuotes = false;
      }

      continue;
    }

    if (character === '"') {
      insideQuotes = true;
      continue;
    }

    if (character === delimiter) {
      count += 1;
    }
  }

  return count;
}

function detectDelimiter(content: string): "," | ";" {
  const firstLine = content.replace(/^\uFEFF/, "").split(/\r?\n/)[0] ?? "";

  const commaCount = countDelimiterOutsideQuotes(firstLine, ",");
  const semicolonCount = countDelimiterOutsideQuotes(firstLine, ";");

  return commaCount > semicolonCount ? "," : ";";
}

function parseCsv(content: string, delimiter: "," | ";") {
  const normalizedContent = content.replace(/^\uFEFF/, "");
  const rows: string[][] = [];

  let currentRow: string[] = [];
  let currentValue = "";
  let insideQuotes = false;

  for (let index = 0; index < normalizedContent.length; index += 1) {
    const character = normalizedContent[index];
    const nextCharacter = normalizedContent[index + 1];

    if (insideQuotes) {
      if (character === '"' && nextCharacter === '"') {
        currentValue += '"';
        index += 1;
      } else if (character === '"') {
        insideQuotes = false;
      } else {
        currentValue += character;
      }

      continue;
    }

    if (character === '"') {
      insideQuotes = true;
      continue;
    }

    if (character === delimiter) {
      currentRow.push(currentValue);
      currentValue = "";
      continue;
    }

    if (character === "\n") {
      currentRow.push(currentValue);
      rows.push(currentRow);

      currentRow = [];
      currentValue = "";
      continue;
    }

    if (character === "\r") {
      continue;
    }

    currentValue += character;
  }

  currentRow.push(currentValue);

  if (currentRow.some((value) => value.trim() !== "")) {
    rows.push(currentRow);
  }

  return rows;
}

function createRowsFromCsv(content: string) {
  const delimiter = detectDelimiter(content);
  const parsedRows = parseCsv(content, delimiter);

  if (parsedRows.length === 0) {
    return {
      delimiter,
      headers: [] as string[],
      rows: [] as CsvRow[],
    };
  }

  const headers = parsedRows[0].map((header) => cleanText(header));

  const rows = parsedRows.slice(1).map((row) => {
    const result: CsvRow = {};

    headers.forEach((header, index) => {
      result[header] = cleanText(row[index]);
    });

    return result;
  });

  return {
    delimiter,
    headers,
    rows,
  };
}

function hasSupportedHeaders(headers: string[]) {
  const headerSet = new Set(headers);

  const hasProgramExportHeaders =
    headerSet.has("first_name") &&
    headerSet.has("last_name") &&
    headerSet.has("email") &&
    headerSet.has("phone");

  const hasPmsExportHeaders =
    (headerSet.has("firstName") || headerSet.has("fullName")) &&
    (headerSet.has("lastName") || headerSet.has("fullName")) &&
    (headerSet.has("email") || headerSet.has("phone"));

  return hasProgramExportHeaders || hasPmsExportHeaders;
}

function getRowValue(row: CsvRow, keys: string[]) {
  for (const key of keys) {
    const value = row[key];

    if (typeof value === "string" && value.trim() !== "") {
      return value;
    }
  }

  return "";
}

function parseBoolean(value: string | undefined | null) {
  const cleanedValue = cleanText(value).toLowerCase();

  return (
    cleanedValue === "true" ||
    cleanedValue === "1" ||
    cleanedValue === "tak" ||
    cleanedValue === "yes" ||
    cleanedValue === "vip"
  );
}

function createDateFromParts(year: number, month: number, day: number) {
  const date = new Date(Date.UTC(year, month - 1, day));

  if (
    date.getUTCFullYear() !== year ||
    date.getUTCMonth() !== month - 1 ||
    date.getUTCDate() !== day
  ) {
    return null;
  }

  return date;
}

function parseBirthDate(value: string | undefined | null) {
  const cleanedValue = cleanText(value);

  if (!cleanedValue) {
    return null;
  }

  const isoMatch = cleanedValue.match(/^(\d{4})-(\d{2})-(\d{2})/);

  if (isoMatch) {
    const year = Number(isoMatch[1]);
    const month = Number(isoMatch[2]);
    const day = Number(isoMatch[3]);

    const date = createDateFromParts(year, month, day);

    if (!date) {
      return null;
    }

    return isDateInFuture(date) ? null : date;
  }

  const polishMatch = cleanedValue.match(/^(\d{2})\.(\d{2})\.(\d{4})$/);

  if (polishMatch) {
    const day = Number(polishMatch[1]);
    const month = Number(polishMatch[2]);
    const year = Number(polishMatch[3]);

    const date = createDateFromParts(year, month, day);

    if (!date) {
      return null;
    }

    return isDateInFuture(date) ? null : date;
  }

  const slashMatch = cleanedValue.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);

  if (slashMatch) {
    const day = Number(slashMatch[1]);
    const month = Number(slashMatch[2]);
    const year = Number(slashMatch[3]);

    const date = createDateFromParts(year, month, day);

    if (!date) {
      return null;
    }

    return isDateInFuture(date) ? null : date;
  }

  return null;
}

function isDateInFuture(date: Date) {
  const now = new Date();
  const today = new Date(
    Date.UTC(now.getFullYear(), now.getMonth(), now.getDate())
  );

  return date.getTime() > today.getTime();
}

function getImportedSource(row: CsvRow) {
  const source = cleanText(row.source).toUpperCase();

  if (
    source === "MANUAL" ||
    source === "BASE44" ||
    source === "CSV_IMPORT" ||
    source === "RESERVATION_SYNC"
  ) {
    return source;
  }

  return "CSV_IMPORT";
}

function createImportedGuest(row: CsvRow): ImportedGuest | null {
  const fullName = cleanText(getRowValue(row, ["fullName", "full_name"]));
  const splitName = splitFullName(fullName);

  const firstName =
    cleanText(getRowValue(row, ["firstName", "first_name"])) ||
    splitName.firstName;

  const lastName =
    cleanText(getRowValue(row, ["lastName", "last_name"])) ||
    splitName.lastName;

  const email = normalizeEmail(cleanText(getRowValue(row, ["email"])));

  const phone = normalizeNullablePhone(
    cleanNullableExcelText(getRowValue(row, ["phone", "phoneNumber"]))
  );

  const fullAddress = cleanNullableText(
    getRowValue(row, ["fullAddress", "full_address", "address"])
  );

  const pesel = normalizePesel(cleanNullableText(getRowValue(row, ["pesel"])));

  const documentNumber = cleanNullableExcelText(
    getRowValue(row, ["documentNumber", "document_number", "id_document"])
  );

  const externalGuestId = cleanNullableText(
    getRowValue(row, ["externalGuestId", "external_guest_id", "id"])
  );

  if (
    !firstName &&
    !lastName &&
    !email &&
    !phone &&
    !pesel &&
    !externalGuestId
  ) {
    return null;
  }

  return {
    firstName: firstName || fullName || "Gość",
    lastName,
    fullName,
    email,
    phone,
    country: cleanNullableText(getRowValue(row, ["country"])),
    street: cleanNullableText(getRowValue(row, ["street"])),
    postalCode: cleanNullableText(getRowValue(row, ["postalCode", "postal_code"])),
    city: cleanNullableText(getRowValue(row, ["city"])),
    fullAddress,
    pesel,
    documentNumber,
    nationality: cleanNullableText(getRowValue(row, ["nationality"])),
    birthDate: parseBirthDate(getRowValue(row, ["birthDate", "birth_date", "date_of_birth"])),
    isVip: parseBoolean(getRowValue(row, ["isVip", "is_vip", "vip_status"])),
    externalGuestId,
    notes: cleanNullableText(getRowValue(row, ["notes"])),
    source: getImportedSource(row),
  };
}

function findExistingGuest(
  importedGuest: ImportedGuest,
  existingGuests: ExistingGuest[]
) {
  const importedExternalGuestId = cleanText(importedGuest.externalGuestId);
  const importedEmail = normalizeEmail(importedGuest.email);
  const importedPhone = normalizePhone(importedGuest.phone);
  const importedPesel = normalizePesel(importedGuest.pesel);

  if (importedExternalGuestId) {
    const foundByExternalGuestId = existingGuests.find(
      (guest) => cleanText(guest.externalGuestId) === importedExternalGuestId
    );

    if (foundByExternalGuestId) {
      return foundByExternalGuestId;
    }
  }

  if (importedEmail) {
    const foundByEmail = existingGuests.find(
      (guest) => normalizeEmail(guest.email) === importedEmail
    );

    if (foundByEmail) {
      return foundByEmail;
    }
  }

  if (importedPhone) {
    const foundByPhone = existingGuests.find(
      (guest) => normalizePhone(guest.phone) === importedPhone
    );

    if (foundByPhone) {
      return foundByPhone;
    }
  }

  if (importedPesel) {
    const foundByPesel = existingGuests.find(
      (guest) => normalizePesel(guest.pesel) === importedPesel
    );

    if (foundByPesel) {
      return foundByPesel;
    }
  }

  return null;
}

function keepExistingOrUseImported(
  existingValue: string | null,
  importedValue: string | null
) {
  if (existingValue && existingValue.trim()) {
    return existingValue;
  }

  return importedValue;
}

function keepExistingName(existingValue: string, importedValue: string) {
  if (existingValue && existingValue.trim() && existingValue !== "Gość") {
    return existingValue;
  }

  if (importedValue && importedValue.trim()) {
    return importedValue;
  }

  return existingValue || "Gość";
}

function mergeImportedNotes(existingNotes: string | null, importedNotes: string | null) {
  if (!existingNotes) {
    return importedNotes;
  }

  if (!importedNotes) {
    return existingNotes;
  }

  if (existingNotes.includes(importedNotes)) {
    return existingNotes;
  }

  return `${existingNotes}\n\n${importedNotes}`;
}

function buildRedirectUrl({
  result,
  rows,
  created,
  updated,
  skipped,
  error,
}: {
  result?: string;
  rows?: number;
  created?: number;
  updated?: number;
  skipped?: number;
  error?: string;
}) {
  const params = new URLSearchParams();

  if (result) {
    params.set("result", result);
  }

  if (typeof rows === "number") {
    params.set("rows", String(rows));
  }

  if (typeof created === "number") {
    params.set("created", String(created));
  }

  if (typeof updated === "number") {
    params.set("updated", String(updated));
  }

  if (typeof skipped === "number") {
    params.set("skipped", String(skipped));
  }

  if (error) {
    params.set("error", error);
  }

  const queryString = params.toString();

  return queryString
    ? `/admin/goscie/import?${queryString}`
    : "/admin/goscie/import";
}

export async function importGuestsFromCsv(formData: FormData) {
  const file = formData.get("file");

  if (!(file instanceof File)) {
    redirect(buildRedirectUrl({ error: "no-file" }));
  }

  if (!file.name.toLowerCase().endsWith(".csv")) {
    redirect(buildRedirectUrl({ error: "wrong-file-type" }));
  }

  const content = Buffer.from(await file.arrayBuffer()).toString("utf-8");
  const { headers, rows } = createRowsFromCsv(content);

  if (!hasSupportedHeaders(headers)) {
    redirect(buildRedirectUrl({ error: "wrong-headers" }));
  }

  const existingGuests: ExistingGuest[] = await prisma.guest.findMany({
    select: {
      id: true,
      firstName: true,
      lastName: true,
      email: true,
      phone: true,
      country: true,
      street: true,
      postalCode: true,
      city: true,
      fullAddress: true,
      pesel: true,
      documentNumber: true,
      nationality: true,
      birthDate: true,
      isVip: true,
      externalGuestId: true,
      notes: true,
      source: true,
    },
  });

  let created = 0;
  let updated = 0;
  let skipped = 0;

  for (const row of rows) {
    const importedGuest = createImportedGuest(row);

    if (!importedGuest) {
      skipped += 1;
      continue;
    }

    const existingGuest = findExistingGuest(importedGuest, existingGuests);

    if (existingGuest) {
      const updatedGuest = await prisma.guest.update({
        where: {
          id: existingGuest.id,
        },
        data: {
          firstName: keepExistingName(
            existingGuest.firstName,
            importedGuest.firstName
          ),
          lastName: keepExistingOrUseImported(
            existingGuest.lastName,
            importedGuest.lastName
          ) || "",
          email: existingGuest.email || importedGuest.email,
          phone: keepExistingOrUseImported(
            existingGuest.phone,
            importedGuest.phone
          ),
          country: keepExistingOrUseImported(
            existingGuest.country,
            importedGuest.country
          ),
          street: keepExistingOrUseImported(
            existingGuest.street,
            importedGuest.street
          ),
          postalCode: keepExistingOrUseImported(
            existingGuest.postalCode,
            importedGuest.postalCode
          ),
          city: keepExistingOrUseImported(
            existingGuest.city,
            importedGuest.city
          ),
          fullAddress: keepExistingOrUseImported(
            existingGuest.fullAddress,
            importedGuest.fullAddress
          ),
          pesel: keepExistingOrUseImported(
            existingGuest.pesel,
            importedGuest.pesel
          ),
          documentNumber: keepExistingOrUseImported(
            existingGuest.documentNumber,
            importedGuest.documentNumber
          ),
          nationality: keepExistingOrUseImported(
            existingGuest.nationality,
            importedGuest.nationality
          ),
          birthDate: existingGuest.birthDate ?? importedGuest.birthDate,
          isVip: existingGuest.isVip || importedGuest.isVip,
          externalGuestId: keepExistingOrUseImported(
            existingGuest.externalGuestId,
            importedGuest.externalGuestId
          ),
          notes: mergeImportedNotes(existingGuest.notes, importedGuest.notes),
          source: existingGuest.source || importedGuest.source,
        },
        select: {
          id: true,
          firstName: true,
          lastName: true,
          email: true,
          phone: true,
          country: true,
          street: true,
          postalCode: true,
          city: true,
          fullAddress: true,
          pesel: true,
          documentNumber: true,
          nationality: true,
          birthDate: true,
          isVip: true,
          externalGuestId: true,
          notes: true,
          source: true,
        },
      });

      const existingGuestIndex = existingGuests.findIndex(
        (guest) => guest.id === existingGuest.id
      );

      if (existingGuestIndex >= 0) {
        existingGuests[existingGuestIndex] = updatedGuest;
      }

      updated += 1;
      continue;
    }

    const createdGuest = await prisma.guest.create({
      data: {
        firstName: importedGuest.firstName,
        lastName: importedGuest.lastName,
        email: importedGuest.email,
        phone: importedGuest.phone,
        country: importedGuest.country,
        street: importedGuest.street,
        postalCode: importedGuest.postalCode,
        city: importedGuest.city,
        fullAddress: importedGuest.fullAddress,
        pesel: importedGuest.pesel,
        documentNumber: importedGuest.documentNumber,
        nationality: importedGuest.nationality,
        birthDate: importedGuest.birthDate,
        isVip: importedGuest.isVip,
        externalGuestId: importedGuest.externalGuestId,
        notes: importedGuest.notes,
        source: importedGuest.source,
      },
      select: {
        id: true,
        firstName: true,
        lastName: true,
        email: true,
        phone: true,
        country: true,
        street: true,
        postalCode: true,
        city: true,
        fullAddress: true,
        pesel: true,
        documentNumber: true,
        nationality: true,
        birthDate: true,
        isVip: true,
        externalGuestId: true,
        notes: true,
        source: true,
      },
    });

    existingGuests.push(createdGuest);
    created += 1;
  }

  redirect(
    buildRedirectUrl({
      result: "ok",
      rows: rows.length,
      created,
      updated,
      skipped,
    })
  );
}