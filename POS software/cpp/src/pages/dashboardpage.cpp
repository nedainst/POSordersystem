#include "dashboardpage.h"
#include "../database.h"
#include "../styles.h"
#include <QHBoxLayout>
#include <QFrame>
#include <QPainter>
#include <QHeaderView>
#include <QDate>
#include <algorithm>

// ── Tiny bar-chart widget ────────────────────────────────────
class BarChart : public QWidget {
public:
    struct Bar { QString label; double value; };
    QVector<Bar> bars;
    explicit BarChart(QWidget* p = nullptr) : QWidget(p) {
        setMinimumHeight(200);
        setStyleSheet("background: transparent;");
    }
protected:
    void paintEvent(QPaintEvent*) override {
        if (bars.isEmpty()) return;
        QPainter p(this);
        p.setRenderHint(QPainter::Antialiasing);
        double maxVal = 1;
        for (auto& b : bars) maxVal = std::max(maxVal, b.value);

        int n = bars.size();
        int margin = 40;
        int chartW = width() - margin * 2;
        int chartH = height() - margin * 2;
        int barW   = std::min(chartW / n - 8, 50);
        int gap    = (chartW - barW * n) / (n + 1);

        // axis
        p.setPen(QColor(Style::BORDER));
        p.drawLine(margin, height() - margin, width() - margin, height() - margin);

        for (int i = 0; i < n; ++i) {
            double ratio = bars[i].value / maxVal;
            int h = int(ratio * chartH);
            int x = margin + gap + i * (barW + gap);
            int y = height() - margin - h;

            QLinearGradient grad(x, y, x, y + h);
            grad.setColorAt(0, QColor(Style::ACCENT_LIGHT));
            grad.setColorAt(1, QColor(Style::ACCENT));
            p.setBrush(grad);
            p.setPen(Qt::NoPen);
            p.drawRoundedRect(x, y, barW, h, 4, 4);

            // label
            p.setPen(QColor(Style::TEXT_MUTED));
            p.setFont(QFont("Segoe UI", 8));
            p.drawText(QRect(x - 5, height() - margin + 4, barW + 10, 20),
                       Qt::AlignCenter, bars[i].label);

            // value on top
            p.setPen(QColor(Style::TEXT_SECONDARY));
            QString val = bars[i].value >= 1000000
                ? QStringLiteral("%1jt").arg(bars[i].value / 1000000.0, 0, 'f', 1)
                : QStringLiteral("%1rb").arg(bars[i].value / 1000.0, 0, 'f', 0);
            p.drawText(QRect(x - 10, y - 18, barW + 20, 16), Qt::AlignCenter, val);
        }
    }
};

// ═══════════════════════════════════════════════════════════════
DashboardPage::DashboardPage(QWidget* parent) : QWidget(parent) {
    buildUI();
    loadData();
}

void DashboardPage::refresh() { loadData(); }

