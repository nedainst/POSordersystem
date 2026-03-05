"""
POS / Cashier Page - Main point of sale interface
"""
import customtkinter as ctk
from tkinter import messagebox
from datetime import datetime
import database as db

COLORS = {
    "bg_dark": "#0f0f14",
    "bg_card": "#1e1e2e",
    "bg_card_hover": "#262638",
    "bg_entry": "#252535",
    "accent": "#6c63ff",
    "accent_hover": "#7b73ff",
    "success": "#2ecc71",
    "success_hover": "#27ae60",
    "warning": "#f39c12",
    "danger": "#e74c3c",
    "danger_hover": "#c0392b",
    "text_primary": "#e8e8f0",
    "text_secondary": "#a9a9c0",
    "text_muted": "#6c6c88",
    "border": "#2a2a3d",
    "header_bg": "#1a1a28",
    "table_row_alt": "#1b1b2b",
}


def format_rupiah(amount):
    try:
        return f"Rp {amount:,.0f}".replace(",", ".")
    except (ValueError, TypeError):
        return "Rp 0"


class POSPage:
    def __init__(self, parent, app):
        self.parent = parent
        self.app = app
        self.cart = []  # list of dicts: {product_id, product_name, quantity, unit_price, subtotal}
        self.settings = db.get_all_settings()
        self.tax_rate = float(self.settings.get("tax_rate", "11")) / 100
        self._build()

    def _build(self):
        # Header
        header = ctk.CTkFrame(self.parent, fg_color=COLORS["header_bg"], height=64, corner_radius=0)
        header.pack(fill="x")
        header.pack_propagate(False)

        ctk.CTkLabel(header, text="🛒  Kasir / POS", font=("Segoe UI Bold", 20),
                     text_color=COLORS["text_primary"]).pack(side="left", padx=24, pady=16)

        # Invoice number
        self.invoice_label = ctk.CTkLabel(header, text=f"Invoice: {db.generate_invoice_number()}",
                                          font=("Segoe UI Semibold", 13), text_color=COLORS["accent"])
        self.invoice_label.pack(side="right", padx=24)

        # Main content: products left, cart right
        main = ctk.CTkFrame(self.parent, fg_color="transparent")
        main.pack(fill="both", expand=True, padx=0, pady=0)
        main.grid_columnconfigure(0, weight=3)
        main.grid_columnconfigure(1, weight=2)
        main.grid_rowconfigure(0, weight=1)

        self._build_product_panel(main)
        self._build_cart_panel(main)

    # ── Product selection panel (LEFT) ───────────────────────
    def _build_product_panel(self, parent):
        panel = ctk.CTkFrame(parent, fg_color=COLORS["bg_dark"], corner_radius=0)
        panel.grid(row=0, column=0, sticky="nsew", padx=(0, 2))

        # Search bar
        search_bar = ctk.CTkFrame(panel, fg_color="transparent", height=50)
        search_bar.pack(fill="x", padx=16, pady=(12, 8))
        search_bar.pack_propagate(False)

        ctk.CTkLabel(search_bar, text="🔍", font=("Segoe UI", 16)).pack(side="left", padx=(0, 8))

        self.search_var = ctk.StringVar()
        self.search_var.trace_add("write", lambda *_: self._filter_products())
        search_entry = ctk.CTkEntry(search_bar, textvariable=self.search_var,
                                    placeholder_text="Cari produk atau scan barcode...",
                                    height=40, corner_radius=10, font=("Segoe UI", 13),
                                    fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                    text_color=COLORS["text_primary"])
        search_entry.pack(side="left", fill="x", expand=True)
        search_entry.bind("<Return>", self._on_barcode_scan)

        # Category filter
        cat_frame = ctk.CTkFrame(panel, fg_color="transparent", height=40)
        cat_frame.pack(fill="x", padx=16, pady=(0, 8))

        categories = db.get_all_categories()
        self.selected_category = ctk.StringVar(value="Semua")

        all_btn = ctk.CTkButton(cat_frame, text="Semua", width=70, height=32, corner_radius=8,
                                fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                                font=("Segoe UI", 11),
                                command=lambda: self._select_category("Semua"))
        all_btn.pack(side="left", padx=2)
        self.cat_buttons = {"Semua": all_btn}

        for cat in categories[:7]:
            btn = ctk.CTkButton(cat_frame, text=cat["name"], width=80, height=32, corner_radius=8,
                                fg_color=COLORS["bg_card"], hover_color=COLORS["bg_card_hover"],
                                font=("Segoe UI", 11), text_color=COLORS["text_secondary"],
                                command=lambda c=cat["name"]: self._select_category(c))
            btn.pack(side="left", padx=2)
            self.cat_buttons[cat["name"]] = btn

        # Product grid
        self.product_scroll = ctk.CTkScrollableFrame(panel, fg_color=COLORS["bg_dark"],
                                                      scrollbar_button_color="#3a3a50",
                                                      scrollbar_button_hover_color=COLORS["accent"])
        self.product_scroll.pack(fill="both", expand=True, padx=12, pady=(0, 8))

        self._load_products()

    def _select_category(self, cat_name):
        self.selected_category.set(cat_name)
        for name, btn in self.cat_buttons.items():
            if name == cat_name:
                btn.configure(fg_color=COLORS["accent"], text_color="white")
            else:
                btn.configure(fg_color=COLORS["bg_card"], text_color=COLORS["text_secondary"])
        self._filter_products()

    def _filter_products(self):
        search = self.search_var.get()
        cat = self.selected_category.get()
        categories = db.get_all_categories()
        cat_id = None
        if cat != "Semua":
            for c in categories:
                if c["name"] == cat:
                    cat_id = c["id"]
                    break
        products = db.get_all_products(search=search, category_id=cat_id)
        self._render_products(products)

    def _load_products(self):
        products = db.get_all_products()
        self._render_products(products)

    def _render_products(self, products):
        for w in self.product_scroll.winfo_children():
            w.destroy()

        if not products:
            ctk.CTkLabel(self.product_scroll, text="Tidak ada produk ditemukan",
                         font=("Segoe UI", 14), text_color=COLORS["text_muted"]).pack(pady=40)
            return

        # Grid layout: 3 columns
        cols = 3
        for i, p in enumerate(products):
            row, col = divmod(i, cols)

            card = ctk.CTkFrame(self.product_scroll, fg_color=COLORS["bg_card"], corner_radius=12,
                                cursor="hand2")
            card.grid(row=row, column=col, sticky="nsew", padx=6, pady=6)
            self.product_scroll.grid_columnconfigure(col, weight=1)

            inner = ctk.CTkFrame(card, fg_color="transparent")
            inner.pack(fill="both", expand=True, padx=12, pady=10)

            # Product name
            ctk.CTkLabel(inner, text=p["name"], font=("Segoe UI Semibold", 13),
                         text_color=COLORS["text_primary"], wraplength=180, anchor="w").pack(fill="x")

            # Category
            cat_name = p.get("category_name", "Lainnya") or "Lainnya"
            ctk.CTkLabel(inner, text=cat_name, font=("Segoe UI", 10),
                         text_color=COLORS["text_muted"]).pack(anchor="w")

            # Price
            ctk.CTkLabel(inner, text=format_rupiah(p["sell_price"]), font=("Segoe UI Bold", 15),
                         text_color=COLORS["accent"]).pack(anchor="w", pady=(4, 2))

            # Stock
            stock_color = COLORS["success"] if p["stock"] > p["min_stock"] else (
                COLORS["warning"] if p["stock"] > 0 else COLORS["danger"])
            ctk.CTkLabel(inner, text=f"Stok: {p['stock']} {p['unit']}",
                         font=("Segoe UI", 11), text_color=stock_color).pack(anchor="w")

            # Quick add button
            add_btn = ctk.CTkButton(inner, text="+ Tambah", height=30, corner_radius=8,
                                    fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                                    font=("Segoe UI Semibold", 11),
                                    command=lambda prod=p: self._add_to_cart(prod))
            add_btn.pack(fill="x", pady=(6, 0))

            # Make entire card clickable
            for widget in [card, inner]:
                widget.bind("<Button-1>", lambda e, prod=p: self._add_to_cart(prod))

    def _on_barcode_scan(self, event=None):
        barcode = self.search_var.get().strip()
        if barcode:
            product = db.get_product_by_barcode(barcode)
            if product:
                self._add_to_cart(product)
                self.search_var.set("")
            else:
                # Try as search
                self._filter_products()

    # ── Cart panel (RIGHT) ───────────────────────────────────
    def _build_cart_panel(self, parent):
        panel = ctk.CTkFrame(parent, fg_color=COLORS["bg_card"], corner_radius=0)
        panel.grid(row=0, column=1, sticky="nsew")

        # Cart header
        cart_header = ctk.CTkFrame(panel, fg_color=COLORS["header_bg"], height=50, corner_radius=0)
        cart_header.pack(fill="x")
        cart_header.pack_propagate(False)

        ctk.CTkLabel(cart_header, text="🛒 Keranjang", font=("Segoe UI Bold", 16),
                     text_color=COLORS["text_primary"]).pack(side="left", padx=16)

        self.cart_count_label = ctk.CTkLabel(cart_header, text="0 item", font=("Segoe UI", 12),
                                              text_color=COLORS["text_muted"])
        self.cart_count_label.pack(side="left", padx=8)

        clear_btn = ctk.CTkButton(cart_header, text="🗑 Hapus Semua", width=110, height=30,
                                  fg_color=COLORS["danger"], hover_color=COLORS["danger_hover"],
                                  font=("Segoe UI", 11), corner_radius=8,
                                  command=self._clear_cart)
        clear_btn.pack(side="right", padx=16)

        # Cart items list
        self.cart_scroll = ctk.CTkScrollableFrame(panel, fg_color="transparent",
                                                   scrollbar_button_color="#3a3a50",
                                                   scrollbar_button_hover_color=COLORS["accent"])
        self.cart_scroll.pack(fill="both", expand=True, padx=8, pady=8)

        self.cart_empty_label = ctk.CTkLabel(self.cart_scroll, text="Keranjang kosong\nPilih produk untuk memulai",
                                              font=("Segoe UI", 14), text_color=COLORS["text_muted"])
        self.cart_empty_label.pack(pady=60)

        # ── Totals & Payment ─────────────────────────────────
        totals_frame = ctk.CTkFrame(panel, fg_color=COLORS["header_bg"], corner_radius=0)
        totals_frame.pack(fill="x", side="bottom")

        # Customer name
        cust_row = ctk.CTkFrame(totals_frame, fg_color="transparent", height=36)
        cust_row.pack(fill="x", padx=16, pady=(12, 4))
        ctk.CTkLabel(cust_row, text="Pelanggan:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(side="left")
        self.customer_entry = ctk.CTkEntry(cust_row, width=160, height=32, corner_radius=8,
                                           fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                           text_color=COLORS["text_primary"], font=("Segoe UI", 12))
        self.customer_entry.pack(side="right")
        self.customer_entry.insert(0, "Umum")

        # Subtotal
        sub_row = ctk.CTkFrame(totals_frame, fg_color="transparent", height=28)
        sub_row.pack(fill="x", padx=16, pady=2)
        ctk.CTkLabel(sub_row, text="Subtotal", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(side="left")
        self.subtotal_label = ctk.CTkLabel(sub_row, text="Rp 0", font=("Segoe UI", 12),
                                            text_color=COLORS["text_primary"])
        self.subtotal_label.pack(side="right")

        # Discount
        disc_row = ctk.CTkFrame(totals_frame, fg_color="transparent", height=36)
        disc_row.pack(fill="x", padx=16, pady=2)
        ctk.CTkLabel(disc_row, text="Diskon", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(side="left")
        self.discount_entry = ctk.CTkEntry(disc_row, width=120, height=28, corner_radius=6,
                                           fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                           text_color=COLORS["text_primary"], font=("Segoe UI", 12),
                                           placeholder_text="0")
        self.discount_entry.pack(side="right")
        self.discount_entry.insert(0, "0")
        self.discount_entry.bind("<KeyRelease>", lambda e: self._update_totals())

        # Tax
        tax_row = ctk.CTkFrame(totals_frame, fg_color="transparent", height=28)
        tax_row.pack(fill="x", padx=16, pady=2)
        ctk.CTkLabel(tax_row, text=f"Pajak ({int(self.tax_rate*100)}%)", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(side="left")
        self.tax_label = ctk.CTkLabel(tax_row, text="Rp 0", font=("Segoe UI", 12),
                                      text_color=COLORS["text_primary"])
        self.tax_label.pack(side="right")

        # Total
        ctk.CTkFrame(totals_frame, fg_color=COLORS["border"], height=1).pack(fill="x", padx=16, pady=6)
        total_row = ctk.CTkFrame(totals_frame, fg_color="transparent", height=36)
        total_row.pack(fill="x", padx=16, pady=2)
        ctk.CTkLabel(total_row, text="TOTAL", font=("Segoe UI Bold", 16),
                     text_color=COLORS["text_primary"]).pack(side="left")
        self.total_label = ctk.CTkLabel(total_row, text="Rp 0", font=("Segoe UI Bold", 20),
                                         text_color=COLORS["accent"])
        self.total_label.pack(side="right")

        # Payment method
        pay_row = ctk.CTkFrame(totals_frame, fg_color="transparent", height=40)
        pay_row.pack(fill="x", padx=16, pady=(8, 4))
        ctk.CTkLabel(pay_row, text="Pembayaran:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(side="left")

        self.payment_method = ctk.CTkSegmentedButton(
            pay_row, values=["Cash", "Debit", "QRIS", "Transfer"],
            font=("Segoe UI", 11), height=32,
            fg_color=COLORS["bg_entry"],
            selected_color=COLORS["accent"],
            selected_hover_color=COLORS["accent_hover"],
            unselected_color=COLORS["bg_entry"],
            unselected_hover_color=COLORS["bg_card_hover"],
            text_color=COLORS["text_secondary"],
            text_color_disabled=COLORS["text_muted"]
        )
        self.payment_method.set("Cash")
        self.payment_method.pack(side="right")

        # Payment amount
        amount_row = ctk.CTkFrame(totals_frame, fg_color="transparent", height=36)
        amount_row.pack(fill="x", padx=16, pady=2)
        ctk.CTkLabel(amount_row, text="Bayar:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(side="left")
        self.payment_entry = ctk.CTkEntry(amount_row, width=160, height=32, corner_radius=8,
                                          fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                          text_color=COLORS["text_primary"], font=("Segoe UI Semibold", 13),
                                          placeholder_text="0")
        self.payment_entry.pack(side="right")

        # Quick cash buttons
        quick_row = ctk.CTkFrame(totals_frame, fg_color="transparent", height=36)
        quick_row.pack(fill="x", padx=16, pady=(4, 4))
        for amt in [10000, 20000, 50000, 100000]:
            label = f"{amt//1000}k"
            btn = ctk.CTkButton(quick_row, text=label, width=60, height=28, corner_radius=6,
                                fg_color=COLORS["bg_entry"], hover_color=COLORS["bg_card_hover"],
                                font=("Segoe UI", 10), text_color=COLORS["text_secondary"],
                                command=lambda a=amt: self._set_payment(a))
            btn.pack(side="left", padx=2)

        exact_btn = ctk.CTkButton(quick_row, text="Uang Pas", width=80, height=28, corner_radius=6,
                                  fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                                  font=("Segoe UI", 10),
                                  command=self._set_exact_payment)
        exact_btn.pack(side="right", padx=2)

        # Process button
        self.process_btn = ctk.CTkButton(totals_frame, text="💳  PROSES PEMBAYARAN", height=50,
                                         corner_radius=12, fg_color=COLORS["success"],
                                         hover_color=COLORS["success_hover"],
                                         font=("Segoe UI Bold", 16),
                                         command=self._process_payment)
        self.process_btn.pack(fill="x", padx=16, pady=(8, 16))

    # ── Cart operations ──────────────────────────────────────
    def _add_to_cart(self, product):
        if product["stock"] <= 0:
            messagebox.showwarning("Stok Habis", f"Stok {product['name']} habis!")
            return

        # Check if already in cart
        for item in self.cart:
            if item["product_id"] == product["id"]:
                if item["quantity"] < product["stock"]:
                    item["quantity"] += 1
                    item["subtotal"] = item["quantity"] * item["unit_price"]
                else:
                    messagebox.showwarning("Stok Terbatas", f"Stok {product['name']} hanya {product['stock']}")
                    return
                self._render_cart()
                return

        self.cart.append({
            "product_id": product["id"],
            "product_name": product["name"],
            "quantity": 1,
            "unit_price": product["sell_price"],
            "subtotal": product["sell_price"],
            "max_stock": product["stock"],
            "unit": product.get("unit", "pcs")
        })
        self._render_cart()

    def _remove_from_cart(self, index):
        if 0 <= index < len(self.cart):
            self.cart.pop(index)
            self._render_cart()

    def _update_qty(self, index, delta):
        if 0 <= index < len(self.cart):
            new_qty = self.cart[index]["quantity"] + delta
            if new_qty <= 0:
                self._remove_from_cart(index)
            elif new_qty <= self.cart[index]["max_stock"]:
                self.cart[index]["quantity"] = new_qty
                self.cart[index]["subtotal"] = new_qty * self.cart[index]["unit_price"]
                self._render_cart()
            else:
                messagebox.showwarning("Stok Terbatas",
                                       f"Stok maksimal: {self.cart[index]['max_stock']}")

    def _clear_cart(self):
        if self.cart:
            if messagebox.askyesno("Konfirmasi", "Hapus semua item dari keranjang?"):
                self.cart.clear()
                self._render_cart()

    def _render_cart(self):
        for w in self.cart_scroll.winfo_children():
            w.destroy()

        if not self.cart:
            self.cart_empty_label = ctk.CTkLabel(self.cart_scroll,
                                                  text="Keranjang kosong\nPilih produk untuk memulai",
                                                  font=("Segoe UI", 14), text_color=COLORS["text_muted"])
            self.cart_empty_label.pack(pady=60)
            self._update_totals()
            return

        for idx, item in enumerate(self.cart):
            bg = COLORS["bg_card"] if idx % 2 == 0 else COLORS["bg_card_hover"]
            row = ctk.CTkFrame(self.cart_scroll, fg_color=bg, corner_radius=10, height=70)
            row.pack(fill="x", padx=4, pady=3)
            row.pack_propagate(False)

            # Product info
            info = ctk.CTkFrame(row, fg_color="transparent")
            info.pack(side="left", fill="both", expand=True, padx=12, pady=8)

            ctk.CTkLabel(info, text=item["product_name"], font=("Segoe UI Semibold", 12),
                         text_color=COLORS["text_primary"], anchor="w").pack(fill="x")
            ctk.CTkLabel(info, text=f'{format_rupiah(item["unit_price"])} / {item["unit"]}',
                         font=("Segoe UI", 10), text_color=COLORS["text_muted"], anchor="w").pack(fill="x")

            # Controls
            ctrl = ctk.CTkFrame(row, fg_color="transparent", width=200)
            ctrl.pack(side="right", padx=8, pady=8)
            ctrl.pack_propagate(False)

            # Subtotal on top
            ctk.CTkLabel(ctrl, text=format_rupiah(item["subtotal"]), font=("Segoe UI Bold", 12),
                         text_color=COLORS["accent"]).pack(anchor="e")

            # Quantity controls
            qty_frame = ctk.CTkFrame(ctrl, fg_color="transparent")
            qty_frame.pack(anchor="e")

            ctk.CTkButton(qty_frame, text="−", width=28, height=28, corner_radius=6,
                          fg_color=COLORS["danger"], hover_color=COLORS["danger_hover"],
                          font=("Segoe UI Bold", 14),
                          command=lambda i=idx: self._update_qty(i, -1)).pack(side="left", padx=2)

            ctk.CTkLabel(qty_frame, text=str(item["quantity"]), font=("Segoe UI Bold", 13),
                         text_color=COLORS["text_primary"], width=36).pack(side="left")

            ctk.CTkButton(qty_frame, text="+", width=28, height=28, corner_radius=6,
                          fg_color=COLORS["success"], hover_color=COLORS["success_hover"],
                          font=("Segoe UI Bold", 14),
                          command=lambda i=idx: self._update_qty(i, 1)).pack(side="left", padx=2)

            ctk.CTkButton(qty_frame, text="✕", width=28, height=28, corner_radius=6,
                          fg_color=COLORS["bg_entry"], hover_color=COLORS["danger"],
                          font=("Segoe UI", 12), text_color=COLORS["text_muted"],
                          command=lambda i=idx: self._remove_from_cart(i)).pack(side="left", padx=(8, 0))

        self._update_totals()

    def _update_totals(self):
        subtotal = sum(i["subtotal"] for i in self.cart)
        try:
            discount = float(self.discount_entry.get() or 0)
        except ValueError:
            discount = 0
        tax = round((subtotal - discount) * self.tax_rate, 2)
        if tax < 0:
            tax = 0
        total = subtotal - discount + tax

        self.subtotal_label.configure(text=format_rupiah(subtotal))
        self.tax_label.configure(text=format_rupiah(tax))
        self.total_label.configure(text=format_rupiah(total))

        total_items = sum(i["quantity"] for i in self.cart)
        self.cart_count_label.configure(text=f"{total_items} item")

    def _set_payment(self, amount):
        self.payment_entry.delete(0, "end")
        self.payment_entry.insert(0, str(int(amount)))

    def _set_exact_payment(self):
        subtotal = sum(i["subtotal"] for i in self.cart)
        try:
            discount = float(self.discount_entry.get() or 0)
        except ValueError:
            discount = 0
        tax = round((subtotal - discount) * self.tax_rate, 2)
        if tax < 0:
            tax = 0
        total = subtotal - discount + tax
        self._set_payment(total)

    def _process_payment(self):
        if not self.cart:
            messagebox.showwarning("Keranjang Kosong", "Tambahkan produk ke keranjang terlebih dahulu!")
            return

        subtotal = sum(i["subtotal"] for i in self.cart)
        try:
            discount = float(self.discount_entry.get() or 0)
        except ValueError:
            discount = 0
        tax = round((subtotal - discount) * self.tax_rate, 2)
        if tax < 0:
            tax = 0
        total = subtotal - discount + tax

        try:
            payment = float(self.payment_entry.get() or 0)
        except ValueError:
            messagebox.showerror("Error", "Jumlah pembayaran tidak valid!")
            return

        if payment < total:
            messagebox.showwarning("Pembayaran Kurang",
                                   f"Pembayaran kurang!\nTotal: {format_rupiah(total)}\nBayar: {format_rupiah(payment)}")
            return

        change = payment - total
        customer = self.customer_entry.get().strip() or "Umum"
        method = self.payment_method.get().lower()

        items = []
        for item in self.cart:
            items.append({
                "product_id": item["product_id"],
                "product_name": item["product_name"],
                "quantity": item["quantity"],
                "unit_price": item["unit_price"],
                "discount": 0,
                "subtotal": item["subtotal"]
            })

        try:
            invoice, final, change_amount = db.create_transaction(
                user_id=self.app.current_user["id"],
                items=items,
                customer_name=customer,
                discount=discount,
                payment_method=method,
                payment_amount=payment,
                notes=""
            )

            # Show success dialog
            self._show_receipt_dialog(invoice, customer, items, subtotal, discount, tax, total, payment, change_amount, method)

            # Reset cart
            self.cart.clear()
            self._render_cart()
            self.customer_entry.delete(0, "end")
            self.customer_entry.insert(0, "Umum")
            self.discount_entry.delete(0, "end")
            self.discount_entry.insert(0, "0")
            self.payment_entry.delete(0, "end")
            self.invoice_label.configure(text=f"Invoice: {db.generate_invoice_number()}")

            # Refresh product list to show updated stock
            self._load_products()

        except Exception as e:
            messagebox.showerror("Error", f"Gagal memproses transaksi:\n{str(e)}")

    def _show_receipt_dialog(self, invoice, customer, items, subtotal, discount, tax, total, payment, change, method):
        dialog = ctk.CTkToplevel(self.parent)
        dialog.title("Transaksi Berhasil")
        dialog.geometry("420x600")
        dialog.resizable(False, False)
        dialog.configure(fg_color=COLORS["bg_dark"])
        dialog.transient(self.parent)
        dialog.grab_set()

        # Center
        dialog.update_idletasks()
        x = (dialog.winfo_screenwidth() - 420) // 2
        y = (dialog.winfo_screenheight() - 600) // 2
        dialog.geometry(f"420x600+{x}+{y}")

        scroll = ctk.CTkScrollableFrame(dialog, fg_color=COLORS["bg_card"],
                                         scrollbar_button_color="#3a3a50")
        scroll.pack(fill="both", expand=True, padx=16, pady=16)

        # Success icon
        ctk.CTkLabel(scroll, text="✅", font=("Segoe UI Emoji", 40)).pack(pady=(10, 4))
        ctk.CTkLabel(scroll, text="Transaksi Berhasil!", font=("Segoe UI Bold", 18),
                     text_color=COLORS["success"]).pack()

        settings = db.get_all_settings()
        store = settings.get("store_name", "TOKO SAYA")
        ctk.CTkLabel(scroll, text=store, font=("Segoe UI Bold", 14),
                     text_color=COLORS["text_primary"]).pack(pady=(12, 0))
        ctk.CTkLabel(scroll, text=settings.get("store_address", ""), font=("Segoe UI", 10),
                     text_color=COLORS["text_muted"]).pack()

        ctk.CTkFrame(scroll, fg_color=COLORS["border"], height=1).pack(fill="x", padx=20, pady=10)

        # Invoice info
        info_data = [
            ("Invoice", invoice),
            ("Tanggal", datetime.now().strftime("%d/%m/%Y %H:%M")),
            ("Kasir", self.app.current_user["full_name"]),
            ("Pelanggan", customer),
        ]
        for label, val in info_data:
            row = ctk.CTkFrame(scroll, fg_color="transparent")
            row.pack(fill="x", padx=20, pady=1)
            ctk.CTkLabel(row, text=label, font=("Segoe UI", 11), text_color=COLORS["text_secondary"]).pack(side="left")
            ctk.CTkLabel(row, text=val, font=("Segoe UI", 11), text_color=COLORS["text_primary"]).pack(side="right")

        ctk.CTkFrame(scroll, fg_color=COLORS["border"], height=1).pack(fill="x", padx=20, pady=10)

        # Items
        for item in items:
            row = ctk.CTkFrame(scroll, fg_color="transparent")
            row.pack(fill="x", padx=20, pady=1)
            ctk.CTkLabel(row, text=f'{item["product_name"]} x{item["quantity"]}',
                         font=("Segoe UI", 11), text_color=COLORS["text_primary"]).pack(side="left")
            ctk.CTkLabel(row, text=format_rupiah(item["subtotal"]),
                         font=("Segoe UI", 11), text_color=COLORS["text_primary"]).pack(side="right")

        ctk.CTkFrame(scroll, fg_color=COLORS["border"], height=1).pack(fill="x", padx=20, pady=10)

        # Totals
        totals_data = [
            ("Subtotal", format_rupiah(subtotal)),
            ("Diskon", format_rupiah(discount)),
            ("Pajak", format_rupiah(tax)),
        ]
        for label, val in totals_data:
            row = ctk.CTkFrame(scroll, fg_color="transparent")
            row.pack(fill="x", padx=20, pady=1)
            ctk.CTkLabel(row, text=label, font=("Segoe UI", 11), text_color=COLORS["text_secondary"]).pack(side="left")
            ctk.CTkLabel(row, text=val, font=("Segoe UI", 11), text_color=COLORS["text_primary"]).pack(side="right")

        # Grand total
        ctk.CTkFrame(scroll, fg_color=COLORS["accent"], height=2).pack(fill="x", padx=20, pady=8)
        total_row = ctk.CTkFrame(scroll, fg_color="transparent")
        total_row.pack(fill="x", padx=20)
        ctk.CTkLabel(total_row, text="TOTAL", font=("Segoe UI Bold", 14),
                     text_color=COLORS["text_primary"]).pack(side="left")
        ctk.CTkLabel(total_row, text=format_rupiah(total), font=("Segoe UI Bold", 16),
                     text_color=COLORS["accent"]).pack(side="right")

        pay_row = ctk.CTkFrame(scroll, fg_color="transparent")
        pay_row.pack(fill="x", padx=20, pady=2)
        ctk.CTkLabel(pay_row, text=f"Bayar ({method.upper()})", font=("Segoe UI", 11),
                     text_color=COLORS["text_secondary"]).pack(side="left")
        ctk.CTkLabel(pay_row, text=format_rupiah(payment), font=("Segoe UI", 11),
                     text_color=COLORS["text_primary"]).pack(side="right")

        change_row = ctk.CTkFrame(scroll, fg_color="transparent")
        change_row.pack(fill="x", padx=20, pady=2)
        ctk.CTkLabel(change_row, text="Kembalian", font=("Segoe UI Bold", 13),
                     text_color=COLORS["success"]).pack(side="left")
        ctk.CTkLabel(change_row, text=format_rupiah(change), font=("Segoe UI Bold", 15),
                     text_color=COLORS["success"]).pack(side="right")

        ctk.CTkFrame(scroll, fg_color=COLORS["border"], height=1).pack(fill="x", padx=20, pady=10)
        ctk.CTkLabel(scroll, text=settings.get("receipt_footer", "Terima kasih!"),
                     font=("Segoe UI", 11), text_color=COLORS["text_muted"]).pack(pady=(0, 8))

        # Close button
        ctk.CTkButton(dialog, text="Tutup", height=40, corner_radius=10,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI Semibold", 14),
                      command=dialog.destroy).pack(fill="x", padx=16, pady=(0, 16))
