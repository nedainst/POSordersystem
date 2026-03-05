"""
Suppliers Management Page
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
    "danger": "#e74c3c",
    "danger_hover": "#c0392b",
    "text_primary": "#e8e8f0",
    "text_secondary": "#a9a9c0",
    "text_muted": "#6c6c88",
    "border": "#2a2a3d",
    "header_bg": "#1a1a28",
    "table_row_alt": "#1b1b2b",
}


class SuppliersPage:
    def __init__(self, parent, app):
        self.parent = parent
        self.app = app
        self._build()

    def _build(self):
        header = ctk.CTkFrame(self.parent, fg_color=COLORS["header_bg"], height=64, corner_radius=0)
        header.pack(fill="x")
        header.pack_propagate(False)

        ctk.CTkLabel(header, text="📇  Manajemen Supplier", font=("Segoe UI Bold", 20),
                     text_color=COLORS["text_primary"]).pack(side="left", padx=24, pady=16)

        ctk.CTkButton(header, text="+ Tambah Supplier", width=160, height=36,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI Semibold", 12), corner_radius=8,
                      command=self._show_add_dialog).pack(side="right", padx=24)

        self.scroll = ctk.CTkScrollableFrame(self.parent, fg_color=COLORS["bg_dark"],
                                              scrollbar_button_color="#3a3a50",
                                              scrollbar_button_hover_color=COLORS["accent"])
        self.scroll.pack(fill="both", expand=True, padx=20, pady=12)
        self._load_suppliers()

    def _load_suppliers(self):
        for w in self.scroll.winfo_children():
            w.destroy()

        suppliers = db.get_all_suppliers()
        if not suppliers:
            ctk.CTkLabel(self.scroll, text="Belum ada supplier", font=("Segoe UI", 14),
                         text_color=COLORS["text_muted"]).pack(pady=40)
            return

        for i, s in enumerate(suppliers):
            card = ctk.CTkFrame(self.scroll, fg_color=COLORS["bg_card"], corner_radius=12)
            card.pack(fill="x", padx=4, pady=4)

            inner = ctk.CTkFrame(card, fg_color="transparent")
            inner.pack(fill="x", padx=16, pady=12)

            left = ctk.CTkFrame(inner, fg_color="transparent")
            left.pack(side="left", fill="x", expand=True)

            ctk.CTkLabel(left, text=s["name"], font=("Segoe UI Semibold", 14),
                         text_color=COLORS["text_primary"]).pack(anchor="w")
            info_parts = []
            if s.get("contact_person"):
                info_parts.append(f"👤 {s['contact_person']}")
            if s.get("phone"):
                info_parts.append(f"📞 {s['phone']}")
            if s.get("email"):
                info_parts.append(f"✉ {s['email']}")
            if info_parts:
                ctk.CTkLabel(left, text="  |  ".join(info_parts), font=("Segoe UI", 11),
                             text_color=COLORS["text_secondary"]).pack(anchor="w")
            if s.get("address"):
                ctk.CTkLabel(left, text=f"📍 {s['address']}", font=("Segoe UI", 11),
                             text_color=COLORS["text_muted"]).pack(anchor="w")

            # Actions
            act = ctk.CTkFrame(inner, fg_color="transparent")
            act.pack(side="right")
            ctk.CTkButton(act, text="✏ Edit", width=70, height=30, corner_radius=6,
                          fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                          font=("Segoe UI", 11),
                          command=lambda sup=s: self._show_edit_dialog(sup)).pack(side="left", padx=4)
            ctk.CTkButton(act, text="🗑", width=36, height=30, corner_radius=6,
                          fg_color=COLORS["danger"], hover_color=COLORS["danger_hover"],
                          font=("Segoe UI", 11),
                          command=lambda sup=s: self._delete_supplier(sup)).pack(side="left")

    def _show_add_dialog(self):
        self._supplier_dialog("Tambah Supplier")

    def _show_edit_dialog(self, supplier):
        self._supplier_dialog("Edit Supplier", supplier)

    def _supplier_dialog(self, title, supplier=None):
        dialog = ctk.CTkToplevel(self.parent)
        dialog.title(title)
        dialog.geometry("450x480")
        dialog.resizable(False, False)
        dialog.configure(fg_color=COLORS["bg_dark"])
        dialog.transient(self.parent)
        dialog.grab_set()

        dialog.update_idletasks()
        x = (dialog.winfo_screenwidth() - 450) // 2
        y = (dialog.winfo_screenheight() - 480) // 2
        dialog.geometry(f"450x480+{x}+{y}")

        card = ctk.CTkFrame(dialog, fg_color=COLORS["bg_card"], corner_radius=16)
        card.pack(fill="both", expand=True, padx=16, pady=16)

        ctk.CTkLabel(card, text=title, font=("Segoe UI Bold", 18),
                     text_color=COLORS["text_primary"]).pack(pady=(16, 12))

        fields = {}
        for key, label in [("name", "Nama Supplier *"), ("contact_person", "Contact Person"),
                           ("phone", "Telepon"), ("email", "Email"), ("address", "Alamat")]:
            ctk.CTkLabel(card, text=label, font=("Segoe UI", 12),
                         text_color=COLORS["text_secondary"], anchor="w").pack(fill="x", padx=20, pady=(8, 2))
            entry = ctk.CTkEntry(card, height=36, corner_radius=8,
                                 fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                 text_color=COLORS["text_primary"], font=("Segoe UI", 12))
            entry.pack(fill="x", padx=20)
            if supplier and supplier.get(key):
                entry.insert(0, supplier[key])
            fields[key] = entry

        def save():
            name = fields["name"].get().strip()
            if not name:
                messagebox.showwarning("Error", "Nama supplier harus diisi!")
                return
            cp = fields["contact_person"].get().strip()
            phone = fields["phone"].get().strip()
            email = fields["email"].get().strip()
            address = fields["address"].get().strip()

            if supplier:
                db.update_supplier(supplier["id"], name, cp, phone, email, address)
            else:
                db.add_supplier(name, cp, phone, email, address)
            dialog.destroy()
            self._load_suppliers()

        ctk.CTkButton(card, text="💾 Simpan", height=40, corner_radius=10,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI Semibold", 14), command=save).pack(fill="x", padx=20, pady=(16, 8))

    def _delete_supplier(self, supplier):
        if messagebox.askyesno("Konfirmasi", f'Hapus supplier "{supplier["name"]}"?'):
            db.delete_supplier(supplier["id"])
            self._load_suppliers()
