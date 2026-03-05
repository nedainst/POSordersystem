"""
Settings Page - Store settings, tax, receipt, etc.
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
    "danger": "#e74c3c",
    "text_primary": "#e8e8f0",
    "text_secondary": "#a9a9c0",
    "text_muted": "#6c6c88",
    "border": "#2a2a3d",
    "header_bg": "#1a1a28",
}


class SettingsPage:
    def __init__(self, parent, app):
        self.parent = parent
        self.app = app
        self._build()

    def _build(self):
        header = ctk.CTkFrame(self.parent, fg_color=COLORS["header_bg"], height=64, corner_radius=0)
        header.pack(fill="x")
        header.pack_propagate(False)

        ctk.CTkLabel(header, text="⚙  Pengaturan", font=("Segoe UI Bold", 20),
                     text_color=COLORS["text_primary"]).pack(side="left", padx=24, pady=16)

        scroll = ctk.CTkScrollableFrame(self.parent, fg_color=COLORS["bg_dark"],
                                         scrollbar_button_color="#3a3a50",
                                         scrollbar_button_hover_color=COLORS["accent"])
        scroll.pack(fill="both", expand=True, padx=20, pady=12)

        settings = db.get_all_settings()
        self.entries = {}

        # ── Store Info ───────────────────────────────────────
        self._section_header(scroll, "🏪 Informasi Toko")
        store_card = ctk.CTkFrame(scroll, fg_color=COLORS["bg_card"], corner_radius=14)
        store_card.pack(fill="x", padx=4, pady=(0, 16))

        for key, label, default in [
            ("store_name", "Nama Toko", "TOKO SAYA"),
            ("store_address", "Alamat Toko", ""),
            ("store_phone", "Telepon Toko", ""),
        ]:
            self._add_setting_field(store_card, key, label, settings.get(key, default))

        # ── Tax & Payment ────────────────────────────────────
        self._section_header(scroll, "💰 Pajak & Pembayaran")
        tax_card = ctk.CTkFrame(scroll, fg_color=COLORS["bg_card"], corner_radius=14)
        tax_card.pack(fill="x", padx=4, pady=(0, 16))

        self._add_setting_field(tax_card, "tax_rate", "Pajak (%)", settings.get("tax_rate", "11"))
        self._add_setting_field(tax_card, "currency_symbol", "Simbol Mata Uang", settings.get("currency_symbol", "Rp"))

        # ── Receipt ──────────────────────────────────────────
        self._section_header(scroll, "🧾 Struk / Receipt")
        receipt_card = ctk.CTkFrame(scroll, fg_color=COLORS["bg_card"], corner_radius=14)
        receipt_card.pack(fill="x", padx=4, pady=(0, 16))

        self._add_setting_field(receipt_card, "receipt_footer", "Footer Struk",
                                settings.get("receipt_footer", "Terima kasih atas kunjungan Anda!"))

        # ── Inventory ────────────────────────────────────────
        self._section_header(scroll, "📦 Inventaris")
        inv_card = ctk.CTkFrame(scroll, fg_color=COLORS["bg_card"], corner_radius=14)
        inv_card.pack(fill="x", padx=4, pady=(0, 16))

        self._add_setting_field(inv_card, "low_stock_alert", "Batas Peringatan Stok Rendah",
                                settings.get("low_stock_alert", "5"))

        # Save button
        ctk.CTkButton(scroll, text="💾  Simpan Pengaturan", height=48, corner_radius=12,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI Bold", 16), command=self._save).pack(fill="x", padx=4, pady=(8, 16))

        # ── App Info ─────────────────────────────────────────
        info_card = ctk.CTkFrame(scroll, fg_color=COLORS["bg_card"], corner_radius=14)
        info_card.pack(fill="x", padx=4, pady=(0, 16))

        ctk.CTkLabel(info_card, text="ℹ️ Tentang Aplikasi", font=("Segoe UI Semibold", 14),
                     text_color=COLORS["text_primary"]).pack(padx=20, pady=(16, 8), anchor="w")

        info_items = [
            ("Nama Aplikasi", "POS System - Point of Sale"),
            ("Versi", "1.0.0"),
            ("Framework", "Python + CustomTkinter"),
            ("Database", "SQLite3"),
            ("Dibuat dengan", "❤️ Python"),
        ]
        for label, val in info_items:
            row = ctk.CTkFrame(info_card, fg_color="transparent", height=28)
            row.pack(fill="x", padx=20, pady=1)
            row.pack_propagate(False)
            ctk.CTkLabel(row, text=label, font=("Segoe UI", 11),
                         text_color=COLORS["text_secondary"]).pack(side="left")
            ctk.CTkLabel(row, text=val, font=("Segoe UI", 11),
                         text_color=COLORS["text_primary"]).pack(side="right")

        ctk.CTkFrame(info_card, fg_color="transparent", height=12).pack()

        # Reset button
        ctk.CTkButton(scroll, text="🔄  Reset Database (Hati-hati!)", height=40, corner_radius=10,
                      fg_color=COLORS["danger"], hover_color="#c0392b",
                      font=("Segoe UI Semibold", 13),
                      command=self._reset_db).pack(fill="x", padx=4, pady=(0, 24))

    def _section_header(self, parent, text):
        ctk.CTkLabel(parent, text=text, font=("Segoe UI Bold", 15),
                     text_color=COLORS["text_primary"]).pack(anchor="w", padx=8, pady=(12, 6))

    def _add_setting_field(self, parent, key, label, value):
        frame = ctk.CTkFrame(parent, fg_color="transparent")
        frame.pack(fill="x", padx=16, pady=6)

        ctk.CTkLabel(frame, text=label, font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(anchor="w", pady=(0, 2))
        entry = ctk.CTkEntry(frame, height=38, corner_radius=8,
                             fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                             text_color=COLORS["text_primary"], font=("Segoe UI", 12))
        entry.pack(fill="x")
        entry.insert(0, str(value))
        self.entries[key] = entry

    def _save(self):
        for key, entry in self.entries.items():
            db.update_setting(key, entry.get().strip())
        messagebox.showinfo("Sukses", "Pengaturan berhasil disimpan!\nBeberapa perubahan memerlukan restart aplikasi.")

    def _reset_db(self):
        if messagebox.askyesno("Reset Database",
                               "PERINGATAN: Ini akan menghapus SEMUA data!\n\nApakah Anda yakin?",
                               icon="warning"):
            if messagebox.askyesno("Konfirmasi Ulang",
                                   "Tindakan ini tidak dapat dibatalkan.\nKonfirmasi reset database?",
                                   icon="warning"):
                import os
                db_path = db.DB_PATH
                if os.path.exists(db_path):
                    conn = db.get_connection()
                    conn.close()
                    os.remove(db_path)
                db.init_database()
                messagebox.showinfo("Sukses", "Database berhasil di-reset!\nSilakan restart aplikasi.")
