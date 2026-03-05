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
	"fyne.io/fyne/v2/layout"
	"fyne.io/fyne/v2/widget"
)

type cartEntry struct {
	ProductID   int
	ProductName string
	UnitPrice   float64
	Quantity    int
	Subtotal    float64
}

type POSPage struct {
	Content fyne.CanvasObject
	user    *database.User

	// Product search
	searchEntry *widget.Entry
	productList *widget.List
	products    []database.Product

	// Cart
	cart         []cartEntry
	cartList     *widget.List
	subtotalLabel *canvas.Text
	taxLabel      *canvas.Text
	totalLabel    *canvas.Text
	customerEntry *widget.Entry
	paymentEntry  *widget.Entry

	// Scroll container to get parent window
	parentWindow fyne.Window
}

func NewPOSPage(user *database.User) *POSPage {
	p := &POSPage{user: user}
	p.build()
	return p
}

func (p *POSPage) build() {
	title := canvas.NewText("🛒 Kasir (POS)", postheme.TextPrimary)
	title.TextSize = 22
	title.TextStyle = fyne.TextStyle{Bold: true}

	// ── LEFT: Product search & list ──────────────────
	p.searchEntry = widget.NewEntry()
	p.searchEntry.SetPlaceHolder("🔍 Cari produk atau scan barcode...")
	p.searchEntry.OnChanged = func(s string) {
		p.searchProducts(s)
	}
	p.searchEntry.OnSubmitted = func(s string) {
		// Try barcode scan
		prod := database.GetProductByBarcode(s)
		if prod != nil {
			p.addToCart(prod)
			p.searchEntry.SetText("")
			return
		}
		p.searchProducts(s)
	}

	p.productList = widget.NewList(
		func() int { return len(p.products) },
		func() fyne.CanvasObject {
			name := canvas.NewText("Product Name", postheme.TextPrimary)
			name.TextSize = 13
			price := canvas.NewText("Rp 0", postheme.Accent)
			price.TextSize = 12
			price.Alignment = fyne.TextAlignTrailing
			stock := canvas.NewText("Stock: 0", postheme.TextMuted)
			stock.TextSize = 11
			return container.NewBorder(nil, stock, name, price)
		},
		func(id widget.ListItemID, obj fyne.CanvasObject) {
			if id >= len(p.products) {
				return
			}
			prod := p.products[id]
			c := obj.(*fyne.Container)
			c.Objects[0].(*canvas.Text).Text = fmt.Sprintf("Stok: %d %s", prod.Stock, prod.Unit)
			c.Objects[0].(*canvas.Text).Refresh()
			c.Objects[1].(*canvas.Text).Text = prod.Name
			c.Objects[1].(*canvas.Text).Refresh()
			c.Objects[2].(*canvas.Text).Text = database.FormatRupiah(prod.SellPrice)
			c.Objects[2].(*canvas.Text).Refresh()
		},
	)
	p.productList.OnSelected = func(id widget.ListItemID) {
		if id < len(p.products) {
			p.addToCart(&p.products[id])
		}
		p.productList.UnselectAll()
	}

	leftBg := canvas.NewRectangle(postheme.BgCard)
	leftPanel := container.NewMax(leftBg, container.NewPadded(
		container.NewBorder(
			container.NewVBox(p.searchEntry, widget.NewSeparator()),
			nil, nil, nil,
			p.productList,
		),
	))

	// ── RIGHT: Cart ──────────────────────────────────
	cartTitle := canvas.NewText("🛍️ Keranjang", postheme.TextPrimary)
	cartTitle.TextSize = 16
	cartTitle.TextStyle = fyne.TextStyle{Bold: true}

	p.cartList = widget.NewList(
		func() int { return len(p.cart) },
		func() fyne.CanvasObject {
			name := canvas.NewText("Item", postheme.TextPrimary)
			name.TextSize = 12
			qty := canvas.NewText("x1", postheme.TextSecondary)
			qty.TextSize = 11
			sub := canvas.NewText("Rp 0", postheme.Accent)
			sub.TextSize = 12
			sub.Alignment = fyne.TextAlignTrailing
			delBtn := widget.NewButton("✕", nil)
			delBtn.Importance = widget.DangerImportance
			addBtn := widget.NewButton("+", nil)
			addBtn.Importance = widget.LowImportance
			minBtn := widget.NewButton("-", nil)
			minBtn.Importance = widget.LowImportance
			btns := container.NewHBox(minBtn, addBtn, delBtn)
			return container.NewBorder(nil, container.NewBorder(nil, nil, qty, btns), name, sub)
		},
		func(id widget.ListItemID, obj fyne.CanvasObject) {
			if id >= len(p.cart) {
				return
			}
			item := p.cart[id]
			c := obj.(*fyne.Container)
			// Bottom row
			bottom := c.Objects[0].(*fyne.Container)
			bottom.Objects[1].(*canvas.Text).Text = fmt.Sprintf("x%d @ %s", item.Quantity, database.FormatRupiah(item.UnitPrice))
			bottom.Objects[1].(*canvas.Text).Refresh()
			btnBox := bottom.Objects[2].(*fyne.Container)
			minBtn := btnBox.Objects[0].(*widget.Button)
			addBtn := btnBox.Objects[1].(*widget.Button)
			delBtn := btnBox.Objects[2].(*widget.Button)
			idx := id
			minBtn.OnTapped = func() { p.updateCartQty(idx, -1) }
			addBtn.OnTapped = func() { p.updateCartQty(idx, 1) }
			delBtn.OnTapped = func() { p.removeFromCart(idx) }

			// Name
			c.Objects[1].(*canvas.Text).Text = item.ProductName
			c.Objects[1].(*canvas.Text).Refresh()
			// Subtotal
			c.Objects[2].(*canvas.Text).Text = database.FormatRupiah(item.Subtotal)
			c.Objects[2].(*canvas.Text).Refresh()
		},
	)

	// ── Totals ───────────────────────────────────────
	p.subtotalLabel = canvas.NewText("Subtotal: Rp 0", postheme.TextSecondary)
	p.subtotalLabel.TextSize = 13
	p.taxLabel = canvas.NewText("Pajak (11%): Rp 0", postheme.TextSecondary)
	p.taxLabel.TextSize = 13
	p.totalLabel = canvas.NewText("TOTAL: Rp 0", postheme.TextPrimary)
	p.totalLabel.TextSize = 18
	p.totalLabel.TextStyle = fyne.TextStyle{Bold: true}

	p.customerEntry = widget.NewEntry()
	p.customerEntry.SetPlaceHolder("Nama pelanggan (opsional)")
	p.customerEntry.Text = "Umum"

	p.paymentEntry = widget.NewEntry()
	p.paymentEntry.SetPlaceHolder("Jumlah bayar")

	// ── Action buttons ───────────────────────────────
	payBtn := widget.NewButton("💳 Bayar", func() {
		p.processPayment()
	})
	payBtn.Importance = widget.HighImportance

	clearBtn := widget.NewButton("🗑️ Kosongkan", func() {
		p.cart = nil
		p.updateTotals()
		p.cartList.Refresh()
	})
	clearBtn.Importance = widget.DangerImportance

	totalsBox := container.NewVBox(
		widget.NewSeparator(),
		p.subtotalLabel,
		p.taxLabel,
		p.totalLabel,
		widget.NewSeparator(),
		widget.NewForm(
			widget.NewFormItem("Pelanggan", p.customerEntry),
			widget.NewFormItem("Bayar", p.paymentEntry),
		),
		container.NewGridWithColumns(2, clearBtn, payBtn),
	)

	rightBg := canvas.NewRectangle(postheme.BgCard)
	rightPanel := container.NewMax(rightBg, container.NewPadded(
		container.NewBorder(
			container.NewVBox(cartTitle, widget.NewSeparator()),
			totalsBox,
			nil, nil,
			p.cartList,
		),
	))

	// ── Main layout: left 60% | right 40% ───────────
	split := container.NewHSplit(leftPanel, rightPanel)
	split.SetOffset(0.55)

	p.Content = container.NewBorder(
		container.NewVBox(title, widget.NewSeparator()),
		nil, nil, nil,
		split,
	)
}

