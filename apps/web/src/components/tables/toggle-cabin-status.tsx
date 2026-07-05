"use client";

import { useTransition } from "react";
import { toggleCabinStatus } from "@/actions/cabins";

export function ToggleCabinStatus({
  id,
  active,
}: {
  id: string;
  active: boolean;
}) {
  const [pending, startTransition] = useTransition();

  return (
    <button
      disabled={pending}
      onClick={() =>
        startTransition(async () => {
          await toggleCabinStatus(id);
        })
      }
      className={`rounded-lg px-3 py-2 text-white ${
        active
          ? "bg-green-600 hover:bg-green-700"
          : "bg-gray-500 hover:bg-gray-600"
      }`}
    >
      {pending ? "..." : active ? "Aktywny" : "Ukryty"}
    </button>
  );
}