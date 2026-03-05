package pages

import (
	"pos-system/database"
	postheme "pos-system/theme"

	"fyne.io/fyne/v2"
	"fyne.io/fyne/v2/canvas"
	"fyne.io/fyne/v2/container"
	"fyne.io/fyne/v2/dialog"
	"fyne.io/fyne/v2/widget"
)

type SettingsPage struct {
	Content fyne.CanvasObject

	storeNameEntry    *widget.Entry
	storeAddressEntry *widget.Entry
	storePhoneEntry   *widget.Entry
	taxRateEntry      *widget.Entry
	receiptFooter     *widget.Entry
	lowStockEntry     *widget.Entry
}

func NewSettingsPage() *SettingsPage {
	p := &SettingsPage{}
	p.build()
	return p
}

func (p *SettingsPage) build() {
	title := canvas.NewText("⚙️ Pengaturan", postheme.TextPrimary)
	title.TextSize = 22
	title.TextStyle = fyne.TextStyle{Bold: true}

	// ── Store settings ───────────────────────────────
	storeTitle := canvas.NewText("🏪 Informasi Toko", postheme.TextPrimary)
	storeTitle.TextSize = 16
	storeTitle.TextStyle = fyne.TextStyle{Bold: true}

	p.storeNameEntry = widget.NewEntry()
	p.storeAddressEntry = widget.NewEntry()
	p.storePhoneEntry = widget.NewEntry()
	p.taxRateEntry = widget.NewEntry()
	p.receiptFooter = widget.NewEntry()
	p.lowStockEntry = widget.NewEntry()

	storeForm := widget.NewForm(
		widget.NewFormItem("Nama Toko", p.storeNameEntry),
		widget.NewFormItem("Alamat", p.storeAddressEntry),
		widget.NewFormItem("Telepon", p.storePhoneEntry),
		widget.NewFormItem("Pajak (%)", p.taxRateEntry),
		widget.NewFormItem("Footer Struk", p.receiptFooter),
		widget.NewFormItem("Batas Stok Rendah", p.lowStockEntry),
	)

	saveBtn := widget.NewButton("💾 Simpan Pengaturan", func() {
		p.saveSettings()
	})
	saveBtn.Importance = widget.HighImportance

	storeBg := canvas.NewRectangle(postheme.BgCard)
	storeCard := container.NewMax(storeBg, container.NewPadded(
		container.NewVBox(storeTitle, widget.NewSeparator(), storeForm, saveBtn)))

	// ── Category management ──────────────────────────
	catTitle := canvas.NewText("🏷️ Manajemen Kategori", postheme.TextPrimary)
	catTitle.TextSize = 16
	catTitle.TextStyle = fyne.TextStyle{Bold: true}

	addCatBtn := widget.NewButton("➕ Tambah Kategori", func() { p.showCategoryDialog(nil) })
	addCatBtn.Importance = widget.HighImportance

	catBg := canvas.NewRectangle(postheme.BgCard)
	catCard := container.NewMax(catBg, container.NewPadded(
		container.NewVBox(catTitle, widget.NewSeparator(), addCatBtn)))

	// ── About ────────────────────────────────────────
	aboutTitle := canvas.NewText("ℹ️ Tentang Aplikasi", postheme.TextPrimary)
	aboutTitle.TextSize = 16
	aboutTitle.TextStyle = fyne.TextStyle{Bold: true}

	appName := canvas.NewText("POS System v1.0", postheme.Accent)
	appName.TextSize = 14
	appName.TextStyle = fyne.TextStyle{Bold: true}
	tech := canvas.NewText("Go + Fyne v2 + SQLite", postheme.TextSecondary)
	tech.TextSize = 12

	aboutBg := canvas.NewRectangle(postheme.BgCard)
	aboutCard := container.NewMax(aboutBg, container.NewPadded(
		container.NewVBox(aboutTitle, widget.NewSeparator(), appName, tech)))

	p.Content = container.NewVBox(
		title,
		widget.NewSeparator(),
		container.NewGridWithColumns(2, storeCard, container.NewVBox(catCard, aboutCard)),
	)
}

func (p *SettingsPage) Refresh() {
	settings := database.GetAllSettings()
	p.storeNameEntry.SetText(settings["store_name"])
	p.storeAddressEntry.SetText(settings["store_address"])
	p.storePhoneEntry.SetText(settings["store_phone"])
	p.taxRateEntry.SetText(settings["tax_rate"])
	p.receiptFooter.SetText(settings["receipt_footer"])
	p.lowStockEntry.SetText(settings["low_stock_alert"])
}

func (p *SettingsPage) saveSettings() {
	database.UpdateSetting("store_name", p.storeNameEntry.Text)
	database.UpdateSetting("store_address", p.storeAddressEntry.Text)
	database.UpdateSetting("store_phone", p.storePhoneEntry.Text)
	database.UpdateSetting("tax_rate", p.taxRateEntry.Text)
	database.UpdateSetting("receipt_footer", p.receiptFooter.Text)
	database.UpdateSetting("low_stock_alert", p.lowStockEntry.Text)

	win := fyne.CurrentApp().Driver().AllWindows()[0]
	dialog.ShowInformation("Sukses", "Pengaturan berhasil disimpan!", win)
}

func (p *SettingsPage) showCategoryDialog(existing *database.Category) {
	win := fyne.CurrentApp().Driver().AllWindows()[0]

	nameEntry := widget.NewEntry()
	nameEntry.SetPlaceHolder("Nama kategori")
	descEntry := widget.NewEntry()
	descEntry.SetPlaceHolder("Deskripsi")
	colorEntry := widget.NewEntry()
	colorEntry.SetPlaceHolder("#3498db")
	colorEntry.Text = "#3498db"

	if existing != nil {
		nameEntry.SetText(existing.Name)
		descEntry.SetText(existing.Description)
		colorEntry.SetText(existing.Color)
	}

	form := widget.NewForm(
		widget.NewFormItem("Nama", nameEntry),
		widget.NewFormItem("Deskripsi", descEntry),
		widget.NewFormItem("Warna", colorEntry),
	)

	dlgTitle := "Tambah Kategori"
	if existing != nil {
		dlgTitle = "Edit Kategori"
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
			database.UpdateCategory(existing.ID, nameEntry.Text, descEntry.Text, colorEntry.Text)
		} else {
			ok, msg := database.AddCategory(nameEntry.Text, descEntry.Text, colorEntry.Text)
			if !ok {
				dialog.ShowInformation("Error", msg, win)
				return
			}
		}
		dialog.ShowInformation("Sukses", "Kategori berhasil disimpan", win)
	}, win)
	d.Resize(fyne.NewSize(400, 280))
	d.Show()
}
