"""
POS System - Modern Point of Sale Application
Main application entry point with login and navigation
"""
import customtkinter as ctk
from tkinter import messagebox
import database as db
from datetime import datetime

# ── Colour Palette ──────────────────────────────────────────────
COLORS = {
    "bg_dark": "#0f0f14",
    "bg_sidebar": "#16161d",
    "bg_card": "#1e1e2e",
    "bg_card_hover": "#262638",
    "bg_entry": "#252535",
    "accent": "#6c63ff",
    "accent_hover": "#7b73ff",
    "accent_light": "#8a82ff",
    "success": "#2ecc71",
    "success_hover": "#27ae60",
    "warning": "#f39c12",
    "danger": "#e74c3c",
    "danger_hover": "#c0392b",
    "text_primary": "#e8e8f0",
    "text_secondary": "#a9a9c0",
    "text_muted": "#6c6c88",
    "border": "#2a2a3d",
    "scrollbar": "#3a3a50",
    "header_bg": "#1a1a28",
    "table_row_alt": "#1b1b2b",
    "tab_active": "#6c63ff",
    "tab_inactive": "#252535",
}

# Icon unicode characters (for buttons / sidebar)
ICONS = {
    "dashboard": "\u2302",    # ⌂
    "pos": "\u2637",          # ☷
    "products": "\u2610",     # ☐
    "inventory": "\u2692",    # ⚒
    "history": "\u2398",      # ⎘
    "reports": "\u2191",      # ↑
    "suppliers": "\u2709",    # ✉
    "expenses": "\u2696",     # ⚖
    "users": "\u263A",        # ☺
    "settings": "\u2699",     # ⚙
    "logout": "\u2BBD",       # ⮽
    "search": "\u2315",       # ⌕
    "cart": "\U0001F6D2",     # 🛒
    "plus": "+",
    "minus": "-",
    "delete": "\u2716",       # ✖
    "edit": "\u270E",         # ✎
    "save": "\u2714",         # ✔
    "refresh": "\u21BB",      # ↻
    "print": "\u2399",        # ⎙
    "money": "\u20B9",        # ₹
}


class ModernScrollableFrame(ctk.CTkScrollableFrame):
    """Reusable scrollable frame with dark styling"""
    def __init__(self, master, **kwargs):
        super().__init__(
            master,
            fg_color=COLORS["bg_dark"],
            scrollbar_button_color=COLORS["scrollbar"],
            scrollbar_button_hover_color=COLORS["accent"],
            **kwargs
        )


class InfoCard(ctk.CTkFrame):
    """Dashboard info card widget"""
    def __init__(self, master, title, value, subtitle="", accent=COLORS["accent"], icon="", **kwargs):
        super().__init__(master, fg_color=COLORS["bg_card"], corner_radius=16, **kwargs)

        self.configure(height=130)
        self.pack_propagate(False)

        inner = ctk.CTkFrame(self, fg_color="transparent")
        inner.pack(fill="both", expand=True, padx=20, pady=16)

        # top row: icon + title
        top = ctk.CTkFrame(inner, fg_color="transparent")
        top.pack(fill="x")
        if icon:
            ctk.CTkLabel(top, text=icon, font=("Segoe UI Emoji", 22), text_color=accent).pack(side="left", padx=(0, 8))
        ctk.CTkLabel(top, text=title, font=("Segoe UI", 13), text_color=COLORS["text_secondary"]).pack(side="left")

        ctk.CTkLabel(inner, text=str(value), font=("Segoe UI Semibold", 28), text_color=COLORS["text_primary"],
                     anchor="w").pack(fill="x", pady=(8, 2))
        if subtitle:
            ctk.CTkLabel(inner, text=subtitle, font=("Segoe UI", 11), text_color=COLORS["text_muted"],
                         anchor="w").pack(fill="x")

        # accent bar at top
        bar = ctk.CTkFrame(self, fg_color=accent, height=3, corner_radius=2)
        bar.place(relx=0.1, rely=0, relwidth=0.8, height=3)


# ═══════════════════════════════════════════════════════════════
#  LOGIN WINDOW
# ═══════════════════════════════════════════════════════════════

