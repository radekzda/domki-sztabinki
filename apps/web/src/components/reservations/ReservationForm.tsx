"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";

import { createReservation } from "@/actions/reservations";
import {
  calculateDefaultTotalPrice,
  calculateReservationNightsFromDateValues,
  getDefaultNightPrice,
} from "@/modules/pricing/pricing.utils";

type CabinOption = {
  id: string;
  name: string;
  maxGuests: number;
  pricePerNight: number;
  priceOneNight: number;
  priceTwoNights: number;
  priceThreeNights: number;
  priceFourNights: number;
  priceFiveNights: number;
  priceSixNights: number;
  priceSevenPlusNights: number;
};

type ReservationFormProps = {
  cabins: CabinOption[];
  initialGuestId?: string;
  initialCabinId?: string;
  initialStartDate?: string;
  initialEndDate?: string;
  initialFirstName?: string;
  initialLastName?: string;
  initialEmail?: string;
  initialPhone?: string;
  initialAdults?: string;
  initialChildren?: string;
  initialSource?: string;
  initialStreet?: string;
  initialPostalCode?: string;
  initialCity?: string;
  initialCountry?: string;
  initialNotes?: string;
  initialCheckInTime?: string;
  initialCheckOutTime?: string;
  minimumNights?: number;
};

const statuses = [
  { value: "PENDING", label: "Oczekująca" },
  { value: "CONFIRMED", label: "Potwierdzona" },
  { value: "CANCELLED", label: "Anulowana" },
  { value: "COMPLETED", label: "Zakończona" },
];

const sources = [
  { value: "MANUAL", label: "Ręcznie" },
  { value: "PHONE", label: "Telefon" },
  { value: "WEBSITE", label: "WWW" },
  { value: "BOOKING", label: "Booking" },
  { value: "AIRBNB", label: "Airbnb" },
];

function formatPrice(value: number | null) {
  if (value === null) {
    return "—";
  }

  return `${value} zł`;
}

