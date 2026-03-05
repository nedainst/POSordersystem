#pragma once
#include <QWidget>
#include <QLineEdit>
#include <QTableWidget>

class SuppliersPage : public QWidget {
    Q_OBJECT
public:
    explicit SuppliersPage(QWidget* parent = nullptr);
private slots:
    void loadSuppliers();
    void showAddDialog();
    void showEditDialog(int id);
    void deleteSupplier(int id);
private:
    void buildUI();
    QLineEdit*    m_searchEdit;
    QTableWidget* m_table;
};
