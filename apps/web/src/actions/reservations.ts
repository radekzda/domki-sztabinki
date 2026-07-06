"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { checkCabinAvailability } from "@/lib/reservations";
import {
  calculateDefaultTotalPrice,
  calculateReservationNights,
} from "@/modules/pricing/pricing.utils";

const allowedStatuses = ["PENDING", "CONFIRMED", "CANCELLED", "COMPLETED"];
const allowedSources = ["MANUAL", "PHONE", "WEBSITE", "BOOKING", "AIRBNB"];

type ErrorRedirect = (message: string) => never;

type CabinPricingData = {
  id: string;
  maxGuests: number;
  pricePerNight: number;
  priceOneNight: number;
  priceTwoNights: number;
  priceThreeNights: number;
  priceFourNights: number;
  priceFiveNights: number;
  priceSixNights: number;
  priceSevenPlusNights: number;
};

type ReservationFormValues = {
  guestId: string | null;

  cabinId: string;

  guestName: string;
  firstName: string;
  lastName: string;

  email: string;
  phone: string | null;

  startDate: Date;
  endDate: Date;

  checkInAt: Date;
  checkOutAt: Date;

  nights: number;
  pricePerNight: number | null;

  guests: number;
  adults: number;
  children: number;

  status: string;
  source: string;

  totalPrice: number | null;
  paidAmount: number | null;

  street: string | null;
  postalCode: string | null;
  city: string | null;
  country: string | null;

  notes: string | null;
};

function redirectWithNewReservationError(message: string): never {
  redirect(`/admin/rezerwacje/nowa?error=${encodeURIComponent(message)}`);
}

function redirectWithEditReservationError(
  reservationId: string,
  message: string
): never {
  redirect(
    `/admin/rezerwacje/${reservationId}/edytuj?error=${encodeURIComponent(
      message
    )}`
  );
}

function redirectWithReservationDetailsError(
  reservationId: string,
  message: string
): never {
  redirect(
    `/admin/rezerwacje/${reservationId}?error=${encodeURIComponent(message)}`
  );
}

function getRequiredString(
  formData: FormData,
  key: string,
  onError: ErrorRedirect
) {
  const value = formData.get(key);

  if (typeof value !== "string" || value.trim() === "") {
    onError("Uzupełnij wszystkie wymagane pola.");
  }

  return value.trim();
}

function getOptionalString(formData: FormData, key: string) {
  const value = formData.get(key);

  if (typeof value !== "string") {
    return null;
  }

  const trimmedValue = value.trim();

  return trimmedValue === "" ? null : trimmedValue;
}

function getNumber(formData: FormData, key: string, defaultValue: number) {
  const value = formData.get(key);

  if (typeof value !== "string" || value.trim() === "") {
    return defaultValue;
  }

  const parsedValue = Number(value);

  if (!Number.isFinite(parsedValue)) {
    return defaultValue;
  }

  return parsedValue;
}

function parseDateTime(dateValue: string, timeValue: string) {
  return new Date(`${dateValue}T${timeValue}:00.000`);
}

function splitGuestName(guestName: string) {
  const parts = guestName.trim().split(/\s+/);

  if (parts.length === 1) {
    return {
      firstName: parts[0],
      lastName: "",
    };
  }

  return {
    firstName: parts.slice(0, -1).join(" "),
    lastName: parts[parts.length - 1],
  };
}

function parseMoney(
  value: string | null,
  fieldName: string,
  onError: ErrorRedirect
) {
  if (value === null) {
    return null;
  }

  const parsedValue = Number(value.replace(",", "."));

  if (!Number.isFinite(parsedValue) || parsedValue < 0) {
    onError(`Nieprawidłowa kwota: ${fieldName}.`);
  }

  return parsedValue;
}

function decimalToNumber(value: { toString: () => string } | null) {
  if (!value) {
    return null;
  }

  return Number(value.toString());
}

