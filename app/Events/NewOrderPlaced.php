<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewOrderPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $orderData;

    public function __construct(Order $order)
    {
        $order->load('table', 'items.menuItem');

        $this->orderData = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'table_name' => $order->table->name ?? '-',
            'customer_name' => $order->customer_name,
            'total' => $order->total,
            'formatted_total' => 'Rp ' . number_format($order->total, 0, ',', '.'),
            'items_count' => $order->items->count(),
            'items' => $order->items->map(fn($item) => [
                'name' => $item->menuItem->name ?? 'Menu dihapus',
                'quantity' => $item->quantity,
            ])->toArray(),
            'notes' => $order->notes,
            'status' => $order->status,
            'created_at' => $order->created_at->format('H:i'),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('admin.orders'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.new';
    }
}
