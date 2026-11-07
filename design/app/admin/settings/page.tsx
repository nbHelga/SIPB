"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { FieldGroup, Field, FieldLabel } from "@/components/ui/field"

export default function SettingsAdminPage() {
  const [settings, setSettings] = useState({
    storeName: "HaloPoala",
    storeDescription: "Elegant fashion for adults and children",
    instagram: "@halopoala",
    tiktok: "@halopoala",
    whatsapp: "+62 812 3456 7890",
    email: "hello@halopoala.com",
    aboutText: "HaloPoala is a fashion brand dedicated to providing elegant and comfortable clothing for the whole family."
  })

  const [isSaving, setIsSaving] = useState(false)
  const [saved, setSaved] = useState(false)

  const handleSave = async () => {
    setIsSaving(true)
    await new Promise(resolve => setTimeout(resolve, 1000))
    console.log("Settings saved:", settings)
    setIsSaving(false)
    setSaved(true)
    setTimeout(() => setSaved(false), 3000)
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-2xl font-semibold tracking-wider">Settings</h1>
          <p className="text-muted-foreground mt-1">
            Manage your store settings and contact information
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

      <div className="grid gap-6">
        {/* Store Information */}
        <Card>
          <CardHeader>
            <CardTitle>Store Information</CardTitle>
            <CardDescription>Basic information about your store</CardDescription>
          </CardHeader>
          <CardContent>
            <FieldGroup>
              <Field>
                <FieldLabel htmlFor="storeName">Store Name</FieldLabel>
                <Input
                  id="storeName"
                  value={settings.storeName}
                  onChange={(e) => setSettings({ ...settings, storeName: e.target.value })}
                />
              </Field>
              <Field>
                <FieldLabel htmlFor="storeDescription">Store Description</FieldLabel>
                <Input
                  id="storeDescription"
                  value={settings.storeDescription}
                  onChange={(e) => setSettings({ ...settings, storeDescription: e.target.value })}
                />
              </Field>
              <Field>
                <FieldLabel htmlFor="aboutText">About Text (Footer)</FieldLabel>
                <Textarea
                  id="aboutText"
                  value={settings.aboutText}
                  onChange={(e) => setSettings({ ...settings, aboutText: e.target.value })}
                  rows={4}
                />
              </Field>
            </FieldGroup>
          </CardContent>
        </Card>

        {/* Contact Information */}
        <Card>
          <CardHeader>
            <CardTitle>Contact Information</CardTitle>
            <CardDescription>Contact details displayed in the footer</CardDescription>
          </CardHeader>
          <CardContent>
            <FieldGroup>
              <div className="grid md:grid-cols-2 gap-4">
                <Field>
                  <FieldLabel htmlFor="instagram">Instagram</FieldLabel>
                  <Input
                    id="instagram"
                    value={settings.instagram}
                    onChange={(e) => setSettings({ ...settings, instagram: e.target.value })}
                    placeholder="@username"
                  />
                </Field>
                <Field>
                  <FieldLabel htmlFor="tiktok">TikTok</FieldLabel>
                  <Input
                    id="tiktok"
                    value={settings.tiktok}
                    onChange={(e) => setSettings({ ...settings, tiktok: e.target.value })}
                    placeholder="@username"
                  />
                </Field>
              </div>
              <div className="grid md:grid-cols-2 gap-4">
                <Field>
                  <FieldLabel htmlFor="whatsapp">WhatsApp Number</FieldLabel>
                  <Input
                    id="whatsapp"
                    value={settings.whatsapp}
                    onChange={(e) => setSettings({ ...settings, whatsapp: e.target.value })}
                    placeholder="+62 xxx xxxx xxxx"
                  />
                </Field>
                <Field>
                  <FieldLabel htmlFor="email">Email</FieldLabel>
                  <Input
                    id="email"
                    type="email"
                    value={settings.email}
                    onChange={(e) => setSettings({ ...settings, email: e.target.value })}
                    placeholder="hello@example.com"
                  />
                </Field>
              </div>
            </FieldGroup>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
