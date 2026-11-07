"use client"

import Image from "next/image"
import Link from "next/link"
import { useState } from "react"
import { ShoppingCart } from "lucide-react"
import { Button } from "@/components/ui/button"

interface Product {
  id: string
  name: string
  price: number
  originalPrice?: number
  image: string
  badge?: string
}

// Sample best seller products (this would come from database/admin in real app)
const sampleProducts: Product[] = [
  {
    id: "1",
    name: "Derry Jacket Light Denim",
    price: 330650,
    originalPrice: 389000,
    image: "https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=400&q=80",
    badge: "SALE 15%"
  },
  {
    id: "2",
    name: "Fifiya Sweater Blue",
    price: 237150,
    originalPrice: 279000,
    image: "https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=400&q=80",
    badge: "SALE 15%"
  },
  {
    id: "3",
    name: "Serena Top Blue",
    price: 262650,
    originalPrice: 309000,
    image: "https://images.unsplash.com/photo-1485968579580-b6d095142e6e?w=400&q=80",
    badge: "SALE 15%"
  },
  {
    id: "4",
    name: "Brigita Top White",
    price: 245650,
    originalPrice: 289000,
    image: "https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=400&q=80",
    badge: "SALE 15%"
  },
  {
    id: "5",
    name: "Vivienne Dress Dark Denim",
    price: 262650,
    originalPrice: 309000,
    image: "https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=400&q=80",
    badge: "SALE 15%"
  }
]

function formatPrice(price: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

function ProductCard({ product }: { product: Product }) {
  const [isHovered, setIsHovered] = useState(false)

  const handleAddToCart = (e: React.MouseEvent) => {
    e.preventDefault()
    e.stopPropagation()
    // Add to cart logic here
    console.log("Added to cart:", product.name)
  }

  return (
    <Link href={`/product/${product.id}`}>
      <div 
        className="group cursor-pointer"
        onMouseEnter={() => setIsHovered(true)}
        onMouseLeave={() => setIsHovered(false)}
      >
        {/* Image Container */}
        <div className="relative aspect-[3/4] overflow-hidden bg-muted mb-4">
          <Image
            src={product.image}
            alt={product.name}
            fill
            className="object-cover transition-transform duration-500 group-hover:scale-105"
          />
          
          {/* Badge */}
          {product.badge && (
            <div className="absolute top-3 left-3 bg-foreground text-primary-foreground px-3 py-1 text-xs tracking-wider">
              {product.badge}
            </div>
          )}

          {/* Add to Cart Button - appears on hover */}
          {isHovered && (
            <button
              onClick={handleAddToCart}
              className="absolute bottom-4 right-4 bg-card hover:bg-accent p-3 rounded-full shadow-lg transition-all duration-300 animate-in fade-in"
            >
              <ShoppingCart className="h-5 w-5 text-foreground" />
            </button>
          )}
        </div>

        {/* Product Info */}
        <div className="text-center">
          <h3 className="text-sm lg:text-base font-medium text-foreground mb-2 tracking-wide">
            {product.name}
          </h3>
          <div className="flex items-center justify-center gap-2">
            {product.originalPrice && (
              <span className="text-sm text-muted-foreground line-through">
                {formatPrice(product.originalPrice)}
              </span>
            )}
            <span className="text-sm lg:text-base font-semibold text-foreground">
              {formatPrice(product.price)}
            </span>
          </div>
        </div>
      </div>
    </Link>
  )
}

interface BestSellerSectionProps {
  products?: Product[]
}

export function BestSellerSection({ products = sampleProducts }: BestSellerSectionProps) {
  return (
    <section className="py-16 lg:py-24 px-4 lg:px-8">
      <div className="container mx-auto">
        {/* Section Title */}
        <h2 className="text-3xl lg:text-4xl font-semibold text-center mb-12 lg:mb-16 tracking-wider">
          BEST SELLER
        </h2>

        {/* Products Grid */}
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 lg:gap-6 mb-12">
          {products.map((product) => (
            <ProductCard key={product.id} product={product} />
          ))}
        </div>

        {/* View All Button */}
        <div className="text-center">
          <Link href="/shop">
            <Button 
              variant="outline" 
              className="tracking-widest px-8 py-6 border-foreground text-foreground hover:bg-foreground hover:text-primary-foreground"
            >
              VIEW ALL PRODUCTS
            </Button>
          </Link>
        </div>
      </div>
    </section>
  )
}
