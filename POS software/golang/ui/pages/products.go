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

type ProductsPage struct {
	Content fyne.CanvasObject

	searchEntry  *widget.Entry
	catFilter    *widget.Select
	productTable *widget.Table
	products     []database.Product
	categories   []database.Category
}

func NewProductsPage() *ProductsPage {
	p := &ProductsPage{}
	p.build()
	return p
}

func (p *ProductsPage) build() {
	title := canvas.NewText("📦 Manajemen Produk", postheme.TextPrimary)
	title.TextSize = 22
	title.TextStyle = fyne.TextStyle{Bold: true}

	// ── Search & filter bar ──────────────────────────
	p.searchEntry = widget.NewEntry()
	p.searchEntry.SetPlaceHolder("🔍 Cari produk...")
	p.searchEntry.OnChanged = func(_ string) { p.loadProducts() }

	p.catFilter = widget.NewSelect([]string{"Semua Kategori"}, func(_ string) { p.loadProducts() })
	p.catFilter.SetSelected("Semua Kategori")

	addBtn := widget.NewButton("➕ Tambah Produk", func() { p.showProductDialog(nil) })
	addBtn.Importance = widget.HighImportance

	toolbar := container.NewBorder(nil, nil,
		container.NewGridWithColumns(2, p.searchEntry, p.catFilter),
		addBtn,
	)

	// ── Product table ────────────────────────────────
	cols := []string{"No", "Barcode", "Nama", "Kategori", "Harga Beli", "Harga Jual", "Stok", "Aksi"}

	p.productTable = widget.NewTable(
		func() (int, int) { return len(p.products) + 1, len(cols) },
		func() fyne.CanvasObject {
			return container.NewMax(
				canvas.NewText("placeholder", postheme.TextPrimary),
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
				// Header
				txt.Text = cols[id.Col]
				txt.TextStyle = fyne.TextStyle{Bold: true}
				txt.Color = postheme.Accent
				txt.TextSize = 12
				txt.Refresh()
				return
			}

			idx := id.Row - 1
			if idx >= len(p.products) {
				return
			}
			prod := p.products[idx]
			txt.TextStyle = fyne.TextStyle{}
			txt.Color = postheme.TextPrimary
			txt.TextSize = 12

			switch id.Col {
			case 0:
				txt.Text = fmt.Sprintf("%d", idx+1)
			case 1:
				txt.Text = prod.Barcode
			case 2:
				txt.Text = prod.Name
			case 3:
				txt.Text = prod.CategoryName
			case 4:
				txt.Text = database.FormatRupiah(prod.BuyPrice)
			case 5:
				txt.Text = database.FormatRupiah(prod.SellPrice)
			case 6:
				txt.Text = fmt.Sprintf("%d %s", prod.Stock, prod.Unit)
				if prod.Stock <= prod.MinStock {
					txt.Color = postheme.Danger
				}
			case 7:
				txt.Hide()
				btn.Show()
				btn.SetText("✏️ Edit")
				btn.Importance = widget.MediumImportance
				pidx := idx
				btn.OnTapped = func() {
					if pidx < len(p.products) {
						pp := p.products[pidx]
						p.showProductDialog(&pp)
					}
				}
			}
			txt.Refresh()
		},
	)

	p.productTable.SetColumnWidth(0, 40)
	p.productTable.SetColumnWidth(1, 110)
	p.productTable.SetColumnWidth(2, 180)
	p.productTable.SetColumnWidth(3, 120)
	p.productTable.SetColumnWidth(4, 120)
	p.productTable.SetColumnWidth(5, 120)
	p.productTable.SetColumnWidth(6, 80)
	p.productTable.SetColumnWidth(7, 80)

	tableBg := canvas.NewRectangle(postheme.BgCard)
	tableCard := container.NewMax(tableBg, container.NewPadded(p.productTable))

	p.Content = container.NewBorder(
		container.NewVBox(title, widget.NewSeparator(), toolbar, widget.NewSeparator()),
		nil, nil, nil,
		tableCard,
	)
}

func (p *ProductsPage) Refresh() {
	p.loadCategories()
	p.loadProducts()
}

func (p *ProductsPage) loadCategories() {
	p.categories = database.GetAllCategories()
	options := []string{"Semua Kategori"}
	for _, c := range p.categories {
		options = append(options, c.Name)
	}
	p.catFilter.Options = options
	p.catFilter.Refresh()
}

