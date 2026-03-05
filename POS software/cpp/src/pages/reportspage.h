#pragma once

#include <QWidget>
#include <QTabWidget>
#include <QDateEdit>
#include <QTableWidget>
#include <QLabel>

class ReportsPage : public QWidget {
    Q_OBJECT
public:
    explicit ReportsPage(QWidget* parent = nullptr);

private slots:
    void loadSalesReport();
    void loadProductReport();
    void loadProfitReport();

private:
    void buildUI();

    QTabWidget* m_tabs;

    // sales tab
    QDateEdit*    m_salesFrom;
    QDateEdit*    m_salesTo;
    QTableWidget* m_salesTable;
    QWidget*      m_salesChart;

    // product tab
    QDateEdit*    m_prodFrom;
    QDateEdit*    m_prodTo;
    QTableWidget* m_prodTable;

    // profit tab
    QDateEdit*    m_profitFrom;
    QDateEdit*    m_profitTo;
    QLabel*       m_revenueLabel;
    QLabel*       m_cogsLabel;
    QLabel*       m_grossLabel;
    QLabel*       m_expenseLabel;
    QLabel*       m_netLabel;
    QLabel*       m_marginLabel;
    QLabel*       m_txCountLabel;
};
