export const ADMIN_LOGIN_PATH = "/logowanie";

export const ADMIN_SESSION_COOKIE_NAME = "domki_sztabinki_admin_session";

export const ADMIN_SESSION_MAX_AGE_SECONDS = 60 * 60 * 24 * 7;

export function getAdminPassword() {
  return process.env.ADMIN_PASSWORD?.trim() ?? "";
}

export function getAdminSessionSecret() {
  return process.env.ADMIN_SESSION_SECRET?.trim() ?? "";
}

export function isAdminAuthConfigured() {
  return getAdminPassword() !== "" && getAdminSessionSecret() !== "";
}

export function isAdminPasswordValid(password: string) {
  const expectedPassword = getAdminPassword();

  if (!expectedPassword) {
    return false;
  }

  return password === expectedPassword;
}

export function isAdminSessionValid(sessionValue: string | undefined) {
  const expectedSessionSecret = getAdminSessionSecret();

  if (!expectedSessionSecret) {
    return false;
  }

  return sessionValue === expectedSessionSecret;
}

export function getSafeAdminRedirectPath(value: string | null | undefined) {
  if (!value) {
    return "/admin";
  }

  if (!value.startsWith("/admin")) {
    return "/admin";
  }

  return value;
}