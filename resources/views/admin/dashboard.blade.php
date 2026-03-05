@extends('layouts.admin')

@section('header', 'Dashboard')

@section('content')
{{-- Stats Cards --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-receipt text-blue-600 text-lg"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-gray-400 uppercase tracking-wider font-bold">Pesanan Hari Ini</p>
                <h3 class="text-2xl font-extrabold text-gray-800 mt-0.5">{{ $stats['total_orders_today'] }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-orange-100 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-clock text-orange-600 text-lg"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-gray-400 uppercase tracking-wider font-bold">Pesanan Aktif</p>
                <h3 class="text-2xl font-extrabold text-orange-600 mt-0.5">{{ $stats['pending_orders'] }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-money-bill-wave text-emerald-600 text-lg"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-gray-400 uppercase tracking-wider font-bold">Pendapatan</p>
                <h3 class="text-xl font-extrabold text-emerald-600 mt-0.5 truncate">Rp {{ number_format($stats['revenue_today'], 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-purple-100 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-hamburger text-purple-600 text-lg"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-gray-400 uppercase tracking-wider font-bold">Total Menu</p>
                <h3 class="text-2xl font-extrabold text-gray-800 mt-0.5">{{ $stats['total_menu_items'] }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-chair text-indigo-600 text-lg"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-gray-400 uppercase tracking-wider font-bold">Meja</p>
                <h3 class="text-2xl font-extrabold text-gray-800 mt-0.5">{{ $stats['occupied_tables'] }}<span class="text-gray-400 text-base font-bold">/{{ $stats['total_tables'] }}</span></h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-teal-100 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-credit-card text-teal-600 text-lg"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-gray-400 uppercase tracking-wider font-bold">Terbayar</p>
                <h3 class="text-xl font-extrabold text-teal-600 mt-0.5 truncate">Rp {{ number_format($stats['paid_today'], 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    @if($stats['unpaid_orders'] > 0)
    <div class="bg-red-50 rounded-2xl shadow-sm p-5 border border-red-200 hover:shadow-md transition col-span-2 sm:col-span-1">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-red-100 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-exclamation-triangle text-red-600 text-lg"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-red-500 uppercase tracking-wider font-bold">Belum Bayar</p>
                <h3 class="text-2xl font-extrabold text-red-600 mt-0.5">{{ $stats['unpaid_orders'] }}</h3>
                <a href="{{ route('admin.payments.index') }}?status=pending" class="text-[11px] text-red-500 hover:text-red-700 font-semibold">Lihat semua →</a>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Pending Orders Section --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-orange-50 to-transparent">
        <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
            <i class="fa-solid fa-fire text-orange-500"></i>
            <span>Pesanan Aktif</span>
            <span id="pending-badge" class="bg-red-600 text-white text-xs font-bold px-2.5 py-0.5 rounded-full">{{ $pendingOrders->count() }}</span>
        </h2>
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
            <span class="text-[11px] text-gray-400 font-medium">Auto refresh</span>
        </div>
    </div>

    <div id="pending-orders" class="divide-y divide-gray-100">
        @forelse($pendingOrders as $order)
        <div class="p-4 hover:bg-gray-50 transition" id="order-{{ $order->id }}">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-bold text-sm text-gray-800">{{ $order->order_number }}</span>
                        {!! $order->status_badge !!}
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        <i class="fas fa-chair mr-1"></i>{{ $order->table->name }}
                        @if($order->customer_name)
                            <span class="mx-1">·</span>
                            <i class="fas fa-user mr-1"></i>{{ $order->customer_name }}
                        @endif
                        <span class="mx-1">·</span>
                        <i class="fas fa-clock mr-1"></i>{{ $order->created_at->diffForHumans() }}
                    </p>
                </div>
                <span class="font-bold text-gray-800">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center gap-2 mb-3">
                {!! $order->payment_status_badge !!}
                @if($order->payment_method)
                <span class="text-xs text-gray-400">
                    @switch($order->payment_method)
                        @case('cash') <i class="fas fa-money-bill-wave"></i> Tunai @break
                        @case('qris') <i class="fas fa-qrcode"></i> QRIS @break
                        @case('transfer') <i class="fas fa-university"></i> Transfer @break
                        @case('ewallet') <i class="fas fa-wallet"></i> E-Wallet @break
                    @endswitch
                </span>
                @endif
            </div>

            <div class="flex flex-wrap gap-1 mb-3">
                @foreach($order->items as $item)
                <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-lg">
                    {{ $item->quantity }}x {{ $item->menuItem->name }}
                </span>
                @endforeach
            </div>

            @if($order->notes)
            <p class="text-xs text-gray-500 bg-yellow-50 px-3 py-2 rounded-lg mb-3">
                <i class="fas fa-sticky-note mr-1 text-yellow-500"></i>{{ $order->notes }}
            </p>
            @endif

            <div class="flex gap-2">
                @if($order->status === 'pending')
                    <button onclick="updateStatus({{ $order->id }}, 'confirmed')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition">
                        <i class="fas fa-check mr-1"></i>Konfirmasi
                    </button>
                    <button onclick="updateStatus({{ $order->id }}, 'cancelled')" class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded-lg text-xs font-medium transition">
                        <i class="fas fa-times mr-1"></i>Tolak
                    </button>
                @elseif($order->status === 'confirmed')
                    <button onclick="updateStatus({{ $order->id }}, 'preparing')" class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition">
                        <i class="fas fa-fire mr-1"></i>Proses
                    </button>
                @elseif($order->status === 'preparing')
                    <button onclick="updateStatus({{ $order->id }}, 'ready')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition">
                        <i class="fas fa-bell mr-1"></i>Siap
                    </button>
                @elseif($order->status === 'ready')
                    <button onclick="updateStatus({{ $order->id }}, 'served')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition">
                        <i class="fas fa-utensils mr-1"></i>Sajikan
                    </button>
                @endif

                @if(in_array($order->status, ['served']))
                    <button onclick="updateStatus({{ $order->id }}, 'completed')" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition">
                        <i class="fas fa-flag-checkered mr-1"></i>Selesai
                    </button>
                @endif
            </div>
        </div>
        @empty
        <div class="p-12 text-center">
            <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fa-solid fa-check-circle text-2xl text-gray-300"></i>
            </div>
            <p class="text-gray-400 font-medium">Tidak ada pesanan aktif</p>
            <p class="text-xs text-gray-300 mt-1">Pesanan baru akan muncul secara otomatis</p>
        </div>
        @endforelse
    </div>
</div>

{{-- Recent Orders --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
            <i class="fa-solid fa-history text-gray-400"></i>
            <span>Pesanan Terbaru</span>
        </h2>
        <a href="{{ route('admin.orders.index') }}" class="text-xs text-red-600 hover:text-red-800 font-semibold">Lihat Semua →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Pesanan</th>
                    <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Meja</th>
                    <th class="px-5 py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">Item</th>
                    <th class="px-5 py-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total</th>
                    <th class="px-5 py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">Bayar</th>
                    <th class="px-5 py-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">Waktu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($recentOrders as $order)
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-5 py-3.5">
                        <a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-xs font-bold text-red-600 hover:text-red-800 hover:underline transition">{{ $order->order_number }}</a>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="inline-flex items-center gap-1 text-gray-700">
                            <i class="fa-solid fa-chair text-gray-300 text-xs"></i>
                            {{ $order->table->name }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <span class="inline-flex items-center justify-center bg-gray-100 text-gray-600 text-xs font-bold px-2 py-0.5 rounded-full min-w-[28px]">{{ $order->items->count() }}</span>
                    </td>
                    <td class="px-5 py-3.5 text-right font-bold text-gray-800">Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                    <td class="px-5 py-3.5 text-center">{!! $order->status_badge !!}</td>
                    <td class="px-5 py-3.5 text-center">{!! $order->payment_status_badge !!}</td>
                    <td class="px-5 py-3.5 text-right text-xs text-gray-400">{{ $order->created_at->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    // Notification sound
    const notificationSound = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdW+Kj4CAaFZYZ3aDiYJ6bmRic3qFh4N+eXZ5foKEg4F/fn+AgYKCgYB/f4CAgYGBgIB/gICAgICAgIB/gICAgIAAAAA=');

    async function updateStatus(orderId, status) {
        try {
            const response = await fetch(`/admin/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status }),
            });

            const data = await response.json();
            if (data.success) {
                location.reload();
            }
        } catch (error) {
            console.error('Error updating status:', error);
        }
    }

    // Realtime updates via Laravel Echo + Reverb
    if (window.Echo) {
        window.Echo.channel('admin.orders')
            .listen('.order.new', (e) => {
                console.log('New order received:', e);
                notificationSound.play().catch(() => {});
                if (Notification.permission === 'granted') {
                    new Notification('Pesanan Baru!', {
                        body: `Pesanan #${e.order_number} dari ${e.customer_name} - Meja ${e.table_number}`,
                        icon: '/favicon.ico',
                    });
                }
                location.reload();
            })
            .listen('.order.updated', (e) => {
                console.log('Order updated:', e);
                location.reload();
            })
            .listen('.payment.received', (e) => {
                console.log('Payment received:', e);
                location.reload();
            });

        console.log('Reverb realtime connected for admin dashboard');
    } else {
        // Fallback: poll for new orders every 5 seconds if Echo not available
        let lastCheck = '{{ now()->toDateTimeString() }}';
        setInterval(async () => {
            try {
                const response = await fetch(`/admin/orders/new?last_check=${encodeURIComponent(lastCheck)}`);
                const data = await response.json();

                if (data.orders && data.orders.length > 0) {
                    notificationSound.play().catch(() => {});
                    location.reload();
                }
                lastCheck = data.timestamp;
            } catch (error) {
                console.error('Polling error:', error);
            }
        }, 5000);
    }

    // Request notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
</script>
@endpush
@endsection
