"use client";

import { FormEvent, useMemo, useRef, useState, useTransition } from "react";

import { createPublicInquiry } from "@/actions/inquiries";

type PublicCabinOption = {
  id: string;
  name: string;
};

type OccupiedDateRange = {
  id: string;
  cabinId: string;
  dateFrom: string;
  dateTo: string;
  status: string;
};

type CalendarDay = {
  dateInputValue: string;
  dayNumber: number;
  isToday: boolean;
  isPast: boolean;
  isOccupied: boolean;
};

type CalendarMonth = {
  key: string;
  label: string;
  emptyDaysBeforeMonth: number[];
  days: CalendarDay[];
};

type InquiryFormProps = {
  recipientEmail: string;
  phoneNumber: string;
  cabins: PublicCabinOption[];
  occupiedDateRanges: OccupiedDateRange[];
  minimumNights: number;
  minimumNightsLabel: string;
  seasonStartMonth: number;
  seasonEndMonth: number;
  seasonLabel: string;
  checkInTime: string;
  checkOutTime: string;
};

const weekDayLabels = ["Pn", "Wt", "Śr", "Cz", "Pt", "So", "Nd"];

const blockingReservationStatuses = ["PENDING", "CONFIRMED", "CHECKED_IN"];

const millisecondsInDay = 24 * 60 * 60 * 1000;

function getStringValue(formData: FormData, key: string) {
  const value = formData.get(key);

  if (typeof value !== "string") {
    return "";
  }

  return value.trim();
}

function getPhoneHref(phone: string) {
  const normalizedPhone = phone.replace(/[^\d+]/g, "");

  if (normalizedPhone.startsWith("+")) {
    return `tel:${normalizedPhone}`;
  }

  if (normalizedPhone.length === 9) {
    return `tel:+48${normalizedPhone}`;
  }

  return `tel:${normalizedPhone}`;
}

function formatDate(value: string) {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return "";
  }

  return new Intl.DateTimeFormat("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    timeZone: "Europe/Warsaw",
  }).format(date);
}

function normalizeReservationStatus(status: string) {
  if (status === "COMPLETED") {
    return "CHECKED_OUT";
  }

  if (
    status === "PENDING" ||
    status === "CONFIRMED" ||
    status === "CHECKED_IN" ||
    status === "CHECKED_OUT" ||
    status === "CANCELLED"
  ) {
    return status;
  }

  return "PENDING";
}

function isBlockingReservationStatus(status: string) {
  return blockingReservationStatuses.includes(normalizeReservationStatus(status));
}

function getReservationStatusLabel(status: string) {
  const normalizedStatus = normalizeReservationStatus(status);

  if (normalizedStatus === "PENDING") {
    return "wstępnie zajęty";
  }

  if (normalizedStatus === "CONFIRMED") {
    return "zajęty";
  }

  if (normalizedStatus === "CHECKED_IN") {
    return "trwający pobyt";
  }

  if (normalizedStatus === "CHECKED_OUT") {
    return "zakończony pobyt";
  }

  if (normalizedStatus === "CANCELLED") {
    return "anulowany";
  }

  return "zajęty";
}

function formatNights(nights: number) {
  if (nights === 1) {
    return "1 noc";
  }

  if (nights >= 2 && nights <= 4) {
    return `${nights} noce`;
  }

  return `${nights} nocy`;
}

function parseDateInputValueToUtcDate(value: string) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    return null;
  }

  const date = new Date(`${value}T00:00:00.000Z`);

  if (Number.isNaN(date.getTime())) {
    return null;
  }

  if (date.toISOString().slice(0, 10) !== value) {
    return null;
  }

  return date;
}

function getStayNightsFromDateInputValues(
  dateFromValue: string,
  dateToValue: string,
) {
  const dateFrom = parseDateInputValueToUtcDate(dateFromValue);
  const dateTo = parseDateInputValueToUtcDate(dateToValue);

  if (!dateFrom || !dateTo) {
    return null;
  }

  return Math.round((dateTo.getTime() - dateFrom.getTime()) / millisecondsInDay);
}

function addUtcDays(date: Date, days: number) {
  const nextDate = new Date(date.getTime());
  nextDate.setUTCDate(nextDate.getUTCDate() + days);

  return nextDate;
}

function isMonthInSeason(
  month: number,
  seasonStartMonth: number,
  seasonEndMonth: number,
) {
  if (seasonStartMonth <= seasonEndMonth) {
    return month >= seasonStartMonth && month <= seasonEndMonth;
  }

  return month >= seasonStartMonth || month <= seasonEndMonth;
}

