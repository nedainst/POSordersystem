"""
Reports Page - Sales reports, product reports, profit analysis
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


class ReportsPage:
    def __init__(self, parent, app):
        self.parent = parent
        self.app = app
        self._build()

    def _build(self):
        header = ctk.CTkFrame(self.parent, fg_color=COLORS["header_bg"], height=64, corner_radius=0)
        header.pack(fill="x")
        header.pack_propagate(False)

        ctk.CTkLabel(header, text="📊  Laporan", font=("Segoe UI Bold", 20),
                     text_color=COLORS["text_primary"]).pack(side="left", padx=24, pady=16)

        # Tabs
        self.tab_view = ctk.CTkTabview(self.parent, fg_color=COLORS["bg_dark"],
                                        segmented_button_fg_color=COLORS["bg_card"],
                                        segmented_button_selected_color=COLORS["accent"],
                                        segmented_button_selected_hover_color=COLORS["accent_hover"],
                                        segmented_button_unselected_color=COLORS["bg_card"],
                                        segmented_button_unselected_hover_color=COLORS["bg_card_hover"],
                                        text_color=COLORS["text_secondary"])
        self.tab_view.pack(fill="both", expand=True, padx=16, pady=8)

        self.tab_sales = self.tab_view.add("💰 Laporan Penjualan")
        self.tab_products = self.tab_view.add("📦 Laporan Produk")
        self.tab_profit = self.tab_view.add("📈 Analisis Profit")

        self._build_sales_report()
        self._build_product_report()
        self._build_profit_report()

    def _create_date_filter(self, parent, load_func):
        filter_frame = ctk.CTkFrame(parent, fg_color=COLORS["bg_card"], corner_radius=12, height=54)
        filter_frame.pack(fill="x", padx=8, pady=(8, 8))
        filter_frame.pack_propagate(False)

        inner = ctk.CTkFrame(filter_frame, fg_color="transparent")
        inner.pack(fill="both", expand=True, padx=12, pady=8)

        ctk.CTkLabel(inner, text="Periode:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(side="left")

        start = ctk.CTkEntry(inner, width=110, height=34, corner_radius=8,
                             fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                             text_color=COLORS["text_primary"], font=("Segoe UI", 11))
        start.pack(side="left", padx=(8, 4))
        start.insert(0, (datetime.now() - timedelta(days=30)).strftime("%Y-%m-%d"))

        ctk.CTkLabel(inner, text="―", text_color=COLORS["text_muted"]).pack(side="left", padx=4)

        end = ctk.CTkEntry(inner, width=110, height=34, corner_radius=8,
                           fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                           text_color=COLORS["text_primary"], font=("Segoe UI", 11))
        end.pack(side="left", padx=(4, 8))
        end.insert(0, datetime.now().strftime("%Y-%m-%d"))

        # Quick date buttons
        def set_period(days):
            start.delete(0, "end")
            start.insert(0, (datetime.now() - timedelta(days=days)).strftime("%Y-%m-%d"))
            end.delete(0, "end")
            end.insert(0, datetime.now().strftime("%Y-%m-%d"))
            load_func(start.get(), end.get())

        for label, days in [("7 Hari", 7), ("30 Hari", 30), ("90 Hari", 90)]:
            ctk.CTkButton(inner, text=label, width=70, height=30, corner_radius=6,
                          fg_color=COLORS["bg_entry"], hover_color=COLORS["bg_card_hover"],
                          font=("Segoe UI", 10), text_color=COLORS["text_secondary"],
                          command=lambda d=days: set_period(d)).pack(side="left", padx=2)

        ctk.CTkButton(inner, text="📊 Tampilkan", width=110, height=34,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI Semibold", 12), corner_radius=8,
                      command=lambda: load_func(start.get(), end.get())).pack(side="right")

        return start, end

    # ── Sales Report ─────────────────────────────────────────
    def _build_sales_report(self):
        tab = self.tab_sales
        self.sales_start, self.sales_end = self._create_date_filter(tab, self._load_sales_report)

        self.sales_scroll = ctk.CTkScrollableFrame(tab, fg_color=COLORS["bg_dark"],
                                                    scrollbar_button_color="#3a3a50",
                                                    scrollbar_button_hover_color=COLORS["accent"])
        self.sales_scroll.pack(fill="both", expand=True, padx=8, pady=(0, 8))
        self._load_sales_report(self.sales_start.get(), self.sales_end.get())

    def _load_sales_report(self, start_date, end_date):
        for w in self.sales_scroll.winfo_children():
            w.destroy()

        data = db.get_sales_report(start_date, end_date)

        if not data:
            ctk.CTkLabel(self.sales_scroll, text="Tidak ada data untuk periode ini",
                         font=("Segoe UI", 14), text_color=COLORS["text_muted"]).pack(pady=40)
            return

        # Summary cards
        total_tx = sum(d["num_transactions"] for d in data)
        total_sales = sum(d["final_total"] for d in data)
        total_discount = sum(d["total_discount"] for d in data)
        total_tax = sum(d["total_tax"] for d in data)
        avg_daily = total_sales / len(data) if data else 0

        summary = ctk.CTkFrame(self.sales_scroll, fg_color="transparent")
        summary.pack(fill="x", padx=4, pady=(8, 12))

        cards_data = [
            ("Total Transaksi", str(total_tx), COLORS["accent"]),
            ("Total Penjualan", format_rupiah(total_sales), COLORS["success"]),
            ("Total Diskon", format_rupiah(total_discount), COLORS["warning"]),
            ("Rata-rata/Hari", format_rupiah(avg_daily), COLORS["accent"]),
        ]

        for i, (title, value, color) in enumerate(cards_data):
            card = ctk.CTkFrame(summary, fg_color=COLORS["bg_card"], corner_radius=12)
            card.grid(row=0, column=i, sticky="nsew", padx=4, pady=4)
            summary.grid_columnconfigure(i, weight=1)

            ctk.CTkFrame(card, fg_color=color, height=3, corner_radius=2).place(relx=0.1, y=0, relwidth=0.8)
            ctk.CTkLabel(card, text=title, font=("Segoe UI", 11),
                         text_color=COLORS["text_secondary"]).pack(padx=12, pady=(12, 2))
            ctk.CTkLabel(card, text=value, font=("Segoe UI Bold", 16),
                         text_color=COLORS["text_primary"]).pack(padx=12, pady=(0, 12))

        # Bar chart
        chart_card = ctk.CTkFrame(self.sales_scroll, fg_color=COLORS["bg_card"], corner_radius=12)
        chart_card.pack(fill="x", padx=4, pady=(0, 12))

        ctk.CTkLabel(chart_card, text="📊 Grafik Penjualan Harian", font=("Segoe UI Semibold", 13),
                     text_color=COLORS["text_primary"]).pack(padx=16, pady=(12, 8), anchor="w")

        chart_area = ctk.CTkFrame(chart_card, fg_color="transparent", height=180)
        chart_area.pack(fill="x", padx=16, pady=(0, 16))
        chart_area.pack_propagate(False)

        max_val = max((d["final_total"] for d in data), default=1)
        if max_val == 0:
            max_val = 1

        bars = ctk.CTkFrame(chart_area, fg_color="transparent")
        bars.pack(fill="both", expand=True)

        display_data = data[-14:]  # Show max 14 days
        for i, d in enumerate(display_data):
            bars.grid_columnconfigure(i, weight=1)
            col = ctk.CTkFrame(bars, fg_color="transparent")
            col.grid(row=0, column=i, sticky="nsew", padx=2)

            val_text = f"{d['final_total']/1000:.0f}k" if d['final_total'] >= 1000 else str(int(d['final_total']))
            ctk.CTkLabel(col, text=val_text, font=("Segoe UI", 8),
                         text_color=COLORS["text_muted"]).pack(side="top", pady=(0, 1))

            bar_container = ctk.CTkFrame(col, fg_color=COLORS["bg_entry"], corner_radius=4)
            bar_container.pack(fill="x", expand=True, padx=3)

            bar_h = max(int((d["final_total"] / max_val) * 120), 3)
            bar = ctk.CTkFrame(bar_container, fg_color=COLORS["accent"], corner_radius=4, height=bar_h)
            bar.pack(side="bottom", fill="x")

            day = datetime.strptime(d["date"], "%Y-%m-%d").strftime("%d")
            ctk.CTkLabel(col, text=day, font=("Segoe UI", 9),
                         text_color=COLORS["text_secondary"]).pack(side="bottom")

        # Daily detail table
        th = ctk.CTkFrame(self.sales_scroll, fg_color=COLORS["header_bg"], corner_radius=8, height=38)
        th.pack(fill="x", padx=4, pady=(0, 4))
        th.pack_propagate(False)

        cols = [("Tanggal", 120), ("Jumlah Transaksi", 140), ("Penjualan", 140),
                ("Diskon", 120), ("Pajak", 120), ("Total Bersih", 140)]
        for name, w in cols:
            ctk.CTkLabel(th, text=name, font=("Segoe UI Semibold", 11),
                         text_color=COLORS["text_secondary"], width=w, anchor="w").pack(side="left", padx=6)

        for i, d in enumerate(data):
            bg = COLORS["bg_card"] if i % 2 == 0 else COLORS["table_row_alt"]
            row = ctk.CTkFrame(self.sales_scroll, fg_color=bg, height=34, corner_radius=4)
            row.pack(fill="x", padx=4, pady=1)
            row.pack_propagate(False)

            values = [
                (d["date"], 120, COLORS["text_primary"]),
                (str(d["num_transactions"]), 140, COLORS["text_secondary"]),
                (format_rupiah(d["total_sales"]), 140, COLORS["text_primary"]),
                (format_rupiah(d["total_discount"]), 120, COLORS["warning"]),
                (format_rupiah(d["total_tax"]), 120, COLORS["text_secondary"]),
                (format_rupiah(d["final_total"]), 140, COLORS["accent"]),
            ]
            for val, w, color in values:
                ctk.CTkLabel(row, text=val, font=("Segoe UI", 11), text_color=color,
                             width=w, anchor="w").pack(side="left", padx=6)

    # ── Product Report ───────────────────────────────────────
    def _build_product_report(self):
        tab = self.tab_products
        self.prod_start, self.prod_end = self._create_date_filter(tab, self._load_product_report)

        self.prod_scroll = ctk.CTkScrollableFrame(tab, fg_color=COLORS["bg_dark"],
                                                   scrollbar_button_color="#3a3a50",
                                                   scrollbar_button_hover_color=COLORS["accent"])
        self.prod_scroll.pack(fill="both", expand=True, padx=8, pady=(0, 8))
        self._load_product_report(self.prod_start.get(), self.prod_end.get())

    def _load_product_report(self, start_date, end_date):
        for w in self.prod_scroll.winfo_children():
            w.destroy()

        data = db.get_product_sales_report(start_date, end_date)

        if not data:
            ctk.CTkLabel(self.prod_scroll, text="Tidak ada data untuk periode ini",
                         font=("Segoe UI", 14), text_color=COLORS["text_muted"]).pack(pady=40)
            return

        # Summary
        total_qty = sum(d["total_qty"] for d in data)
        total_sales = sum(d["total_sales"] for d in data)
        total_profit = sum(d["profit"] for d in data)

        summary = ctk.CTkFrame(self.prod_scroll, fg_color="transparent")
        summary.pack(fill="x", padx=4, pady=(8, 12))

        for i, (t, v, c) in enumerate([
            ("Total Terjual", f"{total_qty} item", COLORS["accent"]),
            ("Total Penjualan", format_rupiah(total_sales), COLORS["success"]),
            ("Total Profit", format_rupiah(total_profit), COLORS["warning"]),
        ]):
            card = ctk.CTkFrame(summary, fg_color=COLORS["bg_card"], corner_radius=12)
            card.grid(row=0, column=i, sticky="nsew", padx=4, pady=4)
            summary.grid_columnconfigure(i, weight=1)
            ctk.CTkFrame(card, fg_color=c, height=3, corner_radius=2).place(relx=0.1, y=0, relwidth=0.8)
            ctk.CTkLabel(card, text=t, font=("Segoe UI", 11),
                         text_color=COLORS["text_secondary"]).pack(padx=12, pady=(12, 2))
            ctk.CTkLabel(card, text=v, font=("Segoe UI Bold", 16),
                         text_color=COLORS["text_primary"]).pack(padx=12, pady=(0, 12))

        # Table
        th = ctk.CTkFrame(self.prod_scroll, fg_color=COLORS["header_bg"], corner_radius=8, height=38)
        th.pack(fill="x", padx=4, pady=(0, 4))
        th.pack_propagate(False)

        cols = [("No", 40), ("Produk", 220), ("Qty Terjual", 100), ("Total Penjualan", 140),
                ("Harga Beli", 120), ("Profit", 140)]
        for name, w in cols:
            ctk.CTkLabel(th, text=name, font=("Segoe UI Semibold", 11),
                         text_color=COLORS["text_secondary"], width=w, anchor="w").pack(side="left", padx=6)

        for i, d in enumerate(data):
            bg = COLORS["bg_card"] if i % 2 == 0 else COLORS["table_row_alt"]
            row = ctk.CTkFrame(self.prod_scroll, fg_color=bg, height=34, corner_radius=4)
            row.pack(fill="x", padx=4, pady=1)
            row.pack_propagate(False)

            profit_color = COLORS["success"] if d["profit"] >= 0 else COLORS["danger"]
            values = [
                (str(i + 1), 40, COLORS["text_muted"]),
                (d["product_name"], 220, COLORS["text_primary"]),
                (str(d["total_qty"]), 100, COLORS["text_secondary"]),
                (format_rupiah(d["total_sales"]), 140, COLORS["accent"]),
                (format_rupiah(d["buy_price"]), 120, COLORS["text_secondary"]),
                (format_rupiah(d["profit"]), 140, profit_color),
            ]
            for val, w, color in values:
                ctk.CTkLabel(row, text=val, font=("Segoe UI", 11), text_color=color,
                             width=w, anchor="w").pack(side="left", padx=6)

    # ── Profit Report ────────────────────────────────────────
    def _build_profit_report(self):
        tab = self.tab_profit
        self.profit_start, self.profit_end = self._create_date_filter(tab, self._load_profit_report)

        self.profit_scroll = ctk.CTkScrollableFrame(tab, fg_color=COLORS["bg_dark"],
                                                     scrollbar_button_color="#3a3a50",
                                                     scrollbar_button_hover_color=COLORS["accent"])
        self.profit_scroll.pack(fill="both", expand=True, padx=8, pady=(0, 8))
        self._load_profit_report(self.profit_start.get(), self.profit_end.get())

    def _load_profit_report(self, start_date, end_date):
        for w in self.profit_scroll.winfo_children():
            w.destroy()

        # Get sales and expenses data
        sales_data = db.get_sales_report(start_date, end_date)
        product_data = db.get_product_sales_report(start_date, end_date)
        expenses = db.get_expenses(start_date, end_date)

        total_revenue = sum(d["final_total"] for d in sales_data)
        total_cogs = sum(d["buy_price"] * d["total_qty"] for d in product_data)
        gross_profit = total_revenue - total_cogs
        total_expenses = sum(e["amount"] for e in expenses)
        net_profit = gross_profit - total_expenses

        # Cards
        cards_frame = ctk.CTkFrame(self.profit_scroll, fg_color="transparent")
        cards_frame.pack(fill="x", padx=4, pady=(8, 16))

        cards = [
            ("Pendapatan", format_rupiah(total_revenue), COLORS["accent"], "💰"),
            ("HPP (Harga Pokok)", format_rupiah(total_cogs), COLORS["warning"], "📦"),
            ("Laba Kotor", format_rupiah(gross_profit), COLORS["success"], "📈"),
            ("Biaya Operasional", format_rupiah(total_expenses), COLORS["danger"], "💸"),
            ("Laba Bersih", format_rupiah(net_profit),
             COLORS["success"] if net_profit >= 0 else COLORS["danger"], "🏆"),
        ]

        for i, (title, value, color, icon) in enumerate(cards):
            card = ctk.CTkFrame(cards_frame, fg_color=COLORS["bg_card"], corner_radius=14)
            card.grid(row=0, column=i, sticky="nsew", padx=4, pady=4)
            cards_frame.grid_columnconfigure(i, weight=1)

            ctk.CTkFrame(card, fg_color=color, height=3, corner_radius=2).place(relx=0.05, y=0, relwidth=0.9)

            ctk.CTkLabel(card, text=icon, font=("Segoe UI Emoji", 18)).pack(pady=(12, 2))
            ctk.CTkLabel(card, text=title, font=("Segoe UI", 10),
                         text_color=COLORS["text_secondary"]).pack()
            ctk.CTkLabel(card, text=value, font=("Segoe UI Bold", 14),
                         text_color=COLORS["text_primary"]).pack(pady=(2, 12))

        # Margin info
        margin_frame = ctk.CTkFrame(self.profit_scroll, fg_color=COLORS["bg_card"], corner_radius=12)
        margin_frame.pack(fill="x", padx=4, pady=(0, 12))

        ctk.CTkLabel(margin_frame, text="📊 Margin & Rasio", font=("Segoe UI Semibold", 14),
                     text_color=COLORS["text_primary"]).pack(padx=16, pady=(12, 8), anchor="w")

        if total_revenue > 0:
            gross_margin = (gross_profit / total_revenue) * 100
            net_margin = (net_profit / total_revenue) * 100
        else:
            gross_margin = 0
            net_margin = 0

        metrics = [
            ("Margin Kotor", f"{gross_margin:.1f}%", COLORS["success"] if gross_margin > 0 else COLORS["danger"]),
            ("Margin Bersih", f"{net_margin:.1f}%", COLORS["success"] if net_margin > 0 else COLORS["danger"]),
            ("Total Transaksi", str(sum(d["num_transactions"] for d in sales_data)), COLORS["accent"]),
            ("Jumlah Produk Terjual", str(sum(d["total_qty"] for d in product_data)), COLORS["text_primary"]),
        ]

        for label, val, color in metrics:
            row = ctk.CTkFrame(margin_frame, fg_color="transparent", height=32)
            row.pack(fill="x", padx=20, pady=2)
            row.pack_propagate(False)
            ctk.CTkLabel(row, text=label, font=("Segoe UI", 12),
                         text_color=COLORS["text_secondary"]).pack(side="left")
            ctk.CTkLabel(row, text=val, font=("Segoe UI Bold", 13),
                         text_color=color).pack(side="right")

        ctk.CTkFrame(margin_frame, fg_color="transparent", height=12).pack()

        # Expenses breakdown
        if expenses:
            exp_card = ctk.CTkFrame(self.profit_scroll, fg_color=COLORS["bg_card"], corner_radius=12)
            exp_card.pack(fill="x", padx=4, pady=(0, 12))
            ctk.CTkLabel(exp_card, text="💸 Rincian Pengeluaran", font=("Segoe UI Semibold", 14),
                         text_color=COLORS["text_primary"]).pack(padx=16, pady=(12, 8), anchor="w")

            # Group by category
            exp_by_cat = {}
            for e in expenses:
                cat = e["category"]
                exp_by_cat[cat] = exp_by_cat.get(cat, 0) + e["amount"]

            for cat, amt in sorted(exp_by_cat.items(), key=lambda x: -x[1]):
                row = ctk.CTkFrame(exp_card, fg_color="transparent", height=30)
                row.pack(fill="x", padx=20, pady=1)
                row.pack_propagate(False)
                ctk.CTkLabel(row, text=cat, font=("Segoe UI", 12),
                             text_color=COLORS["text_primary"]).pack(side="left")
                ctk.CTkLabel(row, text=format_rupiah(amt), font=("Segoe UI", 12),
                             text_color=COLORS["danger"]).pack(side="right")

            ctk.CTkFrame(exp_card, fg_color="transparent", height=12).pack()
