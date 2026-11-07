import { MainLayout } from "@/components/layout/main-layout"
import { ProductGrid } from "@/components/sections/product-grid"

export default function AdultShopPage() {
  return (
    <MainLayout>
      <div className="container mx-auto px-4 lg:px-8 py-12 lg:py-16">
        <h1 className="text-3xl lg:text-4xl font-semibold text-center mb-12 tracking-wider">
          ADULT COLLECTION
        </h1>
        <ProductGrid category="adult" />
      </div>
    </MainLayout>
  )
}
