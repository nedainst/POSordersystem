<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\MenuItem;
use App\Models\Category;
use App\Models\Table;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Events\OrderStatusUpdated;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $stats = [
            'total_orders_today' => Order::whereDate('created_at', $today)->count(),
            'pending_orders' => Order::whereIn('status', ['pending', 'confirmed', 'preparing'])->count(),
            'revenue_today' => Order::whereDate('created_at', $today)->whereIn('status', ['completed', 'served'])->sum('total'),
            'total_menu_items' => MenuItem::count(),
            'total_tables' => Table::where('is_active', true)->count(),
            'occupied_tables' => Table::where('status', 'occupied')->count(),
            'unpaid_orders' => Order::whereIn('status', ['served', 'completed'])->where('payment_status', 'unpaid')->count(),
            'paid_today' => Payment::whereDate('created_at', $today)->where('status', 'completed')->sum('amount'),
        ];

        $recentOrders = Order::with('table', 'items.menuItem')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $pendingOrders = Order::with('table', 'items.menuItem')
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'pendingOrders'));
    }

    /**
     * Get new orders (AJAX polling)
     */
    public function getNewOrders(Request $request)
    {
        $lastCheck = $request->input('last_check', now()->subMinutes(5)->toDateTimeString());

        $newOrders = Order::with('table', 'items.menuItem')
            ->where('created_at', '>', $lastCheck)
            ->orderBy('created_at', 'desc')
            ->get();

        $pendingCount = Order::whereIn('status', ['pending', 'confirmed', 'preparing'])->count();

        return response()->json([
            'orders' => $newOrders,
            'pending_count' => $pendingCount,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,served,completed,cancelled',
        ]);

        $order->update([
            'status' => $request->status,
            'confirmed_at' => $request->status === 'confirmed' ? now() : $order->confirmed_at,
            'completed_at' => $request->status === 'completed' ? now() : $order->completed_at,
        ]);

        // If completed or cancelled, check if table can be freed
        if (in_array($request->status, ['completed', 'cancelled'])) {
            $activeOrders = Order::where('table_id', $order->table_id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();

            if ($activeOrders === 0) {
                Table::where('id', $order->table_id)->update(['status' => 'available']);
            }
        }

        // Broadcast order status update (non-blocking)
        try { event(new OrderStatusUpdated($order->fresh())); } catch (\Throwable $e) { \Log::warning('Broadcast error: ' . $e->getMessage()); }

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diperbarui!',
            'order' => $order->fresh()->load('table', 'items.menuItem'),
        ]);
    }
}
