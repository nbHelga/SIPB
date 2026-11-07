"use client"

import { useState } from "react"
import Link from "next/link"
import Image from "next/image"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { 
  LineChart, 
  Line, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  ResponsiveContainer 
} from "recharts"
import { 
  Eye, 
  ShoppingBag, 
  TrendingUp, 
  Users,
  ArrowRight
} from "lucide-react"

// Dummy data for charts
const weeklyData = [
  { name: "Sen", sales: 4 },
  { name: "Sel", sales: 3 },
  { name: "Rab", sales: 7 },
  { name: "Kam", sales: 5 },
  { name: "Jum", sales: 8 },
  { name: "Sab", sales: 12 },
  { name: "Min", sales: 6 },
]

const monthlyData = [
  { name: "Week 1", sales: 24 },
  { name: "Week 2", sales: 32 },
  { name: "Week 3", sales: 28 },
  { name: "Week 4", sales: 45 },
]

const yearlyData = [
  { name: "Jan", sales: 120 },
  { name: "Feb", sales: 98 },
  { name: "Mar", sales: 145 },
  { name: "Apr", sales: 132 },
  { name: "May", sales: 178 },
  { name: "Jun", sales: 156 },
  { name: "Jul", sales: 189 },
  { name: "Aug", sales: 201 },
  { name: "Sep", sales: 167 },
  { name: "Oct", sales: 234 },
  { name: "Nov", sales: 289 },
  { name: "Dec", sales: 312 },
]

// Top products data
const topProducts = [
  { name: "Aderyn Cardigan Blue", views: 1234, image: "https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=100&q=80" },
  { name: "Vivienne Dress Dark Denim", views: 987, image: "https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=100&q=80" },
  { name: "Serena Top Blue", views: 856, image: "https://images.unsplash.com/photo-1562157873-818bc0726f68?w=100&q=80" },
]

const bestSellers = [
  { name: "Aderyn Cardigan Blue", sold: 156, image: "https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=100&q=80" },
  { name: "Brigita Top White", sold: 134, image: "https://images.unsplash.com/photo-1485462537746-965f33f7f6a7?w=100&q=80" },
  { name: "Fifiya Sweater Blue", sold: 98, image: "https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?w=100&q=80" },
]

// Recent purchases
const recentPurchases = [
  { id: 1, product: "Aderyn Cardigan Blue", customer: "Sarah Putri", qty: 2, total: 508300 },
  { id: 2, product: "Vivienne Dress Dark Denim", customer: "Maya Sari", qty: 1, total: 262650 },
  { id: 3, product: "Serena Top Blue", customer: "Dina Rahmawati", qty: 3, total: 787950 },
  { id: 4, product: "Brigita Top White", customer: "Aisyah Nur", qty: 1, total: 245650 },
]

function formatPrice(price: number) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(price)
}

export default function AdminDashboardPage() {
  const [chartFilter, setChartFilter] = useState("weekly")

  const getChartData = () => {
    switch (chartFilter) {
      case "monthly": return monthlyData
      case "yearly": return yearlyData
      default: return weeklyData
    }
  }

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold tracking-wide">Dashboard</h1>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Total Visits</p>
                <p className="text-2xl font-semibold mt-1">12,458</p>
              </div>
              <div className="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
                <Eye className="h-6 w-6 text-primary" />
              </div>
            </div>
            <p className="text-xs text-muted-foreground mt-2">
              <span className="text-green-600">+12%</span> from last week
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Total Orders</p>
                <p className="text-2xl font-semibold mt-1">456</p>
              </div>
              <div className="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
                <ShoppingBag className="h-6 w-6 text-primary" />
              </div>
            </div>
            <p className="text-xs text-muted-foreground mt-2">
              <span className="text-green-600">+8%</span> from last week
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Revenue</p>
                <p className="text-2xl font-semibold mt-1">Rp 45.2M</p>
              </div>
              <div className="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
                <TrendingUp className="h-6 w-6 text-primary" />
              </div>
            </div>
            <p className="text-xs text-muted-foreground mt-2">
              <span className="text-green-600">+15%</span> from last week
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Customers</p>
                <p className="text-2xl font-semibold mt-1">1,234</p>
              </div>
              <div className="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
                <Users className="h-6 w-6 text-primary" />
              </div>
            </div>
            <p className="text-xs text-muted-foreground mt-2">
              <span className="text-green-600">+5%</span> from last week
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Charts and Products Row */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Sales Chart */}
        <Card className="lg:col-span-2">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-lg font-medium">Sales Overview</CardTitle>
            <Select value={chartFilter} onValueChange={setChartFilter}>
              <SelectTrigger className="w-32">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="weekly">Weekly</SelectItem>
                <SelectItem value="monthly">Monthly</SelectItem>
                <SelectItem value="yearly">Yearly</SelectItem>
              </SelectContent>
            </Select>
          </CardHeader>
          <CardContent>
            <div className="h-[300px]">
              <ResponsiveContainer width="100%" height="100%">
                <LineChart data={getChartData()}>
                  <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                  <XAxis dataKey="name" className="text-xs" />
                  <YAxis className="text-xs" />
                  <Tooltip 
                    contentStyle={{ 
                      backgroundColor: "hsl(var(--card))",
                      border: "1px solid hsl(var(--border))",
                      borderRadius: "8px"
                    }}
                  />
                  <Line 
                    type="monotone" 
                    dataKey="sales" 
                    stroke="hsl(var(--primary))" 
                    strokeWidth={2}
                    dot={{ fill: "hsl(var(--primary))" }}
                  />
                </LineChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>

        {/* Top Viewed Products */}
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg font-medium">Most Viewed</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {topProducts.map((product, index) => (
                <div key={index} className="flex items-center gap-3">
                  <div className="relative w-12 h-12 rounded overflow-hidden bg-muted flex-shrink-0">
                    <Image
                      src={product.image}
                      alt={product.name}
                      fill
                      className="object-cover"
                    />
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium truncate">{product.name}</p>
                    <p className="text-xs text-muted-foreground">{product.views.toLocaleString()} views</p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Best Sellers and Recent Purchases */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Best Sellers */}
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg font-medium">Best Sellers</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {bestSellers.map((product, index) => (
                <div key={index} className="flex items-center gap-3">
                  <div className="relative w-12 h-12 rounded overflow-hidden bg-muted flex-shrink-0">
                    <Image
                      src={product.image}
                      alt={product.name}
                      fill
                      className="object-cover"
                    />
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium truncate">{product.name}</p>
                    <p className="text-xs text-muted-foreground">{product.sold} sold</p>
                  </div>
                  <span className="text-xs font-medium px-2 py-1 bg-primary/10 text-primary rounded">
                    #{index + 1}
                  </span>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Recent Purchases */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-lg font-medium">Recent Purchases</CardTitle>
            <Link href="/admHaloPoala/dashboard/purchase">
              <Button variant="ghost" size="sm" className="text-primary">
                Detail <ArrowRight className="h-4 w-4 ml-1" />
              </Button>
            </Link>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {recentPurchases.map((purchase) => (
                <div key={purchase.id} className="flex items-center justify-between">
                  <div className="min-w-0">
                    <p className="text-sm font-medium truncate">{purchase.product}</p>
                    <p className="text-xs text-muted-foreground">{purchase.customer} &middot; {purchase.qty}x</p>
                  </div>
                  <span className="text-sm font-medium">{formatPrice(purchase.total)}</span>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
