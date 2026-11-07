"use client"

import { useState } from "react"
import { Search, ChevronLeft, ChevronRight, Filter } from "lucide-react"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"

interface Column<T> {
  key: keyof T | string
  header: string
  render?: (item: T) => React.ReactNode
}

interface FilterOption {
  value: string
  label: string
}

interface DataTableProps<T> {
  data: T[]
  columns: Column<T>[]
  searchPlaceholder?: string
  searchKey?: keyof T
  filterOptions?: FilterOption[]
  filterKey?: keyof T
  onAdd?: () => void
  addButtonText?: string
  itemsPerPage?: number
}

export function DataTable<T extends Record<string, unknown>>({
  data,
  columns,
  searchPlaceholder = "Search...",
  searchKey,
  filterOptions,
  filterKey,
  onAdd,
  addButtonText = "Add",
  itemsPerPage = 10,
}: DataTableProps<T>) {
  const [searchQuery, setSearchQuery] = useState("")
  const [filterValue, setFilterValue] = useState("all")
  const [currentPage, setCurrentPage] = useState(1)

  // Filter data based on search and filter
  const filteredData = data.filter((item) => {
    const matchesSearch = searchKey
      ? String(item[searchKey]).toLowerCase().includes(searchQuery.toLowerCase())
      : true
    const matchesFilter = filterKey && filterValue !== "all"
      ? String(item[filterKey]) === filterValue
      : true
    return matchesSearch && matchesFilter
  })

  // Pagination
  const totalPages = Math.ceil(filteredData.length / itemsPerPage)
  const startIndex = (currentPage - 1) * itemsPerPage
  const paginatedData = filteredData.slice(startIndex, startIndex + itemsPerPage)

  // Generate page numbers
  const getPageNumbers = () => {
    const pages: (number | string)[] = []
    if (totalPages <= 5) {
      for (let i = 1; i <= totalPages; i++) pages.push(i)
    } else {
      if (currentPage <= 3) {
        pages.push(1, 2, 3, 4, "...", totalPages)
      } else if (currentPage >= totalPages - 2) {
        pages.push(1, "...", totalPages - 3, totalPages - 2, totalPages - 1, totalPages)
      } else {
        pages.push(1, "...", currentPage - 1, currentPage, currentPage + 1, "...", totalPages)
      }
    }
    return pages
  }

  return (
    <div className="space-y-4">
      {/* Search, Filter, Add Row */}
      <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <div className="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
          {/* Search */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder={searchPlaceholder}
              value={searchQuery}
              onChange={(e) => {
                setSearchQuery(e.target.value)
                setCurrentPage(1)
              }}
              className="pl-9 w-full sm:w-64 bg-card"
            />
          </div>

          {/* Filter */}
          {filterOptions && (
            <Select 
              value={filterValue} 
              onValueChange={(value) => {
                setFilterValue(value)
                setCurrentPage(1)
              }}
            >
              <SelectTrigger className="w-full sm:w-40 bg-card">
                <Filter className="h-4 w-4 mr-2" />
                <SelectValue placeholder="Filter" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All</SelectItem>
                {filterOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          )}
        </div>

        {/* Add Button */}
        {onAdd && (
          <Button onClick={onAdd} className="w-full sm:w-auto">
            + {addButtonText}
          </Button>
        )}
      </div>

      {/* Table */}
      <div className="rounded-lg border border-border overflow-hidden bg-card">
        <Table>
          <TableHeader>
            <TableRow className="bg-muted/50">
              {columns.map((column) => (
                <TableHead key={String(column.key)} className="font-semibold">
                  {column.header}
                </TableHead>
              ))}
            </TableRow>
          </TableHeader>
          <TableBody>
            {paginatedData.length === 0 ? (
              <TableRow>
                <TableCell colSpan={columns.length} className="text-center py-8 text-muted-foreground">
                  No data found
                </TableCell>
              </TableRow>
            ) : (
              paginatedData.map((item, index) => (
                <TableRow key={index}>
                  {columns.map((column) => (
                    <TableCell key={String(column.key)}>
                      {column.render 
                        ? column.render(item) 
                        : String(item[column.key as keyof T] ?? "")}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex items-center justify-between px-2">
          <p className="text-sm text-muted-foreground">
            Showing {startIndex + 1} to {Math.min(startIndex + itemsPerPage, filteredData.length)} of {filteredData.length} entries
          </p>
          <div className="flex items-center gap-1">
            <Button
              variant="outline"
              size="icon"
              className="h-8 w-8"
              onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
              disabled={currentPage === 1}
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
            {getPageNumbers().map((page, index) => (
              typeof page === "number" ? (
                <Button
                  key={index}
                  variant={currentPage === page ? "default" : "outline"}
                  size="icon"
                  className="h-8 w-8"
                  onClick={() => setCurrentPage(page)}
                >
                  {page}
                </Button>
              ) : (
                <span key={index} className="px-2 text-muted-foreground">...</span>
              )
            ))}
            <Button
              variant="outline"
              size="icon"
              className="h-8 w-8"
              onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
              disabled={currentPage === totalPages}
            >
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        </div>
      )}
    </div>
  )
}
