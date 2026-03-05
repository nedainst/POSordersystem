package theme

import (
	"image/color"

	"fyne.io/fyne/v2"
	"fyne.io/fyne/v2/theme"
)

// ── Color palette matching POS design ────────────────────────
var (
	BgDark       = color.NRGBA{R: 15, G: 15, B: 20, A: 255}
	BgSidebar    = color.NRGBA{R: 22, G: 22, B: 29, A: 255}
	BgCard       = color.NRGBA{R: 30, G: 30, B: 46, A: 255}
	BgCardHover  = color.NRGBA{R: 38, G: 38, B: 56, A: 255}
	BgEntry      = color.NRGBA{R: 37, G: 37, B: 53, A: 255}
	Accent       = color.NRGBA{R: 108, G: 99, B: 255, A: 255}
	AccentHover  = color.NRGBA{R: 123, G: 115, B: 255, A: 255}
	AccentLight  = color.NRGBA{R: 138, G: 130, B: 255, A: 255}
	Success      = color.NRGBA{R: 46, G: 204, B: 113, A: 255}
	SuccessHover = color.NRGBA{R: 39, G: 174, B: 96, A: 255}
	Warning      = color.NRGBA{R: 243, G: 156, B: 18, A: 255}
	Danger       = color.NRGBA{R: 231, G: 76, B: 60, A: 255}
	DangerHover  = color.NRGBA{R: 192, G: 57, B: 43, A: 255}
	TextPrimary  = color.NRGBA{R: 232, G: 232, B: 240, A: 255}
	TextSecondary = color.NRGBA{R: 169, G: 169, B: 192, A: 255}
	TextMuted    = color.NRGBA{R: 108, G: 108, B: 136, A: 255}
	Border       = color.NRGBA{R: 42, G: 42, B: 61, A: 255}
)

// ── Custom POS Theme ─────────────────────────────────────────
type POSTheme struct{}

var _ fyne.Theme = (*POSTheme)(nil)

func (t *POSTheme) Color(name fyne.ThemeColorName, variant fyne.ThemeVariant) color.Color {
	switch name {
	case theme.ColorNameBackground:
		return BgDark
	case theme.ColorNameButton:
		return Accent
	case theme.ColorNameDisabledButton:
		return BgEntry
	case theme.ColorNameDisabled:
		return TextMuted
	case theme.ColorNameForeground:
		return TextPrimary
	case theme.ColorNameHover:
		return BgCardHover
	case theme.ColorNameInputBackground:
		return BgEntry
	case theme.ColorNameInputBorder:
		return Border
	case theme.ColorNamePlaceHolder:
		return TextMuted
	case theme.ColorNamePressed:
		return AccentHover
	case theme.ColorNamePrimary:
		return Accent
	case theme.ColorNameScrollBar:
		return Border
	case theme.ColorNameShadow:
		return color.NRGBA{R: 0, G: 0, B: 0, A: 80}
	case theme.ColorNameSelection:
		return color.NRGBA{R: 108, G: 99, B: 255, A: 100}
	case theme.ColorNameFocus:
		return Accent
	case theme.ColorNameSeparator:
		return Border
	case theme.ColorNameMenuBackground:
		return BgCard
	case theme.ColorNameOverlayBackground:
		return BgCard
	}
	return theme.DefaultTheme().Color(name, variant)
}

func (t *POSTheme) Font(style fyne.TextStyle) fyne.Resource {
	return theme.DefaultTheme().Font(style)
}

func (t *POSTheme) Icon(name fyne.ThemeIconName) fyne.Resource {
	return theme.DefaultTheme().Icon(name)
}

func (t *POSTheme) Size(name fyne.ThemeSizeName) float32 {
	switch name {
	case theme.SizeNamePadding:
		return 6
	case theme.SizeNameInlineIcon:
		return 20
	case theme.SizeNameText:
		return 13
	case theme.SizeNameHeadingText:
		return 22
	case theme.SizeNameSubHeadingText:
		return 16
	case theme.SizeNameInputBorder:
		return 1
	case theme.SizeNameScrollBar:
		return 8
	case theme.SizeNameScrollBarSmall:
		return 4
	}
	return theme.DefaultTheme().Size(name)
}
