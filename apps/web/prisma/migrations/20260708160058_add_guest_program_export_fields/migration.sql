-- AlterTable
ALTER TABLE "Guest" ADD COLUMN     "birthDate" TIMESTAMP(3),
ADD COLUMN     "documentNumber" TEXT,
ADD COLUMN     "externalGuestId" TEXT,
ADD COLUMN     "isVip" BOOLEAN NOT NULL DEFAULT false,
ADD COLUMN     "nationality" TEXT,
ADD COLUMN     "pesel" TEXT;

-- CreateIndex
CREATE INDEX "Guest_email_idx" ON "Guest"("email");

-- CreateIndex
CREATE INDEX "Guest_phone_idx" ON "Guest"("phone");

-- CreateIndex
CREATE INDEX "Guest_externalGuestId_idx" ON "Guest"("externalGuestId");

-- CreateIndex
CREATE INDEX "Guest_source_idx" ON "Guest"("source");
