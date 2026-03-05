#pragma once

#include <QWidget>
#include <QLineEdit>
#include <QTableWidget>
#include <QComboBox>
#include <QTabWidget>

class InventoryPage : public QWidget {
    Q_OBJECT
public:
    explicit InventoryPage(QWidget* parent = nullptr);

private slots:
    void loadStockList();
    void loadLowStock();
    void loadMovements();
    void doAdjustStock();

private:
    void buildUI();

    QTabWidget*   m_tabs;

    // stock list tab
    QLineEdit*    m_stockSearch;
    QTableWidget* m_stockTable;

    // low stock tab
    QTableWidget* m_lowStockTable;

    // movements tab
    QTableWidget* m_movementTable;

    // adjust tab
    QComboBox*    m_adjProduct;
    QLineEdit*    m_adjQty;
    QComboBox*    m_adjType;
    QLineEdit*    m_adjNotes;
};
