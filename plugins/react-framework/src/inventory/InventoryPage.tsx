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
      <span>InventoryPage</span>
      <div className="flex flex-col md:flex-row  gap-5">
        <div className="flex flex-col w-full md:w-2/3 rounded-sm gap-5">
          <ProductsTable
            className="bg-white p-3 shadow-md"
            onAddToOrder={addToOrder}
          />
        </div>
        <div className="w-full md:w-1/3 shadow-md p-3 bg-white rounded-sm">
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
