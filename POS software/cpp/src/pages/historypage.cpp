#include "historypage.h"
#include "../database.h"
#include "../styles.h"
#include <QVBoxLayout>
#include <QHBoxLayout>
#include <QHeaderView>
#include <QPushButton>
#include <QLabel>
#include <QDialog>
#include <QMessageBox>
#include <QDate>

HistoryPage::HistoryPage(const QString& userRole, QWidget* parent)
    : QWidget(parent), m_userRole(userRole)
{
    buildUI();
    loadTransactions();
}

void HistoryPage::buildUI() {
    auto* lay = new QVBoxLayout(this);
    lay->setContentsMargins(28, 24, 28, 24);
    lay->setSpacing(16);

    auto* title = new QLabel("\xF0\x9F\x93\x9C  Riwayat Transaksi");
    title->setStyleSheet(QStringLiteral(
        "font-size: 22px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    lay->addWidget(title);

    // filter row
    auto* filterRow = new QHBoxLayout;
    filterRow->setSpacing(10);

    m_searchEdit = new QLineEdit;
    m_searchEdit->setPlaceholderText("\xF0\x9F\x94\x8D Cari invoice / pelanggan...");
    m_searchEdit->setMinimumHeight(38);
    connect(m_searchEdit, &QLineEdit::textChanged, this, &HistoryPage::loadTransactions);
    filterRow->addWidget(m_searchEdit, 2);

    auto* fromLabel = new QLabel("Dari:");
    fromLabel->setStyleSheet(QStringLiteral("color: %1;").arg(Style::TEXT_SECONDARY));
    filterRow->addWidget(fromLabel);
    m_dateFrom = new QDateEdit(QDate::currentDate().addDays(-30));
    m_dateFrom->setCalendarPopup(true);
    m_dateFrom->setMinimumHeight(38);
    m_dateFrom->setStyleSheet(QStringLiteral(
        "QDateEdit{background-color:%1;border:1px solid %2;border-radius:10px;"
        "padding:4px 10px;color:%3;}")
        .arg(Style::BG_ENTRY, Style::BORDER, Style::TEXT_PRIMARY));
    connect(m_dateFrom, &QDateEdit::dateChanged, this, &HistoryPage::loadTransactions);
    filterRow->addWidget(m_dateFrom);

    auto* toLabel = new QLabel("Sampai:");
    toLabel->setStyleSheet(QStringLiteral("color: %1;").arg(Style::TEXT_SECONDARY));
    filterRow->addWidget(toLabel);
    m_dateTo = new QDateEdit(QDate::currentDate());
    m_dateTo->setCalendarPopup(true);
    m_dateTo->setMinimumHeight(38);
    m_dateTo->setStyleSheet(m_dateFrom->styleSheet());
    connect(m_dateTo, &QDateEdit::dateChanged, this, &HistoryPage::loadTransactions);
    filterRow->addWidget(m_dateTo);

    lay->addLayout(filterRow);

    // table
    m_table = new QTableWidget;
    m_table->setColumnCount(8);
    m_table->setHorizontalHeaderLabels(
        {"ID", "Invoice", "Pelanggan", "Total", "Metode", "Status", "Waktu", "Aksi"});
    m_table->horizontalHeader()->setSectionResizeMode(1, QHeaderView::Stretch);
    m_table->verticalHeader()->hide();
    m_table->setAlternatingRowColors(true);
    m_table->setEditTriggers(QAbstractItemView::NoEditTriggers);
    m_table->setSelectionBehavior(QAbstractItemView::SelectRows);
    m_table->setColumnWidth(0, 40);
    m_table->setColumnWidth(3, 110);
    m_table->setColumnWidth(4, 80);
    m_table->setColumnWidth(5, 80);
    m_table->setColumnWidth(6, 140);
    m_table->setColumnWidth(7, 140);
    lay->addWidget(m_table, 1);
}

void HistoryPage::loadTransactions() {
    auto search = m_searchEdit->text().trimmed();
    auto from   = m_dateFrom->date().toString("yyyy-MM-dd");
    auto to     = m_dateTo->date().toString("yyyy-MM-dd");
    auto txs    = Database::instance().getTransactions(search, from, to);

    m_table->setRowCount(txs.size());
    for (int i = 0; i < txs.size(); ++i) {
        auto& t = txs[i];
        m_table->setItem(i, 0, new QTableWidgetItem(t["id"].toString()));
        m_table->setItem(i, 1, new QTableWidgetItem(t["invoice_number"].toString()));
        m_table->setItem(i, 2, new QTableWidgetItem(t["customer_name"].toString()));
        m_table->setItem(i, 3, new QTableWidgetItem(
            Style::formatRupiah(t["total"].toDouble())));
        m_table->setItem(i, 4, new QTableWidgetItem(t["payment_method"].toString()));

        auto* statusItem = new QTableWidgetItem(
            t["status"].toString() == "completed" ? "Selesai" : "Void");
        statusItem->setForeground(QColor(
            t["status"].toString() == "completed" ? Style::SUCCESS : Style::DANGER));
        m_table->setItem(i, 5, statusItem);

        m_table->setItem(i, 6, new QTableWidgetItem(t["created_at"].toString()));

        // action buttons
        int txId = t["id"].toInt();
        auto status = t["status"].toString();
        auto* actWidget = new QWidget;
        auto* actLay = new QHBoxLayout(actWidget);
        actLay->setContentsMargins(4, 2, 4, 2);
        actLay->setSpacing(4);

        auto* detailBtn = new QPushButton("Detail");
        detailBtn->setFixedHeight(26);
        detailBtn->setStyleSheet(Style::flatBtnStyle() + "QPushButton{padding:2px 10px;border-radius:6px;font-size:11px;}");
        detailBtn->setCursor(Qt::PointingHandCursor);
        connect(detailBtn, &QPushButton::clicked, this, [this, txId]{ showDetail(txId); });
        actLay->addWidget(detailBtn);

        if (status == "completed" && m_userRole == "admin") {
            auto* voidBtn = new QPushButton("Void");
            voidBtn->setFixedHeight(26);
            voidBtn->setStyleSheet(Style::dangerBtnStyle() + "QPushButton{padding:2px 10px;border-radius:6px;font-size:11px;}");
            voidBtn->setCursor(Qt::PointingHandCursor);
            connect(voidBtn, &QPushButton::clicked, this, [this, txId]{ voidTransaction(txId); });
            actLay->addWidget(voidBtn);
        }
        m_table->setCellWidget(i, 7, actWidget);
    }
}

void HistoryPage::showDetail(int txId) {
    auto tx = Database::instance().getTransactionDetail(txId);
    if (tx.isEmpty()) return;
    auto items = Database::instance().getTransactionItems(txId);

    auto* dlg = new QDialog(this);
    dlg->setWindowTitle("Detail Transaksi");
    dlg->setFixedSize(500, 520);
    dlg->setStyleSheet(QStringLiteral("QDialog{background-color:%1;}").arg(Style::BG_CARD));

    auto* lay = new QVBoxLayout(dlg);
    lay->setContentsMargins(24, 20, 24, 20);
    lay->setSpacing(8);

    auto* invTitle = new QLabel(tx["invoice_number"].toString());
    invTitle->setStyleSheet(QStringLiteral(
        "font-size: 18px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    lay->addWidget(invTitle);

    auto addInfo = [&](const QString& label, const QString& value) {
        auto* row = new QHBoxLayout;
        auto* l = new QLabel(label);
        l->setStyleSheet(QStringLiteral("color: %1; font-size: 12px;").arg(Style::TEXT_SECONDARY));
        auto* v = new QLabel(value);
        v->setAlignment(Qt::AlignRight);
        v->setStyleSheet(QStringLiteral("color: %1; font-size: 12px;").arg(Style::TEXT_PRIMARY));
        row->addWidget(l);
        row->addWidget(v);
        lay->addLayout(row);
    };
    addInfo("Pelanggan:", tx["customer_name"].toString());
    addInfo("Kasir:", tx["cashier_name"].toString());
    addInfo("Metode:", tx["payment_method"].toString());
    addInfo("Waktu:", tx["created_at"].toString());
    addInfo("Status:", tx["status"].toString() == "completed" ? "Selesai" : "Void");

    auto* sep = new QFrame; sep->setFrameShape(QFrame::HLine);
    sep->setStyleSheet(QStringLiteral("color:%1;").arg(Style::BORDER));
    lay->addWidget(sep);

    // items table
    auto* itemTable = new QTableWidget;
    itemTable->setColumnCount(4);
    itemTable->setHorizontalHeaderLabels({"Produk", "Qty", "Harga", "Subtotal"});
    itemTable->horizontalHeader()->setSectionResizeMode(0, QHeaderView::Stretch);
    itemTable->verticalHeader()->hide();
    itemTable->setAlternatingRowColors(true);
    itemTable->setEditTriggers(QAbstractItemView::NoEditTriggers);
    itemTable->setRowCount(items.size());
    for (int i = 0; i < items.size(); ++i) {
        itemTable->setItem(i, 0, new QTableWidgetItem(items[i]["product_name"].toString()));
        itemTable->setItem(i, 1, new QTableWidgetItem(items[i]["quantity"].toString()));
        itemTable->setItem(i, 2, new QTableWidgetItem(
            Style::formatRupiah(items[i]["unit_price"].toDouble())));
        itemTable->setItem(i, 3, new QTableWidgetItem(
            Style::formatRupiah(items[i]["subtotal"].toDouble())));
    }
    lay->addWidget(itemTable, 1);

    auto* sep2 = new QFrame; sep2->setFrameShape(QFrame::HLine);
    sep2->setStyleSheet(QStringLiteral("color:%1;").arg(Style::BORDER));
    lay->addWidget(sep2);

    addInfo("Subtotal:", Style::formatRupiah(tx["subtotal"].toDouble()));
    addInfo("Pajak:", Style::formatRupiah(tx["tax"].toDouble()));
    if (tx["discount"].toDouble() > 0)
        addInfo("Diskon:", Style::formatRupiah(tx["discount"].toDouble()));
    addInfo("TOTAL:", Style::formatRupiah(tx["total"].toDouble()));

    auto* closeBtn = new QPushButton("Tutup");
    closeBtn->setMinimumHeight(36);
    closeBtn->setCursor(Qt::PointingHandCursor);
    connect(closeBtn, &QPushButton::clicked, dlg, &QDialog::accept);
    lay->addWidget(closeBtn);

    dlg->exec();
    dlg->deleteLater();
}

void HistoryPage::voidTransaction(int txId) {
    auto r = QMessageBox::question(this, "Konfirmasi",
        "Yakin ingin mem-void transaksi ini?\nStok akan dikembalikan.",
        QMessageBox::Yes | QMessageBox::No);
    if (r == QMessageBox::Yes) {
        Database::instance().voidTransaction(txId);
        loadTransactions();
    }
}
