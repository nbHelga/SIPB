"use client"

import Image from "next/image"
import { useState } from "react"
import { use } from "react"
import { Minus, Plus, ShoppingCart } from "lucide-react"
import { Header } from "@/components/layout/header"
import { Footer } from "@/components/layout/footer"
import { WhatsAppButton } from "@/components/ui/whatsapp-button"
import { Button } from "@/components/ui/button"

// Sample products (would come from database)
const products: Record<string, {
  id: string
  name: string
  price: number
  originalPrice?: number
  description: string
  images: string[]
  sizes: string[]
  category: string
}> = {
  "1": {
    id: "1",
    name: "Derry Jacket Light Denim",
    price: 330650,
    originalPrice: 389000,
    description: "A versatile light denim jacket perfect for layering. Features classic collar, button front closure, and two chest pockets. Made from premium quality denim for lasting comfort and style.",
    images: [
      "https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=800&q=80",
      "https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=800&q=80"
    ],
    sizes: ["XS", "S", "M", "L", "XL"],
    category: "Outerwear"
  },
  "2": {
    id: "2",
    name: "Fifiya Sweater Blue",
    price: 237150,
    originalPrice: 279000,
    description: "Cozy blue sweater crafted from soft premium cotton blend. Features a relaxed fit with ribbed cuffs and hem. Perfect for casual everyday wear.",
    images: [
      "https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=800&q=80"
    ],
    sizes: ["XS", "S", "M", "L"],
    category: "Knitwear"
  },
  "3": {
    id: "3",
    name: "Serena Top Blue",
    price: 262650,
    originalPrice: 309000,
    description: "Elegant blue top with delicate details. Features a flattering silhouette with comfortable stretch fabric. Versatile piece for both casual and dressy occasions.",
    images: [
      "https://images.unsplash.com/photo-1485968579580-b6d095142e6e?w=800&q=80"
    ],
    sizes: ["XS", "S", "M", "L", "XL"],
    category: "Tops"
  }
}

function formatPrice(price: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

export default function ProductPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const product = products[id] || products["1"]
  
  const [selectedSize, setSelectedSize] = useState<string>("")
  const [quantity, setQuantity] = useState(1)
  const [selectedImage, setSelectedImage] = useState(0)

  const handleAddToCart = () => {
    if (!selectedSize) {
      alert("Please select a size")
      return
    }
    console.log("Added to cart:", { ...product, size: selectedSize, quantity })
  }

  return (
    <div className="min-h-screen">
      <Header />
      
      <main className="pt-16 lg:pt-20">
        <div className="container mx-auto px-4 lg:px-8 py-12 lg:py-16">
          <div className="grid lg:grid-cols-2 gap-8 lg:gap-16">
            {/* Images */}
            <div className="space-y-4">
              <div className="relative aspect-[3/4] overflow-hidden bg-muted">
                <Image
                  src={product.images[selectedImage]}
                  alt={product.name}
                  fill
                  className="object-cover"
                  priority
                />
              </div>
              {product.images.length > 1 && (
                <div className="flex gap-2">
                  {product.images.map((img, idx) => (
                    <button
                      key={idx}
                      onClick={() => setSelectedImage(idx)}
                      className={`relative w-20 h-24 overflow-hidden border-2 transition-colors ${
                        selectedImage === idx ? "border-foreground" : "border-transparent"
                      }`}
                    >
                      <Image src={img} alt="" fill className="object-cover" />
                    </button>
                  ))}
                </div>
              )}
            </div>

            {/* Product Info */}
            <div>
              <p className="text-sm text-muted-foreground tracking-wider mb-2">
                {product.category}
              </p>
              <h1 className="text-2xl lg:text-3xl font-semibold mb-4">
                {product.name}
              </h1>
              
              <div className="flex items-center gap-3 mb-6">
                {product.originalPrice && (
                  <span className="text-lg text-muted-foreground line-through">
                    {formatPrice(product.originalPrice)}
                  </span>
                )}
                <span className="text-xl lg:text-2xl font-semibold">
                  {formatPrice(product.price)}
                </span>
                {product.originalPrice && (
                  <span className="bg-foreground text-primary-foreground px-2 py-1 text-xs">
                    SALE
                  </span>
                )}
              </div>

              <p className="text-muted-foreground leading-relaxed mb-8">
                {product.description}
              </p>

              {/* Size Selection */}
              <div className="mb-6">
                <p className="text-sm font-medium mb-3">Size</p>
                <div className="flex flex-wrap gap-2">
                  {product.sizes.map(size => (
                    <button
                      key={size}
                      onClick={() => setSelectedSize(size)}
                      className={`w-12 h-12 border transition-colors ${
                        selectedSize === size
                          ? "border-foreground bg-foreground text-primary-foreground"
                          : "border-border hover:border-foreground"
                      }`}
                    >
                      {size}
                    </button>
                  ))}
                </div>
              </div>

              {/* Quantity */}
              <div className="mb-8">
                <p className="text-sm font-medium mb-3">Quantity</p>
                <div className="flex items-center border border-border w-fit">
                  <button
                    onClick={() => setQuantity(q => Math.max(1, q - 1))}
                    className="p-3 hover:bg-accent transition-colors"
                  >
                    <Minus className="h-4 w-4" />
                  </button>
                  <span className="px-6 py-3 text-center min-w-[60px]">
                    {quantity}
                  </span>
                  <button
                    onClick={() => setQuantity(q => q + 1)}
                    className="p-3 hover:bg-accent transition-colors"
                  >
                    <Plus className="h-4 w-4" />
                  </button>
                </div>
              </div>

              {/* Add to Cart */}
              <Button
                onClick={handleAddToCart}
                className="w-full py-6 tracking-widest bg-foreground text-primary-foreground hover:bg-foreground/90"
              >
                <ShoppingCart className="h-5 w-5 mr-2" />
                ADD TO CART
              </Button>
            </div>
          </div>
        </div>
      </main>

      <Footer />
      <WhatsAppButton />
    </div>
  )
}
