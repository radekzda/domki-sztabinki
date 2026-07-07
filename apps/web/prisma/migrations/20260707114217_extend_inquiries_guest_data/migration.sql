-- AlterTable
ALTER TABLE "Inquiry" ADD COLUMN     "adults" INTEGER NOT NULL DEFAULT 1,
ADD COLUMN     "children" INTEGER NOT NULL DEFAULT 0,
ADD COLUMN     "city" TEXT,
ADD COLUMN     "country" TEXT,
ADD COLUMN     "firstName" TEXT,
ADD COLUMN     "lastName" TEXT,
ADD COLUMN     "postalCode" TEXT,
ADD COLUMN     "street" TEXT,
ALTER COLUMN "source" SET DEFAULT 'WWW';
