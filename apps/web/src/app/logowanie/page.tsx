import { loginAdmin } from "@/actions/auth";
import {
  getSafeAdminRedirectPath,
  isAdminAuthConfigured,
} from "@/lib/auth";

type LoginPageProps = {
  searchParams?: Promise<{
    error?: string;
    next?: string;
    logout?: string;
  }>;
};

function getErrorMessage(error: string | undefined) {
  if (error === "invalid-password") {
    return "Nieprawidłowe hasło administratora.";
  }

  if (error === "not-configured") {
    return "Logowanie nie jest skonfigurowane. Uzupełnij ADMIN_PASSWORD i ADMIN_SESSION_SECRET w pliku .env.";
  }

  return null;
}

export default async function LoginPage({ searchParams }: LoginPageProps) {
  const resolvedSearchParams = searchParams ? await searchParams : undefined;

  const nextPath = getSafeAdminRedirectPath(resolvedSearchParams?.next);
  const errorMessage = getErrorMessage(resolvedSearchParams?.error);
  const isConfigured = isAdminAuthConfigured();
  const loggedOut = resolvedSearchParams?.logout === "1";

  return (
    <main className="flex min-h-screen items-center justify-center bg-zinc-100 px-4 py-12">
      <div className="w-full max-w-md rounded-2xl border bg-white p-8 shadow-sm">
        <div>
          <h1 className="text-3xl font-bold text-zinc-900">
            Logowanie
          </h1>

          <p className="mt-2 text-sm leading-6 text-zinc-500">
            Zaloguj się, aby przejść do panelu administratora Domki Sztabinki.
          </p>
        </div>

        {loggedOut ? (
          <div className="mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800">
            Wylogowano z panelu administratora.
          </div>
        ) : null}

        {errorMessage ? (
          <div className="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
            {errorMessage}
          </div>
        ) : null}

        {!isConfigured ? (
          <div className="mt-6 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm leading-6 text-yellow-900">
            Brakuje konfiguracji logowania w pliku <strong>.env</strong>.
            Dodaj zmienne <strong>ADMIN_PASSWORD</strong> oraz{" "}
            <strong>ADMIN_SESSION_SECRET</strong>, a następnie uruchom ponownie
            serwer developerski.
          </div>
        ) : null}

        <form action={loginAdmin} className="mt-8 space-y-5">
          <input type="hidden" name="next" value={nextPath} />

          <div className="space-y-2">
            <label
              htmlFor="password"
              className="text-sm font-medium text-zinc-700"
            >
              Hasło administratora
            </label>

            <input
              id="password"
              name="password"
              type="password"
              required
              autoComplete="current-password"
              className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
            />
          </div>

          <button
            type="submit"
            className="w-full rounded-lg bg-green-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-green-800"
          >
            Zaloguj
          </button>
        </form>
      </div>
    </main>
  );
}