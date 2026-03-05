#include "userspage.h"
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
#include <QLineEdit>
#include <QMessageBox>

UsersPage::UsersPage(QWidget* parent) : QWidget(parent) {
    buildUI();
    loadUsers();
}

void UsersPage::buildUI() {
    auto* lay = new QVBoxLayout(this);
    lay->setContentsMargins(28, 24, 28, 24);
    lay->setSpacing(16);

    auto* headerRow = new QHBoxLayout;
    auto* title = new QLabel("\xF0\x9F\x91\xA5  Manajemen Pengguna");
    title->setStyleSheet(QStringLiteral(
        "font-size: 22px; font-weight: 700; color: %1;").arg(Style::TEXT_PRIMARY));
    headerRow->addWidget(title);
    headerRow->addStretch();
    auto* addBtn = new QPushButton("+ Tambah Pengguna");
    addBtn->setMinimumHeight(36);
    addBtn->setCursor(Qt::PointingHandCursor);
    connect(addBtn, &QPushButton::clicked, this, &UsersPage::showAddDialog);
    headerRow->addWidget(addBtn);
    lay->addLayout(headerRow);

    m_table = new QTableWidget;
    m_table->setColumnCount(7);
    m_table->setHorizontalHeaderLabels(
        {"ID","Username","Nama Lengkap","Role","Status","Dibuat","Aksi"});
    m_table->horizontalHeader()->setSectionResizeMode(2, QHeaderView::Stretch);
    m_table->verticalHeader()->hide();
    m_table->setAlternatingRowColors(true);
    m_table->setEditTriggers(QAbstractItemView::NoEditTriggers);
    m_table->setSelectionBehavior(QAbstractItemView::SelectRows);
    m_table->setColumnWidth(0, 40);
    m_table->setColumnWidth(3, 80);
    m_table->setColumnWidth(4, 80);
    m_table->setColumnWidth(5, 130);
    m_table->setColumnWidth(6, 180);
    lay->addWidget(m_table, 1);
}

void UsersPage::loadUsers() {
    auto users = Database::instance().getUsers();
    m_table->setRowCount(users.size());
    for (int i = 0; i < users.size(); ++i) {
        auto& u = users[i];
        m_table->setItem(i, 0, new QTableWidgetItem(u["id"].toString()));
        m_table->setItem(i, 1, new QTableWidgetItem(u["username"].toString()));
        m_table->setItem(i, 2, new QTableWidgetItem(u["full_name"].toString()));
        m_table->setItem(i, 3, new QTableWidgetItem(
            u["role"].toString() == "admin" ? "Admin" : "Kasir"));
        auto* statusItem = new QTableWidgetItem(
            u["is_active"].toBool() ? "Aktif" : "Nonaktif");
        statusItem->setForeground(QColor(
            u["is_active"].toBool() ? Style::SUCCESS : Style::DANGER));
        m_table->setItem(i, 4, statusItem);
        m_table->setItem(i, 5, new QTableWidgetItem(u["created_at"].toString()));

        int uid = u["id"].toInt();
        bool active = u["is_active"].toBool();
        auto* actW = new QWidget;
        auto* actL = new QHBoxLayout(actW);
        actL->setContentsMargins(4, 2, 4, 2);
        actL->setSpacing(4);

        auto* editBtn = new QPushButton("Edit");
        editBtn->setFixedHeight(26);
        editBtn->setStyleSheet(Style::flatBtnStyle()+"QPushButton{padding:2px 10px;border-radius:6px;font-size:11px;}");
        editBtn->setCursor(Qt::PointingHandCursor);
        connect(editBtn, &QPushButton::clicked, this, [this,uid]{showEditDialog(uid);});
        actL->addWidget(editBtn);

        if (uid != 1) { // can't toggle admin #1
            auto* toggleBtn = new QPushButton(active ? "Nonaktifkan" : "Aktifkan");
            toggleBtn->setFixedHeight(26);
            toggleBtn->setStyleSheet(
                (active ? Style::dangerBtnStyle() : Style::successBtnStyle())
                + "QPushButton{padding:2px 8px;border-radius:6px;font-size:11px;}");
            toggleBtn->setCursor(Qt::PointingHandCursor);
            connect(toggleBtn, &QPushButton::clicked, this,
                [this,uid,active]{toggleUser(uid,!active);});
            actL->addWidget(toggleBtn);
        }
        m_table->setCellWidget(i, 6, actW);
    }
}

