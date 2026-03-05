package pages

import (
	"fmt"
	"pos-system/database"
	postheme "pos-system/theme"
	"time"

	"fyne.io/fyne/v2"
	"fyne.io/fyne/v2/canvas"
	"fyne.io/fyne/v2/container"
	"fyne.io/fyne/v2/widget"
)

type ReportsPage struct {
	Content fyne.CanvasObject

	startEntry     *widget.Entry
	endEntry       *widget.Entry
	reportType     *widget.Select
	salesTable     *widget.Table
	productTable   *widget.Table
	salesReport    []database.SalesReportRow
	productReport  []database.ProductSalesRow
	salesContainer *fyne.Container
	prodContainer  *fyne.Container

	totalSalesLabel    *canvas.Text
	totalTxLabel       *canvas.Text
	totalProfitLabel   *canvas.Text
}

func NewReportsPage() *ReportsPage {
	p := &ReportsPage{}
	p.build()
	return p
}

func (p *ReportsPage) build() {
	title := canvas.NewText("📈 Laporan", postheme.TextPrimary)
	title.TextSize = 22
	title.TextStyle = fyne.TextStyle{Bold: true}

	// ── Date filter ──────────────────────────────────
	now := time.Now()
	p.startEntry = widget.NewEntry()
	p.startEntry.Text = now.AddDate(0, -1, 0).Format("2006-01-02")
	p.endEntry = widget.NewEntry()
	p.endEntry.Text = now.Format("2006-01-02")

	p.reportType = widget.NewSelect([]string{"Penjualan Harian", "Penjualan per Produk"}, func(s string) {
		p.generateReport()
	})
	p.reportType.SetSelected("Penjualan Harian")

	genBtn := widget.NewButton("📊 Generate", func() { p.generateReport() })
	genBtn.Importance = widget.HighImportance

	filterRow := container.NewGridWithColumns(4, p.startEntry, p.endEntry, p.reportType, genBtn)

	// ── Summary cards ────────────────────────────────
	p.totalSalesLabel = newStatValue("Rp 0")
	p.totalTxLabel = newStatValue("0")
	p.totalProfitLabel = newStatValue("Rp 0")

	card1 := makeStatCard("💵 Total Penjualan", p.totalSalesLabel, nil)
	card2 := makeStatCard("🧾 Jumlah Transaksi", p.totalTxLabel, nil)
	card3 := makeStatCard("📈 Estimasi Profit", p.totalProfitLabel, nil)
	summaryRow := container.NewGridWithColumns(3, card1, card2, card3)

	// ── Sales report table ───────────────────────────
	salesCols := []string{"No", "Tanggal", "Jumlah Tx", "Penjualan", "Diskon", "Pajak", "Total"}
	p.salesTable = widget.NewTable(
		func() (int, int) { return len(p.salesReport) + 1, len(salesCols) },
		func() fyne.CanvasObject {
			return canvas.NewText("text", postheme.TextPrimary)
		},
		func(id widget.TableCellID, obj fyne.CanvasObject) {
			txt := obj.(*canvas.Text)
			if id.Row == 0 {
				txt.Text = salesCols[id.Col]
				txt.TextStyle = fyne.TextStyle{Bold: true}
				txt.Color = postheme.Accent
				txt.TextSize = 12
				txt.Refresh()
				return
			}
			idx := id.Row - 1
			if idx >= len(p.salesReport) {
				return
			}
			r := p.salesReport[idx]
			txt.TextStyle = fyne.TextStyle{}
			txt.Color = postheme.TextPrimary
			txt.TextSize = 12
			switch id.Col {
			case 0:
				txt.Text = fmt.Sprintf("%d", idx+1)
			case 1:
				txt.Text = r.Date
			case 2:
				txt.Text = fmt.Sprintf("%d", r.NumTransactions)
			case 3:
				txt.Text = database.FormatRupiah(r.TotalSales)
			case 4:
				txt.Text = database.FormatRupiah(r.TotalDiscount)
			case 5:
				txt.Text = database.FormatRupiah(r.TotalTax)
			case 6:
				txt.Text = database.FormatRupiah(r.FinalTotal)
				txt.Color = postheme.Success
			}
			txt.Refresh()
		},
	)
	for i, w := range []float32{40, 110, 80, 120, 100, 100, 120} {
		p.salesTable.SetColumnWidth(i, w)
	}

	salesBg := canvas.NewRectangle(postheme.BgCard)
	p.salesContainer = container.NewMax(salesBg, container.NewPadded(p.salesTable))

	// ── Product sales report table ───────────────────
	prodCols := []string{"No", "Produk", "Terjual", "Total Penjualan", "Harga Beli", "Profit"}
	p.productTable = widget.NewTable(
		func() (int, int) { return len(p.productReport) + 1, len(prodCols) },
		func() fyne.CanvasObject {
			return canvas.NewText("text", postheme.TextPrimary)
		},
		func(id widget.TableCellID, obj fyne.CanvasObject) {
			txt := obj.(*canvas.Text)
			if id.Row == 0 {
				txt.Text = prodCols[id.Col]
				txt.TextStyle = fyne.TextStyle{Bold: true}
				txt.Color = postheme.Accent
				txt.TextSize = 12
				txt.Refresh()
				return
			}
			idx := id.Row - 1
			if idx >= len(p.productReport) {
				return
			}
			r := p.productReport[idx]
			txt.TextStyle = fyne.TextStyle{}
			txt.Color = postheme.TextPrimary
			txt.TextSize = 12
			switch id.Col {
			case 0:
				txt.Text = fmt.Sprintf("%d", idx+1)
			case 1:
				txt.Text = r.ProductName
			case 2:
				txt.Text = fmt.Sprintf("%d", r.TotalQty)
			case 3:
				txt.Text = database.FormatRupiah(r.TotalSales)
			case 4:
				txt.Text = database.FormatRupiah(r.BuyPrice)
			case 5:
				txt.Text = database.FormatRupiah(r.Profit)
				if r.Profit >= 0 {
					txt.Color = postheme.Success
				} else {
					txt.Color = postheme.Danger
				}
			}
			txt.Refresh()
		},
	)
	for i, w := range []float32{40, 200, 80, 130, 120, 130} {
		p.productTable.SetColumnWidth(i, w)
	}

	prodBg := canvas.NewRectangle(postheme.BgCard)
	p.prodContainer = container.NewMax(prodBg, container.NewPadded(p.productTable))
	p.prodContainer.Hide()

	reportStack := container.NewMax(p.salesContainer, p.prodContainer)

	p.Content = container.NewBorder(
		container.NewVBox(title, widget.NewSeparator(), filterRow, widget.NewSeparator(), summaryRow),
		nil, nil, nil,
		reportStack,
	)
}

