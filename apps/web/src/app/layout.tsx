import "./globals.css";

export const metadata = {
  title: "Domki Sztabinki",
  description: "Wypoczynek w sercu natury"
};

export default function RootLayout({
  children
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="pl">
      <body className="min-h-screen bg-white text-zinc-900">
        {children}
      </body>
    </html>
  );
}