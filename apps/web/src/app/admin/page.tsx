import Link from "next/link";
import { prisma } from "@/lib/prisma";
import {
  AlertCircle,
  ArrowRight,
  BedDouble,
  Building2,
  CalendarCheck,
  CalendarClock,
  CalendarDays,
  CheckCircle2,
  CircleDollarSign,
  Inbox,
  Users,
  Wallet,
} from "lucide-react";

function formatCurrency(value: number) {
  return new Intl.NumberFormat("pl-PL", {
    style: "currency",
    currency: "PLN",
    maximumFractionDigits: 0,
  }).format(value);
}

function formatDate(date: Date) {
  return new Intl.DateTimeFormat("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(date);
}

function toNumber(value: unknown) {
  if (value === null || value === undefined) {
    return 0;
  }

  return Number(value);
}

function getStatusLabel(status: string) {
  if (status === "PENDING") {
    return "Oczekująca";
  }

  if (status === "CONFIRMED") {
    return "Potwierdzona";
  }

  if (status === "CANCELLED") {
    return "Anulowana";
  }

  if (status === "COMPLETED") {
    return "Zakończona";
  }

  return status;
}

function getStatusClassName(status: string) {
  if (status === "PENDING") {
    return "bg-amber-50 text-amber-700 border-amber-200";
  }

  if (status === "CONFIRMED") {
    return "bg-green-50 text-green-700 border-green-200";
  }

  if (status === "CANCELLED") {
    return "bg-red-50 text-red-700 border-red-200";
  }

  if (status === "COMPLETED") {
    return "bg-zinc-100 text-zinc-700 border-zinc-200";
  }

  return "bg-zinc-100 text-zinc-700 border-zinc-200";
}

function getAlertClassName(type: string) {
  if (type === "warning") {
    return "border-amber-200 bg-amber-50";
  }

  if (type === "danger") {
    return "border-red-200 bg-red-50";
  }

  if (type === "success") {
    return "border-green-200 bg-green-50";
  }

  return "border-blue-200 bg-blue-50";
}

function getAlertIconClassName(type: string) {
  if (type === "warning") {
    return "bg-amber-100 text-amber-700";
  }

  if (type === "danger") {
    return "bg-red-100 text-red-700";
  }

  if (type === "success") {
    return "bg-green-100 text-green-700";
  }

  return "bg-blue-100 text-blue-700";
}

export default async function AdminPage() {
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const nextSevenDays = new Date(today);
  nextSevenDays.setDate(nextSevenDays.getDate() + 7);

  const [
    cabinsCount,
    activeCabinsCount,
    reservationsCount,
    pendingReservationsCount,
    confirmedReservationsCount,
    upcomingReservationsCount,
    guestsCount,
    inquiriesCount,
    newInquiriesCount,
    approvedInquiriesCount,
    archivedInquiriesCount,
    revenueSummary,
    upcomingReservations,
    nextSevenDaysReservations,
  ] = await Promise.all([
    prisma.cabin.count(),
    prisma.cabin.count({
      where: {
        isActive: true,
      },
    }),
    prisma.reservation.count(),
    prisma.reservation.count({
      where: {
        status: "PENDING",
      },
    }),
    prisma.reservation.count({
      where: {
        status: "CONFIRMED",
      },
    }),
    prisma.reservation.count({
      where: {
        startDate: {
          gte: today,
        },
        status: {
          in: ["PENDING", "CONFIRMED"],
        },
      },
    }),
    prisma.guest.count(),
    prisma.inquiry.count(),
    prisma.inquiry.count({
      where: {
        status: "NEW",
      },
    }),
    prisma.inquiry.count({
      where: {
        status: {
          in: ["APPROVED", "CONTACTED"],
        },
      },
    }),
    prisma.inquiry.count({
      where: {
        status: "ARCHIVED",
      },
    }),
    prisma.reservation.aggregate({
      _sum: {
        totalPrice: true,
        paidAmount: true,
      },
    }),
    prisma.reservation.findMany({
      where: {
        startDate: {
          gte: today,
        },
        status: {
          in: ["PENDING", "CONFIRMED"],
        },
      },
      orderBy: {
        startDate: "asc",
      },
      take: 5,
      select: {
        id: true,
        guestName: true,
        startDate: true,
        endDate: true,
        status: true,
        totalPrice: true,
        paidAmount: true,
        cabin: {
          select: {
            name: true,
          },
        },
      },
    }),
    prisma.reservation.findMany({
      where: {
        startDate: {
          gte: today,
          lt: nextSevenDays,
        },
        status: {
          in: ["PENDING", "CONFIRMED"],
        },
      },
      orderBy: {
        startDate: "asc",
      },
      select: {
        id: true,
        guestName: true,
        startDate: true,
        endDate: true,
        status: true,
        cabin: {
          select: {
            name: true,
          },
        },
      },
    }),
  ]);

  const totalRevenue = toNumber(revenueSummary._sum.totalPrice);
  const paidRevenue = toNumber(revenueSummary._sum.paidAmount);
  const unpaidRevenue = Math.max(totalRevenue - paidRevenue, 0);

  const mainCards = [
    {
      title: "Domki",
      value: cabinsCount,
      description: `Aktywne: ${activeCabinsCount}`,
      icon: Building2,
      href: "/admin/domki",
    },
    {
      title: "Rezerwacje",
      value: reservationsCount,
      description: `Nadchodzące: ${upcomingReservationsCount}`,
      icon: CalendarDays,
      href: "/admin/rezerwacje",
    },
    {
      title: "Goście",
      value: guestsCount,
      description: "Baza klientów",
      icon: Users,
      href: "/admin/goscie",
    },
    {
      title: "Zapytania WWW",
      value: inquiriesCount,
      description: `Nowe: ${newInquiriesCount}`,
      icon: Inbox,
      href: "/admin/zapytania",
    },
  ];

  const statusCards = [
    {
      title: "Oczekujące rezerwacje",
      value: pendingReservationsCount,
      description: "Rezerwacje do potwierdzenia",
      icon: AlertCircle,
      href: "/admin/rezerwacje?status=PENDING",
    },
    {
      title: "Potwierdzone rezerwacje",
      value: confirmedReservationsCount,
      description: "Aktywne potwierdzone pobyty",
      icon: CheckCircle2,
      href: "/admin/rezerwacje?status=CONFIRMED",
    },
    {
      title: "Nowe zapytania",
      value: newInquiriesCount,
      description: "Zapytania WWW do obsłużenia",
      icon: Inbox,
      href: "/admin/zapytania?status=NEW",
    },
    {
      title: "Zatwierdzone zapytania",
      value: approvedInquiriesCount,
      description: "Zapytania po utworzeniu rezerwacji lub ręcznym zatwierdzeniu",
      icon: CheckCircle2,
      href: "/admin/zapytania?status=APPROVED",
    },
    {
      title: "Archiwalne zapytania",
      value: archivedInquiriesCount,
      description: "Zapytania odłożone do archiwum",
      icon: Inbox,
      href: "/admin/zapytania?status=ARCHIVED",
    },
    {
      title: "Do zapłaty",
      value: formatCurrency(unpaidRevenue),
      description: "Różnica między wartością a wpłatami",
      icon: CircleDollarSign,
      href: "/admin/rezerwacje",
    },
  ];

  const quickActions = [
    {
      title: "Dodaj rezerwację",
      description: "Utwórz nową rezerwację ręcznie.",
      href: "/admin/rezerwacje/nowa",
      icon: CalendarCheck,
    },
    {
      title: "Sprawdź zapytania",
      description: "Obsłuż nowe zapytania ze strony WWW.",
      href: "/admin/zapytania",
      icon: Inbox,
    },
    {
      title: "Dodaj domek",
      description: "Dodaj nowy domek do oferty.",
      href: "/admin/domki/nowy",
      icon: BedDouble,
    },
    {
      title: "Otwórz kalendarz",
      description: "Sprawdź obłożenie domków.",
      href: "/admin/kalendarz",
      icon: CalendarClock,
    },
  ];

  const operationalAlerts = [
    ...(newInquiriesCount > 0
      ? [
          {
            type: "warning",
            title: "Nowe zapytania WWW do obsłużenia",
            description: `Masz ${newInquiriesCount} nowych zapytań ze strony publicznej. Warto je sprawdzić, odpisać gościom albo utworzyć rezerwacje.`,
            href: "/admin/zapytania?status=NEW",
            icon: Inbox,
          },
        ]
      : []),
    ...(pendingReservationsCount > 0
      ? [
          {
            type: "warning",
            title: "Rezerwacje oczekujące na potwierdzenie",
            description: `Masz ${pendingReservationsCount} rezerwacji ze statusem oczekującym. Warto je sprawdzić i potwierdzić albo anulować.`,
            href: "/admin/rezerwacje?status=PENDING",
            icon: AlertCircle,
          },
        ]
      : []),
    ...(unpaidRevenue > 0
      ? [
          {
            type: "warning",
            title: "Płatności do sprawdzenia",
            description: `W systemie pozostaje ${formatCurrency(
              unpaidRevenue,
            )} do rozliczenia względem zapisanych wartości rezerwacji.`,
            href: "/admin/rezerwacje",
            icon: CircleDollarSign,
          },
        ]
      : []),
    ...(nextSevenDaysReservations.length > 0
      ? [
          {
            type: "info",
            title: "Przyjazdy w najbliższych 7 dniach",
            description: `W najbliższym tygodniu zaczyna się ${nextSevenDaysReservations.length} rezerwacji. Sprawdź przygotowanie domków i płatności.`,
            href: "/admin/kalendarz",
            icon: CalendarClock,
          },
        ]
      : []),
    ...(activeCabinsCount === 0
      ? [
          {
            type: "danger",
            title: "Brak aktywnych domków",
            description:
              "Żaden domek nie jest obecnie aktywny. Sprawdź ustawienia domków, żeby oferta była dostępna w systemie.",
            href: "/admin/domki",
            icon: Building2,
          },
        ]
      : []),
  ];

  const visibleOperationalAlerts =
    operationalAlerts.length > 0
      ? operationalAlerts
      : [
          {
            type: "success",
            title: "Brak pilnych alertów",
            description:
              "Nie ma teraz pilnych spraw operacyjnych na dashboardzie. System wygląda spokojnie.",
            href: "/admin",
            icon: CheckCircle2,
          },
        ];

  return (
    <div className="space-y-8">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h1 className="text-3xl font-bold text-zinc-900">
            Dashboard
          </h1>

          <p className="mt-2 text-zinc-600">
            Szybki podgląd najważniejszych danych w systemie Domki Sztabinki
            PMS.
          </p>
        </div>

        <Link
          href="/admin/rezerwacje/nowa"
          className="inline-flex items-center justify-center gap-2 rounded-lg bg-green-700 px-4 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-green-800"
        >
          <CalendarCheck size={18} />
          Dodaj rezerwację
        </Link>
      </div>

      <section className="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        {mainCards.map((card) => {
          const Icon = card.icon;

          return (
            <Link
              key={card.title}
              href={card.href}
              className="rounded-xl border bg-white p-6 shadow-sm transition hover:border-green-200 hover:shadow-md"
            >
              <div className="flex items-start justify-between gap-4">
                <div>
                  <p className="text-sm font-medium text-zinc-500">
                    {card.title}
                  </p>

                  <h2 className="mt-2 text-3xl font-bold text-zinc-900">
                    {card.value}
                  </h2>

                  <p className="mt-2 text-sm text-zinc-500">
                    {card.description}
                  </p>
                </div>

                <div className="rounded-lg bg-green-50 p-3 text-green-700">
                  <Icon size={24} />
                </div>
              </div>
            </Link>
          );
        })}
      </section>

      <section className="grid gap-6 lg:grid-cols-3">
        {statusCards.map((card) => {
          const Icon = card.icon;

          return (
            <Link
              key={card.title}
              href={card.href}
              className="rounded-xl border bg-white p-5 shadow-sm transition hover:border-green-200 hover:shadow-md"
            >
              <div className="flex items-start gap-4">
                <div className="rounded-lg bg-zinc-100 p-3 text-zinc-700">
                  <Icon size={22} />
                </div>

                <div>
                  <p className="text-sm font-medium text-zinc-500">
                    {card.title}
                  </p>

                  <p className="mt-1 text-2xl font-bold text-zinc-900">
                    {card.value}
                  </p>

                  <p className="mt-1 text-sm text-zinc-500">
                    {card.description}
                  </p>
                </div>
              </div>
            </Link>
          );
        })}
      </section>

      <section className="rounded-xl border bg-white shadow-sm">
        <div className="border-b px-6 py-4">
          <h2 className="text-xl font-semibold text-zinc-900">
            Alerty operacyjne
          </h2>

          <p className="mt-1 text-sm text-zinc-500">
            Sprawy, które mogą wymagać uwagi przed kolejnymi przyjazdami.
          </p>
        </div>

        <div className="grid gap-4 p-6 xl:grid-cols-2">
          {visibleOperationalAlerts.map((alert) => {
            const Icon = alert.icon;

            return (
              <Link
                key={alert.title}
                href={alert.href}
                className={`rounded-xl border p-5 transition hover:shadow-md ${getAlertClassName(
                  alert.type,
                )}`}
              >
                <div className="flex items-start gap-4">
                  <div
                    className={`rounded-lg p-3 ${getAlertIconClassName(
                      alert.type,
                    )}`}
                  >
                    <Icon size={22} />
                  </div>

                  <div>
                    <h3 className="font-semibold text-zinc-900">
                      {alert.title}
                    </h3>

                    <p className="mt-2 text-sm leading-6 text-zinc-600">
                      {alert.description}
                    </p>
                  </div>
                </div>
              </Link>
            );
          })}
        </div>
      </section>

      <section className="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
        <div className="rounded-xl border bg-white shadow-sm">
          <div className="flex items-center justify-between border-b px-6 py-4">
            <div>
              <h2 className="text-xl font-semibold text-zinc-900">
                Najbliższe rezerwacje
              </h2>

              <p className="mt-1 text-sm text-zinc-500">
                Pierwsze 5 nadchodzących rezerwacji.
              </p>
            </div>

            <Link
              href="/admin/rezerwacje"
              className="inline-flex items-center gap-2 text-sm font-medium text-green-700 hover:text-green-800"
            >
              Wszystkie
              <ArrowRight size={16} />
            </Link>
          </div>

          <div className="divide-y">
            {upcomingReservations.length === 0 ? (
              <div className="p-6 text-sm text-zinc-500">
                Brak nadchodzących rezerwacji.
              </div>
            ) : (
              upcomingReservations.map((reservation) => {
                const totalPrice = toNumber(reservation.totalPrice);
                const paidAmount = toNumber(reservation.paidAmount);
                const amountLeft = Math.max(totalPrice - paidAmount, 0);

                return (
                  <Link
                    key={reservation.id}
                    href={`/admin/rezerwacje/${reservation.id}`}
                    className="block p-6 transition hover:bg-zinc-50"
                  >
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                      <div>
                        <div className="flex flex-wrap items-center gap-2">
                          <h3 className="font-semibold text-zinc-900">
                            {reservation.guestName}
                          </h3>

                          <span
                            className={`rounded-full border px-2.5 py-1 text-xs font-medium ${getStatusClassName(
                              reservation.status,
                            )}`}
                          >
                            {getStatusLabel(reservation.status)}
                          </span>
                        </div>

                        <p className="mt-1 text-sm text-zinc-500">
                          {reservation.cabin.name}
                        </p>

                        <p className="mt-1 text-sm text-zinc-500">
                          {formatDate(reservation.startDate)} -{" "}
                          {formatDate(reservation.endDate)}
                        </p>
                      </div>

                      <div className="text-left lg:text-right">
                        <p className="text-sm text-zinc-500">
                          Wartość
                        </p>

                        <p className="font-semibold text-zinc-900">
                          {formatCurrency(totalPrice)}
                        </p>

                        <p className="mt-1 text-sm text-zinc-500">
                          Pozostało: {formatCurrency(amountLeft)}
                        </p>
                      </div>
                    </div>
                  </Link>
                );
              })
            )}
          </div>
        </div>

        <div className="space-y-6">
          <div className="rounded-xl border bg-white shadow-sm">
            <div className="border-b px-6 py-4">
              <h2 className="text-xl font-semibold text-zinc-900">
                Najbliższe 7 dni
              </h2>

              <p className="mt-1 text-sm text-zinc-500">
                Rezerwacje zaczynające się w ciągu tygodnia.
              </p>
            </div>

            <div className="divide-y">
              {nextSevenDaysReservations.length === 0 ? (
                <div className="p-6 text-sm text-zinc-500">
                  Brak przyjazdów w najbliższych 7 dniach.
                </div>
              ) : (
                nextSevenDaysReservations.map((reservation) => (
                  <Link
                    key={reservation.id}
                    href={`/admin/rezerwacje/${reservation.id}`}
                    className="block px-6 py-4 transition hover:bg-zinc-50"
                  >
                    <div className="flex items-start justify-between gap-4">
                      <div>
                        <p className="font-medium text-zinc-900">
                          {reservation.guestName}
                        </p>

                        <p className="mt-1 text-sm text-zinc-500">
                          {reservation.cabin.name}
                        </p>
                      </div>

                      <div className="text-right text-sm text-zinc-500">
                        <p>{formatDate(reservation.startDate)}</p>
                        <p>{formatDate(reservation.endDate)}</p>
                      </div>
                    </div>
                  </Link>
                ))
              )}
            </div>
          </div>

          <div className="rounded-xl border bg-white shadow-sm">
            <div className="border-b px-6 py-4">
              <h2 className="text-xl font-semibold text-zinc-900">
                Szybkie akcje
              </h2>

              <p className="mt-1 text-sm text-zinc-500">
                Najczęściej używane działania.
              </p>
            </div>

            <div className="divide-y">
              {quickActions.map((action) => {
                const Icon = action.icon;

                return (
                  <Link
                    key={action.href}
                    href={action.href}
                    className="flex items-center gap-4 px-6 py-4 transition hover:bg-zinc-50"
                  >
                    <div className="rounded-lg bg-green-50 p-3 text-green-700">
                      <Icon size={20} />
                    </div>

                    <div className="flex-1">
                      <p className="font-medium text-zinc-900">
                        {action.title}
                      </p>

                      <p className="mt-1 text-sm text-zinc-500">
                        {action.description}
                      </p>
                    </div>

                    <ArrowRight size={18} className="text-zinc-400" />
                  </Link>
                );
              })}
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}