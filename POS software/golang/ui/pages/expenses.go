package pages

import (
	"fmt"
	"pos-system/database"
	postheme "pos-system/theme"
	"strconv"
	"time"

	"fyne.io/fyne/v2"
	"fyne.io/fyne/v2/canvas"
	"fyne.io/fyne/v2/container"
	"fyne.io/fyne/v2/dialog"
	"fyne.io/fyne/v2/widget"
)

type ExpensesPage struct {
	Content fyne.CanvasObject
	user    *database.User

	startEntry   *widget.Entry
	endEntry     *widget.Entry
	expTable     *widget.Table
	expenses     []database.Expense
	totalLabel   *canvas.Text
}

func NewExpensesPage(user *database.User) *ExpensesPage {
	p := &ExpensesPage{user: user}
	p.build()
	return p
}

func (p *ExpensesPage) build() {
	title := canvas.NewText("💰 Pengeluaran", postheme.TextPrimary)
	title.TextSize = 22
	title.TextStyle = fyne.TextStyle{Bold: true}

	now := time.Now()
	p.startEntry = widget.NewEntry()
	p.startEntry.Text = now.AddDate(0, -1, 0).Format("2006-01-02")
	p.endEntry = widget.NewEntry()
	p.endEntry.Text = now.Format("2006-01-02")

	filterBtn := widget.NewButton("🔍 Filter", func() { p.loadExpenses() })
	filterBtn.Importance = widget.MediumImportance

	addBtn := widget.NewButton("➕ Tambah Pengeluaran", func() { p.showDialog() })
	addBtn.Importance = widget.HighImportance

	p.totalLabel = canvas.NewText("Total: Rp 0", postheme.TextPrimary)
	p.totalLabel.TextSize = 16
	p.totalLabel.TextStyle = fyne.TextStyle{Bold: true}

	toolbar := container.NewBorder(nil, nil,
		container.NewHBox(p.startEntry, p.endEntry, filterBtn),
		container.NewHBox(p.totalLabel, addBtn),
	)

	cols := []string{"No", "Kategori", "Jumlah", "Deskripsi", "Oleh", "Tanggal", "Aksi"}
	p.expTable = widget.NewTable(
		func() (int, int) { return len(p.expenses) + 1, len(cols) },
		func() fyne.CanvasObject {
			return container.NewMax(
				canvas.NewText("text", postheme.TextPrimary),
				widget.NewButton("", nil),
			)
		},
		func(id widget.TableCellID, obj fyne.CanvasObject) {
			stack := obj.(*fyne.Container)
			txt := stack.Objects[0].(*canvas.Text)
			btn := stack.Objects[1].(*widget.Button)
			btn.Hide()
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
			if idx >= len(p.expenses) {
				return
			}
			exp := p.expenses[idx]
			txt.TextStyle = fyne.TextStyle{}
			txt.Color = postheme.TextPrimary
			txt.TextSize = 12

			switch id.Col {
			case 0:
				txt.Text = fmt.Sprintf("%d", idx+1)
			case 1:
				txt.Text = exp.Category
			case 2:
				txt.Text = database.FormatRupiah(exp.Amount)
				txt.Color = postheme.Danger
			case 3:
				txt.Text = exp.Description
			case 4:
				txt.Text = exp.UserName
			case 5:
				if len(exp.CreatedAt) > 16 {
					txt.Text = exp.CreatedAt[:16]
				} else {
					txt.Text = exp.CreatedAt
				}
			case 6:
				txt.Hide()
				btn.Show()
				btn.SetText("🗑️")
				btn.Importance = widget.DangerImportance
				eidx := idx
				btn.OnTapped = func() {
					win := fyne.CurrentApp().Driver().AllWindows()[0]
					dialog.ShowConfirm("Hapus", "Hapus pengeluaran ini?", func(ok bool) {
						if ok && eidx < len(p.expenses) {
							database.DeleteExpense(p.expenses[eidx].ID)
							p.loadExpenses()
						}
					}, win)
				}
			}
			txt.Refresh()
		},
	)

	for i, w := range []float32{40, 120, 120, 200, 100, 130, 60} {
		p.expTable.SetColumnWidth(i, w)
	}

	tableBg := canvas.NewRectangle(postheme.BgCard)
	tableCard := container.NewMax(tableBg, container.NewPadded(p.expTable))

	p.Content = container.NewBorder(
		container.NewVBox(title, widget.NewSeparator(), toolbar, widget.NewSeparator()),
		nil, nil, nil,
		tableCard,
	)
}

func (p *ExpensesPage) Refresh() {
	p.loadExpenses()
}

func (p *ExpensesPage) loadExpenses() {
	p.expenses = database.GetExpenses(p.startEntry.Text, p.endEntry.Text)
	p.expTable.Refresh()

	var total float64
	for _, e := range p.expenses {
		total += e.Amount
	}
	p.totalLabel.Text = "Total: " + database.FormatRupiah(total)
	p.totalLabel.Refresh()
}

func (p *ExpensesPage) showDialog() {
	win := fyne.CurrentApp().Driver().AllWindows()[0]

	catOptions := []string{"Operasional", "Gaji", "Listrik & Air", "Sewa", "Belanja Stok", "Transportasi", "Lainnya"}
	catSelect := widget.NewSelect(catOptions, nil)
	catSelect.SetSelected("Operasional")

	amountEntry := widget.NewEntry()
	amountEntry.SetPlaceHolder("Jumlah (angka)")

	descEntry := widget.NewMultiLineEntry()
	descEntry.SetPlaceHolder("Deskripsi pengeluaran")
	descEntry.SetMinRowsVisible(3)

	form := widget.NewForm(
		widget.NewFormItem("Kategori", catSelect),
		widget.NewFormItem("Jumlah", amountEntry),
		widget.NewFormItem("Deskripsi", descEntry),
	)

	d := dialog.NewCustomConfirm("Tambah Pengeluaran", "Simpan", "Batal", form, func(ok bool) {
		if !ok {
			return
		}
		amount, err := strconv.ParseFloat(amountEntry.Text, 64)
		if err != nil || amount <= 0 {
			dialog.ShowInformation("Error", "Jumlah harus angka positif", win)
			return
		}
		database.AddExpense(catSelect.Selected, amount, descEntry.Text, p.user.ID)
		p.loadExpenses()
	}, win)
	d.Resize(fyne.NewSize(420, 350))
	d.Show()
}
