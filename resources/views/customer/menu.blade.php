@extends('layouts.customer')

@section('title', 'Menu - ' . ($settings['site_name'] ?? 'Warung Order'))

@push('styles')
<style>
    .menu-item-card {
        transition: all 0.3s ease;
    }
    .menu-item-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .cart-badge {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    .category-pill.active {
        background-color: var(--primary);
        color: white;
    }
    .cart-sidebar {
        transition: transform 0.3s ease;
    }
    .cart-sidebar.open {
        transform: translateX(0);
    }
    .qty-btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: bold;
        transition: all 0.2s;
    }
    .fade-in {
        animation: fadeIn 0.3s ease-in;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50 pb-24">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-red-700 to-red-500 text-white px-4 py-6 sticky top-0 z-30 shadow-lg">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center justify-between">
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
                        <p class="text-red-100 text-xs"><i class="fas fa-chair mr-1"></i>{{ $table->name }}</p>
                    </div>
                </div>
                <button onclick="toggleCart()" class="relative bg-white/20 p-3 rounded-full hover:bg-white/30 transition">
                    <i class="fas fa-shopping-cart text-lg"></i>
                    <span id="cart-count" class="absolute -top-1 -right-1 bg-yellow-400 text-red-800 text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center hidden cart-badge">0</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Search Bar --}}
    <div class="max-w-2xl mx-auto px-4 py-3 sticky top-[88px] z-20 bg-gray-50">
        <div class="relative">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" id="search-input" placeholder="Cari menu..." class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm shadow-sm">
        </div>
    </div>

    {{-- Category Pills --}}
    <div class="max-w-2xl mx-auto px-4 pb-3 sticky top-[148px] z-20 bg-gray-50">
        <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
            <button onclick="filterCategory('all')" class="category-pill active shrink-0 px-4 py-2 rounded-full text-sm font-medium bg-gray-200 text-gray-700 transition" data-category="all">
                Semua
            </button>
            @foreach($categories as $category)
                <button onclick="filterCategory('{{ $category->id }}')" class="category-pill shrink-0 px-4 py-2 rounded-full text-sm font-medium bg-gray-200 text-gray-700 transition" data-category="{{ $category->id }}">
                    {{ $category->name }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Featured Items --}}
    @if($featured->count() > 0)
    <div class="max-w-2xl mx-auto px-4 mb-6">
        <h2 class="text-lg font-bold text-gray-800 mb-3"><i class="fas fa-star text-yellow-500 mr-2"></i>Menu Favorit</h2>
        <div class="flex gap-3 overflow-x-auto no-scrollbar pb-2">
            @foreach($featured as $item)
            <div class="shrink-0 w-44 bg-white rounded-xl shadow-md overflow-hidden menu-item-card cursor-pointer" onclick="addToCart({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->price }}, '{{ $item->image ? asset('storage/' . $item->image) : '' }}')">
                @if($item->image)
                    <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}" class="w-full h-28 object-cover">
                @else
                    <div class="w-full h-28 bg-gradient-to-br from-red-100 to-red-50 flex items-center justify-center">
                        <i class="fas fa-utensils text-3xl text-red-300"></i>
                    </div>
                @endif
                <div class="p-3">
                    <h3 class="font-semibold text-sm text-gray-800 truncate">{{ $item->name }}</h3>
                    <p class="text-primary font-bold text-sm mt-1">Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Menu Items by Category --}}
    <div class="max-w-2xl mx-auto px-4">
        @foreach($categories as $category)
            @if($category->menuItems->count() > 0)
            <div class="category-section mb-6" data-category-id="{{ $category->id }}">
                <h2 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
                    @if($category->image)
                        <img src="{{ asset('storage/' . $category->image) }}" class="w-7 h-7 rounded-full object-cover">
                    @endif
                    {{ $category->name }}
                    <span class="text-xs font-normal text-gray-400">({{ $category->menuItems->count() }} menu)</span>
                </h2>

                <div class="space-y-3">
                    @foreach($category->menuItems as $item)
                    <div class="menu-item-card bg-white rounded-xl shadow-sm overflow-hidden flex" data-name="{{ strtolower($item->name) }}" data-category="{{ $category->id }}">
                        @if($item->image)
                            <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}" class="w-28 h-28 object-cover shrink-0">
                        @else
                            <div class="w-28 h-28 bg-gradient-to-br from-red-50 to-red-100 flex items-center justify-center shrink-0">
                                <i class="fas fa-utensils text-2xl text-red-300"></i>
                            </div>
                        @endif
                        <div class="flex-1 p-3 flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between">
                                    <h3 class="font-semibold text-gray-800 text-sm">{{ $item->name }}</h3>
                                    @if($item->is_featured)
                                        <span class="bg-yellow-100 text-yellow-700 text-[10px] px-1.5 py-0.5 rounded-full font-medium ml-1 shrink-0">
                                            <i class="fas fa-star"></i>
                                        </span>
                                    @endif
                                </div>
                                @if($item->description)
                                    <p class="text-xs text-gray-400 mt-1 line-clamp-2">{{ $item->description }}</p>
                                @endif
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-primary font-bold text-sm">Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                                <button onclick="addToCart({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->price }}, '{{ $item->image ? asset('storage/' . $item->image) : '' }}')"
                                    class="bg-red-600 hover:bg-red-700 text-white w-8 h-8 rounded-full flex items-center justify-center transition text-sm shadow-md">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach
    </div>

    {{-- Empty State for Search --}}
    <div id="empty-search" class="hidden max-w-2xl mx-auto px-4 py-12 text-center">
        <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">Menu tidak ditemukan</p>
    </div>
