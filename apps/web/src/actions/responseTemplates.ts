"use server";

import { revalidatePath } from "next/cache";

import { prisma } from "@/lib/prisma";

function getStringValue(formData: FormData, key: string) {
  const value = formData.get(key);

  if (typeof value !== "string") {
    return "";
  }

  return value.trim();
}

function getBooleanValue(formData: FormData, key: string) {
  return formData.get(key) === "on";
}

function getIntegerValue(formData: FormData, key: string, fallback: number) {
  const value = getStringValue(formData, key);
  const parsedValue = Number.parseInt(value, 10);

  if (!Number.isInteger(parsedValue)) {
    return fallback;
  }

  return parsedValue;
}

export async function createResponseTemplate(formData: FormData) {
  const name = getStringValue(formData, "name");
  const subject = getStringValue(formData, "subject");
  const body = getStringValue(formData, "body");
  const sortOrder = getIntegerValue(formData, "sortOrder", 0);
  const isActive = getBooleanValue(formData, "isActive");

  if (!name || !subject || !body) {
    return;
  }

  await prisma.responseTemplate.create({
    data: {
      name,
      subject,
      body,
      sortOrder,
      isActive,
    },
  });

  revalidatePath("/admin/szablony");
}

export async function updateResponseTemplate(formData: FormData) {
  const templateId = getStringValue(formData, "templateId");
  const name = getStringValue(formData, "name");
  const subject = getStringValue(formData, "subject");
  const body = getStringValue(formData, "body");
  const sortOrder = getIntegerValue(formData, "sortOrder", 0);
  const isActive = getBooleanValue(formData, "isActive");

  if (!templateId || !name || !subject || !body) {
    return;
  }

  await prisma.responseTemplate.updateMany({
    where: {
      id: templateId,
    },
    data: {
      name,
      subject,
      body,
      sortOrder,
      isActive,
    },
  });

  revalidatePath("/admin/szablony");
  revalidatePath("/admin/zapytania");
}

export async function deleteResponseTemplate(formData: FormData) {
  const templateId = getStringValue(formData, "templateId");

  if (!templateId) {
    return;
  }

  await prisma.responseTemplate.deleteMany({
    where: {
      id: templateId,
    },
  });

  revalidatePath("/admin/szablony");
  revalidatePath("/admin/zapytania");
}