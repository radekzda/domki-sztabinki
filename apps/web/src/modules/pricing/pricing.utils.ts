export type ReservationPricingCabin = {
  pricePerNight: number;
  priceOneNight: number;
  priceTwoNights: number;
  priceThreeNights: number;
  priceFourNights: number;
  priceFiveNights: number;
  priceSixNights: number;
  priceSevenPlusNights: number;
};

const MILLISECONDS_PER_DAY = 1000 * 60 * 60 * 24;

function getLocalDateStart(date: Date) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

export function calculateReservationNights(startDate: Date, endDate: Date) {
  const startDay = getLocalDateStart(startDate);
  const endDay = getLocalDateStart(endDate);

  const difference = Math.round(
    (endDay.getTime() - startDay.getTime()) / MILLISECONDS_PER_DAY
  );

  return Math.max(1, difference);
}

export function calculateReservationNightsFromDateValues(
  startDateValue: string,
  endDateValue: string
) {
  if (!startDateValue || !endDateValue) {
    return null;
  }

  const startDate = new Date(`${startDateValue}T00:00:00`);
  const endDate = new Date(`${endDateValue}T00:00:00`);

  if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
    return null;
  }

  if (endDate <= startDate) {
    return null;
  }

  return calculateReservationNights(startDate, endDate);
}

export function getDefaultNightPrice(
  nights: number,
  cabin: ReservationPricingCabin
) {
  if (nights <= 1) {
    return cabin.priceOneNight;
  }

  if (nights === 2) {
    return cabin.priceTwoNights;
  }

  if (nights === 3) {
    return cabin.priceThreeNights;
  }

  if (nights === 4) {
    return cabin.priceFourNights;
  }

  if (nights === 5) {
    return cabin.priceFiveNights;
  }

  if (nights === 6) {
    return cabin.priceSixNights;
  }

  return cabin.priceSevenPlusNights;
}

export function calculateDefaultTotalPrice(
  nights: number,
  cabin: ReservationPricingCabin
) {
  return nights * getDefaultNightPrice(nights, cabin);
}

export function formatPriceInputValue(value: number | null) {
  if (value === null) {
    return "";
  }

  return String(value);
}