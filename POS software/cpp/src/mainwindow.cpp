#include "mainwindow.h"
#include "styles.h"
#include "pages/dashboardpage.h"
#include "pages/pospage.h"
#include "pages/productspage.h"
#include "pages/inventorypage.h"
#include "pages/historypage.h"
#include "pages/reportspage.h"
#include "pages/supplierspage.h"
#include "pages/expensespage.h"
#include "pages/userspage.h"
#include "pages/settingspage.h"
#include <QApplication>
#include <QHBoxLayout>
#include <QScrollArea>
#include <QGraphicsDropShadowEffect>
#include <QPropertyAnimation>

MainWindow::MainWindow(const QString& userName, const QString& role,
                       int userId, QWidget* parent)
    : QMainWindow(parent), m_userName(userName), m_userRole(role), m_userId(userId)
{
    setWindowTitle("POS System - Modern");
    resize(1400, 850);
    setMinimumSize(1100, 700);
    setStyleSheet(Style::appStyleSheet());

    // ── Central widget ───────────────────────────────────────
    auto* central = new QWidget;
    setCentralWidget(central);
    auto* rootLay = new QHBoxLayout(central);
    rootLay->setContentsMargins(0, 0, 0, 0);
    rootLay->setSpacing(0);

    // sidebar
    buildSidebar();
    rootLay->addWidget(m_sidebar);

    // content
    m_stack = new QStackedWidget;
    m_stack->setStyleSheet(QStringLiteral("background-color: %1;").arg(Style::BG_DARK));
    rootLay->addWidget(m_stack, 1);

    buildPages();
    updateSidebarButtons();
}

