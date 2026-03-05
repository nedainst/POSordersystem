#include "productspage.h"
#include "../database.h"
#include "../styles.h"
#include <QVBoxLayout>
#include <QHBoxLayout>
#include <QHeaderView>
#include <QMessageBox>
#include <QDialog>
#include <QFormLayout>
#include <QFrame>
#include <QScrollArea>
#include <QLabel>

ProductsPage::ProductsPage(QWidget* parent) : QWidget(parent) {
    buildUI();
    loadProducts();
}

void ProductsPage::buildUI() {
    auto* lay = new QVBoxLayout(this);
    lay->setContentsMargins(28, 24, 28, 24);
    lay->setSpacing(16);

    // header
    auto* headerRow = new QHBoxLayout;
    auto* title = new QLabel("\xF0\x9F\x93\xA6  Manajemen Produk");
    title->setStyleSheet(QStringLiteral(
        "font-size: 22px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    headerRow->addWidget(title);
    headerRow->addStretch();

    auto* catBtn = new QPushButton("Kategori");
    catBtn->setMinimumHeight(36);
    catBtn->setStyleSheet(Style::flatBtnStyle());
    catBtn->setCursor(Qt::PointingHandCursor);
    connect(catBtn, &QPushButton::clicked, this, &ProductsPage::showCategoryDialog);
    headerRow->addWidget(catBtn);

    auto* addBtn = new QPushButton("+ Tambah Produk");
    addBtn->setMinimumHeight(36);
    addBtn->setCursor(Qt::PointingHandCursor);
    connect(addBtn, &QPushButton::clicked, this, &ProductsPage::showAddDialog);
    headerRow->addWidget(addBtn);
    lay->addLayout(headerRow);

    // filter row
    auto* filterRow = new QHBoxLayout;
    m_searchEdit = new QLineEdit;
    m_searchEdit->setPlaceholderText("\xF0\x9F\x94\x8D Cari produk...");
    m_searchEdit->setMinimumHeight(38);
    connect(m_searchEdit, &QLineEdit::textChanged, this, &ProductsPage::loadProducts);
    filterRow->addWidget(m_searchEdit, 2);

    m_categoryCombo = new QComboBox;
    m_categoryCombo->setMinimumHeight(38);
    m_categoryCombo->addItem("Semua");
    auto cats = Database::instance().getCategories();
    for (auto& c : cats)
        m_categoryCombo->addItem(c["name"].toString());
    connect(m_categoryCombo, QOverload<int>::of(&QComboBox::currentIndexChanged),
            this, &ProductsPage::loadProducts);
    filterRow->addWidget(m_categoryCombo, 1);
    lay->addLayout(filterRow);

    // table
    m_table = new QTableWidget;
    m_table->setColumnCount(9);
    m_table->setHorizontalHeaderLabels(
        {"ID", "Barcode", "Nama", "Kategori", "Beli", "Jual", "Stok", "Unit", "Aksi"});
    m_table->horizontalHeader()->setSectionResizeMode(2, QHeaderView::Stretch);
    m_table->verticalHeader()->hide();
    m_table->setAlternatingRowColors(true);
    m_table->setEditTriggers(QAbstractItemView::NoEditTriggers);
    m_table->setSelectionBehavior(QAbstractItemView::SelectRows);
    m_table->setColumnWidth(0, 40);
    m_table->setColumnWidth(1, 90);
    m_table->setColumnWidth(4, 90);
    m_table->setColumnWidth(5, 90);
    m_table->setColumnWidth(6, 60);
    m_table->setColumnWidth(7, 50);
    m_table->setColumnWidth(8, 120);
    lay->addWidget(m_table, 1);
}

void ProductsPage::loadProducts() {
    QString search = m_searchEdit->text().trimmed();
    QString cat    = m_categoryCombo->currentText();
    auto products  = Database::instance().getProducts(search, cat);

    m_table->setRowCount(products.size());
    for (int i = 0; i < products.size(); ++i) {
        auto& p = products[i];
        m_table->setItem(i, 0, new QTableWidgetItem(p["id"].toString()));
        m_table->setItem(i, 1, new QTableWidgetItem(p["barcode"].toString()));
        m_table->setItem(i, 2, new QTableWidgetItem(p["name"].toString()));
        m_table->setItem(i, 3, new QTableWidgetItem(p["category_name"].toString()));
        m_table->setItem(i, 4, new QTableWidgetItem(Style::formatRupiah(p["buy_price"].toDouble())));
        m_table->setItem(i, 5, new QTableWidgetItem(Style::formatRupiah(p["sell_price"].toDouble())));

        auto* stockItem = new QTableWidgetItem(p["stock"].toString());
        if (p["stock"].toInt() <= p["min_stock"].toInt())
            stockItem->setForeground(QColor(Style::DANGER));
        m_table->setItem(i, 6, stockItem);
        m_table->setItem(i, 7, new QTableWidgetItem(p["unit"].toString()));

        // action buttons
        auto* actWidget = new QWidget;
        auto* actLay = new QHBoxLayout(actWidget);
        actLay->setContentsMargins(4, 2, 4, 2);
        actLay->setSpacing(4);

        int pid = p["id"].toInt();
        auto* editBtn = new QPushButton("Edit");
        editBtn->setFixedHeight(26);
        editBtn->setStyleSheet(Style::flatBtnStyle() + "QPushButton{padding:2px 10px;border-radius:6px;font-size:11px;}");
        editBtn->setCursor(Qt::PointingHandCursor);
        connect(editBtn, &QPushButton::clicked, this, [this, pid]{ showEditDialog(pid); });

        auto* delBtn = new QPushButton("Hapus");
        delBtn->setFixedHeight(26);
        delBtn->setStyleSheet(Style::dangerBtnStyle() + "QPushButton{padding:2px 10px;border-radius:6px;font-size:11px;}");
        delBtn->setCursor(Qt::PointingHandCursor);
        connect(delBtn, &QPushButton::clicked, this, [this, pid]{ deleteProduct(pid); });

        actLay->addWidget(editBtn);
        actLay->addWidget(delBtn);
        m_table->setCellWidget(i, 8, actWidget);
    }
}

void ProductsPage::showAddDialog() {
    auto* dlg = new QDialog(this);
    dlg->setWindowTitle("Tambah Produk");
    dlg->setFixedWidth(420);
    dlg->setStyleSheet(QStringLiteral("QDialog{background-color:%1;}").arg(Style::BG_CARD));

    auto* form = new QFormLayout(dlg);
    form->setContentsMargins(24, 20, 24, 20);
    form->setSpacing(10);

    auto* barcode  = new QLineEdit; barcode->setPlaceholderText("Barcode");
    auto* name     = new QLineEdit; name->setPlaceholderText("Nama produk");
    auto* category = new QComboBox;
    auto cats = Database::instance().getCategories();
    for (auto& c : cats) category->addItem(c["name"].toString(), c["id"]);
    auto* buyPrice  = new QLineEdit("0"); buyPrice->setPlaceholderText("Harga beli");
    auto* sellPrice = new QLineEdit("0"); sellPrice->setPlaceholderText("Harga jual");
    auto* stock     = new QLineEdit("0");
    auto* minStock  = new QLineEdit("5");
    auto* unit      = new QLineEdit("pcs");

    for (auto* w : {barcode, name, buyPrice, sellPrice, stock, minStock, unit})
        w->setMinimumHeight(36);
    category->setMinimumHeight(36);

    form->addRow("Barcode:", barcode);
    form->addRow("Nama:", name);
    form->addRow("Kategori:", category);
    form->addRow("Harga Beli:", buyPrice);
    form->addRow("Harga Jual:", sellPrice);
    form->addRow("Stok:", stock);
    form->addRow("Min Stok:", minStock);
    form->addRow("Satuan:", unit);

    auto* saveBtn = new QPushButton("Simpan");
    saveBtn->setMinimumHeight(38);
    saveBtn->setCursor(Qt::PointingHandCursor);
    form->addRow(saveBtn);

    connect(saveBtn, &QPushButton::clicked, dlg, [=]{
        if (name->text().trimmed().isEmpty()) {
            QMessageBox::warning(dlg, "Error", "Nama produk harus diisi!");
            return;
        }
        QVariantMap p;
        p["barcode"]     = barcode->text().trimmed();
        p["name"]        = name->text().trimmed();
        p["category_id"] = category->currentData();
        p["buy_price"]   = buyPrice->text().toDouble();
        p["sell_price"]  = sellPrice->text().toDouble();
        p["stock"]       = stock->text().toInt();
        p["min_stock"]   = minStock->text().toInt();
        p["unit"]        = unit->text().trimmed();

        if (Database::instance().addProduct(p)) {
            dlg->accept();
        } else {
            QMessageBox::critical(dlg, "Error", "Gagal menambahkan produk!");
        }
    });

    if (dlg->exec() == QDialog::Accepted)
        loadProducts();
    dlg->deleteLater();
}

void ProductsPage::showEditDialog(int id) {
    auto prod = Database::instance().getProduct(id);
    if (prod.isEmpty()) return;

    auto* dlg = new QDialog(this);
    dlg->setWindowTitle("Edit Produk");
    dlg->setFixedWidth(420);
    dlg->setStyleSheet(QStringLiteral("QDialog{background-color:%1;}").arg(Style::BG_CARD));

    auto* form = new QFormLayout(dlg);
    form->setContentsMargins(24, 20, 24, 20);
    form->setSpacing(10);

    auto* barcode   = new QLineEdit(prod["barcode"].toString());
    auto* name      = new QLineEdit(prod["name"].toString());
    auto* category  = new QComboBox;
    auto cats = Database::instance().getCategories();
    int catIdx = 0;
    for (int i = 0; i < cats.size(); ++i) {
        category->addItem(cats[i]["name"].toString(), cats[i]["id"]);
        if (cats[i]["id"].toInt() == prod["category_id"].toInt()) catIdx = i;
    }
    category->setCurrentIndex(catIdx);
    auto* buyPrice  = new QLineEdit(QString::number(prod["buy_price"].toDouble(), 'f', 0));
    auto* sellPrice = new QLineEdit(QString::number(prod["sell_price"].toDouble(), 'f', 0));
    auto* stock     = new QLineEdit(QString::number(prod["stock"].toInt()));
    auto* minStock  = new QLineEdit(QString::number(prod["min_stock"].toInt()));
    auto* unit      = new QLineEdit(prod["unit"].toString());

    for (auto* w : {barcode, name, buyPrice, sellPrice, stock, minStock, unit})
        w->setMinimumHeight(36);
    category->setMinimumHeight(36);

    form->addRow("Barcode:", barcode);
    form->addRow("Nama:", name);
    form->addRow("Kategori:", category);
    form->addRow("Harga Beli:", buyPrice);
    form->addRow("Harga Jual:", sellPrice);
    form->addRow("Stok:", stock);
    form->addRow("Min Stok:", minStock);
    form->addRow("Satuan:", unit);

    auto* saveBtn = new QPushButton("Simpan");
    saveBtn->setMinimumHeight(38);
    saveBtn->setCursor(Qt::PointingHandCursor);
    form->addRow(saveBtn);

    connect(saveBtn, &QPushButton::clicked, dlg, [=]{
        QVariantMap p;
        p["barcode"]     = barcode->text().trimmed();
        p["name"]        = name->text().trimmed();
        p["category_id"] = category->currentData();
        p["buy_price"]   = buyPrice->text().toDouble();
        p["sell_price"]  = sellPrice->text().toDouble();
        p["stock"]       = stock->text().toInt();
        p["min_stock"]   = minStock->text().toInt();
        p["unit"]        = unit->text().trimmed();

        if (Database::instance().updateProduct(id, p))
            dlg->accept();
        else
            QMessageBox::critical(dlg, "Error", "Gagal mengupdate produk!");
    });

    if (dlg->exec() == QDialog::Accepted)
        loadProducts();
    dlg->deleteLater();
}

void ProductsPage::deleteProduct(int id) {
    auto r = QMessageBox::question(this, "Konfirmasi",
        "Yakin ingin menghapus produk ini?",
        QMessageBox::Yes | QMessageBox::No);
    if (r == QMessageBox::Yes) {
        Database::instance().deleteProduct(id);
        loadProducts();
    }
}

void ProductsPage::showCategoryDialog() {
    auto* dlg = new QDialog(this);
    dlg->setWindowTitle("Manajemen Kategori");
    dlg->setFixedSize(380, 400);
    dlg->setStyleSheet(QStringLiteral("QDialog{background-color:%1;}").arg(Style::BG_CARD));

    auto* lay = new QVBoxLayout(dlg);
    lay->setContentsMargins(20, 16, 20, 16);
    lay->setSpacing(10);

    auto* addRow = new QHBoxLayout;
    auto* catEdit = new QLineEdit;
    catEdit->setPlaceholderText("Nama kategori baru");
    catEdit->setMinimumHeight(36);
    addRow->addWidget(catEdit, 1);

    auto* addCatBtn = new QPushButton("Tambah");
    addCatBtn->setMinimumHeight(36);
    addCatBtn->setCursor(Qt::PointingHandCursor);
    addRow->addWidget(addCatBtn);
    lay->addLayout(addRow);

    auto* catTable = new QTableWidget;
    catTable->setColumnCount(3);
    catTable->setHorizontalHeaderLabels({"ID", "Nama", "Hapus"});
    catTable->horizontalHeader()->setSectionResizeMode(1, QHeaderView::Stretch);
    catTable->verticalHeader()->hide();
    catTable->setAlternatingRowColors(true);
    catTable->setEditTriggers(QAbstractItemView::NoEditTriggers);
    lay->addWidget(catTable, 1);

    auto loadCats = [catTable, dlg] {
        auto cats = Database::instance().getCategories();
        catTable->setRowCount(cats.size());
        for (int i = 0; i < cats.size(); ++i) {
            catTable->setItem(i, 0, new QTableWidgetItem(cats[i]["id"].toString()));
            catTable->setItem(i, 1, new QTableWidgetItem(cats[i]["name"].toString()));
            auto* delBtn = new QPushButton("\xE2\x9C\x95");
            delBtn->setFixedSize(28, 28);
            delBtn->setStyleSheet(Style::dangerBtnStyle() + "QPushButton{padding:0;border-radius:8px;font-size:11px;}");
            delBtn->setCursor(Qt::PointingHandCursor);
            int cid = cats[i]["id"].toInt();
            QObject::connect(delBtn, &QPushButton::clicked, dlg, [catTable, cid, dlg] {
                Database::instance().deleteCategory(cid);
                // reload
                auto cats2 = Database::instance().getCategories();
                catTable->setRowCount(cats2.size());
                for (int j = 0; j < cats2.size(); ++j) {
                    catTable->setItem(j, 0, new QTableWidgetItem(cats2[j]["id"].toString()));
                    catTable->setItem(j, 1, new QTableWidgetItem(cats2[j]["name"].toString()));
                }
            });
            catTable->setCellWidget(i, 2, delBtn);
        }
    };
    loadCats();

    connect(addCatBtn, &QPushButton::clicked, dlg, [catEdit, loadCats]{
        QString name = catEdit->text().trimmed();
        if (!name.isEmpty()) {
            Database::instance().addCategory(name);
            catEdit->clear();
            loadCats();
        }
    });

    auto* closeBtn = new QPushButton("Tutup");
    closeBtn->setMinimumHeight(36);
    closeBtn->setCursor(Qt::PointingHandCursor);
    connect(closeBtn, &QPushButton::clicked, dlg, &QDialog::accept);
    lay->addWidget(closeBtn);

    dlg->exec();
    dlg->deleteLater();

    // refresh category combo
    m_categoryCombo->blockSignals(true);
    m_categoryCombo->clear();
    m_categoryCombo->addItem("Semua");
    auto cats = Database::instance().getCategories();
    for (auto& c : cats) m_categoryCombo->addItem(c["name"].toString());
    m_categoryCombo->blockSignals(false);
    loadProducts();
}
