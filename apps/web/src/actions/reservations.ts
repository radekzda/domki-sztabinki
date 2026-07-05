"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";

const allowedStatuses = ["PENDING", "CONFIRMED", "CANCELLED", "COMPLETED"];

function getRequiredString(formData: FormData, key: string) {
  const value = formData.get(key);

  if (typeof value !== "string" || value.trim() === "") {
    throw new Error(`Pole ${key} jest wymagane.`);
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
    throw new Error("Nieprawidłowy status rezerwacji.");
  }

  if (!Number.isInteger(guests) || guests < 1) {
    throw new Error("Liczba gości musi być większa od zera.");
  }

  const startDate = parseDate(startDateValue);
  const endDate = parseDate(endDateValue);

  if (endDate <= startDate) {
    throw new Error("Data wyjazdu musi być późniejsza niż data przyjazdu.");
  }

  const cabin = await prisma.cabin.findUnique({
    where: { id: cabinId },
  });

  if (!cabin) {
    throw new Error("Wybrany domek nie istnieje.");
  }

  if (guests > cabin.maxGuests) {
    throw new Error(`Ten domek mieści maksymalnie ${cabin.maxGuests} osób.`);
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