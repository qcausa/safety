"use client";

import * as React from "react";
import {
  ColumnDef,
  ColumnFiltersState,
  GlobalFilterTableState,
  Row,
  SortingState,
  VisibilityState,
  flexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from "@tanstack/react-table";
import { ArrowUpDown, ChevronDown, MoreHorizontal, Plus } from "lucide-react";
import apiFetch from "@wordpress/api-fetch";

import { Button } from "~/components/ui/button";
import { Checkbox } from "~/components/ui/checkbox";
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "~/components/ui/dropdown-menu";
import { Input } from "~/components/ui/input";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "~/components/ui/table";
import { Dialog, DialogContent, DialogTrigger } from "~/components/ui/dialog";
import { cn } from "~/lib/utils";
import { OrderItem } from "~/inventory/InventoryPage";

interface Product {
  id: string;
  name: string;
  categories: {
    id: number;
    name: string;
    slug: string;
  }[];
  images: {
    id: number;
    src: string;
    alt: string;
  }[];
}
// const data: Payment[] = [
//   {
//     id: "m5gr84i9",
//     amount: 316,
//     status: "success",
//     email: "ken99@yahoo.com",
//   },
//   {
//     id: "3u1reuv4",
//     amount: 242,
//     status: "success",
//     email: "Abe45@gmail.com",
//   },
//   {
//     id: "derv1ws0",
//     amount: 837,
//     status: "processing",
//     email: "Monserrat44@gmail.com",
//   },
//   {
//     id: "5kma53ae",
//     amount: 874,
//     status: "success",
//     email: "Silas22@gmail.com",
//   },
//   {
//     id: "bhqecj4p",
//     amount: 721,
//     status: "failed",
//     email: "carmella@hotmail.com",
//   },
// ];

export const columns: ColumnDef<Product>[] = (
  onAddToOrder: (item: OrderItem) => void
): ColumnDef<Product>[] => [
  // {
  //   id: "select",
  //   header: ({ table }) => (
  //     <Checkbox
  //       checked={
  //         table.getIsAllPageRowsSelected() ||
  //         (table.getIsSomePageRowsSelected() && "indeterminate")
  //       }
  //       onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
  //       aria-label="Select all"
  //     />
  //   ),
  //   cell: ({ row }) => (
  //     <Checkbox
  //       checked={row.getIsSelected()}
  //       onCheckedChange={(value) => row.toggleSelected(!!value)}
  //       aria-label="Select row"
  //     />
  //   ),
  //   enableSorting: false,
  //   enableHiding: false,
  // },
  {
    accessorKey: "name",
    header: () => <div className="text-left w-full">Name</div>,
    cell: ({ row }) => (
      <div className="capitalize flex-1">{row.getValue("name")}</div>
    ),
  },
  {
    accessorKey: "categories",
    header: ({ column }) => {
      return (
        <Button
          variant="outline"
          onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
          className="tw-px-0 tw-text-inherit"
        >
          Categories
          <ArrowUpDown className="tw-ml-2 tw-h-4 tw-w-4" />
        </Button>
      );
    },
    cell: ({ row }) => {
      const categories = row.getValue("categories") as Product["categories"];
      return (
        <div>{categories.length > 0 ? categories[0].name : "No Category"}</div>
      );
    },
  },
  {
    accessorKey: "images",
    header: () => <div className="text-left w-full">Product Image</div>,
    cell: ({ row }) => {
      const images = row.getValue("images") as Product["images"];
      const firstImage = images.length > 0 ? images[0] : null;

      return firstImage ? (
        <Dialog>
          <DialogTrigger asChild>
            <img
              src={firstImage.src}
              alt={firstImage.alt || "Product Image"}
              className="w-10 h-10 object-cover cursor-pointer"
            />
          </DialogTrigger>
          <DialogContent className={cn("sm:max-w-[425px]", "z-[10000]")}>
            <img
              src={firstImage.src}
              alt={firstImage.alt || "Product Image"}
              className="w-full h-auto"
            />
          </DialogContent>
        </Dialog>
      ) : (
        <span>No Image</span>
      );
    },
  },
  // {
  //   accessorKey: "user_registered",
  //   header: () => <div className="text-right">Registration Date</div>,
  //   cell: ({ row }) => {
  //     const date = new Date(row.getValue("user_registered"));
  //     console.log("amount:", date);

  //     // Format the date as MM/DD/YY and time as TIME (AM/PM)
  //     const formatted = new Intl.DateTimeFormat("en-US", {
  //       year: "2-digit",
  //       month: "2-digit",
  //       day: "2-digit",
  //       hour: "numeric",
  //       minute: "numeric",
  //       hour12: true, // 12-hour format with AM/PM
  //     }).format(date);

  //     return <div className="text-right font-medium">{formatted}</div>;
  //   },
  // },
  {
    id: "actions",
    enableHiding: false,
    cell: ({ row }) => {
      const product = row.original;

      return (
        <Button
          className="tw-p-2 tw-h-auto tw-text-inherit"
          variant="outline"
          onClick={() =>
            onAddToOrder({
              id: product.id,
              name: product.name,
              quantity: 1,
            })
          }
        >
          <Plus className="h-4 w-4" />
        </Button>
      );
    },
  },
];

// Custom global filter function to handle `name` and `categories`
function globalFilterFn(
  row: Row<Product>,
  columnId: string,
  filterValue: string
) {
  const searchTerm = filterValue.toLowerCase();
  const nameMatch = (row.getValue("name") as string)
    .toLowerCase()
    .includes(searchTerm);

  // Handle categories array
  const categories = row.getValue("categories") as Product["categories"];
  const categoriesMatch = categories.some((category) =>
    category.name.toLowerCase().includes(searchTerm)
  );

  // Return true if the row matches either the name or one of the categories
  return nameMatch || categoriesMatch;
}

interface ProductsTableProps {
  className?: string;
  onAddToOrder: (item: OrderItem) => void;
}

export default function ProductsTable({
  className,
  onAddToOrder,
}: ProductsTableProps) {
  const [sorting, setSorting] = React.useState<SortingState>([]);
  const [columnFilters, setColumnFilters] = React.useState<ColumnFiltersState>(
    []
  );
  // State for global filter
  const [globalFilter, setGlobalFilter] =
    React.useState<GlobalFilterTableState["globalFilter"]>();

  const [columnVisibility, setColumnVisibility] =
    React.useState<VisibilityState>({});
  const [rowSelection, setRowSelection] = React.useState({});

  const [products, setProducts] = React.useState<Product[]>([]);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    async function fetchUsers() {
      setLoading(true);
      try {
        console.log("Fetching users...");
        console.log("mypluginData:", mypluginData);
        const response: Product[] = await apiFetch({
          path: "/wc/v3/products",
          // headers: {
          //   "X-WP-Nonce": mypluginData.nonce,
          // },
        }); // Use your custom endpoint if needed
        setProducts(response);
        console.log("Fetched products:", response);
      } catch (error) {
        console.error("Failed to fetch users:", error);
      } finally {
        setLoading(false);
      }
    }

    fetchUsers();
  }, []);

  const table = useReactTable({
    data: products,
    columns: columns(onAddToOrder),
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onColumnVisibilityChange: setColumnVisibility,
    onRowSelectionChange: setRowSelection,
    onGlobalFilterChange: setGlobalFilter,
    state: {
      sorting,
      columnFilters,
      columnVisibility,
      rowSelection,
      globalFilter,
    },
    globalFilterFn: globalFilterFn, // Custom global filter function
  });

  if (loading) {
    return <div>Loading...</div>;
  }

  return (
    <div className={cn("tw-w-full my-unique-plugin-wrapper-class", className)}>
      <div className="tw-flex tw-items-center tw-py-2">
        <Input
          placeholder="Filter all columns..."
          value={globalFilter ?? ""}
          onChange={(e) => setGlobalFilter(e.target.value)} // Update global filter state
          className="max-w-sm"
        />
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="outline" className="tw-ml-auto tw-text-inherit">
              Columns <ChevronDown className="tw-ml-2 tw-h-4 tw-w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            {table
              .getAllColumns()
              .filter((column) => column.getCanHide())
              .map((column) => {
                return (
                  <DropdownMenuCheckboxItem
                    key={column.id}
                    className="tw-capitalize"
                    checked={column.getIsVisible()}
                    onCheckedChange={(value) =>
                      column.toggleVisibility(!!value)
                    }
                  >
                    {column.id}
                  </DropdownMenuCheckboxItem>
                );
              })}
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
      <div className="tw-rounded-md">
        <Table className="[&_*]:tw-border-0">
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => {
                  return (
                    <TableHead key={header.id}>
                      {header.isPlaceholder
                        ? null
                        : flexRender(
                            header.column.columnDef.header,
                            header.getContext()
                          )}
                    </TableHead>
                  );
                })}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {table.getRowModel().rows?.length ? (
              table.getRowModel().rows.map((row) => (
                <TableRow
                  key={row.id}
                  data-state={row.getIsSelected() && "selected"}
                  className="tw-text-left"
                >
                  {row.getVisibleCells().map((cell) => (
                    <TableCell
                      key={cell.id}
                      className="tw-p-2 tw-px-4 tw-text-left tw-text-xs"
                    >
                      {flexRender(
                        cell.column.columnDef.cell,
                        cell.getContext()
                      )}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell
                  colSpan={columns.length}
                  className="tw-h-24 tw-text-center"
                >
                  No results.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>
      <div className="tw-flex tw-items-center tw-justify-end tw-space-x-2 tw-py-4">
        <div className="tw-flex-1 tw-text-sm tw-text-muted-foreground">
          {table.getFilteredSelectedRowModel().rows.length} of{" "}
          {table.getFilteredRowModel().rows.length} row(s) selected.
        </div>
        <div className="tw-space-x-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => table.previousPage()}
            disabled={!table.getCanPreviousPage()}
          >
            Previous
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => table.nextPage()}
            disabled={!table.getCanNextPage()}
          >
            Next
          </Button>
        </div>
      </div>
    </div>
  );
}
