#include "supplierspage.h"
#include "../database.h"
#include "../styles.h"
#include <QVBoxLayout>
#include <QHBoxLayout>
#include <QHeaderView>
#include <QPushButton>
#include <QLabel>
#include <QDialog>
#include <QFormLayout>
#include <QMessageBox>

SuppliersPage::SuppliersPage(QWidget* parent) : QWidget(parent) {
    buildUI();
    loadSuppliers();
}

void SuppliersPage::buildUI() {
    auto* lay = new QVBoxLayout(this);
    lay->setContentsMargins(28, 24, 28, 24);
    lay->setSpacing(16);

    auto* headerRow = new QHBoxLayout;
    auto* title = new QLabel("\xF0\x9F\x9A\x9A  Manajemen Supplier");
    title->setStyleSheet(QStringLiteral(
        "font-size: 22px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    headerRow->addWidget(title);
    headerRow->addStretch();

    auto* addBtn = new QPushButton("+ Tambah Supplier");
    addBtn->setMinimumHeight(36);
    addBtn->setCursor(Qt::PointingHandCursor);
    connect(addBtn, &QPushButton::clicked, this, &SuppliersPage::showAddDialog);
    headerRow->addWidget(addBtn);
    lay->addLayout(headerRow);

    m_searchEdit = new QLineEdit;
    m_searchEdit->setPlaceholderText("\xF0\x9F\x94\x8D Cari supplier...");
    m_searchEdit->setMinimumHeight(38);
    connect(m_searchEdit, &QLineEdit::textChanged, this, &SuppliersPage::loadSuppliers);
    lay->addWidget(m_searchEdit);

    m_table = new QTableWidget;
    m_table->setColumnCount(6);
    m_table->setHorizontalHeaderLabels({"ID","Nama","Kontak","Alamat","Email","Aksi"});
    m_table->horizontalHeader()->setSectionResizeMode(1, QHeaderView::Stretch);
    m_table->horizontalHeader()->setSectionResizeMode(3, QHeaderView::Stretch);
    m_table->verticalHeader()->hide();
    m_table->setAlternatingRowColors(true);
    m_table->setEditTriggers(QAbstractItemView::NoEditTriggers);
    m_table->setSelectionBehavior(QAbstractItemView::SelectRows);
    m_table->setColumnWidth(0, 40);
    m_table->setColumnWidth(5, 140);
    lay->addWidget(m_table, 1);
}

void SuppliersPage::loadSuppliers() {
    auto search = m_searchEdit->text().trimmed();
    auto data   = Database::instance().getSuppliers(search);
    m_table->setRowCount(data.size());
    for (int i = 0; i < data.size(); ++i) {
        auto& s = data[i];
        m_table->setItem(i, 0, new QTableWidgetItem(s["id"].toString()));
        m_table->setItem(i, 1, new QTableWidgetItem(s["name"].toString()));
        m_table->setItem(i, 2, new QTableWidgetItem(s["contact"].toString()));
        m_table->setItem(i, 3, new QTableWidgetItem(s["address"].toString()));
        m_table->setItem(i, 4, new QTableWidgetItem(s["email"].toString()));

        int sid = s["id"].toInt();
        auto* actW = new QWidget;
        auto* actL = new QHBoxLayout(actW);
        actL->setContentsMargins(4, 2, 4, 2);
        actL->setSpacing(4);
        auto* editBtn = new QPushButton("Edit");
        editBtn->setFixedHeight(26);
        editBtn->setStyleSheet(Style::flatBtnStyle()+"QPushButton{padding:2px 10px;border-radius:6px;font-size:11px;}");
        editBtn->setCursor(Qt::PointingHandCursor);
        connect(editBtn, &QPushButton::clicked, this, [this,sid]{showEditDialog(sid);});
        auto* delBtn = new QPushButton("Hapus");
        delBtn->setFixedHeight(26);
        delBtn->setStyleSheet(Style::dangerBtnStyle()+"QPushButton{padding:2px 10px;border-radius:6px;font-size:11px;}");
        delBtn->setCursor(Qt::PointingHandCursor);
        connect(delBtn, &QPushButton::clicked, this, [this,sid]{deleteSupplier(sid);});
        actL->addWidget(editBtn);
        actL->addWidget(delBtn);
        m_table->setCellWidget(i, 5, actW);
    }
}

void SuppliersPage::showAddDialog() {
    auto* dlg = new QDialog(this);
    dlg->setWindowTitle("Tambah Supplier");
    dlg->setFixedWidth(400);
    dlg->setStyleSheet(QStringLiteral("QDialog{background-color:%1;}").arg(Style::BG_CARD));
    auto* form = new QFormLayout(dlg);
    form->setContentsMargins(24,20,24,20);
    form->setSpacing(10);
    auto* name    = new QLineEdit; name->setMinimumHeight(36);
    auto* contact = new QLineEdit; contact->setMinimumHeight(36);
    auto* address = new QLineEdit; address->setMinimumHeight(36);
    auto* email   = new QLineEdit; email->setMinimumHeight(36);
    auto* notes   = new QLineEdit; notes->setMinimumHeight(36);
    form->addRow("Nama:",name);
    form->addRow("Kontak:",contact);
    form->addRow("Alamat:",address);
    form->addRow("Email:",email);
    form->addRow("Catatan:",notes);
    auto* saveBtn = new QPushButton("Simpan");
    saveBtn->setMinimumHeight(38);
    saveBtn->setCursor(Qt::PointingHandCursor);
    form->addRow(saveBtn);
    connect(saveBtn, &QPushButton::clicked, dlg, [=]{
        if(name->text().trimmed().isEmpty()){QMessageBox::warning(dlg,"Error","Nama harus diisi!");return;}
        QVariantMap s;
        s["name"]=name->text().trimmed();
        s["contact"]=contact->text().trimmed();
        s["address"]=address->text().trimmed();
        s["email"]=email->text().trimmed();
        s["notes"]=notes->text().trimmed();
        if(Database::instance().addSupplier(s)) dlg->accept();
    });
    if(dlg->exec()==QDialog::Accepted) loadSuppliers();
    dlg->deleteLater();
}

void SuppliersPage::showEditDialog(int id) {
    auto sup = Database::instance().getSuppliers();
    QVariantMap s;
    for(auto& x:sup) if(x["id"].toInt()==id){s=x;break;}
    if(s.isEmpty())return;
    auto* dlg = new QDialog(this);
    dlg->setWindowTitle("Edit Supplier");
    dlg->setFixedWidth(400);
    dlg->setStyleSheet(QStringLiteral("QDialog{background-color:%1;}").arg(Style::BG_CARD));
    auto* form = new QFormLayout(dlg);
    form->setContentsMargins(24,20,24,20);
    form->setSpacing(10);
    auto* name    = new QLineEdit(s["name"].toString()); name->setMinimumHeight(36);
    auto* contact = new QLineEdit(s["contact"].toString()); contact->setMinimumHeight(36);
    auto* address = new QLineEdit(s["address"].toString()); address->setMinimumHeight(36);
    auto* email   = new QLineEdit(s["email"].toString()); email->setMinimumHeight(36);
    auto* notes   = new QLineEdit(s["notes"].toString()); notes->setMinimumHeight(36);
    form->addRow("Nama:",name);
    form->addRow("Kontak:",contact);
    form->addRow("Alamat:",address);
    form->addRow("Email:",email);
    form->addRow("Catatan:",notes);
    auto* saveBtn = new QPushButton("Simpan");
    saveBtn->setMinimumHeight(38);
    saveBtn->setCursor(Qt::PointingHandCursor);
    form->addRow(saveBtn);
    connect(saveBtn, &QPushButton::clicked, dlg, [=]{
        QVariantMap u;
        u["name"]=name->text().trimmed();
        u["contact"]=contact->text().trimmed();
        u["address"]=address->text().trimmed();
        u["email"]=email->text().trimmed();
        u["notes"]=notes->text().trimmed();
        if(Database::instance().updateSupplier(id,u)) dlg->accept();
    });
    if(dlg->exec()==QDialog::Accepted) loadSuppliers();
    dlg->deleteLater();
}

void SuppliersPage::deleteSupplier(int id) {
    if(QMessageBox::question(this,"Konfirmasi","Yakin ingin menghapus supplier ini?",
        QMessageBox::Yes|QMessageBox::No)==QMessageBox::Yes){
        Database::instance().deleteSupplier(id);
        loadSuppliers();
    }
}
