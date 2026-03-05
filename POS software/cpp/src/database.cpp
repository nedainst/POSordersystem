#include "database.h"
#include <QSqlError>
#include <QSqlRecord>
#include <QCoreApplication>
#include <QDir>
#include <QRandomGenerator>
#include <QDate>
#include <QDebug>
#include <cmath>

// ═══════════════════════════════════════════════════════════════
//  Singleton
// ═══════════════════════════════════════════════════════════════
Database& Database::instance() {
    static Database inst;
    return inst;
}

Database::~Database() { close(); }

void Database::close() {
    if (m_db.isOpen()) m_db.close();
}

// ═══════════════════════════════════════════════════════════════
//  Init
// ═══════════════════════════════════════════════════════════════
bool Database::initialize() {
    QString appDir = QCoreApplication::applicationDirPath();
    m_dbPath = QDir(appDir).filePath("pos_database.db");
    m_db = QSqlDatabase::addDatabase("QSQLITE");
    m_db.setDatabaseName(m_dbPath);
    if (!m_db.open()) {
        qCritical() << "DB open failed:" << m_db.lastError().text();
        return false;
    }
    execNonQuery("PRAGMA journal_mode=WAL");
    execNonQuery("PRAGMA foreign_keys=ON");
    createTables();
    insertDefaults();
    return true;
}

// ── helpers ──────────────────────────────────────────────────
QVector<QVariantMap> Database::execSelect(const QString& sql,
                                           const QVector<QVariant>& params) {
    QSqlQuery q(m_db);
    q.prepare(sql);
    for (int i = 0; i < params.size(); ++i) q.bindValue(i, params[i]);
    QVector<QVariantMap> rows;
    if (!q.exec()) { qWarning() << "SQL:" << q.lastError().text(); return rows; }
    auto rec = q.record();
    while (q.next()) {
        QVariantMap row;
        for (int c = 0; c < rec.count(); ++c)
            row[rec.fieldName(c)] = q.value(c);
        rows.append(row);
    }
    return rows;
}

bool Database::execNonQuery(const QString& sql, const QVector<QVariant>& params) {
    QSqlQuery q(m_db);
    q.prepare(sql);
    for (int i = 0; i < params.size(); ++i) q.bindValue(i, params[i]);
    if (!q.exec()) { qWarning() << "SQL:" << q.lastError().text(); return false; }
    return true;
}

// ═══════════════════════════════════════════════════════════════
//  Schema
// ═══════════════════════════════════════════════════════════════
void Database::createTables() {
    QStringList ddl = {
        R"(CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            full_name TEXT NOT NULL,
            role TEXT DEFAULT 'cashier',
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ))",
        R"(CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL
        ))",
        R"(CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            barcode TEXT UNIQUE,
            name TEXT NOT NULL,
            category_id INTEGER,
            buy_price REAL DEFAULT 0,
            sell_price REAL DEFAULT 0,
            stock INTEGER DEFAULT 0,
            min_stock INTEGER DEFAULT 5,
            unit TEXT DEFAULT 'pcs',
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(category_id) REFERENCES categories(id)
        ))",
        R"(CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_number TEXT UNIQUE NOT NULL,
            customer_name TEXT DEFAULT 'Umum',
            cashier_id INTEGER,
            subtotal REAL DEFAULT 0,
            discount REAL DEFAULT 0,
            tax REAL DEFAULT 0,
            total REAL DEFAULT 0,
            payment_method TEXT DEFAULT 'Cash',
            payment_amount REAL DEFAULT 0,
            change_amount REAL DEFAULT 0,
            status TEXT DEFAULT 'completed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(cashier_id) REFERENCES users(id)
        ))",
        R"(CREATE TABLE IF NOT EXISTS transaction_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            transaction_id INTEGER NOT NULL,
            product_id INTEGER,
            product_name TEXT,
            quantity INTEGER DEFAULT 1,
            unit_price REAL DEFAULT 0,
            subtotal REAL DEFAULT 0,
            FOREIGN KEY(transaction_id) REFERENCES transactions(id),
            FOREIGN KEY(product_id) REFERENCES products(id)
        ))",
        R"(CREATE TABLE IF NOT EXISTS stock_movements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            type TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(product_id) REFERENCES products(id)
        ))",
        R"(CREATE TABLE IF NOT EXISTS suppliers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            contact TEXT,
            address TEXT,
            email TEXT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ))",
        R"(CREATE TABLE IF NOT EXISTS expenses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category TEXT NOT NULL,
            description TEXT,
            amount REAL NOT NULL,
            date TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ))",
        R"(CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY CHECK (id = 1),
            store_name TEXT DEFAULT 'Toko Saya',
            store_address TEXT DEFAULT '',
            store_phone TEXT DEFAULT '',
            tax_rate REAL DEFAULT 11.0,
            receipt_footer TEXT DEFAULT 'Terima kasih atas kunjungan Anda!',
            low_stock_threshold INTEGER DEFAULT 10
        ))"
    };
    for (auto& s : ddl) execNonQuery(s);
}

