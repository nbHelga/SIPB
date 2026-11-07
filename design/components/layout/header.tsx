"use client"

import Link from "next/link"
import Image from "next/image"
import { useState } from "react"
import { Search, ShoppingBag, ChevronDown, Menu, X } from "lucide-react"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Input } from "@/components/ui/input"
import { useCart } from "@/context/cart-context"

export function Header() {
  const [isSearchOpen, setIsSearchOpen] = useState(false)
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)
  const { openCart, totalItems } = useCart()

  return (
    <header className="fixed top-0 left-0 right-0 z-40 bg-background/95 backdrop-blur-sm border-b border-border">
      <div className="container mx-auto px-4 lg:px-8">
        <div className="flex items-center justify-between h-16 lg:h-20">
          {/* Left - Logo */}
          <Link href="/" className="flex items-center gap-3">
            <Image 
              src="/logo.png" 
              alt="HaloPoala Logo" 
              width={40} 
              height={40}
              className="object-contain"
            />
            <span className="text-xl lg:text-2xl font-semibold tracking-wider text-foreground">
              HALOPOALA
            </span>
          </Link>

          {/* Center - Navigation (Desktop) */}
          <nav className="hidden lg:flex items-center gap-8">
            <Link 
              href="/bestsellers" 
              className="text-sm tracking-widest uppercase hover:text-primary transition-colors"
            >
              Best Sellers
            </Link>
            
            <DropdownMenu>
              <DropdownMenuTrigger className="flex items-center gap-1 text-sm tracking-widest uppercase hover:text-primary transition-colors">
                Shop
                <ChevronDown className="h-4 w-4" />
              </DropdownMenuTrigger>
              <DropdownMenuContent align="center" className="bg-card">
                <DropdownMenuItem asChild>
                  <Link href="/shop" className="cursor-pointer">All Products</Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                  <Link href="/shop/adult" className="cursor-pointer">Adult</Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                  <Link href="/shop/children" className="cursor-pointer">Children</Link>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>

            <Link 
              href="/sale" 
              className="text-sm tracking-widest uppercase text-destructive hover:text-destructive/80 transition-colors"
            >
              Sale
            </Link>

            <Link 
              href="/about" 
              className="text-sm tracking-widest uppercase hover:text-primary transition-colors"
            >
              About Us
            </Link>
          </nav>

          {/* Right - Search & Cart */}
          <div className="flex items-center gap-4">
            {/* Search */}
            <div className="hidden md:flex items-center">
              {isSearchOpen ? (
                <div className="flex items-center gap-2">
                  <Input 
                    type="search" 
                    placeholder="Search..." 
                    className="w-48 h-9 text-sm bg-card"
                    autoFocus
                  />
                  <Button 
                    variant="ghost" 
                    size="icon"
                    onClick={() => setIsSearchOpen(false)}
                  >
                    <X className="h-5 w-5" />
                  </Button>
                </div>
              ) : (
                <Button 
                  variant="ghost" 
                  size="icon"
                  onClick={() => setIsSearchOpen(true)}
                >
                  <Search className="h-5 w-5" />
                </Button>
              )}
            </div>

            {/* Cart */}
            <Button 
              variant="ghost" 
              size="icon" 
              className="relative"
              onClick={openCart}
            >
              <ShoppingBag className="h-5 w-5" />
              {totalItems > 0 && (
                <span className="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-primary text-primary-foreground text-xs flex items-center justify-center">
                  {totalItems}
                </span>
              )}
            </Button>

            {/* Mobile Menu Button */}
            <Button 
              variant="ghost" 
              size="icon" 
              className="lg:hidden"
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            >
              {isMobileMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
            </Button>
          </div>
        </div>

        {/* Mobile Navigation */}
        {isMobileMenuOpen && (
          <div className="lg:hidden py-4 border-t border-border">
            <nav className="flex flex-col gap-4">
              <div className="flex items-center gap-2 px-2">
                <Search className="h-4 w-4 text-muted-foreground" />
                <Input 
                  type="search" 
                  placeholder="Search..." 
                  className="flex-1 h-9 text-sm bg-card"
                />
              </div>
              <Link 
                href="/bestsellers" 
                className="px-2 py-2 text-sm tracking-widest uppercase hover:bg-accent rounded"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                Best Sellers
              </Link>
              <Link 
                href="/shop" 
                className="px-2 py-2 text-sm tracking-widest uppercase hover:bg-accent rounded"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                Shop - All
              </Link>
              <Link 
                href="/shop/adult" 
                className="px-2 py-2 pl-6 text-sm tracking-widest uppercase hover:bg-accent rounded text-muted-foreground"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                Adult
              </Link>
              <Link 
                href="/shop/children" 
                className="px-2 py-2 pl-6 text-sm tracking-widest uppercase hover:bg-accent rounded text-muted-foreground"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                Children
              </Link>
              <Link 
                href="/sale" 
                className="px-2 py-2 text-sm tracking-widest uppercase hover:bg-accent rounded text-destructive"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                Sale
              </Link>
              <Link 
                href="/about" 
                className="px-2 py-2 text-sm tracking-widest uppercase hover:bg-accent rounded"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                About Us
              </Link>
            </nav>
          </div>
        )}
      </div>
    </header>
  )
}
