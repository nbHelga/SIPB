"use client"

import { use } from "react"
import Link from "next/link"
import Image from "next/image"
import { ArrowLeft } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Separator } from "@/components/ui/separator"

interface PurchaseItem {
  id: string
  name: string
  size: string
  quantity: number
  price: number
  image: string
}

interface PurchaseDetail {
  id: string
  customer: string
  email: string
  phone: string
  address: string
  items: PurchaseItem[]
  subtotal: number
  shipping: number
  total: number
  status: "pending" | "processing" | "shipped" | "delivered"
  paymentStatus: "pending" | "approved" | "rejected"
  date: string
}

// Dummy purchase detail data
const purchaseDetails: Record<string, PurchaseDetail> = {
  "1": {
    id: "1",
    customer: "Sarah Putri",
    email: "sarah@email.com",
    phone: "+62 812 3456 7890",
    address: "Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta, 10220",
    items: [
      { id: "1", name: "Aderyn Cardigan Blue", size: "M", quantity: 1, price: 254150, image: "https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=200&q=80" },
      { id: "2", name: "Vivienne Dress Dark Denim", size: "S", quantity: 1, price: 254150, image: "https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=200&q=80" },
    ],
    subtotal: 508300,
    shipping: 15000,
    total: 523300,
    status: "delivered",
    paymentStatus: "approved",
    date: "2024-01-15",
  },
  "2": {
    id: "2",
    customer: "Maya Sari",
    email: "maya@email.com",
    phone: "+62 813 4567 8901",
    address: "Jl. Gatot Subroto No. 45, Bandung, Jawa Barat, 40262",
    items: [
      { id: "3", name: "Serena Top Blue", size: "L", quantity: 1, price: 262650, image: "https://images.unsplash.com/photo-1562157873-818bc0726f68?w=200&q=80" },
    ],
    subtotal: 262650,
    shipping: 20000,
    total: 282650,
    status: "shipped",
    paymentStatus: "approved",
    date: "2024-01-14",
  },
}

function formatPrice(price: number) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(price)
}

export default function PurchaseDetailPage({ 
  params 
}: { 
  params: Promise<{ id: string }> 
}) {
  const { id } = use(params)
  const purchase = purchaseDetails[id] || purchaseDetails["1"]

  const getStatusBadge = (status: string, type: "order" | "payment") => {
    const orderStyles: Record<string, string> = {
      pending: "bg-yellow-100 text-yellow-700",
      processing: "bg-blue-100 text-blue-700",
      shipped: "bg-purple-100 text-purple-700",
      delivered: "bg-green-100 text-green-700",
    }
    const paymentStyles: Record<string, string> = {
      pending: "bg-yellow-100 text-yellow-700",
      approved: "bg-green-100 text-green-700",
      rejected: "bg-red-100 text-red-700",
    }
    const styles = type === "order" ? orderStyles : paymentStyles
    return (
      <Badge variant="outline" className={`${styles[status]} border-0`}>
        {status}
      </Badge>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Link href="/admHaloPoala/dashboard/purchase">
          <Button variant="ghost" size="icon">
            <ArrowLeft className="h-5 w-5" />
          </Button>
        </Link>
        <div>
          <h1 className="text-2xl font-semibold tracking-wide">Purchase Detail</h1>
          <p className="text-sm text-muted-foreground">Order #{purchase.id}</p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Customer Info & Status */}
        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle className="text-lg">Customer Information</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <p className="text-sm text-muted-foreground">Name</p>
              <p className="font-medium">{purchase.customer}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Email</p>
              <p className="font-medium">{purchase.email}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Phone</p>
              <p className="font-medium">{purchase.phone}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Address</p>
              <p className="font-medium">{purchase.address}</p>
            </div>
            <Separator />
            <div>
              <p className="text-sm text-muted-foreground">Order Date</p>
              <p className="font-medium">{purchase.date}</p>
            </div>
            <div className="flex items-center justify-between">
              <p className="text-sm text-muted-foreground">Order Status</p>
              {getStatusBadge(purchase.status, "order")}
            </div>
            <div className="flex items-center justify-between">
              <p className="text-sm text-muted-foreground">Payment Status</p>
              {getStatusBadge(purchase.paymentStatus, "payment")}
            </div>
          </CardContent>
        </Card>

        {/* Order Items */}
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle className="text-lg">Order Items</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {purchase.items.map((item) => (
                <div key={item.id} className="flex gap-4 pb-4 border-b border-border last:border-0">
                  <div className="relative w-20 h-24 rounded overflow-hidden bg-muted flex-shrink-0">
                    <Image
                      src={item.image}
                      alt={item.name}
                      fill
                      className="object-cover"
                    />
                  </div>
                  <div className="flex-1">
                    <h3 className="font-medium">{item.name}</h3>
                    <p className="text-sm text-muted-foreground">Size: {item.size}</p>
                    <p className="text-sm text-muted-foreground">Qty: {item.quantity}</p>
                  </div>
                  <div className="text-right">
                    <p className="font-medium">{formatPrice(item.price)}</p>
                  </div>
                </div>
              ))}
            </div>

            <Separator className="my-4" />

            {/* Summary */}
            <div className="space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Subtotal</span>
                <span>{formatPrice(purchase.subtotal)}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Shipping</span>
                <span>{formatPrice(purchase.shipping)}</span>
              </div>
              <Separator />
              <div className="flex justify-between font-semibold text-lg">
                <span>Total</span>
                <span>{formatPrice(purchase.total)}</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