// ═══════════════════════════════════════════════════════════════
//  Defaults & Sample Data
// ═══════════════════════════════════════════════════════════════
void Database::insertDefaults() {
    auto rows = execSelect("SELECT COUNT(*) AS cnt FROM users");
    if (!rows.isEmpty() && rows[0]["cnt"].toInt() > 0) return;

    // default users
    execNonQuery("INSERT INTO users(username,password,full_name,role)"
                 " VALUES(?,?,?,?)", {"admin","admin123","Administrator","admin"});
    execNonQuery("INSERT INTO users(username,password,full_name,role)"
                 " VALUES(?,?,?,?)", {"kasir","kasir123","Kasir Utama","cashier"});

    // settings
    execNonQuery("INSERT OR IGNORE INTO settings(id) VALUES(1)");

    // categories
    QStringList cats = {"Makanan","Minuman","Snack","Rokok","Obat","ATK","Elektronik","Lainnya"};
    for (auto& c : cats)
        execNonQuery("INSERT OR IGNORE INTO categories(name) VALUES(?)", {c});

    generateSampleData();
}

void Database::generateSampleData() {
    struct Prod { QString name; int cat; double buy; double sell; int stock; QString barcode; };
    QVector<Prod> prods = {
        {"Nasi Goreng",1,8000,15000,50,"1000001"},
        {"Mie Goreng",1,7000,13000,45,"1000002"},
        {"Ayam Goreng",1,10000,18000,30,"1000003"},
        {"Nasi Uduk",1,6000,12000,40,"1000004"},
        {"Soto Ayam",1,9000,16000,35,"1000005"},
        {"Es Teh Manis",2,2000,5000,100,"2000001"},
        {"Es Jeruk",2,3000,7000,80,"2000002"},
        {"Kopi Hitam",2,3000,8000,60,"2000003"},
        {"Air Mineral",2,1500,3000,200,"2000004"},
        {"Jus Alpukat",2,8000,15000,25,"2000005"},
        {"Chitato",3,5000,8000,60,"3000001"},
        {"Oreo",3,4000,6500,50,"3000002"},
        {"Tango",3,3500,5500,70,"3000003"},
        {"Pocky",3,6000,9000,40,"3000004"},
        {"Good Day",3,3000,5000,90,"3000005"},
        {"Sampoerna Mild",4,20000,26000,100,"4000001"},
        {"Gudang Garam",4,18000,23000,80,"4000002"},
        {"Djarum Super",4,17000,22000,70,"4000003"},
        {"Paracetamol",5,1500,3000,50,"5000001"},
        {"Antangin",5,2000,4000,40,"5000002"},
        {"Pulpen",6,1500,3000,100,"6000001"},
        {"Buku Tulis",6,3000,5000,80,"6000002"},
        {"Baterai AA",7,5000,8000,60,"7000001"},
        {"Lampu LED",7,15000,25000,30,"7000002"},
        {"Tisu Paseo",8,8000,12000,50,"8000001"},
    };
    for (auto& p : prods) {
        execNonQuery("INSERT INTO products(barcode,name,category_id,buy_price,sell_price,stock,unit)"
                     " VALUES(?,?,?,?,?,?,'pcs')",
                     {p.barcode, p.name, p.cat, p.buy, p.sell, p.stock});
    }

    // suppliers
    execNonQuery("INSERT INTO suppliers(name,contact,address) VALUES(?,?,?)",
                 {"PT Indofood","021-12345","Jakarta"});
    execNonQuery("INSERT INTO suppliers(name,contact,address) VALUES(?,?,?)",
                 {"CV Maju Jaya","022-67890","Bandung"});
    execNonQuery("INSERT INTO suppliers(name,contact,address) VALUES(?,?,?)",
                 {"UD Sejahtera","031-11111","Surabaya"});

    // sample transactions for last 30 days
    auto rng = QRandomGenerator::global();
    auto today = QDate::currentDate();
    int cashierId = 2;
    for (int d = 30; d >= 0; --d) {
        auto date = today.addDays(-d);
        int txCount = 3 + rng->bounded(8);            // 3-10 tx/day
        for (int t = 0; t < txCount; ++t) {
            int itemCount = 1 + rng->bounded(5);
            double subtotal = 0;
            QVector<QVariantMap> items;
            for (int i = 0; i < itemCount; ++i) {
                int pid = 1 + rng->bounded(prods.size());
                int qty = 1 + rng->bounded(3);
                double price = prods[pid - 1].sell;
                double sub = price * qty;
                subtotal += sub;
                items.append({{"product_id", pid},
                              {"product_name", prods[pid-1].name},
                              {"quantity", qty},
                              {"unit_price", price},
                              {"subtotal", sub}});
            }
            double tax   = std::round(subtotal * 0.11);
            double total = subtotal + tax;
            QString inv  = QStringLiteral("INV-%1-%2")
                               .arg(date.toString("yyyyMMdd"))
                               .arg(t + 1, 3, 10, QChar('0'));
            QString dt   = date.toString("yyyy-MM-dd") +
                           QStringLiteral(" %1:%2:00")
                               .arg(8 + rng->bounded(12), 2, 10, QChar('0'))
                               .arg(rng->bounded(60), 2, 10, QChar('0'));

            execNonQuery("INSERT INTO transactions"
                         "(invoice_number,customer_name,cashier_id,subtotal,discount,"
                         "tax,total,payment_method,payment_amount,change_amount,status,created_at)"
                         " VALUES(?,?,?,?,0,?,?,?,?,0,'completed',?)",
                         {inv,"Umum",cashierId,subtotal,tax,total,"Cash",total,dt});

            auto lastId = execSelect("SELECT last_insert_rowid() AS id");
            int txId = lastId.isEmpty() ? 0 : lastId[0]["id"].toInt();
            for (auto& it : items) {
                execNonQuery("INSERT INTO transaction_items"
                             "(transaction_id,product_id,product_name,quantity,unit_price,subtotal)"
                             " VALUES(?,?,?,?,?,?)",
                             {txId, it["product_id"], it["product_name"],
                              it["quantity"], it["unit_price"], it["subtotal"]});
            }
        }
    }

    // sample expenses
    QStringList expCats = {"Gaji","Sewa","Listrik","Air","Internet","Lainnya"};
    for (int d = 30; d >= 0; d -= 5) {
        auto date = today.addDays(-d).toString("yyyy-MM-dd");
        auto cat  = expCats[rng->bounded(expCats.size())];
        double amt = (5 + rng->bounded(20)) * 10000.0;
        execNonQuery("INSERT INTO expenses(category,description,amount,date)"
                     " VALUES(?,?,?,?)", {cat, cat + " bulan ini", amt, date});
    }
}

