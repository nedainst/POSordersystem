@extends('layouts.admin')

@section('title', 'Meja')
@section('header', 'Kelola Meja')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Daftar Meja</h3>
            <p class="text-sm text-gray-500">Kelola meja dan QR code warung Anda</p>
        </div>
        <a href="{{ route('admin.tables.create') }}" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg font-medium transition-colors shadow-sm">
            <i class="fas fa-plus"></i>
            <span>Tambah Meja</span>
        </a>
    </div>

    {{-- Tables Grid --}}
    @if($tables->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach($tables as $table)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                    {{-- Card Header --}}
                    <div class="px-5 py-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h4 class="text-lg font-bold text-gray-800">{{ $table->name }}</h4>
                            @if($table->status === 'available')
                                <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 px-2.5 py-1 rounded-full text-xs font-medium">
                                    <i class="fas fa-check-circle"></i>
                                    Tersedia
                                </span>
                            @elseif($table->status === 'occupied')
                                <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 px-2.5 py-1 rounded-full text-xs font-medium">
                                    <i class="fas fa-user-check"></i>
                                    Terisi
                                </span>
                            @elseif($table->status === 'reserved')
                                <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 px-2.5 py-1 rounded-full text-xs font-medium">
                                    <i class="fas fa-clock"></i>
                                    Dipesan
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Card Body --}}
                    <div class="px-5 py-4 space-y-3">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <i class="fas fa-users w-5 text-center text-gray-400"></i>
                            <span>Kapasitas: <strong>{{ $table->capacity }} orang</strong></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <i class="fas fa-receipt w-5 text-center text-gray-400"></i>
                            <span>Pesanan Aktif: <strong>{{ $table->active_orders_count ?? 0 }}</strong></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <i class="fas fa-toggle-on w-5 text-center text-gray-400"></i>
                            <span>Status:
                                @if($table->is_active)
                                    <strong class="text-green-600">Aktif</strong>
                                @else
                                    <strong class="text-red-600">Nonaktif</strong>
                                @endif
                            </span>
                        </div>
                    </div>

                    {{-- Card Actions --}}
                    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex flex-wrap gap-2">
                        <a href="{{ route('admin.tables.edit', $table) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition-colors">
                            <i class="fas fa-pen"></i>
                            Edit
                        </a>
                        <button onclick="generateQr({{ $table->id }}, '{{ $table->name }}')" class="inline-flex items-center gap-1.5 text-xs font-medium text-purple-600 hover:text-purple-800 bg-purple-50 hover:bg-purple-100 px-3 py-1.5 rounded-lg transition-colors">
                            <i class="fas fa-qrcode"></i>
                            QR Code
                        </button>
                        <a href="{{ route('admin.tables.print-qr', $table) }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg transition-colors">
                            <i class="fas fa-print"></i>
                            Cetak
                        </a>
                        @if($table->status !== 'available')
                            <button onclick="resetTable({{ $table->id }}, '{{ $table->name }}')" class="inline-flex items-center gap-1.5 text-xs font-medium text-orange-600 hover:text-orange-800 bg-orange-50 hover:bg-orange-100 px-3 py-1.5 rounded-lg transition-colors">
                                <i class="fas fa-redo"></i>
                                Reset
                            </button>
                        @endif
                        <form action="{{ route('admin.tables.destroy', $table) }}" method="POST" class="inline" onsubmit="return confirmDelete(this, 'Meja {{ $table->name }} akan dihapus permanen!')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-medium text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors">
                                <i class="fas fa-trash"></i>
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-chair text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-1">Belum ada meja</h3>
            <p class="text-sm text-gray-500 mb-4">Tambahkan meja pertama Anda untuk memulai</p>
            <a href="{{ route('admin.tables.create') }}" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg font-medium transition-colors">
                <i class="fas fa-plus"></i>
                <span>Tambah Meja</span>
            </a>
        </div>
    @endif
</div>