func (p *ReportsPage) Refresh() {
	p.generateReport()
}

func (p *ReportsPage) generateReport() {
	start := p.startEntry.Text
	end := p.endEntry.Text

	if p.reportType.Selected == "Penjualan Harian" {
		p.salesReport = database.GetSalesReport(start, end)
		p.salesTable.Refresh()
		p.salesContainer.Show()
		p.prodContainer.Hide()

		// Calculate totals
		var totalSales float64
		var totalTx int
		for _, r := range p.salesReport {
			totalSales += r.FinalTotal
			totalTx += r.NumTransactions
		}
		p.totalSalesLabel.Text = database.FormatRupiah(totalSales)
		p.totalSalesLabel.Refresh()
		p.totalTxLabel.Text = fmt.Sprintf("%d", totalTx)
		p.totalTxLabel.Refresh()
		p.totalProfitLabel.Text = "-"
		p.totalProfitLabel.Refresh()
	} else {
		p.productReport = database.GetProductSalesReport(start, end)
		p.productTable.Refresh()
		p.salesContainer.Hide()
		p.prodContainer.Show()

		var totalSales, totalProfit float64
		for _, r := range p.productReport {
			totalSales += r.TotalSales
			totalProfit += r.Profit
		}
		p.totalSalesLabel.Text = database.FormatRupiah(totalSales)
		p.totalSalesLabel.Refresh()
		p.totalTxLabel.Text = fmt.Sprintf("%d produk", len(p.productReport))
		p.totalTxLabel.Refresh()
		p.totalProfitLabel.Text = database.FormatRupiah(totalProfit)
		p.totalProfitLabel.Color = postheme.Success
		p.totalProfitLabel.Refresh()
	}
}