function isStayInsideSeason({
  dateFromValue,
  dateToValue,
  seasonStartMonth,
  seasonEndMonth,
}: {
  dateFromValue: string;
  dateToValue: string;
  seasonStartMonth: number;
  seasonEndMonth: number;
}) {
  const dateFrom = parseDateInputValueToUtcDate(dateFromValue);
  const dateTo = parseDateInputValueToUtcDate(dateToValue);

  if (!dateFrom || !dateTo) {
    return false;
  }

  for (
    let currentDate = new Date(dateFrom.getTime());
    currentDate < dateTo;
    currentDate = addUtcDays(currentDate, 1)
  ) {
    const currentMonth = currentDate.getUTCMonth() + 1;

    if (!isMonthInSeason(currentMonth, seasonStartMonth, seasonEndMonth)) {
      return false;
    }
  }

  return true;
}

function getTodayDateInputValue() {
  return getDateInputValueFromDate(new Date());
}

function getDateSelectionRuleMessage({
  dateFromValue,
  dateToValue,
  minimumNights,
  minimumNightsLabel,
  seasonStartMonth,
  seasonEndMonth,
  seasonLabel,
}: {
  dateFromValue: string;
  dateToValue: string;
  minimumNights: number;
  minimumNightsLabel: string;
  seasonStartMonth: number;
  seasonEndMonth: number;
  seasonLabel: string;
}) {
  if (!dateFromValue || !dateToValue) {
    return "";
  }

  if (dateFromValue < getTodayDateInputValue()) {
    return "Data przyjazdu nie może być wcześniejsza niż dzisiejsza data.";
  }

  const stayNights = getStayNightsFromDateInputValues(dateFromValue, dateToValue);

  if (stayNights === null) {
    return "Podaj poprawny termin pobytu.";
  }

  if (stayNights <= 0) {
    return "Data wyjazdu musi być późniejsza niż data przyjazdu.";
  }

  if (stayNights < minimumNights) {
    return `Minimalny pobyt to ${minimumNightsLabel}. Wybrany termin ma ${formatNights(
      stayNights,
    )}.`;
  }

  if (
    !isStayInsideSeason({
      dateFromValue,
      dateToValue,
      seasonStartMonth,
      seasonEndMonth,
    })
  ) {
    return `Wybrany termin wykracza poza sezon (${seasonLabel}). Wybierz termin w sezonie.`;
  }

  return "";
}

function dateInputRangesOverlap({
  selectedDateFrom,
  selectedDateTo,
  occupiedDateFrom,
  occupiedDateTo,
}: {
  selectedDateFrom: string;
  selectedDateTo: string;
  occupiedDateFrom: string;
  occupiedDateTo: string;
}) {
  return selectedDateFrom < occupiedDateTo && selectedDateTo > occupiedDateFrom;
}

function getDateInputValueFromDate(date: Date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

function getDateInputValueFromIso(value: string) {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return "";
  }

  return getDateInputValueFromDate(date);
}

function getMonthLabel(date: Date) {
  return new Intl.DateTimeFormat("pl-PL", {
    month: "long",
    year: "numeric",
  }).format(date);
}

function getMondayFirstWeekdayIndex(date: Date) {
  const day = date.getDay();

  if (day === 0) {
    return 6;
  }

  return day - 1;
}

function isDateOccupiedByRanges(
  dateInputValue: string,
  occupiedRanges: OccupiedDateRange[],
) {
  return occupiedRanges.some((dateRange) => {
    const occupiedDateFrom = getDateInputValueFromIso(dateRange.dateFrom);
    const occupiedDateTo = getDateInputValueFromIso(dateRange.dateTo);

    if (!occupiedDateFrom || !occupiedDateTo) {
      return false;
    }

    return dateInputValue >= occupiedDateFrom && dateInputValue < occupiedDateTo;
  });
}

function isDateCheckInBoundaryOfRanges(
  dateInputValue: string,
  occupiedRanges: OccupiedDateRange[],
) {
  return occupiedRanges.some((dateRange) => {
    const occupiedDateFrom = getDateInputValueFromIso(dateRange.dateFrom);

    if (!occupiedDateFrom) {
      return false;
    }

    return dateInputValue === occupiedDateFrom;
  });
}

function isDateCheckOutBoundaryOfRanges(
  dateInputValue: string,
  occupiedRanges: OccupiedDateRange[],
) {
  return occupiedRanges.some((dateRange) => {
    const occupiedDateTo = getDateInputValueFromIso(dateRange.dateTo);

    if (!occupiedDateTo) {
      return false;
    }

    return dateInputValue === occupiedDateTo;
  });
}

