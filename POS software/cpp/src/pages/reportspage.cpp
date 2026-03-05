#include "reportspage.h"
#include "../database.h"
#include "../styles.h"
#include <QVBoxLayout>
#include <QHBoxLayout>
#include <QHeaderView>
#include <QPushButton>
#include <QFrame>
#include <QPainter>
#include <QDate>
#include <algorithm>

// ── Small bar chart for reports ──────────────────────────────
class ReportChart : public QWidget {
public:
    struct Bar { QString label; double value; };
    QVector<Bar> bars;
    explicit ReportChart(QWidget* p = nullptr) : QWidget(p) {
        setMinimumHeight(180);
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
        int barW   = std::max(4, std::min(chartW / std::max(n, 1) - 4, 30));
        int gap    = std::max(2, (chartW - barW * n) / (n + 1));

        p.setPen(QColor(Style::BORDER));
        p.drawLine(margin, height() - margin, width() - margin, height() - margin);

        for (int i = 0; i < n; ++i) {
            double ratio = bars[i].value / maxVal;
            int h = std::max(2, int(ratio * chartH));
            int x = margin + gap + i * (barW + gap);
            int y = height() - margin - h;

            QLinearGradient grad(x, y, x, y + h);
            grad.setColorAt(0, QColor(Style::SUCCESS));
            grad.setColorAt(1, QColor("#1a9c50"));
            p.setBrush(grad);
            p.setPen(Qt::NoPen);
            p.drawRoundedRect(x, y, barW, h, 3, 3);

            if (n <= 15) {
                p.setPen(QColor(Style::TEXT_MUTED));
                p.setFont(QFont("Segoe UI", 7));
                p.drawText(QRect(x - 5, height() - margin + 2, barW + 10, 16),
                           Qt::AlignCenter, bars[i].label);
            }
        }
    }
};

// ═══════════════════════════════════════════════════════════════
ReportsPage::ReportsPage(QWidget* parent) : QWidget(parent) {
    buildUI();
    loadSalesReport();
}

