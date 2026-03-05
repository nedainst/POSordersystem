#include <QApplication>
#include "database.h"
#include "logindialog.h"
#include "mainwindow.h"
#include "styles.h"

int main(int argc, char* argv[])
{
    QApplication app(argc, argv);
    app.setApplicationName("POS System");
    app.setOrganizationName("POSTeam");
    app.setStyleSheet(Style::appStyleSheet());
    app.setQuitOnLastWindowClosed(false);

    // ── Initialize database ──────────────────────────────────
    if (!Database::instance().initialize()) {
        qCritical("Failed to initialize database!");
        return 1;
    }

    // ── Login → Main loop ────────────────────────────────────
    bool running = true;
    while (running) {
        LoginDialog login;
        if (login.exec() != QDialog::Accepted) {
            running = false;
            break;
        }

        MainWindow* w = new MainWindow(login.loggedInUser(),
                                       login.loggedInRole(),
                                       login.loggedInId());
        w->setAttribute(Qt::WA_DeleteOnClose);
        w->show();

        // Run event loop until main window is closed
        QEventLoop loop;
        QObject::connect(w, &QMainWindow::destroyed, &loop, &QEventLoop::quit);
        loop.exec();
    }

    Database::instance().close();
    return 0;
}
