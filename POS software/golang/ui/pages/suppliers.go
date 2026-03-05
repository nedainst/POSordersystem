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

type SuppliersPage struct {
	Content fyne.CanvasObject

	supTable  *widget.Table
	suppliers []database.Supplier
}

func NewSuppliersPage() *SuppliersPage {
	p := &SuppliersPage{}
	p.build()
	return p
}

func (p *SuppliersPage) build() {
	title := canvas.NewText("🚚 Manajemen Supplier", postheme.TextPrimary)
	title.TextSize = 22
	title.TextStyle = fyne.TextStyle{Bold: true}

	addBtn := widget.NewButton("➕ Tambah Supplier", func() { p.showDialog(nil) })
	addBtn.Importance = widget.HighImportance

	cols := []string{"No", "Nama", "Kontak", "Telepon", "Email", "Alamat", "Aksi"}
	p.supTable = widget.NewTable(
		func() (int, int) { return len(p.suppliers) + 1, len(cols) },
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
			if idx >= len(p.suppliers) {
				return
			}
			sup := p.suppliers[idx]
			txt.TextStyle = fyne.TextStyle{}
			txt.Color = postheme.TextPrimary
			txt.TextSize = 12

			switch id.Col {
			case 0:
				txt.Text = fmt.Sprintf("%d", idx+1)
			case 1:
				txt.Text = sup.Name
			case 2:
				txt.Text = sup.ContactPerson
			case 3:
				txt.Text = sup.Phone
			case 4:
				txt.Text = sup.Email
			case 5:
				txt.Text = sup.Address
			case 6:
				txt.Hide()
				btns.Show()
				editBtn := btns.Objects[0].(*widget.Button)
				delBtn := btns.Objects[1].(*widget.Button)
				editBtn.SetText("✏️")
				editBtn.Importance = widget.MediumImportance
				delBtn.SetText("🗑️")
				delBtn.Importance = widget.DangerImportance
				sidx := idx
				editBtn.OnTapped = func() {
					if sidx < len(p.suppliers) {
						s := p.suppliers[sidx]
						p.showDialog(&s)
					}
				}
				delBtn.OnTapped = func() {
					if sidx < len(p.suppliers) {
						win := fyne.CurrentApp().Driver().AllWindows()[0]
						dialog.ShowConfirm("Hapus", "Hapus supplier ini?", func(ok bool) {
							if ok {
								database.DeleteSupplier(p.suppliers[sidx].ID)
								p.Refresh()
							}
						}, win)
					}
				}
			}
			txt.Refresh()
		},
	)

	for i, w := range []float32{40, 150, 120, 120, 150, 180, 80} {
		p.supTable.SetColumnWidth(i, w)
	}

	tableBg := canvas.NewRectangle(postheme.BgCard)
	tableCard := container.NewMax(tableBg, container.NewPadded(p.supTable))

	p.Content = container.NewBorder(
		container.NewVBox(title, widget.NewSeparator(), container.NewHBox(addBtn), widget.NewSeparator()),
		nil, nil, nil,
		tableCard,
	)
}

func (p *SuppliersPage) Refresh() {
	p.suppliers = database.GetAllSuppliers()
	p.supTable.Refresh()
}

func (p *SuppliersPage) showDialog(existing *database.Supplier) {
	win := fyne.CurrentApp().Driver().AllWindows()[0]

	nameEntry := widget.NewEntry()
	nameEntry.SetPlaceHolder("Nama supplier")
	contactEntry := widget.NewEntry()
	contactEntry.SetPlaceHolder("Nama kontak")
	phoneEntry := widget.NewEntry()
	phoneEntry.SetPlaceHolder("Telepon")
	emailEntry := widget.NewEntry()
	emailEntry.SetPlaceHolder("Email")
	addressEntry := widget.NewMultiLineEntry()
	addressEntry.SetPlaceHolder("Alamat")
	addressEntry.SetMinRowsVisible(3)

	if existing != nil {
		nameEntry.SetText(existing.Name)
		contactEntry.SetText(existing.ContactPerson)
		phoneEntry.SetText(existing.Phone)
		emailEntry.SetText(existing.Email)
		addressEntry.SetText(existing.Address)
	}

	form := widget.NewForm(
		widget.NewFormItem("Nama", nameEntry),
		widget.NewFormItem("Kontak", contactEntry),
		widget.NewFormItem("Telepon", phoneEntry),
		widget.NewFormItem("Email", emailEntry),
		widget.NewFormItem("Alamat", addressEntry),
	)

	dlgTitle := "Tambah Supplier"
	if existing != nil {
		dlgTitle = "Edit Supplier"
	}

	d := dialog.NewCustomConfirm(dlgTitle, "Simpan", "Batal", form, func(ok bool) {
		if !ok {
			return
		}
		if nameEntry.Text == "" {
			dialog.ShowInformation("Error", "Nama harus diisi", win)
			return
		}
		if existing != nil {
			database.UpdateSupplier(existing.ID, nameEntry.Text, contactEntry.Text, phoneEntry.Text, emailEntry.Text, addressEntry.Text)
		} else {
			database.AddSupplier(nameEntry.Text, contactEntry.Text, phoneEntry.Text, emailEntry.Text, addressEntry.Text)
		}
		p.Refresh()
	}, win)
	d.Resize(fyne.NewSize(450, 400))
	d.Show()
}
