import React from "react";
import {
  ColumnDef,
  flexRender,
  getCoreRowModel,
  useReactTable,
} from "@tanstack/react-table";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "~/components/ui/table";
import { Button } from "~/components/ui/button";
import { Input } from "~/components/ui/input";
import { ChevronUp, ChevronDown, Trash2 } from "lucide-react";

interface OrderItem {
  id: string;
  name: string;
  quantity: number;
}

interface OrderComponentProps {
  items: OrderItem[];
  onSubmitOrder: () => void;
  onUpdateQuantity: (id: string, quantity: number) => void;
  onRemoveFromOrder: (id: string) => void;
}

const OrderComponent: React.FC<OrderComponentProps> = ({
  items,
  onSubmitOrder,
  onUpdateQuantity,
  onRemoveFromOrder,
}) => {
  const columns: ColumnDef<OrderItem>[] = [
    {
      accessorKey: "name",
      header: "Product Name",
    },
    {
      accessorKey: "quantity",
      header: "Quantity",
      cell: ({ row }) => {
        const item = row.original;
        return (
          <div className="flex items-center">
            <Input
              type="number"
              min="1"
              value={item.quantity}
              onChange={(e) => {
                const newQuantity = parseInt(e.target.value, 10);
                if (!isNaN(newQuantity) && newQuantity > 0) {
                  onUpdateQuantity(item.id, newQuantity);
                }
              }}
              className="w-16 text-center"
            />
            <div className="flex flex-col ml-1">
              <Button
                variant="ghost"
                size="icon"
                className="h-5 w-5"
                onClick={() => onUpdateQuantity(item.id, item.quantity + 1)}
              >
                <ChevronUp className="h-4 w-4" />
              </Button>
              <Button
                variant="ghost"
                size="icon"
                className="h-5 w-5"
                onClick={() => onUpdateQuantity(item.id, Math.max(1, item.quantity - 1))}
              >
                <ChevronDown className="h-4 w-4" />
              </Button>
            </div>
          </div>
        );
      },
    },
    {
      id: "actions",
      header: "Actions",
      cell: ({ row }) => {
        const item = row.original;
        return (
          <Button
            variant="ghost"
            onClick={() => onRemoveFromOrder(item.id)}
            className="h-8 w-8 p-0"
          >
            <span className="sr-only">Remove</span>
            <Trash2 className="h-4 w-4" />
          </Button>
        );
      },
    },
  ];

  const table = useReactTable({
    data: items,
    columns,
    getCoreRowModel: getCoreRowModel(),
  });

  return (
    <div>
      <h4 className="text-lg font-semibold mb-4">Your Order</h4>
      {items.length === 0 ? (
        <p>No items in your order yet.</p>
      ) : (
        <div className="rounded-md border">
          <Table className="[&_*]:border-0">
            <TableHeader>
              {table.getHeaderGroups().map((headerGroup) => (
                <TableRow key={headerGroup.id}>
                  {headerGroup.headers.map((header) => (
                    <TableHead key={header.id}>
                      {header.isPlaceholder
                        ? null
                        : flexRender(
                            header.column.columnDef.header,
                            header.getContext()
                          )}
                    </TableHead>
                  ))}
                </TableRow>
              ))}
            </TableHeader>
            <TableBody>
              {table.getRowModel().rows?.length ? (
                table.getRowModel().rows.map((row) => (
                  <TableRow
                    key={row.id}
                    data-state={row.getIsSelected() && "selected"}
                  >
                    {row.getVisibleCells().map((cell) => (
                      <TableCell key={cell.id}>
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
                    className="h-24 text-center"
                  >
                    No results.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </div>
      )}
      <Button
        onClick={onSubmitOrder}
        disabled={items.length === 0}
        className="mt-4"
      >
        Submit Order
      </Button>
    </div>
  );
};

export default OrderComponent;
