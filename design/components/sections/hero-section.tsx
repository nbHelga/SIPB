"use client"

import Image from "next/image"
import Link from "next/link"
import { useState } from "react"
import { Button } from "@/components/ui/button"

interface HeroSectionProps {
  title?: string
  subtitle?: string
  description?: string
  ctaText?: string
  ctaLink?: string
  backgroundImage?: string
}

export function HeroSection({
  title = "NEW COLLECTION",
  subtitle = "ELEGANT COMFORT",
  description = "Discover our latest collection of premium fashion pieces designed for modern living.",
  ctaText = "SHOP NOW",
  ctaLink = "/shop",
  backgroundImage = "/mainimage.png"
}: HeroSectionProps) {
  const [imageError, setImageError] = useState(false)
  
  return (
    <section className="relative w-full h-[80vh] min-h-[600px] overflow-hidden">
      {/* Background Image */}
      <div className="absolute inset-0">
        <Image
          src={imageError ? "https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=1920&q=80" : backgroundImage}
          alt="HaloPoala Collection"
          fill
          className="object-cover object-center"
          priority
          onError={() => setImageError(true)}
        />
        <div className="absolute inset-0 bg-gradient-to-r from-black/20 to-transparent" />
      </div>

      {/* Content Overlay Box */}
      <div className="absolute bottom-12 left-8 lg:bottom-20 lg:left-16 max-w-md lg:max-w-lg">
        <div className="bg-foreground/95 text-primary-foreground p-8 lg:p-12">
          <span className="text-xs lg:text-sm tracking-[0.3em] text-primary-foreground/70">
            {title}
          </span>
          <h1 className="text-3xl lg:text-5xl font-semibold mt-2 mb-4 leading-tight">
            {subtitle}
          </h1>
          <p className="text-sm lg:text-base text-primary-foreground/80 mb-6 leading-relaxed">
            {description}
          </p>
          <Link href={ctaLink}>
            <Button 
              variant="outline" 
              className="bg-transparent border-primary-foreground text-primary-foreground hover:bg-primary-foreground hover:text-foreground tracking-widest px-8 py-6"
            >
              {ctaText}
            </Button>
          </Link>
        </div>
      </div>
    </section>
  )
}
