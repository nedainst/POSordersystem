@extends('layouts.customer')

@section('title', 'Lacak Pesanan #' . $order->order_number)

@section('content')
<div class="min-h-screen bg-gray-50">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-red-700 to-red-500 text-white px-4 py-6">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center gap-3">
                @if(isset($settings['site_logo']) && $settings['site_logo'])
                    <img src="{{ asset('storage/' . $settings['site_logo']) }}" alt="Logo" class="w-10 h-10 rounded-full object-cover border-2 border-white/30">
                @else
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                        <i class="fas fa-utensils"></i>
                    </div>
                @endif
                <div>
                    <h1 class="font-bold text-lg">{{ $settings['site_name'] ?? 'Warung Order' }}</h1>
                    <p class="text-red-100 text-xs">Lacak Pesanan</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-2xl mx-auto px-4 py-6 space-y-4">
        {{-- Order Info Card --}}
        <div class="bg-white rounded-2xl shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-gray-400">No. Pesanan</p>
                    <p class="font-mono font-bold text-gray-800">{{ $order->order_number }}</p>
                </div>
                <div>{!! $order->status_badge !!}</div>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-400 text-xs">Meja</p>
                    <p class="font-semibold text-gray-700">{{ $order->table->name }}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Waktu Pesan</p>
                    <p class="font-semibold text-gray-700">{{ $order->created_at->format('H:i, d M Y') }}</p>
                </div>
                @if($order->customer_name)
                <div>
                    <p class="text-gray-400 text-xs">Nama</p>
                    <p class="font-semibold text-gray-700">{{ $order->customer_name }}</p>
                </div>
                @endif
                <div>
                    <p class="text-gray-400 text-xs">Pembayaran</p>
                    <div class="mt-0.5">{!! $order->payment_status_badge !!}</div>
                </div>
            </div>
        </div>

        {{-- Order Status Timeline --}}
        <div class="bg-white rounded-2xl shadow-md p-6">
            <h3 class="font-bold text-gray-800 mb-4">Status Pesanan</h3>
            @php
                $statuses = ['pending' => 'Menunggu Konfirmasi', 'confirmed' => 'Dikonfirmasi', 'preparing' => 'Sedang Diproses', 'ready' => 'Siap Disajikan', 'served' => 'Disajikan', 'completed' => 'Selesai'];
                $currentIndex = array_search($order->status, array_keys($statuses));
                $icons = ['pending' => 'clock', 'confirmed' => 'check', 'preparing' => 'fire', 'ready' => 'bell', 'served' => 'utensils', 'completed' => 'flag-checkered'];
            @endphp

            <div class="space-y-4">
                @foreach($statuses as $key => $label)
                @php
                    $index = array_search($key, array_keys($statuses));
                    $isCompleted = $index <= $currentIndex && $order->status !== 'cancelled';
                    $isCurrent = $key === $order->status;
                @endphp
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 {{ $isCompleted ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400' }} {{ $isCurrent ? 'ring-4 ring-green-200' : '' }}">
                        <i class="fas fa-{{ $icons[$key] ?? 'circle' }} text-sm"></i>
                    </div>
                    <div>
                        <p class="font-medium {{ $isCompleted ? 'text-gray-800' : 'text-gray-400' }}">{{ $label }}</p>
                        @if($isCurrent)
                            <p class="text-xs text-green-600 font-medium">Saat ini</p>
                        @endif
                    </div>
                </div>
                @if(!$loop->last)
                    <div class="ml-5 w-0.5 h-4 {{ $isCompleted && $index < $currentIndex ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                @endif
                @endforeach

                @if($order->status === 'cancelled')
                <div class="flex items-center gap-4 mt-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-red-500 text-white ring-4 ring-red-200">
                        <i class="fas fa-times text-sm"></i>
                    </div>
                    <div>
                        <p class="font-medium text-red-600">Pesanan Dibatalkan</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Order Items --}}
        <div class="bg-white rounded-2xl shadow-md p-6">
            <h3 class="font-bold text-gray-800 mb-4">Detail Pesanan</h3>
            <div class="space-y-3">
                @foreach($order->items as $item)
                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                    <div class="flex items-center gap-3">
                        @if($item->menuItem->image)
                            <img src="{{ asset('storage/' . $item->menuItem->image) }}" class="w-12 h-12 rounded-lg object-cover">
                        @else
                            <div class="w-12 h-12 rounded-lg bg-red-50 flex items-center justify-center">
                                <i class="fas fa-utensils text-red-300"></i>
                            </div>
                        @endif
                        <div>
                            <p class="font-medium text-sm text-gray-800">{{ $item->menuItem->name }}</p>
                            <p class="text-xs text-gray-400">{{ $item->quantity }}x @ Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <p class="font-semibold text-sm text-gray-700">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                </div>
                @endforeach
            </div>

            <div class="mt-4 pt-4 border-t border-gray-200 space-y-2">
                <div class="flex justify-between text-sm text-gray-500">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($order->tax > 0)
                <div class="flex justify-between text-sm text-gray-500">
                    <span>Pajak</span>
                    <span>Rp {{ number_format($order->tax, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="flex justify-between font-bold text-gray-800 text-lg pt-2 border-t border-gray-200">
                    <span>Total</span>
                    <span>Rp {{ number_format($order->total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Payment Info --}}
        @if($order->payment_status !== 'paid' && !in_array($order->status, ['cancelled']))
        <div class="bg-white rounded-2xl shadow-md p-6">
            <h3 class="font-bold text-gray-800 mb-3">
                <i class="fas fa-credit-card text-red-500 mr-2"></i>Pembayaran
            </h3>
            @if($order->payment_method)
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-3">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        Metode pembayaran: <strong>
                            @switch($order->payment_method)
                                @case('cash') Tunai @break
                                @case('qris') QRIS @break
                                @case('transfer') Transfer Bank @break
                                @case('ewallet') E-Wallet @break
                            @endswitch
                        </strong>
                    </p>
                    @if($order->payment_method === 'cash')
                        <p class="text-xs text-yellow-700 mt-1">Silakan lakukan pembayaran di kasir.</p>
                    @else
                        <p class="text-xs text-yellow-700 mt-1">Menunggu konfirmasi dari kasir.</p>
                    @endif
                </div>
            @endif
            <a href="{{ route('order.payment', $order->id) }}" class="block w-full bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white py-3 rounded-xl font-semibold text-center transition shadow-lg">
                <i class="fas fa-wallet mr-2"></i>{{ $order->payment_method ? 'Ubah Metode Pembayaran' : 'Pilih Metode Pembayaran' }}
            </a>
        </div>
        @elseif($order->payment_status === 'paid')
        <div class="bg-white rounded-2xl shadow-md p-6">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-3xl"></i>
                </div>
                <h3 class="font-bold text-green-700 text-lg">Pembayaran Lunas</h3>
                <p class="text-sm text-gray-500 mt-1">
                    Dibayar via
                    @switch($order->payment_method)
                        @case('cash') Tunai @break
                        @case('qris') QRIS @break
                        @case('transfer') Transfer Bank @break
                        @case('ewallet') E-Wallet @break
                    @endswitch
                    @if($order->paid_at)
                        pada {{ $order->paid_at->format('H:i, d M Y') }}
                    @endif
                </p>
            </div>
        </div>
        @endif

        {{-- Back to Menu --}}
        <div class="text-center pb-6">
            <a href="{{ route('menu.show', $order->table_id) }}" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition shadow-lg">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Menu
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Realtime updates via Laravel Echo + Reverb
    if (window.Echo) {
        const orderId = {{ $order->id }};
        window.Echo.channel(`order.${orderId}`)
            .listen('.order.updated', (e) => {
                console.log('Order status updated:', e);
                location.reload();
            })
            .listen('.payment.received', (e) => {
                console.log('Payment received:', e);
                location.reload();
            });

        console.log(`Reverb realtime connected for order #${orderId}`);
    } else {
        // Fallback: auto refresh every 15 seconds if Echo not available
        setInterval(() => {
            window.location.reload();
        }, 15000);
    }
</script>
@endpush
@endsection
