package pages

import (
	"fmt"
	"pos-system/database"
	postheme "pos-system/theme"

	"fyne.io/fyne/v2"
	"fyne.io/fyne/v2/canvas"
	"fyne.io/fyne/v2/container"
	"fyne.io/fyne/v2/widget"
)

type DashboardPage struct {
	Content fyne.CanvasObject

	todaySalesLabel  *canvas.Text
	todayCountLabel  *canvas.Text
	monthSalesLabel  *canvas.Text
	monthCountLabel  *canvas.Text
	productsLabel    *canvas.Text
	lowStockLabel    *canvas.Text

	topProductsList  *widget.List
	recentTxList     *widget.List
	chartBars        []*canvas.Rectangle
	chartLabels      []*canvas.Text
	chartValues      []*canvas.Text

	topProducts []database.TopProduct
	recentTx    []database.Transaction
	dailySales  []database.DailySale
}

func NewDashboardPage() *DashboardPage {
	p := &DashboardPage{}
	p.build()
	return p
}

func (p *DashboardPage) build() {
	title := canvas.NewText("📊 Dashboard", postheme.TextPrimary)
	title.TextSize = 22
	title.TextStyle = fyne.TextStyle{Bold: true}

	// ── Stat cards ───────────────────────────────────
	p.todaySalesLabel = newStatValue("Rp 0")
	p.todayCountLabel = newStatSub("0 transaksi")
	p.monthSalesLabel = newStatValue("Rp 0")
	p.monthCountLabel = newStatSub("0 transaksi")
	p.productsLabel = newStatValue("0")
	p.lowStockLabel = newStatValue("0")

	card1 := makeStatCard("💵 Penjualan Hari Ini", p.todaySalesLabel, p.todayCountLabel)
	card2 := makeStatCard("📅 Penjualan Bulan Ini", p.monthSalesLabel, p.monthCountLabel)
	card3 := makeStatCard("📦 Total Produk", p.productsLabel, nil)
	card4 := makeStatCard("⚠️ Stok Rendah", p.lowStockLabel, nil)

	statsGrid := container.NewGridWithColumns(4, card1, card2, card3, card4)

	// ── Sales chart (simple bar chart using rectangles) ─
	chartTitle := canvas.NewText("Penjualan 7 Hari Terakhir", postheme.TextPrimary)
	chartTitle.TextSize = 15
	chartTitle.TextStyle = fyne.TextStyle{Bold: true}

	p.chartBars = make([]*canvas.Rectangle, 7)
	p.chartLabels = make([]*canvas.Text, 7)
	p.chartValues = make([]*canvas.Text, 7)

	barItems := make([]fyne.CanvasObject, 7)
	for i := 0; i < 7; i++ {
		bar := canvas.NewRectangle(postheme.Accent)
		bar.SetMinSize(fyne.NewSize(40, 20))
		p.chartBars[i] = bar

		lbl := canvas.NewText("--", postheme.TextMuted)
		lbl.TextSize = 10
		lbl.Alignment = fyne.TextAlignCenter
		p.chartLabels[i] = lbl

		val := canvas.NewText("0", postheme.TextSecondary)
		val.TextSize = 10
		val.Alignment = fyne.TextAlignCenter
		p.chartValues[i] = val

		barItems[i] = container.NewVBox(val, bar, lbl)
	}
	chartRow := container.NewGridWithColumns(7, barItems...)

	chartBg := canvas.NewRectangle(postheme.BgCard)
	chartCard := container.NewMax(chartBg, container.NewPadded(container.NewVBox(chartTitle, widget.NewSeparator(), chartRow)))

	// ── Top products list ────────────────────────────
	topTitle := canvas.NewText("🏆 Produk Terlaris", postheme.TextPrimary)
	topTitle.TextSize = 15
	topTitle.TextStyle = fyne.TextStyle{Bold: true}

	p.topProductsList = widget.NewList(
		func() int { return len(p.topProducts) },
		func() fyne.CanvasObject {
			name := canvas.NewText("Product", postheme.TextPrimary)
			name.TextSize = 13
			qty := canvas.NewText("0", postheme.TextSecondary)
			qty.TextSize = 12
			qty.Alignment = fyne.TextAlignTrailing
			return container.NewBorder(nil, nil, name, qty)
		},
		func(id widget.ListItemID, obj fyne.CanvasObject) {
			if id >= len(p.topProducts) {
				return
			}
			tp := p.topProducts[id]
			c := obj.(*fyne.Container)
			c.Objects[0].(*canvas.Text).Text = fmt.Sprintf("%d. %s", id+1, tp.Name)
			c.Objects[0].(*canvas.Text).Refresh()
			c.Objects[1].(*canvas.Text).Text = fmt.Sprintf("%d pcs - %s", tp.TotalQty, database.FormatRupiah(tp.TotalSales))
			c.Objects[1].(*canvas.Text).Refresh()
		},
	)
	topBg := canvas.NewRectangle(postheme.BgCard)
	topCard := container.NewMax(topBg, container.NewPadded(container.NewBorder(
		container.NewVBox(topTitle, widget.NewSeparator()), nil, nil, nil, p.topProductsList)))

	// ── Recent transactions ──────────────────────────
	recentTitle := canvas.NewText("🕐 Transaksi Terbaru", postheme.TextPrimary)
	recentTitle.TextSize = 15
	recentTitle.TextStyle = fyne.TextStyle{Bold: true}

	p.recentTxList = widget.NewList(
		func() int { return len(p.recentTx) },
		func() fyne.CanvasObject {
			inv := canvas.NewText("INV-XXX", postheme.TextPrimary)
			inv.TextSize = 12
			amt := canvas.NewText("Rp 0", postheme.Success)
			amt.TextSize = 12
			amt.Alignment = fyne.TextAlignTrailing
			return container.NewBorder(nil, nil, inv, amt)
		},
		func(id widget.ListItemID, obj fyne.CanvasObject) {
			if id >= len(p.recentTx) {
				return
			}
			tx := p.recentTx[id]
			c := obj.(*fyne.Container)
			statusColor := postheme.Success
			if tx.Status == "voided" {
				statusColor = postheme.Danger
			}
			invText := c.Objects[0].(*canvas.Text)
			invText.Text = tx.InvoiceNumber
			invText.Refresh()
			amtText := c.Objects[1].(*canvas.Text)
			amtText.Text = database.FormatRupiah(tx.FinalAmount)
			amtText.Color = statusColor
			amtText.Refresh()
		},
	)
	recentBg := canvas.NewRectangle(postheme.BgCard)
	recentCard := container.NewMax(recentBg, container.NewPadded(container.NewBorder(
		container.NewVBox(recentTitle, widget.NewSeparator()), nil, nil, nil, p.recentTxList)))

	// ── Bottom row: top products + recent tx ─────────
	bottomRow := container.NewGridWithColumns(2, topCard, recentCard)

	p.Content = container.NewVBox(
		title,
		widget.NewSeparator(),
		statsGrid,
		chartCard,
		bottomRow,
	)
}

