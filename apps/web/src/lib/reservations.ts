import { prisma } from "@/lib/prisma";

const blockingReservationStatuses = ["PENDING", "CONFIRMED", "CHECKED_IN"];

type ReservationAvailabilityCheck = {
  cabinId: string;
  startDate: Date;
  endDate: Date;
  ignoreReservationId?: string;
};

function getEffectiveReservationStartDate(reservation: {
  startDate: Date;
  checkInAt: Date | null;
}) {
  return reservation.checkInAt ?? reservation.startDate;
}

function getEffectiveReservationEndDate(reservation: {
  endDate: Date;
  checkOutAt: Date | null;
}) {
  return reservation.checkOutAt ?? reservation.endDate;
}

function datesOverlap({
  firstStartDate,
  firstEndDate,
  secondStartDate,
  secondEndDate,
}: {
  firstStartDate: Date;
  firstEndDate: Date;
  secondStartDate: Date;
  secondEndDate: Date;
}) {
  return firstStartDate < secondEndDate && firstEndDate > secondStartDate;
}

export async function checkCabinAvailability({
  cabinId,
  startDate,
  endDate,
  ignoreReservationId,
}: ReservationAvailabilityCheck) {
  const blockingReservations = await prisma.reservation.findMany({
    where: {
      cabinId,
      status: {
        in: blockingReservationStatuses,
      },
      ...(ignoreReservationId
        ? {
            id: {
              not: ignoreReservationId,
            },
          }
        : {}),
    },
    orderBy: {
      startDate: "asc",
    },
    include: {
      cabin: true,
    },
  });

  const conflictingReservation = blockingReservations.find((reservation) => {
    const existingStartDate = getEffectiveReservationStartDate(reservation);
    const existingEndDate = getEffectiveReservationEndDate(reservation);

    return datesOverlap({
      firstStartDate: startDate,
      firstEndDate: endDate,
      secondStartDate: existingStartDate,
      secondEndDate: existingEndDate,
    });
  });

  return {
    available: !conflictingReservation,
    conflictingReservation: conflictingReservation ?? null,
  };
}