"use client"

import { useState } from "react"
import Image from "next/image"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { FieldGroup, Field, FieldLabel } from "@/components/ui/field"
import { Upload } from "lucide-react"

export default function HeroAdminPage() {
  const [heroData, setHeroData] = useState({
    title: "NEW COLLECTION",
    subtitle: "ELEGANT COMFORT",
    description: "Discover our latest collection of premium fashion pieces designed for modern living. Quality craftsmanship meets timeless style.",
    ctaText: "SHOP NOW",
    ctaLink: "/shop",
    backgroundImage: "/mainimage.png"
  })

  const [isSaving, setIsSaving] = useState(false)
  const [saved, setSaved] = useState(false)

  const handleSave = async () => {
    setIsSaving(true)
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    console.log("Hero data saved:", heroData)
    setIsSaving(false)
    setSaved(true)
    setTimeout(() => setSaved(false), 3000)
  }

  const handleImageUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      // In a real app, this would upload to storage
      const url = URL.createObjectURL(file)
      setHeroData({ ...heroData, backgroundImage: url })
    }
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-2xl font-semibold tracking-wider">Hero Section</h1>
          <p className="text-muted-foreground mt-1">
            Manage the main banner on your landing page
          </p>
        </div>
        <Button 
          onClick={handleSave} 
          disabled={isSaving}
          className="tracking-widest"
        >
          {isSaving ? "SAVING..." : saved ? "SAVED!" : "SAVE CHANGES"}
        </Button>
      </div>

      <div className="grid lg:grid-cols-2 gap-8">
        {/* Preview */}
        <Card>
          <CardHeader>
            <CardTitle>Preview</CardTitle>
            <CardDescription>How it will look on the website</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="relative aspect-video overflow-hidden rounded-md">
              <Image
                src={heroData.backgroundImage}
                alt="Hero Preview"
                fill
                className="object-cover"
              />
              <div className="absolute bottom-4 left-4 max-w-[60%]">
                <div className="bg-foreground/95 text-primary-foreground p-4 text-xs">
                  <span className="text-[10px] tracking-[0.2em] text-primary-foreground/70">
                    {heroData.title}
                  </span>
                  <h2 className="text-sm font-semibold mt-1 mb-2">
                    {heroData.subtitle}
                  </h2>
                  <p className="text-[10px] text-primary-foreground/80 mb-2 line-clamp-2">
                    {heroData.description}
                  </p>
                  <span className="text-[10px] border border-primary-foreground/50 px-2 py-1">
                    {heroData.ctaText}
                  </span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Edit Form */}
        <Card>
          <CardHeader>
            <CardTitle>Edit Content</CardTitle>
            <CardDescription>Update the hero section content</CardDescription>
          </CardHeader>
          <CardContent>
            <FieldGroup>
              <Field>
                <FieldLabel>Background Image</FieldLabel>
                <div className="flex items-center gap-4">
                  <label className="flex items-center gap-2 px-4 py-2 border border-border rounded-md cursor-pointer hover:bg-accent transition-colors">
                    <Upload className="h-4 w-4" />
                    <span className="text-sm">Upload Image</span>
                    <input
                      type="file"
                      accept="image/*"
                      onChange={handleImageUpload}
                      className="hidden"
                    />
                  </label>
                  <span className="text-xs text-muted-foreground">
                    Recommended: 1920x1080px
                  </span>
                </div>
              </Field>

              <Field>
                <FieldLabel htmlFor="title">Title (Small Text)</FieldLabel>
                <Input
                  id="title"
                  value={heroData.title}
                  onChange={(e) => setHeroData({ ...heroData, title: e.target.value })}
                  placeholder="e.g., NEW COLLECTION"
                />
              </Field>

              <Field>
                <FieldLabel htmlFor="subtitle">Subtitle (Main Heading)</FieldLabel>
                <Input
                  id="subtitle"
                  value={heroData.subtitle}
                  onChange={(e) => setHeroData({ ...heroData, subtitle: e.target.value })}
                  placeholder="e.g., ELEGANT COMFORT"
                />
              </Field>

              <Field>
                <FieldLabel htmlFor="description">Description</FieldLabel>
                <Textarea
                  id="description"
                  value={heroData.description}
                  onChange={(e) => setHeroData({ ...heroData, description: e.target.value })}
                  placeholder="Brief description of your collection..."
                  rows={3}
                />
              </Field>

              <Field>
                <FieldLabel htmlFor="ctaText">Button Text</FieldLabel>
                <Input
                  id="ctaText"
                  value={heroData.ctaText}
                  onChange={(e) => setHeroData({ ...heroData, ctaText: e.target.value })}
                  placeholder="e.g., SHOP NOW"
                />
              </Field>

              <Field>
                <FieldLabel htmlFor="ctaLink">Button Link</FieldLabel>
                <Input
                  id="ctaLink"
                  value={heroData.ctaLink}
                  onChange={(e) => setHeroData({ ...heroData, ctaLink: e.target.value })}
                  placeholder="e.g., /shop"
                />
              </Field>
            </FieldGroup>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
