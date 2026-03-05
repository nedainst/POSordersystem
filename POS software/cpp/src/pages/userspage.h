#pragma once
#include <QWidget>
#include <QTableWidget>

class UsersPage : public QWidget {
    Q_OBJECT
public:
    explicit UsersPage(QWidget* parent = nullptr);
private slots:
    void loadUsers();
    void showAddDialog();
    void showEditDialog(int id);
    void toggleUser(int id, bool active);
private:
    void buildUI();
    QTableWidget* m_table;
};
