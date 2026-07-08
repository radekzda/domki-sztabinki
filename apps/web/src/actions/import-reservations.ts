"use server";

import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";

type CsvRow = Record<string, string>;

type ImportedReservation = {
  externalReservationId: string;
  externalRoomId: string;
  externalGuestId: string | null;
  cabinName: string;
  checkIn: Date;
  checkOut: Date;
  checkInAt: Date | null;
  checkOutAt: Date | null;
  nights: number;
  adults: number;
  children: number;
  guests: number;
  totalPrice: number;
  paidAmount: number;
  paymentStatus: string | null;
  source: string;
  status: string;
  specialRequests: string | null;
  orderedBy: string | null;
  externalCreatedAt: Date | null;
  externalUpdatedAt: Date | null;
  externalCreatedById: string | null;
  externalCreatedBy: string | null;
  externalIsSample: boolean;
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
  externalGuestId: string | null;
};

type ExistingCabin = {
  id: string;
  name: string;
  shortName: string | null;
};

const externalRoomIdToCabinName: Record<string, string> = {
  "68c3c31b11c927273a012c3c": "Domek 1",
  "68c3c31b11c927273a012c3d": "Domek 2",
  "68c3c31b11c927273a012c3f": "Domek 3",
  "68c3c31b11c927273a012c40": "Domek 4",
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

  return (
    headerSet.has("id") &&
    headerSet.has("room_id") &&
    headerSet.has("guest_id") &&
    headerSet.has("check_in") &&
    headerSet.has("check_out") &&
    headerSet.has("total_price") &&
    headerSet.has("paid_amount")
  );
}

function parseNumber(value: string | undefined | null) {
  const cleanedValue = cleanText(value).replace(",", ".");

  if (!cleanedValue) {
    return 0;
  }

  const parsedValue = Number(cleanedValue);

  if (!Number.isFinite(parsedValue)) {
    return 0;
  }

  return parsedValue;
}

function parseInteger(value: string | undefined | null) {
  return Math.max(0, Math.round(parseNumber(value)));
}

