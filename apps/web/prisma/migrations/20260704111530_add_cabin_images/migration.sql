-- CreateTable
CREATE TABLE "CabinImage" (
    "id" TEXT NOT NULL,
    "cabinId" TEXT NOT NULL,
    "url" TEXT NOT NULL,
    "alt" TEXT,
    "isMain" BOOLEAN NOT NULL DEFAULT false,
    "sortOrder" INTEGER NOT NULL DEFAULT 0,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "CabinImage_pkey" PRIMARY KEY ("id")
);

-- AddForeignKey
ALTER TABLE "CabinImage" ADD CONSTRAINT "CabinImage_cabinId_fkey" FOREIGN KEY ("cabinId") REFERENCES "Cabin"("id") ON DELETE CASCADE ON UPDATE CASCADE;
