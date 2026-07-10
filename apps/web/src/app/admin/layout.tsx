import { User } from "lucide-react";
import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { logoutAdmin } from "@/actions/auth";
import {
  ADMIN_LOGIN_PATH,
  ADMIN_SESSION_COOKIE_NAME,
  isAdminSessionValid,
} from "@/lib/auth";
import AdminSidebar from "../../components/admin/AdminSidebar";

export default async function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const cookieStore = await cookies();
  const sessionValue = cookieStore.get(ADMIN_SESSION_COOKIE_NAME)?.value;

  if (!isAdminSessionValid(sessionValue)) {
    redirect(`${ADMIN_LOGIN_PATH}?next=/admin`);
  }

  return (
    <div className="flex min-h-screen bg-zinc-100">
      <AdminSidebar />

      <div className="flex flex-1 flex-col">
        <header className="flex h-16 items-center justify-between border-b bg-white px-8">
          <h2 className="text-lg font-semibold">
            Panel administratora
          </h2>

          <div className="flex items-center gap-4 text-zinc-700">
            <div className="flex items-center gap-3">
              <User size={20} />
              <span>Administrator</span>
            </div>

            <form action={logoutAdmin}>
              <button
                type="submit"
                className="rounded-lg border px-3 py-2 text-sm font-semibold hover:bg-zinc-50"
              >
                Wyloguj
              </button>
            </form>
          </div>
        </header>

        <main className="flex-1 p-8">
          {children}
        </main>
      </div>
    </div>
  );
}