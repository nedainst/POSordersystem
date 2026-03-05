<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Events\PaymentReceived;
use App\Events\OrderStatusUpdated;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * List all payments
     */
    public function index(Request $request)
    {
        $query = Payment::with('order.table', 'processedBy');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('payment_number', 'like', "%{$request->search}%")
                    ->orWhereHas('order', function ($oq) use ($request) {
                        $oq->where('order_number', 'like', "%{$request->search}%")
                            ->orWhere('customer_name', 'like', "%{$request->search}%");
                    });
            });
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);

        // Stats
        $today = Carbon::today();
        $stats = [
            'total_today' => Payment::whereDate('created_at', $today)->where('status', 'completed')->sum('amount'),
            'pending_count' => Payment::where('status', 'pending')->count(),
            'completed_today' => Payment::whereDate('created_at', $today)->where('status', 'completed')->count(),
            'unpaid_orders' => Order::whereIn('status', ['served', 'completed'])->where('payment_status', 'unpaid')->count(),
        ];

        return view('admin.payments.index', compact('payments', 'stats'));
    }

    /**
     * Show payment form for an order (cashier processes payment)
     */
    public function create(Order $order)
    {
        $order->load('items.menuItem', 'table', 'payments');
        return view('admin.payments.create', compact('order'));
    }

    /**
     * Process payment
     */
    public function store(Request $request, Order $order)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,qris,transfer,ewallet',
            'paid_amount' => 'required|numeric|min:0',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $amount = $order->total;
        $paidAmount = $request->paid_amount;
        $changeAmount = max(0, $paidAmount - $amount);

        // For non-cash payments, paid_amount equals order total
        if ($request->payment_method !== 'cash') {
            $paidAmount = $amount;
            $changeAmount = 0;
        }

        // Validate cash payment is enough
        if ($request->payment_method === 'cash' && $paidAmount < $amount) {
            return back()->withErrors(['paid_amount' => 'Jumlah pembayaran kurang dari total pesanan.'])->withInput();
        }

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method' => $request->payment_method,
            'amount' => $amount,
            'paid_amount' => $paidAmount,
            'change_amount' => $changeAmount,
            'status' => $request->payment_method === 'cash' ? 'completed' : 'pending',
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
            'paid_at' => $request->payment_method === 'cash' ? now() : null,
            'processed_by' => Auth::id(),
        ]);

        // Update order payment status
        if ($request->payment_method === 'cash') {
            $order->update([
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'paid_at' => now(),
            ]);
        } else {
            $order->update([
                'payment_status' => 'pending_payment',
                'payment_method' => $request->payment_method,
            ]);
        }

        // Broadcast payment event (non-blocking)
        try {
            event(new PaymentReceived($payment));
            event(new OrderStatusUpdated($order->fresh()));
        } catch (\Throwable $e) { \Log::warning('Broadcast error: ' . $e->getMessage()); }

        if ($request->payment_method === 'cash') {
            return redirect()->route('admin.payments.receipt', $payment)
                ->with('success', 'Pembayaran tunai berhasil diproses!');
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Pembayaran sedang menunggu konfirmasi.');
    }

    /**
     * Confirm non-cash payment (cashier confirms QRIS/transfer/ewallet)
     */
    public function confirm(Payment $payment)
    {
        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'processed_by' => Auth::id(),
        ]);

        $payment->order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        // Broadcast payment confirmed (non-blocking)
        try {
            event(new PaymentReceived($payment));
            event(new OrderStatusUpdated($payment->order->fresh()));
        } catch (\Throwable $e) { \Log::warning('Broadcast error: ' . $e->getMessage()); }

        return redirect()->route('admin.payments.receipt', $payment)
            ->with('success', 'Pembayaran berhasil dikonfirmasi!');
    }

    /**
     * Reject/fail non-cash payment
     */
    public function reject(Payment $payment)
    {
        $payment->update([
            'status' => 'failed',
            'processed_by' => Auth::id(),
        ]);

        $payment->order->update([
            'payment_status' => 'unpaid',
            'payment_method' => null,
        ]);

        return redirect()->route('admin.orders.show', $payment->order)
            ->with('error', 'Pembayaran ditolak.');
    }

    /**
     * Show payment receipt
     */
    public function receipt(Payment $payment)
    {
        $payment->load('order.items.menuItem', 'order.table', 'processedBy');
        $settings = \App\Models\SiteSetting::pluck('value', 'key')->toArray();
        return view('admin.payments.receipt', compact('payment', 'settings'));
    }

    /**
     * Quick cash payment from dashboard (AJAX)
     */
    public function quickPay(Request $request, Order $order)
    {
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method' => 'cash',
            'amount' => $order->total,
            'paid_amount' => $order->total,
            'change_amount' => 0,
            'status' => 'completed',
            'paid_at' => now(),
            'processed_by' => Auth::id(),
        ]);

        $order->update([
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        // Broadcast quick payment (non-blocking)
        try {
            event(new PaymentReceived($payment));
            event(new OrderStatusUpdated($order->fresh()));
        } catch (\Throwable $e) { \Log::warning('Broadcast error: ' . $e->getMessage()); }

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran tunai berhasil!',
            'receipt_url' => route('admin.payments.receipt', $payment),
        ]);
    }
}
