import { Button } from "@/components/ui/button";

export default function HomePage() {
  return (
    <main className="min-h-screen">
      
      {/* HERO */}
      <section className="relative h-[90vh] flex items-center justify-center text-center bg-[url('/hero.jpg')] bg-cover bg-center">
        
        <div className="absolute inset-0 bg-black/40" />

        <div className="relative z-10 text-white max-w-3xl px-6">
          <h1 className="text-5xl md:text-6xl font-light">
            Domki Sztabinki
          </h1>

          <p className="mt-4 text-lg text-white/80">
            Wypoczynek w sercu natury. Cisza, komfort i przestrzeń tylko dla Ciebie.
          </p>

          <div className="mt-8 flex gap-4 justify-center">
            <Button size="lg">Zarezerwuj pobyt</Button>
            <Button size="lg" variant="outline">
              Zobacz domki
            </Button>
          </div>
        </div>
      </section>

      {/* ABOUT */}
      <section className="py-20 max-w-5xl mx-auto px-6 text-center">
        <h2 className="text-3xl font-light">O nas</h2>
        <p className="mt-6 text-zinc-600 leading-relaxed">
          Domki Sztabinki to kameralne miejsce stworzone dla osób szukających spokoju, natury i komfortu.
          Położone wśród lasów, z dala od miejskiego zgiełku.
        </p>
      </section>

      {/* FEATURES */}
      <section className="grid md:grid-cols-4 gap-6 max-w-6xl mx-auto px-6 py-10">
        {[
          "Cisza i natura",
          "Komfortowe wnętrza",
          "Rodzinny klimat",
          "Prywatność"
        ].map((item) => (
          <div key={item} className="p-6 border rounded-2xl text-center">
            {item}
          </div>
        ))}
      </section>

    </main>
  );
}