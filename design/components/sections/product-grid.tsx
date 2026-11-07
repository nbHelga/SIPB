"use client"

import Image from "next/image"
import Link from "next/link"
import { useState } from "react"
import { ShoppingCart } from "lucide-react"

interface Product {
  id: string
  name: string
  price: number
  originalPrice?: number
  image: string
  badge?: string
  category: "adult" | "children"
}

// Sample products (would come from database in real app)
const allProducts: Product[] = [
  {
    id: "1",
    name: "Derry Jacket Light Denim",
    price: 330650,
    originalPrice: 389000,
    image: "https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=400&q=80",
    badge: "SALE 15%",
    category: "adult"
  },
  {
    id: "2",
    name: "Fifiya Sweater Blue",
    price: 237150,
    originalPrice: 279000,
    image: "https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=400&q=80",
    badge: "SALE 15%",
    category: "adult"
  },
  {
    id: "3",
    name: "Serena Top Blue",
    price: 262650,
    originalPrice: 309000,
    image: "https://images.unsplash.com/photo-1485968579580-b6d095142e6e?w=400&q=80",
    badge: "SALE 15%",
    category: "adult"
  },
  {
    id: "4",
    name: "Brigita Top White",
    price: 245650,
    originalPrice: 289000,
    image: "https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=400&q=80",
    badge: "SALE 15%",
    category: "adult"
  },
  {
    id: "5",
    name: "Vivienne Dress Dark Denim",
    price: 262650,
    originalPrice: 309000,
    image: "https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=400&q=80",
    badge: "SALE 15%",
    category: "adult"
  },
  {
    id: "6",
    name: "Classic Linen Shirt",
    price: 299000,
    image: "https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=400&q=80",
    category: "adult"
  },
  {
    id: "7",
    name: "Summer Floral Dress",
    price: 359000,
    image: "https://images.unsplash.com/photo-1572804013309-59a88b7e92f1?w=400&q=80",
    category: "adult"
  },
  {
    id: "8",
    name: "Kids Cotton T-Shirt",
    price: 129000,
    image: "https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?w=400&q=80",
    category: "children"
  },
  {
    id: "9",
    name: "Kids Denim Overalls",
    price: 199000,
    image: "https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=400&q=80",
    category: "children"
  },
  {
    id: "10",
    name: "Kids Summer Dress",
    price: 179000,
    originalPrice: 219000,
    image: "https://images.unsplash.com/photo-1518831959646-742c3a14ebf7?w=400&q=80",
    badge: "SALE",
    category: "children"
  },
  {
    id: "11",
    name: "Kids Casual Polo",
    price: 149000,
    image: "https://images.unsplash.com/photo-1503944583220-79d8926ad5e2?w=400&q=80",
    category: "children"
  },
  {
    id: "12",
    name: "Kids Cozy Sweater",
    price: 189000,
    image: "https://images.unsplash.com/photo-1514090458221-65bb69cf63e6?w=400&q=80",
    category: "children"
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
    console.log("Added to cart:", product.name)
  }

  return (
    <Link href={`/product/${product.id}`}>
      <div 
        className="group cursor-pointer"
        onMouseEnter={() => setIsHovered(true)}
        onMouseLeave={() => setIsHovered(false)}
      >
        <div className="relative aspect-[3/4] overflow-hidden bg-muted mb-4">
          <Image
            src={product.image}
            alt={product.name}
            fill
            className="object-cover transition-transform duration-500 group-hover:scale-105"
          />
          
          {product.badge && (
            <div className="absolute top-3 left-3 bg-foreground text-primary-foreground px-3 py-1 text-xs tracking-wider">
              {product.badge}
            </div>
          )}

          {isHovered && (
            <button
              onClick={handleAddToCart}
              className="absolute bottom-4 right-4 bg-card hover:bg-accent p-3 rounded-full shadow-lg transition-all duration-300 animate-in fade-in"
            >
              <ShoppingCart className="h-5 w-5 text-foreground" />
            </button>
          )}
        </div>

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

interface ProductGridProps {
  category?: "all" | "adult" | "children" | "sale"
}

export function ProductGrid({ category = "all" }: ProductGridProps) {
  let filteredProducts = allProducts

  if (category === "adult") {
    filteredProducts = allProducts.filter(p => p.category === "adult")
  } else if (category === "children") {
    filteredProducts = allProducts.filter(p => p.category === "children")
  } else if (category === "sale") {
    filteredProducts = allProducts.filter(p => p.badge?.includes("SALE"))
  }

  if (filteredProducts.length === 0) {
    return (
      <div className="text-center py-12">
        <p className="text-muted-foreground">No products found in this category.</p>
      </div>
    )
  }

  return (
    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
      {filteredProducts.map((product) => (
        <ProductCard key={product.id} product={product} />
      ))}
    </div>
  )
}
