"""
User Management Page
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


class UsersPage:
    def __init__(self, parent, app):
        self.parent = parent
        self.app = app
        self._build()

    def _build(self):
        header = ctk.CTkFrame(self.parent, fg_color=COLORS["header_bg"], height=64, corner_radius=0)
        header.pack(fill="x")
        header.pack_propagate(False)

        ctk.CTkLabel(header, text="👤  Manajemen Pengguna", font=("Segoe UI Bold", 20),
                     text_color=COLORS["text_primary"]).pack(side="left", padx=24, pady=16)

        ctk.CTkButton(header, text="+ Tambah User", width=140, height=36,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI Semibold", 12), corner_radius=8,
                      command=self._show_add_dialog).pack(side="right", padx=24)

        self.scroll = ctk.CTkScrollableFrame(self.parent, fg_color=COLORS["bg_dark"],
                                              scrollbar_button_color="#3a3a50",
                                              scrollbar_button_hover_color=COLORS["accent"])
        self.scroll.pack(fill="both", expand=True, padx=20, pady=12)
        self._load_users()

    def _load_users(self):
        for w in self.scroll.winfo_children():
            w.destroy()

        users = db.get_all_users()

        # Header
        th = ctk.CTkFrame(self.scroll, fg_color=COLORS["header_bg"], corner_radius=8, height=40)
        th.pack(fill="x", pady=(0, 4))
        th.pack_propagate(False)

        cols = [("ID", 40), ("Username", 140), ("Nama Lengkap", 200), ("Role", 100),
                ("Status", 80), ("Login Terakhir", 160), ("Aksi", 160)]
        for name, w in cols:
            ctk.CTkLabel(th, text=name, font=("Segoe UI Semibold", 11),
                         text_color=COLORS["text_secondary"], width=w, anchor="w").pack(side="left", padx=6)

        for i, u in enumerate(users):
            bg = COLORS["bg_card"] if i % 2 == 0 else COLORS["table_row_alt"]
            row = ctk.CTkFrame(self.scroll, fg_color=bg, height=42, corner_radius=4)
            row.pack(fill="x", pady=1)
            row.pack_propagate(False)

            status = "Aktif" if u["is_active"] else "Nonaktif"
            status_color = COLORS["success"] if u["is_active"] else COLORS["danger"]
            role_color = COLORS["accent"] if u["role"] == "admin" else COLORS["text_secondary"]

            values = [
                (str(u["id"]), 40, COLORS["text_muted"]),
                (u["username"], 140, COLORS["text_primary"]),
                (u["full_name"], 200, COLORS["text_primary"]),
                (u["role"].capitalize(), 100, role_color),
                (status, 80, status_color),
                (u["last_login"][:16] if u.get("last_login") else "Belum pernah", 160, COLORS["text_muted"]),
            ]
            for val, w, color in values:
                ctk.CTkLabel(row, text=val, font=("Segoe UI", 11), text_color=color,
                             width=w, anchor="w").pack(side="left", padx=6)

            act = ctk.CTkFrame(row, fg_color="transparent", width=160)
            act.pack(side="left", padx=4)

            ctk.CTkButton(act, text="✏ Edit", width=60, height=28, corner_radius=6,
                          fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                          font=("Segoe UI", 10),
                          command=lambda user=u: self._show_edit_dialog(user)).pack(side="left", padx=2)

            if u["id"] != 1:  # Can't delete main admin
                ctk.CTkButton(act, text="🗑", width=32, height=28, corner_radius=6,
                              fg_color=COLORS["danger"], hover_color=COLORS["danger_hover"],
                              font=("Segoe UI", 10),
                              command=lambda user=u: self._delete_user(user)).pack(side="left", padx=2)

    def _show_add_dialog(self):
        self._user_dialog("Tambah User Baru")

    def _show_edit_dialog(self, user):
        self._user_dialog("Edit User", user)

    def _user_dialog(self, title, user=None):
        dialog = ctk.CTkToplevel(self.parent)
        dialog.title(title)
        dialog.geometry("430x500")
        dialog.resizable(False, False)
        dialog.configure(fg_color=COLORS["bg_dark"])
        dialog.transient(self.parent)
        dialog.grab_set()

        dialog.update_idletasks()
        x = (dialog.winfo_screenwidth() - 430) // 2
        y = (dialog.winfo_screenheight() - 500) // 2
        dialog.geometry(f"430x500+{x}+{y}")

        card = ctk.CTkFrame(dialog, fg_color=COLORS["bg_card"], corner_radius=16)
        card.pack(fill="both", expand=True, padx=16, pady=16)

        ctk.CTkLabel(card, text=title, font=("Segoe UI Bold", 18),
                     text_color=COLORS["text_primary"]).pack(pady=(16, 12))

        # Username
        ctk.CTkLabel(card, text="Username:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(anchor="w", padx=20, pady=(8, 2))
        username_entry = ctk.CTkEntry(card, height=36, corner_radius=8,
                                      fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                      text_color=COLORS["text_primary"], font=("Segoe UI", 12))
        username_entry.pack(fill="x", padx=20)
        if user:
            username_entry.insert(0, user["username"])
            username_entry.configure(state="disabled")

        # Full name
        ctk.CTkLabel(card, text="Nama Lengkap:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(anchor="w", padx=20, pady=(12, 2))
        name_entry = ctk.CTkEntry(card, height=36, corner_radius=8,
                                  fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                  text_color=COLORS["text_primary"], font=("Segoe UI", 12))
        name_entry.pack(fill="x", padx=20)
        if user:
            name_entry.insert(0, user["full_name"])

        # Password
        pwd_label = "Password Baru (kosongkan jika tidak diubah):" if user else "Password:"
        ctk.CTkLabel(card, text=pwd_label, font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(anchor="w", padx=20, pady=(12, 2))
        pwd_entry = ctk.CTkEntry(card, height=36, corner_radius=8, show="●",
                                 fg_color=COLORS["bg_entry"], border_color=COLORS["border"],
                                 text_color=COLORS["text_primary"], font=("Segoe UI", 12))
        pwd_entry.pack(fill="x", padx=20)

        # Role
        ctk.CTkLabel(card, text="Role:", font=("Segoe UI", 12),
                     text_color=COLORS["text_secondary"]).pack(anchor="w", padx=20, pady=(12, 2))
        role_var = ctk.CTkSegmentedButton(card, values=["admin", "cashier"], height=34,
                                          fg_color=COLORS["bg_entry"],
                                          selected_color=COLORS["accent"],
                                          selected_hover_color=COLORS["accent_hover"],
                                          unselected_color=COLORS["bg_entry"],
                                          unselected_hover_color=COLORS["bg_card_hover"],
                                          text_color=COLORS["text_secondary"])
        role_var.pack(fill="x", padx=20)
        role_var.set(user["role"] if user else "cashier")

        # Active
        active_var = ctk.BooleanVar(value=user["is_active"] if user else True)
        ctk.CTkCheckBox(card, text="Aktif", variable=active_var,
                        fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                        text_color=COLORS["text_primary"], font=("Segoe UI", 12)).pack(padx=20, pady=(12, 0), anchor="w")

        def save():
            if not user:
                username = username_entry.get().strip()
                password = pwd_entry.get().strip()
                full_name = name_entry.get().strip()
                if not username or not password or not full_name:
                    messagebox.showwarning("Error", "Semua field harus diisi!")
                    return
                ok, msg = db.add_user(username, password, full_name, role_var.get())
                if ok:
                    dialog.destroy()
                    self._load_users()
                    messagebox.showinfo("Sukses", msg)
                else:
                    messagebox.showerror("Error", msg)
            else:
                full_name = name_entry.get().strip()
                if not full_name:
                    messagebox.showwarning("Error", "Nama harus diisi!")
                    return
                password = pwd_entry.get().strip() or None
                db.update_user(user["id"], full_name, role_var.get(), int(active_var.get()), password)
                dialog.destroy()
                self._load_users()
                messagebox.showinfo("Sukses", "User berhasil diupdate!")

        ctk.CTkButton(card, text="💾 Simpan", height=40, corner_radius=10,
                      fg_color=COLORS["accent"], hover_color=COLORS["accent_hover"],
                      font=("Segoe UI Semibold", 14), command=save).pack(fill="x", padx=20, pady=(20, 8))

    def _delete_user(self, user):
        if messagebox.askyesno("Konfirmasi", f'Hapus user "{user["username"]}"?'):
            db.delete_user(user["id"])
            self._load_users()
