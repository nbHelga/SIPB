"use client"

import Image from "next/image"
import Link from "next/link"
import { Instagram, Mail, Phone } from "lucide-react"
import { useState } from "react"

// TikTok icon component
function TikTokIcon({ className }: { className?: string }) {
  return (
    <svg 
      className={className} 
      viewBox="0 0 24 24" 
      fill="currentColor"
    >
      <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z"/>
    </svg>
  )
}

export function Footer() {
  const [footerImageError, setFooterImageError] = useState(false)
  
  return (
    <footer className="bg-foreground text-primary-foreground py-16 lg:py-20">
      <div className="container mx-auto px-4 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-12 lg:gap-8">
          {/* Left - About */}
          <div>
            <h3 className="text-lg font-semibold mb-6 tracking-wider">ABOUT HALOPOALA</h3>
            <p className="text-primary-foreground/70 text-sm leading-relaxed">
              HaloPoala is a fashion brand dedicated to providing elegant and comfortable 
              clothing for the whole family. We believe in quality craftsmanship and 
              timeless design that transcends seasons.
            </p>
            <p className="text-primary-foreground/70 text-sm leading-relaxed mt-4">
              From everyday essentials to special occasion pieces, we create fashion 
              that makes you feel confident and beautiful.
            </p>
          </div>

          {/* Center - Image */}
          <div className="flex justify-center items-center">
            <div className="relative w-full max-w-[280px] aspect-[3/4] overflow-hidden">
              <Image
                src={footerImageError ? "https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=400&q=80" : "/footerImage.png"}
                alt="HaloPoala Model"
                fill
                className="object-cover"
                onError={() => setFooterImageError(true)}
              />
            </div>
          </div>

          {/* Right - Contact */}
          <div>
            <h3 className="text-lg font-semibold mb-6 tracking-wider">CONTACT US</h3>
            <div className="space-y-4">
              {/* Instagram */}
              <Link 
                href="https://instagram.com/halopoala" 
                target="_blank"
                className="flex items-center gap-3 text-primary-foreground/70 hover:text-primary-foreground transition-colors"
              >
                <Instagram className="h-5 w-5" />
                <span className="text-sm">@halopoala</span>
              </Link>

              {/* TikTok */}
              <Link 
                href="https://tiktok.com/@halopoala" 
                target="_blank"
                className="flex items-center gap-3 text-primary-foreground/70 hover:text-primary-foreground transition-colors"
              >
                <TikTokIcon className="h-5 w-5" />
                <span className="text-sm">@halopoala</span>
              </Link>

              {/* WhatsApp */}
              <Link 
                href="https://wa.me/6281234567890" 
                target="_blank"
                className="flex items-center gap-3 text-primary-foreground/70 hover:text-primary-foreground transition-colors"
              >
                <Phone className="h-5 w-5" />
                <span className="text-sm">+62 812 3456 7890</span>
              </Link>

              {/* Email */}
              <Link 
                href="mailto:hello@halopoala.com"
                className="flex items-center gap-3 text-primary-foreground/70 hover:text-primary-foreground transition-colors"
              >
                <Mail className="h-5 w-5" />
                <span className="text-sm">hello@halopoala.com</span>
              </Link>
            </div>
          </div>
        </div>

        {/* Copyright */}
        <div className="mt-12 pt-8 border-t border-primary-foreground/20 text-center">
          <p className="text-primary-foreground/50 text-sm">
            &copy; {new Date().getFullYear()} HaloPoala. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  )
}
