export default function PublicLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen bg-white text-zinc-900">
      
      <header className="border-b px-6 py-4 flex justify-between">
        <div className="font-medium">Domki Sztabinki</div>

        <nav className="flex gap-6 text-sm">
          <a href="/">Start</a>
          <a href="/oferta">Oferta</a>
          <a href="/kontakt">Kontakt</a>
          <a href="/admin">Panel</a>
        </nav>
      </header>

      {children}
    </div>
  );
}