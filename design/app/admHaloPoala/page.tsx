"use client"

import { useState } from "react"
import { useRouter } from "next/navigation"
import Image from "next/image"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Eye, EyeOff } from "lucide-react"

export default function AdminLoginPage() {
  const router = useRouter()
  const [username, setUsername] = useState("")
  const [password, setPassword] = useState("")
  const [showPassword, setShowPassword] = useState(false)
  const [error, setError] = useState("")
  const [isLoading, setIsLoading] = useState(false)

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault()
    setIsLoading(true)
    setError("")

    // Simple demo authentication
    if (username === "admin" && password === "admin123") {
      localStorage.setItem("adminAuth", "true")
      router.push("/admHaloPoala/dashboard")
    } else {
      setError("Invalid username or password")
    }
    setIsLoading(false)
  }

  return (
    <div className="min-h-screen relative flex items-center justify-center p-4">
      {/* Background Image */}
      <div className="absolute inset-0 z-0">
        <Image
          src="/admin/bgLogin.png"
          alt="HaloPoala Background"
          fill
          className="object-cover"
          priority
        />
        <div className="absolute inset-0 bg-background/60 backdrop-blur-sm" />
      </div>

      {/* Login Card */}
      <Card className="relative z-10 w-full max-w-md shadow-2xl border-0 bg-card/95 backdrop-blur">
        <CardHeader className="text-center pb-2">
          <div className="flex justify-center mb-4">
            <Image
              src="/logo.png"
              alt="HaloPoala Logo"
              width={60}
              height={60}
              className="object-contain"
            />
          </div>
          <CardTitle className="text-2xl font-semibold tracking-wider">
            HALOPOALA
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">Admin Panel</p>
        </CardHeader>
        <CardContent className="pt-4">
          <form onSubmit={handleLogin} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="username">Username</Label>
              <Input
                id="username"
                type="text"
                placeholder="Enter username"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                className="h-11 bg-muted/50"
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="password">Password</Label>
              <div className="relative">
                <Input
                  id="password"
                  type={showPassword ? "text" : "password"}
                  placeholder="Enter password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="h-11 pr-10 bg-muted/50"
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                >
                  {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
            </div>

            {error && (
              <p className="text-sm text-destructive text-center">{error}</p>
            )}

            <Button
              type="submit"
              className="w-full h-11 bg-primary hover:bg-primary/90 font-medium"
              disabled={isLoading}
            >
              {isLoading ? "Logging in..." : "Login"}
            </Button>
          </form>

          <p className="text-xs text-muted-foreground text-center mt-6">
            Demo: username &quot;admin&quot; / password &quot;admin123&quot;
          </p>
        </CardContent>
      </Card>
    </div>
  )
}