// ═══════════════════════════════════════════════════════════════
//  Auth
// ═══════════════════════════════════════════════════════════════
QVariantMap Database::authenticate(const QString& u, const QString& p) {
    auto rows = execSelect(
        "SELECT * FROM users WHERE username=? AND password=? AND is_active=1",
        {u, p});
    return rows.isEmpty() ? QVariantMap{} : rows[0];
}

// ═══════════════════════════════════════════════════════════════
//  Dashboard
// ═══════════════════════════════════════════════════════════════
QVariantMap Database::getDashboardStats() {
    auto today = QDate::currentDate().toString("yyyy-MM-dd");
    auto month = QDate::currentDate().toString("yyyy-MM");
    QVariantMap m;

    auto r1 = execSelect("SELECT COALESCE(SUM(total),0) AS v FROM transactions"
                          " WHERE DATE(created_at)=? AND status='completed'", {today});
    m["today_sales"] = r1.isEmpty() ? 0 : r1[0]["v"];

    auto r2 = execSelect("SELECT COALESCE(SUM(total),0) AS v FROM transactions"
                          " WHERE strftime('%Y-%m',created_at)=? AND status='completed'",{month});
    m["month_sales"] = r2.isEmpty() ? 0 : r2[0]["v"];

    auto r3 = execSelect("SELECT COUNT(*) AS v FROM products WHERE is_active=1");
    m["total_products"] = r3.isEmpty() ? 0 : r3[0]["v"];

    int thr = getLowStockThreshold();
    auto r4 = execSelect("SELECT COUNT(*) AS v FROM products WHERE stock<=? AND is_active=1",{thr});
    m["low_stock"] = r4.isEmpty() ? 0 : r4[0]["v"];

    auto r5 = execSelect("SELECT COUNT(*) AS v FROM transactions"
                          " WHERE DATE(created_at)=? AND status='completed'",{today});
    m["today_tx"] = r5.isEmpty() ? 0 : r5[0]["v"];

    return m;
}

