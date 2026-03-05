package pages

import (
	"fmt"
	"pos-system/database"
	postheme "pos-system/theme"
	"time"

	"fyne.io/fyne/v2"
	"fyne.io/fyne/v2/canvas"
	"fyne.io/fyne/v2/container"
	"fyne.io/fyne/v2/dialog"
	"fyne.io/fyne/v2/widget"
)

type HistoryPage struct {
	Content fyne.CanvasObject
	user    *database.User

	searchEntry *widget.Entry
	startEntry  *widget.Entry
	endEntry    *widget.Entry
	txTable     *widget.Table
	transactions []database.Transaction
}

func NewHistoryPage(user *database.User) *HistoryPage {
	p := &HistoryPage{user: user}
	p.build()
	return p
}

func (p *HistoryPage) build() {
	title := canvas.NewText("📜 Riwayat Transaksi", postheme.TextPrimary)
	title.TextSize = 22
	title.TextStyle = fyne.TextStyle{Bold: true}

	// ── Filter bar ───────────────────────────────────
	p.searchEntry = widget.NewEntry()
	p.searchEntry.SetPlaceHolder("🔍 Cari invoice / pelanggan...")
	p.searchEntry.OnChanged = func(_ string) { p.loadTransactions() }

	now := time.Now()
	p.startEntry = widget.NewEntry()
	p.startEntry.SetPlaceHolder("YYYY-MM-DD")
	p.startEntry.Text = now.AddDate(0, -1, 0).Format("2006-01-02")

	p.endEntry = widget.NewEntry()
	p.endEntry.SetPlaceHolder("YYYY-MM-DD")
	p.endEntry.Text = now.Format("2006-01-02")

	filterBtn := widget.NewButton("🔍 Filter", func() { p.loadTransactions() })
	filterBtn.Importance = widget.HighImportance

	filterRow := container.NewGridWithColumns(4, p.searchEntry, p.startEntry, p.endEntry, filterBtn)

	// ── Transaction table ────────────────────────────
	cols := []string{"No", "Invoice", "Kasir", "Pelanggan", "Total", "Bayar", "Kembalian", "Status", "Tanggal", "Aksi"}

	p.txTable = widget.NewTable(
		func() (int, int) { return len(p.transactions) + 1, len(cols) },
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
			if idx >= len(p.transactions) {
				return
			}
			tx := p.transactions[idx]
			txt.TextStyle = fyne.TextStyle{}
			txt.Color = postheme.TextPrimary
			txt.TextSize = 12

			switch id.Col {
			case 0:
				txt.Text = fmt.Sprintf("%d", idx+1)
			case 1:
				txt.Text = tx.InvoiceNumber
			case 2:
				txt.Text = tx.CashierName
			case 3:
				txt.Text = tx.CustomerName
			case 4:
				txt.Text = database.FormatRupiah(tx.FinalAmount)
			case 5:
				txt.Text = database.FormatRupiah(tx.PaymentAmount)
			case 6:
				txt.Text = database.FormatRupiah(tx.ChangeAmount)
			case 7:
				if tx.Status == "completed" {
					txt.Text = "✅ Selesai"
					txt.Color = postheme.Success
				} else {
					txt.Text = "❌ Void"
					txt.Color = postheme.Danger
				}
			case 8:
				if len(tx.CreatedAt) > 16 {
					txt.Text = tx.CreatedAt[:16]
				} else {
					txt.Text = tx.CreatedAt
				}
			case 9:
				txt.Hide()
				btn.Show()
				if tx.Status == "completed" {
					btn.SetText("🔍Detail")
					btn.Importance = widget.MediumImportance
				} else {
					btn.SetText("🔍Detail")
					btn.Importance = widget.LowImportance
				}
				tidx := idx
				btn.OnTapped = func() {
					if tidx < len(p.transactions) {
						p.showDetail(p.transactions[tidx])
					}
				}
			}
			txt.Refresh()
		},
	)

	p.txTable.SetColumnWidth(0, 35)
	p.txTable.SetColumnWidth(1, 150)
	p.txTable.SetColumnWidth(2, 100)
	p.txTable.SetColumnWidth(3, 80)
	p.txTable.SetColumnWidth(4, 110)
	p.txTable.SetColumnWidth(5, 110)
	p.txTable.SetColumnWidth(6, 100)
	p.txTable.SetColumnWidth(7, 90)
	p.txTable.SetColumnWidth(8, 130)
	p.txTable.SetColumnWidth(9, 80)

	tableBg := canvas.NewRectangle(postheme.BgCard)
	tableCard := container.NewMax(tableBg, container.NewPadded(p.txTable))

	p.Content = container.NewBorder(
		container.NewVBox(title, widget.NewSeparator(), filterRow, widget.NewSeparator()),
		nil, nil, nil,
		tableCard,
	)
}

func (p *HistoryPage) Refresh() {
	p.loadTransactions()
}

func (p *HistoryPage) loadTransactions() {
	p.transactions = database.GetTransactions(p.startEntry.Text, p.endEntry.Text, p.searchEntry.Text, 500)
	p.txTable.Refresh()
}

func (p *HistoryPage) showDetail(tx database.Transaction) {
	win := fyne.CurrentApp().Driver().AllWindows()[0]
	items := database.GetTransactionItems(tx.ID)

	// Build detail text
	detail := fmt.Sprintf("Invoice: %s\nKasir: %s\nPelanggan: %s\nStatus: %s\nTanggal: %s\n\n",
		tx.InvoiceNumber, tx.CashierName, tx.CustomerName, tx.Status, tx.CreatedAt)

	detail += "─── Item ───────────────────\n"
	for i, it := range items {
		detail += fmt.Sprintf("%d. %s x%d @ %s = %s\n",
			i+1, it.ProductName, it.Quantity,
			database.FormatRupiah(it.UnitPrice), database.FormatRupiah(it.Subtotal))
	}
	detail += fmt.Sprintf("\nSubtotal: %s\nPajak: %s\nDiskon: %s\nTotal: %s\nBayar: %s\nKembalian: %s",
		database.FormatRupiah(tx.TotalAmount), database.FormatRupiah(tx.TaxAmount),
		database.FormatRupiah(tx.DiscountAmount), database.FormatRupiah(tx.FinalAmount),
		database.FormatRupiah(tx.PaymentAmount), database.FormatRupiah(tx.ChangeAmount))

	detailText := widget.NewLabel(detail)
	detailText.Wrapping = fyne.TextWrapWord

	var dlgContent fyne.CanvasObject
	if tx.Status == "completed" && p.user.Role == "admin" {
		voidBtn := widget.NewButton("❌ Void Transaksi", func() {
			dialog.ShowConfirm("Konfirmasi Void", "Yakin void transaksi ini?", func(ok bool) {
				if ok {
					ok2, msg := database.VoidTransaction(tx.ID, p.user.ID)
					if ok2 {
						dialog.ShowInformation("Sukses", msg, win)
						p.loadTransactions()
					} else {
						dialog.ShowInformation("Error", msg, win)
					}
				}
			}, win)
		})
		voidBtn.Importance = widget.DangerImportance
		dlgContent = container.NewBorder(nil, voidBtn, nil, nil, container.NewScroll(detailText))
	} else {
		dlgContent = container.NewScroll(detailText)
	}

	d := dialog.NewCustom("Detail Transaksi", "Tutup", dlgContent, win)
	d.Resize(fyne.NewSize(500, 500))
	d.Show()
}
