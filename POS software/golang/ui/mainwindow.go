package ui

import (
	"pos-system/database"
	postheme "pos-system/theme"
	"pos-system/ui/pages"

	"fyne.io/fyne/v2"
	"fyne.io/fyne/v2/canvas"
	"fyne.io/fyne/v2/container"
	"fyne.io/fyne/v2/layout"
	"fyne.io/fyne/v2/widget"
)

type navItem struct {
	icon     string
	label    string
	page     string
	adminOnly bool
}

var navItems = []navItem{
	{"📊", "Dashboard", "dashboard", false},
	{"🛒", "Kasir (POS)", "pos", false},
	{"📦", "Produk", "products", true},
	{"📋", "Inventori", "inventory", true},
	{"📜", "Riwayat", "history", false},
	{"📈", "Laporan", "reports", true},
	{"🚚", "Supplier", "suppliers", true},
	{"💰", "Pengeluaran", "expenses", true},
	{"👥", "Pengguna", "users", true},
	{"⚙️", "Pengaturan", "settings", true},
}

// MainWindow holds all references for the main application UI
type MainWindow struct {
	Window      fyne.Window
	User        *database.User
	PageStack   *fyne.Container
	NavButtons  []*widget.Button
	CurrentPage string
	OnLogout    func()

	// Pages
	dashboardPage *pages.DashboardPage
	posPage       *pages.POSPage
	productsPage  *pages.ProductsPage
	inventoryPage *pages.InventoryPage
	historyPage   *pages.HistoryPage
	reportsPage   *pages.ReportsPage
	suppliersPage *pages.SuppliersPage
	expensesPage  *pages.ExpensesPage
	usersPage     *pages.UsersPage
	settingsPage  *pages.SettingsPage

	pageMap map[string]fyne.CanvasObject
}

// BuildMainContent creates the main application UI as a CanvasObject.
func BuildMainContent(w fyne.Window, user *database.User, onLogout func()) fyne.CanvasObject {
	mw := &MainWindow{
		Window:   w,
		User:     user,
		OnLogout: onLogout,
		pageMap:  make(map[string]fyne.CanvasObject),
	}

	return mw.buildUI()
}

func (mw *MainWindow) buildUI() fyne.CanvasObject {
	// ── Create pages ─────────────────────────────────
	mw.dashboardPage = pages.NewDashboardPage()
	mw.posPage = pages.NewPOSPage(mw.User)
	mw.productsPage = pages.NewProductsPage()
	mw.inventoryPage = pages.NewInventoryPage()
	mw.historyPage = pages.NewHistoryPage(mw.User)
	mw.reportsPage = pages.NewReportsPage()
	mw.suppliersPage = pages.NewSuppliersPage()
	mw.expensesPage = pages.NewExpensesPage(mw.User)
	mw.usersPage = pages.NewUsersPage()
	mw.settingsPage = pages.NewSettingsPage()

	mw.pageMap["dashboard"] = mw.dashboardPage.Content
	mw.pageMap["pos"] = mw.posPage.Content
	mw.pageMap["products"] = mw.productsPage.Content
	mw.pageMap["inventory"] = mw.inventoryPage.Content
	mw.pageMap["history"] = mw.historyPage.Content
	mw.pageMap["reports"] = mw.reportsPage.Content
	mw.pageMap["suppliers"] = mw.suppliersPage.Content
	mw.pageMap["expenses"] = mw.expensesPage.Content
	mw.pageMap["users"] = mw.usersPage.Content
	mw.pageMap["settings"] = mw.settingsPage.Content

	// ── Page stack (only one page visible at a time) ─
	mw.PageStack = container.NewMax()
	// Add all pages hidden initially
	for _, obj := range mw.pageMap {
		obj.Hide()
		mw.PageStack.Add(obj)
	}

	// ── Sidebar ──────────────────────────────────────
	sidebar := mw.buildSidebar()

	// ── Main layout ──────────────────────────────────
	sidebarBg := canvas.NewRectangle(postheme.BgSidebar)
	sidebarWithBg := container.NewMax(sidebarBg, sidebar)
	sidebarFixed := container.New(&fixedWidthLayout{width: 220}, sidebarWithBg)

	contentBg := canvas.NewRectangle(postheme.BgDark)
	contentArea := container.NewMax(contentBg, container.NewPadded(mw.PageStack))

	mainLayout := container.NewBorder(nil, nil, sidebarFixed, nil, contentArea)

	// Show dashboard by default
	mw.switchPage("dashboard")

	return mainLayout
}

