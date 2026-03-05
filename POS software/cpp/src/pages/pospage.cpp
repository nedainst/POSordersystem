#include "pospage.h"
#include "../database.h"
#include "../styles.h"
#include <QHBoxLayout>
#include <QScrollArea>
#include <QFrame>
#include <QHeaderView>
#include <QMessageBox>
#include <QDialog>
#include <QDateTime>
#include <cmath>

POSPage::POSPage(int cashierId, QWidget* parent)
    : QWidget(parent), m_cashierId(cashierId)
{
    auto settings = Database::instance().getSettings();
    m_taxRate = settings.value("tax_rate", 11.0).toDouble();
    buildUI();
    loadCategories();
    loadProducts();
}

// ═══════════════════════════════════════════════════════════════
//  UI
// ═══════════════════════════════════════════════════════════════
void POSPage::buildUI() {
    auto* rootLay = new QHBoxLayout(this);
    rootLay->setContentsMargins(0, 0, 0, 0);
    rootLay->setSpacing(0);

    // ── Left: Product panel ──────────────────────────────────
    auto* leftPanel = new QFrame;
    leftPanel->setStyleSheet(QStringLiteral(
        "QFrame { background-color: %1; }").arg(Style::BG_DARK));
    auto* leftLay = new QVBoxLayout(leftPanel);
    leftLay->setContentsMargins(20, 20, 10, 20);
    leftLay->setSpacing(12);

    // title
    auto* title = new QLabel("\xF0\x9F\x9B\x92  Kasir (POS)");
    title->setStyleSheet(QStringLiteral(
        "font-size: 20px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    leftLay->addWidget(title);

    // search + category row
    auto* filterRow = new QHBoxLayout;
    m_searchEdit = new QLineEdit;
    m_searchEdit->setPlaceholderText("\xF0\x9F\x94\x8D Cari produk / barcode...");
    m_searchEdit->setMinimumHeight(40);
    connect(m_searchEdit, &QLineEdit::textChanged, this, &POSPage::onSearch);
    filterRow->addWidget(m_searchEdit, 2);

    m_categoryCombo = new QComboBox;
    m_categoryCombo->setMinimumHeight(40);
    connect(m_categoryCombo, QOverload<int>::of(&QComboBox::currentIndexChanged),
            this, &POSPage::onCategoryChanged);
    filterRow->addWidget(m_categoryCombo, 1);
    leftLay->addLayout(filterRow);

    // product grid (scrollable)
    auto* scroll = new QScrollArea;
    scroll->setWidgetResizable(true);
    scroll->setFrameShape(QFrame::NoFrame);
    scroll->setStyleSheet("background: transparent;");

    m_productContainer = new QWidget;
    m_productGrid = new QGridLayout(m_productContainer);
    m_productGrid->setSpacing(10);
    m_productGrid->setContentsMargins(0, 0, 6, 0);
    scroll->setWidget(m_productContainer);
    leftLay->addWidget(scroll, 1);

    rootLay->addWidget(leftPanel, 3);

    // ── Right: Cart panel ────────────────────────────────────
    auto* rightPanel = new QFrame;
    rightPanel->setStyleSheet(QStringLiteral(
        "QFrame { background-color: %1; border-left: 1px solid %2; }")
        .arg(Style::BG_CARD, Style::BORDER));
    auto* rightLay = new QVBoxLayout(rightPanel);
    rightLay->setContentsMargins(16, 20, 16, 16);
    rightLay->setSpacing(8);

    auto* cartTitle = new QLabel("\xF0\x9F\x93\x8B  Keranjang");
    cartTitle->setStyleSheet(QStringLiteral(
        "font-size: 16px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    rightLay->addWidget(cartTitle);

    // customer
    m_customerEdit = new QLineEdit;
    m_customerEdit->setPlaceholderText("Nama pelanggan (opsional)");
    m_customerEdit->setMinimumHeight(36);
    rightLay->addWidget(m_customerEdit);

    // cart table
    m_cartTable = new QTableWidget;
    m_cartTable->setColumnCount(5);
    m_cartTable->setHorizontalHeaderLabels({"Produk", "Harga", "Qty", "Subtotal", ""});
    m_cartTable->horizontalHeader()->setSectionResizeMode(0, QHeaderView::Stretch);
    m_cartTable->horizontalHeader()->setSectionResizeMode(4, QHeaderView::Fixed);
    m_cartTable->setColumnWidth(4, 36);
    m_cartTable->verticalHeader()->hide();
    m_cartTable->setEditTriggers(QAbstractItemView::NoEditTriggers);
    m_cartTable->setSelectionBehavior(QAbstractItemView::SelectRows);
    m_cartTable->setAlternatingRowColors(true);
    rightLay->addWidget(m_cartTable, 1);

    // totals area
    auto* totalsFrame = new QFrame;
    totalsFrame->setStyleSheet(QStringLiteral(
        "background-color: %1; border-radius: 12px; border: none;").arg(Style::BG_ENTRY));
    auto* totLay = new QVBoxLayout(totalsFrame);
    totLay->setContentsMargins(14, 10, 14, 10);
    totLay->setSpacing(4);

    auto addTotalRow = [&](const QString& label, QLabel** lbl, bool bold = false) {
        auto* row = new QHBoxLayout;
        auto* l = new QLabel(label);
        l->setStyleSheet(QStringLiteral("color: %1; font-size: 12px;").arg(Style::TEXT_SECONDARY));
        row->addWidget(l);
        *lbl = new QLabel("Rp 0");
        (*lbl)->setAlignment(Qt::AlignRight);
        (*lbl)->setStyleSheet(QStringLiteral("color: %1; font-size: %2px; font-weight: %3;")
            .arg(Style::TEXT_PRIMARY).arg(bold ? 18 : 13).arg(bold ? 700 : 400));
        row->addWidget(*lbl);
        totLay->addLayout(row);
    };
    addTotalRow("Subtotal", &m_subtotalLabel);
    addTotalRow(QStringLiteral("Pajak (%1%)").arg(m_taxRate), &m_taxLabel);
    addTotalRow("Diskon", &m_discountLabel);

    auto* sep = new QFrame;
    sep->setFrameShape(QFrame::HLine);
    sep->setStyleSheet(QStringLiteral("color: %1;").arg(Style::BORDER));
    sep->setFixedHeight(1);
    totLay->addWidget(sep);

    addTotalRow("TOTAL", &m_totalLabel, true);
    rightLay->addWidget(totalsFrame);

    // payment row
    auto* payRow = new QHBoxLayout;
    m_discountEdit = new QLineEdit("0");
    m_discountEdit->setPlaceholderText("Diskon");
    m_discountEdit->setMinimumHeight(36);
    m_discountEdit->setMaximumWidth(100);
    connect(m_discountEdit, &QLineEdit::textChanged, this, [this]{ updateTotals(); });
    payRow->addWidget(new QLabel("Diskon:"));
    payRow->addWidget(m_discountEdit);

    m_paymentCombo = new QComboBox;
    m_paymentCombo->addItems({"Cash", "Debit", "QRIS", "Transfer"});
    m_paymentCombo->setMinimumHeight(36);
    payRow->addWidget(m_paymentCombo);
    rightLay->addLayout(payRow);

    // payment amount
    auto* payAmtRow = new QHBoxLayout;
    payAmtRow->addWidget(new QLabel("Bayar:"));
    m_paymentEdit = new QLineEdit;
    m_paymentEdit->setPlaceholderText("Jumlah bayar");
    m_paymentEdit->setMinimumHeight(36);
    connect(m_paymentEdit, &QLineEdit::textChanged, this, [this]{
        double paid = m_paymentEdit->text().toDouble();
        double total = m_totalLabel->text().replace("Rp ", "").replace(".", "").replace(",", "").toDouble();
        double change = std::max(0.0, paid - total);
        m_changeLabel->setText(Style::formatRupiah(change));
    });
    payAmtRow->addWidget(m_paymentEdit, 1);
    rightLay->addLayout(payAmtRow);

    // change
    auto* changeRow = new QHBoxLayout;
    auto* changeLbl = new QLabel("Kembalian:");
    changeLbl->setStyleSheet(QStringLiteral("font-weight: 600; color: %1;").arg(Style::SUCCESS));
    changeRow->addWidget(changeLbl);
    m_changeLabel = new QLabel("Rp 0");
    m_changeLabel->setAlignment(Qt::AlignRight);
    m_changeLabel->setStyleSheet(QStringLiteral(
        "font-size: 16px; font-weight: 700; color: %1;").arg(Style::SUCCESS));
    changeRow->addWidget(m_changeLabel);
    rightLay->addLayout(changeRow);

    // quick cash buttons
    auto* quickRow = new QHBoxLayout;
    quickRow->setSpacing(6);
    for (double v : {10000, 20000, 50000, 100000}) {
        auto* btn = new QPushButton(QStringLiteral("%1rb").arg(int(v / 1000)));
        btn->setMinimumHeight(34);
        btn->setStyleSheet(Style::flatBtnStyle());
        btn->setCursor(Qt::PointingHandCursor);
        connect(btn, &QPushButton::clicked, this, [this, v]{
            m_paymentEdit->setText(QString::number(int(v)));
        });
        quickRow->addWidget(btn);
    }
    rightLay->addLayout(quickRow);

    // action buttons
    auto* actRow = new QHBoxLayout;
    actRow->setSpacing(8);

    auto* clearBtn = new QPushButton("Batal");
    clearBtn->setMinimumHeight(40);
    clearBtn->setStyleSheet(Style::dangerBtnStyle());
    clearBtn->setCursor(Qt::PointingHandCursor);
    connect(clearBtn, &QPushButton::clicked, this, &POSPage::clearCart);
    actRow->addWidget(clearBtn);

    auto* payBtn = new QPushButton("\xF0\x9F\x92\xB3  Bayar");
    payBtn->setMinimumHeight(40);
    payBtn->setStyleSheet(Style::successBtnStyle());
    payBtn->setCursor(Qt::PointingHandCursor);
    connect(payBtn, &QPushButton::clicked, this, &POSPage::processPayment);
    actRow->addWidget(payBtn, 1);
    rightLay->addLayout(actRow);

    rootLay->addWidget(rightPanel, 2);
}

// ═══════════════════════════════════════════════════════════════
//  Data loading
// ═══════════════════════════════════════════════════════════════
void POSPage::loadCategories() {
    m_categoryCombo->blockSignals(true);
    m_categoryCombo->clear();
    m_categoryCombo->addItem("Semua");
    auto cats = Database::instance().getCategories();
    for (auto& c : cats)
        m_categoryCombo->addItem(c["name"].toString());
    m_categoryCombo->blockSignals(false);
}

void POSPage::loadProducts() {
    // clear old buttons
    while (auto* item = m_productGrid->takeAt(0)) {
        if (item->widget()) item->widget()->deleteLater();
        delete item;
    }

    QString search = m_searchEdit->text().trimmed();
    QString cat    = m_categoryCombo->currentText();
    auto products  = Database::instance().getProducts(search, cat);

    int col = 0, row = 0;
    for (auto& p : products) {
        if (!p["is_active"].toBool()) continue;

        auto* card = new QPushButton;
        card->setCursor(Qt::PointingHandCursor);
        card->setMinimumSize(140, 100);
        card->setMaximumWidth(200);

        QString stockColor = p["stock"].toInt() <= 5 ? Style::DANGER : Style::SUCCESS;
        card->setText(QStringLiteral("%1\n%2\nStok: %3")
            .arg(p["name"].toString(),
                 Style::formatRupiah(p["sell_price"].toDouble()),
                 QString::number(p["stock"].toInt())));

        card->setStyleSheet(QStringLiteral(
            "QPushButton { background-color: %1; border-radius: 12px; border: none;"
            " color: %2; font-size: 12px; padding: 12px; text-align: center; }"
            "QPushButton:hover { background-color: %3; border: 1px solid %4; }")
            .arg(Style::BG_CARD, Style::TEXT_PRIMARY, Style::BG_CARD_HOVER, Style::ACCENT));

        int pid = p["id"].toInt();
        connect(card, &QPushButton::clicked, this, [this, pid]{ addToCart(pid); });

        m_productGrid->addWidget(card, row, col);
        if (++col >= 3) { col = 0; ++row; }
    }
    // spacer
    m_productGrid->setRowStretch(row + 1, 1);
}

void POSPage::onCategoryChanged() { loadProducts(); }
void POSPage::onSearch() { loadProducts(); }

// ═══════════════════════════════════════════════════════════════
//  Cart operations
// ═══════════════════════════════════════════════════════════════
void POSPage::addToCart(int productId) {
    // check if already in cart
    for (auto& item : m_cart) {
        if (item.productId == productId) {
            if (item.qty < item.maxStock) item.qty++;
            updateCartUI();
            return;
        }
    }
    auto p = Database::instance().getProduct(productId);
    if (p.isEmpty() || p["stock"].toInt() <= 0) return;
    m_cart.append({productId, p["name"].toString(),
                   p["sell_price"].toDouble(), 1, p["stock"].toInt()});
    updateCartUI();
}

void POSPage::updateCartUI() {
    m_cartTable->setRowCount(m_cart.size());
    for (int i = 0; i < m_cart.size(); ++i) {
        auto& item = m_cart[i];
        m_cartTable->setItem(i, 0, new QTableWidgetItem(item.name));
        m_cartTable->setItem(i, 1, new QTableWidgetItem(Style::formatRupiah(item.price)));

        // qty with +/- buttons
        auto* qtyWidget = new QWidget;
        auto* qtyLay = new QHBoxLayout(qtyWidget);
        qtyLay->setContentsMargins(2, 0, 2, 0);
        qtyLay->setSpacing(2);
        auto* minusBtn = new QPushButton("-");
        minusBtn->setFixedSize(24, 24);
        minusBtn->setStyleSheet(Style::dangerBtnStyle() + "QPushButton{padding:0;border-radius:6px;font-size:14px;}");
        connect(minusBtn, &QPushButton::clicked, this, [this, i]{ changeQty(i, -1); });
        auto* qtyLabel = new QLabel(QString::number(item.qty));
        qtyLabel->setAlignment(Qt::AlignCenter);
        qtyLabel->setStyleSheet(QStringLiteral("color: %1; font-weight: 600;").arg(Style::TEXT_PRIMARY));
        auto* plusBtn = new QPushButton("+");
        plusBtn->setFixedSize(24, 24);
        plusBtn->setStyleSheet(Style::successBtnStyle() + "QPushButton{padding:0;border-radius:6px;font-size:14px;}");
        connect(plusBtn, &QPushButton::clicked, this, [this, i]{ changeQty(i, 1); });
        qtyLay->addWidget(minusBtn);
        qtyLay->addWidget(qtyLabel);
        qtyLay->addWidget(plusBtn);
        m_cartTable->setCellWidget(i, 2, qtyWidget);

        m_cartTable->setItem(i, 3, new QTableWidgetItem(
            Style::formatRupiah(item.price * item.qty)));

        auto* delBtn = new QPushButton("\xE2\x9C\x95"); // ✕
        delBtn->setFixedSize(28, 28);
        delBtn->setStyleSheet(Style::dangerBtnStyle() + "QPushButton{padding:0;border-radius:8px;font-size:12px;}");
        connect(delBtn, &QPushButton::clicked, this, [this, i]{ removeFromCart(i); });
        m_cartTable->setCellWidget(i, 4, delBtn);
    }
    updateTotals();
}

void POSPage::changeQty(int row, int delta) {
    if (row < 0 || row >= m_cart.size()) return;
    m_cart[row].qty += delta;
    if (m_cart[row].qty <= 0) { removeFromCart(row); return; }
    if (m_cart[row].qty > m_cart[row].maxStock)
        m_cart[row].qty = m_cart[row].maxStock;
    updateCartUI();
}

void POSPage::removeFromCart(int row) {
    if (row < 0 || row >= m_cart.size()) return;
    m_cart.removeAt(row);
    updateCartUI();
}

void POSPage::updateTotals() {
    double subtotal = 0;
    for (auto& it : m_cart) subtotal += it.price * it.qty;
    double discount = m_discountEdit->text().toDouble();
    double taxable  = subtotal - discount;
    double tax      = std::round(taxable * m_taxRate / 100.0);
    double total    = taxable + tax;

    m_subtotalLabel->setText(Style::formatRupiah(subtotal));
    m_discountLabel->setText(Style::formatRupiah(discount));
    m_taxLabel->setText(Style::formatRupiah(tax));
    m_totalLabel->setText(Style::formatRupiah(total));
}

void POSPage::clearCart() {
    m_cart.clear();
    m_customerEdit->clear();
    m_discountEdit->setText("0");
    m_paymentEdit->clear();
    m_changeLabel->setText("Rp 0");
    updateCartUI();
}

// ═══════════════════════════════════════════════════════════════
//  Process payment
// ═══════════════════════════════════════════════════════════════
void POSPage::processPayment() {
    if (m_cart.isEmpty()) {
        QMessageBox::warning(this, "Peringatan", "Keranjang masih kosong!");
        return;
    }

    double subtotal = 0;
    for (auto& it : m_cart) subtotal += it.price * it.qty;
    double discount = m_discountEdit->text().toDouble();
    double taxable  = subtotal - discount;
    double tax      = std::round(taxable * m_taxRate / 100.0);
    double total    = taxable + tax;
    double paid     = m_paymentEdit->text().toDouble();

    QString method = m_paymentCombo->currentText();
    if (method == "Cash" && paid < total) {
        QMessageBox::warning(this, "Peringatan",
            "Jumlah pembayaran kurang dari total!");
        return;
    }
    if (method != "Cash") paid = total;

    double change = std::max(0.0, paid - total);
    QString invoice = QStringLiteral("INV-%1-%2")
        .arg(QDateTime::currentDateTime().toString("yyyyMMddHHmmss"))
        .arg(QRandomGenerator::global()->bounded(1000), 3, 10, QChar('0'));

    QString customer = m_customerEdit->text().trimmed();
    if (customer.isEmpty()) customer = "Umum";

    QVariantMap tx;
    tx["invoice_number"] = invoice;
    tx["customer_name"]  = customer;
    tx["cashier_id"]     = m_cashierId;
    tx["subtotal"]       = subtotal;
    tx["discount"]       = discount;
    tx["tax"]            = tax;
    tx["total"]          = total;
    tx["payment_method"] = method;
    tx["payment_amount"] = paid;
    tx["change_amount"]  = change;

    QVector<QVariantMap> items;
    for (auto& c : m_cart) {
        items.append({
            {"product_id",   c.productId},
            {"product_name", c.name},
            {"quantity",     c.qty},
            {"unit_price",   c.price},
            {"subtotal",     c.price * c.qty}
        });
    }

    int txId = Database::instance().createTransaction(tx, items);
    if (txId > 0) {
        tx["id"]            = txId;
        tx["change_amount"] = change;
        showReceipt(tx);
        clearCart();
        loadProducts(); // refresh stock display
    } else {
        QMessageBox::critical(this, "Error", "Gagal menyimpan transaksi!");
    }
}

void POSPage::showReceipt(const QVariantMap& tx) {
    auto* dlg = new QDialog(this);
    dlg->setWindowTitle("Struk Pembayaran");
    dlg->setFixedSize(380, 500);
    dlg->setStyleSheet(QStringLiteral(
        "QDialog { background-color: %1; }").arg(Style::BG_CARD));

    auto* lay = new QVBoxLayout(dlg);
    lay->setContentsMargins(24, 20, 24, 20);
    lay->setSpacing(6);

    auto settings = Database::instance().getSettings();

    auto* storeName = new QLabel(settings["store_name"].toString());
    storeName->setAlignment(Qt::AlignCenter);
    storeName->setStyleSheet(QStringLiteral(
        "font-size: 18px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    lay->addWidget(storeName);

    if (!settings["store_address"].toString().isEmpty()) {
        auto* addr = new QLabel(settings["store_address"].toString());
        addr->setAlignment(Qt::AlignCenter);
        addr->setStyleSheet(QStringLiteral("font-size: 11px; color: %1;").arg(Style::TEXT_MUTED));
        lay->addWidget(addr);
    }

    auto* sep1 = new QLabel("─────────────────────────");
    sep1->setAlignment(Qt::AlignCenter);
    sep1->setStyleSheet(QStringLiteral("color: %1;").arg(Style::BORDER));
    lay->addWidget(sep1);

    auto* invLbl = new QLabel(QStringLiteral("Invoice: %1").arg(tx["invoice_number"].toString()));
    invLbl->setStyleSheet(QStringLiteral("font-size: 11px; color: %1;").arg(Style::TEXT_SECONDARY));
    lay->addWidget(invLbl);

    auto* dateLbl = new QLabel(QStringLiteral("Tanggal: %1")
        .arg(QDateTime::currentDateTime().toString("dd/MM/yyyy HH:mm")));
    dateLbl->setStyleSheet(QStringLiteral("font-size: 11px; color: %1;").arg(Style::TEXT_SECONDARY));
    lay->addWidget(dateLbl);

    auto* sep2 = new QLabel("─────────────────────────");
    sep2->setAlignment(Qt::AlignCenter);
    sep2->setStyleSheet(QStringLiteral("color: %1;").arg(Style::BORDER));
    lay->addWidget(sep2);

    // items
    for (auto& c : m_cart) {
        auto* itemLay = new QHBoxLayout;
        auto* name = new QLabel(QStringLiteral("%1 x%2").arg(c.name).arg(c.qty));
        name->setStyleSheet(QStringLiteral("color: %1; font-size: 12px;").arg(Style::TEXT_PRIMARY));
        auto* price = new QLabel(Style::formatRupiah(c.price * c.qty));
        price->setAlignment(Qt::AlignRight);
        price->setStyleSheet(QStringLiteral("color: %1; font-size: 12px;").arg(Style::TEXT_PRIMARY));
        itemLay->addWidget(name);
        itemLay->addWidget(price);
        lay->addLayout(itemLay);
    }

    auto* sep3 = new QLabel("─────────────────────────");
    sep3->setAlignment(Qt::AlignCenter);
    sep3->setStyleSheet(QStringLiteral("color: %1;").arg(Style::BORDER));
    lay->addWidget(sep3);

    auto addLine = [&](const QString& label, double val, bool bold = false) {
        auto* r = new QHBoxLayout;
        auto* l = new QLabel(label);
        l->setStyleSheet(QStringLiteral("color: %1; font-size: %2px; font-weight: %3;")
            .arg(Style::TEXT_SECONDARY).arg(bold ? 14 : 12).arg(bold ? 700 : 400));
        auto* v = new QLabel(Style::formatRupiah(val));
        v->setAlignment(Qt::AlignRight);
        v->setStyleSheet(QStringLiteral("color: %1; font-size: %2px; font-weight: %3;")
            .arg(bold ? Style::ACCENT : Style::TEXT_PRIMARY)
            .arg(bold ? 14 : 12).arg(bold ? 700 : 400));
        r->addWidget(l);
        r->addWidget(v);
        lay->addLayout(r);
    };
    addLine("Subtotal", tx["subtotal"].toDouble());
    addLine("Pajak", tx["tax"].toDouble());
    if (tx["discount"].toDouble() > 0)
        addLine("Diskon", tx["discount"].toDouble());
    addLine("TOTAL", tx["total"].toDouble(), true);
    addLine("Bayar", tx["payment_amount"].toDouble());
    addLine("Kembali", tx["change_amount"].toDouble());

    lay->addSpacing(8);
    auto* footer = new QLabel(settings["receipt_footer"].toString());
    footer->setAlignment(Qt::AlignCenter);
    footer->setStyleSheet(QStringLiteral("font-size: 11px; color: %1;").arg(Style::TEXT_MUTED));
    footer->setWordWrap(true);
    lay->addWidget(footer);

    lay->addStretch();

    auto* closeBtn = new QPushButton("Tutup");
    closeBtn->setMinimumHeight(36);
    closeBtn->setCursor(Qt::PointingHandCursor);
    connect(closeBtn, &QPushButton::clicked, dlg, &QDialog::accept);
    lay->addWidget(closeBtn);

    dlg->exec();
    dlg->deleteLater();
}
