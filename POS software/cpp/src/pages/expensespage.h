#pragma once
#include <QWidget>
#include <QLineEdit>
#include <QTableWidget>
#include <QDateEdit>

class ExpensesPage : public QWidget {
    Q_OBJECT
public:
    explicit ExpensesPage(QWidget* parent = nullptr);
private slots:
    void loadExpenses();
    void showAddDialog();
    void deleteExpense(int id);
private:
    void buildUI();
    QLineEdit*    m_searchEdit;
    QDateEdit*    m_dateFrom;
    QDateEdit*    m_dateTo;
    QTableWidget* m_table;
    QLabel*       m_totalLabel;
};
