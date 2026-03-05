#pragma once

#include <QSqlDatabase>
#include <QSqlQuery>
#include <QVariant>
#include <QVariantMap>
#include <QVector>
#include <QString>
#include <QDateTime>
#include <functional>

class Database {
public:
    static Database& instance();

    bool initialize();
    void close();

    // ── Auth ─────────────────────────────────────────────────
    QVariantMap authenticate(const QString& username, const QString& password);

    // ── Dashboard ────────────────────────────────────────────
    QVariantMap getDashboardStats();
    QVector<QVariantMap> getWeeklySales();
    QVector<QVariantMap> getTopProducts(int limit = 5);
    QVector<QVariantMap> getRecentTransactions(int limit = 10);
    QVector<QVariantMap> getLowStockProducts(int threshold = -1);

    // ── Products ─────────────────────────────────────────────
    QVector<QVariantMap> getProducts(const QString& search = "",
                                     const QString& category = "Semua");
    QVariantMap getProduct(int id);
    bool addProduct(const QVariantMap& p);
    bool updateProduct(int id, const QVariantMap& p);
    bool deleteProduct(int id);

    // ── Categories ───────────────────────────────────────────
    QVector<QVariantMap> getCategories();
    bool addCategory(const QString& name);
    bool deleteCategory(int id);

    // ── Transactions ─────────────────────────────────────────
    int createTransaction(const QVariantMap& tx, const QVector<QVariantMap>& items);
    QVector<QVariantMap> getTransactions(const QString& search = "",
                                         const QString& dateFrom = "",
                                         const QString& dateTo = "");
    QVariantMap getTransactionDetail(int id);
    QVector<QVariantMap> getTransactionItems(int txId);
    bool voidTransaction(int id);

    // ── Stock / Inventory ────────────────────────────────────
    QVector<QVariantMap> getStockList(const QString& search = "");
    QVector<QVariantMap> getStockMovements(int limit = 100);
    bool adjustStock(int productId, int qty, const QString& type,
                     const QString& notes);

    // ── Suppliers ────────────────────────────────────────────
    QVector<QVariantMap> getSuppliers(const QString& search = "");
    bool addSupplier(const QVariantMap& s);
    bool updateSupplier(int id, const QVariantMap& s);
    bool deleteSupplier(int id);

    // ── Expenses ─────────────────────────────────────────────
    QVector<QVariantMap> getExpenses(const QString& search = "",
                                     const QString& dateFrom = "",
                                     const QString& dateTo = "");
    bool addExpense(const QVariantMap& e);
    bool deleteExpense(int id);
    double getTotalExpenses(const QString& dateFrom = "",
                            const QString& dateTo = "");

    // ── Users ────────────────────────────────────────────────
    QVector<QVariantMap> getUsers();
    bool addUser(const QVariantMap& u);
    bool updateUser(int id, const QVariantMap& u);
    bool toggleUser(int id, bool active);

    // ── Settings ─────────────────────────────────────────────
    QVariantMap getSettings();
    bool updateSettings(const QVariantMap& s);
    int getLowStockThreshold();

    // ── Reports ──────────────────────────────────────────────
    QVector<QVariantMap> getSalesReport(const QString& from, const QString& to);
    QVector<QVariantMap> getProductReport(const QString& from, const QString& to);
    QVariantMap getProfitReport(const QString& from, const QString& to);

private:
    Database() = default;
    ~Database();
    Database(const Database&) = delete;
    Database& operator=(const Database&) = delete;

    void createTables();
    void insertDefaults();
    void generateSampleData();

    QSqlDatabase m_db;
    QString m_dbPath;

    // helpers
    QVector<QVariantMap> execSelect(const QString& sql,
                                    const QVector<QVariant>& params = {});
    bool execNonQuery(const QString& sql,
                      const QVector<QVariant>& params = {});
};
