"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { FieldGroup, Field, FieldLabel } from "@/components/ui/field"

export function FeedbackSection() {
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    message: ""
  })
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [isSubmitted, setIsSubmitted] = useState(false)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setIsSubmitting(true)
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    console.log("Feedback submitted:", formData)
    setIsSubmitted(true)
    setIsSubmitting(false)
    setFormData({ name: "", email: "", message: "" })
    
    // Reset success message after 3 seconds
    setTimeout(() => setIsSubmitted(false), 3000)
  }

  return (
    <section className="py-16 lg:py-24 px-4 lg:px-8 bg-secondary">
      <div className="container mx-auto max-w-2xl">
        {/* Section Title */}
        <div className="text-center mb-10">
          <h2 className="text-3xl lg:text-4xl font-semibold mb-4 tracking-wider">
            SHARE YOUR THOUGHTS
          </h2>
          <p className="text-muted-foreground">
            We value your feedback. Share your suggestions and help us improve.
          </p>
        </div>

        {/* Feedback Form */}
        <form onSubmit={handleSubmit} className="bg-card p-8 lg:p-10 shadow-sm">
          <FieldGroup>
            <Field>
              <FieldLabel htmlFor="name">Name</FieldLabel>
              <Input
                id="name"
                type="text"
                placeholder="Your name"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                required
                className="bg-background"
              />
            </Field>

            <Field>
              <FieldLabel htmlFor="email">Email</FieldLabel>
              <Input
                id="email"
                type="email"
                placeholder="your@email.com"
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                required
                className="bg-background"
              />
            </Field>

            <Field>
              <FieldLabel htmlFor="message">Message</FieldLabel>
              <Textarea
                id="message"
                placeholder="Your suggestions or feedback..."
                value={formData.message}
                onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                required
                rows={5}
                className="bg-background resize-none"
              />
            </Field>
          </FieldGroup>

          <Button 
            type="submit" 
            disabled={isSubmitting}
            className="w-full mt-6 tracking-widest py-6 bg-foreground text-primary-foreground hover:bg-foreground/90"
          >
            {isSubmitting ? "SENDING..." : "SEND FEEDBACK"}
          </Button>

          {isSubmitted && (
            <p className="mt-4 text-center text-sm text-green-600">
              Thank you for your feedback!
            </p>
          )}
        </form>
      </div>
    </section>
  )
}
