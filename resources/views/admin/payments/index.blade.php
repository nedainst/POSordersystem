@extends('layouts.admin')

@section('header', 'Pembayaran')

@section('content')
<div class="space-y-6">

    {{-- ══════════ Statistik Card ══════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-emerald-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-money-bill-wave text-emerald-600 text-lg"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] text-gray-400 uppercase tracking-wider font-bold">Pendapatan Hari Ini</p>
                    <p class="text-xl font-extrabold text-emerald-600 mt-0.5 truncate">Rp {{ number_format($stats['total_today'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-clock text-amber-600 text-lg"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] text-gray-400 uppercase tracking-wider font-bold">Menunggu</p>
                    <p class="text-xl font-extrabold text-amber-600 mt-0.5">{{ $stats['pending_count'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-check-circle text-blue-600 text-lg"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] text-gray-400 uppercase tracking-wider font-bold">Transaksi Hari Ini</p>
                    <p class="text-xl font-extrabold text-blue-600 mt-0.5">{{ $stats['completed_today'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition {{ $stats['unpaid_orders'] > 0 ? 'border-red-200 bg-red-50/30' : '' }}">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 {{ $stats['unpaid_orders'] > 0 ? 'bg-red-100' : 'bg-gray-100' }} rounded-2xl flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-exclamation-triangle {{ $stats['unpaid_orders'] > 0 ? 'text-red-600' : 'text-gray-400' }} text-lg"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] text-gray-400 uppercase tracking-wider font-bold">Belum Bayar</p>
                    <p class="text-xl font-extrabold {{ $stats['unpaid_orders'] > 0 ? 'text-red-600' : 'text-gray-400' }} mt-0.5">{{ $stats['unpaid_orders'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════ Filter ══════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-filter text-gray-400"></i>
                Filter Pembayaran
            </p>
        </div>
        <form action="{{ route('admin.payments.index') }}" method="GET" class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                <div>
                    <label for="search" class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Cari</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="fa-solid fa-search text-xs"></i>
                        </span>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                            placeholder="No. pembayaran, pelanggan..."
                            class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm">
                    </div>
                </div>
                <div>
                    <label for="payment_method" class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Metode</label>
                    <select name="payment_method" id="payment_method"
                        class="w-full border border-gray-200 rounded-xl py-2.5 px-3 focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm">
                        <option value="">Semua Metode</option>
                        <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Tunai</option>
                        <option value="qris" {{ request('payment_method') == 'qris' ? 'selected' : '' }}>QRIS</option>
                        <option value="transfer" {{ request('payment_method') == 'transfer' ? 'selected' : '' }}>Transfer</option>
                        <option value="e-wallet" {{ request('payment_method') == 'e-wallet' ? 'selected' : '' }}>E-Wallet</option>
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Status</label>
                    <select name="status" id="status"
                        class="w-full border border-gray-200 rounded-xl py-2.5 px-3 focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Lunas</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Gagal</option>
                        <option value="refund" {{ request('status') == 'refund' ? 'selected' : '' }}>Refund</option>
                    </select>
                </div>
                <div>
                    <label for="date" class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Tanggal</label>
                    <input type="date" name="date" id="date" value="{{ request('date') }}"
                        class="w-full border border-gray-200 rounded-xl py-2.5 px-3 focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm">
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-4 rounded-xl transition text-sm flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-search text-xs"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('admin.payments.index') }}"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-2.5 px-3 rounded-xl transition text-sm flex items-center justify-center"
                        title="Reset">
                        <i class="fa-solid fa-rotate-right"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- ══════════ Tabel Pembayaran ══════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-credit-card text-red-500"></i>
                Riwayat Pembayaran
            </h2>
            <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded-full font-medium">
                {{ $payments->total() }} transaksi
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Pembayaran</th>
                        <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Pesanan</th>
                        <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Pelanggan</th>
                        <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Metode</th>
                        <th class="px-5 py-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">Jumlah</th>
                        <th class="px-5 py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-gray-50/50 transition group">
                            <td class="px-5 py-3.5">
                                <p class="font-mono font-bold text-gray-800 text-xs">{{ $payment->payment_number }}</p>
                                <p class="text-[11px] text-gray-400 mt-0.5">{{ $payment->created_at->format('d/m/Y H:i') }}</p>
                            </td>
                            <td class="px-5 py-3.5">
                                <a href="{{ route('admin.orders.show', $payment->order) }}"
                                    class="font-mono text-xs font-bold text-red-600 hover:text-red-800 hover:underline transition">
                                    {{ $payment->order->order_number }}
                                </a>
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="text-gray-700 font-medium text-sm">{{ $payment->order->customer_name ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-3.5">{!! $payment->method_label !!}</td>
                            <td class="px-5 py-3.5 text-right">
                                <p class="font-bold text-gray-800">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-center">{!! $payment->status_badge !!}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if($payment->status === 'pending')
                                        <form action="{{ route('admin.payments.confirm', $payment) }}" method="POST"
                                            onsubmit="return confirmAction(this, 'Konfirmasi Pembayaran', 'Yakin ingin mengkonfirmasi pembayaran ini?', 'Ya, Konfirmasi!', '#059669')" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold rounded-lg transition shadow-sm"
                                                title="Konfirmasi Pembayaran">
                                                <i class="fa-solid fa-check"></i>
                                                <span class="hidden sm:inline">Konfirmasi</span>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.payments.reject', $payment) }}" method="POST"
                                            onsubmit="return confirmAction(this, 'Tolak Pembayaran', 'Yakin ingin menolak pembayaran ini?', 'Ya, Tolak!', '#DC2626')" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-[11px] font-bold rounded-lg transition"
                                                title="Tolak Pembayaran">
                                                <i class="fa-solid fa-times"></i>
                                                <span class="hidden sm:inline">Tolak</span>
                                            </button>
                                        </form>
                                    @endif
                                    @if($payment->status === 'completed')
                                        <a href="{{ route('admin.payments.receipt', $payment) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold rounded-lg transition shadow-sm"
                                            title="Cetak Struk">
                                            <i class="fa-solid fa-receipt"></i>
                                            <span class="hidden sm:inline">Struk</span>
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.orders.show', $payment->order) }}"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-[11px] font-bold rounded-lg transition"
                                        title="Lihat Pesanan">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-inbox text-2xl text-gray-300"></i>
                                    </div>
                                    <p class="text-gray-400 font-semibold">Belum ada data pembayaran</p>
                                    <p class="text-xs text-gray-300 mt-1">Data pembayaran akan muncul setelah ada transaksi</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                {{ $payments->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
