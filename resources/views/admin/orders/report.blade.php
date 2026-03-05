@extends('layouts.admin')

@section('header', 'Laporan Pendapatan')

@section('content')
{{-- Date Range Filter --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
    <form method="GET" action="{{ route('admin.orders.report') }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Tanggal Mulai</label>
            <input type="date" name="start_date" value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Tanggal Akhir</label>
            <input type="date" name="end_date" value="{{ request('end_date', now()->format('Y-m-d')) }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                <i class="fas fa-filter mr-1"></i>Tampilkan
            </button>
            <a href="{{ route('admin.orders.report') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition">
                <i class="fas fa-redo mr-1"></i>Reset
            </a>
        </div>
    </form>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Pendapatan</p>
                <h3 class="text-2xl font-bold text-green-600 mt-1">Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}</h3>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-green-100 flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Pesanan</p>
                <h3 class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($totalOrders ?? 0) }}</h3>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-blue-100 flex items-center justify-center">
                <i class="fas fa-receipt text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Rata-rata per Pesanan</p>
                <h3 class="text-2xl font-bold text-red-600 mt-1">Rp {{ number_format($averageOrder ?? 0, 0, ',', '.') }}</h3>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center">
                <i class="fas fa-chart-line text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

{{-- Daily Revenue Table --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100">
    <div class="p-6 border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-800">
            <i class="fas fa-chart-bar text-red-500 mr-2"></i>Pendapatan Harian
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Jumlah Pesanan</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total Pendapatan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($dailyRevenue ?? [] as $day)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm text-gray-700">
                        <i class="fas fa-calendar-day text-gray-400 mr-2"></i>
                        {{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700 text-center">
                        <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full text-xs font-semibold">{{ $day->total_orders }} pesanan</span>
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold text-gray-800 text-right">Rp {{ number_format($day->total_revenue, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-6 py-12 text-center">
                        <i class="fas fa-chart-bar text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-400">Tidak ada data untuk periode ini</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if(!empty($dailyRevenue) && count($dailyRevenue) > 0)
            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                <tr>
                    <td class="px-6 py-4 text-sm font-bold text-gray-800">Total</td>
                    <td class="px-6 py-4 text-sm font-bold text-gray-800 text-center">{{ $totalOrders ?? 0 }} pesanan</td>
                    <td class="px-6 py-4 text-sm font-bold text-red-600 text-right">Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