</div>

{{-- Cart Sidebar --}}
<div id="cart-overlay" class="fixed inset-0 bg-black/50 z-40 hidden" onclick="toggleCart()"></div>
<div id="cart-sidebar" class="cart-sidebar fixed right-0 top-0 bottom-0 w-full max-w-md bg-white z-50 shadow-2xl transform translate-x-full flex flex-col">
    {{-- Cart Header --}}
    <div class="bg-gradient-to-r from-red-700 to-red-500 text-white px-6 py-5">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold"><i class="fas fa-shopping-cart mr-2"></i>Keranjang</h2>
            <button onclick="toggleCart()" class="text-white/80 hover:text-white">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
    </div>

    {{-- Cart Items --}}
    <div id="cart-items" class="flex-1 overflow-y-auto p-4 space-y-3">
        <div id="cart-empty" class="text-center py-12">
            <i class="fas fa-shopping-basket text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">Keranjang masih kosong</p>
            <p class="text-gray-400 text-sm mt-1">Pilih menu untuk mulai memesan</p>
        </div>
    </div>

    {{-- Customer Name & Notes --}}
    <div id="cart-form" class="hidden px-4 py-3 border-t border-gray-100 space-y-2">
        <input type="text" id="customer-name" placeholder="Nama Anda (opsional)" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
        <textarea id="order-notes" placeholder="Catatan pesanan (opsional)" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"></textarea>
    </div>

    {{-- Cart Footer --}}
    <div id="cart-footer" class="hidden border-t border-gray-200 p-4 bg-gray-50">
        <div class="space-y-2 mb-4">
            <div class="flex justify-between text-sm text-gray-600">
                <span>Subtotal</span>
                <span id="cart-subtotal">Rp 0</span>
            </div>
            @if(isset($settings['tax_rate']) && $settings['tax_rate'] > 0)
            <div class="flex justify-between text-sm text-gray-600">
                <span>Pajak ({{ $settings['tax_rate'] }}%)</span>
                <span id="cart-tax">Rp 0</span>
            </div>
            @endif
            <div class="flex justify-between font-bold text-gray-800 text-lg pt-2 border-t border-gray-200">
                <span>Total</span>
                <span id="cart-total">Rp 0</span>
            </div>
        </div>
        <button onclick="submitOrder()" id="btn-order" class="w-full bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white py-3.5 rounded-xl font-bold text-lg shadow-lg transition-all hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas fa-paper-plane mr-2"></i>Kirim Pesanan
        </button>
    </div>
</div>

