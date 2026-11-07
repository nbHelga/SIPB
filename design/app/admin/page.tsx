import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Package, ShoppingCart, Users, TrendingUp } from "lucide-react"

export default function AdminDashboard() {
  return (
    <div>
      <h1 className="text-2xl font-semibold mb-8 tracking-wider">Dashboard</h1>
      
      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Total Products
            </CardTitle>
            <Package className="h-5 w-5 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">12</div>
            <p className="text-xs text-muted-foreground mt-1">
              +2 this month
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Total Orders
            </CardTitle>
            <ShoppingCart className="h-5 w-5 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">48</div>
            <p className="text-xs text-muted-foreground mt-1">
              +12 this week
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Customers
            </CardTitle>
            <Users className="h-5 w-5 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">156</div>
            <p className="text-xs text-muted-foreground mt-1">
              +8 this week
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Revenue
            </CardTitle>
            <TrendingUp className="h-5 w-5 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">Rp 24.5M</div>
            <p className="text-xs text-muted-foreground mt-1">
              +18% from last month
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Quick Actions</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <a 
              href="/admin/hero" 
              className="block p-4 border border-border rounded-md hover:bg-accent transition-colors"
            >
              <h3 className="font-medium mb-1">Edit Hero Section</h3>
              <p className="text-sm text-muted-foreground">
                Update the main banner image and text on the landing page
              </p>
            </a>
            <a 
              href="/admin/bestsellers" 
              className="block p-4 border border-border rounded-md hover:bg-accent transition-colors"
            >
              <h3 className="font-medium mb-1">Manage Best Sellers</h3>
              <p className="text-sm text-muted-foreground">
                Choose which products to feature in the Best Seller section
              </p>
            </a>
            <a 
              href="/admin/products" 
              className="block p-4 border border-border rounded-md hover:bg-accent transition-colors"
            >
              <h3 className="font-medium mb-1">Add New Product</h3>
              <p className="text-sm text-muted-foreground">
                Add a new product to your catalog
              </p>
            </a>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Recent Feedback</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="border-b border-border pb-3">
              <p className="text-sm mb-1">{"\"Great quality products! Love the fabric.\""}</p>
              <p className="text-xs text-muted-foreground">- Sarah, 2 hours ago</p>
            </div>
            <div className="border-b border-border pb-3">
              <p className="text-sm mb-1">{"\"Fast delivery, very satisfied!\""}</p>
              <p className="text-xs text-muted-foreground">- Rina, 5 hours ago</p>
            </div>
            <div>
              <p className="text-sm mb-1">{"\"Would love to see more children's options.\""}</p>
              <p className="text-xs text-muted-foreground">- Dewi, 1 day ago</p>
            </div>
            <a 
              href="/admin/feedback" 
              className="block text-sm text-center text-muted-foreground hover:text-foreground transition-colors mt-4"
            >
              View all feedback
            </a>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
