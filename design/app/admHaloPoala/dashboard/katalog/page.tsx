"use client"

import { useRouter } from "next/navigation"
import Image from "next/image"
import { Button } from "@/components/ui/button"
import { DataTable } from "@/components/admin/data-table"
import { Eye } from "lucide-react"

interface Product {
  id: string
  name: string
  price: number
  category: string
  image: string
  stock: number
}

// Dummy products data
const products: Product[] = [
  { id: "1", name: "Aderyn Cardigan Blue", price: 299000, category: "adult", image: "https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=100&q=80", stock: 45 },
  { id: "2", name: "Vivienne Dress Dark Denim", price: 309000, category: "adult", image: "https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=100&q=80", stock: 32 },
  { id: "3", name: "Serena Top Blue", price: 279000, category: "adult", image: "https://images.unsplash.com/photo-1562157873-818bc0726f68?w=100&q=80", stock: 28 },
  { id: "4", name: "Brigita Top White", price: 289000, category: "adult", image: "https://images.unsplash.com/photo-1485462537746-965f33f7f6a7?w=100&q=80", stock: 56 },
  { id: "5", name: "Fifiya Sweater Blue", price: 279000, category: "adult", image: "https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?w=100&q=80", stock: 41 },
  { id: "6", name: "Luna Kids Dress Pink", price: 189000, category: "children", image: "https://images.unsplash.com/photo-1518831959646-742c3a14ebf7?w=100&q=80", stock: 23 },
  { id: "7", name: "Mini Star Top", price: 149000, category: "children", image: "https://images.unsplash.com/photo-1522771930-78848d9293e8?w=100&q=80", stock: 67 },
  { id: "8", name: "Derry Jacket Light Denim", price: 389000, category: "adult", image: "https://images.unsplash.com/photo-1551028719-00167b16eac5?w=100&q=80", stock: 19 },
  { id: "9", name: "Rosa Blouse Cream", price: 259000, category: "adult", image: "https://images.unsplash.com/photo-1485462537746-965f33f7f6a7?w=100&q=80", stock: 38 },
  { id: "10", name: "Bella Kids Set", price: 219000, category: "children", image: "https://images.unsplash.com/photo-1518831959646-742c3a14ebf7?w=100&q=80", stock: 15 },
  { id: "11", name: "Nina Cardigan Beige", price: 319000, category: "adult", image: "https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=100&q=80", stock: 27 },
  { id: "12", name: "Summer Kids Romper", price: 179000, category: "children", image: "https://images.unsplash.com/photo-1522771930-78848d9293e8?w=100&q=80", stock: 44 },
]

function formatPrice(price: number) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(price)
}

export default function KatalogPage() {
  const router = useRouter()

  const columns = [
    {
      key: "image",
      header: "Image",
      render: (item: Product) => (
        <div className="relative w-12 h-12 rounded overflow-hidden bg-muted">
          <Image
            src={item.image}
            alt={item.name}
            fill
            className="object-cover"
          />
        </div>
      ),
    },
    {
      key: "name",
      header: "Name",
      render: (item: Product) => (
        <span className="font-medium">{item.name}</span>
      ),
    },
    {
      key: "price",
      header: "Price",
      render: (item: Product) => formatPrice(item.price),
    },
    {
      key: "category",
      header: "Category",
      render: (item: Product) => (
        <span className="capitalize px-2 py-1 bg-muted rounded text-xs">
          {item.category}
        </span>
      ),
    },
    {
      key: "stock",
      header: "Stock",
      render: (item: Product) => (
        <span className={item.stock < 20 ? "text-destructive" : ""}>
          {item.stock}
        </span>
      ),
    },
    {
      key: "actions",
      header: "Detail",
      render: (item: Product) => (
        <Button
          variant="ghost"
          size="sm"
          onClick={() => router.push(`/admHaloPoala/dashboard/katalog/${item.id}`)}
        >
          <Eye className="h-4 w-4 mr-1" />
          View
        </Button>
      ),
    },
  ]

  const filterOptions = [
    { value: "adult", label: "Adult" },
    { value: "children", label: "Children" },
  ]

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold tracking-wide">Katalog</h1>
      
      <DataTable
        data={products}
        columns={columns}
        searchPlaceholder="Search product..."
        searchKey="name"
        filterOptions={filterOptions}
        filterKey="category"
        onAdd={() => router.push("/admHaloPoala/dashboard/katalog/add")}
        addButtonText="Add Product"
      />
    </div>
  )
}