function parseReservationForm(
  formData: FormData,
  onError: ErrorRedirect
): ReservationFormValues {
  const guestId = getOptionalString(formData, "guestId");
  const cabinId = getRequiredString(formData, "cabinId", onError);

  const firstName = getOptionalString(formData, "firstName");
  const lastName = getOptionalString(formData, "lastName");

  const legacyGuestName = getOptionalString(formData, "guestName");

  const guestName =
    firstName || lastName
      ? `${firstName ?? ""} ${lastName ?? ""}`.trim()
      : legacyGuestName ?? "";

  if (!guestName) {
    onError("Podaj imię i nazwisko gościa.");
  }

  const splitName = splitGuestName(guestName);

  const finalFirstName = firstName ?? splitName.firstName;
  const finalLastName = lastName ?? splitName.lastName;

  const email = getRequiredString(formData, "email", onError);
  const phone = getOptionalString(formData, "phone");

  const startDateValue = getRequiredString(formData, "startDate", onError);
  const endDateValue = getRequiredString(formData, "endDate", onError);

  const checkInTime = getOptionalString(formData, "checkInTime") ?? "15:00";
  const checkOutTime = getOptionalString(formData, "checkOutTime") ?? "11:00";

  const startDate = parseDateTime(startDateValue, checkInTime);
  const endDate = parseDateTime(endDateValue, checkOutTime);

  if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
    onError("Nieprawidłowa data rezerwacji.");
  }

  if (endDate <= startDate) {
    onError("Data wyjazdu musi być późniejsza niż data przyjazdu.");
  }

  const nights = calculateReservationNights(startDate, endDate);

  if (!Number.isInteger(nights) || nights < 1) {
    onError("Nieprawidłowa liczba nocy.");
  }

  const adults = Math.max(0, Math.floor(getNumber(formData, "adults", 1)));
  const children = Math.max(0, Math.floor(getNumber(formData, "children", 0)));

  const legacyGuests = getNumber(formData, "guests", adults + children);
  const guests = adults + children > 0 ? adults + children : legacyGuests;

  if (!Number.isInteger(guests) || guests < 1) {
    onError("Liczba gości musi być większa od zera.");
  }

  const status = getRequiredString(formData, "status", onError);

  if (!allowedStatuses.includes(status)) {
    onError("Nieprawidłowy status rezerwacji.");
  }

  const source = getOptionalString(formData, "source") ?? "MANUAL";

  if (!allowedSources.includes(source)) {
    onError("Nieprawidłowe źródło rezerwacji.");
  }

  const totalPrice = parseMoney(
    getOptionalString(formData, "totalPrice"),
    "cena pobytu",
    onError
  );

  const paidAmount = parseMoney(
    getOptionalString(formData, "paidAmount"),
    "wpłacono",
    onError
  );

  return {
    guestId,

    cabinId,

    guestName,
    firstName: finalFirstName,
    lastName: finalLastName,

    email,
    phone,

    startDate,
    endDate,

    checkInAt: startDate,
    checkOutAt: endDate,

    nights,
    pricePerNight: null,

    guests,
    adults,
    children,

    status,
    source,

    totalPrice,
    paidAmount,

    street: getOptionalString(formData, "street"),
    postalCode: getOptionalString(formData, "postalCode"),
    city: getOptionalString(formData, "city"),
    country: getOptionalString(formData, "country"),
    notes: getOptionalString(formData, "notes"),
  };
}

async function validateReservationData({
  values,
  onError,
  ignoreReservationId,
}: {
  values: ReservationFormValues;
  onError: ErrorRedirect;
  ignoreReservationId?: string;
}): Promise<CabinPricingData> {
  const cabin = await prisma.cabin.findUnique({
    where: {
      id: values.cabinId,
    },
    select: {
      id: true,
      maxGuests: true,
      pricePerNight: true,
      priceOneNight: true,
      priceTwoNights: true,
      priceThreeNights: true,
      priceFourNights: true,
      priceFiveNights: true,
      priceSixNights: true,
      priceSevenPlusNights: true,
    },
  });

  if (!cabin) {
    return onError("Wybrany domek nie istnieje.");
  }

  if (values.guests > cabin.maxGuests) {
    return onError(`Ten domek mieści maksymalnie ${cabin.maxGuests} osób.`);
  }

  if (values.status === "PENDING" || values.status === "CONFIRMED") {
    const availability = await checkCabinAvailability({
      cabinId: values.cabinId,
      startDate: values.startDate,
      endDate: values.endDate,
      ignoreReservationId,
    });

    if (!availability.available) {
      return onError(
        "Wybrany domek jest już zarezerwowany w podanym terminie."
      );
    }
  }

  return cabin;
}

