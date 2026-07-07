"use client";

import { FormEvent, useMemo, useState, useTransition } from "react";

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

function getReservationStatusLabel(status: string) {
  if (status === "PENDING") {
    return "wstępnie zajęty";
  }

  if (status === "CONFIRMED") {
    return "zajęty";
  }

  if (status === "COMPLETED") {
    return "zakończony pobyt";
  }

  return "zajęty";
}

function parseDateInputValue(value: string) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    return null;
  }

  const date = new Date(`${value}T12:00:00.000Z`);

  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return date;
}

function dateRangesOverlap({
  selectedDateFrom,
  selectedDateTo,
  occupiedDateFrom,
  occupiedDateTo,
}: {
  selectedDateFrom: Date;
  selectedDateTo: Date;
  occupiedDateFrom: Date;
  occupiedDateTo: Date;
}) {
  return selectedDateFrom < occupiedDateTo && selectedDateTo > occupiedDateFrom;
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

  const selectedCabinOccupiedDateRanges = useMemo(
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

  const selectedCabinName =
    cabins.find((cabin) => cabin.id === selectedCabinId)?.name || "";

  const collidingDateRanges = useMemo(() => {
    const selectedDateFrom = parseDateInputValue(dateFromValue);
    const selectedDateTo = parseDateInputValue(dateToValue);

    if (!selectedCabinId || !selectedDateFrom || !selectedDateTo) {
      return [];
    }

    if (selectedDateTo <= selectedDateFrom) {
      return [];
    }

    return selectedCabinOccupiedDateRanges.filter((dateRange) => {
      const occupiedDateFrom = new Date(dateRange.dateFrom);
      const occupiedDateTo = new Date(dateRange.dateTo);

      if (
        Number.isNaN(occupiedDateFrom.getTime()) ||
        Number.isNaN(occupiedDateTo.getTime())
      ) {
        return false;
      }

      return dateRangesOverlap({
        selectedDateFrom,
        selectedDateTo,
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

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const form = event.currentTarget;
    const formData = new FormData(form);

    const firstName = getStringValue(formData, "firstName");
    const lastName = getStringValue(formData, "lastName");
    const phone = getStringValue(formData, "phone");
    const email = getStringValue(formData, "email");
    const cabinId = getStringValue(formData, "cabinId");
    const cabinName =
      cabins.find((cabin) => cabin.id === cabinId)?.name || "";
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
      setIsSuccess(false);
      setMessage(
        "Uzupełnij imię, nazwisko, telefon oraz termin pobytu."
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
        setSelectedCabinId("");
        setDateFromValue("");
        setDateToValue("");
      }
    });
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="mt-10 rounded-[2rem] bg-white p-6 text-left text-slate-950 shadow-xl md:p-8"
    >
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

        <label className="grid gap-2 md:col-span-2">
          <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
            Domek
          </span>
          <select
            name="cabinId"
            value={selectedCabinId}
            onChange={(event) => setSelectedCabinId(event.target.value)}
            className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
          >
            <option value="">Dowolny / do ustalenia</option>
            {cabins.map((cabin) => (
              <option key={cabin.id} value={cabin.id}>
                {cabin.name}
              </option>
            ))}
          </select>
        </label>

        <div className="rounded-3xl border border-slate-200 bg-slate-50 p-5 md:col-span-2">
          <p className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
            Zajęte terminy
          </p>

          {!selectedCabinId ? (
            <p className="mt-3 text-sm leading-6 text-slate-600">
              Wybierz konkretny domek, aby zobaczyć terminy zajęte w systemie.
              Przy opcji dowolnej termin potwierdzimy po kontakcie.
            </p>
          ) : selectedCabinOccupiedDateRanges.length === 0 ? (
            <p className="mt-3 text-sm leading-6 text-emerald-700">
              Dla domku {selectedCabinName} nie ma obecnie zajętych terminów w
              systemie.
            </p>
          ) : (
            <div className="mt-4 grid gap-3">
              <p className="text-sm leading-6 text-slate-600">
                Poniżej widoczne są terminy zajęte dla domku {selectedCabinName}.
                Jeżeli wybierasz termin graniczny, ostateczną dostępność
                potwierdzimy po kontakcie.
              </p>

              <div className="grid gap-2">
                {selectedCabinOccupiedDateRanges.map((dateRange) => (
                  <div
                    key={dateRange.id}
                    className="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700"
                  >
                    <span className="font-black text-slate-950">
                      {formatDate(dateRange.dateFrom)} –{" "}
                      {formatDate(dateRange.dateTo)}
                    </span>{" "}
                    <span className="text-slate-500">
                      ({getReservationStatusLabel(dateRange.status)})
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

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

        {hasDateCollision ? (
          <div className="rounded-3xl border border-red-300 bg-red-50 p-5 text-sm text-red-900 md:col-span-2">
            <p className="font-black uppercase tracking-[0.14em]">
              Uwaga: wybrany termin nachodzi na zajęty pobyt
            </p>

            <p className="mt-3 leading-6">
              Wybrany termin dla domku {selectedCabinName} koliduje z terminem
              zapisanym w systemie. Możesz wysłać zapytanie, ale prawdopodobnie
              trzeba będzie zaproponować inny domek albo inny termin.
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
        ) : selectedCabinId && dateFromValue && dateToValue ? (
          <div className="rounded-3xl border border-emerald-300 bg-emerald-50 p-5 text-sm text-emerald-900 md:col-span-2">
            <p className="font-black uppercase tracking-[0.14em]">
              Brak kolizji z zajętymi terminami
            </p>
            <p className="mt-3 leading-6">
              Wybrany termin nie nachodzi na aktualnie zapisane rezerwacje tego
              domku. Ostateczną dostępność i cenę potwierdzimy po kontakcie.
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
              ? "mt-5 rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900"
              : "mt-5 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900"
          }
        >
          {message}
        </div>
      ) : null}

      <div className="mt-6 flex flex-col gap-3 sm:flex-row">
        <button
          type="submit"
          disabled={isPending}
          className="rounded-2xl bg-slate-950 px-7 py-4 text-sm font-black text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
        >
          {isPending ? "Wysyłanie zapytania..." : "Wyślij zapytanie"}
        </button>

        <a
          href={getPhoneHref(phoneNumber)}
          className="rounded-2xl border border-slate-300 px-7 py-4 text-center text-sm font-black text-slate-950 transition hover:bg-slate-50"
        >
          Zadzwoń: {phoneNumber}
        </a>
      </div>
    </form>
  );
}