QVector<QVariantMap> Database::getWeeklySales() {
    return execSelect(
        "SELECT DATE(created_at) AS date, SUM(total) AS total"
        " FROM transactions WHERE status='completed'"
        " AND DATE(created_at) >= DATE('now','-6 days')"
        " GROUP BY DATE(created_at) ORDER BY date");
}

QVector<QVariantMap> Database::getTopProducts(int limit) {
    return execSelect(
        "SELECT product_name, SUM(quantity) AS qty, SUM(subtotal) AS revenue"
        " FROM transaction_items ti"
        " JOIN transactions t ON t.id=ti.transaction_id"
        " WHERE t.status='completed'"
        " GROUP BY product_name ORDER BY qty DESC LIMIT ?", {limit});
}

QVector<QVariantMap> Database::getRecentTransactions(int limit) {
    return execSelect(
        "SELECT t.*, u.full_name AS cashier_name FROM transactions t"
        " LEFT JOIN users u ON u.id=t.cashier_id"
        " ORDER BY t.created_at DESC LIMIT ?", {limit});
}

QVector<QVariantMap> Database::getLowStockProducts(int threshold) {
    if (threshold < 0) threshold = getLowStockThreshold();
    return execSelect(
        "SELECT p.*, c.name AS category_name FROM products p"
        " LEFT JOIN categories c ON c.id=p.category_id"
        " WHERE p.stock<=? AND p.is_active=1 ORDER BY p.stock", {threshold});
}

// ═══════════════════════════════════════════════════════════════
//  Products
// ═══════════════════════════════════════════════════════════════
QVector<QVariantMap> Database::getProducts(const QString& search,
                                            const QString& category) {
    QString sql = "SELECT p.*, c.name AS category_name FROM products p"
                  " LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1";
    QVector<QVariant> params;
    if (!search.isEmpty()) {
        sql += " AND (p.name LIKE ? OR p.barcode LIKE ?)";
        params << ("%" + search + "%") << ("%" + search + "%");
    }
    if (category != "Semua" && !category.isEmpty()) {
        sql += " AND c.name=?";
        params << category;
    }
    sql += " ORDER BY p.name";
    return execSelect(sql, params);
}

QVariantMap Database::getProduct(int id) {
    auto r = execSelect("SELECT * FROM products WHERE id=?", {id});
    return r.isEmpty() ? QVariantMap{} : r[0];
}

bool Database::addProduct(const QVariantMap& p) {
    return execNonQuery(
        "INSERT INTO products(barcode,name,category_id,buy_price,sell_price,stock,min_stock,unit)"
        " VALUES(?,?,?,?,?,?,?,?)",
        {p["barcode"], p["name"], p["category_id"], p["buy_price"],
         p["sell_price"], p["stock"], p["min_stock"], p["unit"]});
}

bool Database::updateProduct(int id, const QVariantMap& p) {
    return execNonQuery(
        "UPDATE products SET barcode=?,name=?,category_id=?,buy_price=?,sell_price=?,"
        "stock=?,min_stock=?,unit=? WHERE id=?",
        {p["barcode"], p["name"], p["category_id"], p["buy_price"],
         p["sell_price"], p["stock"], p["min_stock"], p["unit"], id});
}

bool Database::deleteProduct(int id) {
    return execNonQuery("UPDATE products SET is_active=0 WHERE id=?", {id});
}

// ═══════════════════════════════════════════════════════════════
//  Categories
// ═══════════════════════════════════════════════════════════════
QVector<QVariantMap> Database::getCategories() {
    return execSelect("SELECT * FROM categories ORDER BY name");
}

bool Database::addCategory(const QString& name) {
    return execNonQuery("INSERT OR IGNORE INTO categories(name) VALUES(?)", {name});
}

