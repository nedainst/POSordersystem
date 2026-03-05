<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - {{ \App\Models\SiteSetting::get('site_name', 'Warung Order') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .sidebar-link { transition: all 0.2s; border-radius: 0.75rem; }
        .sidebar-link:hover { background-color: #FEF2F2; color: #DC2626; }
        .sidebar-link.active { background-color: #DC2626; color: white; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.2); }
        .sidebar-link.active i { color: white; }
        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #D1D5DB; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #9CA3AF; }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside id="sidebar" class="w-64 bg-white shadow-xl fixed h-full z-30 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-red-600 flex items-center justify-center">
                        <i class="fas fa-utensils text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-800">{{ \App\Models\SiteSetting::get('site_name', 'Warung Order') }}</h1>
                        <p class="text-xs text-gray-500">Admin Panel</p>
                    </div>
                </div>
            </div>

            <nav class="p-4 space-y-1">
                {{-- POS Button --}}
                <a href="{{ route('admin.pos.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl mb-3 font-bold text-sm transition {{ request()->routeIs('admin.pos.*') ? 'bg-red-600 text-white shadow-lg' : 'bg-gradient-to-r from-red-600 to-red-700 text-white shadow-md hover:shadow-lg hover:from-red-700 hover:to-red-800' }}">
                    <i class="fas fa-cash-register w-5 text-center"></i>
                    <span>Buka POS</span>
                    <i class="fas fa-arrow-right ml-auto text-xs opacity-60"></i>
                </a>

                <a href="{{ route('admin.dashboard') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt w-5 text-center"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.orders.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 {{ request()->routeIs('admin.orders.*') && !request()->routeIs('admin.orders.report') ? 'active' : '' }}">
                    <i class="fas fa-receipt w-5 text-center"></i>
                    <span>Pesanan</span>
                    @php $pendingCount = \App\Models\Order::whereIn('status', ['pending'])->count(); @endphp
                    @if($pendingCount > 0)
                        <span class="ml-auto bg-red-600 text-white text-xs px-2 py-0.5 rounded-full">{{ $pendingCount }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.payments.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                    <i class="fas fa-credit-card w-5 text-center"></i>
                    <span>Pembayaran</span>
                    @php $unpaidCount = \App\Models\Payment::where('status', 'pending')->count(); @endphp
                    @if($unpaidCount > 0)
                        <span class="ml-auto bg-yellow-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $unpaidCount }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.categories.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <i class="fas fa-tags w-5 text-center"></i>
                    <span>Kategori</span>
                </a>
                <a href="{{ route('admin.menu-items.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 {{ request()->routeIs('admin.menu-items.*') ? 'active' : '' }}">
                    <i class="fas fa-hamburger w-5 text-center"></i>
                    <span>Menu</span>
                </a>
                <a href="{{ route('admin.tables.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 {{ request()->routeIs('admin.tables.*') ? 'active' : '' }}">
                    <i class="fas fa-chair w-5 text-center"></i>
                    <span>Meja</span>
                </a>
                <a href="{{ route('admin.orders.report') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 {{ request()->routeIs('admin.orders.report') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar w-5 text-center"></i>
                    <span>Laporan</span>
                </a>
                <a href="{{ route('admin.settings.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i class="fas fa-cog w-5 text-center"></i>
                    <span>Pengaturan</span>
                </a>

                <div class="pt-4 mt-4 border-t border-gray-200">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 w-full text-left">
                            <i class="fas fa-sign-out-alt w-5 text-center"></i>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </nav>
        </aside>

        {{-- Main Content --}}
        <main class="flex-1 lg:ml-64">
            {{-- Top Bar --}}
            <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-20">
                <div class="flex items-center gap-4">
                    <button id="sidebar-toggle" class="lg:hidden text-gray-600 hover:text-red-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h2 class="text-xl font-semibold text-gray-800">@yield('header', 'Dashboard')</h2>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600">
                        <i class="fas fa-user-circle mr-1"></i>
                        {{ Auth::user()->name }}
                    </span>
                </div>
            </header>

            {{-- Content --}}
            <div class="p-6">
                @yield('content')
            </div>
        </main>
    </div>

    {{-- Sidebar Toggle --}}
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-20 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
        document.getElementById('sidebar-toggle')?.addEventListener('click', toggleSidebar);

        // SweetAlert Flash Messages
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '{{ session('success') }}',
                confirmButtonColor: '#DC2626',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
            });
        @endif
        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '{{ session('error') }}',
                confirmButtonColor: '#DC2626',
            });
        @endif
        @if(session('warning'))
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian!',
                text: '{{ session('warning') }}',
                confirmButtonColor: '#DC2626',
            });
        @endif
        @if(session('info'))
            Swal.fire({
                icon: 'info',
                title: 'Informasi',
                text: '{{ session('info') }}',
                confirmButtonColor: '#DC2626',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
            });
        @endif

        // SweetAlert Confirm Delete Helper
        function confirmDelete(formEl, message = 'Data yang dihapus tidak bisa dikembalikan!') {
            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DC2626',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) formEl.submit();
            });
            return false;
        }

        // SweetAlert Confirm Action Helper
        function confirmAction(formEl, title, message, btnText = 'Ya, Lanjutkan!', btnColor = '#DC2626') {
            Swal.fire({
                title: title,
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: btnColor,
                cancelButtonColor: '#6B7280',
                confirmButtonText: btnText,
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) formEl.submit();
            });
            return false;
        }
    </script>
    @stack('scripts')
</body>
</html>