// ═══════════════════════════════════════════════════════════════
//  Sidebar
// ═══════════════════════════════════════════════════════════════
void MainWindow::buildSidebar() {
    m_sidebar = new QFrame;
    m_sidebar->setFixedWidth(230);
    m_sidebar->setStyleSheet(QStringLiteral(
        "QFrame { background-color: %1; border-right: 1px solid %2; }")
        .arg(Style::BG_SIDEBAR, Style::BORDER));

    auto* lay = new QVBoxLayout(m_sidebar);
    lay->setContentsMargins(14, 20, 14, 14);
    lay->setSpacing(4);

    // ── Logo ─────────────────────────────────────────────────
    auto* logoFrame = new QFrame;
    logoFrame->setStyleSheet("background: transparent; border: none;");
    auto* logoLay = new QHBoxLayout(logoFrame);
    logoLay->setContentsMargins(4, 0, 0, 16);

    auto* logoIcon = new QLabel("\xF0\x9F\x9B\x92"); // 🛒
    logoIcon->setStyleSheet("font-size: 26px;");
    logoLay->addWidget(logoIcon);

    auto* logoText = new QLabel("  POS System");
    logoText->setStyleSheet(QStringLiteral(
        "font-size: 18px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    logoLay->addWidget(logoText);
    logoLay->addStretch();
    lay->addWidget(logoFrame);

    // ── Separator ────────────────────────────────────────────
    auto* sep1 = new QFrame;
    sep1->setFrameShape(QFrame::HLine);
    sep1->setStyleSheet(QStringLiteral("color: %1;").arg(Style::BORDER));
    sep1->setFixedHeight(1);
    lay->addWidget(sep1);
    lay->addSpacing(8);

    // ── Navigation ───────────────────────────────────────────
    m_navItems = {
        {"\xF0\x9F\x93\x8A", "Dashboard"},     // 📊
        {"\xF0\x9F\x9B\x92", "Kasir (POS)"},   // 🛒
        {"\xF0\x9F\x93\xA6", "Produk"},         // 📦
        {"\xF0\x9F\x93\x8B", "Inventaris"},     // 📋
        {"\xF0\x9F\x93\x9C", "Riwayat"},        // 📜
        {"\xF0\x9F\x93\x88", "Laporan"},        // 📈
        {"\xF0\x9F\x9A\x9A", "Supplier"},       // 🚚
        {"\xF0\x9F\x92\xB0", "Pengeluaran"},    // 💰
        {"\xF0\x9F\x91\xA5", "Pengguna"},       // 👥
        {"\xE2\x9A\x99\xEF\xB8\x8F", "Pengaturan"} // ⚙️
    };

    for (int i = 0; i < m_navItems.size(); ++i) {
        auto* btn = new QPushButton(
            QStringLiteral("  %1  %2").arg(m_navItems[i].icon, m_navItems[i].text));
        btn->setCursor(Qt::PointingHandCursor);
        btn->setMinimumHeight(38);
        connect(btn, &QPushButton::clicked, this, [this, i]{ navigateTo(i); });
        lay->addWidget(btn);
        m_navButtons.append(btn);

        // hide admin pages for cashier
        if (m_userRole == "cashier" && (i == 7 || i == 8 || i == 9))
            btn->hide();
    }

    lay->addStretch();

    // ── Separator ────────────────────────────────────────────
    auto* sep2 = new QFrame;
    sep2->setFrameShape(QFrame::HLine);
    sep2->setStyleSheet(QStringLiteral("color: %1;").arg(Style::BORDER));
    sep2->setFixedHeight(1);
    lay->addWidget(sep2);
    lay->addSpacing(6);

    // ── User info ────────────────────────────────────────────
    auto* userFrame = new QFrame;
    userFrame->setStyleSheet(QStringLiteral(
        "background-color: %1; border-radius: 12px; border: none;").arg(Style::BG_CARD));
    auto* userLay = new QVBoxLayout(userFrame);
    userLay->setContentsMargins(12, 10, 12, 10);
    userLay->setSpacing(2);

    m_userLabel = new QLabel(m_userName);
    m_userLabel->setStyleSheet(QStringLiteral(
        "font-size: 13px; font-weight: 600; color: %1;").arg(Style::TEXT_PRIMARY));
    userLay->addWidget(m_userLabel);

    m_roleLabel = new QLabel(m_userRole == "admin" ? "Administrator" : "Kasir");
    m_roleLabel->setStyleSheet(QStringLiteral(
        "font-size: 11px; color: %1;").arg(Style::TEXT_MUTED));
    userLay->addWidget(m_roleLabel);

    lay->addWidget(userFrame);
    lay->addSpacing(6);

    // ── Logout button ────────────────────────────────────────
    auto* logoutBtn = new QPushButton("\xF0\x9F\x9A\xAA  Keluar"); // 🚪
    logoutBtn->setCursor(Qt::PointingHandCursor);
    logoutBtn->setMinimumHeight(38);
    logoutBtn->setStyleSheet(Style::dangerBtnStyle());
    connect(logoutBtn, &QPushButton::clicked, this, &MainWindow::onLogout);
    lay->addWidget(logoutBtn);
}

// ═══════════════════════════════════════════════════════════════
//  Pages
// ═══════════════════════════════════════════════════════════════
void MainWindow::buildPages() {
    m_stack->addWidget(new DashboardPage);
    m_stack->addWidget(new POSPage(m_userId));
    m_stack->addWidget(new ProductsPage);
    m_stack->addWidget(new InventoryPage);
    m_stack->addWidget(new HistoryPage(m_userRole));
    m_stack->addWidget(new ReportsPage);
    m_stack->addWidget(new SuppliersPage);
    m_stack->addWidget(new ExpensesPage);
    m_stack->addWidget(new UsersPage);
    m_stack->addWidget(new SettingsPage);
}

// ═══════════════════════════════════════════════════════════════
//  Navigation
// ═══════════════════════════════════════════════════════════════
void MainWindow::navigateTo(int index) {
    if (index == m_currentIndex) return;
    m_currentIndex = index;
    m_stack->setCurrentIndex(index);

    // refresh the page if it supports it
    if (auto* dash = qobject_cast<DashboardPage*>(m_stack->currentWidget()))
        dash->refresh();

    updateSidebarButtons();
}

void MainWindow::updateSidebarButtons() {
    for (int i = 0; i < m_navButtons.size(); ++i)
        m_navButtons[i]->setStyleSheet(Style::sidebarBtnStyle(i == m_currentIndex));
}

void MainWindow::onLogout() {
    close();
    // main.cpp will re-show login dialog
}
