"use client";

import { FormEvent, useState, useTransition } from "react";

import { createPublicInquiry } from "@/actions/inquiries";

type PublicCabinOption = {
  id: string;
  name: string;
};

type InquiryFormProps = {
  recipientEmail: string;
  phoneNumber: string;
  cabins: PublicCabinOption[];
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

export function InquiryForm({
  phoneNumber,
  cabins,
  minimumNightsLabel,
  checkInTime,
  checkOutTime,
}: InquiryFormProps) {
  const [message, setMessage] = useState("");
  const [isSuccess, setIsSuccess] = useState(false);
  const [isPending, startTransition] = useTransition();

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const form = event.currentTarget;
    const formData = new FormData(form);

    const fullName = getStringValue(formData, "fullName");
    const phone = getStringValue(formData, "phone");
    const email = getStringValue(formData, "email");
    const cabinId = getStringValue(formData, "cabinId");
    const cabinName =
      cabins.find((cabin) => cabin.id === cabinId)?.name || "";
    const dateFrom = getStringValue(formData, "dateFrom");
    const dateTo = getStringValue(formData, "dateTo");
    const guests = getStringValue(formData, "guests");
    const notes = getStringValue(formData, "notes");

    if (!fullName || !phone || !dateFrom || !dateTo || !guests) {
      setIsSuccess(false);
      setMessage(
        "Uzupełnij imię i nazwisko, telefon, termin pobytu oraz liczbę osób."
      );
      return;
    }

    startTransition(async () => {
      const result = await createPublicInquiry({
        fullName,
        phone,
        email,
        cabinId,
        cabinName,
        dateFrom,
        dateTo,
        guests,
        notes,
      });

      setIsSuccess(result.ok);
      setMessage(result.message);

      if (result.ok) {
        form.reset();
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
            Imię i nazwisko
          </span>
          <input
            name="fullName"
            type="text"
            required
            className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
            placeholder="Jan Kowalski"
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
            Domek
          </span>
          <select
            name="cabinId"
            className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
            defaultValue=""
          >
            <option value="">Dowolny / do ustalenia</option>
            {cabins.map((cabin) => (
              <option key={cabin.id} value={cabin.id}>
                {cabin.name}
              </option>
            ))}
          </select>
        </label>

        <label className="grid gap-2">
          <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
            Pobyt od
          </span>
          <input
            name="dateFrom"
            type="date"
            required
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
            className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
          />
        </label>

        <label className="grid gap-2 md:col-span-2">
          <span className="text-sm font-black uppercase tracking-[0.18em] text-slate-500">
            Liczba osób
          </span>
          <input
            name="guests"
            type="number"
            required
            min={1}
            max={20}
            className="rounded-2xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-950"
            placeholder="np. 4"
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