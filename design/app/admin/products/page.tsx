"use client"

import { useState } from "react"
import Image from "next/image"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { Plus, Pencil, Trash2, Search } from "lucide-react"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"
import { FieldGroup, Field, FieldLabel } from "@/components/ui/field"
import { Textarea } from "@/components/ui/textarea"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"

interface Product {
  id: string
  name: string
  price: number
  originalPrice?: number
  category: "adult" | "children"
  image: string
  stock: number
}

const initialProducts: Product[] = [
  { id: "1", name: "Derry Jacket Light Denim", price: 330650, originalPrice: 389000, category: "adult", image: "https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=400&q=80", stock: 25 },
  { id: "2", name: "Fifiya Sweater Blue", price: 237150, originalPrice: 279000, category: "adult", image: "https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=400&q=80", stock: 18 },
  { id: "3", name: "Serena Top Blue", price: 262650, originalPrice: 309000, category: "adult", image: "https://images.unsplash.com/photo-1485968579580-b6d095142e6e?w=400&q=80", stock: 30 },
  { id: "4", name: "Brigita Top White", price: 245650, originalPrice: 289000, category: "adult", image: "https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=400&q=80", stock: 15 },
  { id: "5", name: "Vivienne Dress Dark Denim", price: 262650, originalPrice: 309000, category: "adult", image: "https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=400&q=80", stock: 12 },
  { id: "8", name: "Kids Cotton T-Shirt", price: 129000, category: "children", image: "https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?w=400&q=80", stock: 40 },
  { id: "9", name: "Kids Denim Overalls", price: 199000, category: "children", image: "https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=400&q=80", stock: 22 },
]

function formatPrice(price: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

export default function ProductsAdminPage() {
  const [products, setProducts] = useState(initialProducts)
  const [searchQuery, setSearchQuery] = useState("")
  const [isAddDialogOpen, setIsAddDialogOpen] = useState(false)
  const [newProduct, setNewProduct] = useState({
    name: "",
    price: "",
    originalPrice: "",
    category: "adult" as "adult" | "children",
    description: "",
    stock: ""
  })

  const filteredProducts = products.filter(p => 
    p.name.toLowerCase().includes(searchQuery.toLowerCase())
  )

  const handleAddProduct = () => {
    const product: Product = {
      id: String(Date.now()),
      name: newProduct.name,
      price: Number(newProduct.price),
      originalPrice: newProduct.originalPrice ? Number(newProduct.originalPrice) : undefined,
      category: newProduct.category,
      image: "https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=400&q=80",
      stock: Number(newProduct.stock) || 0
    }
    setProducts([...products, product])
    setNewProduct({ name: "", price: "", originalPrice: "", category: "adult", description: "", stock: "" })
    setIsAddDialogOpen(false)
  }

  const handleDeleteProduct = (id: string) => {
    setProducts(products.filter(p => p.id !== id))
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-2xl font-semibold tracking-wider">Products</h1>
          <p className="text-muted-foreground mt-1">
            Manage your product catalog ({products.length} products)
          </p>
        </div>
        <Dialog open={isAddDialogOpen} onOpenChange={setIsAddDialogOpen}>
          <DialogTrigger asChild>
            <Button className="tracking-widest">
              <Plus className="h-4 w-4 mr-2" />
              ADD PRODUCT
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Add New Product</DialogTitle>
            </DialogHeader>
            <FieldGroup className="mt-4">
              <Field>
                <FieldLabel>Product Name</FieldLabel>
                <Input
                  value={newProduct.name}
                  onChange={(e) => setNewProduct({ ...newProduct, name: e.target.value })}
                  placeholder="e.g., Summer Floral Dress"
                />
              </Field>
              <div className="grid grid-cols-2 gap-4">
                <Field>
                  <FieldLabel>Price (IDR)</FieldLabel>
                  <Input
                    type="number"
                    value={newProduct.price}
                    onChange={(e) => setNewProduct({ ...newProduct, price: e.target.value })}
                    placeholder="299000"
                  />
                </Field>
                <Field>
                  <FieldLabel>Original Price (Optional)</FieldLabel>
                  <Input
                    type="number"
                    value={newProduct.originalPrice}
                    onChange={(e) => setNewProduct({ ...newProduct, originalPrice: e.target.value })}
                    placeholder="For sale items"
                  />
                </Field>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <Field>
                  <FieldLabel>Category</FieldLabel>
                  <Select
                    value={newProduct.category}
                    onValueChange={(value: "adult" | "children") => setNewProduct({ ...newProduct, category: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="adult">Adult</SelectItem>
                      <SelectItem value="children">Children</SelectItem>
                    </SelectContent>
                  </Select>
                </Field>
                <Field>
                  <FieldLabel>Stock</FieldLabel>
                  <Input
                    type="number"
                    value={newProduct.stock}
                    onChange={(e) => setNewProduct({ ...newProduct, stock: e.target.value })}
                    placeholder="0"
                  />
                </Field>
              </div>
              <Field>
                <FieldLabel>Description</FieldLabel>
                <Textarea
                  value={newProduct.description}
                  onChange={(e) => setNewProduct({ ...newProduct, description: e.target.value })}
                  placeholder="Product description..."
                  rows={3}
                />
              </Field>
            </FieldGroup>
            <div className="flex justify-end gap-3 mt-6">
              <Button variant="outline" onClick={() => setIsAddDialogOpen(false)}>
                Cancel
              </Button>
              <Button onClick={handleAddProduct}>
                Add Product
              </Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-center gap-4">
            <div className="relative flex-1 max-w-sm">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search products..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10"
              />
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Product</TableHead>
                <TableHead>Category</TableHead>
                <TableHead>Price</TableHead>
                <TableHead>Stock</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredProducts.map(product => (
                <TableRow key={product.id}>
                  <TableCell>
                    <div className="flex items-center gap-3">
                      <div className="relative w-10 h-12 flex-shrink-0">
                        <Image
                          src={product.image}
                          alt={product.name}
                          fill
                          className="object-cover rounded"
                        />
                      </div>
                      <span className="font-medium">{product.name}</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge variant="secondary" className="capitalize">
                      {product.category}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div>
                      <span className="font-medium">{formatPrice(product.price)}</span>
                      {product.originalPrice && (
                        <span className="text-xs text-muted-foreground line-through ml-2">
                          {formatPrice(product.originalPrice)}
                        </span>
                      )}
                    </div>
                  </TableCell>
                  <TableCell>
                    <span className={product.stock < 10 ? "text-destructive" : ""}>
                      {product.stock}
                    </span>
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex justify-end gap-2">
                      <Button variant="ghost" size="icon">
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button 
                        variant="ghost" 
                        size="icon"
                        onClick={() => handleDeleteProduct(product.id)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  )
}
