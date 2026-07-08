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
  notes: string | null;
  source: string;
};

const requiredHeaders = [
  "firstName",
  "lastName",
  "fullName",
  "email",
  "phone",
  "country",
  "street",
  "postalCode",
  "city",
  "fullAddress",
  "notes",
  "source",
];

function cleanText(value: string | undefined | null) {
  if (!value) {
    return "";
  }

  return value.trim();
}

function cleanNullableText(value: string | undefined | null) {
  const cleanedValue = cleanText(value);

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

  return value.replace(/[^\d+]/g, "");
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

function parseCsv(content: string) {
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

    if (character === ";") {
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
  const parsedRows = parseCsv(content);

  if (parsedRows.length === 0) {
    return {
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
    headers,
    rows,
  };
}

function hasRequiredHeaders(headers: string[]) {
  return requiredHeaders.every((requiredHeader) =>
    headers.includes(requiredHeader)
  );
}

function createImportedGuest(row: CsvRow): ImportedGuest | null {
  const fullName = cleanText(row.fullName);
  const splitName = splitFullName(fullName);

  const firstName = cleanText(row.firstName) || splitName.firstName;
  const lastName = cleanText(row.lastName) || splitName.lastName;

  const email = cleanText(row.email);
  const phone = cleanNullableText(row.phone);

  if (!firstName && !lastName && !email && !phone) {
    return null;
  }

  return {
    firstName: firstName || fullName || "Gość",
    lastName,
    fullName,
    email,
    phone,
    country: cleanNullableText(row.country),
    street: cleanNullableText(row.street),
    postalCode: cleanNullableText(row.postalCode),
    city: cleanNullableText(row.city),
    fullAddress: cleanNullableText(row.fullAddress),
    notes: cleanNullableText(row.notes),
    source: cleanText(row.source) || "CSV_IMPORT",
  };
}

function findExistingGuest(
  importedGuest: ImportedGuest,
  existingGuests: ExistingGuest[]
) {
  const importedEmail = normalizeEmail(importedGuest.email);
  const importedPhone = normalizePhone(importedGuest.phone);

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

  if (!hasRequiredHeaders(headers)) {
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
          firstName:
            existingGuest.firstName || importedGuest.firstName || "Gość",
          lastName: existingGuest.lastName || importedGuest.lastName,
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