func (p *POSPage) Refresh() {
	p.searchProducts("")
}

func (p *POSPage) searchProducts(query string) {
	p.products = database.GetAllProducts(query, 0, true)
	p.productList.Refresh()
}

func (p *POSPage) addToCart(prod *database.Product) {
	if prod.Stock <= 0 {
		return
	}
	// Check if already in cart
	for i, item := range p.cart {
		if item.ProductID == prod.ID {
			p.cart[i].Quantity++
			p.cart[i].Subtotal = float64(p.cart[i].Quantity) * p.cart[i].UnitPrice
			p.updateTotals()
			p.cartList.Refresh()
			return
		}
	}
	p.cart = append(p.cart, cartEntry{
		ProductID:   prod.ID,
		ProductName: prod.Name,
		UnitPrice:   prod.SellPrice,
		Quantity:    1,
		Subtotal:    prod.SellPrice,
	})
	p.updateTotals()
	p.cartList.Refresh()
}

func (p *POSPage) updateCartQty(idx int, delta int) {
	if idx >= len(p.cart) {
		return
	}
	p.cart[idx].Quantity += delta
	if p.cart[idx].Quantity <= 0 {
		p.removeFromCart(idx)
		return
	}
	p.cart[idx].Subtotal = float64(p.cart[idx].Quantity) * p.cart[idx].UnitPrice
	p.updateTotals()
	p.cartList.Refresh()
}

