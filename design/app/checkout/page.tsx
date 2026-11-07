"use client"

import Image from "next/image"
import Link from "next/link"
import { useState } from "react"
import { ShoppingBag, Tag } from "lucide-react"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { MainLayout } from "@/components/layout/main-layout"
import { useCart } from "@/context/cart-context"

function formatPrice(price: number) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(price)
}

// Wrapper component to use cart context
function CheckoutContent() {
  const { items, totalPrice, totalOriginalPrice } = useCart()
  const [discountCode, setDiscountCode] = useState("")
  
  const savings = totalOriginalPrice - totalPrice

  const handleCheckout = () => {
    // Generate WhatsApp message with order details
    const orderDetails = items.map(item => 
      `- ${item.name} (${item.size}) x${item.quantity}: ${formatPrice(item.price * item.quantity)}`
    ).join('\n')
    
    const message = `Halo HaloPoala! Saya ingin memesan:\n\n${orderDetails}\n\nTotal: ${formatPrice(totalPrice)}`
    const whatsappUrl = `https://wa.me/6281234567890?text=${encodeURIComponent(message)}`
    window.open(whatsappUrl, '_blank')
  }

  return (
    <div className="min-h-screen bg-background">
      {/* Checkout Header */}
      <div className="border-b border-border bg-card">
        <div className="container mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <Link href="/" className="text-2xl font-semibold tracking-wider">
              HALOPOALA
            </Link>
            <Link href="/cart">
              <ShoppingBag className="h-5 w-5" />
            </Link>
          </div>
        </div>
      </div>

      <div className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16">
          {/* Left - Customer Information Form */}
          <div className="order-2 lg:order-1">
            <div className="space-y-8">
              {/* Contact Section */}
              <div>
                <div className="flex items-center justify-between mb-4">
                  <h2 className="text-xl font-semibold">Contact</h2>
                </div>
                <div className="space-y-4">
                  <div>
                    <Input
                      type="email"
                      placeholder="Email"
                      className="h-12 bg-card"
                    />
                  </div>
                </div>
              </div>

              {/* Delivery Section */}
              <div>
                <h2 className="text-xl font-semibold mb-4">Delivery</h2>
                <div className="space-y-4">
                  {/* Country */}
                  <div>
                    <Label className="text-sm text-muted-foreground">Country/Region</Label>
                    <Select defaultValue="indonesia">
                      <SelectTrigger className="h-12 mt-1 bg-card">
                        <SelectValue placeholder="Select country" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="indonesia">Indonesia</SelectItem>
                        <SelectItem value="malaysia">Malaysia</SelectItem>
                        <SelectItem value="singapore">Singapore</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  {/* Name */}
                  <div className="grid grid-cols-2 gap-4">
                    <Input
                      placeholder="First name (optional)"
                      className="h-12 bg-card"
                    />
                    <Input
                      placeholder="Last name"
                      className="h-12 bg-card"
                    />
                  </div>

                  {/* Address */}
                  <Input
                    placeholder="Address"
                    className="h-12 bg-card"
                  />

                  {/* Apartment */}
                  <Input
                    placeholder="Apartment, suite, etc. (optional)"
                    className="h-12 bg-card"
                  />

                  {/* City, Province, Postal Code */}
                  <div className="grid grid-cols-3 gap-4">
                    <Input
                      placeholder="City"
                      className="h-12 bg-card"
                    />
                    <Select>
                      <SelectTrigger className="h-12 bg-card">
                        <SelectValue placeholder="Province" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="jakarta">DKI Jakarta</SelectItem>
                        <SelectItem value="jabar">Jawa Barat</SelectItem>
                        <SelectItem value="jatim">Jawa Timur</SelectItem>
                        <SelectItem value="jateng">Jawa Tengah</SelectItem>
                        <SelectItem value="banten">Banten</SelectItem>
                        <SelectItem value="yogya">DI Yogyakarta</SelectItem>
                      </SelectContent>
                    </Select>
                    <Input
                      placeholder="Postal code"
                      className="h-12 bg-card"
                    />
                  </div>

                  {/* Phone */}
                  <Input
                    type="tel"
                    placeholder="Phone"
                    className="h-12 bg-card"
                  />
                </div>
              </div>

              {/* Checkout Button */}
              <Button 
                className="w-full h-14 text-lg font-medium tracking-wider bg-primary hover:bg-primary/90"
                onClick={handleCheckout}
              >
                Complete Order via WhatsApp
              </Button>
            </div>
          </div>

          {/* Right - Order Summary */}
          <div className="order-1 lg:order-2 lg:border-l lg:border-border lg:pl-8">
            <div className="bg-muted/30 rounded-lg p-6 lg:p-0 lg:bg-transparent">
              {/* Cart Items */}
              <div className="space-y-4 mb-6">
                {items.map((item) => (
                  <div key={`${item.id}-${item.size}`} className="flex gap-4">
                    {/* Product Image with quantity badge */}
                    <div className="relative w-16 h-20 bg-muted rounded overflow-hidden flex-shrink-0">
                      <Image
                        src={item.image}
                        alt={item.name}
                        fill
                        className="object-cover"
                      />
                      <span className="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-primary text-primary-foreground text-xs flex items-center justify-center">
                        {item.quantity}
                      </span>
                    </div>

                    {/* Product Info */}
                    <div className="flex-1 min-w-0">
                      <h3 className="font-medium text-sm">{item.name}</h3>
                      {item.discount && (
                        <div className="flex items-center gap-1 text-xs text-muted-foreground">
                          <Tag className="h-3 w-3" />
                          VIP {item.discount}% (-{formatPrice((item.originalPrice || item.price) - item.price)})
                        </div>
                      )}
                      <p className="text-xs text-muted-foreground">{item.size}</p>
                    </div>

                    {/* Price */}
                    <div className="text-right">
                      {item.originalPrice && (
                        <p className="text-xs text-muted-foreground line-through">
                          {formatPrice(item.originalPrice)}
                        </p>
                      )}
                      <p className="text-sm font-medium">
                        {formatPrice(item.price)}
                      </p>
                    </div>
                  </div>
                ))}
              </div>

              {/* Discount Code */}
              <div className="flex gap-2 mb-6">
                <Input
                  placeholder="Discount code or gift card"
                  value={discountCode}
                  onChange={(e) => setDiscountCode(e.target.value)}
                  className="flex-1 h-12 bg-card"
                />
                <Button variant="outline" className="h-12 px-6">
                  Apply
                </Button>
              </div>

              {/* Summary */}
              <div className="space-y-2 pt-4 border-t border-border">
                <div className="flex justify-between text-sm">
                  <span>Subtotal &middot; {items.length} items</span>
                  <span>{formatPrice(totalPrice)}</span>
                </div>
                <div className="flex justify-between text-sm text-muted-foreground">
                  <span>Shipping</span>
                  <span>Enter shipping address</span>
                </div>
                <div className="flex justify-between text-lg font-semibold pt-2">
                  <span>Total</span>
                  <div className="text-right">
                    <span className="text-xs text-muted-foreground mr-2">IDR</span>
                    <span>{formatPrice(totalPrice)}</span>
                  </div>
                </div>
                {savings > 0 && (
                  <div className="flex items-center gap-2 text-sm text-primary">
                    <Tag className="h-4 w-4" />
                    TOTAL SAVINGS {formatPrice(savings)}
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default function CheckoutPage() {
  return (
    <MainLayout>
      <CheckoutContent />
    </MainLayout>
  )
}
