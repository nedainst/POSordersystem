@extends('layouts.customer')

@section('title', 'Pembayaran - ' . $order->order_number)

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
                    <p class="text-red-100 text-xs">Pembayaran</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-2xl mx-auto px-4 py-6 space-y-4">
        {{-- Order Summary Card --}}
        <div class="bg-white rounded-2xl shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-gray-400">No. Pesanan</p>
                    <p class="font-mono font-bold text-gray-800">{{ $order->order_number }}</p>
                </div>
                {!! $order->status_badge !!}
            </div>

            <div class="space-y-2 mb-4">
                @foreach($order->items as $item)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">{{ $item->quantity }}x {{ $item->menuItem->name }}</span>
                    <span class="text-gray-700 font-medium">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
                @endforeach
            </div>

            <div class="border-t border-gray-200 pt-3 space-y-1">
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
                <div class="flex justify-between font-bold text-lg text-gray-800 pt-2 border-t border-gray-200">
                    <span>Total</span>
                    <span class="text-red-600">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Payment Method Selection --}}
        <div class="bg-white rounded-2xl shadow-md p-6">
            <h3 class="font-bold text-gray-800 mb-4">
                <i class="fas fa-credit-card text-red-500 mr-2"></i>Pilih Metode Pembayaran
            </h3>

            <div class="grid grid-cols-2 gap-3" id="payment-methods">
                {{-- Cash --}}
                <div onclick="selectMethod('cash')" class="payment-option cursor-pointer border-2 border-gray-200 rounded-xl p-4 text-center hover:border-red-500 hover:bg-red-50 transition-all" data-method="cash">
                    <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                    </div>
                    <p class="font-semibold text-gray-800 text-sm">Tunai</p>
                    <p class="text-xs text-gray-400 mt-1">Bayar di kasir</p>
                </div>

                {{-- QRIS --}}
                <div onclick="selectMethod('qris')" class="payment-option cursor-pointer border-2 border-gray-200 rounded-xl p-4 text-center hover:border-red-500 hover:bg-red-50 transition-all" data-method="qris">
                    <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-qrcode text-blue-600 text-xl"></i>
                    </div>
                    <p class="font-semibold text-gray-800 text-sm">QRIS</p>
                    <p class="text-xs text-gray-400 mt-1">Scan QR Code</p>
                </div>

                {{-- Transfer --}}
                <div onclick="selectMethod('transfer')" class="payment-option cursor-pointer border-2 border-gray-200 rounded-xl p-4 text-center hover:border-red-500 hover:bg-red-50 transition-all" data-method="transfer">
                    <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-purple-100 flex items-center justify-center">
                        <i class="fas fa-university text-purple-600 text-xl"></i>
                    </div>
                    <p class="font-semibold text-gray-800 text-sm">Transfer</p>
                    <p class="text-xs text-gray-400 mt-1">Transfer bank</p>
                </div>

                {{-- E-Wallet --}}
                <div onclick="selectMethod('ewallet')" class="payment-option cursor-pointer border-2 border-gray-200 rounded-xl p-4 text-center hover:border-red-500 hover:bg-red-50 transition-all" data-method="ewallet">
                    <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-orange-100 flex items-center justify-center">
                        <i class="fas fa-wallet text-orange-600 text-xl"></i>
                    </div>
                    <p class="font-semibold text-gray-800 text-sm">E-Wallet</p>
                    <p class="text-xs text-gray-400 mt-1">GoPay, OVO, dll</p>
                </div>
            </div>

            {{-- Payment Instructions (shown after selecting method) --}}
            <div id="payment-info" class="hidden mt-4">
                <div id="info-cash" class="payment-info-panel hidden bg-green-50 border border-green-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-green-600 mt-0.5"></i>
                        <div>
                            <p class="font-semibold text-green-800 text-sm">Pembayaran Tunai</p>
                            <p class="text-green-700 text-xs mt-1">Silakan menuju kasir untuk melakukan pembayaran tunai. Tunjukkan nomor pesanan Anda.</p>
                        </div>
                    </div>
                </div>

                <div id="info-qris" class="payment-info-panel hidden bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                        <div>
                            <p class="font-semibold text-blue-800 text-sm">Pembayaran QRIS</p>
                            <p class="text-blue-700 text-xs mt-1">Scan kode QRIS yang tersedia di meja kasir. Kasir akan mengkonfirmasi pembayaran Anda.</p>
                        </div>
                    </div>
                </div>

                <div id="info-transfer" class="payment-info-panel hidden bg-purple-50 border border-purple-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-purple-600 mt-0.5"></i>
                        <div>
                            <p class="font-semibold text-purple-800 text-sm">Transfer Bank</p>
                            <p class="text-purple-700 text-xs mt-1">Transfer ke rekening warung dan konfirmasi ke kasir. Pembayaran akan diverifikasi oleh kasir.</p>
                        </div>
                    </div>
                </div>

                <div id="info-ewallet" class="payment-info-panel hidden bg-orange-50 border border-orange-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-orange-600 mt-0.5"></i>
                        <div>
                            <p class="font-semibold text-orange-800 text-sm">E-Wallet</p>
                            <p class="text-orange-700 text-xs mt-1">Gunakan GoPay, OVO, DANA, atau e-wallet lainnya. Tunjukkan bukti pembayaran ke kasir.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Confirm Button --}}
            <button id="btn-confirm-payment" onclick="confirmPayment()" disabled
                class="w-full mt-5 bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white py-3.5 rounded-xl font-bold text-lg shadow-lg transition-all hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-check-circle mr-2"></i>Konfirmasi Pembayaran
            </button>
        </div>

        {{-- Skip / Pay Later --}}
        <div class="text-center pb-6">
            <a href="{{ route('order.track', $order->id) }}" class="text-sm text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-clock mr-1"></i>Bayar nanti / Lacak pesanan
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let selectedMethod = null;

    function selectMethod(method) {
        selectedMethod = method;

        // Update selected state
        document.querySelectorAll('.payment-option').forEach(el => {
            el.classList.remove('border-red-500', 'bg-red-50', 'ring-2', 'ring-red-200');
            el.classList.add('border-gray-200');
        });

        const selected = document.querySelector(`[data-method="${method}"]`);
        selected.classList.remove('border-gray-200');
        selected.classList.add('border-red-500', 'bg-red-50', 'ring-2', 'ring-red-200');

        // Show info panel
        document.getElementById('payment-info').classList.remove('hidden');
        document.querySelectorAll('.payment-info-panel').forEach(el => el.classList.add('hidden'));
        document.getElementById(`info-${method}`).classList.remove('hidden');

        // Enable confirm button
        document.getElementById('btn-confirm-payment').disabled = false;
    }

    async function confirmPayment() {
        if (!selectedMethod) return;

        const btn = document.getElementById('btn-confirm-payment');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';

        try {
            const response = await fetch('{{ route("order.payment.select", $order->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    payment_method: selectedMethod,
                }),
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = data.redirect;
            } else {
                Swal.fire({icon:'error',title:'Gagal',text:data.message||'Terjadi kesalahan.',confirmButtonColor:'#DC2626'});
            }
        } catch (error) {
            Swal.fire({icon:'error',title:'Error',text:'Terjadi kesalahan. Silakan coba lagi.',confirmButtonColor:'#DC2626'});
            console.error(error);
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Konfirmasi Pembayaran';
    }
</script>
@endpush
@endsection
