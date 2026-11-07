"use client"

import { useState } from "react"
import { useRouter } from "next/navigation"
import { ArrowLeft, Plus, X, Upload } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import Link from "next/link"

interface SizeStock {
  size: string
  stock: number
}

const availableSizes = ["XS", "S", "M", "L", "XL", "XXL"]

export default function AddKatalogPage() {
  const router = useRouter()
  const [name, setName] = useState("")
  const [price, setPrice] = useState("")
  const [category, setCategory] = useState("")
  const [sizeStocks, setSizeStocks] = useState<SizeStock[]>([{ size: "M", stock: 0 }])
  const [image, setImage] = useState<string | null>(null)

  const addSizeStock = () => {
    const usedSizes = sizeStocks.map(s => s.size)
    const availableSize = availableSizes.find(s => !usedSizes.includes(s))
    if (availableSize) {
      setSizeStocks([...sizeStocks, { size: availableSize, stock: 0 }])
    }
  }

  const removeSizeStock = (index: number) => {
    if (sizeStocks.length > 1) {
      setSizeStocks(sizeStocks.filter((_, i) => i !== index))
    }
  }

  const updateSizeStock = (index: number, field: keyof SizeStock, value: string | number) => {
    const updated = [...sizeStocks]
    if (field === "size") {
      updated[index].size = value as string
    } else {
      updated[index].stock = Number(value)
    }
    setSizeStocks(updated)
  }

  const handleImageUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      const reader = new FileReader()
      reader.onloadend = () => {
        setImage(reader.result as string)
      }
      reader.readAsDataURL(file)
    }
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    // In a real app, this would save to the database
    console.log({ name, price, category, sizeStocks, image })
    router.push("/admHaloPoala/dashboard/katalog")
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Link href="/admHaloPoala/dashboard/katalog">
          <Button variant="ghost" size="icon">
            <ArrowLeft className="h-5 w-5" />
          </Button>
        </Link>
        <h1 className="text-2xl font-semibold tracking-wide">Add Product</h1>
      </div>

      {/* Form */}
      <Card>
        <CardHeader>
          <CardTitle>Product Information</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-6">
            {/* Name */}
            <div className="space-y-2">
              <Label htmlFor="name">Product Name</Label>
              <Input
                id="name"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Enter product name"
                required
                className="bg-muted/50"
              />
            </div>

            {/* Price */}
            <div className="space-y-2">
              <Label htmlFor="price">Price (IDR)</Label>
              <Input
                id="price"
                type="number"
                value={price}
                onChange={(e) => setPrice(e.target.value)}
                placeholder="Enter price"
                required
                className="bg-muted/50"
              />
            </div>

            {/* Category */}
            <div className="space-y-2">
              <Label>Category</Label>
              <Select value={category} onValueChange={setCategory} required>
                <SelectTrigger className="bg-muted/50">
                  <SelectValue placeholder="Select category" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="adult">Adult</SelectItem>
                  <SelectItem value="children">Children</SelectItem>
                </SelectContent>
              </Select>
            </div>

            {/* Size & Stock */}
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <Label>Size & Stock</Label>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={addSizeStock}
                  disabled={sizeStocks.length >= availableSizes.length}
                >
                  <Plus className="h-4 w-4 mr-1" />
                  Add Size
                </Button>
              </div>
              <div className="space-y-3">
                {sizeStocks.map((item, index) => (
                  <div key={index} className="flex items-center gap-3">
                    <Select
                      value={item.size}
                      onValueChange={(value) => updateSizeStock(index, "size", value)}
                    >
                      <SelectTrigger className="w-24 bg-muted/50">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {availableSizes.map((size) => (
                          <SelectItem 
                            key={size} 
                            value={size}
                            disabled={sizeStocks.some((s, i) => i !== index && s.size === size)}
                          >
                            {size}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <Input
                      type="number"
                      value={item.stock}
                      onChange={(e) => updateSizeStock(index, "stock", e.target.value)}
                      placeholder="Stock"
                      className="flex-1 bg-muted/50"
                      min={0}
                    />
                    {sizeStocks.length > 1 && (
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() => removeSizeStock(index)}
                        className="text-destructive hover:text-destructive"
                      >
                        <X className="h-4 w-4" />
                      </Button>
                    )}
                  </div>
                ))}
              </div>
            </div>

            {/* Image Upload */}
            <div className="space-y-2">
              <Label>Product Image</Label>
              <div className="border-2 border-dashed border-border rounded-lg p-6 text-center">
                {image ? (
                  <div className="relative">
                    <img
                      src={image}
                      alt="Preview"
                      className="max-h-48 mx-auto rounded"
                    />
                    <Button
                      type="button"
                      variant="destructive"
                      size="sm"
                      className="absolute top-2 right-2"
                      onClick={() => setImage(null)}
                    >
                      <X className="h-4 w-4" />
                    </Button>
                  </div>
                ) : (
                  <label className="cursor-pointer">
                    <div className="flex flex-col items-center gap-2 text-muted-foreground">
                      <Upload className="h-8 w-8" />
                      <span className="text-sm">Click to upload image</span>
                      <span className="text-xs">PNG, JPG up to 5MB</span>
                    </div>
                    <input
                      type="file"
                      accept="image/*"
                      onChange={handleImageUpload}
                      className="hidden"
                    />
                  </label>
                )}
              </div>
            </div>

            {/* Actions */}
            <div className="flex justify-end gap-3 pt-4">
              <Link href="/admHaloPoala/dashboard/katalog">
                <Button type="button" variant="outline">
                  Cancel
                </Button>
              </Link>
              <Button type="submit">
                Save Product
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
