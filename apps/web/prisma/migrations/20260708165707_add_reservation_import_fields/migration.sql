/*
  Warnings:

  - A unique constraint covering the columns `[externalReservationId]` on the table `Reservation` will be added. If there are existing duplicate values, this will fail.

*/
-- AlterTable
ALTER TABLE "Reservation" ADD COLUMN     "externalCreatedAt" TIMESTAMP(3),
ADD COLUMN     "externalCreatedBy" TEXT,
ADD COLUMN     "externalCreatedById" TEXT,
ADD COLUMN     "externalGuestId" TEXT,
ADD COLUMN     "externalIsSample" BOOLEAN NOT NULL DEFAULT false,
ADD COLUMN     "externalReservationId" TEXT,
ADD COLUMN     "externalRoomId" TEXT,
ADD COLUMN     "externalUpdatedAt" TIMESTAMP(3),
ADD COLUMN     "orderedBy" TEXT,
ADD COLUMN     "paymentStatus" TEXT,
ADD COLUMN     "specialRequests" TEXT;

-- CreateIndex
CREATE UNIQUE INDEX "Reservation_externalReservationId_key" ON "Reservation"("externalReservationId");

-- CreateIndex
CREATE INDEX "Reservation_guestId_idx" ON "Reservation"("guestId");

-- CreateIndex
CREATE INDEX "Reservation_cabinId_idx" ON "Reservation"("cabinId");

-- CreateIndex
CREATE INDEX "Reservation_externalRoomId_idx" ON "Reservation"("externalRoomId");

-- CreateIndex
CREATE INDEX "Reservation_externalGuestId_idx" ON "Reservation"("externalGuestId");

-- CreateIndex
CREATE INDEX "Reservation_status_idx" ON "Reservation"("status");

-- CreateIndex
CREATE INDEX "Reservation_source_idx" ON "Reservation"("source");

-- CreateIndex
CREATE INDEX "Reservation_paymentStatus_idx" ON "Reservation"("paymentStatus");

-- CreateIndex
CREATE INDEX "Reservation_startDate_idx" ON "Reservation"("startDate");

-- CreateIndex
CREATE INDEX "Reservation_endDate_idx" ON "Reservation"("endDate");
