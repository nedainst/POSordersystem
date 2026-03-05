#include "settingspage.h"
#include "../database.h"
#include "../styles.h"
#include <QVBoxLayout>
#include <QHBoxLayout>
#include <QFormLayout>
#include <QPushButton>
#include <QFrame>
#include <QMessageBox>
#include <QScrollArea>
#include <QFile>
#include <QCoreApplication>
#include <QDir>

SettingsPage::SettingsPage(QWidget* parent) : QWidget(parent) {
    buildUI();
    loadSettings();
}

void SettingsPage::buildUI() {
    auto* scroll = new QScrollArea(this);
    scroll->setWidgetResizable(true);
    scroll->setFrameShape(QFrame::NoFrame);
    scroll->setStyleSheet("background: transparent;");

    auto* content = new QWidget;
    auto* lay = new QVBoxLayout(content);
    lay->setContentsMargins(28, 24, 28, 24);
    lay->setSpacing(20);

    auto* title = new QLabel("\xE2\x9A\x99\xEF\xB8\x8F  Pengaturan");
    title->setStyleSheet(QStringLiteral(
        "font-size: 22px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    lay->addWidget(title);

    // ── Store info card ──────────────────────────────────────
    auto* storeCard = new QFrame;
    storeCard->setStyleSheet(QStringLiteral(
        "QFrame{background-color:%1;border-radius:16px;}").arg(Style::BG_CARD));
    storeCard->setMaximumWidth(600);
    auto* storeForm = new QFormLayout(storeCard);
    storeForm->setContentsMargins(24, 20, 24, 20);
    storeForm->setSpacing(12);

    auto* storeTitle = new QLabel("Informasi Toko");
    storeTitle->setStyleSheet(QStringLiteral(
        "font-size: 16px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    storeForm->addRow(storeTitle);

    m_storeName = new QLineEdit;    m_storeName->setMinimumHeight(36);
    m_storeAddress = new QLineEdit; m_storeAddress->setMinimumHeight(36);
    m_storePhone = new QLineEdit;   m_storePhone->setMinimumHeight(36);
    storeForm->addRow("Nama Toko:", m_storeName);
    storeForm->addRow("Alamat:", m_storeAddress);
    storeForm->addRow("Telepon:", m_storePhone);
    lay->addWidget(storeCard);

    // ── Tax & receipt card ───────────────────────────────────
    auto* taxCard = new QFrame;
    taxCard->setStyleSheet(QStringLiteral(
        "QFrame{background-color:%1;border-radius:16px;}").arg(Style::BG_CARD));
    taxCard->setMaximumWidth(600);
    auto* taxForm = new QFormLayout(taxCard);
    taxForm->setContentsMargins(24, 20, 24, 20);
    taxForm->setSpacing(12);

    auto* taxTitle = new QLabel("Pajak & Struk");
    taxTitle->setStyleSheet(QStringLiteral(
        "font-size: 16px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    taxForm->addRow(taxTitle);

    m_taxRate = new QLineEdit;        m_taxRate->setMinimumHeight(36);
    m_receiptFooter = new QLineEdit;  m_receiptFooter->setMinimumHeight(36);
    m_lowStockThreshold = new QLineEdit; m_lowStockThreshold->setMinimumHeight(36);
    taxForm->addRow("Pajak (%):", m_taxRate);
    taxForm->addRow("Footer Struk:", m_receiptFooter);
    taxForm->addRow("Threshold Stok Rendah:", m_lowStockThreshold);
    lay->addWidget(taxCard);

    // ── Save button ──────────────────────────────────────────
    auto* saveBtn = new QPushButton("Simpan Pengaturan");
    saveBtn->setMinimumHeight(42);
    saveBtn->setMaximumWidth(600);
    saveBtn->setCursor(Qt::PointingHandCursor);
    connect(saveBtn, &QPushButton::clicked, this, &SettingsPage::saveSettings);
    lay->addWidget(saveBtn);

    // ── App info card ────────────────────────────────────────
    auto* infoCard = new QFrame;
    infoCard->setStyleSheet(QStringLiteral(
        "QFrame{background-color:%1;border-radius:16px;}").arg(Style::BG_CARD));
    infoCard->setMaximumWidth(600);
    auto* infoLay = new QVBoxLayout(infoCard);
    infoLay->setContentsMargins(24, 18, 24, 18);
    infoLay->setSpacing(8);
    auto* appTitle = new QLabel("Tentang Aplikasi");
    appTitle->setStyleSheet(QStringLiteral(
        "font-size: 16px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    infoLay->addWidget(appTitle);

    auto addInfo = [&](const QString& label, const QString& value) {
        auto* row = new QHBoxLayout;
        auto* l = new QLabel(label);
        l->setStyleSheet(QStringLiteral("color:%1;font-size:12px;").arg(Style::TEXT_SECONDARY));
        auto* v = new QLabel(value);
        v->setStyleSheet(QStringLiteral("color:%1;font-size:12px;").arg(Style::TEXT_PRIMARY));
        row->addWidget(l);
        row->addWidget(v);
        row->addStretch();
        infoLay->addLayout(row);
    };
    addInfo("Aplikasi:", "POS System v2.0");
    addInfo("Framework:", "Qt 6 (C++)");
    addInfo("Database:", "SQLite");
    addInfo("Developer:", "POS Team");
    lay->addWidget(infoCard);

    // ── Danger zone ──────────────────────────────────────────
    auto* dangerCard = new QFrame;
    dangerCard->setStyleSheet(QStringLiteral(
        "QFrame{background-color:%1;border:1px solid %2;border-radius:16px;}")
        .arg(Style::BG_CARD, Style::DANGER));
    dangerCard->setMaximumWidth(600);
    auto* dangerLay = new QVBoxLayout(dangerCard);
    dangerLay->setContentsMargins(24, 18, 24, 18);
    dangerLay->setSpacing(10);
    auto* dangerTitle = new QLabel("\xE2\x9A\xA0\xEF\xB8\x8F  Zona Berbahaya");
    dangerTitle->setStyleSheet(QStringLiteral(
        "font-size: 16px; font-weight: 700; color: %1;").arg(Style::DANGER));
    dangerLay->addWidget(dangerTitle);
    auto* dangerDesc = new QLabel("Reset database akan menghapus semua data dan mengembalikan ke default.");
    dangerDesc->setStyleSheet(QStringLiteral("color:%1;font-size:12px;").arg(Style::TEXT_SECONDARY));
    dangerDesc->setWordWrap(true);
    dangerLay->addWidget(dangerDesc);
    auto* resetBtn = new QPushButton("Reset Database");
    resetBtn->setMinimumHeight(38);
    resetBtn->setStyleSheet(Style::dangerBtnStyle());
    resetBtn->setCursor(Qt::PointingHandCursor);
    connect(resetBtn, &QPushButton::clicked, this, &SettingsPage::resetDatabase);
    dangerLay->addWidget(resetBtn);
    lay->addWidget(dangerCard);

    lay->addStretch();

    scroll->setWidget(content);
    auto* rootLay = new QVBoxLayout(this);
    rootLay->setContentsMargins(0, 0, 0, 0);
    rootLay->addWidget(scroll);
}

void SettingsPage::loadSettings() {
    auto s = Database::instance().getSettings();
    m_storeName->setText(s["store_name"].toString());
    m_storeAddress->setText(s["store_address"].toString());
    m_storePhone->setText(s["store_phone"].toString());
    m_taxRate->setText(QString::number(s["tax_rate"].toDouble()));
    m_receiptFooter->setText(s["receipt_footer"].toString());
    m_lowStockThreshold->setText(QString::number(s["low_stock_threshold"].toInt()));
}

void SettingsPage::saveSettings() {
    QVariantMap s;
    s["store_name"]          = m_storeName->text().trimmed();
    s["store_address"]       = m_storeAddress->text().trimmed();
    s["store_phone"]         = m_storePhone->text().trimmed();
    s["tax_rate"]            = m_taxRate->text().toDouble();
    s["receipt_footer"]      = m_receiptFooter->text().trimmed();
    s["low_stock_threshold"] = m_lowStockThreshold->text().toInt();

    if (Database::instance().updateSettings(s))
        QMessageBox::information(this, "Berhasil", "Pengaturan berhasil disimpan!");
    else
        QMessageBox::critical(this, "Error", "Gagal menyimpan pengaturan!");
}

void SettingsPage::resetDatabase() {
    auto r = QMessageBox::warning(this, "Konfirmasi",
        "PERINGATAN: Semua data akan dihapus!\n\nYakin ingin mereset database?",
        QMessageBox::Yes | QMessageBox::No);
    if (r != QMessageBox::Yes) return;

    Database::instance().close();
    QString dbPath = QDir(QCoreApplication::applicationDirPath()).filePath("pos_database.db");
    QFile::remove(dbPath);
    Database::instance().initialize();
    loadSettings();
    QMessageBox::information(this, "Berhasil", "Database berhasil direset!");
}
