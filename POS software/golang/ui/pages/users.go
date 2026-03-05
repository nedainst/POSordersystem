package pages

import (
	"fmt"
	"pos-system/database"
	postheme "pos-system/theme"

	"fyne.io/fyne/v2"
	"fyne.io/fyne/v2/canvas"
	"fyne.io/fyne/v2/container"
	"fyne.io/fyne/v2/dialog"
	"fyne.io/fyne/v2/widget"
)

type UsersPage struct {
	Content fyne.CanvasObject

	userTable *widget.Table
	users     []database.User
}

func NewUsersPage() *UsersPage {
	p := &UsersPage{}
	p.build()
	return p
}

func (p *UsersPage) build() {
	title := canvas.NewText("👥 Manajemen Pengguna", postheme.TextPrimary)
	title.TextSize = 22
	title.TextStyle = fyne.TextStyle{Bold: true}

	addBtn := widget.NewButton("➕ Tambah Pengguna", func() { p.showDialog(nil) })
	addBtn.Importance = widget.HighImportance

	cols := []string{"No", "Username", "Nama Lengkap", "Role", "Status", "Login Terakhir", "Aksi"}
	p.userTable = widget.NewTable(
		func() (int, int) { return len(p.users) + 1, len(cols) },
		func() fyne.CanvasObject {
			return container.NewMax(
				canvas.NewText("text", postheme.TextPrimary),
				container.NewHBox(widget.NewButton("", nil), widget.NewButton("", nil)),
			)
		},
		func(id widget.TableCellID, obj fyne.CanvasObject) {
			stack := obj.(*fyne.Container)
			txt := stack.Objects[0].(*canvas.Text)
			btns := stack.Objects[1].(*fyne.Container)
			btns.Hide()
			txt.Show()

			if id.Row == 0 {
				txt.Text = cols[id.Col]
				txt.TextStyle = fyne.TextStyle{Bold: true}
				txt.Color = postheme.Accent
				txt.TextSize = 12
				txt.Refresh()
				return
			}

			idx := id.Row - 1
			if idx >= len(p.users) {
				return
			}
			u := p.users[idx]
			txt.TextStyle = fyne.TextStyle{}
			txt.Color = postheme.TextPrimary
			txt.TextSize = 12

			switch id.Col {
			case 0:
				txt.Text = fmt.Sprintf("%d", idx+1)
			case 1:
				txt.Text = u.Username
			case 2:
				txt.Text = u.FullName
			case 3:
				if u.Role == "admin" {
					txt.Text = "👑 Admin"
					txt.Color = postheme.Accent
				} else {
					txt.Text = "🛒 Kasir"
				}
			case 4:
				if u.IsActive == 1 {
					txt.Text = "✅ Aktif"
					txt.Color = postheme.Success
				} else {
					txt.Text = "❌ Nonaktif"
					txt.Color = postheme.Danger
				}
			case 5:
				if u.LastLogin.Valid {
					ll := u.LastLogin.String
					if len(ll) > 16 {
						ll = ll[:16]
					}
					txt.Text = ll
				} else {
					txt.Text = "-"
				}
			case 6:
				txt.Hide()
				btns.Show()
				editBtn := btns.Objects[0].(*widget.Button)
				delBtn := btns.Objects[1].(*widget.Button)
				editBtn.SetText("✏️")
				editBtn.Importance = widget.MediumImportance
				delBtn.SetText("🗑️")
				delBtn.Importance = widget.DangerImportance
				uidx := idx
				editBtn.OnTapped = func() {
					if uidx < len(p.users) {
						uu := p.users[uidx]
						p.showDialog(&uu)
					}
				}
				delBtn.OnTapped = func() {
					if uidx < len(p.users) && p.users[uidx].ID != 1 {
						win := fyne.CurrentApp().Driver().AllWindows()[0]
						dialog.ShowConfirm("Hapus", "Hapus user ini?", func(ok bool) {
							if ok {
								database.DeleteUser(p.users[uidx].ID)
								p.Refresh()
							}
						}, win)
					}
				}
			}
			txt.Refresh()
		},
	)

	for i, w := range []float32{40, 110, 150, 100, 90, 130, 80} {
		p.userTable.SetColumnWidth(i, w)
	}

	tableBg := canvas.NewRectangle(postheme.BgCard)
	tableCard := container.NewMax(tableBg, container.NewPadded(p.userTable))

	p.Content = container.NewBorder(
		container.NewVBox(title, widget.NewSeparator(), container.NewHBox(addBtn), widget.NewSeparator()),
		nil, nil, nil,
		tableCard,
	)
}

func (p *UsersPage) Refresh() {
	p.users = database.GetAllUsers()
	p.userTable.Refresh()
}

func (p *UsersPage) showDialog(existing *database.User) {
	win := fyne.CurrentApp().Driver().AllWindows()[0]

	usernameEntry := widget.NewEntry()
	usernameEntry.SetPlaceHolder("Username")
	fullNameEntry := widget.NewEntry()
	fullNameEntry.SetPlaceHolder("Nama lengkap")
	passwordEntry := widget.NewPasswordEntry()
	passwordEntry.SetPlaceHolder("Password")

	roleSelect := widget.NewSelect([]string{"admin", "cashier"}, nil)
	roleSelect.SetSelected("cashier")

	activeCheck := widget.NewCheck("Aktif", nil)
	activeCheck.Checked = true

	if existing != nil {
		usernameEntry.SetText(existing.Username)
		usernameEntry.Disable() // can't change username
		fullNameEntry.SetText(existing.FullName)
		roleSelect.SetSelected(existing.Role)
		activeCheck.Checked = existing.IsActive == 1
		passwordEntry.SetPlaceHolder("Kosongkan jika tidak diubah")
	}

	form := widget.NewForm(
		widget.NewFormItem("Username", usernameEntry),
		widget.NewFormItem("Nama Lengkap", fullNameEntry),
		widget.NewFormItem("Password", passwordEntry),
		widget.NewFormItem("Role", roleSelect),
		widget.NewFormItem("Status", activeCheck),
	)

	dlgTitle := "Tambah Pengguna"
	if existing != nil {
		dlgTitle = "Edit Pengguna"
	}

	d := dialog.NewCustomConfirm(dlgTitle, "Simpan", "Batal", form, func(ok bool) {
		if !ok {
			return
		}
		if existing != nil {
			isActive := 0
			if activeCheck.Checked {
				isActive = 1
			}
			database.UpdateUser(existing.ID, fullNameEntry.Text, roleSelect.Selected, isActive, passwordEntry.Text)
		} else {
			if usernameEntry.Text == "" || passwordEntry.Text == "" || fullNameEntry.Text == "" {
				dialog.ShowInformation("Error", "Semua field harus diisi", win)
				return
			}
			ok, msg := database.AddUser(usernameEntry.Text, passwordEntry.Text, fullNameEntry.Text, roleSelect.Selected)
			if !ok {
				dialog.ShowInformation("Error", msg, win)
				return
			}
		}
		p.Refresh()
	}, win)
	d.Resize(fyne.NewSize(420, 380))
	d.Show()
}
