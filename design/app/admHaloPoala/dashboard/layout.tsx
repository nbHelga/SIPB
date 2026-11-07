"use client"

import { useEffect, useState } from "react"
import { useRouter, usePathname } from "next/navigation"
import Link from "next/link"
import Image from "next/image"
import { 
  LayoutDashboard, 
  Package, 
  CreditCard, 
  ShoppingCart,
  LogOut,
  Menu,
  X,
  User
} from "lucide-react"
import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"

const sidebarItems = [
  { name: "Dashboard", href: "/admHaloPoala/dashboard", icon: LayoutDashboard },
  { name: "Katalog", href: "/admHaloPoala/dashboard/katalog", icon: Package },
  { name: "Payment", href: "/admHaloPoala/dashboard/payment", icon: CreditCard },
  { name: "Purchase", href: "/admHaloPoala/dashboard/purchase", icon: ShoppingCart },
]

export default function AdminDashboardLayout({
  children,
}: {
  children: React.ReactNode
}) {
  const router = useRouter()
  const pathname = usePathname()
  const [isSidebarOpen, setIsSidebarOpen] = useState(false)
  const [isAuthenticated, setIsAuthenticated] = useState(false)

  useEffect(() => {
    const auth = localStorage.getItem("adminAuth")
    if (!auth) {
      router.push("/admHaloPoala")
    } else {
      setIsAuthenticated(true)
    }
  }, [router])

  const handleLogout = () => {
    localStorage.removeItem("adminAuth")
    router.push("/admHaloPoala")
  }

  if (!isAuthenticated) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <p>Loading...</p>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-muted/30">
      {/* Mobile Sidebar Overlay */}
      {isSidebarOpen && (
        <div 
          className="fixed inset-0 bg-black/50 z-40 lg:hidden"
          onClick={() => setIsSidebarOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside className={cn(
        "fixed top-0 left-0 z-50 h-full w-64 bg-card border-r border-border transform transition-transform duration-300 lg:translate-x-0",
        isSidebarOpen ? "translate-x-0" : "-translate-x-full"
      )}>
        <div className="flex flex-col h-full">
          {/* Logo */}
          <div className="flex items-center gap-3 px-6 py-6 border-b border-border">
            <Image
              src="/logo.png"
              alt="HaloPoala"
              width={40}
              height={40}
              className="object-contain"
            />
            <span className="font-semibold tracking-wider">HALOPOALA</span>
          </div>

          {/* Navigation */}
          <nav className="flex-1 px-4 py-6 space-y-2">
            {sidebarItems.map((item) => {
              const isActive = pathname === item.href || 
                (item.href !== "/admHaloPoala/dashboard" && pathname.startsWith(item.href))
              return (
                <Link
                  key={item.name}
                  href={item.href}
                  onClick={() => setIsSidebarOpen(false)}
                  className={cn(
                    "flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors",
                    isActive 
                      ? "bg-primary text-primary-foreground" 
                      : "text-muted-foreground hover:bg-accent hover:text-foreground"
                  )}
                >
                  <item.icon className="h-5 w-5" />
                  {item.name}
                </Link>
              )
            })}
          </nav>

          {/* Logout */}
          <div className="p-4 border-t border-border">
            <Button
              variant="ghost"
              className="w-full justify-start text-muted-foreground hover:text-destructive"
              onClick={handleLogout}
            >
              <LogOut className="h-5 w-5 mr-3" />
              Logout
            </Button>
          </div>
        </div>
      </aside>

      {/* Main Content */}
      <div className="lg:ml-64">
        {/* Header */}
        <header className="sticky top-0 z-30 bg-card border-b border-border">
          <div className="flex items-center justify-between px-4 lg:px-8 h-16">
            <Button
              variant="ghost"
              size="icon"
              className="lg:hidden"
              onClick={() => setIsSidebarOpen(true)}
            >
              <Menu className="h-5 w-5" />
            </Button>

            <div className="flex-1" />

            {/* Profile */}
            <div className="flex items-center gap-3">
              <span className="text-sm font-medium hidden sm:block">Admin</span>
              <div className="h-9 w-9 rounded-full bg-primary flex items-center justify-center">
                <User className="h-5 w-5 text-primary-foreground" />
              </div>
            </div>
          </div>
        </header>

        {/* Page Content */}
        <main className="p-4 lg:p-8">
          {children}
        </main>
      </div>
    </div>
  )
}
