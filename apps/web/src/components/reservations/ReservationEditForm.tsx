"use client";

import Link from "next/link";
import { useMemo, useState } from "react";
import { updateReservation } from "@/actions/reservations";
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

type EditableReservation = {
  id: string;
  cabinId: string;

  guestName: string;
  firstName: string;
  lastName: string;

  email: string;
  phone: string;

  startDateValue: string;
  endDateValue: string;

  checkInTimeValue: string;
  checkOutTimeValue: string;

  adults: number;
  children: number;

  status: string;
  source: string;

  totalPrice: number | null;
  paidAmount: number | null;

  street: string;
  postalCode: string;
  city: string;
  country: string;

  notes: string;
};

type ReservationEditFormProps = {
  cabins: CabinOption[];
  reservation: EditableReservation;
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

function formatMoneyInputValue(value: number | null) {
  if (value === null) {
    return "";
  }

  return String(value);
}

export default function ReservationEditForm({
  cabins,
  reservation,
  minimumNights = 4,
}: ReservationEditFormProps) {
  const [firstNameValue, setFirstNameValue] = useState(reservation.firstName);
  const [lastNameValue, setLastNameValue] = useState(reservation.lastName);
  const [emailValue, setEmailValue] = useState(reservation.email);
  const [phoneValue, setPhoneValue] = useState(reservation.phone);

  const [selectedCabinId, setSelectedCabinId] = useState(reservation.cabinId);
  const [startDateValue, setStartDateValue] = useState(
    reservation.startDateValue,
  );
  const [endDateValue, setEndDateValue] = useState(reservation.endDateValue);
  const [checkInTimeValue, setCheckInTimeValue] = useState(
    reservation.checkInTimeValue,
  );
  const [checkOutTimeValue, setCheckOutTimeValue] = useState(
    reservation.checkOutTimeValue,
  );

  const [adultsValue, setAdultsValue] = useState(String(reservation.adults));
  const [childrenValue, setChildrenValue] = useState(
    String(reservation.children),
  );

  const [statusValue, setStatusValue] = useState(reservation.status);
  const [sourceValue, setSourceValue] = useState(reservation.source);

  const [totalPriceValue, setTotalPriceValue] = useState(
    formatMoneyInputValue(reservation.totalPrice),
  );
  const [paidAmountValue, setPaidAmountValue] = useState(
    formatMoneyInputValue(reservation.paidAmount ?? 0),
  );

  const [streetValue, setStreetValue] = useState(reservation.street);
  const [postalCodeValue, setPostalCodeValue] = useState(
    reservation.postalCode,
  );
  const [cityValue, setCityValue] = useState(reservation.city);
  const [countryValue, setCountryValue] = useState(reservation.country);
  const [notesValue, setNotesValue] = useState(reservation.notes);

  const selectedCabin = useMemo(
    () => cabins.find((cabin) => cabin.id === selectedCabinId) ?? null,
    [cabins, selectedCabinId],
  );

  const nights = useMemo(
    () =>
      calculateReservationNightsFromDateValues(startDateValue, endDateValue),
    [startDateValue, endDateValue],
  );

  const defaultNightPrice =
    selectedCabin && nights ? getDefaultNightPrice(nights, selectedCabin) : null;

  const defaultTotalPrice =
    selectedCabin && nights
      ? calculateDefaultTotalPrice(nights, selectedCabin)
      : null;

  const parsedPaidAmount = Number(paidAmountValue.replace(",", "."));
  const parsedTotalPrice = Number(totalPriceValue.replace(",", "."));

  const totalPriceForCalculation = Number.isFinite(parsedTotalPrice)
    ? parsedTotalPrice
    : defaultTotalPrice;

  const paidAmountForCalculation = Number.isFinite(parsedPaidAmount)
    ? parsedPaidAmount
    : 0;

  const remainingAmount =
    totalPriceForCalculation === null
      ? null
      : Math.max(0, totalPriceForCalculation - paidAmountForCalculation);

  const isBelowMinimumNights =
    nights !== null && nights !== undefined && nights < minimumNights;

  function recalculateDefaultPrice() {
    if (defaultTotalPrice === null) {
      return;
    }

    setTotalPriceValue(String(defaultTotalPrice));
  }

  return (
    <form
      action={updateReservation}
      className="space-y-8 rounded-xl border bg-white p-6 shadow-sm"
    >
      <input type="hidden" name="reservationId" value={reservation.id} />

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
              value={firstNameValue}
              onChange={(event) => setFirstNameValue(event.target.value)}
              className="w-full rounded-lg border p-3"
              placeholder="np. Jan"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Nazwisko</label>
            <input
              name="lastName"
              required
              value={lastNameValue}
              onChange={(event) => setLastNameValue(event.target.value)}
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
              value={emailValue}
              onChange={(event) => setEmailValue(event.target.value)}
              className="w-full rounded-lg border p-3"
              placeholder="np. jan@example.com"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Telefon</label>
            <input
              name="phone"
              value={phoneValue}
              onChange={(event) => setPhoneValue(event.target.value)}
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
            Przy zmianie domku lub dat możesz przeliczyć cenę według cennika
            domku. Minimalna liczba nocy według ustawień systemu:{" "}
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
              required
              value={checkInTimeValue}
              onChange={(event) => setCheckInTimeValue(event.target.value)}
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
              required
              value={checkOutTimeValue}
              onChange={(event) => setCheckOutTimeValue(event.target.value)}
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
            <div className="text-sm text-zinc-500">Cena domyślna razem</div>
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
              value={adultsValue}
              onChange={(event) => setAdultsValue(event.target.value)}
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
              value={childrenValue}
              onChange={(event) => setChildrenValue(event.target.value)}
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
            Cena pobytu jest edytowalna. Przyciskiem możesz wrócić do ceny
            domyślnej z cennika domku.
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

            <button
              type="button"
              onClick={recalculateDefaultPrice}
              className="text-sm font-medium text-green-700 hover:text-green-800"
            >
              Przelicz według cennika domku
            </button>
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
              {formatPrice(remainingAmount)}
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
              value={statusValue}
              onChange={(event) => setStatusValue(event.target.value)}
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
              value={sourceValue}
              onChange={(event) => setSourceValue(event.target.value)}
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
            value={streetValue}
            onChange={(event) => setStreetValue(event.target.value)}
            className="w-full rounded-lg border p-3"
            placeholder="np. Leśna 12"
          />
        </div>

        <div className="grid gap-6 md:grid-cols-3">
          <div className="space-y-2">
            <label className="text-sm font-medium">Kod pocztowy</label>
            <input
              name="postalCode"
              value={postalCodeValue}
              onChange={(event) => setPostalCodeValue(event.target.value)}
              className="w-full rounded-lg border p-3"
              placeholder="np. 00-001"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Miasto</label>
            <input
              name="city"
              value={cityValue}
              onChange={(event) => setCityValue(event.target.value)}
              className="w-full rounded-lg border p-3"
              placeholder="np. Warszawa"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Kraj</label>
            <input
              name="country"
              value={countryValue}
              onChange={(event) => setCountryValue(event.target.value)}
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
          value={notesValue}
          onChange={(event) => setNotesValue(event.target.value)}
          className="w-full rounded-lg border p-3"
          placeholder="Uwagi do rezerwacji, płatności, przyjazdu lub gości"
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
          Zapisz zmiany
        </button>

        <Link
          href={`/admin/rezerwacje/${reservation.id}`}
          className="rounded-lg border px-6 py-3 hover:bg-zinc-50"
        >
          Anuluj
        </Link>
      </div>
    </form>
  );
}