func (p *POSPage) removeFromCart(idx int) {
	if idx >= len(p.cart) {
		return
	}
	p.cart = append(p.cart[:idx], p.cart[idx+1:]...)
	p.updateTotals()
	p.cartList.Refresh()
}

func (p *POSPage) updateTotals() {
	subtotal := 0.0
	for _, item := range p.cart {
		subtotal += item.Subtotal
	}

	settings := database.GetAllSettings()
	taxRate := 11.0
	if v, ok := settings["tax_rate"]; ok {
		fmt.Sscanf(v, "%f", &taxRate)
	}
	tax := float64(int(subtotal*taxRate/100*100)) / 100
	total := subtotal + tax

	p.subtotalLabel.Text = "Subtotal: " + database.FormatRupiah(subtotal)
	p.subtotalLabel.Refresh()
	p.taxLabel.Text = fmt.Sprintf("Pajak (%.0f%%): %s", taxRate, database.FormatRupiah(tax))
	p.taxLabel.Refresh()
	p.totalLabel.Text = "TOTAL: " + database.FormatRupiah(total)
	p.totalLabel.Refresh()
}

func (p *POSPage) processPayment() {
	if len(p.cart) == 0 {
		return
	}

	payStr := p.paymentEntry.Text
	payAmt, err := strconv.ParseFloat(payStr, 64)
	if err != nil || payAmt <= 0 {
		// Show error dialog
		if p.parentWindow == nil {
			p.parentWindow = fyne.CurrentApp().Driver().AllWindows()[0]
		}
		dialog.ShowInformation("Error", "Masukkan jumlah pembayaran yang valid", p.parentWindow)
		return
	}

	// Calculate total
	subtotal := 0.0
	for _, item := range p.cart {
		subtotal += item.Subtotal
	}
	settings := database.GetAllSettings()
	taxRate := 11.0
	if v, ok := settings["tax_rate"]; ok {
		fmt.Sscanf(v, "%f", &taxRate)
	}
	tax := float64(int(subtotal*taxRate/100*100)) / 100
	total := subtotal + tax

	if payAmt < total {
		if p.parentWindow == nil {
			p.parentWindow = fyne.CurrentApp().Driver().AllWindows()[0]
		}
		dialog.ShowInformation("Error", "Pembayaran kurang!", p.parentWindow)
		return
	}

	// Create cart items
	var items []database.CartItem
	for _, c := range p.cart {
		items = append(items, database.CartItem{
			ProductID:   c.ProductID,
			ProductName: c.ProductName,
			Quantity:    c.Quantity,
			UnitPrice:   c.UnitPrice,
			Discount:    0,
			Subtotal:    c.Subtotal,
		})
	}

	customer := p.customerEntry.Text
	if customer == "" {
		customer = "Umum"
	}

	invoice, finalAmt, change := database.CreateTransaction(p.user.ID, items, customer, 0, "cash", payAmt)

	if invoice != "" {
		// Show success
		if p.parentWindow == nil {
			p.parentWindow = fyne.CurrentApp().Driver().AllWindows()[0]
		}
		msg := fmt.Sprintf("✅ Transaksi Berhasil!\n\nInvoice: %s\nTotal: %s\nBayar: %s\nKembalian: %s",
			invoice, database.FormatRupiah(finalAmt), database.FormatRupiah(payAmt), database.FormatRupiah(change))
		dialog.ShowInformation("Sukses", msg, p.parentWindow)

		// Clear cart
		p.cart = nil
		p.paymentEntry.SetText("")
		p.customerEntry.SetText("Umum")
		p.updateTotals()
		p.cartList.Refresh()
		p.searchProducts("")
	}
}

// Unused but needed for layout
var _ = layout.NewSpacer
