"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  Building2,
  CalendarDays,
  CalendarRange,
  FileText,
  LayoutDashboard,
  MessageSquare,
  Settings,
  Users,
} from "lucide-react";

const menuItems = [
  {
    label: "Dashboard",
    href: "/admin",
    icon: LayoutDashboard,
    exact: true,
  },
  {
    label: "Rezerwacje",
    href: "/admin/rezerwacje",
    icon: CalendarDays,
  },
  {
    label: "Goście",
    href: "/admin/goscie",
    icon: Users,
  },
  {
    label: "Domki",
    href: "/admin/domki",
    icon: Building2,
  },
  {
    label: "Kalendarz",
    href: "/admin/kalendarz",
    icon: CalendarRange,
  },
  {
    label: "Zapytania",
    href: "/admin/zapytania",
    icon: MessageSquare,
  },
  {
    label: "Szablony",
    href: "/admin/szablony",
    icon: FileText,
  },
  {
    label: "Ustawienia",
    href: "/admin/ustawienia",
    icon: Settings,
  },
];

function isActivePath(pathname: string, href: string, exact?: boolean) {
  if (exact) {
    return pathname === href;
  }

  return pathname === href || pathname.startsWith(`${href}/`);
}

export default function AdminSidebar() {
  const pathname = usePathname();

  return (
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
        {menuItems.map((item) => {
          const Icon = item.icon;
          const isActive = isActivePath(pathname, item.href, item.exact);

          return (
            <Link
              key={item.href}
              href={item.href}
              aria-current={isActive ? "page" : undefined}
              className={
                isActive
                  ? "flex items-center gap-3 rounded-lg p-3 bg-green-50 text-green-700 font-medium border border-green-100 transition"
                  : "flex items-center gap-3 rounded-lg p-3 text-zinc-700 hover:bg-green-50 hover:text-green-700 transition"
              }
            >
              <Icon size={20} />
              <span>{item.label}</span>
            </Link>
          );
        })}
      </nav>

      <div className="border-t p-4 text-sm text-zinc-500">
        © 2026 Domki Sztabinki
      </div>
    </aside>
  );
}