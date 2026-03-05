package pages

import (
	"fmt"
	"pos-system/database"
	postheme "pos-system/theme"
	"strconv"

	"fyne.io/fyne/v2"
	"fyne.io/fyne/v2/canvas"
	"fyne.io/fyne/v2/container"
	"fyne.io/fyne/v2/dialog"
	"fyne.io/fyne/v2/widget"
)

type InventoryPage struct {
	Content fyne.CanvasObject

	lowStockTable *widget.Table
	movementTable *widget.Table
	lowStockItems []database.Product
	movements     []database.StockMovement
}

func NewInventoryPage() *InventoryPage {
	p := &InventoryPage{}
	p.build()
	return p
}

func (p *InventoryPage) build() {
	title := canvas.NewText("📋 Manajemen Inventori", postheme.TextPrimary)
	title.TextSize = 22
	title.TextStyle = fyne.TextStyle{Bold: true}

	// ── Low stock section ────────────────────────────
	lowStockTitle := canvas.NewText("⚠️ Produk Stok Rendah", postheme.Warning)
	lowStockTitle.TextSize = 16
	lowStockTitle.TextStyle = fyne.TextStyle{Bold: true}

	lowCols := []string{"No", "Produk", "Kategori", "Stok", "Min Stok", "Status", "Aksi"}
	p.lowStockTable = widget.NewTable(
		func() (int, int) { return len(p.lowStockItems) + 1, len(lowCols) },
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
				txt.Text = lowCols[id.Col]
				txt.TextStyle = fyne.TextStyle{Bold: true}
				txt.Color = postheme.Accent
				txt.TextSize = 12
				txt.Refresh()
				return
			}

			idx := id.Row - 1
			if idx >= len(p.lowStockItems) {
				return
			}
			prod := p.lowStockItems[idx]
			txt.TextStyle = fyne.TextStyle{}
			txt.Color = postheme.TextPrimary
			txt.TextSize = 12

			switch id.Col {
			case 0:
				txt.Text = fmt.Sprintf("%d", idx+1)
			case 1:
				txt.Text = prod.Name
			case 2:
				txt.Text = prod.CategoryName
			case 3:
				txt.Text = fmt.Sprintf("%d", prod.Stock)
				txt.Color = postheme.Danger
			case 4:
				txt.Text = fmt.Sprintf("%d", prod.MinStock)
			case 5:
				if prod.Stock == 0 {
					txt.Text = "HABIS"
					txt.Color = postheme.Danger
				} else {
					txt.Text = "RENDAH"
					txt.Color = postheme.Warning
				}
			case 6:
				txt.Hide()
				btn.Show()
				btn.SetText("📥 Stok Masuk")
				btn.Importance = widget.HighImportance
				pidx := idx
				btn.OnTapped = func() {
					if pidx < len(p.lowStockItems) {
						p.showStockInDialog(p.lowStockItems[pidx])
					}
				}
			}
			txt.Refresh()
		},
	)
	p.lowStockTable.SetColumnWidth(0, 40)
	p.lowStockTable.SetColumnWidth(1, 180)
	p.lowStockTable.SetColumnWidth(2, 120)
	p.lowStockTable.SetColumnWidth(3, 60)
	p.lowStockTable.SetColumnWidth(4, 70)
	p.lowStockTable.SetColumnWidth(5, 80)
	p.lowStockTable.SetColumnWidth(6, 110)

	lowBg := canvas.NewRectangle(postheme.BgCard)
	lowCard := container.NewMax(lowBg, container.NewPadded(
		container.NewBorder(container.NewVBox(lowStockTitle, widget.NewSeparator()), nil, nil, nil, p.lowStockTable)))

	// ── Stock movement history ───────────────────────
	mvTitle := canvas.NewText("📜 Riwayat Pergerakan Stok", postheme.TextPrimary)
	mvTitle.TextSize = 16
	mvTitle.TextStyle = fyne.TextStyle{Bold: true}

	mvCols := []string{"No", "Produk", "Tipe", "Jumlah", "Stok Lama", "Stok Baru", "Catatan", "Tanggal"}
	p.movementTable = widget.NewTable(
		func() (int, int) { return len(p.movements) + 1, len(mvCols) },
		func() fyne.CanvasObject {
			return canvas.NewText("text", postheme.TextPrimary)
		},
		func(id widget.TableCellID, obj fyne.CanvasObject) {
			txt := obj.(*canvas.Text)
			if id.Row == 0 {
				txt.Text = mvCols[id.Col]
				txt.TextStyle = fyne.TextStyle{Bold: true}
				txt.Color = postheme.Accent
				txt.TextSize = 12
				txt.Refresh()
				return
			}

			idx := id.Row - 1
			if idx >= len(p.movements) {
				return
			}
			mv := p.movements[idx]
			txt.TextStyle = fyne.TextStyle{}
			txt.Color = postheme.TextPrimary
			txt.TextSize = 12

			switch id.Col {
			case 0:
				txt.Text = fmt.Sprintf("%d", idx+1)
			case 1:
				txt.Text = mv.ProductName
			case 2:
				switch mv.MovementType {
				case "in":
					txt.Text = "MASUK"
					txt.Color = postheme.Success
				case "out":
					txt.Text = "KELUAR"
					txt.Color = postheme.Danger
				default:
					txt.Text = "ADJUST"
					txt.Color = postheme.Warning
				}
			case 3:
				txt.Text = fmt.Sprintf("%d", mv.Quantity)
			case 4:
				txt.Text = fmt.Sprintf("%d", mv.PrevStock)
			case 5:
				txt.Text = fmt.Sprintf("%d", mv.NewStock)
			case 6:
				txt.Text = mv.Notes
			case 7:
				if len(mv.CreatedAt) > 16 {
					txt.Text = mv.CreatedAt[:16]
				} else {
					txt.Text = mv.CreatedAt
				}
			}
			txt.Refresh()
		},
	)
	p.movementTable.SetColumnWidth(0, 40)
	p.movementTable.SetColumnWidth(1, 150)
	p.movementTable.SetColumnWidth(2, 70)
	p.movementTable.SetColumnWidth(3, 60)
	p.movementTable.SetColumnWidth(4, 70)
	p.movementTable.SetColumnWidth(5, 70)
	p.movementTable.SetColumnWidth(6, 140)
	p.movementTable.SetColumnWidth(7, 130)

	mvBg := canvas.NewRectangle(postheme.BgCard)
	mvCard := container.NewMax(mvBg, container.NewPadded(
		container.NewBorder(container.NewVBox(mvTitle, widget.NewSeparator()), nil, nil, nil, p.movementTable)))

	// Manual stock adjustment button
	adjustBtn := widget.NewButton("📦 Stok Masuk Manual", func() { p.showManualStockIn() })
	adjustBtn.Importance = widget.HighImportance

	p.Content = container.NewBorder(
		container.NewVBox(title, widget.NewSeparator(), container.NewHBox(adjustBtn)),
		nil, nil, nil,
		container.NewVSplit(lowCard, mvCard),
	)
}

