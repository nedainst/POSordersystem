@extends('layouts.admin')

@section('title', 'Menu')
@section('header', 'Kelola Menu')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Daftar Menu</h3>
            <p class="text-sm text-gray-500">Kelola item menu warung Anda</p>
        </div>
        <a href="{{ route('admin.menu-items.create') }}" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg font-medium transition-colors shadow-sm">
            <i class="fas fa-plus"></i>
            <span>Tambah Menu</span>
        </a>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Table Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($menuItems->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left px-6 py-4 font-semibold text-gray-600">Gambar</th>
                            <th class="text-left px-6 py-4 font-semibold text-gray-600">Nama Menu</th>
                            <th class="text-left px-6 py-4 font-semibold text-gray-600">Kategori</th>
                            <th class="text-right px-6 py-4 font-semibold text-gray-600">Harga</th>
                            <th class="text-center px-6 py-4 font-semibold text-gray-600">Tersedia</th>
                            <th class="text-center px-6 py-4 font-semibold text-gray-600">Unggulan</th>
                            <th class="text-center px-6 py-4 font-semibold text-gray-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($menuItems as $item)
                            <tr class="hover:bg-gray-50 transition-colors">
                                {{-- Image --}}
                                <td class="px-6 py-4">
                                    @if($item->image)
                                        <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}" class="w-14 h-14 rounded-lg object-cover border border-gray-200">
                                    @else
                                        <div class="w-14 h-14 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200">
                                            <i class="fas fa-image text-gray-400 text-lg"></i>
                                        </div>
                                    @endif
                                </td>

                                {{-- Name --}}
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-gray-800">{{ $item->name }}</span>
                                    @if($item->description)
                                        <p class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $item->description }}</p>
                                    @endif
                                    <div class="text-xs text-gray-400 mt-0.5">Urutan: {{ $item->sort_order }}</div>
                                </td>

                                {{-- Category --}}
                                <td class="px-6 py-4">
                                    @if($item->category)
                                        <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-xs font-medium">
                                            <i class="fas fa-tag"></i>
                                            {{ $item->category->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                {{-- Price --}}
                                <td class="px-6 py-4 text-right">
                                    <span class="font-semibold text-gray-800">Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                                </td>

                                {{-- Availability Toggle --}}
                                <td class="px-6 py-4 text-center">
                                    <button type="button"
                                        onclick="toggleAvailability({{ $item->id }}, this)"
                                        class="toggle-btn relative inline-flex items-center cursor-pointer"
                                        title="{{ $item->is_available ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        <div class="w-11 h-6 rounded-full transition-colors {{ $item->is_available ? 'bg-green-500' : 'bg-gray-300' }}">
                                            <div class="absolute top-[2px] left-[2px] bg-white border border-gray-300 rounded-full h-5 w-5 transition-transform {{ $item->is_available ? 'translate-x-5' : 'translate-x-0' }}"></div>
                                        </div>
                                    </button>
                                </td>

                                {{-- Featured --}}
                                <td class="px-6 py-4 text-center">
                                    @if($item->is_featured)
                                        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-medium">
                                            <i class="fas fa-star"></i>
                                            Unggulan
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.menu-items.edit', $item) }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors" title="Edit">
                                            <i class="fas fa-pen text-sm"></i>
                                        </a>
                                        <form action="{{ route('admin.menu-items.destroy', $item) }}" method="POST" onsubmit="return confirmDelete(this, 'Menu ini akan dihapus permanen!')">
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

            {{-- Pagination --}}
            @if($menuItems->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $menuItems->links() }}
                </div>
            @endif
        @else
            <div class="px-6 py-16 text-center">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-hamburger text-gray-400 text-2xl"></i>
                </div>
                <h4 class="text-gray-600 font-semibold mb-1">Belum ada menu</h4>
                <p class="text-sm text-gray-400 mb-4">Mulai tambahkan item menu untuk warung Anda</p>
                <a href="{{ route('admin.menu-items.create') }}" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg font-medium transition-colors">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Menu</span>
                </a>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function toggleAvailability(itemId, btn) {
    const track = btn.querySelector('div.w-11');
    const thumb = btn.querySelector('div.absolute');

    fetch(`{{ url('admin/menu-items') }}/${itemId}/toggle`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.is_available) {
            track.classList.remove('bg-gray-300');
            track.classList.add('bg-green-500');
            thumb.classList.remove('translate-x-0');
            thumb.classList.add('translate-x-5');
            btn.title = 'Nonaktifkan';
        } else {
            track.classList.remove('bg-green-500');
            track.classList.add('bg-gray-300');
            thumb.classList.remove('translate-x-5');
            thumb.classList.add('translate-x-0');
            btn.title = 'Aktifkan';
        }
    })
    .catch(error => {
        console.error('Toggle error:', error);
        Swal.fire({icon:'error',title:'Gagal',text:'Gagal mengubah status ketersediaan.',confirmButtonColor:'#DC2626'});
    });
}
</script>
@endpush
@endsection