export default function ReservationForm({
  cabins,
  initialGuestId = "",
  initialCabinId = "",
  initialStartDate = "",
  initialEndDate = "",
  initialFirstName = "",
  initialLastName = "",
  initialEmail = "",
  initialPhone = "",
  initialAdults = "2",
  initialChildren = "0",
  initialSource = "MANUAL",
  initialStreet = "",
  initialPostalCode = "",
  initialCity = "",
  initialCountry = "Polska",
  initialNotes = "",
  initialCheckInTime = "15:00",
  initialCheckOutTime = "11:00",
  minimumNights = 4,
}: ReservationFormProps) {
  const [selectedCabinId, setSelectedCabinId] = useState(initialCabinId);
  const [startDateValue, setStartDateValue] = useState(initialStartDate);
  const [endDateValue, setEndDateValue] = useState(initialEndDate);
  const [totalPriceValue, setTotalPriceValue] = useState("");
  const [paidAmountValue, setPaidAmountValue] = useState("0");

  const selectedCabin = useMemo(
    () => cabins.find((cabin) => cabin.id === selectedCabinId) ?? null,
    [cabins, selectedCabinId],
  );

  const nights = useMemo(
    () =>
      calculateReservationNightsFromDateValues(
        startDateValue,
        endDateValue,
      ),
    [startDateValue, endDateValue],
  );

  const defaultNightPrice =
    selectedCabin && nights ? getDefaultNightPrice(nights, selectedCabin) : null;

  const defaultTotalPrice =
    selectedCabin && nights
      ? calculateDefaultTotalPrice(nights, selectedCabin)
      : null;

  const paidAmount = Number(paidAmountValue.replace(",", "."));
  const totalPrice = Number(totalPriceValue.replace(",", "."));

  const remainingAmount =
    Number.isFinite(totalPrice) && Number.isFinite(paidAmount)
      ? Math.max(0, totalPrice - paidAmount)
      : defaultTotalPrice;

  const isBelowMinimumNights =
    nights !== null && nights !== undefined && nights < minimumNights;

  useEffect(() => {
    if (defaultTotalPrice === null) {
      return;
    }

    setTotalPriceValue(String(defaultTotalPrice));
    setPaidAmountValue((currentValue) => currentValue || "0");
  }, [defaultTotalPrice]);

  return (
    <form
      action={createReservation}
      className="space-y-8 rounded-xl border bg-white p-6 shadow-sm"
    >
      <input type="hidden" name="guestId" value={initialGuestId} />

      <section className="space-y-4">
        <div>
          <h2 className="text-xl font-semibold">Gość</h2>
          <p className="text-sm text-zinc-500">
            Podstawowe dane kontaktowe osoby rezerwującej.
          </p>
        </div>

        <div className="grid gap-6 md:grid-cols-2">
          <div className="space-y-2">
            <label className="text-sm font-medium">Imię</label>
            <input
              name="firstName"
              required
              defaultValue={initialFirstName}
              className="w-full rounded-lg border p-3"
              placeholder="np. Jan"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Nazwisko</label>
            <input
              name="lastName"
              required
              defaultValue={initialLastName}
              className="w-full rounded-lg border p-3"
              placeholder="np. Kowalski"
            />
          </div>
        </div>

        <div className="grid gap-6 md:grid-cols-2">
          <div className="space-y-2">
            <label className="text-sm font-medium">Email</label>
            <input
              type="email"
              name="email"
              required
              defaultValue={initialEmail}
              className="w-full rounded-lg border p-3"
              placeholder="np. jan@example.com"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Telefon</label>
            <input
              name="phone"
              defaultValue={initialPhone}
              className="w-full rounded-lg border p-3"
              placeholder="np. 500 000 000"
            />
          </div>
        </div>
      </section>

      <section className="space-y-4 border-t pt-8">
        <div>
          <h2 className="text-xl font-semibold">Pobyt</h2>
          <p className="text-sm text-zinc-500">
            Po wybraniu domku i dat system automatycznie wyliczy liczbę nocy i
            cenę domyślną. Minimalna liczba nocy według ustawień systemu:{" "}
            <span className="font-semibold text-zinc-700">
              {minimumNights}
            </span>
            .
          </p>
        </div>

        <div className="space-y-2">
          <label className="text-sm font-medium">Domek</label>

          <select
            name="cabinId"
            required
            value={selectedCabinId}
            onChange={(event) => setSelectedCabinId(event.target.value)}
            className="w-full rounded-lg border p-3"
          >
            <option value="">Wybierz domek</option>

            {cabins.map((cabin) => (
              <option key={cabin.id} value={cabin.id}>
                {cabin.name} — maks. {cabin.maxGuests} osób
              </option>
            ))}
          </select>
        </div>

        <div className="grid gap-6 md:grid-cols-2">
          <div className="space-y-2">
            <label className="text-sm font-medium">Data przyjazdu</label>
            <input
              type="date"
              name="startDate"
              required
              value={startDateValue}
              onChange={(event) => setStartDateValue(event.target.value)}
              className="w-full rounded-lg border p-3"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Godzina przyjazdu</label>
            <input
              type="time"
              name="checkInTime"
              defaultValue={initialCheckInTime}
              required
              className="w-full rounded-lg border p-3"
            />
          </div>
        </div>

        <div className="grid gap-6 md:grid-cols-2">
          <div className="space-y-2">
            <label className="text-sm font-medium">Data wyjazdu</label>
            <input
              type="date"
              name="endDate"
              required
              value={endDateValue}
              onChange={(event) => setEndDateValue(event.target.value)}
              className="w-full rounded-lg border p-3"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Godzina wyjazdu</label>
            <input
              type="time"
              name="checkOutTime"
              defaultValue={initialCheckOutTime}
              required
              className="w-full rounded-lg border p-3"
            />
          </div>
        </div>

        <div className="grid gap-4 rounded-xl bg-zinc-50 p-4 md:grid-cols-3">
          <div>
            <div className="text-sm text-zinc-500">Liczba nocy</div>
            <div className="mt-1 text-2xl font-bold">{nights ?? "—"}</div>
          </div>

          <div>
            <div className="text-sm text-zinc-500">Cena domyślna / noc</div>
            <div className="mt-1 text-2xl font-bold">
              {formatPrice(defaultNightPrice)}
            </div>
          </div>

          <div>
            <div className="text-sm text-zinc-500">Domyślnie do zapłaty</div>
            <div className="mt-1 text-2xl font-bold">
              {formatPrice(defaultTotalPrice)}
            </div>
          </div>
        </div>

        {isBelowMinimumNights ? (
          <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Wybrany pobyt ma {nights} nocy, a minimalna liczba nocy w
            ustawieniach systemu to {minimumNights}. Zmień datę wyjazdu albo
            minimalną liczbę nocy w ustawieniach systemu.
          </div>
        ) : null}
      </section>

      <section className="space-y-4 border-t pt-8">
        <div>
          <h2 className="text-xl font-semibold">Goście</h2>
        </div>

        <div className="grid gap-6 md:grid-cols-2">
          <div className="space-y-2">
            <label className="text-sm font-medium">Dorośli</label>
            <input
              type="number"
              name="adults"
              min={0}
              defaultValue={initialAdults}
              required
              className="w-full rounded-lg border p-3"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Dzieci</label>
            <input
              type="number"
              name="children"
              min={0}
              defaultValue={initialChildren}
              required
              className="w-full rounded-lg border p-3"
            />
          </div>
        </div>
      </section>

      <section className="space-y-4 border-t pt-8">
        <div>
          <h2 className="text-xl font-semibold">Płatność</h2>
          <p className="text-sm text-zinc-500">
            Cena pobytu uzupełnia się automatycznie, ale możesz ją ręcznie
            zmienić.
          </p>
        </div>

        <div className="grid gap-6 md:grid-cols-3">
          <div className="space-y-2">
            <label className="text-sm font-medium">Cena pobytu</label>
            <input
              type="number"
              name="totalPrice"
              min={0}
              step="0.01"
              value={totalPriceValue}
              onChange={(event) => setTotalPriceValue(event.target.value)}
              className="w-full rounded-lg border p-3"
              placeholder="np. 2400"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Wpłacono</label>
            <input
              type="number"
              name="paidAmount"
              min={0}
              step="0.01"
              value={paidAmountValue}
              onChange={(event) => setPaidAmountValue(event.target.value)}
              className="w-full rounded-lg border p-3"
              placeholder="np. 1000"
            />
          </div>

          <div className="rounded-lg border bg-zinc-50 p-4">
            <div className="text-sm text-zinc-500">Pozostało do zapłaty</div>
            <div className="mt-1 text-2xl font-bold text-red-700">
              {formatPrice(remainingAmount ?? null)}
            </div>
          </div>
        </div>
      </section>

      <section className="space-y-4 border-t pt-8">
        <div>
          <h2 className="text-xl font-semibold">Status i źródło</h2>
        </div>

        <div className="grid gap-6 md:grid-cols-2">
          <div className="space-y-2">
            <label className="text-sm font-medium">Status</label>
            <select
              name="status"
              defaultValue="PENDING"
              required
              className="w-full rounded-lg border p-3"
            >
              {statuses.map((status) => (
                <option key={status.value} value={status.value}>
                  {status.label}
                </option>
              ))}
            </select>
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Źródło</label>
            <select
              name="source"
              defaultValue={initialSource}
              required
              className="w-full rounded-lg border p-3"
            >
              {sources.map((source) => (
                <option key={source.value} value={source.value}>
                  {source.label}
                </option>
              ))}
            </select>
          </div>
        </div>
      </section>

      <section className="space-y-4 border-t pt-8">
        <div>
          <h2 className="text-xl font-semibold">Adres</h2>
        </div>

        <div className="space-y-2">
          <label className="text-sm font-medium">Ulica i numer</label>
          <input
            name="street"
            defaultValue={initialStreet}
            className="w-full rounded-lg border p-3"
            placeholder="np. Leśna 12"
          />
        </div>

        <div className="grid gap-6 md:grid-cols-3">
          <div className="space-y-2">
            <label className="text-sm font-medium">Kod pocztowy</label>
            <input
              name="postalCode"
              defaultValue={initialPostalCode}
              className="w-full rounded-lg border p-3"
              placeholder="np. 00-001"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Miasto</label>
            <input
              name="city"
              defaultValue={initialCity}
              className="w-full rounded-lg border p-3"
              placeholder="np. Warszawa"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Kraj</label>
            <input
              name="country"
              defaultValue={initialCountry}
              className="w-full rounded-lg border p-3"
            />
          </div>
        </div>
      </section>

      <section className="space-y-4 border-t pt-8">
        <div>
          <h2 className="text-xl font-semibold">Uwagi</h2>
        </div>

        <textarea
          name="notes"
          rows={5}
          defaultValue={initialNotes}
          className="w-full rounded-lg border p-3"
          placeholder="Uwagi do rezerwacji, płatności, przyjazdu lub gości..."
        />
      </section>

      <div className="flex gap-3 border-t pt-8">
        <button
          type="submit"
          disabled={isBelowMinimumNights}
          className={
            isBelowMinimumNights
              ? "cursor-not-allowed rounded-lg bg-zinc-300 px-6 py-3 text-zinc-600"
              : "rounded-lg bg-green-700 px-6 py-3 text-white hover:bg-green-800"
          }
        >
          Zapisz rezerwację
        </button>

        <Link
          href="/admin/rezerwacje"
          className="rounded-lg border px-6 py-3 hover:bg-zinc-50"
        >
          Anuluj
        </Link>
      </div>
    </form>
  );
}