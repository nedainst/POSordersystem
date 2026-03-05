@extends('layouts.admin')

@section('header', 'Daftar Pesanan')

@section('content')

{{-- ══════════ Filter Bar ══════════ --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    <div class="px-6 py-3 bg-gray-50 border-b border-gray-100">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
            <i class="fa-solid fa-filter text-gray-400"></i>
            Filter Pesanan
        </p>
    </div>
    <form method="GET" action="{{ route('admin.orders.index') }}" class="p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Status</label>
                <select name="status" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu</option>
                    <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
                    <option value="preparing" {{ request('status') === 'preparing' ? 'selected' : '' }}>Diproses</option>
                    <option value="ready" {{ request('status') === 'ready' ? 'selected' : '' }}>Siap</option>
                    <option value="served" {{ request('status') === 'served' ? 'selected' : '' }}>Disajikan</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Tanggal</label>
                <input type="date" name="date" value="{{ request('date') }}" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Cari</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fa-solid fa-search text-xs"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="No. pesanan / nama pelanggan"
                        class="w-full pl-9 pr-4 border border-gray-200 rounded-xl py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-xl text-sm font-bold transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-search text-xs"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('admin.orders.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-2.5 rounded-xl text-sm font-bold transition flex items-center justify-center"
                    title="Reset">
                    <i class="fa-solid fa-rotate-right"></i>
                </a>
            </div>
        </div>
    </form>
</div>

{{-- ══════════ Orders Table ══════════ --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
            <i class="fa-solid fa-receipt text-red-500"></i>
            Pesanan
        </h2>
        <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded-full font-medium">
            {{ $orders->total() }} pesanan
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Pesanan</th>
                    <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Meja</th>
                    <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Pelanggan</th>
                    <th class="px-5 py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">Item</th>
                    <th class="px-5 py-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total</th>
                    <th class="px-5 py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">Bayar</th>
                    <th class="px-5 py-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">Waktu</th>
                    <th class="px-5 py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($orders as $order)
                <tr class="hover:bg-gray-50/50 transition group">
                    <td class="px-5 py-3.5">
                        <a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-xs font-bold text-red-600 hover:text-red-800 hover:underline transition">
                            {{ $order->order_number }}
                        </a>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="inline-flex items-center gap-1 text-sm text-gray-700">
                            <i class="fa-solid fa-chair text-gray-300 text-xs"></i>
                            {{ $order->table->name ?? '-' }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5">
                        <p class="text-gray-700 font-medium text-sm">{{ $order->customer_name ?? '-' }}</p>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <span class="inline-flex items-center justify-center bg-gray-100 text-gray-600 text-xs font-bold px-2 py-0.5 rounded-full min-w-[28px]">
                            {{ $order->items_count ?? $order->items->count() }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <p class="font-bold text-gray-800">Rp {{ number_format($order->total, 0, ',', '.') }}</p>
                    </td>
                    <td class="px-5 py-3.5 text-center">{!! $order->status_badge !!}</td>
                    <td class="px-5 py-3.5 text-center">{!! $order->payment_status_badge !!}</td>
                    <td class="px-5 py-3.5 text-right">
                        <p class="text-xs text-gray-400">{{ $order->created_at->format('d/m/Y') }}</p>
                        <p class="text-xs text-gray-300">{{ $order->created_at->format('H:i') }}</p>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <a href="{{ route('admin.orders.show', $order) }}"
                            class="inline-flex items-center gap-1.5 bg-red-50 hover:bg-red-100 text-red-600 px-3 py-1.5 rounded-lg text-[11px] font-bold transition">
                            <i class="fa-solid fa-eye"></i>
                            <span>Detail</span>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fa-solid fa-inbox text-2xl text-gray-300"></i>
                            </div>
                            <p class="text-gray-400 font-semibold">Tidak ada pesanan ditemukan</p>
                            <p class="text-xs text-gray-300 mt-1">Coba ubah filter untuk melihat pesanan lainnya</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
        {{ $orders->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
