package ui

import (
	"pos-system/database"
	postheme "pos-system/theme"

	"fyne.io/fyne/v2"
	"fyne.io/fyne/v2/canvas"
	"fyne.io/fyne/v2/container"
	"fyne.io/fyne/v2/layout"
	"fyne.io/fyne/v2/widget"
)

// BuildLoginContent creates the login UI as a CanvasObject.
// onLogin is called with the authenticated user on success.
func BuildLoginContent(w fyne.Window, onLogin func(*database.User)) fyne.CanvasObject {
	// ── Header ───────────────────────────────────────
	titleLabel := canvas.NewText("POS SYSTEM", postheme.Accent)
	titleLabel.TextSize = 28
	titleLabel.TextStyle = fyne.TextStyle{Bold: true}
	titleLabel.Alignment = fyne.TextAlignCenter

	subtitleLabel := canvas.NewText("Silakan login untuk melanjutkan", postheme.TextSecondary)
	subtitleLabel.TextSize = 13
	subtitleLabel.Alignment = fyne.TextAlignCenter

	headerBox := container.NewVBox(
		layout.NewSpacer(),
		titleLabel,
		widget.NewSeparator(),
		subtitleLabel,
		layout.NewSpacer(),
	)

	// ── Form fields ──────────────────────────────────
	usernameEntry := widget.NewEntry()
	usernameEntry.SetPlaceHolder("Username")

	passwordEntry := widget.NewPasswordEntry()
	passwordEntry.SetPlaceHolder("Password")

	errLabel := canvas.NewText("", postheme.Danger)
	errLabel.TextSize = 12
	errLabel.Alignment = fyne.TextAlignCenter

	// ── Login button ─────────────────────────────────
	loginBtn := widget.NewButton("LOGIN", nil)
	loginBtn.Importance = widget.HighImportance

	doLogin := func() {
		username := usernameEntry.Text
		password := passwordEntry.Text
		if username == "" || password == "" {
			errLabel.Text = "Username dan password harus diisi"
			errLabel.Refresh()
			return
		}
		user, err := database.Authenticate(username, password)
		if err != nil {
			errLabel.Text = "Username atau password salah"
			errLabel.Refresh()
			return
		}
		onLogin(user)
	}

	loginBtn.OnTapped = doLogin

	// Enter key support
	passwordEntry.OnSubmitted = func(_ string) { doLogin() }
	usernameEntry.OnSubmitted = func(_ string) {
		w.Canvas().Focus(passwordEntry)
	}

	// ── Info label ───────────────────────────────────
	infoLabel := canvas.NewText("Default: admin / admin123", postheme.TextMuted)
	infoLabel.TextSize = 11
	infoLabel.Alignment = fyne.TextAlignCenter

	// ── Card layout ──────────────────────────────────
	formBox := container.NewVBox(
		headerBox,
		widget.NewLabel(""), // spacer
		widget.NewForm(
			widget.NewFormItem("Username", usernameEntry),
			widget.NewFormItem("Password", passwordEntry),
		),
		errLabel,
		widget.NewLabel(""), // spacer
		loginBtn,
		widget.NewLabel(""), // spacer
		infoLabel,
	)

	// Background
	bg := canvas.NewRectangle(postheme.BgDark)
	cardBg := canvas.NewRectangle(postheme.BgCard)

	card := container.NewPadded(container.NewPadded(formBox))
	cardWithBg := container.NewMax(cardBg, card)

	content := container.NewMax(
		bg,
		container.NewCenter(
			container.NewPadded(cardWithBg),
		),
	)

	// Focus username field
	w.Canvas().Focus(usernameEntry)

	return content
}
