"""
Transaction History Page
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


class HistoryPage:
    def __init__(self, parent, app):
        self.parent = parent
        self.app = app
        self._build()

    def _build(self):
        # Header
        header = ctk.CTkFrame(self.parent, fg_color=COLORS["header_bg"], height=64, corner_radius=0)
        header.pack(fill="x")
        header.pack_propagate(False)

        ctk.CTkLabel(header, text="📜  Riwayat Transaksi", font=("Segoe UI Bold", 20),
                     text_color=COLORS["text_primary"]).pack(side="left", padx=24, pady=16)

        # Filter bar
        filter_bar = ctk.CTkFrame(self.parent, fg_color=COLORS["bg_card"], corner_radius=12, height=56)
        filter_bar.pack(fill="x", padx=20, pady=(12, 8))
        filter_bar.pack_propagate(False)

        inner = ctk.CTkFrame(filter_bar, fg_color="transparent")
        inner.pack(fill="both", expand=True, padx=16, pady=8)

        # Search
        self.search_var = ctk.StringVar()
        ctk.CTkEntry(inner, textvariable=self.search_var,
                     placeholder_text="🔍 Cari invoice / pelanggan...",
                     height=36, width=250, corner_radius=8,
                     fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                     text_color=COLORS["text_primary"], font=("Segoe UI", 12)).pack(side="left")

        # Date filters
        ctk.CTkLabel(inner, text="Dari:", font=("Segoe UI", 11),
                     text_color=COLORS["text_secondary"]).pack(side="left", padx=(16, 4))
        self.start_date = ctk.CTkEntry(inner, width=110, height=36, corner_radius=8,
                                       fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                       text_color=COLORS["text_primary"], font=("Segoe UI", 11))
        self.start_date.pack(side="left")
        self.start_date.insert(0, (datetime.now() - timedelta(days=30)).strftime("%Y-%m-%d"))

        ctk.CTkLabel(inner, text="Sampai:", font=("Segoe UI", 11),
                     text_color=COLORS["text_secondary"]).pack(side="left", padx=(12, 4))
        self.end_date = ctk.CTkEntry(inner, width=110, height=36, corner_radius=8,
                                     fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                     text_color=COLORS["text_primary"], font=("Segoe UI", 11))
        self.end_date.pack(side="left")
        self.end_date.insert(0, datetime.now().strftime("%Y-%m-%d"))

        ctk.CTkButton(inner, text="🔍 Filter", width=90, height=36,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI Semibold", 12), corner_radius=8,
                      command=self._load_transactions).pack(side="left", padx=12)

        ctk.CTkButton(inner, text="↻", width=36, height=36,
                      fg_color=COLORS["bg_entry"], hover_color=COLORS["bg_card_hover"],
                      font=("Segoe UI", 14), corner_radius=8,
                      text_color=COLORS["text_secondary"],
                      command=self._load_transactions).pack(side="left")

        # Summary row
        self.summary_frame = ctk.CTkFrame(self.parent, fg_color="transparent", height=36)
        self.summary_frame.pack(fill="x", padx=24, pady=(4, 4))

        self.summary_label = ctk.CTkLabel(self.summary_frame, text="", font=("Segoe UI", 12),
                                           text_color=COLORS["text_secondary"])
        self.summary_label.pack(side="left")

        # Table
        self.table_scroll = ctk.CTkScrollableFrame(self.parent, fg_color=COLORS["bg_dark"],
                                                    scrollbar_button_color="#3a3a50",
                                                    scrollbar_button_hover_color=COLORS["accent"])
        self.table_scroll.pack(fill="both", expand=True, padx=20, pady=(0, 16))

        self._load_transactions()

    def _load_transactions(self):
        for w in self.table_scroll.winfo_children():
            w.destroy()

        start = self.start_date.get().strip()
        end = self.end_date.get().strip()
        search = self.search_var.get().strip()

        transactions = db.get_transactions(start_date=start, end_date=end, search=search)

        total_sales = sum(t["final_amount"] for t in transactions if t["status"] == "completed")
        completed = sum(1 for t in transactions if t["status"] == "completed")
        voided = sum(1 for t in transactions if t["status"] == "voided")

        self.summary_label.configure(
            text=f"Total: {len(transactions)} transaksi | Selesai: {completed} | Void: {voided} | Total Penjualan: {format_rupiah(total_sales)}"
        )

        # Table header
        th = ctk.CTkFrame(self.table_scroll, fg_color=COLORS["header_bg"], corner_radius=8, height=40)
        th.pack(fill="x", pady=(0, 4))
        th.pack_propagate(False)

        cols = [("Invoice", 160), ("Pelanggan", 120), ("Kasir", 120), ("Total", 120),
                ("Bayar", 100), ("Kembalian", 100), ("Metode", 80), ("Status", 80), ("Waktu", 140), ("Aksi", 80)]
        for name, w in cols:
            ctk.CTkLabel(th, text=name, font=("Segoe UI Semibold", 11),
                         text_color=COLORS["text_secondary"], width=w, anchor="w").pack(side="left", padx=4)

        for i, tx in enumerate(transactions):
            bg = COLORS["bg_card"] if i % 2 == 0 else COLORS["table_row_alt"]
            row = ctk.CTkFrame(self.table_scroll, fg_color=bg, height=36, corner_radius=4)
            row.pack(fill="x", pady=1)
            row.pack_propagate(False)

            status_color = COLORS["success"] if tx["status"] == "completed" else COLORS["danger"]
            status_text = "Selesai" if tx["status"] == "completed" else "Void"

            values = [
                (tx["invoice_number"], 160, COLORS["accent"]),
                (tx.get("customer_name", "Umum"), 120, COLORS["text_primary"]),
                (tx.get("cashier_name", "-") or "-", 120, COLORS["text_secondary"]),
                (format_rupiah(tx["final_amount"]), 120, COLORS["text_primary"]),
                (format_rupiah(tx["payment_amount"]), 100, COLORS["text_secondary"]),
                (format_rupiah(tx["change_amount"]), 100, COLORS["text_secondary"]),
                (tx["payment_method"].upper(), 80, COLORS["text_muted"]),
                (status_text, 80, status_color),
                (tx["created_at"][:16] if tx["created_at"] else "-", 140, COLORS["text_muted"]),
            ]
            for val, w, color in values:
                ctk.CTkLabel(row, text=str(val), font=("Segoe UI", 10), text_color=color,
                             width=w, anchor="w").pack(side="left", padx=4)

            # Action buttons
            act_frame = ctk.CTkFrame(row, fg_color="transparent", width=80)
            act_frame.pack(side="left", padx=2)

            ctk.CTkButton(act_frame, text="👁", width=28, height=24, corner_radius=4,
                          fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                          font=("Segoe UI", 10),
                          command=lambda t=tx: self._show_detail(t)).pack(side="left", padx=1)

            if tx["status"] == "completed" and self.app.current_user["role"] == "admin":
                ctk.CTkButton(act_frame, text="✕", width=28, height=24, corner_radius=4,
                              fg_color=COLORS["danger"], hover_color=COLORS["danger_hover"],
                              font=("Segoe UI", 10),
                              command=lambda t=tx: self._void_transaction(t)).pack(side="left", padx=1)

    def _show_detail(self, tx):
        dialog = ctk.CTkToplevel(self.parent)
        dialog.title(f"Detail Transaksi - {tx['invoice_number']}")
        dialog.geometry("460x550")
        dialog.resizable(False, False)
        dialog.configure(fg_color=COLORS["bg_dark"])
        dialog.transient(self.parent)
        dialog.grab_set()

        dialog.update_idletasks()
        x = (dialog.winfo_screenwidth() - 460) // 2
        y = (dialog.winfo_screenheight() - 550) // 2
        dialog.geometry(f"460x550+{x}+{y}")

        scroll = ctk.CTkScrollableFrame(dialog, fg_color=COLORS["bg_card"],
                                         scrollbar_button_color="#3a3a50")
        scroll.pack(fill="both", expand=True, padx=16, pady=16)

        # Status banner
        status_color = COLORS["success"] if tx["status"] == "completed" else COLORS["danger"]
        status_text = "✅ SELESAI" if tx["status"] == "completed" else "❌ VOID"
        ctk.CTkLabel(scroll, text=status_text, font=("Segoe UI Bold", 16),
                     text_color=status_color).pack(pady=(8, 4))

        ctk.CTkLabel(scroll, text=tx["invoice_number"], font=("Segoe UI Bold", 14),
                     text_color=COLORS["accent"]).pack()

        ctk.CTkFrame(scroll, fg_color=COLORS["border"], height=1).pack(fill="x", padx=16, pady=10)

        # Info
        info = [
            ("Tanggal", tx["created_at"][:16] if tx["created_at"] else "-"),
            ("Pelanggan", tx.get("customer_name", "Umum")),
            ("Kasir", tx.get("cashier_name", "-") or "-"),
            ("Metode Bayar", tx["payment_method"].upper()),
        ]
        for label, val in info:
            row = ctk.CTkFrame(scroll, fg_color="transparent")
            row.pack(fill="x", padx=20, pady=1)
            ctk.CTkLabel(row, text=label, font=("Segoe UI", 11), text_color=COLORS["text_secondary"]).pack(side="left")
            ctk.CTkLabel(row, text=str(val), font=("Segoe UI", 11), text_color=COLORS["text_primary"]).pack(side="right")

        ctk.CTkFrame(scroll, fg_color=COLORS["border"], height=1).pack(fill="x", padx=16, pady=10)

        # Items
        ctk.CTkLabel(scroll, text="Item:", font=("Segoe UI Semibold", 12),
                     text_color=COLORS["text_primary"]).pack(anchor="w", padx=20, pady=(0, 6))

        items = db.get_transaction_items(tx["id"])
        for item in items:
            row = ctk.CTkFrame(scroll, fg_color="transparent")
            row.pack(fill="x", padx=20, pady=1)
            ctk.CTkLabel(row, text=f'{item["product_name"]} x{item["quantity"]}',
                         font=("Segoe UI", 11), text_color=COLORS["text_primary"]).pack(side="left")
            ctk.CTkLabel(row, text=format_rupiah(item["subtotal"]),
                         font=("Segoe UI", 11), text_color=COLORS["text_primary"]).pack(side="right")

        ctk.CTkFrame(scroll, fg_color=COLORS["border"], height=1).pack(fill="x", padx=16, pady=10)

        # Totals
        totals = [
            ("Subtotal", format_rupiah(tx["total_amount"])),
            ("Diskon", format_rupiah(tx["discount_amount"])),
            ("Pajak", format_rupiah(tx["tax_amount"])),
            ("Total", format_rupiah(tx["final_amount"])),
            ("Bayar", format_rupiah(tx["payment_amount"])),
            ("Kembalian", format_rupiah(tx["change_amount"])),
        ]
        for label, val in totals:
            row = ctk.CTkFrame(scroll, fg_color="transparent")
            row.pack(fill="x", padx=20, pady=1)
            bold = label in ("Total", "Kembalian")
            font = ("Segoe UI Bold", 12) if bold else ("Segoe UI", 11)
            color = COLORS["accent"] if label == "Total" else (COLORS["success"] if label == "Kembalian" else COLORS["text_primary"])
            ctk.CTkLabel(row, text=label, font=font, text_color=COLORS["text_secondary"]).pack(side="left")
            ctk.CTkLabel(row, text=val, font=font, text_color=color).pack(side="right")

        if tx.get("notes"):
            ctk.CTkFrame(scroll, fg_color=COLORS["border"], height=1).pack(fill="x", padx=16, pady=10)
            ctk.CTkLabel(scroll, text=f"Catatan: {tx['notes']}", font=("Segoe UI", 11),
                         text_color=COLORS["text_muted"]).pack(padx=20, anchor="w")

        ctk.CTkButton(dialog, text="Tutup", height=38, corner_radius=10,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI Semibold", 13),
                      command=dialog.destroy).pack(fill="x", padx=16, pady=(0, 16))

    def _void_transaction(self, tx):
        if messagebox.askyesno("Void Transaksi",
                               f'Void transaksi {tx["invoice_number"]}?\nStok akan dikembalikan.'):
            ok, msg = db.void_transaction(tx["id"], self.app.current_user["id"])
            if ok:
                self._load_transactions()
                messagebox.showinfo("Sukses", msg)
            else:
                messagebox.showerror("Error", msg)