func (p *ProductsPage) loadProducts() {
	search := p.searchEntry.Text
	catID := 0
	if p.catFilter.Selected != "Semua Kategori" {
		for _, c := range p.categories {
			if c.Name == p.catFilter.Selected {
				catID = c.ID
				break
			}
		}
	}
	p.products = database.GetAllProducts(search, catID, true)
	p.productTable.Refresh()
}

func (p *ProductsPage) showProductDialog(existing *database.Product) {
	win := fyne.CurrentApp().Driver().AllWindows()[0]

	barcodeEntry := widget.NewEntry()
	barcodeEntry.SetPlaceHolder("Barcode")
	nameEntry := widget.NewEntry()
	nameEntry.SetPlaceHolder("Nama produk")
	buyEntry := widget.NewEntry()
	buyEntry.SetPlaceHolder("Harga beli")
	sellEntry := widget.NewEntry()
	sellEntry.SetPlaceHolder("Harga jual")
	stockEntry := widget.NewEntry()
	stockEntry.SetPlaceHolder("Stok")
	minStockEntry := widget.NewEntry()
	minStockEntry.SetPlaceHolder("Min stok")
	unitEntry := widget.NewEntry()
	unitEntry.SetPlaceHolder("Satuan")
	unitEntry.Text = "pcs"

	catOptions := []string{}
	for _, c := range p.categories {
		catOptions = append(catOptions, c.Name)
	}
	catSelect := widget.NewSelect(catOptions, nil)

	if existing != nil {
		barcodeEntry.SetText(existing.Barcode)
		nameEntry.SetText(existing.Name)
		buyEntry.SetText(fmt.Sprintf("%.0f", existing.BuyPrice))
		sellEntry.SetText(fmt.Sprintf("%.0f", existing.SellPrice))
		stockEntry.SetText(fmt.Sprintf("%d", existing.Stock))
		minStockEntry.SetText(fmt.Sprintf("%d", existing.MinStock))
		unitEntry.SetText(existing.Unit)
		catSelect.SetSelected(existing.CategoryName)
	}

	form := widget.NewForm(
		widget.NewFormItem("Barcode", barcodeEntry),
		widget.NewFormItem("Nama", nameEntry),
		widget.NewFormItem("Kategori", catSelect),
		widget.NewFormItem("Harga Beli", buyEntry),
		widget.NewFormItem("Harga Jual", sellEntry),
		widget.NewFormItem("Stok", stockEntry),
		widget.NewFormItem("Min Stok", minStockEntry),
		widget.NewFormItem("Satuan", unitEntry),
	)

	dlgTitle := "Tambah Produk"
	if existing != nil {
		dlgTitle = "Edit Produk"

		// Add delete button
		delBtn := widget.NewButton("🗑️ Hapus Produk", func() {
			dialog.ShowConfirm("Konfirmasi", "Hapus produk ini?", func(ok bool) {
				if ok {
					database.DeleteProduct(existing.ID)
					p.loadProducts()
				}
			}, win)
		})
		delBtn.Importance = widget.DangerImportance
		form.Append("", delBtn)
	}

	d := dialog.NewCustomConfirm(dlgTitle, "Simpan", "Batal", form, func(ok bool) {
		if !ok {
			return
		}
		buy, _ := strconv.ParseFloat(buyEntry.Text, 64)
		sell, _ := strconv.ParseFloat(sellEntry.Text, 64)
		stock, _ := strconv.Atoi(stockEntry.Text)
		minStock, _ := strconv.Atoi(minStockEntry.Text)

		catID := 0
		for _, c := range p.categories {
			if c.Name == catSelect.Selected {
				catID = c.ID
				break
			}
		}

		if existing != nil {
			ok, msg := database.UpdateProduct(existing.ID, barcodeEntry.Text, nameEntry.Text, catID, buy, sell, stock, minStock, unitEntry.Text)
			if !ok {
				dialog.ShowInformation("Error", msg, win)
				return
			}
		} else {
			ok, msg := database.AddProduct(barcodeEntry.Text, nameEntry.Text, catID, buy, sell, stock, minStock, unitEntry.Text)
			if !ok {
				dialog.ShowInformation("Error", msg, win)
				return
			}
		}
		p.loadProducts()
	}, win)
	d.Resize(fyne.NewSize(450, 500))
	d.Show()
}
