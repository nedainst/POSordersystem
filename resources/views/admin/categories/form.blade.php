@extends('layouts.admin')

@section('title', isset($category) ? 'Edit Kategori' : 'Tambah Kategori')
@section('header', isset($category) ? 'Edit Kategori' : 'Tambah Kategori')

@section('content')
<div class="max-w-3xl">
    {{-- Back Link --}}
    <div class="mb-6">
        <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-red-600 transition-colors text-sm">
            <i class="fas fa-arrow-left"></i>
            <span>Kembali ke Daftar Kategori</span>
        </a>
    </div>

    {{-- Form Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-{{ isset($category) ? 'pen' : 'plus' }} text-red-600 mr-2"></i>
                {{ isset($category) ? 'Edit Kategori' : 'Tambah Kategori Baru' }}
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                {{ isset($category) ? 'Perbarui informasi kategori' : 'Isi informasi untuk membuat kategori baru' }}
            </p>
        </div>

        <form action="{{ isset($category) ? route('admin.categories.update', $category) : route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @if(isset($category))
                @method('PUT')
            @endif

            {{-- Nama Kategori --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Kategori <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name', $category->name ?? '') }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-colors @error('name') border-red-500 @enderror"
                    placeholder="Contoh: Makanan Berat">
                @error('name')
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
                    placeholder="Deskripsi singkat tentang kategori ini">{{ old('description', $category->description ?? '') }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Gambar --}}
            <div>
                <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                    Gambar Kategori
                </label>

                @if(isset($category) && $category->image)
                    <div class="mb-3 flex items-center gap-4">
                        <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" class="w-20 h-20 rounded-lg object-cover border border-gray-200">
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

            {{-- Urutan & Status --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                {{-- Urutan Tampil --}}
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">
                        Urutan Tampil
                    </label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0"
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

                {{-- Status Aktif --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Status
                    </label>
                    <div class="flex items-center mt-2">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                                {{ old('is_active', $category->is_active ?? 1) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-300 peer-focus:ring-2 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                            <span class="ml-3 text-sm text-gray-600">Kategori Aktif</span>
                        </label>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-400">Kategori nonaktif tidak ditampilkan ke pelanggan</p>
                </div>
            </div>

            {{-- Submit Buttons --}}
            <div class="flex items-center gap-3 pt-4 border-t border-gray-200">
                <button type="submit" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-lg font-medium transition-colors shadow-sm">
                    <i class="fas fa-{{ isset($category) ? 'save' : 'plus' }}"></i>
                    <span>{{ isset($category) ? 'Simpan Perubahan' : 'Tambah Kategori' }}</span>
                </button>
                <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg font-medium transition-colors">
                    <i class="fas fa-times"></i>
                    <span>Batal</span>
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