void ReportsPage::buildUI() {
    auto* lay = new QVBoxLayout(this);
    lay->setContentsMargins(28, 24, 28, 24);
    lay->setSpacing(16);

    auto* title = new QLabel("\xF0\x9F\x93\x88  Laporan");
    title->setStyleSheet(QStringLiteral(
        "font-size: 22px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    lay->addWidget(title);

    m_tabs = new QTabWidget;
    lay->addWidget(m_tabs, 1);

    auto makeDateFilter = [this](QDateEdit*& from, QDateEdit*& to,
                                  const std::function<void()>& loadFn) -> QHBoxLayout* {
        auto* row = new QHBoxLayout;
        row->setSpacing(8);
        auto* fLbl = new QLabel("Dari:");
        fLbl->setStyleSheet(QStringLiteral("color:%1;").arg(Style::TEXT_SECONDARY));
        row->addWidget(fLbl);
        from = new QDateEdit(QDate::currentDate().addDays(-30));
        from->setCalendarPopup(true);
        from->setMinimumHeight(36);
        from->setStyleSheet(QStringLiteral(
            "QDateEdit{background-color:%1;border:1px solid %2;border-radius:10px;"
            "padding:4px 10px;color:%3;}")
            .arg(Style::BG_ENTRY, Style::BORDER, Style::TEXT_PRIMARY));
        row->addWidget(from);

        auto* tLbl = new QLabel("Sampai:");
        tLbl->setStyleSheet(QStringLiteral("color:%1;").arg(Style::TEXT_SECONDARY));
        row->addWidget(tLbl);
        to = new QDateEdit(QDate::currentDate());
        to->setCalendarPopup(true);
        to->setMinimumHeight(36);
        to->setStyleSheet(from->styleSheet());
        row->addWidget(to);

        auto* refreshBtn = new QPushButton("Refresh");
        refreshBtn->setMinimumHeight(36);
        refreshBtn->setCursor(Qt::PointingHandCursor);
        connect(refreshBtn, &QPushButton::clicked, this, loadFn);
        row->addWidget(refreshBtn);
        row->addStretch();
        return row;
    };

    // ── Tab 1: Sales Report ──────────────────────────────────
    {
        auto* tab = new QWidget;
        auto* tLay = new QVBoxLayout(tab);
        tLay->setContentsMargins(8, 12, 8, 8);

        tLay->addLayout(makeDateFilter(m_salesFrom, m_salesTo,
            [this]{ loadSalesReport(); }));

        m_salesChart = new ReportChart;
        tLay->addWidget(m_salesChart);

        m_salesTable = new QTableWidget;
        m_salesTable->setColumnCount(5);
        m_salesTable->setHorizontalHeaderLabels(
            {"Tanggal", "Transaksi", "Subtotal", "Pajak", "Total"});
        m_salesTable->horizontalHeader()->setSectionResizeMode(0, QHeaderView::Stretch);
        m_salesTable->verticalHeader()->hide();
        m_salesTable->setAlternatingRowColors(true);
        m_salesTable->setEditTriggers(QAbstractItemView::NoEditTriggers);
        tLay->addWidget(m_salesTable, 1);

        m_tabs->addTab(tab, "Laporan Penjualan");
    }

    // ── Tab 2: Product Report ────────────────────────────────
    {
        auto* tab = new QWidget;
        auto* tLay = new QVBoxLayout(tab);
        tLay->setContentsMargins(8, 12, 8, 8);

        tLay->addLayout(makeDateFilter(m_prodFrom, m_prodTo,
            [this]{ loadProductReport(); }));

        m_prodTable = new QTableWidget;
        m_prodTable->setColumnCount(4);
        m_prodTable->setHorizontalHeaderLabels(
            {"Produk", "Terjual", "Pendapatan", "Laba"});
        m_prodTable->horizontalHeader()->setSectionResizeMode(0, QHeaderView::Stretch);
        m_prodTable->verticalHeader()->hide();
        m_prodTable->setAlternatingRowColors(true);
        m_prodTable->setEditTriggers(QAbstractItemView::NoEditTriggers);
        tLay->addWidget(m_prodTable, 1);

        m_tabs->addTab(tab, "Laporan Produk");
    }

    // ── Tab 3: Profit Report ─────────────────────────────────
    {
        auto* tab = new QWidget;
        auto* tLay = new QVBoxLayout(tab);
        tLay->setContentsMargins(8, 12, 8, 8);
        tLay->setSpacing(14);

        tLay->addLayout(makeDateFilter(m_profitFrom, m_profitTo,
            [this]{ loadProfitReport(); }));

        auto* cardsRow = new QHBoxLayout;
        cardsRow->setSpacing(12);

        auto makeCard = [&](const QString& label, QLabel** valLbl, const char* color) {
            auto* card = new QFrame;
            card->setStyleSheet(QStringLiteral(
                "QFrame{background-color:%1;border-radius:14px;}").arg(Style::BG_CARD));
            auto* cLay = new QVBoxLayout(card);
            cLay->setContentsMargins(16, 14, 16, 14);
            cLay->setSpacing(4);
            auto* bar = new QFrame;
            bar->setFixedSize(35, 3);
            bar->setStyleSheet(Style::accentBarStyle(color));
            cLay->addWidget(bar);
            auto* l = new QLabel(label);
            l->setStyleSheet(QStringLiteral("font-size:11px;color:%1;").arg(Style::TEXT_SECONDARY));
            cLay->addWidget(l);
            *valLbl = new QLabel("...");
            (*valLbl)->setStyleSheet(QStringLiteral(
                "font-size:17px;font-weight:700;color:%1;").arg(Style::TEXT_PRIMARY));
            cLay->addWidget(*valLbl);
            cardsRow->addWidget(card);
        };

        makeCard("Pendapatan", &m_revenueLabel, Style::ACCENT);
        makeCard("HPP", &m_cogsLabel, Style::WARNING);
        makeCard("Laba Kotor", &m_grossLabel, Style::SUCCESS);
        makeCard("Pengeluaran", &m_expenseLabel, Style::DANGER);
        makeCard("Laba Bersih", &m_netLabel, Style::ACCENT_LIGHT);
        tLay->addLayout(cardsRow);

        auto* infoRow = new QHBoxLayout;
        m_marginLabel = new QLabel("Margin: -");
        m_marginLabel->setStyleSheet(QStringLiteral("color:%1;font-size:13px;").arg(Style::TEXT_SECONDARY));
        infoRow->addWidget(m_marginLabel);
        m_txCountLabel = new QLabel("Transaksi: -");
        m_txCountLabel->setStyleSheet(QStringLiteral("color:%1;font-size:13px;").arg(Style::TEXT_SECONDARY));
        infoRow->addWidget(m_txCountLabel);
        infoRow->addStretch();
        tLay->addLayout(infoRow);
        tLay->addStretch();

        m_tabs->addTab(tab, "Analisis Profit");
    }

    connect(m_tabs, &QTabWidget::currentChanged, this, [this](int idx){
        if (idx == 0) loadSalesReport();
        else if (idx == 1) loadProductReport();
        else if (idx == 2) loadProfitReport();
    });
}

void ReportsPage::loadSalesReport() {
    auto from = m_salesFrom->date().toString("yyyy-MM-dd");
    auto to   = m_salesTo->date().toString("yyyy-MM-dd");
    auto data = Database::instance().getSalesReport(from, to);

    m_salesTable->setRowCount(data.size());
    auto* chart = static_cast<ReportChart*>(m_salesChart);
    chart->bars.clear();

    for (int i = 0; i < data.size(); ++i) {
        auto& r = data[i];
        auto d = QDate::fromString(r["date"].toString(), "yyyy-MM-dd");
        m_salesTable->setItem(i, 0, new QTableWidgetItem(d.toString("dd MMM yyyy")));
        m_salesTable->setItem(i, 1, new QTableWidgetItem(r["tx_count"].toString()));
        m_salesTable->setItem(i, 2, new QTableWidgetItem(
            Style::formatRupiah(r["subtotal"].toDouble())));
        m_salesTable->setItem(i, 3, new QTableWidgetItem(
            Style::formatRupiah(r["tax"].toDouble())));
        m_salesTable->setItem(i, 4, new QTableWidgetItem(
            Style::formatRupiah(r["total"].toDouble())));
        chart->bars.append({d.toString("dd"), r["total"].toDouble()});
    }
    chart->update();
}

void ReportsPage::loadProductReport() {
    auto from = m_prodFrom->date().toString("yyyy-MM-dd");
    auto to   = m_prodTo->date().toString("yyyy-MM-dd");
    auto data = Database::instance().getProductReport(from, to);

    m_prodTable->setRowCount(data.size());
    for (int i = 0; i < data.size(); ++i) {
        auto& r = data[i];
        m_prodTable->setItem(i, 0, new QTableWidgetItem(r["product_name"].toString()));
        m_prodTable->setItem(i, 1, new QTableWidgetItem(r["qty"].toString()));
        m_prodTable->setItem(i, 2, new QTableWidgetItem(
            Style::formatRupiah(r["revenue"].toDouble())));
        auto* profitItem = new QTableWidgetItem(
            Style::formatRupiah(r["profit"].toDouble()));
        profitItem->setForeground(QColor(
            r["profit"].toDouble() >= 0 ? Style::SUCCESS : Style::DANGER));
        m_prodTable->setItem(i, 3, profitItem);
    }
}

void ReportsPage::loadProfitReport() {
    auto from = m_profitFrom->date().toString("yyyy-MM-dd");
    auto to   = m_profitTo->date().toString("yyyy-MM-dd");
    auto data = Database::instance().getProfitReport(from, to);

    m_revenueLabel->setText(Style::formatRupiah(data["revenue"].toDouble()));
    m_cogsLabel->setText(Style::formatRupiah(data["cogs"].toDouble()));
    m_grossLabel->setText(Style::formatRupiah(data["gross_profit"].toDouble()));
    m_expenseLabel->setText(Style::formatRupiah(data["expenses"].toDouble()));
    m_netLabel->setText(Style::formatRupiah(data["net_profit"].toDouble()));

    double margin = data["revenue"].toDouble() > 0
        ? (data["net_profit"].toDouble() / data["revenue"].toDouble() * 100) : 0;
    m_marginLabel->setText(QStringLiteral("Margin: %1%").arg(margin, 0, 'f', 1));
    m_txCountLabel->setText(QStringLiteral("Transaksi: %1").arg(data["tx_count"].toInt()));
}
