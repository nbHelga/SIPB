"use client"

import { createContext, useContext, useState, ReactNode } from "react"

export interface CartItem {
  id: string
  name: string
  price: number
  originalPrice?: number
  image: string
  size: string
  quantity: number
  discount?: number
}

interface CartContextType {
  items: CartItem[]
  isOpen: boolean
  openCart: () => void
  closeCart: () => void
  toggleCart: () => void
  addItem: (item: CartItem) => void
  removeItem: (id: string, size: string) => void
  updateQuantity: (id: string, size: string, quantity: number) => void
  clearCart: () => void
  totalItems: number
  totalPrice: number
  totalOriginalPrice: number
}

const CartContext = createContext<CartContextType | undefined>(undefined)

// Dummy cart items
const initialCartItems: CartItem[] = [
  {
    id: "1",
    name: "Aderyn Cardigan Blue",
    price: 254150,
    originalPrice: 299000,
    image: "https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=400&q=80",
    size: "M",
    quantity: 1,
    discount: 15
  },
  {
    id: "2",
    name: "Vivienne Dress Dark Denim",
    price: 262650,
    originalPrice: 309000,
    image: "https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=400&q=80",
    size: "S/M",
    quantity: 1,
    discount: 15
  }
]

export function CartProvider({ children }: { children: ReactNode }) {
  const [items, setItems] = useState<CartItem[]>(initialCartItems)
  const [isOpen, setIsOpen] = useState(false)

  const openCart = () => setIsOpen(true)
  const closeCart = () => setIsOpen(false)
  const toggleCart = () => setIsOpen(!isOpen)

  const addItem = (item: CartItem) => {
    setItems(prev => {
      const existingIndex = prev.findIndex(i => i.id === item.id && i.size === item.size)
      if (existingIndex > -1) {
        const updated = [...prev]
        updated[existingIndex].quantity += item.quantity
        return updated
      }
      return [...prev, item]
    })
  }

  const removeItem = (id: string, size: string) => {
    setItems(prev => prev.filter(item => !(item.id === id && item.size === size)))
  }

  const updateQuantity = (id: string, size: string, quantity: number) => {
    if (quantity <= 0) {
      removeItem(id, size)
      return
    }
    setItems(prev => prev.map(item => 
      item.id === id && item.size === size ? { ...item, quantity } : item
    ))
  }

  const clearCart = () => setItems([])

  const totalItems = items.reduce((sum, item) => sum + item.quantity, 0)
  const totalPrice = items.reduce((sum, item) => sum + (item.price * item.quantity), 0)
  const totalOriginalPrice = items.reduce((sum, item) => sum + ((item.originalPrice || item.price) * item.quantity), 0)

  return (
    <CartContext.Provider value={{
      items,
      isOpen,
      openCart,
      closeCart,
      toggleCart,
      addItem,
      removeItem,
      updateQuantity,
      clearCart,
      totalItems,
      totalPrice,
      totalOriginalPrice
    }}>
      {children}
    </CartContext.Provider>
  )
}

export function useCart() {
  const context = useContext(CartContext)
  if (!context) {
    throw new Error("useCart must be used within a CartProvider")
  }
  return context
}
