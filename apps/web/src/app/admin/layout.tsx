import Link from "next/link";
import {
  Home,
  Building2,
  CalendarDays,
  Settings,
  LayoutDashboard,
  User,
} from "lucide-react";

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="flex min-h-screen bg-zinc-100">
      {/* SIDEBAR */}
      <aside className="w-72 bg-white border-r shadow-sm flex flex-col">
        <div className="border-b p-6">
          <h1 className="text-2xl font-bold text-green-700">
            Domki Sztabinki
          </h1>

          <p className="text-sm text-zinc-500 mt-1">
            Panel administratora
          </p>
        </div>

        <nav className="flex-1 p-4 space-y-2">
          <Link
            href="/admin"
            className="flex items-center gap-3 rounded-lg p-3 hover:bg-green-50 hover:text-green-700 transition"
          >
            <LayoutDashboard size={20} />
            Dashboard
          </Link>

          <Link
            href="/admin/domki"
            className="flex items-center gap-3 rounded-lg p-3 hover:bg-green-50 hover:text-green-700 transition"
          >
            <Building2 size={20} />
            Domki
          </Link>

          <Link
            href="/admin/rezerwacje"
            className="flex items-center gap-3 rounded-lg p-3 hover:bg-green-50 hover:text-green-700 transition"
          >
            <CalendarDays size={20} />
            Rezerwacje
          </Link>

          <Link
            href="/admin/kalendarz"
            className="flex items-center gap-3 rounded-lg p-3 hover:bg-green-50 hover:text-green-700 transition"
          >
            <Home size={20} />
            Kalendarz
          </Link>

          <Link
            href="/admin/ustawienia"
            className="flex items-center gap-3 rounded-lg p-3 hover:bg-green-50 hover:text-green-700 transition"
          >
            <Settings size={20} />
            Ustawienia
          </Link>
        </nav>

        <div className="border-t p-4 text-sm text-zinc-500">
          © 2026 Domki Sztabinki
        </div>
      </aside>

      {/* CONTENT */}
      <div className="flex flex-1 flex-col">
        <header className="h-16 bg-white border-b px-8 flex items-center justify-between">
          <h2 className="font-semibold text-lg">
            Panel administratora
          </h2>

          <div className="flex items-center gap-3">
            <User size={20} />
            <span>Administrator</span>
          </div>
        </header>

        <main className="flex-1 p-8">
          {children}
        </main>
      </div>
    </div>
  );
}