void UsersPage::showAddDialog() {
    auto* dlg = new QDialog(this);
    dlg->setWindowTitle("Tambah Pengguna");
    dlg->setFixedWidth(400);
    dlg->setStyleSheet(QStringLiteral("QDialog{background-color:%1;}").arg(Style::BG_CARD));
    auto* form = new QFormLayout(dlg);
    form->setContentsMargins(24,20,24,20);
    form->setSpacing(10);
    auto* username = new QLineEdit; username->setMinimumHeight(36);
    auto* password = new QLineEdit; password->setMinimumHeight(36);
    password->setEchoMode(QLineEdit::Password);
    auto* fullName = new QLineEdit; fullName->setMinimumHeight(36);
    auto* role = new QComboBox;
    role->addItems({"admin","cashier"});
    role->setMinimumHeight(36);
    form->addRow("Username:",username);
    form->addRow("Password:",password);
    form->addRow("Nama Lengkap:",fullName);
    form->addRow("Role:",role);
    auto* saveBtn = new QPushButton("Simpan");
    saveBtn->setMinimumHeight(38);
    saveBtn->setCursor(Qt::PointingHandCursor);
    form->addRow(saveBtn);
    connect(saveBtn, &QPushButton::clicked, dlg, [=]{
        if(username->text().trimmed().isEmpty()||password->text().isEmpty()){
            QMessageBox::warning(dlg,"Error","Username dan password harus diisi!");return;
        }
        QVariantMap u;
        u["username"]=username->text().trimmed();
        u["password"]=password->text();
        u["full_name"]=fullName->text().trimmed();
        u["role"]=role->currentText();
        if(Database::instance().addUser(u)) dlg->accept();
        else QMessageBox::critical(dlg,"Error","Gagal menambah user (username mungkin sudah ada)!");
    });
    if(dlg->exec()==QDialog::Accepted) loadUsers();
    dlg->deleteLater();
}

void UsersPage::showEditDialog(int id) {
    auto users = Database::instance().getUsers();
    QVariantMap u;
    for(auto& x:users) if(x["id"].toInt()==id){u=x;break;}
    if(u.isEmpty())return;
    auto* dlg = new QDialog(this);
    dlg->setWindowTitle("Edit Pengguna");
    dlg->setFixedWidth(400);
    dlg->setStyleSheet(QStringLiteral("QDialog{background-color:%1;}").arg(Style::BG_CARD));
    auto* form = new QFormLayout(dlg);
    form->setContentsMargins(24,20,24,20);
    form->setSpacing(10);
    auto* username = new QLineEdit(u["username"].toString()); username->setMinimumHeight(36);
    auto* password = new QLineEdit; password->setMinimumHeight(36);
    password->setPlaceholderText("Kosongkan jika tidak diubah");
    password->setEchoMode(QLineEdit::Password);
    auto* fullName = new QLineEdit(u["full_name"].toString()); fullName->setMinimumHeight(36);
    auto* role = new QComboBox;
    role->addItems({"admin","cashier"});
    role->setCurrentText(u["role"].toString());
    role->setMinimumHeight(36);
    form->addRow("Username:",username);
    form->addRow("Password:",password);
    form->addRow("Nama Lengkap:",fullName);
    form->addRow("Role:",role);
    auto* saveBtn = new QPushButton("Simpan");
    saveBtn->setMinimumHeight(38);
    saveBtn->setCursor(Qt::PointingHandCursor);
    form->addRow(saveBtn);
    connect(saveBtn, &QPushButton::clicked, dlg, [=]{
        QVariantMap uu;
        uu["username"]=username->text().trimmed();
        uu["password"]=password->text();
        uu["full_name"]=fullName->text().trimmed();
        uu["role"]=role->currentText();
        if(Database::instance().updateUser(id,uu)) dlg->accept();
    });
    if(dlg->exec()==QDialog::Accepted) loadUsers();
    dlg->deleteLater();
}

void UsersPage::toggleUser(int id, bool active) {
    Database::instance().toggleUser(id, active);
    loadUsers();
}