{{-- QR Code Modal --}}
<div id="qrModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
        {{-- Modal Header --}}
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-qrcode text-red-600 mr-2"></i>
                QR Code <span id="qrTableName"></span>
            </h3>
            <button onclick="closeQrModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="p-6 text-center space-y-4">
            <div id="qrLoading" class="py-8">
                <i class="fas fa-spinner fa-spin text-red-600 text-3xl"></i>
                <p class="text-sm text-gray-500 mt-3">Membuat QR Code...</p>
            </div>
            <div id="qrContent" class="hidden">
                <div id="qrSvgContainer" class="inline-block p-4 bg-white border-2 border-gray-200 rounded-xl"></div>
                <p class="text-xs text-gray-400 mt-3 break-all" id="qrUrl"></p>
            </div>
        </div>

        {{-- Modal Footer --}}
        <div id="qrActions" class="hidden px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-center gap-3">
            <button onclick="printQrFromModal()" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                <i class="fas fa-print"></i>
                Cetak
            </button>
            <button onclick="downloadQr()" class="inline-flex items-center gap-2 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                <i class="fas fa-download"></i>
                Download
            </button>
            <button onclick="closeQrModal()" class="inline-flex items-center gap-2 bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                <i class="fas fa-times"></i>
                Tutup
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentTableId = null;

    function generateQr(tableId, tableName) {
        currentTableId = tableId;
        const modal = document.getElementById('qrModal');
        const loading = document.getElementById('qrLoading');
        const content = document.getElementById('qrContent');
        const actions = document.getElementById('qrActions');
        const tableNameEl = document.getElementById('qrTableName');

        // Show modal with loading
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        loading.classList.remove('hidden');
        content.classList.add('hidden');
        actions.classList.add('hidden');
        tableNameEl.textContent = tableName;

        // Fetch QR code
        fetch(`{{ url('admin/tables') }}/${tableId}/qr`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            loading.classList.add('hidden');
            content.classList.remove('hidden');
            actions.classList.remove('hidden');

            // Decode base64 SVG and display
            const svgHtml = atob(data.qr_svg_base64);
            document.getElementById('qrSvgContainer').innerHTML = svgHtml;
            document.getElementById('qrUrl').textContent = data.url;
        })
        .catch(error => {
            loading.innerHTML = `
                <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                <p class="text-sm text-red-600 mt-3">Gagal membuat QR Code</p>
            `;
            console.error('Error:', error);
        });
    }

    function closeQrModal() {
        const modal = document.getElementById('qrModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function printQrFromModal() {
        if (currentTableId) {
            window.open(`{{ url('admin/tables') }}/${currentTableId}/print-qr`, '_blank');
        }
    }

    function downloadQr() {
        const svgEl = document.querySelector('#qrSvgContainer svg');
        if (!svgEl) return;

        const svgData = new XMLSerializer().serializeToString(svgEl);
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();

        img.onload = function () {
            canvas.width = img.width * 2;
            canvas.height = img.height * 2;
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

            const link = document.createElement('a');
            link.download = `qr-${document.getElementById('qrTableName').textContent.trim()}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        };

        img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)));
    }

    function resetTable(tableId, tableName) {
        Swal.fire({
            title: 'Reset Meja?',
            text: `Yakin ingin mereset meja "${tableName}" ke status tersedia?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal',
            reverseButtons: true,
        }).then((result) => {
            if (!result.isConfirmed) return;

            fetch(`{{ url('admin/tables') }}/${tableId}/reset`, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({icon:'success',title:'Berhasil!',text:'Meja berhasil direset.',confirmButtonColor:'#DC2626',timer:1500,showConfirmButton:false}).then(()=>window.location.reload());
                } else {
                    Swal.fire({icon:'error',title:'Gagal',text:data.message||'Gagal mereset meja',confirmButtonColor:'#DC2626'});
                }
            })
            .catch(error => {
                Swal.fire({icon:'error',title:'Error',text:'Terjadi kesalahan saat mereset meja',confirmButtonColor:'#DC2626'});
                console.error('Error:', error);
            });
        });
    }

    // Close modal on backdrop click
    document.getElementById('qrModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeQrModal();
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeQrModal();
    });
</script>
@endpush
