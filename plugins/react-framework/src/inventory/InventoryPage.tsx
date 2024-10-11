import React, { useState } from "react";
import ProductsTable from "~/products/products-table";
import OrderComponent from "./OrderComponent";

import { toast } from "~/hooks/use-toast";

export interface OrderItem {
  id: string;
  name: string;
  quantity: number;
}

function InventoryPage() {
  const [orderItems, setOrderItems] = useState<OrderItem[]>([]);

  const addToOrder = (item: OrderItem) => {
    setOrderItems((prevItems) => {
      const existingItem = prevItems.find((i) => i.id === item.id);
      if (existingItem) {
        toast({
          title: "Item quantity updated",
          description: `${item.name} quantity increased to ${
            existingItem.quantity + 1
          }`,
        });
        return prevItems.map((i) =>
          i.id === item.id ? { ...i, quantity: i.quantity + 1 } : i
        );
      }
      toast({
        title: "Item added to order",
        description: `${item.name} added to your order`,
      });
      return [...prevItems, item];
    });
  };

  const updateQuantity = (id: string, quantity: number) => {
    setOrderItems((prevItems) =>
      prevItems.map((item) => (item.id === id ? { ...item, quantity } : item))
    );
  };

  const removeFromOrder = (id: string) => {
    setOrderItems((prevItems) => prevItems.filter((item) => item.id !== id));
    toast({
      title: "Item removed from order",
      description: "The item has been removed from your order",
    });
  };

  const submitOrder = () => {
    // Implement order submission logic here
    console.log("Submitting order:", orderItems);
    // Reset the order after submission
    setOrderItems([]);
  };

  return (
    <div className="my-unique-plugin-wrapper-class">
      <span className="tw-bg-blue-100">InventoryPage</span>
      <div className="tw-flex tw-flex-col md:tw-flex-row tw-gap-5">
        <div className="tw-flex tw-flex-col tw-w-full md:tw-w-2/3 tw-rounded-sm tw-gap-5">
          <ProductsTable
            className="tw-bg-white tw-p-3 tw-shadow-md"
            onAddToOrder={addToOrder}
          />
        </div>
        <div className="tw-w-full md:tw-w-1/3 tw-shadow-md tw-p-3 tw-bg-white tw-rounded-sm">
          <OrderComponent
            items={orderItems}
            onSubmitOrder={submitOrder}
            onUpdateQuantity={updateQuantity}
            onRemoveFromOrder={removeFromOrder}
          />
        </div>
      </div>
    </div>
  );
}

export default InventoryPage;