function parseBoolean(value: string | undefined | null) {
  const cleanedValue = cleanText(value).toLowerCase();

  return (
    cleanedValue === "true" ||
    cleanedValue === "1" ||
    cleanedValue === "tak" ||
    cleanedValue === "yes"
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

function parseDate(value: string | undefined | null) {
  const cleanedValue = cleanText(value);

  if (!cleanedValue) {
    return null;
  }

  const isoMatch = cleanedValue.match(/^(\d{4})-(\d{2})-(\d{2})/);

  if (!isoMatch) {
    return null;
  }

  const year = Number(isoMatch[1]);
  const month = Number(isoMatch[2]);
  const day = Number(isoMatch[3]);

  return createDateFromParts(year, month, day);
}

function parseDateTime(value: string | undefined | null) {
  const cleanedValue = cleanText(value);

  if (!cleanedValue) {
    return null;
  }

  const date = new Date(`${cleanedValue.replace(" ", "T")}Z`);

  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return date;
}

function parseTime(value: string | undefined | null) {
  const cleanedValue = cleanText(value);

  if (!cleanedValue) {
    return null;
  }

  const match = cleanedValue.match(/^(\d{1,2}):(\d{2})$/);

  if (!match) {
    return null;
  }

  const hour = Number(match[1]);
  const minute = Number(match[2]);

  if (hour < 0 || hour > 23 || minute < 0 || minute > 59) {
    return null;
  }

  return {
    hour,
    minute,
  };
}

function combineDateAndTime(date: Date, timeValue: string | undefined | null) {
  const time = parseTime(timeValue);

  if (!time) {
    return null;
  }

  return new Date(
    Date.UTC(
      date.getUTCFullYear(),
      date.getUTCMonth(),
      date.getUTCDate(),
      time.hour,
      time.minute,
      0
    )
  );
}

function getNights(checkIn: Date, checkOut: Date) {
  const millisecondsPerDay = 24 * 60 * 60 * 1000;
  const difference = checkOut.getTime() - checkIn.getTime();

  return Math.max(1, Math.round(difference / millisecondsPerDay));
}

function mapReservationStatus(value: string | undefined | null) {
  const cleanedValue = cleanText(value).toLowerCase();

  if (cleanedValue === "confirmed" || cleanedValue === "checked_in") {
    return "CONFIRMED";
  }

  if (cleanedValue === "checked_out" || cleanedValue === "completed") {
    return "COMPLETED";
  }

  if (cleanedValue === "cancelled" || cleanedValue === "canceled") {
    return "CANCELLED";
  }

  if (cleanedValue === "pending") {
    return "PENDING";
  }

  return "CONFIRMED";
}

function mapReservationSource(value: string | undefined | null) {
  const cleanedValue = cleanText(value).toLowerCase();

  if (cleanedValue === "booking_com" || cleanedValue === "booking") {
    return "BOOKING";
  }

  if (cleanedValue === "airbnb") {
    return "AIRBNB";
  }

  if (cleanedValue === "website" || cleanedValue === "www") {
    return "WEBSITE";
  }

  if (cleanedValue === "phone" || cleanedValue === "telefon") {
    return "PHONE";
  }

  return "MANUAL";
}

function normalizeCabinName(value: string) {
  return value.trim().toLowerCase().replace(/\s+/g, " ");
}

function findCabinByExternalRoomId(
  externalRoomId: string,
  cabins: ExistingCabin[]
) {
  const cabinName = externalRoomIdToCabinName[externalRoomId];

  if (!cabinName) {
    return null;
  }

  const normalizedCabinName = normalizeCabinName(cabinName);

  return (
    cabins.find(
      (cabin) =>
        normalizeCabinName(cabin.name) === normalizedCabinName ||
        normalizeCabinName(cabin.shortName ?? "") === normalizedCabinName
    ) ?? null
  );
}

function getGuestFullName(guest: ExistingGuest) {
  return `${guest.firstName} ${guest.lastName}`.trim() || "Gość";
}

function findGuestByExternalGuestId(
  externalGuestId: string | null,
  guests: ExistingGuest[]
) {
  if (!externalGuestId) {
    return null;
  }

  return (
    guests.find((guest) => cleanText(guest.externalGuestId) === externalGuestId) ??
    null
  );
}

function createImportedReservation(row: CsvRow): ImportedReservation | null {
  const externalReservationId = cleanText(row.id);
  const externalRoomId = cleanText(row.room_id);
  const externalGuestId = cleanNullableText(row.guest_id);

  const checkIn = parseDate(row.check_in);
  const checkOut = parseDate(row.check_out);

  if (!externalReservationId || !externalRoomId || !checkIn || !checkOut) {
    return null;
  }

  const adults = parseInteger(row.adults_count);
  const children = parseInteger(row.children_count);
  const guests = Math.max(1, adults + children);
  const nights = getNights(checkIn, checkOut);
  const totalPrice = parseNumber(row.total_price);
  const paidAmount = parseNumber(row.paid_amount);

  return {
    externalReservationId,
    externalRoomId,
    externalGuestId,
    cabinName: externalRoomIdToCabinName[externalRoomId] ?? "",
    checkIn,
    checkOut,
    checkInAt: combineDateAndTime(checkIn, row.check_in_time),
    checkOutAt: combineDateAndTime(checkOut, row.check_out_time),
    nights,
    adults,
    children,
    guests,
    totalPrice,
    paidAmount,
    paymentStatus: cleanNullableText(row.payment_status),
    source: mapReservationSource(row.source),
    status: mapReservationStatus(row.status),
    specialRequests: cleanNullableText(row.special_requests),
    orderedBy: cleanNullableText(row.ordered_by),
    externalCreatedAt: parseDateTime(row.created_date),
    externalUpdatedAt: parseDateTime(row.updated_date),
    externalCreatedById: cleanNullableText(row.created_by_id),
    externalCreatedBy: cleanNullableText(row.created_by),
    externalIsSample: parseBoolean(row.is_sample),
  };
}

function buildNotes(importedReservation: ImportedReservation) {
  const notes = [];

  if (importedReservation.specialRequests) {
    notes.push(importedReservation.specialRequests);
  }

  if (importedReservation.orderedBy) {
    notes.push(`Zamawiający: ${importedReservation.orderedBy}`);
  }

  if (importedReservation.paymentStatus) {
    notes.push(`Status płatności z programu: ${importedReservation.paymentStatus}`);
  }

  return notes.join("\n");
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
    ? `/admin/rezerwacje/import?${queryString}`
    : "/admin/rezerwacje/import";
}

export async function importReservationsFromCsv(formData: FormData) {
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

  const cabins = await prisma.cabin.findMany({
    select: {
      id: true,
      name: true,
      shortName: true,
    },
  });

  const guests = await prisma.guest.findMany({
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
      externalGuestId: true,
    },
  });

  let created = 0;
  let updated = 0;
  let skipped = 0;

  for (const row of rows) {
    const importedReservation = createImportedReservation(row);

    if (!importedReservation) {
      skipped += 1;
      continue;
    }

    const cabin = findCabinByExternalRoomId(
      importedReservation.externalRoomId,
      cabins
    );

    if (!cabin) {
      skipped += 1;
      continue;
    }

    const guest = findGuestByExternalGuestId(
      importedReservation.externalGuestId,
      guests
    );

    if (!guest) {
      skipped += 1;
      continue;
    }

    const guestName = getGuestFullName(guest);
    const notes = buildNotes(importedReservation);
    const pricePerNight =
      importedReservation.nights > 0
        ? Math.round((importedReservation.totalPrice / importedReservation.nights) * 100) / 100
        : importedReservation.totalPrice;

    const existingReservation = await prisma.reservation.findFirst({
      where: {
        OR: [
          {
            externalReservationId:
              importedReservation.externalReservationId,
          },
          {
            cabinId: cabin.id,
            guestId: guest.id,
            startDate: importedReservation.checkIn,
            endDate: importedReservation.checkOut,
          },
        ],
      },
      select: {
        id: true,
      },
    });

    if (existingReservation) {
      await prisma.reservation.update({
        where: {
          id: existingReservation.id,
        },
        data: {
          cabinId: cabin.id,
          guestId: guest.id,
          guestName,
          email: guest.email,
          phone: guest.phone,
          firstName: guest.firstName,
          lastName: guest.lastName,
          startDate: importedReservation.checkIn,
          endDate: importedReservation.checkOut,
          checkInAt: importedReservation.checkInAt,
          checkOutAt: importedReservation.checkOutAt,
          nights: importedReservation.nights,
          pricePerNight,
          guests: importedReservation.guests,
          adults: importedReservation.adults,
          children: importedReservation.children,
          status: importedReservation.status,
          source: importedReservation.source,
          externalReservationId:
            importedReservation.externalReservationId,
          externalRoomId: importedReservation.externalRoomId,
          externalGuestId: importedReservation.externalGuestId,
          externalCreatedAt: importedReservation.externalCreatedAt,
          externalUpdatedAt: importedReservation.externalUpdatedAt,
          externalCreatedById: importedReservation.externalCreatedById,
          externalCreatedBy: importedReservation.externalCreatedBy,
          externalIsSample: importedReservation.externalIsSample,
          paymentStatus: importedReservation.paymentStatus,
          specialRequests: importedReservation.specialRequests,
          orderedBy: importedReservation.orderedBy,
          totalPrice: importedReservation.totalPrice,
          paidAmount: importedReservation.paidAmount,
          street: guest.street,
          postalCode: guest.postalCode,
          city: guest.city,
          country: guest.country,
          notes,
        },
      });

      updated += 1;
      continue;
    }

    await prisma.reservation.create({
      data: {
        cabinId: cabin.id,
        guestId: guest.id,
        guestName,
        email: guest.email,
        phone: guest.phone,
        firstName: guest.firstName,
        lastName: guest.lastName,
        startDate: importedReservation.checkIn,
        endDate: importedReservation.checkOut,
        checkInAt: importedReservation.checkInAt,
        checkOutAt: importedReservation.checkOutAt,
        nights: importedReservation.nights,
        pricePerNight,
        guests: importedReservation.guests,
        adults: importedReservation.adults,
        children: importedReservation.children,
        status: importedReservation.status,
        source: importedReservation.source,
        externalReservationId: importedReservation.externalReservationId,
        externalRoomId: importedReservation.externalRoomId,
        externalGuestId: importedReservation.externalGuestId,
        externalCreatedAt: importedReservation.externalCreatedAt,
        externalUpdatedAt: importedReservation.externalUpdatedAt,
        externalCreatedById: importedReservation.externalCreatedById,
        externalCreatedBy: importedReservation.externalCreatedBy,
        externalIsSample: importedReservation.externalIsSample,
        paymentStatus: importedReservation.paymentStatus,
        specialRequests: importedReservation.specialRequests,
        orderedBy: importedReservation.orderedBy,
        totalPrice: importedReservation.totalPrice,
        paidAmount: importedReservation.paidAmount,
        street: guest.street,
        postalCode: guest.postalCode,
        city: guest.city,
        country: guest.country,
        notes,
      },
    });

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