function completeReservationPricing(
  values: ReservationFormValues,
  cabin: CabinPricingData,
  onError: ErrorRedirect
): ReservationFormValues {
  const defaultTotalPrice = calculateDefaultTotalPrice(values.nights, cabin);

  const totalPrice = values.totalPrice ?? defaultTotalPrice;
  const paidAmount = values.paidAmount ?? 0;

  if (paidAmount > totalPrice) {
    onError("Kwota wpłacona nie może być większa niż cena pobytu.");
  }

  return {
    ...values,
    totalPrice,
    paidAmount,
    pricePerNight: Number((totalPrice / values.nights).toFixed(2)),
  };
}

async function saveGuestForReservation({
  values,
  existingGuestId,
}: {
  values: ReservationFormValues;
  existingGuestId?: string | null;
}) {
  const guestData = {
    firstName: values.firstName,
    lastName: values.lastName,
    email: values.email,
    phone: values.phone,
    country: values.country,
  };

  if (existingGuestId) {
    const existingGuest = await prisma.guest.findUnique({
      where: {
        id: existingGuestId,
      },
    });

    if (existingGuest) {
      return prisma.guest.update({
        where: {
          id: existingGuestId,
        },
        data: guestData,
      });
    }
  }

  const existingGuestByEmail = await prisma.guest.findFirst({
    where: {
      email: values.email,
    },
  });

  if (existingGuestByEmail) {
    return prisma.guest.update({
      where: {
        id: existingGuestByEmail.id,
      },
      data: guestData,
    });
  }

  return prisma.guest.create({
    data: guestData,
  });
}

export async function createReservation(formData: FormData) {
  const values = parseReservationForm(
    formData,
    redirectWithNewReservationError
  );

  const cabin = await validateReservationData({
    values,
    onError: redirectWithNewReservationError,
  });

  const valuesWithPricing = completeReservationPricing(
    values,
    cabin,
    redirectWithNewReservationError
  );

  const guest = await saveGuestForReservation({
    values: valuesWithPricing,
    existingGuestId: valuesWithPricing.guestId,
  });

  const { guestId, ...reservationData } = valuesWithPricing;

  await prisma.reservation.create({
    data: {
      ...reservationData,
      guestId: guest.id,
    },
  });

  revalidatePath("/admin/rezerwacje");
  revalidatePath("/admin/kalendarz");
  revalidatePath("/admin/goscie");
  revalidatePath(`/admin/goscie/${guest.id}`);

  redirect("/admin/rezerwacje");
}

export async function updateReservation(formData: FormData) {
  const reservationIdValue = formData.get("reservationId");

  if (
    typeof reservationIdValue !== "string" ||
    reservationIdValue.trim() === ""
  ) {
    redirect("/admin/rezerwacje");
  }

  const reservationId = reservationIdValue.trim();

  const onError = (message: string): never => {
    redirectWithEditReservationError(reservationId, message);
  };

  const existingReservation = await prisma.reservation.findUnique({
    where: {
      id: reservationId,
    },
  });

  if (!existingReservation) {
    redirect("/admin/rezerwacje");
  }

  const values = parseReservationForm(formData, onError);

  const cabin = await validateReservationData({
    values,
    onError,
    ignoreReservationId: reservationId,
  });

  const valuesWithPricing = completeReservationPricing(values, cabin, onError);

  const guest = await saveGuestForReservation({
    values: valuesWithPricing,
    existingGuestId: existingReservation.guestId,
  });

  const { guestId, ...reservationData } = valuesWithPricing;

  await prisma.reservation.update({
    where: {
      id: reservationId,
    },
    data: {
      ...reservationData,
      guestId: guest.id,
    },
  });

  revalidatePath("/admin/rezerwacje");
  revalidatePath(`/admin/rezerwacje/${reservationId}`);
  revalidatePath(`/admin/rezerwacje/${reservationId}/edytuj`);
  revalidatePath("/admin/kalendarz");
  revalidatePath("/admin/goscie");
  revalidatePath(`/admin/goscie/${guest.id}`);

  redirect(`/admin/rezerwacje/${reservationId}`);
}

