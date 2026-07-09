"use client";

import { FormEvent, useEffect, useMemo, useState, useTransition } from "react";

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

type InquiryFormProps = {
  recipientEmail: string;
  phoneNumber: string;
  cabins: PublicCabinOption[];
  occupiedDateRanges: OccupiedDateRange[];
  minimumNightsLabel: string;
  checkInTime: string;
  checkOutTime: string;
};

type CabinAvailabilityStatus = "UNKNOWN" | "AVAILABLE" | "UNAVAILABLE";

const blockingReservationStatuses = ["PENDING", "CONFIRMED", "CHECKED_IN"];

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

function isBlockingReservationStatus(status: string) {
  return blockingReservationStatuses.includes(status);
}

function isValidDateInputValue(value: string) {
  return /^\d{4}-\d{2}-\d{2}$/.test(value);
}

function normalizeDateOnlyValue(value: string) {
  if (isValidDateInputValue(value)) {
    return value;
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return "";
  }

  return date.toISOString().slice(0, 10);
}

function getSelectedNights(dateFrom: string, dateTo: string) {
  if (!isValidDateInputValue(dateFrom) || !isValidDateInputValue(dateTo)) {
    return null;
  }

  const startDate = new Date(`${dateFrom}T00:00:00.000Z`);
  const endDate = new Date(`${dateTo}T00:00:00.000Z`);
  const millisecondsPerDay = 1000 * 60 * 60 * 24;
  const difference = endDate.getTime() - startDate.getTime();

  if (difference <= 0) {
    return null;
  }

  return Math.round(difference / millisecondsPerDay);
}

