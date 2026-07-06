"use client";

import Link from "next/link";

type MonthNavigationProps = {
  year: number;
  month: number;
};

function getCalendarUrl(year: number, month: number) {
  return `/admin/kalendarz?year=${year}&month=${month + 1}`;
}

function getPreviousMonth(year: number, month: number) {
  if (month === 0) {
    return {
      year: year - 1,
      month: 11,
    };
  }

  return {
    year,
    month: month - 1,
  };
}

function getNextMonth(year: number, month: number) {
  if (month === 11) {
    return {
      year: year + 1,
      month: 0,
    };
  }

  return {
    year,
    month: month + 1,
  };
}

export default function MonthNavigation({
  year,
  month,
}: MonthNavigationProps) {
  const previousMonth = getPreviousMonth(year, month);
  const nextMonth = getNextMonth(year, month);

  const today = new Date();

  return (
    <div className="flex flex-wrap items-center gap-3">
      <Link
        href={getCalendarUrl(previousMonth.year, previousMonth.month)}
        className="flex h-10 w-10 items-center justify-center rounded-lg border bg-white text-lg font-semibold hover:bg-zinc-50"
        aria-label="Poprzedni miesiąc"
      >
        ←
      </Link>

      <Link
        href={getCalendarUrl(today.getFullYear(), today.getMonth())}
        className="rounded-lg border bg-white px-4 py-2 text-sm font-semibold hover:bg-zinc-50"
      >
        Dziś
      </Link>

      <Link
        href={getCalendarUrl(nextMonth.year, nextMonth.month)}
        className="flex h-10 w-10 items-center justify-center rounded-lg border bg-white text-lg font-semibold hover:bg-zinc-50"
        aria-label="Następny miesiąc"
      >
        →
      </Link>
    </div>
  );
}