bool Database::deleteCategory(int id) {
    return execNonQuery("DELETE FROM categories WHERE id=?", {id});
}

// ═══════════════════════════════════════════════════════════════
//  Transactions
// ═══════════════════════════════════════════════════════════════
int Database::createTransaction(const QVariantMap& tx,
                                 const QVector<QVariantMap>& items) {
    m_db.transaction();
    bool ok = execNonQuery(
        "INSERT INTO transactions(invoice_number,customer_name,cashier_id,"
        "subtotal,discount,tax,total,payment_method,payment_amount,change_amount)"
        " VALUES(?,?,?,?,?,?,?,?,?,?)",
        {tx["invoice_number"], tx["customer_name"], tx["cashier_id"],
         tx["subtotal"], tx["discount"], tx["tax"], tx["total"],
         tx["payment_method"], tx["payment_amount"], tx["change_amount"]});

    if (!ok) { m_db.rollback(); return -1; }

    auto lastId = execSelect("SELECT last_insert_rowid() AS id");
    int txId = lastId.isEmpty() ? -1 : lastId[0]["id"].toInt();

    for (auto& it : items) {
        execNonQuery("INSERT INTO transaction_items"
                     "(transaction_id,product_id,product_name,quantity,unit_price,subtotal)"
                     " VALUES(?,?,?,?,?,?)",
                     {txId, it["product_id"], it["product_name"],
                      it["quantity"], it["unit_price"], it["subtotal"]});
        // decrease stock
        execNonQuery("UPDATE products SET stock=stock-? WHERE id=?",
                     {it["quantity"], it["product_id"]});
        // movement
        execNonQuery("INSERT INTO stock_movements(product_id,type,quantity,notes)"
                     " VALUES(?,'out',?,?)",
                     {it["product_id"], it["quantity"],
                      QStringLiteral("Penjualan #%1").arg(tx["invoice_number"].toString())});
    }
    m_db.commit();
    return txId;
}

QVector<QVariantMap> Database::getTransactions(const QString& search,
                                                const QString& dateFrom,
                                                const QString& dateTo) {
    QString sql = "SELECT t.*, u.full_name AS cashier_name FROM transactions t"
                  " LEFT JOIN users u ON u.id=t.cashier_id WHERE 1=1";
    QVector<QVariant> params;
    if (!search.isEmpty()) {
        sql += " AND (t.invoice_number LIKE ? OR t.customer_name LIKE ?)";
        params << ("%" + search + "%") << ("%" + search + "%");
    }
    if (!dateFrom.isEmpty()) { sql += " AND DATE(t.created_at)>=?"; params << dateFrom; }
    if (!dateTo.isEmpty())   { sql += " AND DATE(t.created_at)<=?"; params << dateTo; }
    sql += " ORDER BY t.created_at DESC";
    return execSelect(sql, params);
}

QVariantMap Database::getTransactionDetail(int id) {
    auto r = execSelect(
        "SELECT t.*, u.full_name AS cashier_name FROM transactions t"
        " LEFT JOIN users u ON u.id=t.cashier_id WHERE t.id=?", {id});
    return r.isEmpty() ? QVariantMap{} : r[0];
}

QVector<QVariantMap> Database::getTransactionItems(int txId) {
    return execSelect("SELECT * FROM transaction_items WHERE transaction_id=?", {txId});
}

bool Database::voidTransaction(int id) {
    m_db.transaction();
    auto items = getTransactionItems(id);
    for (auto& it : items) {
        execNonQuery("UPDATE products SET stock=stock+? WHERE id=?",
                     {it["quantity"], it["product_id"]});
    }
    execNonQuery("UPDATE transactions SET status='voided' WHERE id=?", {id});
    m_db.commit();
    return true;
}

// ═══════════════════════════════════════════════════════════════
//  Stock / Inventory
// ═══════════════════════════════════════════════════════════════
QVector<QVariantMap> Database::getStockList(const QString& search) {
    QString sql = "SELECT p.*, c.name AS category_name FROM products p"
                  " LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1";
    QVector<QVariant> params;
    if (!search.isEmpty()) {
        sql += " AND (p.name LIKE ? OR p.barcode LIKE ?)";
        params << ("%" + search + "%") << ("%" + search + "%");
    }
    sql += " ORDER BY p.stock ASC";
    return execSelect(sql, params);
}