function dateRangesOverlap({
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

function getAvailabilityStatusLabel(status: CabinAvailabilityStatus) {
  if (status === "AVAILABLE") {
    return "Wolny w wybranym terminie";
  }

  if (status === "UNAVAILABLE") {
    return "Zajęty w wybranym terminie";
  }

  return "Wybierz daty, aby sprawdzić";
}

function getAvailabilityStatusClassName(status: CabinAvailabilityStatus) {
  if (status === "AVAILABLE") {
    return "bg-green-100 text-green-800";
  }

  if (status === "UNAVAILABLE") {
    return "bg-red-100 text-red-800";
  }

  return "bg-slate-100 text-slate-700";
}

function getCabinCardClassName({
  isSelected,
  status,
}: {
  isSelected: boolean;
  status: CabinAvailabilityStatus;
}) {
  if (isSelected) {
    return "border-slate-950 bg-slate-950 text-white";
  }

  if (status === "UNAVAILABLE") {
    return "border-red-200 bg-red-50 text-slate-500";
  }

  if (status === "AVAILABLE") {
    return "border-green-200 bg-green-50 text-slate-950 hover:border-green-500";
  }

  return "border-slate-200 bg-white text-slate-950 hover:border-slate-400";
}

function parsePeopleCount(value: string, fallback: number) {
  const parsedValue = Number.parseInt(value, 10);

  if (!Number.isInteger(parsedValue)) {
    return fallback;
  }

  return parsedValue;
}

export function InquiryForm({
  phoneNumber,
  cabins,
  occupiedDateRanges,
  minimumNightsLabel,
  checkInTime,
  checkOutTime,
}: InquiryFormProps) {
  const [message, setMessage] = useState("");
  const [isSuccess, setIsSuccess] = useState(false);
  const [isPending, startTransition] = useTransition();

  const [selectedCabinId, setSelectedCabinId] = useState("");
  const [dateFromValue, setDateFromValue] = useState("");
  const [dateToValue, setDateToValue] = useState("");
  const [adultsValue, setAdultsValue] = useState("2");
  const [childrenValue, setChildrenValue] = useState("0");

  const selectedNights = getSelectedNights(dateFromValue, dateToValue);
  const hasValidDateRange = selectedNights !== null;

  const adultsCount = parsePeopleCount(adultsValue, 1);
  const childrenCount = parsePeopleCount(childrenValue, 0);
  const guestsCount = Math.max(0, adultsCount + childrenCount);

  const cabinsWithAvailability = useMemo(
    () =>
      cabins.map((cabin) => {
        if (!hasValidDateRange) {
          return {
            ...cabin,
            availabilityStatus: "UNKNOWN" as CabinAvailabilityStatus,
          };
        }

        const hasConflict = occupiedDateRanges.some((range) => {
          if (range.cabinId !== cabin.id) {
            return false;
          }

          if (!isBlockingReservationStatus(range.status)) {
            return false;
          }

          const occupiedDateFrom = normalizeDateOnlyValue(range.dateFrom);
          const occupiedDateTo = normalizeDateOnlyValue(range.dateTo);

          if (!occupiedDateFrom || !occupiedDateTo) {
            return false;
          }

          return dateRangesOverlap({
            selectedDateFrom: dateFromValue,
            selectedDateTo: dateToValue,
            occupiedDateFrom,
            occupiedDateTo,
          });
        });

        return {
          ...cabin,
          availabilityStatus: hasConflict
            ? ("UNAVAILABLE" as CabinAvailabilityStatus)
            : ("AVAILABLE" as CabinAvailabilityStatus),
        };
      }),
    [cabins, dateFromValue, dateToValue, hasValidDateRange, occupiedDateRanges],
  );

  const availableCabinsCount = cabinsWithAvailability.filter(
    (cabin) => cabin.availabilityStatus === "AVAILABLE",
  ).length;

  const selectedCabin = cabinsWithAvailability.find(
    (cabin) => cabin.id === selectedCabinId,
  );

  const isSelectedCabinUnavailable =
    selectedCabin?.availabilityStatus === "UNAVAILABLE";

  useEffect(() => {
    if (selectedCabinId && isSelectedCabinUnavailable) {
      setSelectedCabinId("");
    }
  }, [isSelectedCabinUnavailable, selectedCabinId]);

  function resetAvailabilityFields() {
    setSelectedCabinId("");
    setDateFromValue("");
    setDateToValue("");
    setAdultsValue("2");
    setChildrenValue("0");
  }

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const form = event.currentTarget;
    const formData = new FormData(form);

    const firstName = getStringValue(formData, "firstName");
    const lastName = getStringValue(formData, "lastName");
    const phone = getStringValue(formData, "phone");
    const email = getStringValue(formData, "email");
    const cabinId = selectedCabinId;
    const cabinName =
      cabins.find((cabin) => cabin.id === cabinId)?.name || "";
    const dateFrom = dateFromValue;
    const dateTo = dateToValue;
    const adults = adultsValue;
    const children = childrenValue;
    const street = getStringValue(formData, "street");
    const postalCode = getStringValue(formData, "postalCode");
    const city = getStringValue(formData, "city");
    const country = getStringValue(formData, "country");
    const notes = getStringValue(formData, "notes");

    if (!firstName || !lastName || !phone || !dateFrom || !dateTo) {
      setIsSuccess(false);
      setMessage("Uzupełnij imię, nazwisko, telefon oraz termin pobytu.");
      return;
    }

    if (!hasValidDateRange) {
      setIsSuccess(false);
      setMessage("Data wyjazdu musi być późniejsza niż data przyjazdu.");
      return;
    }

    if (adultsCount < 1 || adultsCount > 20 || childrenCount < 0 || childrenCount > 20) {
      setIsSuccess(false);
      setMessage("Podaj poprawną liczbę dorosłych i dzieci.");
      return;
    }

    if (guestsCount < 1 || guestsCount > 20) {
      setIsSuccess(false);
      setMessage("Łączna liczba osób musi być od 1 do 20.");
      return;
    }

    if (isSelectedCabinUnavailable) {
      setIsSuccess(false);
      setMessage(
        "Wybrany domek jest zajęty w tym terminie. Wybierz inny domek albo opcję dowolną / do ustalenia.",
      );
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

      setIsSuccess(result.ok);
      setMessage(result.message);

      if (result.ok) {
        form.reset();
        resetAvailabilityFields();
      }
    });
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="mt-10 rounded-[2rem] bg-white p-6 text-left text-slate-950 shadow-xl md:p-8"
    >
      <section className="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
        <div>
          <p className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
            Sprawdź termin
          </p>

          <h3 className="mt-2 text-2xl font-black">
            Wybierz daty i zobacz wolne domki
          </h3>

          <p className="mt-2 text-sm leading-6 text-slate-600">
            System pokazuje dostępność na podstawie rezerwacji oczekujących,
            potwierdzonych i aktualnie zameldowanych. Można rozpocząć pobyt w
            dniu wymeldowania poprzednich gości.
          </p>
        </div>

        <div className="mt-5 grid gap-5 md:grid-cols-4">
          <label className="grid gap-2">
            <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Pobyt od
            </span>
            <input
              name="dateFrom"
              type="date"
              required
              value={dateFromValue}
              onChange={(event) => setDateFromValue(event.target.value)}
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
              onChange={(event) => setDateToValue(event.target.value)}
              className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
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
              value={adultsValue}
              onChange={(event) => setAdultsValue(event.target.value)}
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
              value={childrenValue}
              onChange={(event) => setChildrenValue(event.target.value)}
              className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
            />
          </label>
        </div>

        <div className="mt-5 grid gap-3 rounded-2xl bg-white p-4 text-sm text-slate-600 md:grid-cols-3">
          <div>
            <span className="font-black text-slate-950">Osoby: </span>
            {guestsCount}
          </div>

          <div>
            <span className="font-black text-slate-950">Noce: </span>
            {selectedNights ?? "—"}
          </div>

          <div>
            <span className="font-black text-slate-950">Wolne domki: </span>
            {hasValidDateRange ? availableCabinsCount : "—"}
          </div>
        </div>
      </section>

      <section className="mt-6 rounded-[1.5rem] border border-slate-200 p-5">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
              Domek
            </p>

            <h3 className="mt-2 text-2xl font-black">
              Wybierz dostępny domek
            </h3>
          </div>

          <button
            type="button"
            onClick={() => setSelectedCabinId("")}
            className="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-black transition hover:bg-slate-50"
          >
            Dowolny / do ustalenia
          </button>
        </div>

        <input type="hidden" name="cabinId" value={selectedCabinId} />

        <div className="mt-5 grid gap-3">
          <label
            className={`cursor-pointer rounded-2xl border p-4 transition ${getCabinCardClassName(
              {
                isSelected: selectedCabinId === "",
                status: "AVAILABLE",
              },
            )}`}
          >
            <input
              type="radio"
              name="cabinChoice"
              value=""
              checked={selectedCabinId === ""}
              onChange={() => setSelectedCabinId("")}
              className="sr-only"
            />

            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <div className="font-black">Dowolny / do ustalenia</div>
                <div
                  className={
                    selectedCabinId === ""
                      ? "mt-1 text-sm text-white/80"
                      : "mt-1 text-sm text-slate-500"
                  }
                >
                  Wyślij zapytanie bez wyboru konkretnego domku.
                </div>
              </div>

              <span
                className={
                  selectedCabinId === ""
                    ? "rounded-full bg-white/20 px-3 py-1 text-xs font-black text-white"
                    : "rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700"
                }
              >
                Bez wyboru
              </span>
            </div>
          </label>

          {cabinsWithAvailability.map((cabin) => {
            const isSelected = selectedCabinId === cabin.id;
            const isUnavailable = cabin.availabilityStatus === "UNAVAILABLE";

            return (
              <label
                key={cabin.id}
                className={`rounded-2xl border p-4 transition ${
                  isUnavailable
                    ? "cursor-not-allowed"
                    : "cursor-pointer"
                } ${getCabinCardClassName({
                  isSelected,
                  status: cabin.availabilityStatus,
                })}`}
              >
                <input
                  type="radio"
                  name="cabinChoice"
                  value={cabin.id}
                  checked={isSelected}
                  disabled={isUnavailable}
                  onChange={() => setSelectedCabinId(cabin.id)}
                  className="sr-only"
                />

                <div className="flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <div className="font-black">{cabin.name}</div>
                    <div
                      className={
                        isSelected
                          ? "mt-1 text-sm text-white/80"
                          : "mt-1 text-sm text-slate-500"
                      }
                    >
                      {hasValidDateRange
                        ? `Termin: ${dateFromValue} – ${dateToValue}`
                        : "Najpierw wybierz daty pobytu."}
                    </div>
                  </div>

                  <span
                    className={`rounded-full px-3 py-1 text-xs font-black ${
                      isSelected
                        ? "bg-white/20 text-white"
                        : getAvailabilityStatusClassName(
                            cabin.availabilityStatus,
                          )
                    }`}
                  >
                    {getAvailabilityStatusLabel(cabin.availabilityStatus)}
                  </span>
                </div>
              </label>
            );
          })}
        </div>
      </section>

      <section className="mt-6 grid gap-5 md:grid-cols-2">
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
      </section>

      <div className="mt-6 rounded-3xl bg-slate-50 p-5 text-sm leading-7 text-slate-600">
        Minimalny pobyt: <strong>{minimumNightsLabel}</strong>. Zameldowanie od{" "}
        <strong>{checkInTime}</strong>, wymeldowanie do{" "}
        <strong>{checkOutTime}</strong>. Ostateczną dostępność i cenę
        potwierdzamy po kontakcie.
      </div>

      {message ? (
        <div
          className={
            isSuccess
              ? "mt-6 rounded-3xl border border-green-200 bg-green-50 p-5 text-sm font-semibold leading-6 text-green-800"
              : "mt-6 rounded-3xl border border-red-200 bg-red-50 p-5 text-sm font-semibold leading-6 text-red-800"
          }
        >
          {message}
        </div>
      ) : null}

      <div className="mt-6 flex flex-col gap-3 sm:flex-row">
        <button
          type="submit"
          disabled={isPending}
          className={
            isPending
              ? "cursor-not-allowed rounded-2xl bg-slate-400 px-6 py-4 text-center text-sm font-black text-white"
              : "rounded-2xl bg-slate-950 px-6 py-4 text-center text-sm font-black text-white transition hover:bg-slate-800"
          }
        >
          {isPending ? "Wysyłanie..." : "Wyślij zapytanie"}
        </button>

        <a
          href={getPhoneHref(phoneNumber)}
          className="rounded-2xl border border-slate-300 px-6 py-4 text-center text-sm font-black text-slate-950 transition hover:bg-slate-50"
        >
          Zadzwoń: {phoneNumber}
        </a>
      </div>
    </form>
  );
}