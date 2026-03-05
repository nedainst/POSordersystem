"""
Dashboard Page - Overview of sales, stats, charts
"""
import customtkinter as ctk
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
    "warning": "#f39c12",
    "danger": "#e74c3c",
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


class DashboardPage:
    def __init__(self, parent, app):
        self.parent = parent
        self.app = app
        self._build()

    def _build(self):
        # Header
        header = ctk.CTkFrame(self.parent, fg_color=COLORS["header_bg"], height=64, corner_radius=0)
        header.pack(fill="x")
        header.pack_propagate(False)

        ctk.CTkLabel(header, text="📊  Dashboard", font=("Segoe UI Bold", 20),
                     text_color=COLORS["text_primary"]).pack(side="left", padx=24, pady=16)

        now = datetime.now().strftime("%A, %d %B %Y")
        ctk.CTkLabel(header, text=now, font=("Segoe UI", 12),
                     text_color=COLORS["text_muted"]).pack(side="right", padx=24)

        refresh_btn = ctk.CTkButton(header, text="↻ Refresh", width=100, height=34,
                                    fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                                    font=("Segoe UI", 12), corner_radius=8,
                                    command=self._refresh)
        refresh_btn.pack(side="right", padx=8)

        # Scrollable content
        self.scroll = ctk.CTkScrollableFrame(self.parent, fg_color=COLORS["bg_dark"],
                                              scrollbar_button_color="#3a3a50",
                                              scrollbar_button_hover_color=COLORS["accent"])
        self.scroll.pack(fill="both", expand=True, padx=0, pady=0)

        self._load_data()

    def _refresh(self):
        for w in self.scroll.winfo_children():
            w.destroy()
        self._load_data()

    def _load_data(self):
        stats = db.get_dashboard_stats()
        settings = db.get_all_settings()
        content = self.scroll

        # ── Stats Cards Row ────────────────────────────────────
        cards_frame = ctk.CTkFrame(content, fg_color="transparent")
        cards_frame.pack(fill="x", padx=24, pady=(20, 0))

        cards_data = [
            ("Penjualan Hari Ini", format_rupiah(stats["today_sales"]["total"]),
             f"{stats['today_sales']['count']} transaksi", COLORS["accent"], "💰"),
            ("Penjualan Bulan Ini", format_rupiah(stats["month_sales"]["total"]),
             f"{stats['month_sales']['count']} transaksi", COLORS["success"], "📈"),
            ("Total Produk", str(stats["total_products"]),
             "Produk aktif", COLORS["warning"], "📦"),
            ("Stok Menipis", str(stats["low_stock_count"]),
             "Perlu restok", COLORS["danger"], "⚠"),
        ]

        for i, (title, value, sub, color, icon) in enumerate(cards_data):
            card = ctk.CTkFrame(cards_frame, fg_color=COLORS["bg_card"], corner_radius=16)
            card.grid(row=0, column=i, sticky="nsew", padx=6, pady=6)
            cards_frame.grid_columnconfigure(i, weight=1)

            inner = ctk.CTkFrame(card, fg_color="transparent")
            inner.pack(fill="both", expand=True, padx=20, pady=16)

            # Accent bar
            bar = ctk.CTkFrame(card, fg_color=color, height=3, corner_radius=2)
            bar.place(relx=0.1, y=0, relwidth=0.8, height=3)

            top = ctk.CTkFrame(inner, fg_color="transparent")
            top.pack(fill="x")
            ctk.CTkLabel(top, text=icon, font=("Segoe UI Emoji", 20), text_color=color).pack(side="left", padx=(0, 8))
            ctk.CTkLabel(top, text=title, font=("Segoe UI", 12), text_color=COLORS["text_secondary"]).pack(side="left")

            ctk.CTkLabel(inner, text=value, font=("Segoe UI Semibold", 26), text_color=COLORS["text_primary"],
                         anchor="w").pack(fill="x", pady=(8, 2))
            ctk.CTkLabel(inner, text=sub, font=("Segoe UI", 11), text_color=COLORS["text_muted"],
                         anchor="w").pack(fill="x")

        # ── Charts Row ─────────────────────────────────────────
        charts_row = ctk.CTkFrame(content, fg_color="transparent")
        charts_row.pack(fill="x", padx=24, pady=(16, 0))
        charts_row.grid_columnconfigure(0, weight=3)
        charts_row.grid_columnconfigure(1, weight=2)

        # Sales chart (bar chart using frames)
        chart_card = ctk.CTkFrame(charts_row, fg_color=COLORS["bg_card"], corner_radius=16)
        chart_card.grid(row=0, column=0, sticky="nsew", padx=6, pady=6)

        ctk.CTkLabel(chart_card, text="📊 Penjualan 7 Hari Terakhir", font=("Segoe UI Semibold", 14),
                     text_color=COLORS["text_primary"]).pack(padx=20, pady=(16, 8), anchor="w")

        chart_area = ctk.CTkFrame(chart_card, fg_color="transparent", height=200)
        chart_area.pack(fill="x", padx=20, pady=(0, 16))
        chart_area.pack_propagate(False)

        daily = stats["daily_sales"]
        max_val = max((d["total"] for d in daily), default=1)
        if max_val == 0:
            max_val = 1

        bars_frame = ctk.CTkFrame(chart_area, fg_color="transparent")
        bars_frame.pack(fill="both", expand=True)

        for i, d in enumerate(daily):
            bars_frame.grid_columnconfigure(i, weight=1)
            col = ctk.CTkFrame(bars_frame, fg_color="transparent")
            col.grid(row=0, column=i, sticky="nsew", padx=4)

            # Value label
            val_text = f"{d['total']/1000:.0f}k" if d['total'] >= 1000 else str(int(d['total']))
            ctk.CTkLabel(col, text=val_text, font=("Segoe UI", 9),
                         text_color=COLORS["text_muted"]).pack(side="top", pady=(0, 2))

            # Bar container
            bar_container = ctk.CTkFrame(col, fg_color=COLORS["bg_entry"], corner_radius=6)
            bar_container.pack(fill="x", expand=True, padx=6)

            bar_height = max(int((d["total"] / max_val) * 140), 4)
            bar = ctk.CTkFrame(bar_container, fg_color=COLORS["accent"], corner_radius=6, height=bar_height)
            bar.pack(side="bottom", fill="x")

            # Day label
            day_name = datetime.strptime(d["date"], "%Y-%m-%d").strftime("%a")
            ctk.CTkLabel(col, text=day_name, font=("Segoe UI", 10),
                         text_color=COLORS["text_secondary"]).pack(side="bottom", pady=(4, 0))

        # Top products
        top_card = ctk.CTkFrame(charts_row, fg_color=COLORS["bg_card"], corner_radius=16)
        top_card.grid(row=0, column=1, sticky="nsew", padx=6, pady=6)

        ctk.CTkLabel(top_card, text="🏆 Produk Terlaris", font=("Segoe UI Semibold", 14),
                     text_color=COLORS["text_primary"]).pack(padx=20, pady=(16, 12), anchor="w")

        for i, p in enumerate(stats["top_products"]):
            row = ctk.CTkFrame(top_card, fg_color="transparent", height=40)
            row.pack(fill="x", padx=16, pady=2)
            row.pack_propagate(False)

            rank_colors = [COLORS["warning"], "#c0c0c0", "#cd7f32", COLORS["text_muted"], COLORS["text_muted"]]
            ctk.CTkLabel(row, text=f"#{i+1}", font=("Segoe UI Bold", 12),
                         text_color=rank_colors[i] if i < 5 else COLORS["text_muted"],
                         width=30).pack(side="left")
            ctk.CTkLabel(row, text=p["product_name"], font=("Segoe UI", 12),
                         text_color=COLORS["text_primary"]).pack(side="left", padx=(4, 0))
            ctk.CTkLabel(row, text=f'{p["total_qty"]} terjual', font=("Segoe UI", 11),
                         text_color=COLORS["text_secondary"]).pack(side="right")

        if not stats["top_products"]:
            ctk.CTkLabel(top_card, text="Belum ada data", font=("Segoe UI", 12),
                         text_color=COLORS["text_muted"]).pack(pady=20)

        # ── Recent Transactions ────────────────────────────────
        recent_card = ctk.CTkFrame(content, fg_color=COLORS["bg_card"], corner_radius=16)
        recent_card.pack(fill="x", padx=30, pady=(16, 24))

        ctk.CTkLabel(recent_card, text="📜 Transaksi Terakhir", font=("Segoe UI Semibold", 14),
                     text_color=COLORS["text_primary"]).pack(padx=20, pady=(16, 8), anchor="w")

        # Table header
        th = ctk.CTkFrame(recent_card, fg_color=COLORS["header_bg"], corner_radius=8, height=36)
        th.pack(fill="x", padx=16, pady=(0, 4))
        th.pack_propagate(False)

        cols = [("Invoice", 180), ("Kasir", 140), ("Total", 140), ("Metode", 100), ("Status", 100), ("Waktu", 160)]
        for name, w in cols:
            ctk.CTkLabel(th, text=name, font=("Segoe UI Semibold", 11), text_color=COLORS["text_secondary"],
                         width=w, anchor="w").pack(side="left", padx=8)

        for i, tx in enumerate(stats["recent_transactions"]):
            bg = COLORS["bg_card"] if i % 2 == 0 else COLORS["table_row_alt"]
            row = ctk.CTkFrame(recent_card, fg_color=bg, height=34, corner_radius=4)
            row.pack(fill="x", padx=16, pady=1)
            row.pack_propagate(False)

            status_color = COLORS["success"] if tx["status"] == "completed" else COLORS["danger"]
            status_text = "Selesai" if tx["status"] == "completed" else "Void"

            values = [
                (tx["invoice_number"], 180, COLORS["accent"]),
                (tx.get("cashier_name", "-"), 140, COLORS["text_primary"]),
                (format_rupiah(tx["final_amount"]), 140, COLORS["text_primary"]),
                (tx["payment_method"].upper(), 100, COLORS["text_secondary"]),
                (status_text, 100, status_color),
                (tx["created_at"][:16] if tx["created_at"] else "-", 160, COLORS["text_secondary"]),
            ]
            for val, w, color in values:
                ctk.CTkLabel(row, text=str(val), font=("Segoe UI", 11), text_color=color,
                             width=w, anchor="w").pack(side="left", padx=8)

        # ── Low stock alert ────────────────────────────────────
        low_stock = db.get_low_stock_products()
        if low_stock:
            alert_card = ctk.CTkFrame(content, fg_color=COLORS["bg_card"], corner_radius=16,
                                      border_color=COLORS["danger"], border_width=1)
            alert_card.pack(fill="x", padx=30, pady=(0, 24))

            ctk.CTkLabel(alert_card, text="⚠ Peringatan Stok Menipis", font=("Segoe UI Semibold", 14),
                         text_color=COLORS["danger"]).pack(padx=20, pady=(16, 8), anchor="w")

            for p in low_stock[:8]:
                row = ctk.CTkFrame(alert_card, fg_color="transparent", height=32)
                row.pack(fill="x", padx=20, pady=1)
                row.pack_propagate(False)

                ctk.CTkLabel(row, text=f"• {p['name']}", font=("Segoe UI", 12),
                             text_color=COLORS["text_primary"]).pack(side="left")
                stock_color = COLORS["danger"] if p["stock"] <= 0 else COLORS["warning"]
                ctk.CTkLabel(row, text=f"Stok: {p['stock']} / Min: {p['min_stock']}",
                             font=("Segoe UI", 11), text_color=stock_color).pack(side="right")

            ctk.CTkFrame(alert_card, fg_color="transparent", height=12).pack()