function isDateTurnoverBlockedDay({
  dateInputValue,
  allDateRanges,
  blockingDateRanges,
}: {
  dateInputValue: string;
  allDateRanges: OccupiedDateRange[];
  blockingDateRanges: OccupiedDateRange[];
}) {
  return (
    isDateCheckOutBoundaryOfRanges(dateInputValue, allDateRanges) &&
    isDateCheckInBoundaryOfRanges(dateInputValue, blockingDateRanges)
  );
}

function canUseOccupiedDayAsCheckout({
  day,
  dateFromValue,
  dateToValue,
  blockingDateRanges,
  allDateRanges,
}: {
  day: CalendarDay;
  dateFromValue: string;
  dateToValue: string;
  blockingDateRanges: OccupiedDateRange[];
  allDateRanges: OccupiedDateRange[];
}) {
  if (day.isPast) {
    return false;
  }

  if (!day.isOccupied) {
    return false;
  }

  if (
    isDateTurnoverBlockedDay({
      dateInputValue: day.dateInputValue,
      allDateRanges,
      blockingDateRanges,
    })
  ) {
    return false;
  }

  if (!dateFromValue || dateToValue) {
    return false;
  }

  if (day.dateInputValue <= dateFromValue) {
    return false;
  }

  return isDateCheckInBoundaryOfRanges(day.dateInputValue, blockingDateRanges);
}

function buildCalendarMonths({
  occupiedRanges,
  monthsCount,
}: {
  occupiedRanges: OccupiedDateRange[];
  monthsCount: number;
}): CalendarMonth[] {
  const today = new Date();
  const todayInputValue = getDateInputValueFromDate(today);
  const months: CalendarMonth[] = [];

  for (let monthOffset = 0; monthOffset < monthsCount; monthOffset += 1) {
    const monthDate = new Date(
      today.getFullYear(),
      today.getMonth() + monthOffset,
      1,
      12,
    );
    const year = monthDate.getFullYear();
    const month = monthDate.getMonth();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDayOfMonth = new Date(year, month, 1, 12);
    const emptyDaysBeforeMonth = Array.from({
      length: getMondayFirstWeekdayIndex(firstDayOfMonth),
    }).map((_, index) => index);

    const days = Array.from({ length: daysInMonth }).map((_, index) => {
      const dayNumber = index + 1;
      const date = new Date(year, month, dayNumber, 12);
      const dateInputValue = getDateInputValueFromDate(date);

      return {
        dateInputValue,
        dayNumber,
        isToday: dateInputValue === todayInputValue,
        isPast: dateInputValue < todayInputValue,
        isOccupied: isDateOccupiedByRanges(dateInputValue, occupiedRanges),
      };
    });

    months.push({
      key: `${year}-${String(month + 1).padStart(2, "0")}`,
      label: getMonthLabel(monthDate),
      emptyDaysBeforeMonth,
      days,
    });
  }

  return months;
}

function getCalendarDayClassName({
  isToday,
  isPast,
  isOccupied,
  isSelectedStart,
  isSelectedEnd,
  isSelectedBetween,
  isCheckInBoundary,
  isTurnoverBlockedDay,
}: {
  isToday: boolean;
  isPast: boolean;
  isOccupied: boolean;
  isSelectedStart: boolean;
  isSelectedEnd: boolean;
  isSelectedBetween: boolean;
  isCheckInBoundary: boolean;
  isTurnoverBlockedDay: boolean;
}) {
  if (isPast || isTurnoverBlockedDay) {
    return "flex h-9 cursor-not-allowed items-center justify-center rounded-xl border border-red-200 bg-red-50 text-xs font-black text-red-800";
  }

  if (isSelectedStart || isSelectedEnd) {
    return "flex h-9 items-center justify-center rounded-xl bg-slate-950 text-xs font-black text-white ring-2 ring-slate-400";
  }

  if (isSelectedBetween) {
    return "flex h-9 items-center justify-center rounded-xl border border-sky-200 bg-sky-50 text-xs font-black text-sky-800";
  }

  if (isOccupied && !isCheckInBoundary) {
    return "flex h-9 cursor-not-allowed items-center justify-center rounded-xl border border-red-200 bg-red-50 text-xs font-black text-red-800";
  }

  if (isToday) {
    return "flex h-9 items-center justify-center rounded-xl border border-emerald-400 bg-emerald-50 text-xs font-black text-emerald-900 ring-2 ring-emerald-200 transition hover:bg-emerald-100";
  }

  return "flex h-9 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 text-xs font-black text-emerald-800 transition hover:bg-emerald-100";
}