{{-- Floating Cart Button --}}
<div id="floating-cart" class="fixed bottom-6 left-0 right-0 z-30 px-4 hidden">
    <div class="max-w-2xl mx-auto">
        <button onclick="toggleCart()" class="w-full bg-gradient-to-r from-red-600 to-red-500 text-white py-4 rounded-2xl font-bold shadow-2xl flex items-center justify-between px-6 hover:from-red-700 hover:to-red-600 transition-all">
            <div class="flex items-center gap-3">
                <div class="bg-white/20 w-10 h-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <span id="floating-cart-count">0 item</span>
            </div>
            <span id="floating-cart-total" class="text-lg">Rp 0</span>
        </button>
    </div>
</div>

{{-- Order Success Modal --}}
<div id="success-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full p-8 text-center fade-in">
        <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-green-100 flex items-center justify-center">
            <i class="fas fa-check text-3xl text-green-600"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Pesanan Terkirim!</h2>
        <p class="text-gray-500 mb-2">Pesanan Anda sedang diproses</p>
        <p class="text-sm text-gray-400 mb-6">No. Pesanan: <span id="order-number" class="font-mono font-bold text-gray-700"></span></p>
        <a id="track-order-link" href="#" class="block w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-xl font-semibold mb-3 transition">
            <i class="fas fa-credit-card mr-2"></i>Lanjut ke Pembayaran
        </a>
        <button onclick="closeSuccessModal()" class="text-gray-500 hover:text-gray-700 text-sm">Pesan Lagi</button>
    </div>
</div>