func (p *InventoryPage) Refresh() {
	p.lowStockItems = database.GetLowStockProducts()
	p.lowStockTable.Refresh()
	p.movements = database.GetStockMovements(0, 100)
	p.movementTable.Refresh()
}

func (p *InventoryPage) showStockInDialog(prod database.Product) {
	win := fyne.CurrentApp().Driver().AllWindows()[0]
	qtyEntry := widget.NewEntry()
	qtyEntry.SetPlaceHolder("Jumlah")
	notesEntry := widget.NewEntry()
	notesEntry.SetPlaceHolder("Catatan")

	form := widget.NewForm(
		widget.NewFormItem("Produk", widget.NewLabel(prod.Name)),
		widget.NewFormItem("Stok Saat Ini", widget.NewLabel(fmt.Sprintf("%d", prod.Stock))),
		widget.NewFormItem("Jumlah Masuk", qtyEntry),
		widget.NewFormItem("Catatan", notesEntry),
	)

	d := dialog.NewCustomConfirm("Stok Masuk", "Simpan", "Batal", form, func(ok bool) {
		if !ok {
			return
		}
		qty, err := strconv.Atoi(qtyEntry.Text)
		if err != nil || qty <= 0 {
			dialog.ShowInformation("Error", "Jumlah harus angka positif", win)
			return
		}
		ok2, msg := database.UpdateStock(prod.ID, qty, "in", "", notesEntry.Text, 0)
		if !ok2 {
			dialog.ShowInformation("Error", msg, win)
			return
		}
		dialog.ShowInformation("Sukses", "Stok berhasil ditambahkan", win)
		p.Refresh()
	}, win)
	d.Resize(fyne.NewSize(400, 300))
	d.Show()
}

func (p *InventoryPage) showManualStockIn() {
	win := fyne.CurrentApp().Driver().AllWindows()[0]

	products := database.GetAllProducts("", 0, true)
	prodNames := make([]string, len(products))
	for i, pr := range products {
		prodNames[i] = fmt.Sprintf("[%s] %s (Stok: %d)", pr.Barcode, pr.Name, pr.Stock)
	}

	prodSelect := widget.NewSelect(prodNames, nil)
	qtyEntry := widget.NewEntry()
	qtyEntry.SetPlaceHolder("Jumlah")
	notesEntry := widget.NewEntry()
	notesEntry.SetPlaceHolder("Catatan")

	form := widget.NewForm(
		widget.NewFormItem("Produk", prodSelect),
		widget.NewFormItem("Jumlah Masuk", qtyEntry),
		widget.NewFormItem("Catatan", notesEntry),
	)

	d := dialog.NewCustomConfirm("Stok Masuk Manual", "Simpan", "Batal", form, func(ok bool) {
		if !ok {
			return
		}
		selIdx := -1
		for i, n := range prodNames {
			if n == prodSelect.Selected {
				selIdx = i
				break
			}
		}
		if selIdx < 0 {
			dialog.ShowInformation("Error", "Pilih produk terlebih dahulu", win)
			return
		}
		qty, err := strconv.Atoi(qtyEntry.Text)
		if err != nil || qty <= 0 {
			dialog.ShowInformation("Error", "Jumlah harus angka positif", win)
			return
		}
		ok2, msg := database.UpdateStock(products[selIdx].ID, qty, "in", "", notesEntry.Text, 0)
		if !ok2 {
			dialog.ShowInformation("Error", msg, win)
			return
		}
		dialog.ShowInformation("Sukses", "Stok berhasil ditambahkan", win)
		p.Refresh()
	}, win)
	d.Resize(fyne.NewSize(500, 300))
	d.Show()
}
