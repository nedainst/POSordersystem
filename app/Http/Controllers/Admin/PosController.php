<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Table;
use App\Models\SiteSetting;
use App\Events\PaymentReceived;
use App\Events\OrderStatusUpdated;
use App\Events\NewOrderPlaced;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    /**
     * Show the POS interface
     */
    public function index()
    {
        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $menuItems = MenuItem::where('is_available', true)
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $tables = Table::where('is_active', true)
            ->orderBy('name')
            ->get();

        $settings = SiteSetting::pluck('value', 'key')->toArray();
        $taxRate = floatval($settings['tax_rate'] ?? 11);

        $menuItemsJson = $menuItems->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'price' => floatval($item->price),
                'formatted_price' => $item->formatted_price,
                'image' => $item->image ? asset('storage/' . $item->image) : null,
                'category_id' => $item->category_id,
                'category' => $item->category->name ?? '-',
                'description' => $item->description,
                'is_available' => $item->is_available,
            ];
        })->values();

        return view('admin.pos.index', compact('categories', 'menuItems', 'menuItemsJson', 'tables', 'settings', 'taxRate'));
    }

    /**
     * Search menu items (AJAX)
     */
    public function searchItems(Request $request)
    {
        $query = MenuItem::where('is_available', true)->with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->orderBy('sort_order')->orderBy('name')->get();

        return response()->json([
            'items' => $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => floatval($item->price),
                    'formatted_price' => $item->formatted_price,
                    'image' => $item->image ? asset('storage/' . $item->image) : null,
                    'category' => $item->category->name ?? '-',
                    'description' => $item->description,
                    'is_available' => $item->is_available,
                ];
            }),
        ]);
    }

    /**
     * Process POS order + payment in one step (AJAX)
     */
    public function processOrder(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:tables,id',
            'customer_name' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string|max:255',
            'payment_method' => 'required|in:cash,qris,transfer,ewallet',
            'paid_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $settings = SiteSetting::pluck('value', 'key')->toArray();
            $taxRate = floatval($settings['tax_rate'] ?? 11) / 100;

            // Calculate totals
            $subtotal = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $menuItem = MenuItem::findOrFail($item['menu_item_id']);
                $qty = $item['quantity'];
                $itemSubtotal = $menuItem->price * $qty;
                $subtotal += $itemSubtotal;

                $orderItems[] = [
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $qty,
                    'price' => $menuItem->price,
                    'subtotal' => $itemSubtotal,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            $tax = round($subtotal * $taxRate);
            $total = $subtotal + $tax;
            $paidAmount = $request->paid_amount;
            $changeAmount = max(0, $paidAmount - $total);

            // For non-cash, paid = total
            if ($request->payment_method !== 'cash') {
                $paidAmount = $total;
                $changeAmount = 0;
            }

            // Validate cash
            if ($request->payment_method === 'cash' && $paidAmount < $total) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Jumlah pembayaran kurang dari total.',
                ], 422);
            }

            // Create order — confirmed + served immediately from POS
            $order = Order::create([
                'table_id' => $request->table_id,
                'customer_name' => $request->customer_name ?: 'Walk-in',
                'notes' => $request->notes,
                'status' => 'served',
                'payment_status' => $request->payment_method === 'cash' ? 'paid' : 'pending_payment',
                'payment_method' => $request->payment_method,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'confirmed_at' => now(),
                'completed_at' => null,
                'paid_at' => $request->payment_method === 'cash' ? now() : null,
            ]);

            // Create order items
            foreach ($orderItems as $oi) {
                $order->items()->create($oi);
            }

            // Create payment
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => $request->payment_method,
                'amount' => $total,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'status' => $request->payment_method === 'cash' ? 'completed' : 'pending',
                'paid_at' => $request->payment_method === 'cash' ? now() : null,
                'processed_by' => Auth::id(),
            ]);

            DB::commit();

            // Broadcast events (non-blocking)
            try {
                event(new NewOrderPlaced($order));
                event(new PaymentReceived($payment));
            } catch (\Throwable $e) {
                \Log::warning('Broadcast error: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat!',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => $total,
                    'formatted_total' => 'Rp ' . number_format($total, 0, ',', '.'),
                    'change' => $changeAmount,
                    'formatted_change' => 'Rp ' . number_format($changeAmount, 0, ',', '.'),
                ],
                'payment' => [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'method' => $payment->method_label,
                    'status' => $payment->status,
                ],
                'receipt_url' => route('admin.payments.receipt', $payment),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('POS Order Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent POS orders for today (AJAX)
     */
    public function recentOrders()
    {
        $orders = Order::with('items.menuItem', 'table', 'payments')
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'orders' => $orders->map(function ($o) {
                return [
                    'id' => $o->id,
                    'order_number' => $o->order_number,
                    'table' => $o->table->name ?? '-',
                    'customer_name' => $o->customer_name,
                    'total' => floatval($o->total),
                    'formatted_total' => $o->formatted_total,
                    'status' => $o->status,
                    'payment_status' => $o->payment_status,
                    'items_count' => $o->items->count(),
                    'time' => $o->created_at->format('H:i'),
                    'receipt_url' => $o->payments->where('status', 'completed')->first()
                        ? route('admin.payments.receipt', $o->payments->where('status', 'completed')->first())
                        : null,
                ];
            }),
        ]);
    }
}
