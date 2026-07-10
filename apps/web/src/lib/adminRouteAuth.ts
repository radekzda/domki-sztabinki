import { NextResponse } from "next/server";
import {
  ADMIN_LOGIN_PATH,
  ADMIN_SESSION_COOKIE_NAME,
  isAdminSessionValid,
} from "@/lib/auth";

function getCookieValue(cookieHeader: string | null, cookieName: string) {
  if (!cookieHeader) {
    return undefined;
  }

  const cookies = cookieHeader.split(";").map((cookie) => cookie.trim());

  for (const cookie of cookies) {
    const separatorIndex = cookie.indexOf("=");

    if (separatorIndex === -1) {
      continue;
    }

    const name = cookie.slice(0, separatorIndex);
    const value = cookie.slice(separatorIndex + 1);

    if (name !== cookieName) {
      continue;
    }

    try {
      return decodeURIComponent(value);
    } catch {
      return value;
    }
  }

  return undefined;
}

export function isAdminRouteRequestAuthorized(request: Request) {
  const sessionValue = getCookieValue(
    request.headers.get("cookie"),
    ADMIN_SESSION_COOKIE_NAME,
  );

  return isAdminSessionValid(sessionValue);
}

export function getAdminRouteUnauthorizedResponse(request: Request) {
  const requestUrl = new URL(request.url);
  const loginUrl = new URL(ADMIN_LOGIN_PATH, requestUrl.origin);

  loginUrl.searchParams.set(
    "next",
    `${requestUrl.pathname}${requestUrl.search}`,
  );

  return NextResponse.redirect(loginUrl);
}