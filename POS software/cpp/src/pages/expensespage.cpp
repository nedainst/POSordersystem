#include "expensespage.h"
#include "../database.h"
#include "../styles.h"
#include <QVBoxLayout>
#include <QHBoxLayout>
#include <QHeaderView>
#include <QPushButton>
#include <QLabel>
#include <QDialog>
#include <QFormLayout>
#include <QComboBox>
#include <QMessageBox>
#include <QDate>

ExpensesPage::ExpensesPage(QWidget* parent) : QWidget(parent) {
    buildUI();
    loadExpenses();
}

void ExpensesPage::buildUI() {
    auto* lay = new QVBoxLayout(this);
    lay->setContentsMargins(28, 24, 28, 24);
    lay->setSpacing(16);

    auto* headerRow = new QHBoxLayout;
    auto* title = new QLabel("\xF0\x9F\x92\xB0  Pengeluaran");
    title->setStyleSheet(QStringLiteral(
        "font-size: 22px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    headerRow->addWidget(title);
    headerRow->addStretch();
    auto* addBtn = new QPushButton("+ Tambah Pengeluaran");
    addBtn->setMinimumHeight(36);
    addBtn->setCursor(Qt::PointingHandCursor);
    connect(addBtn, &QPushButton::clicked, this, &ExpensesPage::showAddDialog);
    headerRow->addWidget(addBtn);
    lay->addLayout(headerRow);

    // filters
    auto* filterRow = new QHBoxLayout;
    filterRow->setSpacing(8);
    m_searchEdit = new QLineEdit;
    m_searchEdit->setPlaceholderText("\xF0\x9F\x94\x8D Cari...");
    m_searchEdit->setMinimumHeight(38);
    connect(m_searchEdit, &QLineEdit::textChanged, this, &ExpensesPage::loadExpenses);
    filterRow->addWidget(m_searchEdit, 2);

    auto* fLbl = new QLabel("Dari:");
    fLbl->setStyleSheet(QStringLiteral("color:%1;").arg(Style::TEXT_SECONDARY));
    filterRow->addWidget(fLbl);
    m_dateFrom = new QDateEdit(QDate::currentDate().addDays(-30));
    m_dateFrom->setCalendarPopup(true);
    m_dateFrom->setMinimumHeight(38);
    m_dateFrom->setStyleSheet(QStringLiteral(
        "QDateEdit{background-color:%1;border:1px solid %2;border-radius:10px;"
        "padding:4px 10px;color:%3;}")
        .arg(Style::BG_ENTRY, Style::BORDER, Style::TEXT_PRIMARY));
    connect(m_dateFrom, &QDateEdit::dateChanged, this, &ExpensesPage::loadExpenses);
    filterRow->addWidget(m_dateFrom);

    auto* tLbl = new QLabel("Sampai:");
    tLbl->setStyleSheet(QStringLiteral("color:%1;").arg(Style::TEXT_SECONDARY));
    filterRow->addWidget(tLbl);
    m_dateTo = new QDateEdit(QDate::currentDate());
    m_dateTo->setCalendarPopup(true);
    m_dateTo->setMinimumHeight(38);
    m_dateTo->setStyleSheet(m_dateFrom->styleSheet());
    connect(m_dateTo, &QDateEdit::dateChanged, this, &ExpensesPage::loadExpenses);
    filterRow->addWidget(m_dateTo);
    lay->addLayout(filterRow);

    m_table = new QTableWidget;
    m_table->setColumnCount(6);
    m_table->setHorizontalHeaderLabels({"ID","Kategori","Deskripsi","Jumlah","Tanggal","Aksi"});
    m_table->horizontalHeader()->setSectionResizeMode(2, QHeaderView::Stretch);
    m_table->verticalHeader()->hide();
    m_table->setAlternatingRowColors(true);
    m_table->setEditTriggers(QAbstractItemView::NoEditTriggers);
    m_table->setSelectionBehavior(QAbstractItemView::SelectRows);
    m_table->setColumnWidth(0, 40);
    m_table->setColumnWidth(3, 110);
    m_table->setColumnWidth(5, 80);
    lay->addWidget(m_table, 1);

    m_totalLabel = new QLabel("Total: Rp 0");
    m_totalLabel->setStyleSheet(QStringLiteral(
        "font-size: 16px; font-weight: 700; color: %1;").arg(Style::DANGER));
    m_totalLabel->setAlignment(Qt::AlignRight);
    lay->addWidget(m_totalLabel);
}

void ExpensesPage::loadExpenses() {
    auto search = m_searchEdit->text().trimmed();
    auto from   = m_dateFrom->date().toString("yyyy-MM-dd");
    auto to     = m_dateTo->date().toString("yyyy-MM-dd");
    auto data   = Database::instance().getExpenses(search, from, to);

    m_table->setRowCount(data.size());
    double total = 0;
    for (int i = 0; i < data.size(); ++i) {
        auto& e = data[i];
        m_table->setItem(i, 0, new QTableWidgetItem(e["id"].toString()));
        m_table->setItem(i, 1, new QTableWidgetItem(e["category"].toString()));
        m_table->setItem(i, 2, new QTableWidgetItem(e["description"].toString()));
        m_table->setItem(i, 3, new QTableWidgetItem(
            Style::formatRupiah(e["amount"].toDouble())));
        m_table->setItem(i, 4, new QTableWidgetItem(e["date"].toString()));
        total += e["amount"].toDouble();

        int eid = e["id"].toInt();
        auto* delBtn = new QPushButton("\xE2\x9C\x95");
        delBtn->setFixedSize(28, 28);
        delBtn->setStyleSheet(Style::dangerBtnStyle()+"QPushButton{padding:0;border-radius:8px;font-size:11px;}");
        delBtn->setCursor(Qt::PointingHandCursor);
        connect(delBtn, &QPushButton::clicked, this, [this,eid]{deleteExpense(eid);});
        m_table->setCellWidget(i, 5, delBtn);
    }
    m_totalLabel->setText(QStringLiteral("Total: %1").arg(Style::formatRupiah(total)));
}

void ExpensesPage::showAddDialog() {
    auto* dlg = new QDialog(this);
    dlg->setWindowTitle("Tambah Pengeluaran");
    dlg->setFixedWidth(400);
    dlg->setStyleSheet(QStringLiteral("QDialog{background-color:%1;}").arg(Style::BG_CARD));
    auto* form = new QFormLayout(dlg);
    form->setContentsMargins(24,20,24,20);
    form->setSpacing(10);

    auto* category = new QComboBox;
    category->addItems({"Gaji","Sewa","Listrik","Air","Internet","Transportasi","Lainnya"});
    category->setMinimumHeight(36);
    auto* desc   = new QLineEdit; desc->setMinimumHeight(36);
    auto* amount = new QLineEdit; amount->setMinimumHeight(36);
    auto* date   = new QDateEdit(QDate::currentDate());
    date->setCalendarPopup(true);
    date->setMinimumHeight(36);
    date->setStyleSheet(QStringLiteral(
        "QDateEdit{background-color:%1;border:1px solid %2;border-radius:10px;"
        "padding:4px 10px;color:%3;}")
        .arg(Style::BG_ENTRY, Style::BORDER, Style::TEXT_PRIMARY));

    form->addRow("Kategori:",category);
    form->addRow("Deskripsi:",desc);
    form->addRow("Jumlah:",amount);
    form->addRow("Tanggal:",date);

    auto* saveBtn = new QPushButton("Simpan");
    saveBtn->setMinimumHeight(38);
    saveBtn->setCursor(Qt::PointingHandCursor);
    form->addRow(saveBtn);
    connect(saveBtn, &QPushButton::clicked, dlg, [=]{
        double amt = amount->text().toDouble();
        if(amt<=0){QMessageBox::warning(dlg,"Error","Jumlah harus lebih dari 0!");return;}
        QVariantMap e;
        e["category"]=category->currentText();
        e["description"]=desc->text().trimmed();
        e["amount"]=amt;
        e["date"]=date->date().toString("yyyy-MM-dd");
        if(Database::instance().addExpense(e)) dlg->accept();
    });
    if(dlg->exec()==QDialog::Accepted) loadExpenses();
    dlg->deleteLater();
}

void ExpensesPage::deleteExpense(int id) {
    if(QMessageBox::question(this,"Konfirmasi","Yakin ingin menghapus pengeluaran ini?",
        QMessageBox::Yes|QMessageBox::No)==QMessageBox::Yes){
        Database::instance().deleteExpense(id);
        loadExpenses();
    }
}
