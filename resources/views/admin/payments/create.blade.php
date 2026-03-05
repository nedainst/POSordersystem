@extends('layouts.admin')

@section('header', 'Proses Pembayaran')

@push('styles')
<style>
    .quick-btn {
        transition: all 0.15s ease;
    }
    .quick-btn:active {
        transform: scale(0.96);
    }
</style>
@endpush

@section('content')
<div class="space-y-5">
    {{-- Tombol Kembali --}}
    <div>
        <a href="{{ route('admin.orders.show', $order) }}"
            class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-red-600 transition font-medium">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Detail Pesanan</span>
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

        {{-- ==================== LEFT: Ringkasan Pesanan ==================== --}}
        <div class="xl:col-span-3 space-y-5">

            {{-- Info Pesanan Ringkas --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-red-600 to-red-700 flex items-center justify-between">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-clipboard-list"></i>
                        <span>Ringkasan Pesanan</span>
                    </h3>
                    <span class="text-red-100 text-sm font-mono font-bold">{{ $order->order_number }}</span>
                </div>

                {{-- Info Grid --}}
                <div class="px-6 py-4 grid grid-cols-2 sm:grid-cols-4 gap-4 bg-red-50/50 border-b border-red-100">
                    <div class="text-center sm:text-left">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-0.5">Meja</p>
                        <p class="font-bold text-gray-800 text-sm">{{ $order->table->name ?? '-' }}</p>
                    </div>
                    <div class="text-center sm:text-left">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-0.5">Pelanggan</p>
                        <p class="font-bold text-gray-800 text-sm">{{ $order->customer_name ?? '-' }}</p>
                    </div>
                    <div class="text-center sm:text-left">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-0.5">Status</p>
                        <div class="mt-0.5">{!! $order->status_badge !!}</div>
                    </div>
                    <div class="text-center sm:text-left">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-0.5">Waktu</p>
                        <p class="text-gray-700 text-sm">{{ $order->created_at->format('H:i') }}</p>
                    </div>
                </div>

                {{-- Daftar Item --}}
                <div class="p-6">
                    <div class="space-y-0">
                        @foreach($order->items as $item)
                        <div class="flex items-center gap-4 py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                            {{-- Gambar / Icon --}}
                            @if($item->menuItem && $item->menuItem->image)
                            <img src="{{ asset('storage/' . $item->menuItem->image) }}"
                                 alt="{{ $item->menuItem->name }}"
                                 class="w-12 h-12 rounded-xl object-cover flex-shrink-0 shadow-sm">
                            @else
                            <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-utensils text-gray-300"></i>
                            </div>
                            @endif

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-800 text-sm truncate">
                                    {{ $item->menuItem->name ?? $item->name }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $item->quantity }}x @ Rp {{ number_format($item->price, 0, ',', '.') }}
                                </p>
                                @if($item->notes)
                                <p class="text-xs text-amber-600 mt-0.5 italic">
                                    <i class="fa-solid fa-note-sticky mr-1"></i>{{ $item->notes }}
                                </p>
                                @endif
                            </div>

                            {{-- Subtotal --}}
                            <p class="font-bold text-gray-800 text-sm flex-shrink-0">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                            </p>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Total Summary --}}
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-2xl">
                    <div class="space-y-2 max-w-sm ml-auto">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Subtotal ({{ $order->items->count() }} item)</span>
                            <span class="text-gray-700 font-medium">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($order->tax > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Pajak</span>
                            <span class="text-gray-700 font-medium">Rp {{ number_format($order->tax, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between items-center text-lg font-bold border-t-2 border-gray-200 pt-3 mt-1">
                            <span class="text-gray-800">TOTAL</span>
                            <span class="text-red-600 text-xl">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== RIGHT: Form Pembayaran ==================== --}}
        <div class="xl:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden sticky top-20">
                <div class="px-6 py-4 bg-gradient-to-r from-red-600 to-red-700">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-cash-register"></i>
                        <span>Pembayaran</span>
                    </h3>
                </div>

                <form action="{{ route('admin.payments.store', $order) }}" method="POST" class="p-6 space-y-5">
                    @csrf

                    {{-- ── Metode Pembayaran ── --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Metode Pembayaran</label>
                        <div class="grid grid-cols-4 gap-2">
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
                                    class="peer sr-only" {{ $i === 0 ? 'checked' : '' }}>
                                <div class="border-2 border-gray-200 peer-checked:border-red-500 peer-checked:bg-red-50 rounded-xl p-3 text-center transition-all hover:border-gray-300 hover:shadow-sm">
                                    <i class="fa-solid {{ $method['icon'] }} text-lg text-gray-400 peer-checked:text-red-600 mb-1.5 block"></i>
                                    <span class="text-[11px] font-semibold text-gray-500 peer-checked:text-red-600 leading-none block">{{ $method['label'] }}</span>
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @error('payment_method')
                            <p class="text-xs text-red-600 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ── Jumlah Bayar (Tunai) ── --}}
                    <div id="cash-fields">
                        <label for="paid_amount" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Jumlah Bayar</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 font-bold text-sm select-none">Rp</span>
                            <input type="number" name="paid_amount" id="paid_amount" step="100" min="0"
                                value="{{ old('paid_amount', $order->total) }}"
                                class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 text-lg font-bold text-gray-800 transition"
                                placeholder="0">
                        </div>
                        @error('paid_amount')
                            <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror

                        {{-- Tombol Jumlah Cepat --}}
                        <div class="mt-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-2">Pilih Cepat</p>
                            <div class="grid grid-cols-4 gap-1.5">
                                <button type="button"
                                    class="quick-btn quick-amount-btn bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 rounded-lg shadow-sm"
                                    data-amount="{{ $order->total }}">
                                    Uang Pas
                                </button>
                                <button type="button"
                                    class="quick-btn quick-amount-btn bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 rounded-lg"
                                    data-amount="{{ $order->total + 500 }}">
                                    +500
                                </button>
                                <button type="button"
                                    class="quick-btn quick-amount-btn bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 rounded-lg"
                                    data-amount="{{ $order->total + 1000 }}">
                                    +1rb
                                </button>
                                <button type="button"
                                    class="quick-btn quick-amount-btn bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 rounded-lg"
                                    data-amount="{{ $order->total + 2000 }}">
                                    +2rb
                                </button>
                                <button type="button"
                                    class="quick-btn quick-amount-btn bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 rounded-lg"
                                    data-amount="{{ $order->total + 5000 }}">
                                    +5rb
                                </button>
                                <button type="button"
                                    class="quick-btn quick-amount-btn bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 rounded-lg"
                                    data-amount="{{ $order->total + 10000 }}">
                                    +10rb
                                </button>
                                <button type="button"
                                    class="quick-btn quick-amount-btn bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 rounded-lg"
                                    data-amount="{{ $order->total + 20000 }}">
                                    +20rb
                                </button>
                                <button type="button"
                                    class="quick-btn quick-amount-btn bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 rounded-lg"
                                    data-amount="{{ $order->total + 50000 }}">
                                    +50rb
                                </button>
                            </div>
                        </div>

                        {{-- Kembalian --}}
                        <div class="mt-3" id="change-wrapper">
                            <div id="change-box" class="bg-emerald-50 border-2 border-emerald-200 rounded-xl p-4">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <div id="change-icon-wrap" class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center">
                                            <i id="change-icon" class="fa-solid fa-coins text-emerald-600 text-sm"></i>
                                        </div>
                                        <span id="change-label" class="text-sm font-semibold text-emerald-700">Kembalian</span>
                                    </div>
                                    <span id="change-amount" class="text-xl font-bold text-emerald-700">Rp 0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Nomor Referensi (Non-Tunai) ── --}}
                    <div id="non-cash-fields" class="hidden space-y-4">
                        <div>
                            <label for="reference_number" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Nomor Referensi</label>
                            <input type="text" name="reference_number" id="reference_number"
                                value="{{ old('reference_number') }}"
                                class="w-full border-2 border-gray-200 rounded-xl py-3 px-4 focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm font-medium transition"
                                placeholder="Masukkan nomor referensi...">
                            @error('reference_number')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Info Non-Tunai --}}
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                                <div class="text-sm text-blue-700">
                                    <p class="font-semibold mb-1">Pembayaran Non-Tunai</p>
                                    <p class="text-xs text-blue-600">Pastikan pembayaran sudah diterima sebelum mengkonfirmasi.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Catatan ── --}}
                    <div>
                        <label for="notes" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                            Catatan <span class="text-gray-400 normal-case font-normal">(opsional)</span>
                        </label>
                        <textarea name="notes" id="notes" rows="2"
                            class="w-full border-2 border-gray-200 rounded-xl py-3 px-4 focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm resize-none transition"
                            placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
                    </div>

                    {{-- ── Total + Submit ── --}}
                    <div class="space-y-3 pt-2">
                        <div class="bg-gradient-to-br from-red-50 to-red-100 border-2 border-red-200 rounded-xl p-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-[10px] text-red-500 uppercase tracking-wider font-bold">Total Bayar</p>
                                    <p class="text-xs text-red-400 mt-0.5">{{ $order->items->count() }} item</p>
                                </div>
                                <span class="text-2xl font-extrabold text-red-700">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-4 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl text-sm flex items-center justify-center gap-2 active:scale-[0.98]">
                            <i class="fa-solid fa-cash-register text-lg"></i>
                            <span>Proses Pembayaran</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const orderTotal = {{ $order->total }};
        const methodRadios = document.querySelectorAll('input[name="payment_method"]');
        const cashFields = document.getElementById('cash-fields');
        const nonCashFields = document.getElementById('non-cash-fields');
        const paidAmountInput = document.getElementById('paid_amount');
        const changeAmountDisplay = document.getElementById('change-amount');
        const changeBox = document.getElementById('change-box');
        const changeIconWrap = document.getElementById('change-icon-wrap');
        const changeIcon = document.getElementById('change-icon');
        const changeLabel = document.getElementById('change-label');
        const quickAmountButtons = document.querySelectorAll('.quick-amount-btn');

        function togglePaymentFields() {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;
            if (selectedMethod === 'cash') {
                cashFields.classList.remove('hidden');
                nonCashFields.classList.add('hidden');
            } else {
                cashFields.classList.add('hidden');
                nonCashFields.classList.remove('hidden');
                paidAmountInput.value = orderTotal;
                calculateChange();
            }
        }

        function calculateChange() {
            const paid = parseFloat(paidAmountInput.value) || 0;
            const change = paid - orderTotal;

            if (change >= 0) {
                changeAmountDisplay.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(change);
                changeAmountDisplay.className = 'text-xl font-bold text-emerald-700';
                changeBox.className = 'bg-emerald-50 border-2 border-emerald-200 rounded-xl p-4';
                changeIconWrap.className = 'w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center';
                changeIcon.className = 'fa-solid fa-coins text-emerald-600 text-sm';
                changeLabel.className = 'text-sm font-semibold text-emerald-700';
            } else {
                changeAmountDisplay.textContent = '-Rp ' + new Intl.NumberFormat('id-ID').format(Math.abs(change));
                changeAmountDisplay.className = 'text-xl font-bold text-red-600';
                changeBox.className = 'bg-red-50 border-2 border-red-200 rounded-xl p-4';
                changeIconWrap.className = 'w-8 h-8 bg-red-100 rounded-full flex items-center justify-center';
                changeIcon.className = 'fa-solid fa-coins text-red-600 text-sm';
                changeLabel.className = 'text-sm font-semibold text-red-600';
            }
        }

        methodRadios.forEach(function (radio) {
            radio.addEventListener('change', togglePaymentFields);
        });

        paidAmountInput.addEventListener('input', calculateChange);

        quickAmountButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                paidAmountInput.value = this.dataset.amount;
                calculateChange();
                // Visual feedback
                btn.classList.add('ring-2', 'ring-red-400');
                setTimeout(() => btn.classList.remove('ring-2', 'ring-red-400'), 300);
            });
        });

        // Inisialisasi
        togglePaymentFields();
        calculateChange();
    });
</script>
@endpush
