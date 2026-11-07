"use client"

import { useRouter } from "next/navigation"
import { Button } from "@/components/ui/button"
import { DataTable } from "@/components/admin/data-table"
import { Eye } from "lucide-react"

interface Purchase {
  id: string
  customer: string
  email: string
  itemCount: number
  total: number
  status: "pending" | "processing" | "shipped" | "delivered"
  date: string
}

// Dummy purchase data
const purchases: Purchase[] = [
  { id: "1", customer: "Sarah Putri", email: "sarah@email.com", itemCount: 2, total: 508300, status: "delivered", date: "2024-01-15" },
  { id: "2", customer: "Maya Sari", email: "maya@email.com", itemCount: 1, total: 262650, status: "shipped", date: "2024-01-14" },
  { id: "3", customer: "Dina Rahmawati", email: "dina@email.com", itemCount: 3, total: 787950, status: "processing", date: "2024-01-14" },
  { id: "4", customer: "Aisyah Nur", email: "aisyah@email.com", itemCount: 1, total: 245650, status: "delivered", date: "2024-01-13" },
  { id: "5", customer: "Fatimah Zahra", email: "fatimah@email.com", itemCount: 2, total: 598000, status: "pending", date: "2024-01-13" },
  { id: "6", customer: "Nur Aini", email: "aini@email.com", itemCount: 1, total: 378000, status: "shipped", date: "2024-01-12" },
  { id: "7", customer: "Rahma Dewi", email: "rahma@email.com", itemCount: 2, total: 425000, status: "delivered", date: "2024-01-12" },
  { id: "8", customer: "Siti Aminah", email: "siti@email.com", itemCount: 1, total: 189000, status: "processing", date: "2024-01-11" },
  { id: "9", customer: "Zahra Aulia", email: "zahra@email.com", itemCount: 3, total: 567000, status: "delivered", date: "2024-01-11" },
  { id: "10", customer: "Khadijah Putri", email: "khadijah@email.com", itemCount: 1, total: 329000, status: "shipped", date: "2024-01-10" },
  { id: "11", customer: "Maryam Salsabila", email: "maryam@email.com", itemCount: 2, total: 456000, status: "pending", date: "2024-01-10" },
  { id: "12", customer: "Halimah Yusuf", email: "halimah@email.com", itemCount: 4, total: 678000, status: "delivered", date: "2024-01-09" },
]

function formatPrice(price: number) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(price)
}

export default function PurchasePage() {
  const router = useRouter()

  const getStatusBadge = (status: Purchase["status"]) => {
    const styles = {
      pending: "bg-yellow-100 text-yellow-700",
      processing: "bg-blue-100 text-blue-700",
      shipped: "bg-purple-100 text-purple-700",
      delivered: "bg-green-100 text-green-700",
    }
    return (
      <span className={`px-2 py-1 rounded text-xs capitalize ${styles[status]}`}>
        {status}
      </span>
    )
  }

  const columns = [
    {
      key: "customer",
      header: "Customer",
      render: (item: Purchase) => (
        <div>
          <p className="font-medium">{item.customer}</p>
          <p className="text-xs text-muted-foreground">{item.email}</p>
        </div>
      ),
    },
    {
      key: "itemCount",
      header: "Items",
      render: (item: Purchase) => (
        <span>{item.itemCount} product{item.itemCount > 1 ? "s" : ""}</span>
      ),
    },
    {
      key: "total",
      header: "Total",
      render: (item: Purchase) => (
        <span className="font-medium">{formatPrice(item.total)}</span>
      ),
    },
    {
      key: "status",
      header: "Status",
      render: (item: Purchase) => getStatusBadge(item.status),
    },
    {
      key: "date",
      header: "Date",
      render: (item: Purchase) => (
        <span className="text-sm text-muted-foreground">{item.date}</span>
      ),
    },
    {
      key: "actions",
      header: "Detail",
      render: (item: Purchase) => (
        <Button
          variant="ghost"
          size="sm"
          onClick={() => router.push(`/admHaloPoala/dashboard/purchase/${item.id}`)}
        >
          <Eye className="h-4 w-4 mr-1" />
          View
        </Button>
      ),
    },
  ]

  const filterOptions = [
    { value: "pending", label: "Pending" },
    { value: "processing", label: "Processing" },
    { value: "shipped", label: "Shipped" },
    { value: "delivered", label: "Delivered" },
  ]

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold tracking-wide">Purchase</h1>
      
      <DataTable
        data={purchases}
        columns={columns}
        searchPlaceholder="Search customer..."
        searchKey="customer"
        filterOptions={filterOptions}
        filterKey="status"
      />
    </div>
  )
}
