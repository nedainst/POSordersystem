"""
Expenses Management Page
"""
import customtkinter as ctk
from tkinter import messagebox
from datetime import datetime, timedelta
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


def format_rupiah(amount):
    try:
        return f"Rp {amount:,.0f}".replace(",", ".")
    except (ValueError, TypeError):
        return "Rp 0"


class ExpensesPage:
    def __init__(self, parent, app):
        self.parent = parent
        self.app = app
        self._build()

    def _build(self):
        header = ctk.CTkFrame(self.parent, fg_color=COLORS["header_bg"], height=64, corner_radius=0)
        header.pack(fill="x")
        header.pack_propagate(False)

        ctk.CTkLabel(header, text="💰  Pengeluaran", font=("Segoe UI Bold", 20),
                     text_color=COLORS["text_primary"]).pack(side="left", padx=24, pady=16)

        ctk.CTkButton(header, text="+ Tambah Pengeluaran", width=180, height=36,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI Semibold", 12), corner_radius=8,
                      command=self._show_add_dialog).pack(side="right", padx=24)

        # Filter bar
        filter_bar = ctk.CTkFrame(self.parent, fg_color=COLORS["bg_card"], corner_radius=12, height=54)
        filter_bar.pack(fill="x", padx=20, pady=(12, 8))
        filter_bar.pack_propagate(False)

        inner = ctk.CTkFrame(filter_bar, fg_color="transparent")
        inner.pack(fill="both", expand=True, padx=12, pady=8)

        ctk.CTkLabel(inner, text="Periode:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(side="left")

        self.start_date = ctk.CTkEntry(inner, width=110, height=34, corner_radius=8,
                                       fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                       text_color=COLORS["text_primary"], font=("Segoe UI", 11))
        self.start_date.pack(side="left", padx=(8, 4))
        self.start_date.insert(0, (datetime.now() - timedelta(days=30)).strftime("%Y-%m-%d"))

        self.end_date = ctk.CTkEntry(inner, width=110, height=34, corner_radius=8,
                                     fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                     text_color=COLORS["text_primary"], font=("Segoe UI", 11))
        self.end_date.pack(side="left", padx=(8, 8))
        self.end_date.insert(0, datetime.now().strftime("%Y-%m-%d"))

        ctk.CTkButton(inner, text="🔍 Filter", width=90, height=34,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI Semibold", 12), corner_radius=8,
                      command=self._load_expenses).pack(side="left", padx=4)

        self.total_label = ctk.CTkLabel(inner, text="", font=("Segoe UI Bold", 14),
                                         text_color=COLORS["danger"])
        self.total_label.pack(side="right", padx=12)

        self.scroll = ctk.CTkScrollableFrame(self.parent, fg_color=COLORS["bg_dark"],
                                              scrollbar_button_color="#3a3a50",
                                              scrollbar_button_hover_color=COLORS["accent"])
        self.scroll.pack(fill="both", expand=True, padx=20, pady=(0, 16))
        self._load_expenses()

    def _load_expenses(self):
        for w in self.scroll.winfo_children():
            w.destroy()

        start = self.start_date.get().strip()
        end = self.end_date.get().strip()
        expenses = db.get_expenses(start, end)

        total = sum(e["amount"] for e in expenses)
        self.total_label.configure(text=f"Total: {format_rupiah(total)}")

        if not expenses:
            ctk.CTkLabel(self.scroll, text="Belum ada data pengeluaran",
                         font=("Segoe UI", 14), text_color=COLORS["text_muted"]).pack(pady=40)
            return

        # Table header
        th = ctk.CTkFrame(self.scroll, fg_color=COLORS["header_bg"], corner_radius=8, height=38)
        th.pack(fill="x", pady=(0, 4))
        th.pack_propagate(False)

        cols = [("Tanggal", 140), ("Kategori", 160), ("Jumlah", 140), ("Deskripsi", 260), ("User", 120)]
        for name, w in cols:
            ctk.CTkLabel(th, text=name, font=("Segoe UI Semibold", 11),
                         text_color=COLORS["text_secondary"], width=w, anchor="w").pack(side="left", padx=6)

        for i, e in enumerate(expenses):
            bg = COLORS["bg_card"] if i % 2 == 0 else COLORS["table_row_alt"]
            row = ctk.CTkFrame(self.scroll, fg_color=bg, height=36, corner_radius=4)
            row.pack(fill="x", pady=1)
            row.pack_propagate(False)

            values = [
                (e["created_at"][:16] if e["created_at"] else "-", 140, COLORS["text_muted"]),
                (e["category"], 160, COLORS["text_primary"]),
                (format_rupiah(e["amount"]), 140, COLORS["danger"]),
                (e.get("description", "-") or "-", 260, COLORS["text_secondary"]),
                (e.get("user_name", "-") or "-", 120, COLORS["text_muted"]),
            ]
            for val, w, color in values:
                ctk.CTkLabel(row, text=val, font=("Segoe UI", 11), text_color=color,
                             width=w, anchor="w").pack(side="left", padx=6)

    def _show_add_dialog(self):
        dialog = ctk.CTkToplevel(self.parent)
        dialog.title("Tambah Pengeluaran")
        dialog.geometry("430x400")
        dialog.resizable(False, False)
        dialog.configure(fg_color=COLORS["bg_dark"])
        dialog.transient(self.parent)
        dialog.grab_set()

        dialog.update_idletasks()
        x = (dialog.winfo_screenwidth() - 430) // 2
        y = (dialog.winfo_screenheight() - 400) // 2
        dialog.geometry(f"430x400+{x}+{y}")

        card = ctk.CTkFrame(dialog, fg_color=COLORS["bg_card"], corner_radius=16)
        card.pack(fill="both", expand=True, padx=16, pady=16)

        ctk.CTkLabel(card, text="💰 Tambah Pengeluaran", font=("Segoe UI Bold", 18),
                     text_color=COLORS["text_primary"]).pack(pady=(16, 12))

        # Category
        ctk.CTkLabel(card, text="Kategori:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(anchor="w", padx=20, pady=(8, 2))
        expense_cats = ["Gaji", "Sewa", "Listrik", "Air", "Internet", "Transportasi",
                        "Perlengkapan", "Perawatan", "Lainnya"]
        category = ctk.CTkComboBox(card, values=expense_cats, height=36,
                                   fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                   button_color=COLORS["accent"],
                                   dropdown_fg_color=COLORS["bg_card"],
                                   dropdown_hover_color=COLORS["bg_card_hover"],
                                   text_color=COLORS["text_primary"], font=("Segoe UI", 12))
        category.set("Lainnya")
        category.pack(fill="x", padx=20)

        # Amount
        ctk.CTkLabel(card, text="Jumlah (Rp):", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(anchor="w", padx=20, pady=(12, 2))
        amount = ctk.CTkEntry(card, height=36, corner_radius=8,
                              fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                              text_color=COLORS["text_primary"], font=("Segoe UI", 12),
                              placeholder_text="0")
        amount.pack(fill="x", padx=20)

        # Description
        ctk.CTkLabel(card, text="Deskripsi:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(anchor="w", padx=20, pady=(12, 2))
        desc = ctk.CTkEntry(card, height=36, corner_radius=8,
                            fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                            text_color=COLORS["text_primary"], font=("Segoe UI", 12),
                            placeholder_text="Keterangan pengeluaran")
        desc.pack(fill="x", padx=20)

        def save():
            try:
                amt = float(amount.get())
                if amt <= 0:
                    raise ValueError
            except ValueError:
                messagebox.showwarning("Error", "Jumlah harus angka positif!")
                return

            db.add_expense(category.get(), amt, desc.get().strip(), self.app.current_user["id"])
            dialog.destroy()
            self._load_expenses()

        ctk.CTkButton(card, text="💾 Simpan", height=40, corner_radius=10,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI Semibold", 14), command=save).pack(fill="x", padx=20, pady=(20, 8))
