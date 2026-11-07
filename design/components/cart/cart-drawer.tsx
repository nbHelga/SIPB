"use client"

import Image from "next/image"
import Link from "next/link"
import { X, Minus, Plus, Tag } from "lucide-react"
import { Button } from "@/components/ui/button"
import { ScrollArea } from "@/components/ui/scroll-area"
import { useCart } from "@/context/cart-context"

function formatPrice(price: number) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(price)
}

export function CartDrawer() {
  const { 
    items, 
    isOpen, 
    closeCart, 
    removeItem, 
    updateQuantity,
    totalPrice,
    totalOriginalPrice
  } = useCart()

  if (!isOpen) return null

  const savings = totalOriginalPrice - totalPrice

  return (
    <>
      {/* Backdrop */}
      <div 
        className="fixed inset-0 bg-black/50 z-50"
        onClick={closeCart}
      />
      
      {/* Drawer */}
      <div className="fixed top-0 right-0 h-full w-full max-w-md bg-card z-50 shadow-xl flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b border-border">
          <h2 className="text-lg font-semibold tracking-wide">Cart</h2>
          <Button variant="ghost" size="icon" onClick={closeCart}>
            <X className="h-5 w-5" />
          </Button>
        </div>

        {/* Cart Items */}
        <ScrollArea className="flex-1 p-4">
          {items.length === 0 ? (
            <div className="flex flex-col items-center justify-center h-64 text-muted-foreground">
              <p>Your cart is empty</p>
              <Button variant="outline" className="mt-4" onClick={closeCart}>
                Continue Shopping
              </Button>
            </div>
          ) : (
            <div className="space-y-6">
              {items.map((item) => (
                <div key={`${item.id}-${item.size}`} className="flex gap-4">
                  {/* Product Image */}
                  <div className="relative w-28 h-36 bg-muted rounded overflow-hidden flex-shrink-0">
                    <Image
                      src={item.image}
                      alt={item.name}
                      fill
                      className="object-cover"
                    />
                  </div>

                  {/* Product Info */}
                  <div className="flex-1 min-w-0">
                    <h3 className="font-medium text-sm leading-tight">{item.name}</h3>
                    <div className="flex items-center gap-2 mt-1">
                      <span className="text-primary font-semibold text-sm">
                        {formatPrice(item.price)}
                      </span>
                      {item.originalPrice && (
                        <span className="text-muted-foreground text-xs line-through">
                          {formatPrice(item.originalPrice)}
                        </span>
                      )}
                    </div>
                    <p className="text-muted-foreground text-xs mt-1">{item.size}</p>

                    {/* Quantity Controls */}
                    <div className="flex items-center gap-3 mt-3">
                      <div className="flex items-center border border-border rounded">
                        <Button
                          variant="ghost"
                          size="icon"
                          className="h-8 w-8 rounded-none"
                          onClick={() => updateQuantity(item.id, item.size, item.quantity - 1)}
                        >
                          <Minus className="h-3 w-3" />
                        </Button>
                        <span className="w-8 text-center text-sm">{item.quantity}</span>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="h-8 w-8 rounded-none"
                          onClick={() => updateQuantity(item.id, item.size, item.quantity + 1)}
                        >
                          <Plus className="h-3 w-3" />
                        </Button>
                      </div>
                      <button
                        onClick={() => removeItem(item.id, item.size)}
                        className="text-sm text-muted-foreground hover:text-foreground underline"
                      >
                        Remove
                      </button>
                    </div>

                    {/* Discount Badge */}
                    {item.discount && (
                      <div className="inline-flex items-center gap-1 mt-2 px-2 py-1 bg-destructive/10 text-destructive text-xs rounded">
                        <Tag className="h-3 w-3" />
                        VIP {item.discount}% ( {formatPrice((item.originalPrice || item.price) - item.price)} )
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </ScrollArea>

        {/* Footer */}
        {items.length > 0 && (
          <div className="border-t border-border p-4 space-y-3">
            <div className="text-sm text-muted-foreground">
              Add order note
            </div>
            <p className="text-xs text-muted-foreground">
              Taxes and shipping calculated at checkout
            </p>
            <Link href="/checkout" onClick={closeCart}>
              <Button className="w-full h-12 bg-foreground hover:bg-foreground/90 text-background font-medium tracking-wider">
                CHECKOUT &middot;{" "}
                {savings > 0 && (
                  <span className="line-through opacity-70 mr-2">
                    {formatPrice(totalOriginalPrice)}
                  </span>
                )}
                {formatPrice(totalPrice)}
              </Button>
            </Link>
          </div>
        )}
      </div>
    </>
  )
}
