<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('table', 'items.menuItem');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                    ->orWhere('customer_name', 'like', "%{$request->search}%");
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('table', 'items.menuItem', 'payments');
        return view('admin.orders.show', compact('order'));
    }

    public function report(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $orders = Order::whereBetween('created_at', [$startDate, Carbon::parse($endDate)->endOfDay()])
            ->whereIn('status', ['completed', 'served'])
            ->get();

        $dailyRevenue = Order::whereBetween('created_at', [$startDate, Carbon::parse($endDate)->endOfDay()])
            ->whereIn('status', ['completed', 'served'])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total_orders, SUM(total) as total_revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalRevenue = $orders->sum('total');
        $totalOrders = $orders->count();
        $averageOrder = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        return view('admin.orders.report', compact(
            'dailyRevenue', 'totalRevenue', 'totalOrders', 'averageOrder', 'startDate', 'endDate'
        ));
    }
}
