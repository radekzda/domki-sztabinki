import { NextResponse, type NextRequest } from "next/server";
import {
  ADMIN_LOGIN_PATH,
  ADMIN_SESSION_COOKIE_NAME,
  isAdminSessionValid,
} from "@/lib/auth";

export function proxy(request: NextRequest) {
  const sessionValue = request.cookies.get(ADMIN_SESSION_COOKIE_NAME)?.value;

  if (isAdminSessionValid(sessionValue)) {
    return NextResponse.next();
  }

  const loginUrl = request.nextUrl.clone();

  loginUrl.pathname = ADMIN_LOGIN_PATH;
  loginUrl.searchParams.set(
    "next",
    `${request.nextUrl.pathname}${request.nextUrl.search}`,
  );

  return NextResponse.redirect(loginUrl);
}

export const config = {
  matcher: ["/admin/:path*"],
};