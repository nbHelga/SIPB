import { MainLayout } from "@/components/layout/main-layout"
import { ProductGrid } from "@/components/sections/product-grid"

export default function SalePage() {
  return (
    <MainLayout>
      <div className="container mx-auto px-4 lg:px-8 py-12 lg:py-16">
        <div className="text-center mb-12">
          <h1 className="text-3xl lg:text-4xl font-semibold mb-4 tracking-wider text-destructive">
            SALE
          </h1>
          <p className="text-muted-foreground">
            Discover amazing deals on selected items
          </p>
        </div>
        <ProductGrid category="sale" />
      </div>
    </MainLayout>
  )
}
