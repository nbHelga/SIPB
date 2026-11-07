import Link from "next/link"
import Image from "next/image"
import { LayoutDashboard, Package, ImageIcon, MessageSquare, Settings } from "lucide-react"

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <div className="min-h-screen bg-background">
      {/* Admin Header */}
      <header className="fixed top-0 left-0 right-0 z-50 bg-foreground text-primary-foreground h-16 flex items-center px-6">
        <Link href="/admin" className="flex items-center gap-3">
          <Image 
            src="/logo.png" 
            alt="HaloPoala Admin" 
            width={32} 
            height={32}
            className="object-contain invert"
          />
          <span className="text-lg font-semibold tracking-wider">
            HALOPOALA ADMIN
          </span>
        </Link>
        <div className="ml-auto">
          <Link 
            href="/" 
            className="text-sm text-primary-foreground/70 hover:text-primary-foreground transition-colors"
          >
            View Store
          </Link>
        </div>
      </header>

      <div className="flex pt-16">
        {/* Sidebar */}
        <aside className="fixed left-0 top-16 bottom-0 w-64 bg-card border-r border-border p-4">
          <nav className="space-y-2">
            <Link 
              href="/admin"
              className="flex items-center gap-3 px-4 py-3 rounded-md hover:bg-accent transition-colors text-sm"
            >
              <LayoutDashboard className="h-5 w-5" />
              Dashboard
            </Link>
            <Link 
              href="/admin/hero"
              className="flex items-center gap-3 px-4 py-3 rounded-md hover:bg-accent transition-colors text-sm"
            >
              <ImageIcon className="h-5 w-5" />
              Hero Section
            </Link>
            <Link 
              href="/admin/products"
              className="flex items-center gap-3 px-4 py-3 rounded-md hover:bg-accent transition-colors text-sm"
            >
              <Package className="h-5 w-5" />
              Products
            </Link>
            <Link 
              href="/admin/bestsellers"
              className="flex items-center gap-3 px-4 py-3 rounded-md hover:bg-accent transition-colors text-sm"
            >
              <Package className="h-5 w-5" />
              Best Sellers
            </Link>
            <Link 
              href="/admin/feedback"
              className="flex items-center gap-3 px-4 py-3 rounded-md hover:bg-accent transition-colors text-sm"
            >
              <MessageSquare className="h-5 w-5" />
              Feedback
            </Link>
            <Link 
              href="/admin/settings"
              className="flex items-center gap-3 px-4 py-3 rounded-md hover:bg-accent transition-colors text-sm"
            >
              <Settings className="h-5 w-5" />
              Settings
            </Link>
          </nav>
        </aside>

        {/* Main Content */}
        <main className="ml-64 flex-1 p-8">
          {children}
        </main>
      </div>
    </div>
  )
}
