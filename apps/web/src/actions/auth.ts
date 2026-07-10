"use server";

import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import {
  ADMIN_LOGIN_PATH,
  ADMIN_SESSION_COOKIE_NAME,
  ADMIN_SESSION_MAX_AGE_SECONDS,
  getAdminSessionSecret,
  getSafeAdminRedirectPath,
  isAdminAuthConfigured,
  isAdminPasswordValid,
} from "@/lib/auth";

function getString(formData: FormData, key: string) {
  const value = formData.get(key);

  if (typeof value !== "string") {
    return "";
  }

  return value.trim();
}

function buildLoginUrl(error: string, nextPath: string) {
  const params = new URLSearchParams();

  params.set("error", error);

  if (nextPath) {
    params.set("next", nextPath);
  }

  return `${ADMIN_LOGIN_PATH}?${params.toString()}`;
}

export async function loginAdmin(formData: FormData) {
  const password = getString(formData, "password");
  const nextPath = getSafeAdminRedirectPath(getString(formData, "next"));

  if (!isAdminAuthConfigured()) {
    redirect(buildLoginUrl("not-configured", nextPath));
  }

  if (!isAdminPasswordValid(password)) {
    redirect(buildLoginUrl("invalid-password", nextPath));
  }

  const cookieStore = await cookies();

  cookieStore.set(ADMIN_SESSION_COOKIE_NAME, getAdminSessionSecret(), {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: ADMIN_SESSION_MAX_AGE_SECONDS,
  });

  redirect(nextPath);
}

export async function logoutAdmin() {
  const cookieStore = await cookies();

  cookieStore.set(ADMIN_SESSION_COOKIE_NAME, "", {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: 0,
  });

  redirect(`${ADMIN_LOGIN_PATH}?logout=1`);
}