@extends('layouts.admin')

@section('title', 'Pengaturan Website')
@section('header', 'Pengaturan & Kustomisasi Website')

@section('content')
<form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <div class="flex items-center gap-2 mb-2">
                <i class="fas fa-exclamation-triangle"></i>
                <span class="font-semibold">Terdapat kesalahan:</span>
            </div>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ============================================================= --}}
    {{-- 1. Informasi Warung --}}
    {{-- ============================================================= --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                <i class="fas fa-store text-red-600"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Informasi Warung</h3>
                <p class="text-sm text-gray-500">Data dasar warung Anda</p>
            </div>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Nama Warung --}}
            <div>
                <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-heading mr-1 text-red-500"></i> Nama Warung
                </label>
                <input type="text" name="site_name" id="site_name"
                       value="{{ $settings['site_name'] ?? 'Warung Order' }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                       placeholder="Nama warung Anda">
            </div>

            {{-- Tagline --}}
            <div>
                <label for="site_tagline" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-quote-left mr-1 text-red-500"></i> Tagline
                </label>
                <input type="text" name="site_tagline" id="site_tagline"
                       value="{{ $settings['site_tagline'] ?? '' }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                       placeholder="Tagline warung Anda">
            </div>

            {{-- Deskripsi --}}
            <div class="md:col-span-2">
                <label for="site_description" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-align-left mr-1 text-red-500"></i> Deskripsi
                </label>
                <textarea name="site_description" id="site_description" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                          placeholder="Deskripsi singkat tentang warung Anda">{{ $settings['site_description'] ?? '' }}</textarea>
            </div>

            {{-- No. Telepon --}}
            <div>
                <label for="site_phone" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-phone mr-1 text-red-500"></i> No. Telepon
                </label>
                <input type="text" name="site_phone" id="site_phone"
                       value="{{ $settings['site_phone'] ?? '' }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                       placeholder="08xxxxxxxxxx">
            </div>

            {{-- Jam Buka --}}
            <div>
                <label for="opening_hours" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-clock mr-1 text-red-500"></i> Jam Buka
                </label>
                <input type="text" name="opening_hours" id="opening_hours"
                       value="{{ $settings['opening_hours'] ?? '' }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                       placeholder="Senin - Minggu, 08:00 - 22:00">
            </div>

            {{-- Alamat --}}
            <div class="md:col-span-2">
                <label for="site_address" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-map-marker-alt mr-1 text-red-500"></i> Alamat
                </label>
                <textarea name="site_address" id="site_address" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                          placeholder="Alamat lengkap warung Anda">{{ $settings['site_address'] ?? '' }}</textarea>
            </div>

            {{-- Password WiFi --}}
            <div>
                <label for="wifi_password" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-wifi mr-1 text-red-500"></i> Password WiFi
                </label>
                <input type="text" name="wifi_password" id="wifi_password"
                       value="{{ $settings['wifi_password'] ?? '' }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                       placeholder="Password WiFi untuk pelanggan">
            </div>
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- 2. Tampilan Website --}}
    {{-- ============================================================= --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                <i class="fas fa-palette text-red-600"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Tampilan Website</h3>
                <p class="text-sm text-gray-500">Kustomisasi tampilan dan warna website</p>
            </div>
        </div>
        <div class="p-6 space-y-8">
            {{-- Warna --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas fa-swatchbook text-red-500"></i> Pengaturan Warna
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Warna Utama --}}
                    <div>
                        <label for="primary_color" class="block text-sm font-medium text-gray-700 mb-1">Warna Utama</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="primary_color" id="primary_color"
                                   value="{{ $settings['primary_color'] ?? '#DC2626' }}"
                                   class="w-12 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5"
                                   onchange="document.getElementById('primary_color_hex').value = this.value; updatePreview();">
                            <input type="text" id="primary_color_hex"
                                   value="{{ $settings['primary_color'] ?? '#DC2626' }}"
                                   class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition font-mono"
                                   placeholder="#DC2626"
                                   onchange="document.getElementById('primary_color').value = this.value; updatePreview();">
                        </div>
                    </div>

                    {{-- Warna Sekunder --}}
                    <div>
                        <label for="secondary_color" class="block text-sm font-medium text-gray-700 mb-1">Warna Sekunder</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="secondary_color" id="secondary_color"
                                   value="{{ $settings['secondary_color'] ?? '#FFFFFF' }}"
                                   class="w-12 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5"
                                   onchange="document.getElementById('secondary_color_hex').value = this.value; updatePreview();">
                            <input type="text" id="secondary_color_hex"
                                   value="{{ $settings['secondary_color'] ?? '#FFFFFF' }}"
                                   class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition font-mono"
                                   placeholder="#FFFFFF"
                                   onchange="document.getElementById('secondary_color').value = this.value; updatePreview();">
                        </div>
                    </div>

                    {{-- Warna Aksen --}}
                    <div>
                        <label for="accent_color" class="block text-sm font-medium text-gray-700 mb-1">Warna Aksen</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="accent_color" id="accent_color"
                                   value="{{ $settings['accent_color'] ?? '#FEE2E2' }}"
                                   class="w-12 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5"
                                   onchange="document.getElementById('accent_color_hex').value = this.value; updatePreview();">
                            <input type="text" id="accent_color_hex"
                                   value="{{ $settings['accent_color'] ?? '#FEE2E2' }}"
                                   class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition font-mono"
                                   placeholder="#FEE2E2"
                                   onchange="document.getElementById('accent_color').value = this.value; updatePreview();">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Upload Gambar --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas fa-images text-red-500"></i> Gambar & Media
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Logo --}}
                    <div>
                        <label for="site_logo" class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                        @if(!empty($settings['site_logo']))
                            <div class="mb-2 p-2 border border-gray-200 rounded-lg bg-gray-50 inline-block">
                                <img src="{{ asset('storage/' . $settings['site_logo']) }}" alt="Logo saat ini"
                                     class="h-16 w-auto object-contain">
                            </div>
                        @endif
                        <input type="file" name="site_logo" id="site_logo" accept="image/*"
                               class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-red-50 file:text-red-600 hover:file:bg-red-100 file:cursor-pointer cursor-pointer border border-gray-300 rounded-lg">
                        <p class="mt-1 text-xs text-gray-400">Format: JPG, PNG, SVG. Maks 2MB</p>
                    </div>

                    {{-- Hero Image --}}
                    <div>
                        <label for="hero_image" class="block text-sm font-medium text-gray-700 mb-1">Gambar Hero</label>
                        @if(!empty($settings['hero_image']))
                            <div class="mb-2 p-2 border border-gray-200 rounded-lg bg-gray-50 inline-block">
                                <img src="{{ asset('storage/' . $settings['hero_image']) }}" alt="Hero saat ini"
                                     class="h-16 w-auto object-contain">
                            </div>
                        @endif
                        <input type="file" name="hero_image" id="hero_image" accept="image/*"
                               class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-red-50 file:text-red-600 hover:file:bg-red-100 file:cursor-pointer cursor-pointer border border-gray-300 rounded-lg">
                        <p class="mt-1 text-xs text-gray-400">Format: JPG, PNG. Maks 5MB. Rekomendasi: 1920x600px</p>
                    </div>

                    {{-- Favicon --}}
                    <div>
                        <label for="favicon" class="block text-sm font-medium text-gray-700 mb-1">Favicon</label>
                        @if(!empty($settings['favicon']))
                            <div class="mb-2 p-2 border border-gray-200 rounded-lg bg-gray-50 inline-block">
                                <img src="{{ asset('storage/' . $settings['favicon']) }}" alt="Favicon saat ini"
                                     class="h-10 w-auto object-contain">
                            </div>
                        @endif
                        <input type="file" name="favicon" id="favicon" accept="image/*"
                               class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-red-50 file:text-red-600 hover:file:bg-red-100 file:cursor-pointer cursor-pointer border border-gray-300 rounded-lg">
                        <p class="mt-1 text-xs text-gray-400">Format: ICO, PNG. Maks 1MB. Rekomendasi: 32x32px</p>
                    </div>
                </div>
            </div>

            {{-- Teks --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas fa-font text-red-500"></i> Konten Teks
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Pesan Selamat Datang --}}
                    <div class="md:col-span-2">
                        <label for="welcome_message" class="block text-sm font-medium text-gray-700 mb-1">Pesan Selamat Datang</label>
                        <textarea name="welcome_message" id="welcome_message" rows="3"
                                  class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                                  placeholder="Pesan yang ditampilkan di halaman utama">{{ $settings['welcome_message'] ?? '' }}</textarea>
                    </div>

                    {{-- Teks Footer --}}
                    <div class="md:col-span-2">
                        <label for="footer_text" class="block text-sm font-medium text-gray-700 mb-1">Teks Footer</label>
                        <input type="text" name="footer_text" id="footer_text"
                               value="{{ $settings['footer_text'] ?? '' }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                               placeholder="Teks yang ditampilkan di bagian bawah website">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- 3. Pengaturan Pembayaran --}}
    {{-- ============================================================= --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-red-600"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Pengaturan Pembayaran</h3>
                <p class="text-sm text-gray-500">Pajak dan mata uang</p>
            </div>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Tarif Pajak --}}
            <div>
                <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-percent mr-1 text-red-500"></i> Tarif Pajak (%)
                </label>
                <input type="number" name="tax_rate" id="tax_rate" step="0.01" min="0" max="100"
                       value="{{ $settings['tax_rate'] ?? '0' }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                       placeholder="0.00">
                <p class="mt-1 text-xs text-gray-400">Masukkan 0 jika tidak ada pajak</p>
            </div>

            {{-- Simbol Mata Uang --}}
            <div>
                <label for="currency_symbol" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-coins mr-1 text-red-500"></i> Simbol Mata Uang
                </label>
                <input type="text" name="currency_symbol" id="currency_symbol"
                       value="{{ $settings['currency_symbol'] ?? 'Rp' }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                       placeholder="Rp">
            </div>
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- Live Preview --}}
    {{-- ============================================================= --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                <i class="fas fa-eye text-red-600"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Live Preview Warna</h3>
                <p class="text-sm text-gray-500">Pratinjau bagaimana warna terlihat bersama</p>
            </div>
        </div>
        <div class="p-6">
            <div id="color-preview" class="rounded-xl overflow-hidden border border-gray-200 shadow-sm">
                {{-- Preview Header --}}
                <div id="preview-header" class="px-6 py-4 flex items-center justify-between"
                     style="background-color: {{ $settings['primary_color'] ?? '#DC2626' }}; color: {{ $settings['secondary_color'] ?? '#FFFFFF' }};">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-utensils text-xl"></i>
                        <span class="font-bold text-lg" id="preview-site-name">{{ $settings['site_name'] ?? 'Warung Order' }}</span>
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <span>Beranda</span>
                        <span>Menu</span>
                        <span>Kontak</span>
                    </div>
                </div>

                {{-- Preview Hero --}}
                <div id="preview-hero" class="px-6 py-10 text-center"
                     style="background-color: {{ $settings['accent_color'] ?? '#FEE2E2' }};">
                    <h2 class="text-2xl font-bold mb-2" id="preview-welcome"
                        style="color: {{ $settings['primary_color'] ?? '#DC2626' }};">
                        Selamat Datang di <span id="preview-name-hero">{{ $settings['site_name'] ?? 'Warung Order' }}</span>
                    </h2>
                    <p class="text-gray-600 text-sm" id="preview-tagline">{{ $settings['site_tagline'] ?? 'Tagline warung Anda' }}</p>
                    <button class="mt-4 px-6 py-2 rounded-lg text-sm font-semibold transition"
                            id="preview-button"
                            style="background-color: {{ $settings['primary_color'] ?? '#DC2626' }}; color: {{ $settings['secondary_color'] ?? '#FFFFFF' }};">
                        Lihat Menu
                    </button>
                </div>

                {{-- Preview Content --}}
                <div class="px-6 py-6 bg-white">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="rounded-lg p-4 text-center" id="preview-card-1"
                             style="background-color: {{ $settings['accent_color'] ?? '#FEE2E2' }};">
                            <i class="fas fa-hamburger text-2xl mb-2" style="color: {{ $settings['primary_color'] ?? '#DC2626' }};"></i>
                            <p class="text-sm font-medium text-gray-700">Nasi Goreng</p>
                            <p class="text-xs mt-1" style="color: {{ $settings['primary_color'] ?? '#DC2626' }};">Rp 15.000</p>
                        </div>
                        <div class="rounded-lg p-4 text-center" id="preview-card-2"
                             style="background-color: {{ $settings['accent_color'] ?? '#FEE2E2' }};">
                            <i class="fas fa-mug-hot text-2xl mb-2" style="color: {{ $settings['primary_color'] ?? '#DC2626' }};"></i>
                            <p class="text-sm font-medium text-gray-700">Es Teh Manis</p>
                            <p class="text-xs mt-1" style="color: {{ $settings['primary_color'] ?? '#DC2626' }};">Rp 5.000</p>
                        </div>
                        <div class="rounded-lg p-4 text-center" id="preview-card-3"
                             style="background-color: {{ $settings['accent_color'] ?? '#FEE2E2' }};">
                            <i class="fas fa-cookie-bite text-2xl mb-2" style="color: {{ $settings['primary_color'] ?? '#DC2626' }};"></i>
                            <p class="text-sm font-medium text-gray-700">Mie Ayam</p>
                            <p class="text-xs mt-1" style="color: {{ $settings['primary_color'] ?? '#DC2626' }};">Rp 12.000</p>
                        </div>
                    </div>
                </div>

                {{-- Preview Footer --}}
                <div id="preview-footer" class="px-6 py-3 text-center text-sm"
                     style="background-color: {{ $settings['primary_color'] ?? '#DC2626' }}; color: {{ $settings['secondary_color'] ?? '#FFFFFF' }};">
                    <span id="preview-footer-text">{{ $settings['footer_text'] ?? '© 2026 Warung Order. All rights reserved.' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Submit --}}
    <div class="flex items-center justify-end gap-3 mb-6">
        <a href="{{ route('admin.dashboard') }}"
           class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
        <button type="submit"
                class="px-8 py-2.5 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 transition shadow-sm">
            <i class="fas fa-save mr-1"></i> Simpan Pengaturan
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function updatePreview() {
        const primary = document.getElementById('primary_color').value;
        const secondary = document.getElementById('secondary_color').value;
        const accent = document.getElementById('accent_color').value;

        // Header
        const header = document.getElementById('preview-header');
        header.style.backgroundColor = primary;
        header.style.color = secondary;

        // Hero section
        const hero = document.getElementById('preview-hero');
        hero.style.backgroundColor = accent;

        const welcome = document.getElementById('preview-welcome');
        welcome.style.color = primary;

        // Button
        const button = document.getElementById('preview-button');
        button.style.backgroundColor = primary;
        button.style.color = secondary;

        // Cards
        ['preview-card-1', 'preview-card-2', 'preview-card-3'].forEach(id => {
            const card = document.getElementById(id);
            card.style.backgroundColor = accent;
            card.querySelectorAll('i').forEach(icon => icon.style.color = primary);
            card.querySelectorAll('p:last-child').forEach(p => p.style.color = primary);
        });

        // Footer
        const footer = document.getElementById('preview-footer');
        footer.style.backgroundColor = primary;
        footer.style.color = secondary;
    }

    // Sync site name to preview
    document.getElementById('site_name')?.addEventListener('input', function () {
        document.getElementById('preview-site-name').textContent = this.value || 'Warung Order';
        document.getElementById('preview-name-hero').textContent = this.value || 'Warung Order';
    });

    // Sync tagline to preview
    document.getElementById('site_tagline')?.addEventListener('input', function () {
        document.getElementById('preview-tagline').textContent = this.value || 'Tagline warung Anda';
    });

    // Sync footer text to preview
    document.getElementById('footer_text')?.addEventListener('input', function () {
        document.getElementById('preview-footer-text').textContent = this.value || '© 2026 Warung Order. All rights reserved.';
    });
</script>
@endpush
