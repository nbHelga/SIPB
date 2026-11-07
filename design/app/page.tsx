import { MainLayout } from "@/components/layout/main-layout"
import { HeroSection } from "@/components/sections/hero-section"
import { BestSellerSection } from "@/components/sections/bestseller-section"
import { FeedbackSection } from "@/components/sections/feedback-section"

export default function HomePage() {
  return (
    <MainLayout>
      <HeroSection 
        title="NEW COLLECTION"
        subtitle="ELEGANT COMFORT"
        description="Discover our latest collection of premium fashion pieces designed for modern living. Quality craftsmanship meets timeless style."
        ctaText="SHOP NOW"
        ctaLink="/shop"
      />
      
      <BestSellerSection />
      
      <FeedbackSection />
    </MainLayout>
  )
}
