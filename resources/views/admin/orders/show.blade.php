@extends('layouts.admin')

@section('header', 'Detail Pesanan')

@push('styles')
<style>
    .quick-btn { transition: all 0.15s ease; }
    .quick-btn:active { transform: scale(0.96); }
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; }
    input[type=number] { -moz-appearance: textfield; }
</style>
@endpush

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-red-600 transition font-medium">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Kembali ke Daftar Pesanan</span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- ══════════ LEFT: Order Details ══════════ --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Informasi Pesanan --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-red-500"></i>
                    Informasi Pesanan
                </h2>
                <button onclick="window.print()" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fa-solid fa-print"></i>
                    <span>Cetak</span>
                </button>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1">No. Pesanan</p>
                        <p class="font-mono font-bold text-gray-800">{{ $order->order_number }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1">Meja</p>
                        <p class="text-gray-700 font-medium flex items-center gap-1.5">
                            <i class="fa-solid fa-chair text-gray-300 text-xs"></i>
                            {{ $order->table->name ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1">Nama Pelanggan</p>
                        <p class="text-gray-700 font-medium">{{ $order->customer_name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1">Status</p>
                        <div class="mt-0.5">{!! $order->status_badge !!}</div>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1">Pembayaran</p>
                        <div class="mt-0.5">{!! $order->payment_status_badge !!}</div>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1">Dibuat</p>
                        <p class="text-gray-700 text-sm">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @if($order->confirmed_at)
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1">Dikonfirmasi</p>
                        <p class="text-gray-700 text-sm">{{ $order->confirmed_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @endif
                    @if($order->completed_at)
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1">Selesai</p>
                        <p class="text-gray-700 text-sm">{{ $order->completed_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @endif
                </div>

                @if($order->notes)
                <div class="mt-5 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex items-start gap-3">
                    <i class="fa-solid fa-note-sticky text-amber-500 mt-0.5"></i>
                    <div>
                        <p class="text-[10px] text-amber-600 uppercase font-bold tracking-wider mb-0.5">Catatan</p>
                        <p class="text-gray-700 text-sm">{{ $order->notes }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Item Pesanan --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-utensils text-red-500"></i>
                    Item Pesanan
                    <span class="bg-gray-100 text-gray-500 text-xs font-bold px-2 py-0.5 rounded-full">{{ $order->items->count() }}</span>
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Menu</th>
                            <th class="px-6 py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">Qty</th>
                            <th class="px-6 py-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">Harga</th>
                            <th class="px-6 py-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($order->items as $item)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($item->menuItem && $item->menuItem->image)
                                    <img src="{{ asset('storage/' . $item->menuItem->image) }}" alt="{{ $item->menuItem->name }}" class="w-11 h-11 rounded-xl object-cover shadow-sm flex-shrink-0">
                                    @else
                                    <div class="w-11 h-11 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
                                        <i class="fa-solid fa-image text-gray-300 text-sm"></i>
                                    </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-800 text-sm truncate">{{ $item->menuItem->name ?? 'Menu dihapus' }}</p>
                                        @if($item->notes)
                                        <p class="text-xs text-gray-400 truncate mt-0.5">{{ $item->notes }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center bg-gray-100 text-gray-700 text-xs font-bold px-2.5 py-1 rounded-lg min-w-[32px]">{{ $item->quantity }}</span>
                            </td>
                            <td class="px-6 py-4 text-right text-gray-600">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right font-bold text-gray-800">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="px-6 py-5 border-t border-gray-100 bg-gray-50/80 rounded-b-2xl">
                <div class="max-w-xs ml-auto space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="text-gray-700 font-medium">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Pajak</span>
                        <span class="text-gray-700 font-medium">Rp {{ number_format($order->tax, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-lg font-bold border-t-2 border-gray-200 pt-3 mt-1">
                        <span class="text-gray-800">Total</span>
                        <span class="text-red-600">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════ RIGHT: Status & Payment ══════════ --}}
    <div class="space-y-6">

        {{-- Update Status Card --}}
        @if(!in_array($order->status, ['completed', 'cancelled']))
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-red-600 to-red-700">
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-tasks"></i>
                    Update Status
                </h2>
            </div>
            <div class="p-5 space-y-2.5">
                @if($order->status === 'pending')
                    <button onclick="confirmUpdateStatus({{ $order->id }}, 'confirmed', 'Konfirmasi Pesanan', 'Pesanan akan dikonfirmasi dan mulai diproses.')"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2 shadow-sm active:scale-[0.98]">
                        <i class="fa-solid fa-check"></i>Konfirmasi Pesanan
                    </button>
                    <button onclick="confirmUpdateStatus({{ $order->id }}, 'cancelled', 'Tolak Pesanan', 'Pesanan akan ditolak/dibatalkan.', true)"
                        class="w-full bg-red-50 hover:bg-red-100 text-red-700 px-4 py-3 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2 border border-red-200">
                        <i class="fa-solid fa-times"></i>Tolak Pesanan
                    </button>
                @elseif($order->status === 'confirmed')
                    <button onclick="confirmUpdateStatus({{ $order->id }}, 'preparing', 'Mulai Proses', 'Pesanan akan mulai diproses.')"
                        class="w-full bg-orange-600 hover:bg-orange-700 text-white px-4 py-3 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2 shadow-sm active:scale-[0.98]">
                        <i class="fa-solid fa-fire"></i>Mulai Proses
                    </button>
                @elseif($order->status === 'preparing')
                    <button onclick="confirmUpdateStatus({{ $order->id }}, 'ready', 'Tandai Siap', 'Pesanan akan ditandai siap disajikan.')"
                        class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2 shadow-sm active:scale-[0.98]">
                        <i class="fa-solid fa-bell"></i>Tandai Siap
                    </button>
                @elseif($order->status === 'ready')
                    <button onclick="confirmUpdateStatus({{ $order->id }}, 'served', 'Sajikan Pesanan', 'Pesanan akan ditandai telah disajikan.')"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-3 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2 shadow-sm active:scale-[0.98]">
                        <i class="fa-solid fa-utensils"></i>Sajikan
                    </button>
                @elseif($order->status === 'served')
                    <button onclick="confirmUpdateStatus({{ $order->id }}, 'completed', 'Selesaikan Pesanan', 'Pesanan akan ditandai selesai.')"
                        class="w-full bg-gray-700 hover:bg-gray-800 text-white px-4 py-3 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2 shadow-sm active:scale-[0.98]">
                        <i class="fa-solid fa-flag-checkered"></i>Selesai
                    </button>
                @endif
            </div>
        </div>
        @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-tasks text-red-500"></i>
                    Status Pesanan
                </h2>
            </div>
            <div class="p-5 text-center py-6">
                <div class="w-14 h-14 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-3">
                    <i class="fa-solid {{ $order->status === 'completed' ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-400' }} text-2xl"></i>
                </div>
                <p class="text-sm text-gray-500 font-medium">
                    Pesanan telah {{ $order->status === 'completed' ? 'selesai' : 'dibatalkan' }}
                </p>
            </div>
        </div>
        @endif

        {{-- Status Timeline --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-stream text-red-500"></i>
                    Alur Status
                </h2>
            </div>
            <div class="p-5">
                @php
                    $statusFlow = ['pending', 'confirmed', 'preparing', 'ready', 'served', 'completed'];
                    $statusLabels = [
                        'pending' => 'Menunggu',
                        'confirmed' => 'Dikonfirmasi',
                        'preparing' => 'Diproses',
                        'ready' => 'Siap',
                        'served' => 'Disajikan',
                        'completed' => 'Selesai',
                    ];
                    $currentIndex = array_search($order->status, $statusFlow);
                    $isCancelled = $order->status === 'cancelled';
                @endphp
                <div class="space-y-2">
                    @foreach($statusFlow as $i => $status)
                    <div class="flex items-center gap-3 py-1">
                        @if($isCancelled)
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-circle text-gray-300 text-[8px]"></i>
                            </div>
                            <span class="text-sm text-gray-400">{{ $statusLabels[$status] }}</span>
                        @elseif($currentIndex !== false && $i <= $currentIndex)
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-check text-green-600 text-xs"></i>
                            </div>
                            <span class="text-sm font-semibold text-green-700">{{ $statusLabels[$status] }}</span>
                        @elseif($currentIndex !== false && $i === $currentIndex + 1)
                            <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center animate-pulse flex-shrink-0">
                                <i class="fa-solid fa-arrow-right text-red-600 text-xs"></i>
                            </div>
                            <span class="text-sm font-semibold text-red-600">{{ $statusLabels[$status] }}</span>
                        @else
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-circle text-gray-300 text-[8px]"></i>
                            </div>
                            <span class="text-sm text-gray-400">{{ $statusLabels[$status] }}</span>
                        @endif
                    </div>
                    @endforeach

                    @if($isCancelled)
                    <div class="flex items-center gap-3 pt-2 mt-2 border-t border-gray-100">
                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-times text-red-600 text-xs"></i>
                        </div>
                        <span class="text-sm font-semibold text-red-600">Dibatalkan</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ══════════ Payment Section ══════════ --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-credit-card text-red-500"></i>
                    Pembayaran
                </h2>
            </div>
            <div class="p-5 space-y-4">
                {{-- Status & Metode --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1.5">Status Bayar</p>
                        <div>{!! $order->payment_status_badge !!}</div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1.5">Metode</p>
                        @if($order->payment_method)
                        <p class="text-sm text-gray-700 font-semibold">
                            @switch($order->payment_method)
                                @case('cash') <i class="fa-solid fa-money-bill-wave text-green-600 mr-1"></i>Tunai @break
                                @case('qris') <i class="fa-solid fa-qrcode text-blue-600 mr-1"></i>QRIS @break
                                @case('transfer') <i class="fa-solid fa-building-columns text-purple-600 mr-1"></i>Transfer @break
                                @case('ewallet') <i class="fa-solid fa-wallet text-orange-600 mr-1"></i>E-Wallet @break
                            @endswitch
                        </p>
                        @else
                        <p class="text-sm text-gray-400">-</p>
                        @endif
                    </div>
                </div>

                @if($order->paid_at)
                <div class="bg-green-50 border border-green-200 rounded-xl p-3 flex items-center gap-3">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-check text-green-600 text-xs"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-green-600 uppercase font-bold tracking-wider">Dibayar Pada</p>
                        <p class="text-sm text-green-700 font-semibold">{{ $order->paid_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                </div>
                @endif

                {{-- Payment History --}}
                @if($order->payments->count() > 0)
                <div class="space-y-2">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold">Riwayat Pembayaran</p>
                    @foreach($order->payments as $payment)
                    <div class="bg-gray-50 hover:bg-gray-100 rounded-xl p-3.5 transition">
                        <div class="flex justify-between items-center mb-1.5">
                            <span class="font-mono text-xs text-gray-500 font-medium">{{ $payment->payment_number }}</span>
                            {!! $payment->status_badge !!}
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">{!! $payment->method_label !!}</span>
                            <span class="font-bold text-gray-800 text-sm">{{ $payment->formatted_amount }}</span>
                        </div>
                        @if($payment->status === 'completed')
                        <a href="{{ route('admin.payments.receipt', $payment) }}" class="inline-flex items-center gap-1 text-xs text-red-600 hover:text-red-800 mt-2 font-semibold transition">
                            <i class="fa-solid fa-receipt"></i>Lihat Struk
                        </a>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- ══════════ INLINE PAYMENT FORM ══════════ --}}
                @if($order->payment_status !== 'paid' && $order->status !== 'cancelled')
                <div class="pt-3 border-t border-gray-100 space-y-4">
                    <p class="text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-cash-register text-red-500"></i>
                        Proses Pembayaran
                    </p>

                    <form action="{{ route('admin.payments.store', $order) }}" method="POST" class="space-y-4">
                        @csrf

                        {{-- Metode Pembayaran --}}
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Metode</label>
                            <div class="grid grid-cols-4 gap-1.5">
                                @php
                                    $methods = [
                                        ['value' => 'cash', 'icon' => 'fa-money-bill-wave', 'label' => 'Tunai'],
                                        ['value' => 'qris', 'icon' => 'fa-qrcode', 'label' => 'QRIS'],
                                        ['value' => 'transfer', 'icon' => 'fa-building-columns', 'label' => 'Transfer'],
                                        ['value' => 'ewallet', 'icon' => 'fa-wallet', 'label' => 'E-Wallet'],
                                    ];
                                @endphp
                                @foreach($methods as $i => $method)
                                <label class="cursor-pointer group">
                                    <input type="radio" name="payment_method" value="{{ $method['value'] }}"
                                        class="peer sr-only pay-method-radio" {{ $i === 0 ? 'checked' : '' }}>
                                    <div class="border-2 border-gray-200 peer-checked:border-red-500 peer-checked:bg-red-50 rounded-xl p-2 text-center transition-all hover:border-gray-300">
                                        <i class="fa-solid {{ $method['icon'] }} text-sm text-gray-400 mb-1 block"></i>
                                        <span class="text-[10px] font-bold text-gray-500 leading-none block">{{ $method['label'] }}</span>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            @error('payment_method')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Cash Fields --}}
                        <div id="pay-cash-fields">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Jumlah Bayar</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 font-bold text-sm">Rp</span>
                                <input type="number" name="paid_amount" id="pay-amount" step="100" min="0"
                                    value="{{ old('paid_amount', $order->total) }}"
                                    class="w-full pl-10 pr-3 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 text-lg font-bold text-gray-800 transition"
                                    placeholder="0">
                            </div>
                            @error('paid_amount')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror

                            {{-- Quick Amount Buttons --}}
                            <div class="grid grid-cols-4 gap-1.5 mt-2">
                                <button type="button" class="quick-btn pay-quick-btn bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold py-2 rounded-lg" data-amount="{{ $order->total }}">Uang Pas</button>
                                <button type="button" class="quick-btn pay-quick-btn bg-gray-100 hover:bg-gray-200 text-gray-700 text-[10px] font-bold py-2 rounded-lg" data-amount="{{ $order->total + 5000 }}">+5rb</button>
                                <button type="button" class="quick-btn pay-quick-btn bg-gray-100 hover:bg-gray-200 text-gray-700 text-[10px] font-bold py-2 rounded-lg" data-amount="{{ $order->total + 10000 }}">+10rb</button>
                                <button type="button" class="quick-btn pay-quick-btn bg-gray-100 hover:bg-gray-200 text-gray-700 text-[10px] font-bold py-2 rounded-lg" data-amount="{{ $order->total + 20000 }}">+20rb</button>
                                <button type="button" class="quick-btn pay-quick-btn bg-gray-100 hover:bg-gray-200 text-gray-700 text-[10px] font-bold py-2 rounded-lg" data-amount="{{ $order->total + 50000 }}">+50rb</button>
                                <button type="button" class="quick-btn pay-quick-btn bg-gray-100 hover:bg-gray-200 text-gray-700 text-[10px] font-bold py-2 rounded-lg" data-amount="{{ $order->total + 100000 }}">+100rb</button>
                            </div>

                            {{-- Change Display --}}
                            <div id="pay-change-box" class="mt-2 bg-emerald-50 border border-emerald-200 rounded-xl p-3 flex items-center justify-between">
                                <span id="pay-change-label" class="text-xs font-semibold text-emerald-700"><i class="fa-solid fa-coins mr-1"></i>Kembalian</span>
                                <span id="pay-change-amount" class="text-base font-bold text-emerald-700">Rp 0</span>
                            </div>
                        </div>

                        {{-- Non-Cash Fields --}}
                        <div id="pay-noncash-fields" class="hidden">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">No. Referensi</label>
                            <input type="text" name="reference_number" id="pay-ref"
                                class="w-full border-2 border-gray-200 rounded-xl py-2.5 px-3 text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                                placeholder="Opsional">
                            <p class="text-[10px] text-blue-500 mt-1.5"><i class="fa-solid fa-info-circle mr-1"></i>Pastikan pembayaran sudah diterima.</p>
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Catatan <span class="text-gray-300 normal-case font-normal">(opsional)</span></label>
                            <textarea name="notes" rows="2" class="w-full border-2 border-gray-200 rounded-xl py-2 px-3 text-sm resize-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition" placeholder="Catatan...">{{ old('notes') }}</textarea>
                        </div>

                        {{-- Total & Submit --}}
                        <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-3 flex justify-between items-center">
                            <div>
                                <p class="text-[10px] text-red-500 uppercase font-bold">Total Bayar</p>
                                <p class="text-xs text-red-400">{{ $order->items->count() }} item</p>
                            </div>
                            <span class="text-xl font-extrabold text-red-700">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
                        </div>

                        <button type="submit"
                            class="w-full bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white font-bold py-3.5 rounded-xl text-sm flex items-center justify-center gap-2 shadow-sm active:scale-[0.98] transition">
                            <i class="fa-solid fa-cash-register"></i>
                            <span>Proses Pembayaran</span>
                        </button>
                    </form>

                    <button onclick="quickPay({{ $order->id }})"
                        class="w-full bg-blue-50 hover:bg-blue-100 text-blue-700 px-4 py-3 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2 border border-blue-200">
                        <i class="fa-solid fa-bolt"></i>Bayar Tunai Cepat (Uang Pas)
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // ── Status Update ──
    async function confirmUpdateStatus(orderId, status, title, text, isDanger = false) {
        const result = await Swal.fire({
            title: title,
            text: text,
            icon: isDanger ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonColor: isDanger ? '#DC2626' : '#2563EB',
            cancelButtonColor: '#6B7280',
            confirmButtonText: isDanger ? 'Ya, Tolak!' : 'Ya, Lanjutkan!',
            cancelButtonText: 'Batal',
            reverseButtons: true,
        });
        if (!result.isConfirmed) return;

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
                Swal.fire({icon:'success',title:'Berhasil!',text:'Status pesanan berhasil diupdate.',confirmButtonColor:'#DC2626',timer:1500,showConfirmButton:false}).then(()=>location.reload());
            } else {
                Swal.fire({icon:'error',title:'Gagal',text:'Gagal mengupdate status pesanan.',confirmButtonColor:'#DC2626'});
            }
        } catch (error) {
            console.error('Error updating status:', error);
            Swal.fire({icon:'error',title:'Error',text:'Terjadi kesalahan saat mengupdate status.',confirmButtonColor:'#DC2626'});
        }
    }

    // ── Quick Pay ──
    async function quickPay(orderId) {
        const result = await Swal.fire({
            title: 'Pembayaran Cepat',
            text: 'Proses pembayaran tunai cepat (jumlah pas)?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Proses!',
            cancelButtonText: 'Batal',
            reverseButtons: true,
        });
        if (!result.isConfirmed) return;

        try {
            const response = await fetch(`/admin/payments/${orderId}/quick-pay`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();
            if (data.success) {
                Swal.fire({icon:'success',title:'Pembayaran Berhasil!',confirmButtonColor:'#DC2626',timer:1500,showConfirmButton:false}).then(()=>{
                    if (data.receipt_url) window.location.href = data.receipt_url;
                    else location.reload();
                });
            } else {
                Swal.fire({icon:'error',title:'Gagal',text:'Gagal memproses pembayaran.',confirmButtonColor:'#DC2626'});
            }
        } catch (error) {
            console.error('Error processing payment:', error);
            Swal.fire({icon:'error',title:'Error',text:'Terjadi kesalahan saat memproses pembayaran.',confirmButtonColor:'#DC2626'});
        }
    }

    // ── Inline Payment Form Logic ──
    document.addEventListener('DOMContentLoaded', function() {
        const orderTotal = {{ $order->total }};
        const methodRadios = document.querySelectorAll('.pay-method-radio');
        const cashFields = document.getElementById('pay-cash-fields');
        const nonCashFields = document.getElementById('pay-noncash-fields');
        const paidInput = document.getElementById('pay-amount');
        const changeBox = document.getElementById('pay-change-box');
        const changeLabel = document.getElementById('pay-change-label');
        const changeAmount = document.getElementById('pay-change-amount');
        const quickBtns = document.querySelectorAll('.pay-quick-btn');

        if (!methodRadios.length) return; // No payment form rendered

        function toggleMethod() {
            const m = document.querySelector('.pay-method-radio:checked')?.value;
            if (!m) return;
            if (m === 'cash') {
                cashFields?.classList.remove('hidden');
                nonCashFields?.classList.add('hidden');
            } else {
                cashFields?.classList.add('hidden');
                nonCashFields?.classList.remove('hidden');
                if (paidInput) { paidInput.value = orderTotal; calcChange(); }
            }
        }

        function calcChange() {
            if (!paidInput || !changeBox) return;
            const paid = parseFloat(paidInput.value) || 0;
            const ch = paid - orderTotal;
            if (ch >= 0) {
                changeBox.className = 'mt-2 bg-emerald-50 border border-emerald-200 rounded-xl p-3 flex items-center justify-between';
                changeLabel.innerHTML = '<i class="fa-solid fa-coins mr-1"></i>Kembalian';
                changeLabel.className = 'text-xs font-semibold text-emerald-700';
                changeAmount.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(ch);
                changeAmount.className = 'text-base font-bold text-emerald-700';
            } else {
                changeBox.className = 'mt-2 bg-red-50 border border-red-200 rounded-xl p-3 flex items-center justify-between';
                changeLabel.innerHTML = '<i class="fa-solid fa-exclamation-triangle mr-1"></i>Kurang';
                changeLabel.className = 'text-xs font-semibold text-red-600';
                changeAmount.textContent = '-Rp ' + new Intl.NumberFormat('id-ID').format(Math.abs(ch));
                changeAmount.className = 'text-base font-bold text-red-600';
            }
        }

        methodRadios.forEach(r => r.addEventListener('change', toggleMethod));
        if (paidInput) paidInput.addEventListener('input', calcChange);
        quickBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                if (paidInput) { paidInput.value = this.dataset.amount; calcChange(); }
            });
        });

        toggleMethod();
        calcChange();
    });
</script>
@endpush
@endsection
