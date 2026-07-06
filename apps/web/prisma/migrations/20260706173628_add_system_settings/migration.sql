-- CreateTable
CREATE TABLE "SystemSettings" (
    "id" TEXT NOT NULL DEFAULT 'main',
    "propertyName" TEXT NOT NULL DEFAULT 'Domki Sztabinki',
    "ownerName" TEXT,
    "ownerEmail" TEXT,
    "ownerPhone" TEXT,
    "contactEmail" TEXT,
    "contactPhone" TEXT,
    "propertyStreet" TEXT,
    "propertyPostalCode" TEXT,
    "propertyCity" TEXT,
    "propertyCountry" TEXT NOT NULL DEFAULT 'Polska',
    "checkInTime" TEXT NOT NULL DEFAULT '15:00',
    "checkOutTime" TEXT NOT NULL DEFAULT '11:00',
    "minimumNights" INTEGER NOT NULL DEFAULT 4,
    "seasonStartMonth" INTEGER NOT NULL DEFAULT 5,
    "seasonEndMonth" INTEGER NOT NULL DEFAULT 9,
    "websiteUrl" TEXT,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "SystemSettings_pkey" PRIMARY KEY ("id")
);
