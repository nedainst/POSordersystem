@extends('layouts.admin')

@section('title', 'Kategori')
@section('header', 'Kelola Kategori')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Daftar Kategori</h3>
            <p class="text-sm text-gray-500">Kelola kategori menu warung Anda</p>
        </div>
        <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg font-medium transition-colors shadow-sm">
            <i class="fas fa-plus"></i>
            <span>Tambah Kategori</span>
        </a>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($categories->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left px-6 py-4 font-semibold text-gray-600">Gambar</th>
                            <th class="text-left px-6 py-4 font-semibold text-gray-600">Nama</th>
                            <th class="text-left px-6 py-4 font-semibold text-gray-600">Deskripsi</th>
                            <th class="text-center px-6 py-4 font-semibold text-gray-600">Jumlah Menu</th>
                            <th class="text-center px-6 py-4 font-semibold text-gray-600">Status</th>
                            <th class="text-center px-6 py-4 font-semibold text-gray-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($categories as $category)
                            <tr class="hover:bg-gray-50 transition-colors">
                                {{-- Image --}}
                                <td class="px-6 py-4">
                                    @if($category->image)
                                        <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" class="w-14 h-14 rounded-lg object-cover border border-gray-200">
                                    @else
                                        <div class="w-14 h-14 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200">
                                            <i class="fas fa-image text-gray-400 text-lg"></i>
                                        </div>
                                    @endif
                                </td>

                                {{-- Name --}}
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-gray-800">{{ $category->name }}</span>
                                    <div class="text-xs text-gray-400 mt-0.5">Urutan: {{ $category->sort_order }}</div>
                                </td>

                                {{-- Description --}}
                                <td class="px-6 py-4 text-gray-600 max-w-xs">
                                    <p class="truncate">{{ $category->description ?? '-' }}</p>
                                </td>

                                {{-- Menu Count --}}
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-xs font-medium">
                                        <i class="fas fa-utensils"></i>
                                        {{ $category->menu_items_count }} menu
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4 text-center">
                                    @if($category->is_active)
                                        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-medium">
                                            <i class="fas fa-check-circle"></i>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-medium">
                                            <i class="fas fa-times-circle"></i>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors" title="Edit">
                                            <i class="fas fa-pen text-sm"></i>
                                        </a>
                                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirmDelete(this, 'Kategori ini akan dihapus permanen!')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors" title="Hapus">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-16 text-center">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-tags text-gray-400 text-2xl"></i>
                </div>
                <h4 class="text-gray-600 font-semibold mb-1">Belum ada kategori</h4>
                <p class="text-sm text-gray-400 mb-4">Mulai tambahkan kategori untuk mengatur menu Anda</p>
                <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg font-medium transition-colors">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Kategori</span>
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
