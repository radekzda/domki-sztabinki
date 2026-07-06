import { User } from "lucide-react";
import AdminSidebar from "../../components/admin/AdminSidebar";

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="flex min-h-screen bg-zinc-100">
      <AdminSidebar />

      <div className="flex flex-1 flex-col">
        <header className="h-16 bg-white border-b px-8 flex items-center justify-between">
          <h2 className="font-semibold text-lg">
            Panel administratora
          </h2>

          <div className="flex items-center gap-3 text-zinc-700">
            <User size={20} />
            <span>Administrator</span>
          </div>
        </header>

        <main className="flex-1 p-8">
          {children}
        </main>
      </div>
    </div>
  );
}