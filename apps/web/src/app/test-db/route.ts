import { prisma } from "@/lib/prisma";

export async function GET() {
  const cabins = await prisma.cabin.findMany();
  return Response.json(cabins);
}