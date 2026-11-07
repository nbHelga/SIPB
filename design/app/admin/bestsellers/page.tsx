"use client"

import { useState } from "react"
import Image from "next/image"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Checkbox } from "@/components/ui/checkbox"
import { Star, GripVertical } from "lucide-react"

interface Product {
  id: string
  name: string
  price: number
  image: string
  isBestSeller: boolean
}

// Sample products
const initialProducts: Product[] = [
  {
    id: "1",
    name: "Derry Jacket Light Denim",
    price: 330650,
    image: "https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=400&q=80",
    isBestSeller: true
  },
  {
    id: "2",
    name: "Fifiya Sweater Blue",
    price: 237150,
    image: "https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=400&q=80",
    isBestSeller: true
  },
  {
    id: "3",
    name: "Serena Top Blue",
    price: 262650,
    image: "https://images.unsplash.com/photo-1485968579580-b6d095142e6e?w=400&q=80",
    isBestSeller: true
  },
  {
    id: "4",
    name: "Brigita Top White",
    price: 245650,
    image: "https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=400&q=80",
    isBestSeller: true
  },
  {
    id: "5",
    name: "Vivienne Dress Dark Denim",
    price: 262650,
    image: "https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=400&q=80",
    isBestSeller: true
  },
  {
    id: "6",
    name: "Classic Linen Shirt",
    price: 299000,
    image: "https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=400&q=80",
    isBestSeller: false
  },
  {
    id: "7",
    name: "Summer Floral Dress",
    price: 359000,
    image: "https://images.unsplash.com/photo-1572804013309-59a88b7e92f1?w=400&q=80",
    isBestSeller: false
  },
  {
    id: "8",
    name: "Kids Cotton T-Shirt",
    price: 129000,
    image: "https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?w=400&q=80",
    isBestSeller: false
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

export default function BestSellersAdminPage() {
  const [products, setProducts] = useState(initialProducts)
  const [isSaving, setIsSaving] = useState(false)
  const [saved, setSaved] = useState(false)

  const toggleBestSeller = (id: string) => {
    setProducts(products.map(p => 
      p.id === id ? { ...p, isBestSeller: !p.isBestSeller } : p
    ))
  }

  const handleSave = async () => {
    setIsSaving(true)
    await new Promise(resolve => setTimeout(resolve, 1000))
    const bestSellers = products.filter(p => p.isBestSeller)
    console.log("Best sellers saved:", bestSellers)
    setIsSaving(false)
    setSaved(true)
    setTimeout(() => setSaved(false), 3000)
  }

  const bestSellerCount = products.filter(p => p.isBestSeller).length

  return (
    <div>
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-2xl font-semibold tracking-wider">Best Sellers</h1>
          <p className="text-muted-foreground mt-1">
            Select which products to feature in the Best Seller section ({bestSellerCount} selected)
          </p>
        </div>
        <Button 
          onClick={handleSave} 
          disabled={isSaving}
          className="tracking-widest"
        >
          {isSaving ? "SAVING..." : saved ? "SAVED!" : "SAVE CHANGES"}
        </Button>
      </div>

      <div className="grid lg:grid-cols-2 gap-8">
        {/* Selected Best Sellers */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Star className="h-5 w-5 fill-current" />
              Selected Best Sellers
            </CardTitle>
            <CardDescription>
              These products will appear in the Best Seller section on the homepage
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {products.filter(p => p.isBestSeller).map(product => (
                <div 
                  key={product.id}
                  className="flex items-center gap-4 p-3 border border-border rounded-md bg-accent/50"
                >
                  <GripVertical className="h-5 w-5 text-muted-foreground cursor-grab" />
                  <div className="relative w-12 h-16 flex-shrink-0">
                    <Image
                      src={product.image}
                      alt={product.name}
                      fill
                      className="object-cover rounded"
                    />
                  </div>
                  <div className="flex-1 min-w-0">
                    <h3 className="text-sm font-medium truncate">{product.name}</h3>
                    <p className="text-xs text-muted-foreground">{formatPrice(product.price)}</p>
                  </div>
                  <Checkbox
                    checked={product.isBestSeller}
                    onCheckedChange={() => toggleBestSeller(product.id)}
                  />
                </div>
              ))}
              {bestSellerCount === 0 && (
                <p className="text-center text-muted-foreground py-8">
                  No products selected. Check products from the list below.
                </p>
              )}
            </div>
          </CardContent>
        </Card>

        {/* All Products */}
        <Card>
          <CardHeader>
            <CardTitle>All Products</CardTitle>
            <CardDescription>
              Check the products you want to feature as Best Sellers
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {products.filter(p => !p.isBestSeller).map(product => (
                <div 
                  key={product.id}
                  className="flex items-center gap-4 p-3 border border-border rounded-md hover:bg-accent/30 transition-colors"
                >
                  <div className="relative w-12 h-16 flex-shrink-0">
                    <Image
                      src={product.image}
                      alt={product.name}
                      fill
                      className="object-cover rounded"
                    />
                  </div>
                  <div className="flex-1 min-w-0">
                    <h3 className="text-sm font-medium truncate">{product.name}</h3>
                    <p className="text-xs text-muted-foreground">{formatPrice(product.price)}</p>
                  </div>
                  <Checkbox
                    checked={product.isBestSeller}
                    onCheckedChange={() => toggleBestSeller(product.id)}
                  />
                </div>
              ))}
              {products.filter(p => !p.isBestSeller).length === 0 && (
                <p className="text-center text-muted-foreground py-8">
                  All products are marked as Best Sellers
                </p>
              )}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
