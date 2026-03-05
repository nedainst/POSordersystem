<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $paymentData;

    public function __construct(Payment $payment)
    {
        $payment->load('order');

        $this->paymentData = [
            'id' => $payment->id,
            'payment_number' => $payment->payment_number,
            'order_id' => $payment->order_id,
            'order_number' => $payment->order->order_number,
            'amount' => $payment->amount,
            'formatted_amount' => 'Rp ' . number_format($payment->amount, 0, ',', '.'),
            'payment_method' => $payment->payment_method,
            'method_label' => $payment->method_label,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at?->format('H:i'),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('admin.orders'),
            new Channel('order.' . $this->paymentData['order_id']),
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.received';
    }
}