class LoginWindow(ctk.CTkToplevel):
    def __init__(self, app):
        super().__init__()
        self.app = app
        self.title("POS System - Login")
        self.geometry("460x580")
        self.resizable(False, False)
        self.configure(fg_color=COLORS["bg_dark"])
        self.protocol("WM_DELETE_WINDOW", self._on_close)

        # Center the login window
        self.update_idletasks()
        w, h = 460, 580
        x = (self.winfo_screenwidth() - w) // 2
        y = (self.winfo_screenheight() - h) // 2
        self.geometry(f"{w}x{h}+{x}+{y}")

        self._build_ui()
        self.after(100, self.lift)
        self.after(100, self.focus_force)

    def _on_close(self):
        self.app.destroy()

    def _build_ui(self):
        # Card
        card = ctk.CTkFrame(self, fg_color=COLORS["bg_card"], corner_radius=20, width=380, height=480)
        card.place(relx=0.5, rely=0.5, anchor="center")
        card.pack_propagate(False)

        inner = ctk.CTkFrame(card, fg_color="transparent")
        inner.pack(fill="both", expand=True, padx=40, pady=36)

        # Logo circle
        logo_frame = ctk.CTkFrame(inner, fg_color=COLORS["accent"], width=72, height=72, corner_radius=36)
        logo_frame.pack(pady=(0, 6))
        logo_frame.pack_propagate(False)
        ctk.CTkLabel(logo_frame, text="POS", font=("Segoe UI Black", 20), text_color="white").place(relx=0.5, rely=0.5, anchor="center")

        ctk.CTkLabel(inner, text="Point of Sale", font=("Segoe UI Semibold", 22), text_color=COLORS["text_primary"]).pack()
        ctk.CTkLabel(inner, text="Silakan login untuk melanjutkan", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(pady=(2, 24))

        # Username
        ctk.CTkLabel(inner, text="Username", font=("Segoe UI", 12), text_color=COLORS["text_secondary"],
                     anchor="w").pack(fill="x")
        self.username_entry = ctk.CTkEntry(inner, height=42, corner_radius=10,
                                           fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                           text_color=COLORS["text_primary"], placeholder_text="Masukkan username",
                                           font=("Segoe UI", 13))
        self.username_entry.pack(fill="x", pady=(4, 14))
        self.username_entry.insert(0, "admin")

        # Password
        ctk.CTkLabel(inner, text="Password", font=("Segoe UI", 12), text_color=COLORS["text_secondary"],
                     anchor="w").pack(fill="x")
        self.password_entry = ctk.CTkEntry(inner, height=42, corner_radius=10, show="●",
                                           fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                           text_color=COLORS["text_primary"], placeholder_text="Masukkan password",
                                           font=("Segoe UI", 13))
        self.password_entry.pack(fill="x", pady=(4, 6))
        self.password_entry.insert(0, "admin123")

        self.error_label = ctk.CTkLabel(inner, text="", font=("Segoe UI", 11), text_color=COLORS["danger"])
        self.error_label.pack(fill="x", pady=(0, 6))

        # Login button
        self.login_btn = ctk.CTkButton(inner, text="LOGIN", height=44, corner_radius=10,
                                       fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                                       font=("Segoe UI Semibold", 15), command=self._login)
        self.login_btn.pack(fill="x", pady=(4, 10))

        ctk.CTkLabel(inner, text="Default: admin / admin123", font=("Segoe UI", 10),
                     text_color=COLORS["text_muted"]).pack()

        self.password_entry.bind("<Return>", lambda e: self._login())
        self.username_entry.bind("<Return>", lambda e: self.password_entry.focus())

    def _login(self):
        username = self.username_entry.get().strip()
        password = self.password_entry.get().strip()
        if not username or not password:
            self.error_label.configure(text="Username dan password harus diisi!")
            return

        user = db.authenticate_user(username, password)
        if user:
            self.app.current_user = user
            self.destroy()
            self.app.after(100, self.app._show_main_ui)
        else:
            self.error_label.configure(text="Username atau password salah!")


# ═══════════════════════════════════════════════════════════════
#  MAIN APPLICATION
# ═══════════════════════════════════════════════════════════════

class POSApp(ctk.CTk):
    def __init__(self):
        super().__init__()
        self.title("POS System - Point of Sale")
        self.geometry("1366x768")
        self.minsize(1100, 650)
        self.configure(fg_color=COLORS["bg_dark"])
        self.current_user = None
        self.active_page = None
        self.sidebar_buttons = {}
        self._pages = {}

        # Start maximized
        self.state("zoomed")

        # Hide root while login is shown
        self.withdraw()
        self.after(200, self._show_login)

    def _show_login(self):
        self.login_window = LoginWindow(self)

    def _show_main_ui(self):
        self.deiconify()
        self._build_sidebar()
        self._build_content_area()
        self._navigate("dashboard")

    # ── Sidebar ──────────────────────────────────────────────────
    def _build_sidebar(self):
        self.sidebar = ctk.CTkFrame(self, width=240, fg_color=COLORS["bg_sidebar"], corner_radius=0)
        self.sidebar.pack(side="left", fill="y")
        self.sidebar.pack_propagate(False)

        # Brand header
        brand = ctk.CTkFrame(self.sidebar, fg_color="transparent", height=80)
        brand.pack(fill="x", pady=(10, 0))
        brand.pack_propagate(False)

        brand_inner = ctk.CTkFrame(brand, fg_color="transparent")
        brand_inner.place(relx=0.5, rely=0.5, anchor="center")

        logo = ctk.CTkFrame(brand_inner, fg_color=COLORS["accent"], width=42, height=42, corner_radius=12)
        logo.pack(side="left", padx=(0, 10))
        logo.pack_propagate(False)
        ctk.CTkLabel(logo, text="P", font=("Segoe UI Black", 18), text_color="white").place(relx=0.5, rely=0.5, anchor="center")

        title_frame = ctk.CTkFrame(brand_inner, fg_color="transparent")
        title_frame.pack(side="left")
        ctk.CTkLabel(title_frame, text="POS System", font=("Segoe UI Bold", 16),
                     text_color=COLORS["text_primary"]).pack(anchor="w")

        settings = db.get_all_settings()
        store_name = settings.get("store_name", "Toko Saya")
        ctk.CTkLabel(title_frame, text=store_name, font=("Segoe UI", 10),
                     text_color=COLORS["text_muted"]).pack(anchor="w")

        # Separator
        ctk.CTkFrame(self.sidebar, fg_color=COLORS["border"], height=1).pack(fill="x", padx=20, pady=(10, 10))

        # User info
        user_frame = ctk.CTkFrame(self.sidebar, fg_color=COLORS["bg_card"], corner_radius=12, height=56)
        user_frame.pack(fill="x", padx=14, pady=(0, 10))
        user_frame.pack_propagate(False)

        avatar = ctk.CTkFrame(user_frame, fg_color=COLORS["accent"], width=34, height=34, corner_radius=17)
        avatar.place(x=12, rely=0.5, anchor="w")
        avatar.pack_propagate(False)
        initial = self.current_user["full_name"][0].upper() if self.current_user else "?"
        ctk.CTkLabel(avatar, text=initial, font=("Segoe UI Bold", 14), text_color="white").place(relx=0.5, rely=0.5, anchor="center")

        ctk.CTkLabel(user_frame, text=self.current_user["full_name"], font=("Segoe UI Semibold", 12),
                     text_color=COLORS["text_primary"]).place(x=54, y=12)
        ctk.CTkLabel(user_frame, text=self.current_user["role"].capitalize(), font=("Segoe UI", 10),
                     text_color=COLORS["text_muted"]).place(x=54, y=32)

        # Separator
        ctk.CTkFrame(self.sidebar, fg_color=COLORS["border"], height=1).pack(fill="x", padx=20, pady=(4, 8))

        # Navigation menu
        menu_label = ctk.CTkLabel(self.sidebar, text="  MENU UTAMA", font=("Segoe UI", 10, "bold"),
                                  text_color=COLORS["text_muted"], anchor="w")
        menu_label.pack(fill="x", padx=20, pady=(4, 4))

        nav_items = [
            ("dashboard", "⌂  Dashboard"),
            ("pos", "🛒  Kasir / POS"),
            ("products", "📦  Produk"),
            ("inventory", "📋  Inventaris & Stok"),
            ("history", "📜  Riwayat Transaksi"),
            ("reports", "📊  Laporan"),
        ]

        for key, label in nav_items:
            self._add_sidebar_btn(key, label)

        # Management section
        ctk.CTkFrame(self.sidebar, fg_color=COLORS["border"], height=1).pack(fill="x", padx=20, pady=(12, 8))
        ctk.CTkLabel(self.sidebar, text="  MANAJEMEN", font=("Segoe UI", 10, "bold"),
                     text_color=COLORS["text_muted"], anchor="w").pack(fill="x", padx=20, pady=(0, 4))

        mgmt_items = [
            ("suppliers", "📇  Supplier"),
            ("expenses", "💰  Pengeluaran"),
            ("users", "👤  Pengguna"),
            ("settings", "⚙  Pengaturan"),
        ]
        for key, label in mgmt_items:
            # Only admin can access management pages
            if self.current_user["role"] == "admin" or key in ("expenses",):
                self._add_sidebar_btn(key, label)

        # Logout at bottom
        spacer = ctk.CTkFrame(self.sidebar, fg_color="transparent")
        spacer.pack(fill="both", expand=True)

        ctk.CTkFrame(self.sidebar, fg_color=COLORS["border"], height=1).pack(fill="x", padx=20, pady=(0, 8))
        logout_btn = ctk.CTkButton(
            self.sidebar, text="🚪  Logout", anchor="w", height=40,
            fg_color="transparent", hover_color=COLORS["danger"],
            text_color=COLORS["text_secondary"], font=("Segoe UI", 13),
            corner_radius=10, command=self._logout
        )
        logout_btn.pack(fill="x", padx=14, pady=(0, 16))

    def _add_sidebar_btn(self, key, label):
        btn = ctk.CTkButton(
            self.sidebar, text=label, anchor="w", height=40,
            fg_color="transparent", hover_color=COLORS["bg_card_hover"],
            text_color=COLORS["text_secondary"], font=("Segoe UI", 13),
            corner_radius=10,
            command=lambda k=key: self._navigate(k)
        )
        btn.pack(fill="x", padx=14, pady=1)
        self.sidebar_buttons[key] = btn

    # ── Content area ─────────────────────────────────────────────
    def _build_content_area(self):
        self.content = ctk.CTkFrame(self, fg_color=COLORS["bg_dark"], corner_radius=0)
        self.content.pack(side="right", fill="both", expand=True)

    def _navigate(self, page_name):
        # Update sidebar highlight
        for key, btn in self.sidebar_buttons.items():
            if key == page_name:
                btn.configure(fg_color=COLORS["accent"], text_color="white")
            else:
                btn.configure(fg_color="transparent", text_color=COLORS["text_secondary"])

        # Clear content
        for w in self.content.winfo_children():
            w.destroy()

        self.active_page = page_name

        # Import and show page
        if page_name == "dashboard":
            from pages.dashboard import DashboardPage
            DashboardPage(self.content, self)
        elif page_name == "pos":
            from pages.pos_cashier import POSPage
            POSPage(self.content, self)
        elif page_name == "products":
            from pages.products import ProductsPage
            ProductsPage(self.content, self)
        elif page_name == "inventory":
            from pages.inventory import InventoryPage
            InventoryPage(self.content, self)
        elif page_name == "history":
            from pages.history import HistoryPage
            HistoryPage(self.content, self)
        elif page_name == "reports":
            from pages.reports import ReportsPage
            ReportsPage(self.content, self)
        elif page_name == "suppliers":
            from pages.suppliers import SuppliersPage
            SuppliersPage(self.content, self)
        elif page_name == "expenses":
            from pages.expenses import ExpensesPage
            ExpensesPage(self.content, self)
        elif page_name == "users":
            from pages.users import UsersPage
            UsersPage(self.content, self)
        elif page_name == "settings":
            from pages.settings_page import SettingsPage
            SettingsPage(self.content, self)

    def _logout(self):
        if messagebox.askyesno("Logout", "Apakah Anda yakin ingin logout?"):
            self.current_user = None
            for w in self.content.winfo_children():
                w.destroy()
            self.sidebar.destroy()
            self.withdraw()
            self.after(100, self._show_login)


def main():
    # Initialize database
    db.init_database()

    # Configure appearance
    ctk.set_appearance_mode("dark")
    ctk.set_default_color_theme("blue")

    app = POSApp()
    app.mainloop()


if __name__ == "__main__":
    main()
