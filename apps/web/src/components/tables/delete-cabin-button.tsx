"use client";

import { useTransition } from "react";
import { deleteCabin } from "@/actions/cabins";

export function DeleteCabinButton({ id }: { id: string }) {
  const [isPending, startTransition] = useTransition();

  function handleDelete() {
    const confirmed = confirm("Czy na pewno chcesz usunąć ten domek?");

    if (!confirmed) return;

    startTransition(async () => {
      await deleteCabin(id);
    });
  }

  return (
    <button
      onClick={handleDelete}
      disabled={isPending}
      className="rounded-lg bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50"
    >
      {isPending ? "Usuwanie..." : "Usuń"}
    </button>
  );
}