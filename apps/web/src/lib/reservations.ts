import { prisma } from "@/lib/prisma";

const blockingReservationStatuses = ["PENDING", "CONFIRMED"];

export async function checkCabinAvailability({
  cabinId,
  startDate,
  endDate,
  ignoreReservationId,
}: {
  cabinId: string;
  startDate: Date;
  endDate: Date;
  ignoreReservationId?: string;
}) {
  const conflictingReservation = await prisma.reservation.findFirst({
    where: {
      cabinId,
      status: {
        in: blockingReservationStatuses,
      },
      startDate: {
        lt: endDate,
      },
      endDate: {
        gt: startDate,
      },
      ...(ignoreReservationId
        ? {
            id: {
              not: ignoreReservationId,
            },
          }
        : {}),
    },
    include: {
      cabin: true,
    },
  });

  return {
    available: !conflictingReservation,
    conflictingReservation,
  };
}