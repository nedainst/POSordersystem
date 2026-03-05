#pragma once

#include <QWidget>
#include <QVBoxLayout>
#include <QLabel>
#include <QTableWidget>
#include <QScrollArea>

class DashboardPage : public QWidget {
    Q_OBJECT
public:
    explicit DashboardPage(QWidget* parent = nullptr);
    void refresh();

private:
    void buildUI();
    void buildStatsCards(QLayout* parent);
    void buildChart(QLayout* parent);
    void buildBottomSection(QLayout* parent);
    void loadData();

    QLabel* m_todaySales;
    QLabel* m_monthSales;
    QLabel* m_totalProducts;
    QLabel* m_lowStock;
    QLabel* m_todayTx;

    QWidget*      m_chartWidget;
    QTableWidget* m_recentTable;
    QTableWidget* m_topTable;
    QTableWidget* m_lowStockTable;
};
