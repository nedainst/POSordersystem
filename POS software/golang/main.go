package main

import (
	"log"

	"pos-system/database"
	postheme "pos-system/theme"
	"pos-system/ui"

	"fyne.io/fyne/v2"
	"fyne.io/fyne/v2/app"
)

func main() {
	// Initialize database
	if err := database.InitDatabase(); err != nil {
		log.Fatal("Failed to initialize database:", err)
	}

	// Create Fyne application
	a := app.New()
	a.Settings().SetTheme(&postheme.POSTheme{})

	// Single window — content will be swapped between login and main
	w := a.NewWindow("POS System")
	w.Resize(fyne.NewSize(1280, 780))
	w.CenterOnScreen()

	var showLogin func()

	showLogin = func() {
		w.SetTitle("POS System - Login")
		loginContent := ui.BuildLoginContent(w, func(user *database.User) {
			// On successful login — switch to main UI
			w.SetTitle("POS System - " + user.FullName)
			mainContent := ui.BuildMainContent(w, user, func() {
				// On logout — go back to login
				showLogin()
			})
			w.SetContent(mainContent)
		})
		w.SetContent(loginContent)
	}

	showLogin()
	w.ShowAndRun()
}
