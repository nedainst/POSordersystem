@extends('layouts.admin')

@section('title', isset($table) ? 'Edit Meja' : 'Tambah Meja')
@section('header', isset($table) ? 'Edit Meja' : 'Tambah Meja')

@section('content')
<div class="max-w-3xl">
    {{-- Back Link --}}
    <div class="mb-6">
        <a href="{{ route('admin.tables.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-red-600 transition-colors text-sm">
            <i class="fas fa-arrow-left"></i>
            <span>Kembali ke Daftar Meja</span>
        </a>
    </div>

    {{-- Form Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-{{ isset($table) ? 'pen' : 'plus' }} text-red-600 mr-2"></i>
                {{ isset($table) ? 'Edit Meja' : 'Tambah Meja Baru' }}
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                {{ isset($table) ? 'Perbarui informasi meja' : 'Isi informasi untuk membuat meja baru' }}
            </p>
        </div>

        <form action="{{ isset($table) ? route('admin.tables.update', $table) : route('admin.tables.store') }}" method="POST" class="p-6 space-y-6">
            @csrf
            @if(isset($table))
                @method('PUT')
            @endif

            {{-- Nama Meja --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Meja <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name', $table->name ?? '') }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-colors @error('name') border-red-500 @enderror"
                    placeholder="Contoh: Meja 1">
                @error('name')
                    <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Kapasitas --}}
            <div>
                <label for="capacity" class="block text-sm font-medium text-gray-700 mb-2">
                    Kapasitas <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" name="capacity" id="capacity" value="{{ old('capacity', $table->capacity ?? 4) }}" required min="1" max="50"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-colors pr-16 @error('capacity') border-red-500 @enderror"
                        placeholder="4">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-400">orang</span>
                </div>
                @error('capacity')
                    <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Status Aktif --}}
            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                        {{ old('is_active', $table->is_active ?? true) ? 'checked' : '' }}
                        class="w-5 h-5 rounded border-gray-300 text-red-600 focus:ring-red-500 transition-colors">
                    <div>
                        <span class="text-sm font-medium text-gray-700">Meja Aktif</span>
                        <p class="text-xs text-gray-400">Nonaktifkan jika meja sedang tidak tersedia</p>
                    </div>
                </label>
                @error('is_active')
                    <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Buttons --}}
            <div class="flex items-center gap-3 pt-4 border-t border-gray-200">
                <button type="submit" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-lg font-medium transition-colors shadow-sm">
                    <i class="fas fa-save"></i>
                    <span>{{ isset($table) ? 'Perbarui Meja' : 'Simpan Meja' }}</span>
                </button>
                <a href="{{ route('admin.tables.index') }}" class="inline-flex items-center gap-2 bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg font-medium transition-colors">
                    <i class="fas fa-times"></i>
                    <span>Batal</span>
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