// ═══════════════════════════════════════════════════════════════
//  UI
// ═══════════════════════════════════════════════════════════════
void DashboardPage::buildUI() {
    auto* scroll = new QScrollArea(this);
    scroll->setWidgetResizable(true);
    scroll->setFrameShape(QFrame::NoFrame);
    scroll->setStyleSheet("background: transparent;");

    auto* content = new QWidget;
    auto* lay = new QVBoxLayout(content);
    lay->setContentsMargins(28, 24, 28, 24);
    lay->setSpacing(20);

    // header
    auto* header = new QLabel("Dashboard");
    header->setStyleSheet(QStringLiteral(
        "font-size: 22px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    lay->addWidget(header);

    buildStatsCards(lay);
    buildChart(lay);
    buildBottomSection(lay);

    scroll->setWidget(content);

    auto* rootLay = new QVBoxLayout(this);
    rootLay->setContentsMargins(0, 0, 0, 0);
    rootLay->addWidget(scroll);
}

void DashboardPage::buildStatsCards(QLayout* parent) {
    auto* row = new QHBoxLayout;
    row->setSpacing(14);

    struct CardDef { QString title; QString icon; const char* accentColor; QLabel** label; };
    CardDef defs[] = {
        {"Penjualan Hari Ini", "\xF0\x9F\x92\xB5", Style::ACCENT,  &m_todaySales},
        {"Penjualan Bulan Ini","\xF0\x9F\x93\x85", Style::SUCCESS, &m_monthSales},
        {"Total Produk",       "\xF0\x9F\x93\xA6", Style::WARNING, &m_totalProducts},
        {"Stok Rendah",        "\xE2\x9A\xA0\xEF\xB8\x8F", Style::DANGER,  &m_lowStock},
        {"Transaksi Hari Ini", "\xF0\x9F\x9B\x92", Style::ACCENT_LIGHT, &m_todayTx},
    };

    for (auto& d : defs) {
        auto* card = new QFrame;
        card->setStyleSheet(QStringLiteral(
            "QFrame { background-color: %1; border-radius: 16px; border: none; }").arg(Style::BG_CARD));
        card->setMinimumHeight(110);

        auto* cLay = new QVBoxLayout(card);
        cLay->setContentsMargins(18, 16, 18, 16);
        cLay->setSpacing(6);

        // accent bar
        auto* bar = new QFrame;
        bar->setFixedSize(40, 4);
        bar->setStyleSheet(Style::accentBarStyle(d.accentColor));
        cLay->addWidget(bar);

        auto* titleRow = new QHBoxLayout;
        auto* icon = new QLabel(d.icon);
        icon->setStyleSheet("font-size: 18px;");
        titleRow->addWidget(icon);
        auto* titleLbl = new QLabel(d.title);
        titleLbl->setStyleSheet(QStringLiteral(
            "font-size: 11px; color: %1;").arg(Style::TEXT_SECONDARY));
        titleRow->addWidget(titleLbl);
        titleRow->addStretch();
        cLay->addLayout(titleRow);

        *d.label = new QLabel("...");
        (*d.label)->setStyleSheet(QStringLiteral(
            "font-size: 20px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
        cLay->addWidget(*d.label);

        row->addWidget(card);
    }
    static_cast<QVBoxLayout*>(parent)->addLayout(row);
}

void DashboardPage::buildChart(QLayout* parent) {
    auto* card = new QFrame;
    card->setStyleSheet(QStringLiteral(
        "QFrame { background-color: %1; border-radius: 16px; }").arg(Style::BG_CARD));
    auto* cLay = new QVBoxLayout(card);
    cLay->setContentsMargins(20, 16, 20, 16);

    auto* title = new QLabel("\xF0\x9F\x93\x8A  Penjualan 7 Hari Terakhir");
    title->setStyleSheet(QStringLiteral(
        "font-size: 14px; font-weight: 600; color: %1;").arg(Style::TEXT_PRIMARY));
    cLay->addWidget(title);

    m_chartWidget = new BarChart;
    cLay->addWidget(m_chartWidget);

    static_cast<QVBoxLayout*>(parent)->addWidget(card);
}

void DashboardPage::buildBottomSection(QLayout* parent) {
    auto* row = new QHBoxLayout;
    row->setSpacing(14);

    // ── Recent transactions ──────────────────────────────────
    {
        auto* card = new QFrame;
        card->setStyleSheet(QStringLiteral(
            "QFrame { background-color: %1; border-radius: 16px; }").arg(Style::BG_CARD));
        auto* cLay = new QVBoxLayout(card);
        cLay->setContentsMargins(18, 14, 18, 14);

        auto* t = new QLabel("\xF0\x9F\x93\x9C  Transaksi Terakhir");
        t->setStyleSheet(QStringLiteral(
            "font-size: 14px; font-weight: 600; color: %1;").arg(Style::TEXT_PRIMARY));
        cLay->addWidget(t);

        m_recentTable = new QTableWidget;
        m_recentTable->setColumnCount(4);
        m_recentTable->setHorizontalHeaderLabels({"Invoice", "Pelanggan", "Total", "Waktu"});
        m_recentTable->horizontalHeader()->setStretchLastSection(true);
        m_recentTable->horizontalHeader()->setSectionResizeMode(QHeaderView::Stretch);
        m_recentTable->verticalHeader()->hide();
        m_recentTable->setAlternatingRowColors(true);
        m_recentTable->setEditTriggers(QAbstractItemView::NoEditTriggers);
        m_recentTable->setSelectionBehavior(QAbstractItemView::SelectRows);
        cLay->addWidget(m_recentTable);
        row->addWidget(card, 2);
    }

    // ── Right column ─────────────────────────────────────────
    auto* rightCol = new QVBoxLayout;
    rightCol->setSpacing(14);

    // Top products
    {
        auto* card = new QFrame;
        card->setStyleSheet(QStringLiteral(
            "QFrame { background-color: %1; border-radius: 16px; }").arg(Style::BG_CARD));
        auto* cLay = new QVBoxLayout(card);
        cLay->setContentsMargins(18, 14, 18, 14);

        auto* t = new QLabel("\xF0\x9F\x8F\x86  Produk Terlaris");
        t->setStyleSheet(QStringLiteral(
            "font-size: 14px; font-weight: 600; color: %1;").arg(Style::TEXT_PRIMARY));
        cLay->addWidget(t);

        m_topTable = new QTableWidget;
        m_topTable->setColumnCount(3);
        m_topTable->setHorizontalHeaderLabels({"Produk", "Qty", "Revenue"});
        m_topTable->horizontalHeader()->setStretchLastSection(true);
        m_topTable->horizontalHeader()->setSectionResizeMode(QHeaderView::Stretch);
        m_topTable->verticalHeader()->hide();
        m_topTable->setAlternatingRowColors(true);
        m_topTable->setEditTriggers(QAbstractItemView::NoEditTriggers);
        cLay->addWidget(m_topTable);
        rightCol->addWidget(card);
    }

    // Low stock
    {
        auto* card = new QFrame;
        card->setStyleSheet(QStringLiteral(
            "QFrame { background-color: %1; border-radius: 16px; }").arg(Style::BG_CARD));
        auto* cLay = new QVBoxLayout(card);
        cLay->setContentsMargins(18, 14, 18, 14);

        auto* t = new QLabel("\xE2\x9A\xA0\xEF\xB8\x8F  Stok Rendah");
        t->setStyleSheet(QStringLiteral(
            "font-size: 14px; font-weight: 600; color: %1;").arg(Style::TEXT_PRIMARY));
        cLay->addWidget(t);

        m_lowStockTable = new QTableWidget;
        m_lowStockTable->setColumnCount(3);
        m_lowStockTable->setHorizontalHeaderLabels({"Produk", "Stok", "Min"});
        m_lowStockTable->horizontalHeader()->setStretchLastSection(true);
        m_lowStockTable->horizontalHeader()->setSectionResizeMode(QHeaderView::Stretch);
        m_lowStockTable->verticalHeader()->hide();
        m_lowStockTable->setAlternatingRowColors(true);
        m_lowStockTable->setEditTriggers(QAbstractItemView::NoEditTriggers);
        cLay->addWidget(m_lowStockTable);
        rightCol->addWidget(card);
    }

    row->addLayout(rightCol, 1);
    static_cast<QVBoxLayout*>(parent)->addLayout(row);
}

// ═══════════════════════════════════════════════════════════════
//  Data
// ═══════════════════════════════════════════════════════════════
void DashboardPage::loadData() {
    auto& db = Database::instance();

    // stats cards
    auto stats = db.getDashboardStats();
    m_todaySales->setText(Style::formatRupiah(stats["today_sales"].toDouble()));
    m_monthSales->setText(Style::formatRupiah(stats["month_sales"].toDouble()));
    m_totalProducts->setText(QString::number(stats["total_products"].toInt()));
    m_lowStock->setText(QString::number(stats["low_stock"].toInt()));
    m_todayTx->setText(QString::number(stats["today_tx"].toInt()));

    // chart
    auto weekly = db.getWeeklySales();
    auto* chart = static_cast<BarChart*>(m_chartWidget);
    chart->bars.clear();
    for (auto& r : weekly) {
        auto d = QDate::fromString(r["date"].toString(), "yyyy-MM-dd");
        chart->bars.append({d.toString("dd MMM"), r["total"].toDouble()});
    }
    chart->update();

    // recent transactions
    auto recent = db.getRecentTransactions(8);
    m_recentTable->setRowCount(recent.size());
    for (int i = 0; i < recent.size(); ++i) {
        auto& r = recent[i];
        m_recentTable->setItem(i, 0, new QTableWidgetItem(r["invoice_number"].toString()));
        m_recentTable->setItem(i, 1, new QTableWidgetItem(r["customer_name"].toString()));
        m_recentTable->setItem(i, 2, new QTableWidgetItem(Style::formatRupiah(r["total"].toDouble())));
        m_recentTable->setItem(i, 3, new QTableWidgetItem(r["created_at"].toString().mid(5, 11)));
    }

    // top products
    auto top = db.getTopProducts(5);
    m_topTable->setRowCount(top.size());
    for (int i = 0; i < top.size(); ++i) {
        m_topTable->setItem(i, 0, new QTableWidgetItem(top[i]["product_name"].toString()));
        m_topTable->setItem(i, 1, new QTableWidgetItem(QString::number(top[i]["qty"].toInt())));
        m_topTable->setItem(i, 2, new QTableWidgetItem(Style::formatRupiah(top[i]["revenue"].toDouble())));
    }

    // low stock
    auto low = db.getLowStockProducts();
    m_lowStockTable->setRowCount(std::min(low.size(), qsizetype(5)));
    for (int i = 0; i < std::min(low.size(), qsizetype(5)); ++i) {
        m_lowStockTable->setItem(i, 0, new QTableWidgetItem(low[i]["name"].toString()));
        auto* stockItem = new QTableWidgetItem(QString::number(low[i]["stock"].toInt()));
        stockItem->setForeground(QColor(Style::DANGER));
        m_lowStockTable->setItem(i, 1, stockItem);
        m_lowStockTable->setItem(i, 2, new QTableWidgetItem(QString::number(low[i]["min_stock"].toInt())));
    }
}
