-- CreateTable
CREATE TABLE "ResponseTemplate" (
    "id" TEXT NOT NULL,
    "name" TEXT NOT NULL,
    "subject" TEXT NOT NULL,
    "body" TEXT NOT NULL,
    "isActive" BOOLEAN NOT NULL DEFAULT true,
    "sortOrder" INTEGER NOT NULL DEFAULT 0,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "ResponseTemplate_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE INDEX "ResponseTemplate_isActive_idx" ON "ResponseTemplate"("isActive");

-- CreateIndex
CREATE INDEX "ResponseTemplate_sortOrder_idx" ON "ResponseTemplate"("sortOrder");
