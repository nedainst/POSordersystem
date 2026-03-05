@extends('layouts.admin')

@section('header', 'Struk Pembayaran')

@push('styles')
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #receipt-area, #receipt-area * {
            visibility: visible;
        }
        #receipt-area {
            position: absolute;
            left: 50%;
            top: 0;
            transform: translateX(-50%);
            width: 80mm;
            padding: 0;
            margin: 0;
            box-shadow: none;
            border: none;
        }
        .no-print {
            display: none !important;
        }
    }
    .receipt-divider {
        border-top: 2px dashed #D1D5DB;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Tombol Aksi --}}
    <div class="no-print flex items-center justify-between">
        <a href="{{ route('admin.orders.show', $payment->order) }}"
            class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-red-600 transition font-medium">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Detail Pesanan</span>
        </a>
        <button onclick="window.print()"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition text-sm shadow-sm active:scale-[0.98]">
            <i class="fa-solid fa-print"></i>
            <span>Cetak Struk</span>
        </button>
    </div>

    {{-- Struk --}}
    <div id="receipt-area" class="max-w-sm mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-8">

            {{-- Header --}}
            <div class="text-center mb-2">
                <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-utensils text-red-600 text-xl"></i>
                </div>
                <h2 class="text-xl font-extrabold text-gray-900">{{ $settings['site_name'] ?? 'Warung' }}</h2>
                @if(!empty($settings['site_address']))
                    <p class="text-xs text-gray-500 mt-1 max-w-[250px] mx-auto leading-relaxed">{{ $settings['site_address'] }}</p>
                @endif
                @if(!empty($settings['site_phone']))
                    <p class="text-xs text-gray-500">
                        <i class="fa-solid fa-phone text-[10px] mr-1"></i>{{ $settings['site_phone'] }}
                    </p>
                @endif
            </div>

            <div class="receipt-divider my-4"></div>

            {{-- Info Pembayaran --}}
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-400 text-xs">No. Pembayaran</span>
                    <span class="font-mono font-bold text-gray-800 text-xs">{{ $payment->payment_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-xs">No. Pesanan</span>
                    <span class="font-mono font-bold text-gray-800 text-xs">{{ $payment->order->order_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-xs">Tanggal</span>
                    <span class="text-gray-700 text-xs">{{ $payment->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-xs">Meja</span>
                    <span class="text-gray-700 text-xs">{{ $payment->order->table->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-xs">Pelanggan</span>
                    <span class="text-gray-700 text-xs">{{ $payment->order->customer_name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-xs">Kasir</span>
                    <span class="text-gray-700 text-xs">{{ $payment->processedBy->name ?? 'System' }}</span>
                </div>
            </div>

            <div class="receipt-divider my-4"></div>

            {{-- Daftar Item --}}
            <div class="space-y-3">
                @foreach($payment->order->items as $item)
                    <div class="text-sm">
                        <div class="flex justify-between items-start">
                            <span class="text-gray-800 font-semibold text-xs flex-1 mr-2">{{ $item->menuItem->name ?? $item->name }}</span>
                            <span class="font-bold text-gray-800 text-xs whitespace-nowrap">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>

            <div class="receipt-divider my-4"></div>

            {{-- Total --}}
            <div class="space-y-1.5">
                <div class="flex justify-between text-xs">
                    <span class="text-gray-400">Subtotal</span>
                    <span class="text-gray-700">Rp {{ number_format($payment->order->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($payment->order->tax > 0)
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-400">Pajak</span>
                        <span class="text-gray-700">Rp {{ number_format($payment->order->tax, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-base font-extrabold border-t-2 border-gray-200 pt-2.5 mt-2">
                    <span class="text-gray-900">TOTAL</span>
                    <span class="text-red-600">Rp {{ number_format($payment->order->total, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="receipt-divider my-4"></div>

            {{-- Info Pembayaran --}}
            <div class="space-y-1.5">
                <div class="flex justify-between text-xs">
                    <span class="text-gray-400">Metode</span>
                    <span class="font-bold text-gray-800">{!! strip_tags($payment->method_label) !!}</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-gray-400">Dibayar</span>
                    <span class="font-bold text-gray-800">Rp {{ number_format($payment->paid_amount, 0, ',', '.') }}</span>
                </div>
                @if($payment->change_amount > 0)
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-400">Kembalian</span>
                        <span class="font-bold text-emerald-600">Rp {{ number_format($payment->change_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
            </div>

            {{-- Status Badge --}}
            <div class="mt-5 text-center">
                @if($payment->status === 'completed')
                    <div class="inline-flex items-center gap-2 px-5 py-2 bg-emerald-100 text-emerald-800 rounded-full">
                        <i class="fa-solid fa-check-circle"></i>
                        <span class="font-extrabold text-sm tracking-wider">LUNAS</span>
                    </div>
                @else
                    <div class="inline-flex items-center gap-2 px-5 py-2 bg-amber-100 text-amber-800 rounded-full">
                        <i class="fa-solid fa-clock"></i>
                        <span class="font-extrabold text-sm tracking-wider">MENUNGGU</span>
                    </div>
                @endif
            </div>

            <div class="receipt-divider my-4"></div>

            {{-- Footer --}}
            <div class="text-center space-y-1">
                <p class="text-sm text-gray-600 font-semibold">Terima kasih!</p>
                <p class="text-[11px] text-gray-400">{{ $settings['site_name'] ?? 'Warung' }}</p>
                <p class="text-[10px] text-gray-300 mt-2">{{ now()->format('d/m/Y H:i:s') }}</p>
            </div>
        </div>
    </div>

    {{-- Tombol Aksi Bawah --}}
    <div class="no-print max-w-sm mx-auto flex gap-3">
        <button onclick="window.print()"
            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition text-sm shadow-sm active:scale-[0.98]">
            <i class="fa-solid fa-print"></i>
            <span>Cetak Struk</span>
        </button>
        <a href="{{ route('admin.orders.show', $payment->order) }}"
            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition text-sm">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali</span>
        </a>
    </div>
</div>
@endsection
