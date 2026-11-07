"use client"

import { Header } from "./header"
import { Footer } from "./footer"
import { WhatsAppButton } from "@/components/ui/whatsapp-button"
import { CartDrawer } from "@/components/cart/cart-drawer"
import { CartProvider } from "@/context/cart-context"

interface MainLayoutProps {
  children: React.ReactNode
}

export function MainLayout({ children }: MainLayoutProps) {
  return (
    <CartProvider>
      <div className="min-h-screen flex flex-col">
        <Header />
        <main className="flex-1 pt-16 lg:pt-20">
          {children}
        </main>
        <Footer />
        <WhatsAppButton />
        <CartDrawer />
      </div>
    </CartProvider>
  )
}