QVector<QVariantMap> Database::getStockMovements(int limit) {
    return execSelect(
        "SELECT sm.*, p.name AS product_name FROM stock_movements sm"
        " LEFT JOIN products p ON p.id=sm.product_id"
        " ORDER BY sm.created_at DESC LIMIT ?", {limit});
}

bool Database::adjustStock(int productId, int qty, const QString& type,
                            const QString& notes) {
    m_db.transaction();
    if (type == "in")
        execNonQuery("UPDATE products SET stock=stock+? WHERE id=?", {qty, productId});
    else
        execNonQuery("UPDATE products SET stock=stock-? WHERE id=?", {qty, productId});

    execNonQuery("INSERT INTO stock_movements(product_id,type,quantity,notes)"
                 " VALUES(?,?,?,?)", {productId, type, qty, notes});
    m_db.commit();
    return true;
}

// ═══════════════════════════════════════════════════════════════
//  Suppliers
// ═══════════════════════════════════════════════════════════════
QVector<QVariantMap> Database::getSuppliers(const QString& search) {
    if (search.isEmpty())
        return execSelect("SELECT * FROM suppliers ORDER BY name");
    return execSelect("SELECT * FROM suppliers WHERE name LIKE ? OR contact LIKE ?"
                      " ORDER BY name", {"%" + search + "%", "%" + search + "%"});
}

bool Database::addSupplier(const QVariantMap& s) {
    return execNonQuery(
        "INSERT INTO suppliers(name,contact,address,email,notes) VALUES(?,?,?,?,?)",
        {s["name"], s["contact"], s["address"], s["email"], s["notes"]});
}

bool Database::updateSupplier(int id, const QVariantMap& s) {
    return execNonQuery(
        "UPDATE suppliers SET name=?,contact=?,address=?,email=?,notes=? WHERE id=?",
        {s["name"], s["contact"], s["address"], s["email"], s["notes"], id});
}

bool Database::deleteSupplier(int id) {
    return execNonQuery("DELETE FROM suppliers WHERE id=?", {id});
}

// ═══════════════════════════════════════════════════════════════
//  Expenses
// ═══════════════════════════════════════════════════════════════
QVector<QVariantMap> Database::getExpenses(const QString& search,
                                            const QString& dateFrom,
                                            const QString& dateTo) {
    QString sql = "SELECT * FROM expenses WHERE 1=1";
    QVector<QVariant> params;
    if (!search.isEmpty()) {
        sql += " AND (category LIKE ? OR description LIKE ?)";
        params << ("%" + search + "%") << ("%" + search + "%");
    }
    if (!dateFrom.isEmpty()) { sql += " AND date>=?"; params << dateFrom; }
    if (!dateTo.isEmpty())   { sql += " AND date<=?"; params << dateTo; }
    sql += " ORDER BY date DESC";
    return execSelect(sql, params);
}

bool Database::addExpense(const QVariantMap& e) {
    return execNonQuery(
        "INSERT INTO expenses(category,description,amount,date) VALUES(?,?,?,?)",
        {e["category"], e["description"], e["amount"], e["date"]});
}

bool Database::deleteExpense(int id) {
    return execNonQuery("DELETE FROM expenses WHERE id=?", {id});
}

double Database::getTotalExpenses(const QString& dateFrom, const QString& dateTo) {
    QString sql = "SELECT COALESCE(SUM(amount),0) AS v FROM expenses WHERE 1=1";
    QVector<QVariant> params;
    if (!dateFrom.isEmpty()) { sql += " AND date>=?"; params << dateFrom; }
    if (!dateTo.isEmpty())   { sql += " AND date<=?"; params << dateTo; }
    auto r = execSelect(sql, params);
    return r.isEmpty() ? 0 : r[0]["v"].toDouble();
}

// ═══════════════════════════════════════════════════════════════
//  Users
// ═══════════════════════════════════════════════════════════════
QVector<QVariantMap> Database::getUsers() {
    return execSelect("SELECT * FROM users ORDER BY id");
}

bool Database::addUser(const QVariantMap& u) {
    return execNonQuery(
        "INSERT INTO users(username,password,full_name,role) VALUES(?,?,?,?)",
        {u["username"], u["password"], u["full_name"], u["role"]});
}

