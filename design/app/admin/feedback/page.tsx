"use client"

import { useState } from "react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Trash2, CheckCircle } from "lucide-react"

interface Feedback {
  id: string
  name: string
  email: string
  message: string
  createdAt: string
  isRead: boolean
}

const initialFeedback: Feedback[] = [
  {
    id: "1",
    name: "Sarah Wijaya",
    email: "sarah@email.com",
    message: "Great quality products! Love the fabric and the fit is perfect. Will definitely order again!",
    createdAt: "2024-01-15T10:30:00Z",
    isRead: false
  },
  {
    id: "2",
    name: "Rina Kusuma",
    email: "rina@email.com",
    message: "Fast delivery, very satisfied with my purchase. The packaging was also very nice.",
    createdAt: "2024-01-15T08:15:00Z",
    isRead: false
  },
  {
    id: "3",
    name: "Dewi Lestari",
    email: "dewi@email.com",
    message: "Would love to see more children's options. My daughter loves the designs!",
    createdAt: "2024-01-14T15:45:00Z",
    isRead: true
  },
  {
    id: "4",
    name: "Budi Santoso",
    email: "budi@email.com",
    message: "The sweater I ordered is beautiful but runs a bit small. Consider adding more detailed size guides.",
    createdAt: "2024-01-13T09:20:00Z",
    isRead: true
  }
]

function formatDate(dateString: string): string {
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

export default function FeedbackAdminPage() {
  const [feedback, setFeedback] = useState(initialFeedback)

  const markAsRead = (id: string) => {
    setFeedback(feedback.map(f => 
      f.id === id ? { ...f, isRead: true } : f
    ))
  }

  const deleteFeedback = (id: string) => {
    setFeedback(feedback.filter(f => f.id !== id))
  }

  const unreadCount = feedback.filter(f => !f.isRead).length

  return (
    <div>
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-2xl font-semibold tracking-wider">Customer Feedback</h1>
          <p className="text-muted-foreground mt-1">
            {unreadCount > 0 ? `${unreadCount} unread messages` : "All messages read"}
          </p>
        </div>
      </div>

      <div className="space-y-4">
        {feedback.length === 0 ? (
          <Card>
            <CardContent className="py-12 text-center text-muted-foreground">
              No feedback received yet.
            </CardContent>
          </Card>
        ) : (
          feedback.map(item => (
            <Card key={item.id} className={!item.isRead ? "border-l-4 border-l-foreground" : ""}>
              <CardHeader className="pb-3">
                <div className="flex items-start justify-between">
                  <div>
                    <CardTitle className="text-base flex items-center gap-2">
                      {item.name}
                      {!item.isRead && (
                        <Badge variant="secondary" className="text-xs">New</Badge>
                      )}
                    </CardTitle>
                    <p className="text-sm text-muted-foreground">{item.email}</p>
                  </div>
                  <span className="text-xs text-muted-foreground">
                    {formatDate(item.createdAt)}
                  </span>
                </div>
              </CardHeader>
              <CardContent>
                <p className="text-sm mb-4">{item.message}</p>
                <div className="flex gap-2">
                  {!item.isRead && (
                    <Button 
                      variant="outline" 
                      size="sm"
                      onClick={() => markAsRead(item.id)}
                    >
                      <CheckCircle className="h-4 w-4 mr-2" />
                      Mark as Read
                    </Button>
                  )}
                  <Button 
                    variant="ghost" 
                    size="sm"
                    onClick={() => deleteFeedback(item.id)}
                    className="text-muted-foreground hover:text-destructive"
                  >
                    <Trash2 className="h-4 w-4 mr-2" />
                    Delete
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))
        )}
      </div>
    </div>
  )
}
