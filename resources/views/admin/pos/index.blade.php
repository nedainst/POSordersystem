<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS - {{ \App\Models\SiteSetting::get('site_name', 'Warung Order') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/app.css'])
    <style>
        *{font-family:'Inter',sans-serif;box-sizing:border-box}
        body{overflow:hidden;background:#f8fafc}
        .scroll::-webkit-scrollbar{width:4px}
        .scroll::-webkit-scrollbar-thumb{background:#ddd;border-radius:4px}
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button{-webkit-appearance:none}
        input[type=number]{-moz-appearance:textfield}
    </style>
</head>
<body class="h-screen">

<div class="flex h-screen overflow-hidden">

    {{-- ====== LEFT: MENU ====== --}}
    <div class="flex-1 flex flex-col min-w-0">

        {{-- Top bar --}}
        <div class="bg-white border-b px-5 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.dashboard') }}" class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center text-white hover:bg-red-700">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                </a>
                <div>
                    <h1 class="text-sm font-bold text-gray-800">POS Kasir</h1>
                    <p class="text-[10px] text-gray-400">{{ now()->translatedFormat('d M Y') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="loadRecent()" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-2 rounded-lg font-semibold">
                    <i class="fa-solid fa-clock-rotate-left mr-1"></i>Riwayat
                </button>
                <div class="text-xs text-gray-500 bg-gray-50 px-3 py-2 rounded-lg font-semibold">
                    <i class="fa-solid fa-user mr-1"></i>{{ Auth::user()->name }}
                </div>
            </div>
        </div>

        {{-- Search --}}
        <div class="bg-white border-b px-5 py-3 space-y-2">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="search" placeholder="Cari menu..."
                    class="w-full pl-9 pr-8 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none">
                <button id="clearSearch" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 hidden">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>
            {{-- Categories --}}
            <div class="flex gap-1.5 overflow-x-auto" id="cats">
                <button class="cat active text-xs font-semibold px-3 py-1.5 rounded-full whitespace-nowrap bg-red-600 text-white" data-id="all">Semua</button>
                @foreach($categories as $c)
                <button class="cat text-xs font-semibold px-3 py-1.5 rounded-full whitespace-nowrap bg-gray-100 text-gray-600 hover:bg-red-50 hover:text-red-600" data-id="{{ $c->id }}">{{ $c->name }}</button>
                @endforeach
            </div>
        </div>

        {{-- Grid --}}
        <div class="flex-1 overflow-y-auto scroll p-4" id="gridWrap">
            <div id="grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3"></div>
            <div id="empty" class="hidden text-center py-20">
                <i class="fa-solid fa-utensils text-4xl text-gray-200 mb-3"></i>
                <p class="text-gray-400 text-sm font-semibold">Tidak ditemukan</p>
            </div>
        </div>
    </div>

    {{-- ====== RIGHT: CART ====== --}}
    <div class="w-[370px] bg-white border-l flex flex-col h-screen max-h-screen overflow-hidden">

        {{-- Header --}}
        <div class="px-4 py-3 border-b flex items-center justify-between shrink-0">
            <h2 class="text-sm font-bold text-gray-800"><i class="fa-solid fa-cart-shopping text-red-500 mr-1.5"></i>Keranjang <span id="cartBadge" class="text-gray-400 font-normal">(0)</span></h2>
            <button onclick="clearCart()" id="btnClear" class="text-[11px] text-red-500 font-semibold hover:underline hidden">Kosongkan</button>
        </div>

        {{-- Meja & Customer --}}
        <div class="px-4 py-2.5 border-b grid grid-cols-2 gap-2 shrink-0">
            <div>
                <label class="text-[10px] text-gray-400 font-bold uppercase">Meja</label>
                <select id="table" class="w-full border rounded-lg px-2 py-1.5 text-xs font-semibold mt-0.5 focus:ring-1 focus:ring-red-500">
                    @foreach($tables as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[10px] text-gray-400 font-bold uppercase">Pelanggan</label>
                <input type="text" id="customer" value="Walk-in" class="w-full border rounded-lg px-2 py-1.5 text-xs font-semibold mt-0.5 focus:ring-1 focus:ring-red-500">
            </div>
        </div>

        {{-- Items --}}
        <div class="flex-1 overflow-y-auto scroll min-h-0" id="cartArea">
            <div id="cartEmpty" class="flex flex-col items-center justify-center h-full text-center px-4">
                <i class="fa-solid fa-cart-shopping text-3xl text-gray-200 mb-2"></i>
                <p class="text-xs text-gray-400 font-semibold">Keranjang kosong</p>
                <p class="text-[10px] text-gray-300 mt-0.5">Klik menu untuk menambahkan</p>
            </div>
            <div id="cartList" class="hidden divide-y"></div>
        </div>

        {{-- Totals --}}
        <div class="border-t bg-gray-50 px-4 py-3 space-y-1 shrink-0">
            <div class="flex justify-between text-xs text-gray-500">
                <span>Subtotal</span><span id="dSubtotal" class="font-semibold text-gray-700">Rp 0</span>
            </div>
            <div class="flex justify-between text-xs text-gray-500">
                <span>Pajak {{ $taxRate }}%</span><span id="dTax" class="font-semibold text-gray-700">Rp 0</span>
            </div>
            <div class="flex justify-between text-sm font-bold text-gray-800 pt-1 border-t mt-1">
                <span>TOTAL</span><span id="dTotal" class="text-red-600">Rp 0</span>
            </div>
        </div>

        {{-- Pay button --}}
        <div class="p-4 pt-2 shrink-0">
            <button onclick="openPay()" id="btnPay" disabled
                class="w-full bg-red-600 hover:bg-red-700 disabled:bg-gray-200 disabled:text-gray-400 text-white font-bold py-3 rounded-xl text-sm transition">
                <i class="fa-solid fa-cash-register mr-1.5"></i>Bayar
            </button>
        </div>
    </div>
</div>

{{-- ====== PAYMENT MODAL ====== --}}
<div id="payModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40" onclick="closePay()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[460px] max-h-[90vh] bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col">
        {{-- Header --}}
        <div class="bg-red-600 px-5 py-4 flex items-center justify-between">
            <h3 class="text-white font-bold"><i class="fa-solid fa-cash-register mr-2"></i>Pembayaran</h3>
            <button onclick="closePay()" class="text-white/70 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="flex-1 overflow-y-auto scroll p-5 space-y-4">
            {{-- Total --}}
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
                <p class="text-[10px] text-red-400 uppercase font-bold tracking-wider">Total Bayar</p>
                <p id="payTotal" class="text-3xl font-extrabold text-red-700 mt-1">Rp 0</p>
            </div>

            {{-- Method --}}
            <div>
                <label class="text-[10px] text-gray-400 uppercase font-bold mb-2 block">Metode</label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="method" value="cash" class="peer sr-only" checked>
                        <div class="border-2 peer-checked:border-red-500 peer-checked:bg-red-50 rounded-xl p-2.5 text-center hover:border-gray-300">
                            <i class="fa-solid fa-money-bill-wave text-gray-400 peer-checked:text-red-500 text-base"></i>
                            <p class="text-[10px] font-bold text-gray-500 mt-1">Tunai</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="method" value="qris" class="peer sr-only">
                        <div class="border-2 peer-checked:border-red-500 peer-checked:bg-red-50 rounded-xl p-2.5 text-center hover:border-gray-300">
                            <i class="fa-solid fa-qrcode text-gray-400 peer-checked:text-red-500 text-base"></i>
                            <p class="text-[10px] font-bold text-gray-500 mt-1">QRIS</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="method" value="transfer" class="peer sr-only">
                        <div class="border-2 peer-checked:border-red-500 peer-checked:bg-red-50 rounded-xl p-2.5 text-center hover:border-gray-300">
                            <i class="fa-solid fa-building-columns text-gray-400 peer-checked:text-red-500 text-base"></i>
                            <p class="text-[10px] font-bold text-gray-500 mt-1">Transfer</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="method" value="ewallet" class="peer sr-only">
                        <div class="border-2 peer-checked:border-red-500 peer-checked:bg-red-50 rounded-xl p-2.5 text-center hover:border-gray-300">
                            <i class="fa-solid fa-wallet text-gray-400 peer-checked:text-red-500 text-base"></i>
                            <p class="text-[10px] font-bold text-gray-500 mt-1">E-Wallet</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Cash input --}}
            <div id="cashBox">
                <label class="text-[10px] text-gray-400 uppercase font-bold mb-1.5 block">Jumlah Bayar</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">Rp</span>
                    <input type="number" id="paidAmt" class="w-full pl-10 pr-3 py-3 border-2 rounded-xl text-xl font-bold focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none" placeholder="0">
                </div>
                <div class="flex gap-1.5 mt-2">
                    <button type="button" class="qamt flex-1 bg-red-600 text-white text-[11px] font-bold py-2 rounded-lg hover:bg-red-700" data-t="exact">Uang Pas</button>
                    <button type="button" class="qamt flex-1 bg-gray-100 text-gray-600 text-[11px] font-bold py-2 rounded-lg hover:bg-gray-200" data-t="add" data-v="5000">+5rb</button>
                    <button type="button" class="qamt flex-1 bg-gray-100 text-gray-600 text-[11px] font-bold py-2 rounded-lg hover:bg-gray-200" data-t="add" data-v="10000">+10rb</button>
                    <button type="button" class="qamt flex-1 bg-gray-100 text-gray-600 text-[11px] font-bold py-2 rounded-lg hover:bg-gray-200" data-t="add" data-v="20000">+20rb</button>
                    <button type="button" class="qamt flex-1 bg-gray-100 text-gray-600 text-[11px] font-bold py-2 rounded-lg hover:bg-gray-200" data-t="add" data-v="50000">+50rb</button>
                </div>
                {{-- Change --}}
                <div id="changeBox" class="mt-3 rounded-xl p-3 flex items-center justify-between bg-green-50 border border-green-200">
                    <span id="changeLabel" class="text-sm font-semibold text-green-700"><i class="fa-solid fa-coins mr-1"></i>Kembalian</span>
                    <span id="changeAmt" class="text-lg font-extrabold text-green-700">Rp 0</span>
                </div>
            </div>

            {{-- Non-cash ref --}}
            <div id="refBox" class="hidden">
                <label class="text-[10px] text-gray-400 uppercase font-bold mb-1.5 block">No. Referensi</label>
                <input type="text" id="refNum" class="w-full border-2 rounded-xl py-2.5 px-3 text-sm font-medium focus:ring-2 focus:ring-red-500 outline-none" placeholder="Opsional">
                <p class="text-[10px] text-blue-500 mt-1.5"><i class="fa-solid fa-info-circle mr-1"></i>Pastikan pembayaran sudah diterima.</p>
            </div>
        </div>

        <div class="p-5 border-t">
            <button onclick="doProcess()" id="btnProcess" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3.5 rounded-xl text-sm">
                <i class="fa-solid fa-check-circle mr-1.5"></i>Proses Pembayaran
            </button>
        </div>
    </div>
</div>

{{-- ====== RECENT MODAL ====== --}}
<div id="recentModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40" onclick="closeRecent()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[560px] max-h-[80vh] bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col">
        <div class="bg-gray-700 px-5 py-4 flex items-center justify-between">
            <h3 class="text-white font-bold"><i class="fa-solid fa-clock-rotate-left mr-2"></i>Pesanan Hari Ini</h3>
            <button onclick="closeRecent()" class="text-white/70 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="flex-1 overflow-y-auto scroll" id="recentList">
            <div class="p-8 text-center text-gray-400"><i class="fa-solid fa-spinner fa-spin text-xl"></i></div>
        </div>
    </div>
</div>

{{-- ====== SUCCESS MODAL ====== --}}
<div id="successModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[380px] bg-white rounded-2xl shadow-xl p-6 text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fa-solid fa-check text-2xl text-green-600"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Berhasil!</h3>
        <p id="sOrderNum" class="text-xs text-gray-400 mt-0.5"></p>
        <div class="bg-gray-50 rounded-xl p-3 mt-4 space-y-2 text-left text-sm">
            <div class="flex justify-between"><span class="text-gray-400">Total</span><span id="sTotal" class="font-bold"></span></div>
            <div id="sChangeRow" class="flex justify-between hidden"><span class="text-gray-400">Kembalian</span><span id="sChange" class="font-bold text-green-600"></span></div>
            <div class="flex justify-between"><span class="text-gray-400">Metode</span><span id="sMethod" class="font-bold"></span></div>
        </div>
        <div class="flex gap-2 mt-4">
            <a id="sReceipt" href="#" target="_blank" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-xl text-sm text-center"><i class="fa-solid fa-print mr-1"></i>Struk</a>
            <button onclick="closeSuccess()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl text-sm"><i class="fa-solid fa-plus mr-1"></i>Order Baru</button>
        </div>
    </div>
</div>

{{-- ====== JS ====== --}}
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const TAX = {{ $taxRate }} / 100;
const ITEMS = @json($menuItemsJson);
const COLORS = ['#FEF3C7','#DBEAFE','#D1FAE5','#FEE2E2','#EDE9FE','#CFFAFE','#FEF9C3','#FFE4E6'];

let cart = [], catId = 'all', query = '';

function rp(n){ return 'Rp '+new Intl.NumberFormat('id-ID').format(Math.round(n)) }
function toast(m,t='success'){
    Swal.fire({
        icon: t==='error'?'error':t==='info'?'info':'success',
        title: m,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
    });
}

// ── RENDER MENU ──
function render(){
    const g = document.getElementById('grid'), e = document.getElementById('empty');
    let f = ITEMS;
    if(catId!=='all') f = f.filter(i=>i.category_id==catId);
    if(query){ const q=query.toLowerCase(); f=f.filter(i=>i.name.toLowerCase().includes(q)||(i.category&&i.category.toLowerCase().includes(q))) }

    if(!f.length){ g.innerHTML=''; e.classList.remove('hidden'); return }
    e.classList.add('hidden');

    g.innerHTML = f.map(item=>{
        const ci = cart.find(c=>c.id===item.id);
        const badge = ci ? `<span class="absolute -top-1 -right-1 w-5 h-5 bg-red-600 text-white rounded-full text-[10px] font-bold flex items-center justify-center">${ci.qty}</span>` : '';
        const idx = item.id % 8;
        const img = item.image
            ? `<img src="${item.image}" class="w-full h-full object-cover" loading="lazy">`
            : `<div class="w-full h-full" style="background:${COLORS[idx]}"></div>`;

        return `<div class="relative bg-white border rounded-xl overflow-hidden cursor-pointer hover:shadow-md hover:-translate-y-0.5 transition-all active:scale-[.97]" onclick="addItem(${item.id})">
            ${badge}
            <div class="aspect-square overflow-hidden">${img}</div>
            <div class="p-2.5">
                <p class="text-[11px] font-bold text-gray-800 leading-tight line-clamp-2">${item.name}</p>
                <p class="text-xs font-extrabold text-red-600 mt-1">${item.formatted_price}</p>
            </div>
        </div>`;
    }).join('');
}

// ── CART ──
function addItem(id){
    const item = ITEMS.find(i=>i.id===id);
    if(!item) return;
    const ex = cart.find(c=>c.id===id);
    if(ex) ex.qty++; else cart.push({id:item.id, name:item.name, price:item.price, qty:1, image:item.image});
    updCart(); render();
}
function rmItem(id){ cart=cart.filter(c=>c.id!==id); updCart(); render() }
function chgQty(id,d){
    const c=cart.find(x=>x.id===id); if(!c)return;
    c.qty+=d; if(c.qty<1){rmItem(id);return} updCart(); render();
}
function clearCart(){
    if(!cart.length)return;
    Swal.fire({
        title:'Kosongkan Keranjang?',
        text:'Semua item akan dihapus dari keranjang.',
        icon:'warning',
        showCancelButton:true,
        confirmButtonColor:'#DC2626',
        cancelButtonColor:'#6B7280',
        confirmButtonText:'Ya, Kosongkan!',
        cancelButtonText:'Batal',
        reverseButtons:true,
    }).then(r=>{
        if(r.isConfirmed){cart=[];updCart();render();toast('Keranjang dikosongkan','info')}
    });
}

function sub(){ return cart.reduce((s,c)=>s+c.price*c.qty,0) }
function tax(){ return Math.round(sub()*TAX) }
function total(){ return sub()+tax() }
function totalQty(){ return cart.reduce((s,c)=>s+c.qty,0) }

function updCart(){
    const list=document.getElementById('cartList'), empty=document.getElementById('cartEmpty');
    const btn=document.getElementById('btnPay'), clr=document.getElementById('btnClear'), badge=document.getElementById('cartBadge');

    badge.textContent = `(${totalQty()})`;

    if(!cart.length){
        list.classList.add('hidden'); empty.classList.remove('hidden');
        btn.disabled=true; clr.classList.add('hidden');
    } else {
        empty.classList.add('hidden'); list.classList.remove('hidden');
        btn.disabled=false; clr.classList.remove('hidden');
    }

    list.innerHTML = cart.map(c=>{
        const idx=c.id%8;
        const thumb = c.image
            ? `<img src="${c.image}" class="w-9 h-9 rounded-lg object-cover">`
            : `<div class="w-9 h-9 rounded-lg" style="background:${COLORS[idx]}"></div>`;
        return `<div class="px-4 py-2.5 flex gap-2.5 items-center hover:bg-gray-50">
            ${thumb}
            <div class="flex-1 min-w-0">
                <p class="text-[11px] font-bold text-gray-800 truncate">${c.name}</p>
                <p class="text-[10px] text-gray-400">${rp(c.price)}</p>
            </div>
            <div class="flex items-center gap-1">
                <button onclick="chgQty(${c.id},-1)" class="w-6 h-6 rounded bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center text-[10px]">
                    <i class="fa-solid ${c.qty===1?'fa-trash-can text-red-400':'fa-minus'}"></i>
                </button>
                <span class="w-6 text-center text-xs font-bold">${c.qty}</span>
                <button onclick="chgQty(${c.id},1)" class="w-6 h-6 rounded bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center text-[10px]">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
            <span class="text-xs font-bold text-gray-800 w-16 text-right">${rp(c.price*c.qty)}</span>
        </div>`;
    }).join('');

    document.getElementById('dSubtotal').textContent = rp(sub());
    document.getElementById('dTax').textContent = rp(tax());
    document.getElementById('dTotal').textContent = rp(total());
}

// ── CATEGORY ──
document.querySelectorAll('.cat').forEach(b=>{
    b.onclick=()=>{
        document.querySelectorAll('.cat').forEach(x=>{x.classList.remove('active','bg-red-600','text-white');x.classList.add('bg-gray-100','text-gray-600')});
        b.classList.add('active','bg-red-600','text-white'); b.classList.remove('bg-gray-100','text-gray-600');
        catId=b.dataset.id; render();
    };
});

// ── SEARCH ──
const si=document.getElementById('search'), cb=document.getElementById('clearSearch');
let st;
si.addEventListener('input',()=>{clearTimeout(st);st=setTimeout(()=>{query=si.value.trim();cb.classList.toggle('hidden',!query);render()},200)});
cb.onclick=()=>{si.value='';query='';cb.classList.add('hidden');render();si.focus()};

// ── PAYMENT MODAL ──
function openPay(){
    if(!cart.length) return;
    document.getElementById('payModal').classList.remove('hidden');
    document.getElementById('payTotal').textContent = rp(total());
    document.getElementById('paidAmt').value = total();
    document.querySelector('input[name=method][value=cash]').checked = true;
    togMethod(); calcChange();
}
function closePay(){ document.getElementById('payModal').classList.add('hidden') }

document.querySelectorAll('input[name=method]').forEach(r=>r.onchange=togMethod);
function togMethod(){
    const m = document.querySelector('input[name=method]:checked').value;
    document.getElementById('cashBox').classList.toggle('hidden', m!=='cash');
    document.getElementById('refBox').classList.toggle('hidden', m==='cash');
}

document.querySelectorAll('.qamt').forEach(b=>{
    b.onclick=()=>{
        const inp=document.getElementById('paidAmt'), t=total();
        if(b.dataset.t==='exact') inp.value=t;
        else inp.value=t+parseInt(b.dataset.v);
        calcChange();
    };
});

document.getElementById('paidAmt').addEventListener('input', calcChange);
function calcChange(){
    const paid=parseFloat(document.getElementById('paidAmt').value)||0;
    const ch=paid-total();
    const lbl=document.getElementById('changeLabel'), amt=document.getElementById('changeAmt'), box=document.getElementById('changeBox');
    if(ch>=0){
        box.className='mt-3 rounded-xl p-3 flex items-center justify-between bg-green-50 border border-green-200';
        lbl.innerHTML='<i class="fa-solid fa-coins mr-1"></i>Kembalian'; lbl.className='text-sm font-semibold text-green-700';
        amt.textContent=rp(ch); amt.className='text-lg font-extrabold text-green-700';
    } else {
        box.className='mt-3 rounded-xl p-3 flex items-center justify-between bg-red-50 border border-red-200';
        lbl.innerHTML='<i class="fa-solid fa-exclamation-triangle mr-1"></i>Kurang'; lbl.className='text-sm font-semibold text-red-600';
        amt.textContent='-'+rp(Math.abs(ch)); amt.className='text-lg font-extrabold text-red-600';
    }
}

// ── PROCESS ──
let busy=false;
async function doProcess(){
    if(busy||!cart.length)return;
    const method=document.querySelector('input[name=method]:checked').value;
    const paid=parseFloat(document.getElementById('paidAmt').value)||0;
    if(method==='cash'&&paid<total()){toast('Pembayaran kurang!','error');return}

    busy=true;
    const btn=document.getElementById('btnProcess');
    btn.innerHTML='<i class="fa-solid fa-spinner fa-spin mr-1"></i>Memproses...'; btn.disabled=true;

    try{
        const res=await fetch('{{ route("admin.pos.process") }}',{
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body:JSON.stringify({
                table_id:document.getElementById('table').value,
                customer_name:document.getElementById('customer').value||'Walk-in',
                items:cart.map(c=>({menu_item_id:c.id,quantity:c.qty,notes:''})),
                payment_method:method,paid_amount:paid,notes:''
            })
        });
        const d=await res.json();
        if(d.success){
            closePay();
            document.getElementById('sOrderNum').textContent=d.order.order_number;
            document.getElementById('sTotal').textContent=d.order.formatted_total;
            document.getElementById('sMethod').textContent=d.payment.method;
            document.getElementById('sReceipt').href=d.receipt_url;
            if(d.order.change>0){document.getElementById('sChange').textContent=d.order.formatted_change;document.getElementById('sChangeRow').classList.remove('hidden')}
            else{document.getElementById('sChangeRow').classList.add('hidden')}
            document.getElementById('successModal').classList.remove('hidden');
            cart=[];updCart();render();
        } else { toast(d.message||'Gagal','error') }
    }catch(e){console.error(e);toast('Error jaringan!','error')}
    finally{busy=false;btn.innerHTML='<i class="fa-solid fa-check-circle mr-1.5"></i>Proses Pembayaran';btn.disabled=false}
}
function closeSuccess(){document.getElementById('successModal').classList.add('hidden');document.getElementById('customer').value='Walk-in'}

// ── RECENT ORDERS ──
async function loadRecent(){
    document.getElementById('recentModal').classList.remove('hidden');
    const el=document.getElementById('recentList');
    el.innerHTML='<div class="p-8 text-center text-gray-400"><i class="fa-solid fa-spinner fa-spin text-xl"></i></div>';
    try{
        const r=await fetch('{{ route("admin.pos.recent") }}');
        const d=await r.json();
        if(!d.orders.length){el.innerHTML='<div class="p-10 text-center text-gray-400"><i class="fa-solid fa-inbox text-3xl mb-2"></i><p class="text-sm font-semibold">Belum ada pesanan</p></div>';return}
        const sc={pending:'bg-yellow-100 text-yellow-700',confirmed:'bg-blue-100 text-blue-700',preparing:'bg-orange-100 text-orange-700',ready:'bg-green-100 text-green-700',served:'bg-indigo-100 text-indigo-700',completed:'bg-gray-100 text-gray-700',cancelled:'bg-red-100 text-red-700'};
        const sl={pending:'Menunggu',confirmed:'Dikonfirmasi',preparing:'Diproses',ready:'Siap',served:'Disajikan',completed:'Selesai',cancelled:'Batal'};
        el.innerHTML='<div class="divide-y">'+d.orders.map(o=>`
            <div class="px-5 py-3 flex items-center gap-3 hover:bg-gray-50">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 mb-0.5">
                        <span class="text-xs font-bold text-gray-800">${o.order_number}</span>
                        <span class="px-1.5 py-0.5 text-[9px] font-bold rounded ${sc[o.status]||'bg-gray-100 text-gray-600'}">${sl[o.status]||o.status}</span>
                        <span class="px-1.5 py-0.5 text-[9px] font-bold rounded ${o.payment_status==='paid'?'bg-green-100 text-green-700':'bg-red-100 text-red-700'}">${o.payment_status==='paid'?'Lunas':'Belum'}</span>
                    </div>
                    <p class="text-[10px] text-gray-400">${o.table} · ${o.customer_name} · ${o.items_count} item · ${o.time}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold">${o.formatted_total}</p>
                    ${o.receipt_url?`<a href="${o.receipt_url}" target="_blank" class="text-[10px] text-red-600 font-bold hover:underline">Struk</a>`:''}
                </div>
            </div>`).join('')+'</div>';
    }catch{el.innerHTML='<div class="p-8 text-center text-red-400"><p class="font-semibold">Gagal memuat</p></div>'}
}
function closeRecent(){document.getElementById('recentModal').classList.add('hidden')}

// ── KEYBOARD ──
document.addEventListener('keydown',e=>{
    if(e.key==='F2'){e.preventDefault();si.focus()}
    if(e.key==='F9'){e.preventDefault();if(cart.length)openPay()}
    if(e.key==='Escape'){closePay();closeRecent();closeSuccess()}
    if(e.key==='F12'){e.preventDefault();loadRecent()}
});

// INIT
render(); updCart();
</script>
</body>
</html>
