"use client"

import { useState } from "react"
import Image from "next/image"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { DataTable } from "@/components/admin/data-table"

interface Payment {
  id: string
  customer: string
  email: string
  total: number
  image: string
  status: "pending" | "approved" | "rejected"
  date: string
}

// Dummy payment data
const initialPayments: Payment[] = [
  { id: "1", customer: "Sarah Putri", email: "sarah@email.com", total: 508300, image: "https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=100&q=80", status: "pending", date: "2024-01-15" },
  { id: "2", customer: "Maya Sari", email: "maya@email.com", total: 262650, image: "https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=100&q=80", status: "approved", date: "2024-01-14" },
  { id: "3", customer: "Dina Rahmawati", email: "dina@email.com", total: 787950, image: "https://images.unsplash.com/photo-1562157873-818bc0726f68?w=100&q=80", status: "approved", date: "2024-01-14" },
  { id: "4", customer: "Aisyah Nur", email: "aisyah@email.com", total: 245650, image: "https://images.unsplash.com/photo-1485462537746-965f33f7f6a7?w=100&q=80", status: "rejected", date: "2024-01-13" },
  { id: "5", customer: "Fatimah Zahra", email: "fatimah@email.com", total: 598000, image: "https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?w=100&q=80", status: "pending", date: "2024-01-13" },
  { id: "6", customer: "Nur Aini", email: "aini@email.com", total: 378000, image: "https://images.unsplash.com/photo-1551028719-00167b16eac5?w=100&q=80", status: "pending", date: "2024-01-12" },
  { id: "7", customer: "Rahma Dewi", email: "rahma@email.com", total: 425000, image: "https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=100&q=80", status: "approved", date: "2024-01-12" },
  { id: "8", customer: "Siti Aminah", email: "siti@email.com", total: 189000, image: "https://images.unsplash.com/photo-1518831959646-742c3a14ebf7?w=100&q=80", status: "pending", date: "2024-01-11" },
  { id: "9", customer: "Zahra Aulia", email: "zahra@email.com", total: 567000, image: "https://images.unsplash.com/photo-1522771930-78848d9293e8?w=100&q=80", status: "approved", date: "2024-01-11" },
  { id: "10", customer: "Khadijah Putri", email: "khadijah@email.com", total: 329000, image: "https://images.unsplash.com/photo-1485462537746-965f33f7f6a7?w=100&q=80", status: "rejected", date: "2024-01-10" },
  { id: "11", customer: "Maryam Salsabila", email: "maryam@email.com", total: 456000, image: "https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=100&q=80", status: "pending", date: "2024-01-10" },
  { id: "12", customer: "Halimah Yusuf", email: "halimah@email.com", total: 678000, image: "https://images.unsplash.com/photo-1562157873-818bc0726f68?w=100&q=80", status: "approved", date: "2024-01-09" },
]

function formatPrice(price: number) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(price)
}

export default function PaymentPage() {
  const [payments, setPayments] = useState(initialPayments)

  const updateStatus = (id: string, status: Payment["status"]) => {
    setPayments(payments.map(p => 
      p.id === id ? { ...p, status } : p
    ))
  }

  const getStatusColor = (status: Payment["status"]) => {
    switch (status) {
      case "approved": return "bg-green-100 text-green-700 border-green-200"
      case "rejected": return "bg-red-100 text-red-700 border-red-200"
      default: return "bg-yellow-100 text-yellow-700 border-yellow-200"
    }
  }

  const columns = [
    {
      key: "image",
      header: "Image",
      render: (item: Payment) => (
        <div className="relative w-12 h-12 rounded overflow-hidden bg-muted">
          <Image
            src={item.image}
            alt={item.customer}
            fill
            className="object-cover"
          />
        </div>
      ),
    },
    {
      key: "customer",
      header: "Customer",
      render: (item: Payment) => (
        <div>
          <p className="font-medium">{item.customer}</p>
          <p className="text-xs text-muted-foreground">{item.email}</p>
        </div>
      ),
    },
    {
      key: "total",
      header: "Total",
      render: (item: Payment) => (
        <span className="font-medium">{formatPrice(item.total)}</span>
      ),
    },
    {
      key: "date",
      header: "Date",
      render: (item: Payment) => (
        <span className="text-sm text-muted-foreground">{item.date}</span>
      ),
    },
    {
      key: "status",
      header: "Status",
      render: (item: Payment) => (
        <Select
          value={item.status}
          onValueChange={(value) => updateStatus(item.id, value as Payment["status"])}
        >
          <SelectTrigger className={`w-28 h-8 text-xs border ${getStatusColor(item.status)}`}>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="approved">Approved</SelectItem>
            <SelectItem value="rejected">Rejected</SelectItem>
          </SelectContent>
        </Select>
      ),
    },
  ]

  const filterOptions = [
    { value: "pending", label: "Pending" },
    { value: "approved", label: "Approved" },
    { value: "rejected", label: "Rejected" },
  ]

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold tracking-wide">Payment</h1>
      
      <DataTable
        data={payments}
        columns={columns}
        searchPlaceholder="Search customer..."
        searchKey="customer"
        filterOptions={filterOptions}
        filterKey="status"
      />
    </div>
  )
}