func (mw *MainWindow) buildSidebar() fyne.CanvasObject {
	// ── Logo / title ─────────────────────────────────
	logoText := canvas.NewText("🏪 POS System", postheme.Accent)
	logoText.TextSize = 18
	logoText.TextStyle = fyne.TextStyle{Bold: true}
	logoText.Alignment = fyne.TextAlignCenter

	sep := canvas.NewRectangle(postheme.Border)
	sep.SetMinSize(fyne.NewSize(0, 1))

	// ── Navigation buttons ───────────────────────────
	navBox := container.NewVBox()
	mw.NavButtons = nil

	for _, item := range navItems {
		if item.adminOnly && mw.User.Role != "admin" {
			continue
		}
		ni := item // capture
		btn := widget.NewButton(ni.icon+"  "+ni.label, nil)
		btn.Alignment = widget.ButtonAlignLeading
		btn.Importance = widget.LowImportance
		btn.OnTapped = func() {
			mw.switchPage(ni.page)
		}
		mw.NavButtons = append(mw.NavButtons, btn)
		navBox.Add(btn)
	}

	// ── User info card ───────────────────────────────
	userIcon := canvas.NewText("👤", postheme.TextPrimary)
	userIcon.TextSize = 16

	userName := canvas.NewText(mw.User.FullName, postheme.TextPrimary)
	userName.TextSize = 12
	userName.TextStyle = fyne.TextStyle{Bold: true}

	roleLabel := canvas.NewText(mw.User.Role, postheme.TextMuted)
	roleLabel.TextSize = 11

	userInfo := container.NewHBox(
		userIcon,
		container.NewVBox(userName, roleLabel),
	)

	// ── Logout button ────────────────────────────────
	logoutBtn := widget.NewButton("🚪  Logout", func() {
		if mw.OnLogout != nil {
			mw.OnLogout()
		}
	})
	logoutBtn.Importance = widget.DangerImportance
	logoutBtn.Alignment = widget.ButtonAlignLeading

	// ── Assemble sidebar ─────────────────────────────
	sidebarContent := container.NewVBox(
		widget.NewLabel(""), // top padding
		logoText,
		widget.NewLabel(""), // spacer
		sep,
		widget.NewLabel(""), // spacer
		navBox,
		layout.NewSpacer(),
		canvas.NewRectangle(postheme.Border), // separator
		widget.NewLabel(""), // spacer
		userInfo,
		logoutBtn,
		widget.NewLabel(""), // bottom padding
	)

	return container.NewPadded(sidebarContent)
}

func (mw *MainWindow) switchPage(pageName string) {
	// Hide all pages
	for _, obj := range mw.pageMap {
		obj.Hide()
	}

	// Show selected page
	if page, ok := mw.pageMap[pageName]; ok {
		page.Show()
		mw.CurrentPage = pageName

		// Refresh data on page switch
		switch pageName {
		case "dashboard":
			mw.dashboardPage.Refresh()
		case "pos":
			mw.posPage.Refresh()
		case "products":
			mw.productsPage.Refresh()
		case "inventory":
			mw.inventoryPage.Refresh()
		case "history":
			mw.historyPage.Refresh()
		case "reports":
			mw.reportsPage.Refresh()
		case "suppliers":
			mw.suppliersPage.Refresh()
		case "expenses":
			mw.expensesPage.Refresh()
		case "users":
			mw.usersPage.Refresh()
		case "settings":
			mw.settingsPage.Refresh()
		}
	}

	// Update button states - highlight active
	idx := 0
	for _, item := range navItems {
		if item.adminOnly && mw.User.Role != "admin" {
			continue
		}
		if idx < len(mw.NavButtons) {
			if item.page == pageName {
				mw.NavButtons[idx].Importance = widget.HighImportance
			} else {
				mw.NavButtons[idx].Importance = widget.LowImportance
			}
			mw.NavButtons[idx].Refresh()
		}
		idx++
	}
}

// ── Fixed width layout ───────────────────────────────────────

type fixedWidthLayout struct {
	width float32
}

func (l *fixedWidthLayout) MinSize(objects []fyne.CanvasObject) fyne.Size {
	h := float32(0)
	for _, o := range objects {
		if o.Visible() {
			ms := o.MinSize()
			if ms.Height > h {
				h = ms.Height
			}
		}
	}
	return fyne.NewSize(l.width, h)
}

func (l *fixedWidthLayout) Layout(objects []fyne.CanvasObject, size fyne.Size) {
	for _, o := range objects {
		o.Resize(fyne.NewSize(l.width, size.Height))
		o.Move(fyne.NewPos(0, 0))
	}
}
