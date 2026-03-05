#include "inventorypage.h"
#include "../database.h"
#include "../styles.h"
#include <QVBoxLayout>
#include <QHBoxLayout>
#include <QHeaderView>
#include <QLabel>
#include <QPushButton>
#include <QFormLayout>
#include <QMessageBox>
#include <QProgressBar>
#include <QScrollArea>

InventoryPage::InventoryPage(QWidget* parent) : QWidget(parent) {
    buildUI();
    loadStockList();
    loadLowStock();
    loadMovements();
}

void InventoryPage::buildUI() {
    auto* lay = new QVBoxLayout(this);
    lay->setContentsMargins(28, 24, 28, 24);
    lay->setSpacing(16);

    auto* title = new QLabel("\xF0\x9F\x93\x8B  Inventaris & Stok");
    title->setStyleSheet(QStringLiteral(
        "font-size: 22px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    lay->addWidget(title);

    m_tabs = new QTabWidget;
    lay->addWidget(m_tabs, 1);

    // ── Tab 1: Stock List ────────────────────────────────────
    {
        auto* tab = new QWidget;
        auto* tLay = new QVBoxLayout(tab);
        tLay->setContentsMargins(8, 12, 8, 8);

        auto* searchRow = new QHBoxLayout;
        m_stockSearch = new QLineEdit;
        m_stockSearch->setPlaceholderText("\xF0\x9F\x94\x8D Cari stok...");
        m_stockSearch->setMinimumHeight(36);
        connect(m_stockSearch, &QLineEdit::textChanged, this, &InventoryPage::loadStockList);
        searchRow->addWidget(m_stockSearch);
        tLay->addLayout(searchRow);

        m_stockTable = new QTableWidget;
        m_stockTable->setColumnCount(7);
        m_stockTable->setHorizontalHeaderLabels(
            {"Produk", "Barcode", "Kategori", "Stok", "Min", "Harga Beli", "Nilai Stok"});
        m_stockTable->horizontalHeader()->setSectionResizeMode(0, QHeaderView::Stretch);
        m_stockTable->verticalHeader()->hide();
        m_stockTable->setAlternatingRowColors(true);
        m_stockTable->setEditTriggers(QAbstractItemView::NoEditTriggers);
        tLay->addWidget(m_stockTable, 1);

        m_tabs->addTab(tab, "Daftar Stok");
    }

    // ── Tab 2: Low Stock ─────────────────────────────────────
    {
        auto* tab = new QWidget;
        auto* tLay = new QVBoxLayout(tab);
        tLay->setContentsMargins(8, 12, 8, 8);

        m_lowStockTable = new QTableWidget;
        m_lowStockTable->setColumnCount(5);
        m_lowStockTable->setHorizontalHeaderLabels(
            {"Produk", "Kategori", "Stok", "Min Stok", "Status"});
        m_lowStockTable->horizontalHeader()->setSectionResizeMode(0, QHeaderView::Stretch);
        m_lowStockTable->horizontalHeader()->setSectionResizeMode(4, QHeaderView::Stretch);
        m_lowStockTable->verticalHeader()->hide();
        m_lowStockTable->setAlternatingRowColors(true);
        m_lowStockTable->setEditTriggers(QAbstractItemView::NoEditTriggers);
        tLay->addWidget(m_lowStockTable, 1);

        m_tabs->addTab(tab, "Stok Rendah");
    }

    // ── Tab 3: Stock Movements ───────────────────────────────
    {
        auto* tab = new QWidget;
        auto* tLay = new QVBoxLayout(tab);
        tLay->setContentsMargins(8, 12, 8, 8);

        m_movementTable = new QTableWidget;
        m_movementTable->setColumnCount(5);
        m_movementTable->setHorizontalHeaderLabels(
            {"Produk", "Tipe", "Qty", "Catatan", "Waktu"});
        m_movementTable->horizontalHeader()->setSectionResizeMode(0, QHeaderView::Stretch);
        m_movementTable->horizontalHeader()->setSectionResizeMode(3, QHeaderView::Stretch);
        m_movementTable->verticalHeader()->hide();
        m_movementTable->setAlternatingRowColors(true);
        m_movementTable->setEditTriggers(QAbstractItemView::NoEditTriggers);
        tLay->addWidget(m_movementTable, 1);

        m_tabs->addTab(tab, "Riwayat Pergerakan");
    }

    // ── Tab 4: Adjust Stock ──────────────────────────────────
    {
        auto* tab = new QWidget;
        auto* tLay = new QVBoxLayout(tab);
        tLay->setContentsMargins(20, 20, 20, 20);

        auto* card = new QFrame;
        card->setStyleSheet(QStringLiteral(
            "QFrame{background-color:%1;border-radius:16px;}").arg(Style::BG_CARD));
        card->setMaximumWidth(500);
        auto* form = new QFormLayout(card);
        form->setContentsMargins(24, 20, 24, 20);
        form->setSpacing(12);

        auto* adjTitle = new QLabel("Penyesuaian Stok");
        adjTitle->setStyleSheet(QStringLiteral(
            "font-size: 16px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
        form->addRow(adjTitle);

        m_adjProduct = new QComboBox;
        m_adjProduct->setMinimumHeight(36);
        auto products = Database::instance().getProducts();
        for (auto& p : products)
            m_adjProduct->addItem(p["name"].toString(), p["id"]);
        form->addRow("Produk:", m_adjProduct);

        m_adjType = new QComboBox;
        m_adjType->setMinimumHeight(36);
        m_adjType->addItem("Stok Masuk", "in");
        m_adjType->addItem("Stok Keluar", "out");
        form->addRow("Tipe:", m_adjType);

        m_adjQty = new QLineEdit;
        m_adjQty->setPlaceholderText("Jumlah");
        m_adjQty->setMinimumHeight(36);
        form->addRow("Jumlah:", m_adjQty);

        m_adjNotes = new QLineEdit;
        m_adjNotes->setPlaceholderText("Catatan (opsional)");
        m_adjNotes->setMinimumHeight(36);
        form->addRow("Catatan:", m_adjNotes);

        auto* adjBtn = new QPushButton("Simpan Penyesuaian");
        adjBtn->setMinimumHeight(40);
        adjBtn->setCursor(Qt::PointingHandCursor);
        connect(adjBtn, &QPushButton::clicked, this, &InventoryPage::doAdjustStock);
        form->addRow(adjBtn);

        tLay->addWidget(card);
        tLay->addStretch();

        m_tabs->addTab(tab, "Penyesuaian Stok");
    }

    connect(m_tabs, &QTabWidget::currentChanged, this, [this](int idx){
        if (idx == 0) loadStockList();
        else if (idx == 1) loadLowStock();
        else if (idx == 2) loadMovements();
    });
}

void InventoryPage::loadStockList() {
    auto search = m_stockSearch->text().trimmed();
    auto stocks = Database::instance().getStockList(search);
    m_stockTable->setRowCount(stocks.size());
    for (int i = 0; i < stocks.size(); ++i) {
        auto& s = stocks[i];
        m_stockTable->setItem(i, 0, new QTableWidgetItem(s["name"].toString()));
        m_stockTable->setItem(i, 1, new QTableWidgetItem(s["barcode"].toString()));
        m_stockTable->setItem(i, 2, new QTableWidgetItem(s["category_name"].toString()));

        auto* stockItem = new QTableWidgetItem(s["stock"].toString());
        if (s["stock"].toInt() <= s["min_stock"].toInt())
            stockItem->setForeground(QColor(Style::DANGER));
        m_stockTable->setItem(i, 3, stockItem);

        m_stockTable->setItem(i, 4, new QTableWidgetItem(s["min_stock"].toString()));
        m_stockTable->setItem(i, 5, new QTableWidgetItem(
            Style::formatRupiah(s["buy_price"].toDouble())));
        double value = s["stock"].toInt() * s["buy_price"].toDouble();
        m_stockTable->setItem(i, 6, new QTableWidgetItem(Style::formatRupiah(value)));
    }
}

void InventoryPage::loadLowStock() {
    auto items = Database::instance().getLowStockProducts();
    m_lowStockTable->setRowCount(items.size());
    for (int i = 0; i < items.size(); ++i) {
        auto& s = items[i];
        m_lowStockTable->setItem(i, 0, new QTableWidgetItem(s["name"].toString()));
        m_lowStockTable->setItem(i, 1, new QTableWidgetItem(s["category_name"].toString()));

        auto* stockItem = new QTableWidgetItem(s["stock"].toString());
        stockItem->setForeground(QColor(Style::DANGER));
        m_lowStockTable->setItem(i, 2, stockItem);

        m_lowStockTable->setItem(i, 3, new QTableWidgetItem(s["min_stock"].toString()));

        // progress bar
        auto* bar = new QProgressBar;
        int pct = s["min_stock"].toInt() > 0
            ? (s["stock"].toInt() * 100 / s["min_stock"].toInt()) : 0;
        bar->setValue(std::min(pct, 100));
        if (pct < 30) bar->setStyleSheet("QProgressBar::chunk{background:#e74c3c;}");
        else if (pct < 60) bar->setStyleSheet("QProgressBar::chunk{background:#f39c12;}");
        m_lowStockTable->setCellWidget(i, 4, bar);
    }
}

void InventoryPage::loadMovements() {
    auto moves = Database::instance().getStockMovements(200);
    m_movementTable->setRowCount(moves.size());
    for (int i = 0; i < moves.size(); ++i) {
        auto& m = moves[i];
        m_movementTable->setItem(i, 0, new QTableWidgetItem(m["product_name"].toString()));

        auto* typeItem = new QTableWidgetItem(m["type"].toString() == "in" ? "Masuk" : "Keluar");
        typeItem->setForeground(QColor(
            m["type"].toString() == "in" ? Style::SUCCESS : Style::DANGER));
        m_movementTable->setItem(i, 1, typeItem);

        m_movementTable->setItem(i, 2, new QTableWidgetItem(m["quantity"].toString()));
        m_movementTable->setItem(i, 3, new QTableWidgetItem(m["notes"].toString()));
        m_movementTable->setItem(i, 4, new QTableWidgetItem(m["created_at"].toString()));
    }
}

void InventoryPage::doAdjustStock() {
    int productId = m_adjProduct->currentData().toInt();
    int qty = m_adjQty->text().toInt();
    QString type = m_adjType->currentData().toString();
    QString notes = m_adjNotes->text().trimmed();

    if (qty <= 0) {
        QMessageBox::warning(this, "Error", "Jumlah harus lebih dari 0!");
        return;
    }

    if (Database::instance().adjustStock(productId, qty, type, notes)) {
        QMessageBox::information(this, "Berhasil", "Stok berhasil disesuaikan");
        m_adjQty->clear();
        m_adjNotes->clear();
        loadStockList();
        loadLowStock();
        loadMovements();
    }
}