export function InquiryForm({
  phoneNumber,
  cabins,
  occupiedDateRanges,
  minimumNights,
  minimumNightsLabel,
  seasonStartMonth,
  seasonEndMonth,
  seasonLabel,
  checkInTime,
  checkOutTime,
}: InquiryFormProps) {
  const defaultCabinId = cabins[0]?.id ?? "";

  const formRef = useRef<HTMLFormElement | null>(null);
  const messageRef = useRef<HTMLDivElement | null>(null);

  const [message, setMessage] = useState("");
  const [isSuccess, setIsSuccess] = useState(false);
  const [isPending, startTransition] = useTransition();
  const [selectedCabinId, setSelectedCabinId] = useState(defaultCabinId);
  const [dateFromValue, setDateFromValue] = useState("");
  const [dateToValue, setDateToValue] = useState("");

  const selectedCabinAllDateRanges = useMemo(
    () =>
      occupiedDateRanges
        .filter((dateRange) => dateRange.cabinId === selectedCabinId)
        .sort(
          (firstDateRange, secondDateRange) =>
            new Date(firstDateRange.dateFrom).getTime() -
            new Date(secondDateRange.dateFrom).getTime(),
        ),
    [occupiedDateRanges, selectedCabinId],
  );

  const selectedCabinOccupiedDateRanges = useMemo(
    () =>
      selectedCabinAllDateRanges.filter((dateRange) =>
        isBlockingReservationStatus(dateRange.status),
      ),
    [selectedCabinAllDateRanges],
  );

  const selectedCabinName =
    cabins.find((cabin) => cabin.id === selectedCabinId)?.name || "";

  const selectedCabinDisplayName =
    selectedCabinName || "Dowolny domek / do ustalenia";

  const dateSelectionHint = dateFromValue
    ? dateToValue
      ? "Termin został wybrany. Teraz możesz wysłać zapytanie albo zmienić daty."
      : "Wybrano dzień przyjazdu. Teraz kliknij dzień wyjazdu."
    : "Najpierw kliknij wolny dzień przyjazdu w kalendarzu.";

  const availabilityCalendarMonths = useMemo(
    () =>
      buildCalendarMonths({
        occupiedRanges: selectedCabinOccupiedDateRanges,
        monthsCount: 6,
      }),
    [selectedCabinOccupiedDateRanges],
  );

  const dateSelectionRuleMessage = useMemo(
    () =>
      getDateSelectionRuleMessage({
        dateFromValue,
        dateToValue,
        minimumNights,
        minimumNightsLabel,
        seasonStartMonth,
        seasonEndMonth,
        seasonLabel,
      }),
    [
      dateFromValue,
      dateToValue,
      minimumNights,
      minimumNightsLabel,
      seasonStartMonth,
      seasonEndMonth,
      seasonLabel,
    ],
  );

  const collidingDateRanges = useMemo(() => {
    if (!selectedCabinId || !dateFromValue || !dateToValue) {
      return [];
    }

    if (dateToValue <= dateFromValue) {
      return [];
    }

    return selectedCabinOccupiedDateRanges.filter((dateRange) => {
      const occupiedDateFrom = getDateInputValueFromIso(dateRange.dateFrom);
      const occupiedDateTo = getDateInputValueFromIso(dateRange.dateTo);

      if (!occupiedDateFrom || !occupiedDateTo) {
        return false;
      }

      return dateInputRangesOverlap({
        selectedDateFrom: dateFromValue,
        selectedDateTo: dateToValue,
        occupiedDateFrom,
        occupiedDateTo,
      });
    });
  }, [
    dateFromValue,
    dateToValue,
    selectedCabinId,
    selectedCabinOccupiedDateRanges,
  ]);

  const hasDateCollision = collidingDateRanges.length > 0;
  const isSubmitDisabled =
    isPending ||
    hasDateCollision ||
    Boolean(dateSelectionRuleMessage) ||
    isSuccess;

  function scrollToMessage() {
    window.setTimeout(() => {
      messageRef.current?.scrollIntoView({
        behavior: "smooth",
        block: "center",
      });
    }, 0);
  }

  function showFormMessage({ ok, text }: { ok: boolean; text: string }) {
    setIsSuccess(ok);
    setMessage(text);
    scrollToMessage();
  }

  function clearFormMessage() {
    setIsSuccess(false);
    setMessage("");
  }

  function clearSelectedDates() {
    setDateFromValue("");
    setDateToValue("");
  }

  function showCheckInBoundaryStartMessage() {
    showFormMessage({
      ok: false,
      text: "Ten dzień jest dniem zameldowania innej rezerwacji. Może być wybrany jako dzień wyjazdu, ale nie jako dzień rozpoczęcia nowego pobytu.",
    });
  }

  function handlePrepareNextInquiry() {
    formRef.current?.reset();
    setSelectedCabinId(defaultCabinId);
    clearSelectedDates();
    clearFormMessage();
  }

  function handleCabinChange(nextCabinId: string) {
    clearFormMessage();
    setSelectedCabinId(nextCabinId);
    clearSelectedDates();
  }

  function handleCalendarDayClick(day: CalendarDay) {
    const isCheckInBoundary = isDateCheckInBoundaryOfRanges(
      day.dateInputValue,
      selectedCabinOccupiedDateRanges,
    );

    const isTurnoverBlockedDay = isDateTurnoverBlockedDay({
      dateInputValue: day.dateInputValue,
      allDateRanges: selectedCabinAllDateRanges,
      blockingDateRanges: selectedCabinOccupiedDateRanges,
    });

    const isCheckoutBoundaryAvailable = canUseOccupiedDayAsCheckout({
      day,
      dateFromValue,
      dateToValue,
      allDateRanges: selectedCabinAllDateRanges,
      blockingDateRanges: selectedCabinOccupiedDateRanges,
    });

    if (day.isPast || isTurnoverBlockedDay) {
      return;
    }

    if (day.isOccupied && !isCheckInBoundary) {
      return;
    }

    if (!dateFromValue || (dateFromValue && dateToValue)) {
      if (isCheckInBoundary) {
        showCheckInBoundaryStartMessage();
        return;
      }

      clearFormMessage();
      setDateFromValue(day.dateInputValue);
      setDateToValue("");
      return;
    }

    if (day.dateInputValue <= dateFromValue) {
      if (isCheckInBoundary) {
        showCheckInBoundaryStartMessage();
        return;
      }

      clearFormMessage();
      setDateFromValue(day.dateInputValue);
      setDateToValue("");
      return;
    }

    if (day.isOccupied && !isCheckoutBoundaryAvailable) {
      return;
    }

    clearFormMessage();
    setDateToValue(day.dateInputValue);
  }

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (isSuccess) {
      return;
    }

    const form = event.currentTarget;
    const formData = new FormData(form);

    const firstName = getStringValue(formData, "firstName");
    const lastName = getStringValue(formData, "lastName");
    const phone = getStringValue(formData, "phone");
    const email = getStringValue(formData, "email");
    const cabinId = getStringValue(formData, "cabinId");
    const cabinName = cabins.find((cabin) => cabin.id === cabinId)?.name || "";
    const dateFrom = getStringValue(formData, "dateFrom");
    const dateTo = getStringValue(formData, "dateTo");
    const adults = getStringValue(formData, "adults");
    const children = getStringValue(formData, "children");
    const street = getStringValue(formData, "street");
    const postalCode = getStringValue(formData, "postalCode");
    const city = getStringValue(formData, "city");
    const country = getStringValue(formData, "country");
    const notes = getStringValue(formData, "notes");

    if (!firstName || !lastName || !phone || !dateFrom || !dateTo) {
      showFormMessage({
        ok: false,
        text: "Uzupełnij imię, nazwisko, telefon oraz termin pobytu.",
      });
      return;
    }

    if (dateSelectionRuleMessage) {
      showFormMessage({
        ok: false,
        text: dateSelectionRuleMessage,
      });
      return;
    }

    if (hasDateCollision) {
      showFormMessage({
        ok: false,
        text: "Wybrany termin jest zajęty dla tego domku. Wybierz inny termin, inny domek albo opcję dowolną / do ustalenia.",
      });
      return;
    }

    startTransition(async () => {
      const result = await createPublicInquiry({
        firstName,
        lastName,
        phone,
        email,
        cabinId,
        cabinName,
        dateFrom,
        dateTo,
        adults,
        children,
        street,
        postalCode,
        city,
        country,
        notes,
      });

      showFormMessage({
        ok: result.ok,
        text: result.message,
      });

      if (result.ok) {
        form.reset();
        setSelectedCabinId(defaultCabinId);
        clearSelectedDates();
      }
    });
  }

  return (
    <form
      ref={formRef}
      onSubmit={handleSubmit}
      className="mt-10 grid gap-8 rounded-[2rem] bg-white p-6 text-left text-slate-950 shadow-xl lg:grid-cols-[0.9fr_1.1fr] md:p-8"
    >
      <div className="space-y-5">
        <div className="rounded-3xl border border-slate-200 bg-slate-50 p-5">
          <p className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
            Dane zapytania
          </p>
          <p className="mt-2 text-sm leading-6 text-slate-600">
            Uzupełnij dane kontaktowe i termin pobytu. Dostępność wybranego
            domku sprawdzisz w kalendarzu po prawej stronie.
          </p>
        </div>

        <div className="grid gap-5 md:grid-cols-2">
          <label className="grid gap-2">
            <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Imię
            </span>
            <input
              name="firstName"
              type="text"
              required
              className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
              placeholder="Jan"
            />
          </label>

          <label className="grid gap-2">
            <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Nazwisko
            </span>
            <input
              name="lastName"
              type="text"
              required
              className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
              placeholder="Kowalski"
            />
          </label>

          <label className="grid gap-2">
            <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Telefon
            </span>
            <input
              name="phone"
              type="tel"
              required
              className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
              placeholder="502 286 724"
            />
          </label>

          <label className="grid gap-2">
            <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              E-mail
            </span>
            <input
              name="email"
              type="email"
              className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
              placeholder="adres@email.com"
            />
          </label>

          <label className="grid gap-2">
            <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Dorośli
            </span>
            <input
              name="adults"
              type="number"
              required
              min={1}
              max={20}
              defaultValue={2}
              className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
            />
          </label>

          <label className="grid gap-2">
            <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Dzieci
            </span>
            <input
              name="children"
              type="number"
              min={0}
              max={20}
              defaultValue={0}
              className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
            />
          </label>

          <label className="grid gap-2">
            <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Pobyt od
            </span>
            <input
              name="dateFrom"
              type="date"
              required
              value={dateFromValue}
              onChange={(event) => {
                clearFormMessage();
                setDateFromValue(event.target.value);
              }}
              className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
            />
          </label>

          <label className="grid gap-2">
            <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Pobyt do
            </span>
            <input
              name="dateTo"
              type="date"
              required
              value={dateToValue}
              onChange={(event) => {
                clearFormMessage();
                setDateToValue(event.target.value);
              }}
              className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
            />
          </label>

          {hasDateCollision ? (
            <div className="rounded-3xl border border-red-300 bg-red-50 p-5 text-sm text-red-900 md:col-span-2">
              <p className="font-black uppercase tracking-[0.14em]">
                Uwaga: wybrany termin jest zajęty
              </p>

              <p className="mt-3 leading-6">
                Wybrany termin dla domku {selectedCabinName} koliduje z
                terminem zapisanym w systemie. Wybierz inny termin, inny domek
                albo opcję dowolną / do ustalenia.
              </p>

              <div className="mt-4 grid gap-2">
                {collidingDateRanges.map((dateRange) => (
                  <div
                    key={dateRange.id}
                    className="rounded-2xl border border-red-200 bg-white px-4 py-3"
                  >
                    <span className="font-black">
                      {formatDate(dateRange.dateFrom)} –{" "}
                      {formatDate(dateRange.dateTo)}
                    </span>{" "}
                    <span>
                      ({getReservationStatusLabel(dateRange.status)})
                    </span>
                  </div>
                ))}
              </div>
            </div>
          ) : dateSelectionRuleMessage ? (
            <div className="rounded-3xl border border-amber-300 bg-amber-50 p-5 text-sm text-amber-950 md:col-span-2">
              <p className="font-black uppercase tracking-[0.14em]">
                Termin nie spełnia zasad pobytu
              </p>
              <p className="mt-3 leading-6">{dateSelectionRuleMessage}</p>
            </div>
          ) : selectedCabinId && dateFromValue && dateToValue ? (
            <div className="rounded-3xl border border-emerald-300 bg-emerald-50 p-5 text-sm text-emerald-900 md:col-span-2">
              <p className="font-black uppercase tracking-[0.14em]">
                Brak kolizji z zajętymi terminami
              </p>
              <p className="mt-3 leading-6">
                Wybrany termin spełnia podstawowe zasady pobytu i nie nachodzi
                na aktualnie zapisane rezerwacje domku{" "}
                <strong>{selectedCabinDisplayName}</strong>. Ostateczną
                dostępność i cenę potwierdzimy po kontakcie.
              </p>
            </div>
          ) : null}

          <label className="grid gap-2 md:col-span-2">
            <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Ulica i numer
            </span>
            <input
              name="street"
              type="text"
              className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
              placeholder="Leśna 23"
            />
          </label>

          <label className="grid gap-2">
            <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Kod pocztowy
            </span>
            <input
              name="postalCode"
              type="text"
              className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
              placeholder="16-500"
            />
          </label>

          <label className="grid gap-2">
            <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Miasto
            </span>
            <input
              name="city"
              type="text"
              className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
              placeholder="Sejny"
            />
          </label>

          <label className="grid gap-2 md:col-span-2">
            <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Kraj
            </span>
            <input
              name="country"
              type="text"
              defaultValue="Polska"
              className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
              placeholder="Polska"
            />
          </label>

          <label className="grid gap-2 md:col-span-2">
            <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Wiadomość
            </span>
            <textarea
              name="notes"
              rows={5}
              className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
              placeholder="Napisz dodatkowe informacje, np. przyjazd z dziećmi, pytanie o późniejsze wymeldowanie albo konkretny domek."
            />
          </label>
        </div>

        <div className="rounded-3xl bg-slate-50 p-5 text-sm leading-7 text-slate-600">
          Minimalny pobyt: <strong>{minimumNightsLabel}</strong>. Sezon:{" "}
          <strong>{seasonLabel}</strong>. Zameldowanie od{" "}
          <strong>{checkInTime}</strong>, wymeldowanie do{" "}
          <strong>{checkOutTime}</strong>. Ostateczną dostępność i cenę
          potwierdzamy po kontakcie.
        </div>

        {message ? (
          <div
            ref={messageRef}
            role={isSuccess ? "status" : "alert"}
            className={
              isSuccess
                ? "rounded-3xl border border-emerald-300 bg-emerald-50 p-5 text-sm text-emerald-950"
                : "rounded-3xl border border-amber-300 bg-amber-50 p-5 text-sm text-amber-950"
            }
          >
            {isSuccess ? (
              <div className="space-y-3">
                <p className="font-black uppercase tracking-[0.14em]">
                  Zapytanie zostało wysłane
                </p>
                <p className="leading-6">{message}</p>
                <p className="leading-6">
                  To jeszcze nie jest potwierdzona rezerwacja. Termin i cenę
                  potwierdzimy po kontakcie telefonicznym lub mailowym.
                </p>
              </div>
            ) : (
              <p className="font-semibold leading-6">{message}</p>
            )}
          </div>
        ) : null}

        <div className="flex flex-col gap-3 sm:flex-row">
          <button
            type="submit"
            disabled={isSubmitDisabled}
            className={
              isSubmitDisabled
                ? "cursor-not-allowed rounded-2xl bg-slate-400 px-7 py-4 text-sm font-black text-white"
                : "rounded-2xl bg-slate-950 px-7 py-4 text-sm font-black text-white transition hover:bg-slate-800"
            }
          >
            {isPending
              ? "Wysyłanie zapytania..."
              : isSuccess
                ? "Zapytanie zapisane"
                : hasDateCollision
                  ? "Termin zajęty — wybierz inny"
                  : dateSelectionRuleMessage
                    ? "Termin nie spełnia zasad"
                    : "Wyślij zapytanie"}
          </button>

          {isSuccess ? (
            <button
              type="button"
              onClick={handlePrepareNextInquiry}
              className="rounded-2xl border border-slate-300 px-7 py-4 text-center text-sm font-black text-slate-950 transition hover:bg-slate-50"
            >
              Wypełnij kolejne zapytanie
            </button>
          ) : null}

          <a
            href={getPhoneHref(phoneNumber)}
            className="rounded-2xl border border-slate-300 px-7 py-4 text-center text-sm font-black text-slate-950 transition hover:bg-slate-50"
          >
            Zadzwoń: {phoneNumber}
          </a>
        </div>
      </div>

      <aside className="space-y-5">
        <div className="rounded-3xl border border-slate-200 bg-slate-50 p-5">
          <label className="grid gap-2">
            <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Wybierz domek
            </span>
            <select
              name="cabinId"
              value={selectedCabinId}
              onChange={(event) => handleCabinChange(event.target.value)}
              className="rounded-2xl border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-slate-950"
            >
              {cabins.map((cabin) => (
                <option key={cabin.id} value={cabin.id}>
                  {cabin.name}
                </option>
              ))}
              <option value="">Dowolny domek / proszę dopasować</option>
            </select>
          </label>

          <div className="mt-4 rounded-2xl border border-slate-200 bg-white p-4 text-sm leading-6 text-slate-700">
            <p>
              Aktualnie sprawdzasz:{" "}
              <strong>{selectedCabinDisplayName}</strong>.
            </p>

            {selectedCabinId ? (
              <p className="mt-2">
                Po zmianie domku wybrane daty zostaną wyczyszczone, żeby nie
                pomylić dostępności między domkami.
              </p>
            ) : (
              <p className="mt-2">
                Przy opcji dowolnej nie pokazujemy kalendarza jednego domku.
                Dopasujemy najlepszy wolny domek po kontakcie.
              </p>
            )}
          </div>
        </div>

        {selectedCabinId ? (
          <div className="rounded-3xl border border-slate-200 bg-white p-5">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
              <div>
                <p className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
                  Kalendarz dostępności
                </p>
                <h3 className="mt-2 text-2xl font-black text-slate-950">
                  {selectedCabinDisplayName}
                </h3>
                <p className="mt-2 text-sm leading-6 text-slate-600">
                  {dateSelectionHint}
                </p>
                <p className="mt-2 text-sm leading-6 text-slate-600">
                  Dzień zameldowania kolejnych gości wygląda jak wolny, ale nie
                  może być początkiem nowego pobytu. Jeżeli tego samego dnia
                  jest wymeldowanie i zameldowanie, dzień jest zajęty.
                </p>
              </div>

              <div className="flex flex-wrap gap-2 text-xs font-bold">
                <span className="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-emerald-800">
                  wolny
                </span>
                <span className="rounded-full border border-red-200 bg-red-50 px-3 py-1 text-red-800">
                  zajęty / miniony
                </span>
                <span className="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-sky-800">
                  wybrany zakres
                </span>
              </div>
            </div>

            {dateFromValue ? (
              <div className="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700">
                <strong>Wybrany termin:</strong>{" "}
                {formatDate(`${dateFromValue}T12:00:00.000Z`)}
                {dateToValue
                  ? ` – ${formatDate(`${dateToValue}T12:00:00.000Z`)}`
                  : " – kliknij datę wyjazdu"}
              </div>
            ) : null}

            <div className="mt-6 grid gap-5">
              {availabilityCalendarMonths.map((calendarMonth) => (
                <div
                  key={calendarMonth.key}
                  className="rounded-3xl border border-slate-200 bg-slate-50 p-4"
                >
                  <p className="text-center text-sm font-black uppercase tracking-[0.16em] text-slate-700">
                    {calendarMonth.label}
                  </p>

                  <div className="mt-4 grid grid-cols-7 gap-1 text-center">
                    {weekDayLabels.map((weekDayLabel) => (
                      <div
                        key={weekDayLabel}
                        className="py-1 text-xs font-black uppercase text-slate-500"
                      >
                        {weekDayLabel}
                      </div>
                    ))}

                    {calendarMonth.emptyDaysBeforeMonth.map((emptyDay) => (
                      <div key={emptyDay} className="h-9" />
                    ))}

                    {calendarMonth.days.map((day) => {
                      const isSelectedStart =
                        day.dateInputValue === dateFromValue;
                      const isSelectedEnd = day.dateInputValue === dateToValue;
                      const isSelectedBetween =
                        Boolean(dateFromValue) &&
                        Boolean(dateToValue) &&
                        day.dateInputValue > dateFromValue &&
                        day.dateInputValue < dateToValue;
                      const isCheckInBoundary = isDateCheckInBoundaryOfRanges(
                        day.dateInputValue,
                        selectedCabinOccupiedDateRanges,
                      );
                      const isTurnoverBlockedDay = isDateTurnoverBlockedDay({
                        dateInputValue: day.dateInputValue,
                        allDateRanges: selectedCabinAllDateRanges,
                        blockingDateRanges: selectedCabinOccupiedDateRanges,
                      });
                      const isDayDisabled =
                        day.isPast ||
                        isTurnoverBlockedDay ||
                        (day.isOccupied && !isCheckInBoundary);

                      return (
                        <button
                          key={day.dateInputValue}
                          type="button"
                          disabled={isDayDisabled}
                          title={
                            day.isPast
                              ? `${day.dateInputValue} — data miniona`
                              : isTurnoverBlockedDay
                                ? `${day.dateInputValue} — zajęty: wymeldowanie i zameldowanie tego samego dnia`
                                : day.isOccupied && !isCheckInBoundary
                                  ? `${day.dateInputValue} — zajęty`
                                  : isCheckInBoundary
                                    ? `${day.dateInputValue} — możliwy tylko jako dzień wyjazdu`
                                    : `${day.dateInputValue} — wolny`
                          }
                          onClick={() => handleCalendarDayClick(day)}
                          className={getCalendarDayClassName({
                            isToday: day.isToday,
                            isPast: day.isPast,
                            isOccupied: day.isOccupied,
                            isSelectedStart,
                            isSelectedEnd,
                            isSelectedBetween,
                            isCheckInBoundary,
                            isTurnoverBlockedDay,
                          })}
                        >
                          {day.dayNumber}
                        </button>
                      );
                    })}
                  </div>
                </div>
              ))}
            </div>
          </div>
        ) : (
          <div className="rounded-3xl border border-slate-200 bg-white p-5">
            <p className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Kalendarz dostępności
            </p>
            <h3 className="mt-2 text-2xl font-black text-slate-950">
              Dowolny domek / do ustalenia
            </h3>
            <p className="mt-3 text-sm leading-6 text-slate-600">
              W tej opcji nie wybierasz konkretnego domku. Wyślij zapytanie, a
              my sprawdzimy, który domek najlepiej pasuje do terminu i liczby
              osób.
            </p>
          </div>
        )}
      </aside>
    </form>
  );
}