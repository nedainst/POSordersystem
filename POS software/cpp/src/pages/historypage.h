#pragma once

#include <QWidget>
#include <QLineEdit>
#include <QTableWidget>
#include <QDateEdit>

class HistoryPage : public QWidget {
    Q_OBJECT
public:
    explicit HistoryPage(const QString& userRole, QWidget* parent = nullptr);

private slots:
    void loadTransactions();
    void showDetail(int txId);
    void voidTransaction(int txId);

private:
    void buildUI();

    QLineEdit*    m_searchEdit;
    QDateEdit*    m_dateFrom;
    QDateEdit*    m_dateTo;
    QTableWidget* m_table;
    QString       m_userRole;
};