func (p *DashboardPage) Refresh() {
	stats := database.GetDashboardStats()

	p.todaySalesLabel.Text = database.FormatRupiah(stats.TodaySalesTotal)
	p.todaySalesLabel.Refresh()
	p.todayCountLabel.Text = fmt.Sprintf("%d transaksi", stats.TodaySalesCount)
	p.todayCountLabel.Refresh()

	p.monthSalesLabel.Text = database.FormatRupiah(stats.MonthSalesTotal)
	p.monthSalesLabel.Refresh()
	p.monthCountLabel.Text = fmt.Sprintf("%d transaksi", stats.MonthSalesCount)
	p.monthCountLabel.Refresh()

	p.productsLabel.Text = fmt.Sprintf("%d", stats.TotalProducts)
	p.productsLabel.Refresh()
	p.lowStockLabel.Text = fmt.Sprintf("%d", stats.LowStockCount)
	if stats.LowStockCount > 0 {
		p.lowStockLabel.Color = postheme.Danger
	} else {
		p.lowStockLabel.Color = postheme.Success
	}
	p.lowStockLabel.Refresh()

	// Update chart
	p.dailySales = stats.DailySales
	maxVal := 1.0
	for _, ds := range p.dailySales {
		if ds.Total > maxVal {
			maxVal = ds.Total
		}
	}
	for i, ds := range p.dailySales {
		if i >= 7 {
			break
		}
		h := float32(ds.Total / maxVal * 100)
		if h < 5 {
			h = 5
		}
		p.chartBars[i].SetMinSize(fyne.NewSize(40, h))
		p.chartBars[i].Refresh()

		p.chartLabels[i].Text = ds.Date[5:] // MM-DD
		p.chartLabels[i].Refresh()

		if ds.Total >= 1000000 {
			p.chartValues[i].Text = fmt.Sprintf("%.1fJt", ds.Total/1000000)
		} else if ds.Total >= 1000 {
			p.chartValues[i].Text = fmt.Sprintf("%.0fRb", ds.Total/1000)
		} else {
			p.chartValues[i].Text = fmt.Sprintf("%.0f", ds.Total)
		}
		p.chartValues[i].Refresh()
	}

	// Update lists
	p.topProducts = stats.TopProducts
	p.topProductsList.Refresh()

	p.recentTx = stats.RecentTx
	p.recentTxList.Refresh()
}

// ── Helper functions ─────────────────────────────────────────

func newStatValue(text string) *canvas.Text {
	t := canvas.NewText(text, postheme.TextPrimary)
	t.TextSize = 20
	t.TextStyle = fyne.TextStyle{Bold: true}
	return t
}

func newStatSub(text string) *canvas.Text {
	t := canvas.NewText(text, postheme.TextSecondary)
	t.TextSize = 11
	return t
}

func makeStatCard(title string, value *canvas.Text, sub *canvas.Text) fyne.CanvasObject {
	titleText := canvas.NewText(title, postheme.TextSecondary)
	titleText.TextSize = 12

	items := []fyne.CanvasObject{titleText, value}
	if sub != nil {
		items = append(items, sub)
	}

	bg := canvas.NewRectangle(postheme.BgCard)
	content := container.NewVBox(items...)
	return container.NewMax(bg, container.NewPadded(content))
}
