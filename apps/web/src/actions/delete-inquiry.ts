"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";

import { prisma } from "@/lib/prisma";

function getRequiredString(formData: FormData, key: string) {
  const value = formData.get(key);

  if (typeof value !== "string") {
    return "";
  }

  return value.trim();
}

export async function deleteInquiry(formData: FormData) {
  const inquiryId = getRequiredString(formData, "inquiryId");

  if (!inquiryId) {
    redirect("/admin/zapytania");
  }

  const inquiry = await prisma.inquiry.findUnique({
    where: {
      id: inquiryId,
    },
    select: {
      id: true,
    },
  });

  if (!inquiry) {
    redirect("/admin/zapytania");
  }

  await prisma.inquiry.delete({
    where: {
      id: inquiry.id,
    },
  });

  revalidatePath("/admin/zapytania");
  revalidatePath(`/admin/zapytania/${inquiry.id}`);
  revalidatePath(`/admin/zapytania/${inquiry.id}/usun`);

  redirect("/admin/zapytania");
}