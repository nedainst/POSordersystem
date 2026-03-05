<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\SiteSetting;
use App\Events\NewOrderPlaced;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Show menu for a specific table (customer scans QR)
     */
    public function show($tableId)
    {
        $table = Table::where('id', $tableId)->where('is_active', true)->firstOrFail();
        $categories = Category::where('is_active', true)
            ->with(['menuItems' => function ($query) {
                $query->where('is_available', true)->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        $featured = MenuItem::where('is_featured', true)
            ->where('is_available', true)
            ->get();

        $settings = SiteSetting::pluck('value', 'key')->toArray();

        return view('customer.menu', compact('table', 'categories', 'featured', 'settings'));
    }

    /**
     * Place an order
     */
    public function order(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:tables,id',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $subtotal = 0;
        $orderItemsData = [];

        foreach ($request->items as $item) {
            $menuItem = MenuItem::findOrFail($item['menu_item_id']);
            $itemSubtotal = $menuItem->price * $item['quantity'];
            $subtotal += $itemSubtotal;

            $orderItemsData[] = [
                'menu_item_id' => $menuItem->id,
                'quantity' => $item['quantity'],
                'price' => $menuItem->price,
                'subtotal' => $itemSubtotal,
                'notes' => $item['notes'] ?? null,
            ];
        }

        $taxRate = (float) SiteSetting::get('tax_rate', 0) / 100;
        $tax = $subtotal * $taxRate;
        $total = $subtotal + $tax;

        $order = Order::create([
            'table_id' => $request->table_id,
            'customer_name' => $request->customer_name,
            'notes' => $request->notes,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'status' => 'pending',
        ]);

        foreach ($orderItemsData as $itemData) {
            $order->items()->create($itemData);
        }

        // Update table status
        Table::where('id', $request->table_id)->update(['status' => 'occupied']);

        // Broadcast new order event (non-blocking)
        try { event(new NewOrderPlaced($order)); } catch (\Throwable $e) { \Log::warning('Broadcast error: ' . $e->getMessage()); }

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dikirim!',
            'order' => $order->load('items.menuItem'),
        ]);
    }

    /**
     * Track order status
     */
    public function trackOrder($orderId)
    {
        $order = Order::with('items.menuItem', 'table', 'payments')->findOrFail($orderId);
        $settings = SiteSetting::pluck('value', 'key')->toArray();
        return view('customer.track-order', compact('order', 'settings'));
    }

    /**
     * Show payment method selection page
     */
    public function paymentPage($orderId)
    {
        $order = Order::with('items.menuItem', 'table')->findOrFail($orderId);

        // Don't show payment page if already paid
        if ($order->payment_status === 'paid') {
            return redirect()->route('order.track', $order->id);
        }

        $settings = SiteSetting::pluck('value', 'key')->toArray();
        return view('customer.payment', compact('order', 'settings'));
    }

    /**
     * Customer selects payment method
     */
    public function selectPayment(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);

        $request->validate([
            'payment_method' => 'required|in:cash,qris,transfer,ewallet',
        ]);

        $order->update([
            'payment_method' => $request->payment_method,
            'payment_status' => $request->payment_method === 'cash' ? 'unpaid' : 'pending_payment',
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->payment_method === 'cash'
                ? 'Silakan lakukan pembayaran di kasir.'
                : 'Metode pembayaran dipilih. Menunggu konfirmasi.',
            'redirect' => route('order.track', $order->id),
        ]);
    }
}