export async function updateReservationStatus(formData: FormData) {
  const reservationIdValue = formData.get("reservationId");
  const statusValue = formData.get("status");

  if (
    typeof reservationIdValue !== "string" ||
    reservationIdValue.trim() === ""
  ) {
    redirect("/admin/rezerwacje");
  }

  const reservationId = reservationIdValue.trim();

  if (typeof statusValue !== "string" || statusValue.trim() === "") {
    redirectWithReservationDetailsError(
      reservationId,
      "Nie wybrano statusu rezerwacji."
    );
  }

  const status = statusValue.trim();

  if (!allowedStatuses.includes(status)) {
    redirectWithReservationDetailsError(
      reservationId,
      "Nieprawidłowy status rezerwacji."
    );
  }

  const reservation = await prisma.reservation.findUnique({
    where: {
      id: reservationId,
    },
  });

  if (!reservation) {
    redirect("/admin/rezerwacje");
  }

  if (status === "PENDING" || status === "CONFIRMED") {
    const availability = await checkCabinAvailability({
      cabinId: reservation.cabinId,
      startDate: reservation.checkInAt ?? reservation.startDate,
      endDate: reservation.checkOutAt ?? reservation.endDate,
      ignoreReservationId: reservation.id,
    });

    if (!availability.available) {
      redirectWithReservationDetailsError(
        reservationId,
        "Nie można ustawić tego statusu, ponieważ termin koliduje z inną rezerwacją."
      );
    }
  }

  await prisma.reservation.update({
    where: {
      id: reservationId,
    },
    data: {
      status,
    },
  });

  revalidatePath("/admin/rezerwacje");
  revalidatePath(`/admin/rezerwacje/${reservationId}`);
  revalidatePath(`/admin/rezerwacje/${reservationId}/edytuj`);
  revalidatePath("/admin/kalendarz");

  redirect(`/admin/rezerwacje/${reservationId}`);
}

export async function updateReservationPayment(formData: FormData) {
  const reservationIdValue = formData.get("reservationId");
  const paidAmountValue = formData.get("paidAmount");
  const actionValue = formData.get("paymentAction");

  if (
    typeof reservationIdValue !== "string" ||
    reservationIdValue.trim() === ""
  ) {
    redirect("/admin/rezerwacje");
  }

  const reservationId = reservationIdValue.trim();

  const reservation = await prisma.reservation.findUnique({
    where: {
      id: reservationId,
    },
  });

  if (!reservation) {
    redirect("/admin/rezerwacje");
  }

  const totalPrice = decimalToNumber(reservation.totalPrice);

  let paidAmount: number | null = null;

  if (actionValue === "MARK_AS_PAID") {
    if (totalPrice === null) {
      redirectWithReservationDetailsError(
        reservationId,
        "Nie można oznaczyć jako opłacone, bo nie wpisano ceny pobytu."
      );
    }

    paidAmount = totalPrice;
  } else {
    if (typeof paidAmountValue !== "string" || paidAmountValue.trim() === "") {
      paidAmount = null;
    } else {
      const parsedPaidAmount = Number(paidAmountValue.trim().replace(",", "."));

      if (!Number.isFinite(parsedPaidAmount) || parsedPaidAmount < 0) {
        redirectWithReservationDetailsError(
          reservationId,
          "Nieprawidłowa kwota wpłaty."
        );
      }

      paidAmount = parsedPaidAmount;
    }
  }

  if (totalPrice !== null && paidAmount !== null && paidAmount > totalPrice) {
    redirectWithReservationDetailsError(
      reservationId,
      "Kwota wpłacona nie może być większa niż cena pobytu."
    );
  }

  await prisma.reservation.update({
    where: {
      id: reservationId,
    },
    data: {
      paidAmount,
    },
  });

  revalidatePath("/admin/rezerwacje");
  revalidatePath(`/admin/rezerwacje/${reservationId}`);
  revalidatePath(`/admin/rezerwacje/${reservationId}/edytuj`);
  revalidatePath("/admin/kalendarz");

  redirect(`/admin/rezerwacje/${reservationId}`);
}