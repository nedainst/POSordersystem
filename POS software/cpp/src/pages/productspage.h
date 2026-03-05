#pragma once

#include <QWidget>
#include <QLineEdit>
#include <QTableWidget>
#include <QComboBox>
#include <QPushButton>

class ProductsPage : public QWidget {
    Q_OBJECT
public:
    explicit ProductsPage(QWidget* parent = nullptr);

private slots:
    void loadProducts();
    void showAddDialog();
    void showEditDialog(int id);
    void deleteProduct(int id);
    void showCategoryDialog();

private:
    void buildUI();

    QLineEdit*    m_searchEdit;
    QComboBox*    m_categoryCombo;
    QTableWidget* m_table;
};
