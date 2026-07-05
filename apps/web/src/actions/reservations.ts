"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { checkCabinAvailability } from "@/lib/reservations";

const allowedStatuses = ["PENDING", "CONFIRMED", "CANCELLED", "COMPLETED"];

function redirectWithError(message: string): never {
  redirect(`/admin/rezerwacje/nowa?error=${encodeURIComponent(message)}`);
}

function getRequiredString(formData: FormData, key: string) {
  const value = formData.get(key);

  if (typeof value !== "string" || value.trim() === "") {
    redirectWithError("Uzupełnij wszystkie wymagane pola.");
  }

  return value.trim();
}

function parseDate(value: string) {
  return new Date(`${value}T12:00:00.000Z`);
}

export async function createReservation(formData: FormData) {
  const cabinId = getRequiredString(formData, "cabinId");
  const guestName = getRequiredString(formData, "guestName");
  const email = getRequiredString(formData, "email");
  const phone = formData.get("phone")?.toString().trim() || null;
  const startDateValue = getRequiredString(formData, "startDate");
  const endDateValue = getRequiredString(formData, "endDate");
  const guests = Number(formData.get("guests"));
  const status = getRequiredString(formData, "status");

  if (!allowedStatuses.includes(status)) {
    redirectWithError("Nieprawidłowy status rezerwacji.");
  }

  if (!Number.isInteger(guests) || guests < 1) {
    redirectWithError("Liczba gości musi być większa od zera.");
  }

  const startDate = parseDate(startDateValue);
  const endDate = parseDate(endDateValue);

  if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
    redirectWithError("Nieprawidłowa data rezerwacji.");
  }

  if (endDate <= startDate) {
    redirectWithError("Data wyjazdu musi być późniejsza niż data przyjazdu.");
  }

  const cabin = await prisma.cabin.findUnique({
    where: { id: cabinId },
  });

  if (!cabin) {
    redirectWithError("Wybrany domek nie istnieje.");
  }

  if (guests > cabin.maxGuests) {
    redirectWithError(`Ten domek mieści maksymalnie ${cabin.maxGuests} osób.`);
  }

  if (status === "PENDING" || status === "CONFIRMED") {
    const availability = await checkCabinAvailability({
      cabinId,
      startDate,
      endDate,
    });

    if (!availability.available) {
      redirectWithError(
        "Wybrany domek jest już zarezerwowany w podanym terminie."
      );
    }
  }

  await prisma.reservation.create({
    data: {
      cabinId,
      guestName,
      email,
      phone,
      startDate,
      endDate,
      guests,
      status,
    },
  });

  revalidatePath("/admin/rezerwacje");
  redirect("/admin/rezerwacje");
}