bool Database::updateUser(int id, const QVariantMap& u) {
    if (u.contains("password") && !u["password"].toString().isEmpty())
        return execNonQuery(
            "UPDATE users SET username=?,password=?,full_name=?,role=? WHERE id=?",
            {u["username"], u["password"], u["full_name"], u["role"], id});
    return execNonQuery(
        "UPDATE users SET username=?,full_name=?,role=? WHERE id=?",
        {u["username"], u["full_name"], u["role"], id});
}

bool Database::toggleUser(int id, bool active) {
    return execNonQuery("UPDATE users SET is_active=? WHERE id=?", {active ? 1 : 0, id});
}

// ═══════════════════════════════════════════════════════════════
//  Settings
// ═══════════════════════════════════════════════════════════════
QVariantMap Database::getSettings() {
    auto r = execSelect("SELECT * FROM settings WHERE id=1");
    return r.isEmpty() ? QVariantMap{} : r[0];
}

bool Database::updateSettings(const QVariantMap& s) {
    return execNonQuery(
        "UPDATE settings SET store_name=?,store_address=?,store_phone=?,"
        "tax_rate=?,receipt_footer=?,low_stock_threshold=? WHERE id=1",
        {s["store_name"], s["store_address"], s["store_phone"],
         s["tax_rate"], s["receipt_footer"], s["low_stock_threshold"]});
}

int Database::getLowStockThreshold() {
    auto r = execSelect("SELECT low_stock_threshold AS v FROM settings WHERE id=1");
    return r.isEmpty() ? 10 : r[0]["v"].toInt();
}

// ═══════════════════════════════════════════════════════════════
//  Reports
// ═══════════════════════════════════════════════════════════════
QVector<QVariantMap> Database::getSalesReport(const QString& from,
                                               const QString& to) {
    return execSelect(
        "SELECT DATE(created_at) AS date, COUNT(*) AS tx_count,"
        " SUM(subtotal) AS subtotal, SUM(tax) AS tax, SUM(total) AS total"
        " FROM transactions WHERE status='completed'"
        " AND DATE(created_at) BETWEEN ? AND ?"
        " GROUP BY DATE(created_at) ORDER BY date", {from, to});
}

QVector<QVariantMap> Database::getProductReport(const QString& from,
                                                 const QString& to) {
    return execSelect(
        "SELECT ti.product_name, SUM(ti.quantity) AS qty,"
        " SUM(ti.subtotal) AS revenue,"
        " SUM(ti.quantity * (ti.unit_price - COALESCE(p.buy_price,0))) AS profit"
        " FROM transaction_items ti"
        " JOIN transactions t ON t.id=ti.transaction_id"
        " LEFT JOIN products p ON p.id=ti.product_id"
        " WHERE t.status='completed' AND DATE(t.created_at) BETWEEN ? AND ?"
        " GROUP BY ti.product_name ORDER BY qty DESC", {from, to});
}

QVariantMap Database::getProfitReport(const QString& from, const QString& to) {
    QVariantMap m;
    auto r1 = execSelect(
        "SELECT COALESCE(SUM(total),0) AS revenue,"
        " COALESCE(SUM(subtotal),0) AS subtotal,"
        " COALESCE(SUM(tax),0) AS tax,"
        " COUNT(*) AS tx_count"
        " FROM transactions WHERE status='completed'"
        " AND DATE(created_at) BETWEEN ? AND ?", {from, to});
    if (!r1.isEmpty()) {
        m["revenue"]  = r1[0]["revenue"];
        m["subtotal"] = r1[0]["subtotal"];
        m["tax"]      = r1[0]["tax"];
        m["tx_count"] = r1[0]["tx_count"];
    }
    auto r2 = execSelect(
        "SELECT COALESCE(SUM(ti.quantity * COALESCE(p.buy_price,0)),0) AS cogs"
        " FROM transaction_items ti"
        " JOIN transactions t ON t.id=ti.transaction_id"
        " LEFT JOIN products p ON p.id=ti.product_id"
        " WHERE t.status='completed' AND DATE(t.created_at) BETWEEN ? AND ?",{from,to});
    m["cogs"] = r2.isEmpty() ? 0 : r2[0]["cogs"];

    double expenses = getTotalExpenses(from, to);
    m["expenses"] = expenses;
    double rev = m["subtotal"].toDouble();
    double cogs = m["cogs"].toDouble();
    m["gross_profit"] = rev - cogs;
    m["net_profit"]   = rev - cogs - expenses;
    return m;
}
