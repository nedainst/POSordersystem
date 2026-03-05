"""
Products Management Page - CRUD for products and categories
"""
import customtkinter as ctk
from tkinter import messagebox
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


class ProductsPage:
    def __init__(self, parent, app):
        self.parent = parent
        self.app = app
        self._build()

    def _build(self):
        # Header
        header = ctk.CTkFrame(self.parent, fg_color=COLORS["header_bg"], height=64, corner_radius=0)
        header.pack(fill="x")
        header.pack_propagate(False)

        ctk.CTkLabel(header, text="📦  Manajemen Produk", font=("Segoe UI Bold", 20),
                     text_color=COLORS["text_primary"]).pack(side="left", padx=24, pady=16)

        # Add product button
        add_btn = ctk.CTkButton(header, text="+ Tambah Produk", width=150, height=36,
                                fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                                font=("Segoe UI Semibold", 12), corner_radius=8,
                                command=self._show_add_dialog)
        add_btn.pack(side="right", padx=8)

        # Category management button
        cat_btn = ctk.CTkButton(header, text="📁 Kategori", width=120, height=36,
                                fg_color=COLORS["bg_card"], hover_color=COLORS["bg_card_hover"],
                                font=("Segoe UI", 12), corner_radius=8,
                                text_color=COLORS["text_secondary"],
                                command=self._show_category_dialog)
        cat_btn.pack(side="right", padx=4)

        # Search & filter bar
        filter_bar = ctk.CTkFrame(self.parent, fg_color="transparent", height=50)
        filter_bar.pack(fill="x", padx=24, pady=(12, 8))

        self.search_var = ctk.StringVar()
        self.search_var.trace_add("write", lambda *_: self._load_products())
        ctk.CTkEntry(filter_bar, textvariable=self.search_var,
                     placeholder_text="🔍 Cari produk...",
                     height=38, width=300, corner_radius=10,
                     fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                     text_color=COLORS["text_primary"], font=("Segoe UI", 12)).pack(side="left")

        # Category filter
        categories = db.get_all_categories()
        cat_names = ["Semua Kategori"] + [c["name"] for c in categories]
        self.cat_filter = ctk.CTkComboBox(filter_bar, values=cat_names, width=180, height=38,
                                          fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                          button_color=COLORS["accent"],
                                          dropdown_fg_color=COLORS["bg_card"],
                                          dropdown_hover_color=COLORS["bg_card_hover"],
                                          text_color=COLORS["text_primary"],
                                          font=("Segoe UI", 12),
                                          command=lambda _: self._load_products())
        self.cat_filter.set("Semua Kategori")
        self.cat_filter.pack(side="left", padx=12)

        self.product_count_label = ctk.CTkLabel(filter_bar, text="", font=("Segoe UI", 12),
                                                 text_color=COLORS["text_muted"])
        self.product_count_label.pack(side="right")

        # Product table
        self.table_scroll = ctk.CTkScrollableFrame(self.parent, fg_color=COLORS["bg_dark"],
                                                    scrollbar_button_color="#3a3a50",
                                                    scrollbar_button_hover_color=COLORS["accent"])
        self.table_scroll.pack(fill="both", expand=True, padx=24, pady=(0, 16))

        self._load_products()

    def _load_products(self):
        search = self.search_var.get()
        cat_name = self.cat_filter.get()
        cat_id = None
        if cat_name != "Semua Kategori":
            for c in db.get_all_categories():
                if c["name"] == cat_name:
                    cat_id = c["id"]
                    break

        products = db.get_all_products(search=search, category_id=cat_id, active_only=False)
        self.product_count_label.configure(text=f"{len(products)} produk")

        for w in self.table_scroll.winfo_children():
            w.destroy()

        # Table header
        th = ctk.CTkFrame(self.table_scroll, fg_color=COLORS["header_bg"], corner_radius=8, height=40)
        th.pack(fill="x", pady=(0, 4))
        th.pack_propagate(False)

        cols = [("No", 40), ("Barcode", 110), ("Nama Produk", 200), ("Kategori", 120),
                ("Harga Beli", 110), ("Harga Jual", 110), ("Stok", 70), ("Status", 80), ("Aksi", 120)]
        for name, w in cols:
            ctk.CTkLabel(th, text=name, font=("Segoe UI Semibold", 11),
                         text_color=COLORS["text_secondary"], width=w, anchor="w").pack(side="left", padx=6)

        for i, p in enumerate(products):
            bg = COLORS["bg_card"] if i % 2 == 0 else COLORS["table_row_alt"]
            row = ctk.CTkFrame(self.table_scroll, fg_color=bg, height=38, corner_radius=4)
            row.pack(fill="x", pady=1)
            row.pack_propagate(False)

            status = "Aktif" if p["is_active"] else "Nonaktif"
            status_color = COLORS["success"] if p["is_active"] else COLORS["danger"]
            stock_color = COLORS["success"] if p["stock"] > p["min_stock"] else (
                COLORS["warning"] if p["stock"] > 0 else COLORS["danger"])

            values = [
                (str(i + 1), 40, COLORS["text_muted"]),
                (p["barcode"] or "-", 110, COLORS["text_secondary"]),
                (p["name"], 200, COLORS["text_primary"]),
                (p.get("category_name") or "-", 120, COLORS["text_secondary"]),
                (format_rupiah(p["buy_price"]), 110, COLORS["text_secondary"]),
                (format_rupiah(p["sell_price"]), 110, COLORS["accent"]),
                (str(p["stock"]), 70, stock_color),
                (status, 80, status_color),
            ]
            for val, w, color in values:
                ctk.CTkLabel(row, text=val, font=("Segoe UI", 11), text_color=color,
                             width=w, anchor="w").pack(side="left", padx=6)

            # Action buttons
            act_frame = ctk.CTkFrame(row, fg_color="transparent", width=120)
            act_frame.pack(side="left", padx=4)
            ctk.CTkButton(act_frame, text="✏", width=32, height=28, corner_radius=6,
                          fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                          font=("Segoe UI", 12),
                          command=lambda prod=p: self._show_edit_dialog(prod)).pack(side="left", padx=2)
            ctk.CTkButton(act_frame, text="🗑", width=32, height=28, corner_radius=6,
                          fg_color=COLORS["danger"], hover_color=COLORS["danger_hover"],
                          font=("Segoe UI", 12),
                          command=lambda prod=p: self._delete_product(prod)).pack(side="left", padx=2)

    def _show_add_dialog(self):
        self._product_dialog("Tambah Produk Baru")

    def _show_edit_dialog(self, product):
        self._product_dialog("Edit Produk", product)

    def _product_dialog(self, title, product=None):
        dialog = ctk.CTkToplevel(self.parent)
        dialog.title(title)
        dialog.geometry("500x620")
        dialog.resizable(False, False)
        dialog.configure(fg_color=COLORS["bg_dark"])
        dialog.transient(self.parent)
        dialog.grab_set()

        dialog.update_idletasks()
        x = (dialog.winfo_screenwidth() - 500) // 2
        y = (dialog.winfo_screenheight() - 620) // 2
        dialog.geometry(f"500x620+{x}+{y}")

        scroll = ctk.CTkScrollableFrame(dialog, fg_color=COLORS["bg_card"],
                                         scrollbar_button_color="#3a3a50")
        scroll.pack(fill="both", expand=True, padx=16, pady=16)

        ctk.CTkLabel(scroll, text=title, font=("Segoe UI Bold", 18),
                     text_color=COLORS["text_primary"]).pack(pady=(8, 16))

        fields = {}
        field_defs = [
            ("barcode", "Barcode", "Text"),
            ("name", "Nama Produk *", "Text"),
            ("category", "Kategori", "Combo"),
            ("buy_price", "Harga Beli *", "Number"),
            ("sell_price", "Harga Jual *", "Number"),
            ("stock", "Stok", "Number"),
            ("min_stock", "Stok Minimum", "Number"),
            ("unit", "Satuan", "Text"),
        ]

        categories = db.get_all_categories()
        cat_names = [c["name"] for c in categories]

        for key, label, ftype in field_defs:
            ctk.CTkLabel(scroll, text=label, font=("Segoe UI", 12),
                         text_color=COLORS["text_secondary"], anchor="w").pack(fill="x", padx=20, pady=(8, 2))

            if ftype == "Combo":
                combo = ctk.CTkComboBox(scroll, values=cat_names, height=38,
                                        fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                        button_color=COLORS["accent"],
                                        dropdown_fg_color=COLORS["bg_card"],
                                        dropdown_hover_color=COLORS["bg_card_hover"],
                                        text_color=COLORS["text_primary"],
                                        font=("Segoe UI", 12))
                combo.pack(fill="x", padx=20)
                if product and product.get("category_name"):
                    combo.set(product["category_name"])
                elif cat_names:
                    combo.set(cat_names[0])
                fields[key] = combo
            else:
                entry = ctk.CTkEntry(scroll, height=38, corner_radius=8,
                                     fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                     text_color=COLORS["text_primary"], font=("Segoe UI", 12))
                entry.pack(fill="x", padx=20)
                if product:
                    val = product.get(key, "")
                    if key == "category":
                        val = product.get("category_name", "")
                    entry.insert(0, str(val) if val else "")
                elif key == "unit":
                    entry.insert(0, "pcs")
                elif key in ("stock", "min_stock"):
                    entry.insert(0, "0" if key == "stock" else "5")
                fields[key] = entry

        def save():
            name = fields["name"].get().strip()
            if not name:
                messagebox.showwarning("Error", "Nama produk harus diisi!")
                return

            cat_name = fields["category"].get()
            cat_id = None
            for c in categories:
                if c["name"] == cat_name:
                    cat_id = c["id"]
                    break

            try:
                buy_p = float(fields["buy_price"].get() or 0)
                sell_p = float(fields["sell_price"].get() or 0)
                stk = int(fields["stock"].get() or 0)
                min_stk = int(fields["min_stock"].get() or 5)
            except ValueError:
                messagebox.showwarning("Error", "Harga dan stok harus berupa angka!")
                return

            barcode = fields["barcode"].get().strip() or None
            unit = fields["unit"].get().strip() or "pcs"

            if product:
                ok, msg = db.update_product(product["id"], barcode, name, cat_id, buy_p, sell_p, stk, min_stk, unit)
            else:
                ok, msg = db.add_product(barcode, name, cat_id, buy_p, sell_p, stk, min_stk, unit)

            if ok:
                dialog.destroy()
                self._load_products()
                messagebox.showinfo("Sukses", msg)
            else:
                messagebox.showerror("Error", msg)

        ctk.CTkButton(scroll, text="💾 Simpan", height=42, corner_radius=10,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI Semibold", 14), command=save).pack(fill="x", padx=20, pady=(20, 8))

        ctk.CTkButton(scroll, text="Batal", height=36, corner_radius=10,
                      fg_color=COLORS["bg_entry"], hover_color=COLORS["bg_card_hover"],
                      font=("Segoe UI", 13), text_color=COLORS["text_secondary"],
                      command=dialog.destroy).pack(fill="x", padx=20, pady=(0, 8))

    def _delete_product(self, product):
        if messagebox.askyesno("Konfirmasi", f'Nonaktifkan produk "{product["name"]}"?'):
            db.delete_product(product["id"])
            self._load_products()

    def _show_category_dialog(self):
        dialog = ctk.CTkToplevel(self.parent)
        dialog.title("Manajemen Kategori")
        dialog.geometry("500x500")
        dialog.resizable(False, False)
        dialog.configure(fg_color=COLORS["bg_dark"])
        dialog.transient(self.parent)
        dialog.grab_set()

        dialog.update_idletasks()
        x = (dialog.winfo_screenwidth() - 500) // 2
        y = (dialog.winfo_screenheight() - 500) // 2
        dialog.geometry(f"500x500+{x}+{y}")

        ctk.CTkLabel(dialog, text="📁 Manajemen Kategori", font=("Segoe UI Bold", 18),
                     text_color=COLORS["text_primary"]).pack(padx=20, pady=(16, 8))

        # Add category
        add_frame = ctk.CTkFrame(dialog, fg_color=COLORS["bg_card"], corner_radius=12)
        add_frame.pack(fill="x", padx=16, pady=8)

        add_inner = ctk.CTkFrame(add_frame, fg_color="transparent")
        add_inner.pack(fill="x", padx=12, pady=12)

        name_entry = ctk.CTkEntry(add_inner, placeholder_text="Nama kategori baru", height=36,
                                  fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                  text_color=COLORS["text_primary"], font=("Segoe UI", 12))
        name_entry.pack(side="left", fill="x", expand=True, padx=(0, 8))

        def add_cat():
            nm = name_entry.get().strip()
            if nm:
                ok, msg = db.add_category(nm)
                if ok:
                    name_entry.delete(0, "end")
                    load_cats()
                else:
                    messagebox.showwarning("Error", msg)

        ctk.CTkButton(add_inner, text="+ Tambah", width=100, height=36,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI Semibold", 12), command=add_cat).pack(side="right")

        # Category list
        cat_scroll = ctk.CTkScrollableFrame(dialog, fg_color=COLORS["bg_dark"],
                                             scrollbar_button_color="#3a3a50")
        cat_scroll.pack(fill="both", expand=True, padx=16, pady=8)

        def load_cats():
            for w in cat_scroll.winfo_children():
                w.destroy()
            cats = db.get_all_categories()
            for c in cats:
                row = ctk.CTkFrame(cat_scroll, fg_color=COLORS["bg_card"], corner_radius=8, height=42)
                row.pack(fill="x", pady=2)
                row.pack_propagate(False)

                color_dot = ctk.CTkFrame(row, fg_color=c["color"], width=14, height=14, corner_radius=7)
                color_dot.pack(side="left", padx=(12, 8))

                ctk.CTkLabel(row, text=c["name"], font=("Segoe UI", 12),
                             text_color=COLORS["text_primary"]).pack(side="left")

                ctk.CTkButton(row, text="🗑", width=30, height=26, corner_radius=6,
                              fg_color=COLORS["danger"], hover_color=COLORS["danger_hover"],
                              font=("Segoe UI", 10),
                              command=lambda cid=c["id"], cname=c["name"]: del_cat(cid, cname)).pack(side="right", padx=8)

        def del_cat(cid, cname):
            if messagebox.askyesno("Konfirmasi", f'Hapus kategori "{cname}"?'):
                db.delete_category(cid)
                load_cats()

        load_cats()
