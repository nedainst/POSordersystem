@extends('layouts.customer')

@section('title', $settings['site_name'] ?? 'Warung Order')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-red-50 to-white">
    {{-- Hero Section --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-red-700 to-red-500 text-white">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                <circle cx="20" cy="20" r="15" fill="white"/>
                <circle cx="80" cy="60" r="20" fill="white"/>
                <circle cx="50" cy="80" r="10" fill="white"/>
            </svg>
        </div>
        <div class="relative max-w-4xl mx-auto px-6 py-16 text-center">
            @if(isset($settings['site_logo']) && $settings['site_logo'])
                <img src="{{ asset('storage/' . $settings['site_logo']) }}" alt="Logo" class="w-24 h-24 mx-auto mb-6 rounded-full shadow-lg border-4 border-white/30 object-cover">
            @else
                <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-white/20 flex items-center justify-center border-4 border-white/30">
                    <i class="fas fa-utensils text-4xl"></i>
                </div>
            @endif
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4">{{ $settings['site_name'] ?? 'Warung Order' }}</h1>
            <p class="text-red-100 text-lg mb-2">{{ $settings['site_tagline'] ?? 'Pesan makanan favorit Anda dengan mudah' }}</p>
            @if(isset($settings['welcome_message']) && $settings['welcome_message'])
                <p class="text-white/80 text-sm mt-4">{{ $settings['welcome_message'] }}</p>
            @endif
        </div>
    </div>

    {{-- Info Section --}}
    <div class="max-w-4xl mx-auto px-6 py-12">
        <div class="bg-white rounded-2xl shadow-lg p-8 -mt-8 relative z-10">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Cara Memesan</h2>
                <p class="text-gray-500">Pesan makanan dalam 3 langkah mudah</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center group">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center group-hover:bg-red-600 transition-colors duration-300">
                        <i class="fas fa-qrcode text-2xl text-red-600 group-hover:text-white transition-colors duration-300"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-2">1. Scan QR Code</h3>
                    <p class="text-sm text-gray-500">Scan QR code yang ada di meja Anda</p>
                </div>
                <div class="text-center group">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center group-hover:bg-red-600 transition-colors duration-300">
                        <i class="fas fa-hand-pointer text-2xl text-red-600 group-hover:text-white transition-colors duration-300"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-2">2. Pilih Menu</h3>
                    <p class="text-sm text-gray-500">Pilih menu makanan & minuman favorit Anda</p>
                </div>
                <div class="text-center group">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center group-hover:bg-red-600 transition-colors duration-300">
                        <i class="fas fa-paper-plane text-2xl text-red-600 group-hover:text-white transition-colors duration-300"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-2">3. Kirim Pesanan</h3>
                    <p class="text-sm text-gray-500">Pesanan langsung masuk ke dapur kami</p>
                </div>
            </div>
        </div>

        {{-- Restaurant Info --}}
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            @if(isset($settings['opening_hours']) && $settings['opening_hours'])
            <div class="bg-white rounded-xl shadow-md p-6 flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <i class="fas fa-clock text-red-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">Jam Buka</h3>
                    <p class="text-sm text-gray-500">{{ $settings['opening_hours'] }}</p>
                </div>
            </div>
            @endif

            @if(isset($settings['site_address']) && $settings['site_address'])
            <div class="bg-white rounded-xl shadow-md p-6 flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <i class="fas fa-map-marker-alt text-red-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">Alamat</h3>
                    <p class="text-sm text-gray-500">{{ $settings['site_address'] }}</p>
                </div>
            </div>
            @endif

            @if(isset($settings['site_phone']) && $settings['site_phone'])
            <div class="bg-white rounded-xl shadow-md p-6 flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <i class="fas fa-phone text-red-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">Telepon</h3>
                    <p class="text-sm text-gray-500">{{ $settings['site_phone'] }}</p>
                </div>
            </div>
            @endif

            @if(isset($settings['wifi_password']) && $settings['wifi_password'])
            <div class="bg-white rounded-xl shadow-md p-6 flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <i class="fas fa-wifi text-red-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">WiFi Password</h3>
                    <p class="text-sm text-gray-500 font-mono">{{ $settings['wifi_password'] }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Footer --}}
    <footer class="bg-gray-900 text-white py-8 mt-12">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <p class="text-gray-400 text-sm">{{ $settings['footer_text'] ?? '© ' . date('Y') . ' ' . ($settings['site_name'] ?? 'Warung Order') . '. All rights reserved.' }}</p>
        </div>
    </footer>
</div>
@endsection
