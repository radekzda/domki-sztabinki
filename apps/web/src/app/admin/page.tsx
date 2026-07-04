import { prisma } from "@/lib/prisma";
import {
  Building2,
  CalendarDays,
  Users,
  Wallet,
} from "lucide-react";

export default async function AdminPage() {
  const [cabinsCount, reservationsCount, usersCount] =
    await Promise.all([
      prisma.cabin.count(),
      prisma.reservation.count(),
      prisma.user.count(),
    ]);

  const cards = [
    {
      title: "Domki",
      value: cabinsCount,
      icon: Building2,
    },
    {
      title: "Rezerwacje",
      value: reservationsCount,
      icon: CalendarDays,
    },
    {
      title: "Użytkownicy",
      value: usersCount,
      icon: Users,
    },
    {
      title: "Przychód",
      value: "—",
      icon: Wallet,
    },
  ];

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold">
          Dashboard
        </h1>

        <p className="text-zinc-500 mt-1">
          Witaj w panelu administracyjnym Domki Sztabinki.
        </p>
      </div>

      <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        {cards.map((card) => {
          const Icon = card.icon;

          return (
            <div
              key={card.title}
              className="rounded-xl border bg-white p-6 shadow-sm"
            >
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-zinc-500">
                    {card.title}
                  </p>

                  <h2 className="mt-2 text-3xl font-bold">
                    {card.value}
                  </h2>
                </div>

                <Icon className="h-8 w-8 text-green-700" />
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}