@push('scripts')
<script>
    const tableId = {{ $table->id }};
    const taxRate = {{ isset($settings['tax_rate']) ? (float)$settings['tax_rate'] : 0 }};
    let cart = {};

    function formatRupiah(amount) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
    }

    function addToCart(id, name, price, image) {
        if (cart[id]) {
            cart[id].quantity++;
        } else {
            cart[id] = { id, name, price, image, quantity: 1, notes: '' };
        }
        updateCartUI();
        showToast(name + ' ditambahkan ke keranjang');
    }

    function removeFromCart(id) {
        delete cart[id];
        updateCartUI();
    }

    function updateQuantity(id, delta) {
        if (cart[id]) {
            cart[id].quantity += delta;
            if (cart[id].quantity <= 0) {
                delete cart[id];
            }
        }
        updateCartUI();
    }

    function updateCartUI() {
        const items = Object.values(cart);
        const totalItems = items.reduce((sum, item) => sum + item.quantity, 0);
        const subtotal = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const tax = subtotal * (taxRate / 100);
        const total = subtotal + tax;

        // Update cart count badge
        const badge = document.getElementById('cart-count');
        if (totalItems > 0) {
            badge.textContent = totalItems;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }

        // Update floating cart
        const floatingCart = document.getElementById('floating-cart');
        if (totalItems > 0) {
            floatingCart.classList.remove('hidden');
            document.getElementById('floating-cart-count').textContent = totalItems + ' item';
            document.getElementById('floating-cart-total').textContent = formatRupiah(total);
        } else {
            floatingCart.classList.add('hidden');
        }

        // Update cart items
        const cartItemsDiv = document.getElementById('cart-items');
        const cartEmpty = document.getElementById('cart-empty');
        const cartForm = document.getElementById('cart-form');
        const cartFooter = document.getElementById('cart-footer');

        if (items.length === 0) {
            cartEmpty.classList.remove('hidden');
            cartForm.classList.add('hidden');
            cartFooter.classList.add('hidden');
            cartItemsDiv.innerHTML = cartEmpty.outerHTML;
            return;
        }

        cartForm.classList.remove('hidden');
        cartFooter.classList.remove('hidden');

        let html = '';
        items.forEach(item => {
            html += `
                <div class="bg-gray-50 rounded-xl p-3 flex gap-3 fade-in">
                    ${item.image ?
                        `<img src="${item.image}" class="w-16 h-16 rounded-lg object-cover shrink-0">` :
                        `<div class="w-16 h-16 rounded-lg bg-red-100 flex items-center justify-center shrink-0"><i class="fas fa-utensils text-red-300"></i></div>`
                    }
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-sm text-gray-800 truncate">${item.name}</h4>
                        <p class="text-primary text-sm font-medium">${formatRupiah(item.price)}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <button onclick="updateQuantity(${item.id}, -1)" class="qty-btn bg-red-100 text-red-600 hover:bg-red-200">-</button>
                            <span class="text-sm font-bold w-6 text-center">${item.quantity}</span>
                            <button onclick="updateQuantity(${item.id}, 1)" class="qty-btn bg-red-100 text-red-600 hover:bg-red-200">+</button>
                            <span class="text-xs text-gray-400 ml-2">${formatRupiah(item.price * item.quantity)}</span>
                            <button onclick="removeFromCart(${item.id})" class="ml-auto text-gray-400 hover:text-red-600 transition">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        cartItemsDiv.innerHTML = html;

        // Update totals
        document.getElementById('cart-subtotal').textContent = formatRupiah(subtotal);
        if (document.getElementById('cart-tax')) {
            document.getElementById('cart-tax').textContent = formatRupiah(tax);
        }
        document.getElementById('cart-total').textContent = formatRupiah(total);
    }

    function toggleCart() {
        const sidebar = document.getElementById('cart-sidebar');
        const overlay = document.getElementById('cart-overlay');
        sidebar.classList.toggle('translate-x-full');
        overlay.classList.toggle('hidden');
    }

    async function submitOrder() {
        const items = Object.values(cart);
        if (items.length === 0) return;

        const btn = document.getElementById('btn-order');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';

        try {
            const response = await fetch('{{ route("order.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    table_id: tableId,
                    customer_name: document.getElementById('customer-name').value,
                    notes: document.getElementById('order-notes').value,
                    items: items.map(item => ({
                        menu_item_id: item.id,
                        quantity: item.quantity,
                        notes: item.notes,
                    })),
                }),
            });

            const data = await response.json();

            if (data.success) {
                document.getElementById('order-number').textContent = data.order.order_number;
                document.getElementById('track-order-link').href = '/order/' + data.order.id + '/payment';
                document.getElementById('success-modal').classList.remove('hidden');
                document.getElementById('success-modal').classList.add('flex');

                // Reset cart
                cart = {};
                updateCartUI();
                toggleCart();

                // Play notification sound
                if ('vibrate' in navigator) {
                    navigator.vibrate(200);
                }
            } else {
                Swal.fire({icon:'error',title:'Gagal',text:data.message||'Terjadi kesalahan. Silakan coba lagi.',confirmButtonColor:'#DC2626'});
            }
        } catch (error) {
            Swal.fire({icon:'error',title:'Error',text:'Terjadi kesalahan. Pastikan Anda terhubung ke internet.',confirmButtonColor:'#DC2626'});
            console.error(error);
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Kirim Pesanan';
    }

    function closeSuccessModal() {
        document.getElementById('success-modal').classList.add('hidden');
        document.getElementById('success-modal').classList.remove('flex');
    }

    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 bg-gray-800 text-white px-4 py-2 rounded-lg text-sm z-[70] fade-in shadow-lg';
        toast.innerHTML = '<i class="fas fa-check-circle mr-2 text-green-400"></i>' + message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    }

    // Search functionality
    document.getElementById('search-input').addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.menu-item-card[data-name]');
        const sections = document.querySelectorAll('.category-section');
        let visibleCount = 0;

        cards.forEach(card => {
            const name = card.dataset.name;
            if (name.includes(query) || query === '') {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        sections.forEach(section => {
            const visibleItems = section.querySelectorAll('.menu-item-card[data-name]:not([style*="display: none"])');
            section.style.display = visibleItems.length > 0 ? '' : 'none';
        });

        document.getElementById('empty-search').classList.toggle('hidden', visibleCount > 0);
    });

    // Category filter
    function filterCategory(categoryId) {
        document.querySelectorAll('.category-pill').forEach(pill => {
            pill.classList.toggle('active', pill.dataset.category === categoryId);
        });

        const sections = document.querySelectorAll('.category-section');
        sections.forEach(section => {
            if (categoryId === 'all') {
                section.style.display = '';
            } else {
                section.style.display = section.dataset.categoryId === categoryId ? '' : 'none';
            }
        });
    }
</script>
@endpush
@endsection
