"""
Inventory & Stock Management Page
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


class InventoryPage:
    def __init__(self, parent, app):
        self.parent = parent
        self.app = app
        self._build()

    def _build(self):
        # Header
        header = ctk.CTkFrame(self.parent, fg_color=COLORS["header_bg"], height=64, corner_radius=0)
        header.pack(fill="x")
        header.pack_propagate(False)

        ctk.CTkLabel(header, text="📋  Inventaris & Stok", font=("Segoe UI Bold", 20),
                     text_color=COLORS["text_primary"]).pack(side="left", padx=24, pady=16)

        # Tabs
        self.tab_view = ctk.CTkTabview(self.parent, fg_color=COLORS["bg_dark"],
                                        segmented_button_fg_color=COLORS["bg_card"],
                                        segmented_button_selected_color=COLORS["accent"],
                                        segmented_button_selected_hover_color=COLORS["accent_hover"],
                                        segmented_button_unselected_color=COLORS["bg_card"],
                                        segmented_button_unselected_hover_color=COLORS["bg_card_hover"],
                                        text_color=COLORS["text_secondary"],
                                        text_color_disabled=COLORS["text_muted"])
        self.tab_view.pack(fill="both", expand=True, padx=16, pady=8)

        self.tab_stock = self.tab_view.add("📦 Stok Produk")
        self.tab_low = self.tab_view.add("⚠ Stok Menipis")
        self.tab_movement = self.tab_view.add("📝 Riwayat Stok")
        self.tab_adjust = self.tab_view.add("🔧 Penyesuaian Stok")

        self._build_stock_tab()
        self._build_low_stock_tab()
        self._build_movement_tab()
        self._build_adjust_tab()

    def _build_stock_tab(self):
        tab = self.tab_stock

        # Search
        search_row = ctk.CTkFrame(tab, fg_color="transparent", height=44)
        search_row.pack(fill="x", padx=8, pady=(8, 8))

        self.stock_search = ctk.StringVar()
        self.stock_search.trace_add("write", lambda *_: self._load_stock())
        ctk.CTkEntry(search_row, textvariable=self.stock_search,
                     placeholder_text="🔍 Cari produk...", height=38, width=300,
                     corner_radius=10, fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                     text_color=COLORS["text_primary"], font=("Segoe UI", 12)).pack(side="left")

        ctk.CTkButton(search_row, text="↻ Refresh", width=100, height=36,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI", 12), corner_radius=8,
                      command=self._load_stock).pack(side="right")

        # Table
        self.stock_scroll = ctk.CTkScrollableFrame(tab, fg_color=COLORS["bg_dark"],
                                                    scrollbar_button_color="#3a3a50",
                                                    scrollbar_button_hover_color=COLORS["accent"])
        self.stock_scroll.pack(fill="both", expand=True, padx=8, pady=(0, 8))

        self._load_stock()

    def _load_stock(self):
        for w in self.stock_scroll.winfo_children():
            w.destroy()

        search = self.stock_search.get() if hasattr(self, 'stock_search') else ""
        products = db.get_all_products(search=search)

        # Header
        th = ctk.CTkFrame(self.stock_scroll, fg_color=COLORS["header_bg"], corner_radius=8, height=38)
        th.pack(fill="x", pady=(0, 4))
        th.pack_propagate(False)

        cols = [("Produk", 220), ("Kategori", 120), ("Stok Saat Ini", 100), ("Stok Min", 80),
                ("Status", 100), ("Nilai Stok", 140), ("Aksi", 100)]
        for name, w in cols:
            ctk.CTkLabel(th, text=name, font=("Segoe UI Semibold", 11),
                         text_color=COLORS["text_secondary"], width=w, anchor="w").pack(side="left", padx=6)

        total_value = 0
        for i, p in enumerate(products):
            bg = COLORS["bg_card"] if i % 2 == 0 else COLORS["table_row_alt"]
            row = ctk.CTkFrame(self.stock_scroll, fg_color=bg, height=38, corner_radius=4)
            row.pack(fill="x", pady=1)
            row.pack_propagate(False)

            stock_val = p["stock"] * p["sell_price"]
            total_value += stock_val

            if p["stock"] <= 0:
                status, stat_color = "Habis", COLORS["danger"]
            elif p["stock"] <= p["min_stock"]:
                status, stat_color = "Menipis", COLORS["warning"]
            else:
                status, stat_color = "Tersedia", COLORS["success"]

            values = [
                (p["name"], 220, COLORS["text_primary"]),
                (p.get("category_name") or "-", 120, COLORS["text_secondary"]),
                (f'{p["stock"]} {p["unit"]}', 100, stat_color),
                (str(p["min_stock"]), 80, COLORS["text_muted"]),
                (status, 100, stat_color),
                (format_rupiah(stock_val), 140, COLORS["text_secondary"]),
            ]
            for val, w, color in values:
                ctk.CTkLabel(row, text=val, font=("Segoe UI", 11), text_color=color,
                             width=w, anchor="w").pack(side="left", padx=6)

            ctk.CTkButton(row, text="± Stok", width=70, height=26, corner_radius=6,
                          fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                          font=("Segoe UI", 10),
                          command=lambda prod=p: self._quick_stock_adjust(prod)).pack(side="left", padx=4)

        # Total row
        total_row = ctk.CTkFrame(self.stock_scroll, fg_color=COLORS["header_bg"], corner_radius=8, height=38)
        total_row.pack(fill="x", pady=(8, 0))
        total_row.pack_propagate(False)

        ctk.CTkLabel(total_row, text=f"Total Nilai Stok: {format_rupiah(total_value)}",
                     font=("Segoe UI Bold", 13), text_color=COLORS["accent"]).pack(side="right", padx=16)
        ctk.CTkLabel(total_row, text=f"{len(products)} produk",
                     font=("Segoe UI", 12), text_color=COLORS["text_muted"]).pack(side="left", padx=16)

    def _build_low_stock_tab(self):
        tab = self.tab_low
        self.low_scroll = ctk.CTkScrollableFrame(tab, fg_color=COLORS["bg_dark"],
                                                  scrollbar_button_color="#3a3a50",
                                                  scrollbar_button_hover_color=COLORS["accent"])
        self.low_scroll.pack(fill="both", expand=True, padx=8, pady=8)
        self._load_low_stock()

    def _load_low_stock(self):
        for w in self.low_scroll.winfo_children():
            w.destroy()

        products = db.get_low_stock_products()

        if not products:
            ctk.CTkLabel(self.low_scroll, text="✅ Semua stok mencukupi!",
                         font=("Segoe UI", 16), text_color=COLORS["success"]).pack(pady=60)
            return

        ctk.CTkLabel(self.low_scroll, text=f"⚠ {len(products)} produk stok menipis",
                     font=("Segoe UI Bold", 14), text_color=COLORS["warning"]).pack(pady=(8, 12), anchor="w", padx=8)

        for i, p in enumerate(products):
            card = ctk.CTkFrame(self.low_scroll, fg_color=COLORS["bg_card"], corner_radius=12,
                                border_color=COLORS["warning"] if p["stock"] > 0 else COLORS["danger"],
                                border_width=1)
            card.pack(fill="x", padx=4, pady=4)

            inner = ctk.CTkFrame(card, fg_color="transparent")
            inner.pack(fill="x", padx=16, pady=12)

            left = ctk.CTkFrame(inner, fg_color="transparent")
            left.pack(side="left", fill="x", expand=True)

            ctk.CTkLabel(left, text=p["name"], font=("Segoe UI Semibold", 13),
                         text_color=COLORS["text_primary"]).pack(anchor="w")

            info_text = f'Kategori: {p.get("category_name", "-")} | Stok: {p["stock"]} / Min: {p["min_stock"]} | Harga: {format_rupiah(p["sell_price"])}'
            ctk.CTkLabel(left, text=info_text, font=("Segoe UI", 11),
                         text_color=COLORS["text_muted"]).pack(anchor="w")

            # Progress bar
            pct = (p["stock"] / p["min_stock"] * 100) if p["min_stock"] > 0 else 0
            bar_color = COLORS["danger"] if pct <= 30 else COLORS["warning"]
            prog = ctk.CTkProgressBar(left, width=200, height=8, corner_radius=4,
                                      fg_color=COLORS["bg_entry"], progress_color=bar_color)
            prog.set(min(pct / 100, 1.0))
            prog.pack(anchor="w", pady=(4, 0))

            ctk.CTkButton(inner, text="+ Restok", width=90, height=32, corner_radius=8,
                          fg_color=COLORS["success"], hover_color=COLORS["success_hover"],
                          font=("Segoe UI Semibold", 11),
                          command=lambda prod=p: self._quick_stock_adjust(prod)).pack(side="right")

    def _build_movement_tab(self):
        tab = self.tab_movement

        ctk.CTkButton(tab, text="↻ Refresh", width=100, height=34,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI", 12), corner_radius=8,
                      command=self._load_movements).pack(anchor="e", padx=8, pady=8)

        self.move_scroll = ctk.CTkScrollableFrame(tab, fg_color=COLORS["bg_dark"],
                                                   scrollbar_button_color="#3a3a50",
                                                   scrollbar_button_hover_color=COLORS["accent"])
        self.move_scroll.pack(fill="both", expand=True, padx=8, pady=(0, 8))
        self._load_movements()

    def _load_movements(self):
        for w in self.move_scroll.winfo_children():
            w.destroy()

        movements = db.get_stock_movements(limit=200)

        th = ctk.CTkFrame(self.move_scroll, fg_color=COLORS["header_bg"], corner_radius=8, height=38)
        th.pack(fill="x", pady=(0, 4))
        th.pack_propagate(False)

        cols = [("Waktu", 140), ("Produk", 180), ("Tipe", 90), ("Jumlah", 70),
                ("Stok Lama", 80), ("Stok Baru", 80), ("Referensi", 120), ("User", 100)]
        for name, w in cols:
            ctk.CTkLabel(th, text=name, font=("Segoe UI Semibold", 11),
                         text_color=COLORS["text_secondary"], width=w, anchor="w").pack(side="left", padx=4)

        type_colors = {"in": COLORS["success"], "out": COLORS["danger"], "adjustment": COLORS["warning"]}
        type_labels = {"in": "Masuk", "out": "Keluar", "adjustment": "Adjust"}

        for i, m in enumerate(movements):
            bg = COLORS["bg_card"] if i % 2 == 0 else COLORS["table_row_alt"]
            row = ctk.CTkFrame(self.move_scroll, fg_color=bg, height=34, corner_radius=4)
            row.pack(fill="x", pady=1)
            row.pack_propagate(False)

            mtype = m["movement_type"]
            values = [
                (m["created_at"][:16] if m["created_at"] else "-", 140, COLORS["text_muted"]),
                (m.get("product_name", "-"), 180, COLORS["text_primary"]),
                (type_labels.get(mtype, mtype), 90, type_colors.get(mtype, COLORS["text_secondary"])),
                (str(m["quantity"]), 70, COLORS["text_primary"]),
                (str(m.get("previous_stock", "-")), 80, COLORS["text_secondary"]),
                (str(m.get("new_stock", "-")), 80, COLORS["text_primary"]),
                (m.get("reference", "-") or "-", 120, COLORS["text_muted"]),
                (m.get("user_name", "-") or "-", 100, COLORS["text_secondary"]),
            ]
            for val, w, color in values:
                ctk.CTkLabel(row, text=val, font=("Segoe UI", 10), text_color=color,
                             width=w, anchor="w").pack(side="left", padx=4)

    def _build_adjust_tab(self):
        tab = self.tab_adjust

        card = ctk.CTkFrame(tab, fg_color=COLORS["bg_card"], corner_radius=16)
        card.pack(fill="x", padx=20, pady=20)

        ctk.CTkLabel(card, text="🔧 Penyesuaian Stok", font=("Segoe UI Bold", 16),
                     text_color=COLORS["text_primary"]).pack(padx=20, pady=(16, 12), anchor="w")

        inner = ctk.CTkFrame(card, fg_color="transparent")
        inner.pack(fill="x", padx=20, pady=(0, 16))

        # Product selection
        ctk.CTkLabel(inner, text="Produk:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(anchor="w", pady=(0, 4))
        products = db.get_all_products()
        prod_names = [f'{p["name"]} (Stok: {p["stock"]})' for p in products]
        self.adjust_product = ctk.CTkComboBox(inner, values=prod_names, height=38,
                                              fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                              button_color=COLORS["accent"],
                                              dropdown_fg_color=COLORS["bg_card"],
                                              dropdown_hover_color=COLORS["bg_card_hover"],
                                              text_color=COLORS["text_primary"], font=("Segoe UI", 12))
        self.adjust_product.pack(fill="x", pady=(0, 12))
        if prod_names:
            self.adjust_product.set(prod_names[0])
        self._products_list = products

        # Movement type
        ctk.CTkLabel(inner, text="Tipe:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(anchor="w", pady=(0, 4))
        self.adjust_type = ctk.CTkSegmentedButton(inner, values=["Stok Masuk", "Stok Keluar", "Penyesuaian"],
                                                   height=36,
                                                   fg_color=COLORS["bg_entry"],
                                                   selected_color=COLORS["accent"],
                                                   selected_hover_color=COLORS["accent_hover"],
                                                   unselected_color=COLORS["bg_entry"],
                                                   unselected_hover_color=COLORS["bg_card_hover"],
                                                   text_color=COLORS["text_secondary"])
        self.adjust_type.set("Stok Masuk")
        self.adjust_type.pack(fill="x", pady=(0, 12))

        # Quantity
        ctk.CTkLabel(inner, text="Jumlah:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(anchor="w", pady=(0, 4))
        self.adjust_qty = ctk.CTkEntry(inner, height=38, corner_radius=8,
                                       fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                       text_color=COLORS["text_primary"], font=("Segoe UI", 12),
                                       placeholder_text="Masukkan jumlah")
        self.adjust_qty.pack(fill="x", pady=(0, 12))

        # Notes
        ctk.CTkLabel(inner, text="Catatan:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(anchor="w", pady=(0, 4))
        self.adjust_notes = ctk.CTkEntry(inner, height=38, corner_radius=8,
                                         fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                         text_color=COLORS["text_primary"], font=("Segoe UI", 12),
                                         placeholder_text="Catatan (opsional)")
        self.adjust_notes.pack(fill="x", pady=(0, 16))

        ctk.CTkButton(inner, text="💾 Simpan Penyesuaian", height=42, corner_radius=10,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI Semibold", 14), command=self._do_adjustment).pack(fill="x")

    def _do_adjustment(self):
        selected = self.adjust_product.get()
        idx = None
        for i, p in enumerate(self._products_list):
            if selected.startswith(p["name"]):
                idx = i
                break
        if idx is None:
            messagebox.showwarning("Error", "Pilih produk!")
            return

        try:
            qty = int(self.adjust_qty.get())
        except ValueError:
            messagebox.showwarning("Error", "Jumlah harus angka!")
            return

        if qty <= 0:
            messagebox.showwarning("Error", "Jumlah harus lebih dari 0!")
            return

        product = self._products_list[idx]
        type_map = {"Stok Masuk": "in", "Stok Keluar": "out", "Penyesuaian": "adjustment"}
        mtype = type_map.get(self.adjust_type.get(), "in")
        notes = self.adjust_notes.get().strip()

        ok, msg = db.update_stock(product["id"], qty, mtype, notes=notes,
                                  user_id=self.app.current_user["id"])
        if ok:
            messagebox.showinfo("Sukses", msg)
            self.adjust_qty.delete(0, "end")
            self.adjust_notes.delete(0, "end")
            self._load_stock()
            self._load_low_stock()
            self._load_movements()
            # Refresh product list in adjust tab
            products = db.get_all_products()
            self._products_list = products
            prod_names = [f'{p["name"]} (Stok: {p["stock"]})' for p in products]
            self.adjust_product.configure(values=prod_names)
        else:
            messagebox.showerror("Error", msg)

    def _quick_stock_adjust(self, product):
        dialog = ctk.CTkToplevel(self.parent)
        dialog.title(f"Penyesuaian Stok - {product['name']}")
        dialog.geometry("400x350")
        dialog.resizable(False, False)
        dialog.configure(fg_color=COLORS["bg_dark"])
        dialog.transient(self.parent)
        dialog.grab_set()

        dialog.update_idletasks()
        x = (dialog.winfo_screenwidth() - 400) // 2
        y = (dialog.winfo_screenheight() - 350) // 2
        dialog.geometry(f"400x350+{x}+{y}")

        card = ctk.CTkFrame(dialog, fg_color=COLORS["bg_card"], corner_radius=16)
        card.pack(fill="both", expand=True, padx=16, pady=16)

        ctk.CTkLabel(card, text=f"📦 {product['name']}", font=("Segoe UI Bold", 16),
                     text_color=COLORS["text_primary"]).pack(padx=20, pady=(16, 4))
        ctk.CTkLabel(card, text=f"Stok saat ini: {product['stock']} {product['unit']}",
                     font=("Segoe UI", 12), text_color=COLORS["text_muted"]).pack()

        inner = ctk.CTkFrame(card, fg_color="transparent")
        inner.pack(fill="x", padx=20, pady=16)

        ctk.CTkLabel(inner, text="Jumlah tambah:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(anchor="w", pady=(0, 4))
        qty_entry = ctk.CTkEntry(inner, height=38, corner_radius=8,
                                 fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                 text_color=COLORS["text_primary"], font=("Segoe UI", 12))
        qty_entry.pack(fill="x", pady=(0, 12))

        ctk.CTkLabel(inner, text="Catatan:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(anchor="w", pady=(0, 4))
        notes_entry = ctk.CTkEntry(inner, height=38, corner_radius=8,
                                   fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                   text_color=COLORS["text_primary"], font=("Segoe UI", 12),
                                   placeholder_text="Restok dari supplier")
        notes_entry.pack(fill="x", pady=(0, 16))

        def save():
            try:
                qty = int(qty_entry.get())
                if qty <= 0:
                    raise ValueError
            except ValueError:
                messagebox.showwarning("Error", "Masukkan jumlah yang valid!")
                return

            ok, msg = db.update_stock(product["id"], qty, "in",
                                      notes=notes_entry.get().strip(),
                                      user_id=self.app.current_user["id"])
            if ok:
                dialog.destroy()
                self._load_stock()
                self._load_low_stock()
                self._load_movements()
                messagebox.showinfo("Sukses", msg)
            else:
                messagebox.showerror("Error", msg)

        ctk.CTkButton(inner, text="💾 Tambah Stok", height=40, corner_radius=10,
                      fg_color=COLORS["success"], hover_color=COLORS["success_hover"],
                      font=("Segoe UI Semibold", 14), command=save).pack(fill="x")
