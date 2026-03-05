@extends('layouts.admin')

@section('title', isset($menuItem) ? 'Edit Menu' : 'Tambah Menu')
@section('header', isset($menuItem) ? 'Edit Menu' : 'Tambah Menu')

@section('content')
<div class="max-w-3xl">
    {{-- Back Link --}}
    <div class="mb-6">
        <a href="{{ route('admin.menu-items.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-red-600 transition-colors text-sm">
            <i class="fas fa-arrow-left"></i>
            <span>Kembali ke Daftar Menu</span>
        </a>
    </div>

    {{-- Form Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-{{ isset($menuItem) ? 'pen' : 'plus' }} text-red-600 mr-2"></i>
                {{ isset($menuItem) ? 'Edit Menu' : 'Tambah Menu Baru' }}
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                {{ isset($menuItem) ? 'Perbarui informasi item menu' : 'Isi informasi untuk membuat item menu baru' }}
            </p>
        </div>

        <form action="{{ isset($menuItem) ? route('admin.menu-items.update', $menuItem) : route('admin.menu-items.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @if(isset($menuItem))
                @method('PUT')
            @endif

            {{-- Nama Menu --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Menu <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name', $menuItem->name ?? '') }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-colors @error('name') border-red-500 @enderror"
                    placeholder="Contoh: Nasi Goreng Spesial">
                @error('name')
                    <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Kategori --}}
            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Kategori <span class="text-red-500">*</span>
                </label>
                <select name="category_id" id="category_id" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-colors bg-white @error('category_id') border-red-500 @enderror">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $menuItem->category_id ?? '') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Deskripsi --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Deskripsi
                </label>
                <textarea name="description" id="description" rows="3"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-colors resize-none @error('description') border-red-500 @enderror"
                    placeholder="Deskripsi singkat tentang menu ini">{{ old('description', $menuItem->description ?? '') }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Harga --}}
            <div>
                <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                    Harga <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium">Rp</span>
                    <input type="number" name="price" id="price" value="{{ old('price', isset($menuItem) ? (int) $menuItem->price : '') }}" required min="0" step="500"
                        class="w-full pl-12 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-colors @error('price') border-red-500 @enderror"
                        placeholder="15000">
                </div>
                @error('price')
                    <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Gambar --}}
            <div>
                <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                    Gambar Menu
                </label>

                @if(isset($menuItem) && $menuItem->image)
                    <div class="mb-3 flex items-center gap-4">
                        <img src="{{ asset('storage/' . $menuItem->image) }}" alt="{{ $menuItem->name }}" class="w-20 h-20 rounded-lg object-cover border border-gray-200">
                        <div class="text-sm text-gray-500">
                            <p>Gambar saat ini</p>
                            <p class="text-xs text-gray-400">Upload gambar baru untuk mengganti</p>
                        </div>
                    </div>
                @endif

                <div class="relative">
                    <input type="file" name="image" id="image" accept="image/*"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-colors file:mr-4 file:py-1.5 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-red-50 file:text-red-600 hover:file:bg-red-100 @error('image') border-red-500 @enderror">
                </div>
                <p class="mt-1.5 text-xs text-gray-400">Format: JPG, PNG, GIF, WebP. Maksimal 2MB.</p>
                @error('image')
                    <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Urutan --}}
            <div>
                <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">
                    Urutan Tampil
                </label>
                <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $menuItem->sort_order ?? 0) }}" min="0"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-colors @error('sort_order') border-red-500 @enderror"
                    placeholder="0">
                <p class="mt-1.5 text-xs text-gray-400">Semakin kecil angka, semakin atas posisinya</p>
                @error('sort_order')
                    <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Status Toggles --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                {{-- Ketersediaan --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Ketersediaan
                    </label>
                    <div class="flex items-center mt-2">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="is_available" value="0">
                            <input type="checkbox" name="is_available" value="1" class="sr-only peer"
                                {{ old('is_available', $menuItem->is_available ?? 1) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-300 peer-focus:ring-2 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                            <span class="ml-3 text-sm text-gray-600">Menu Tersedia</span>
                        </label>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-400">Menu tidak tersedia tidak ditampilkan ke pelanggan</p>
                </div>

                {{-- Unggulan --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Menu Unggulan
                    </label>
                    <div class="flex items-center mt-2">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox" name="is_featured" value="1" class="sr-only peer"
                                {{ old('is_featured', $menuItem->is_featured ?? 0) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-300 peer-focus:ring-2 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-500"></div>
                            <span class="ml-3 text-sm text-gray-600">
                                <i class="fas fa-star text-yellow-500 mr-1"></i>Tandai sebagai Unggulan
                            </span>
                        </label>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-400">Menu unggulan ditampilkan di bagian atas halaman pelanggan</p>
                </div>
            </div>

            {{-- Submit Buttons --}}
            <div class="flex items-center gap-3 pt-4 border-t border-gray-200">
                <button type="submit" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-lg font-medium transition-colors shadow-sm">
                    <i class="fas fa-{{ isset($menuItem) ? 'save' : 'plus' }}"></i>
                    <span>{{ isset($menuItem) ? 'Simpan Perubahan' : 'Tambah Menu' }}</span>
                </button>
                <a href="{{ route('admin.menu-items.index') }}" class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg font-medium transition-colors">
                    <i class="fas fa-times"></i>
                    <span>Batal</span>
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
