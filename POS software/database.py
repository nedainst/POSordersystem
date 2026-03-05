"""
Database module for POS System
Handles all database operations using SQLite
"""
import sqlite3
import os
import hashlib
from datetime import datetime, timedelta
import random

DB_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), "pos_system.db")


def get_connection():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA foreign_keys = ON")
    return conn


def hash_password(password):
    return hashlib.sha256(password.encode()).hexdigest()


def init_database():
    """Initialize database with all required tables"""
    conn = get_connection()
    cursor = conn.cursor()

    # Users table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            full_name TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'cashier',
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP
        )
    """)

    # Categories table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL,
            description TEXT,
            color TEXT DEFAULT '#3498db',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    """)

    # Products table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            barcode TEXT UNIQUE,
            name TEXT NOT NULL,
            category_id INTEGER,
            buy_price REAL NOT NULL DEFAULT 0,
            sell_price REAL NOT NULL DEFAULT 0,
            stock INTEGER NOT NULL DEFAULT 0,
            min_stock INTEGER DEFAULT 5,
            unit TEXT DEFAULT 'pcs',
            image_path TEXT,
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )
    """)

    # Transactions table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_number TEXT UNIQUE NOT NULL,
            user_id INTEGER,
            customer_name TEXT DEFAULT 'Umum',
            total_amount REAL NOT NULL DEFAULT 0,
            discount_amount REAL DEFAULT 0,
            tax_amount REAL DEFAULT 0,
            final_amount REAL NOT NULL DEFAULT 0,
            payment_method TEXT DEFAULT 'cash',
            payment_amount REAL DEFAULT 0,
            change_amount REAL DEFAULT 0,
            status TEXT DEFAULT 'completed',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    """)

    # Transaction items table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS transaction_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            transaction_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            product_name TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            unit_price REAL NOT NULL,
            discount REAL DEFAULT 0,
            subtotal REAL NOT NULL,
            FOREIGN KEY (transaction_id) REFERENCES transactions(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )
    """)

    # Stock movements table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS stock_movements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            movement_type TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            previous_stock INTEGER,
            new_stock INTEGER,
            reference TEXT,
            notes TEXT,
            user_id INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    """)

    # Suppliers table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS suppliers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            contact_person TEXT,
            phone TEXT,
            email TEXT,
            address TEXT,
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    """)

    # Expenses table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS expenses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category TEXT NOT NULL,
            amount REAL NOT NULL,
            description TEXT,
            user_id INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    """)

    # Settings table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT
        )
    """)

    conn.commit()

    # Insert default admin if not exists
    cursor.execute("SELECT COUNT(*) FROM users")
    if cursor.fetchone()[0] == 0:
        cursor.execute("""
            INSERT INTO users (username, password, full_name, role)
            VALUES (?, ?, ?, ?)
        """, ("admin", hash_password("admin123"), "Administrator", "admin"))
        cursor.execute("""
            INSERT INTO users (username, password, full_name, role)
            VALUES (?, ?, ?, ?)
        """, ("kasir", hash_password("kasir123"), "Kasir Default", "cashier"))
        conn.commit()

    # Insert default settings
    default_settings = {
        "store_name": "TOKO SAYA",
        "store_address": "Jl. Contoh No. 123, Kota",
        "store_phone": "021-1234567",
        "tax_rate": "11",
        "currency_symbol": "Rp",
        "receipt_footer": "Terima kasih atas kunjungan Anda!",
        "theme": "dark",
        "low_stock_alert": "5"
    }
    for key, value in default_settings.items():
        cursor.execute("""
            INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)
        """, (key, value))
    conn.commit()

    # Insert sample categories if empty
    cursor.execute("SELECT COUNT(*) FROM categories")
    if cursor.fetchone()[0] == 0:
        sample_categories = [
            ("Makanan", "Produk makanan", "#e74c3c"),
            ("Minuman", "Produk minuman", "#3498db"),
            ("Snack", "Makanan ringan", "#f39c12"),
            ("Kebutuhan Rumah", "Peralatan rumah tangga", "#2ecc71"),
            ("Elektronik", "Produk elektronik", "#9b59b6"),
            ("Obat-obatan", "Produk kesehatan", "#1abc9c"),
            ("Alat Tulis", "Peralatan tulis kantor", "#e67e22"),
            ("Lainnya", "Produk lainnya", "#95a5a6"),
        ]
        cursor.executemany("""
            INSERT INTO categories (name, description, color) VALUES (?, ?, ?)
        """, sample_categories)
        conn.commit()

    # Insert sample products if empty
    cursor.execute("SELECT COUNT(*) FROM products")
    if cursor.fetchone()[0] == 0:
        sample_products = [
            ("8991234001", "Nasi Goreng Instan", 1, 2500, 4000, 150, 20, "pcs"),
            ("8991234002", "Mie Goreng Instan", 1, 2000, 3500, 200, 20, "pcs"),
            ("8991234003", "Roti Tawar", 1, 8000, 14000, 30, 5, "pcs"),
            ("8991234004", "Sarden Kaleng", 1, 10000, 15000, 50, 10, "pcs"),
            ("8991234005", "Teh Botol 500ml", 2, 3000, 5000, 120, 24, "botol"),
            ("8991234006", "Air Mineral 600ml", 2, 1500, 3000, 200, 48, "botol"),
            ("8991234007", "Kopi Sachet", 2, 1000, 2000, 300, 50, "pcs"),
            ("8991234008", "Susu UHT 250ml", 2, 4000, 6500, 80, 24, "pcs"),
            ("8991234009", "Jus Buah 200ml", 2, 3500, 5500, 60, 12, "pcs"),
            ("8991234010", "Keripik Kentang", 3, 5000, 9000, 70, 15, "pcs"),
            ("8991234011", "Cokelat Batang", 3, 7000, 12000, 50, 10, "pcs"),
            ("8991234012", "Biskuit Kaleng", 3, 25000, 40000, 25, 5, "pcs"),
            ("8991234013", "Kacang Kulit 200g", 3, 8000, 13000, 40, 10, "pcs"),
            ("8991234014", "Sabun Mandi", 4, 3000, 5500, 80, 20, "pcs"),
            ("8991234015", "Shampoo 170ml", 4, 12000, 18000, 45, 10, "botol"),
            ("8991234016", "Deterjen 1kg", 4, 15000, 22000, 35, 10, "pcs"),
            ("8991234017", "Pasta Gigi", 4, 8000, 13000, 55, 15, "pcs"),
            ("8991234018", "Baterai AA (2pcs)", 5, 7000, 12000, 40, 10, "pack"),
            ("8991234019", "Charger HP", 5, 25000, 45000, 15, 5, "pcs"),
            ("8991234020", "Obat Sakit Kepala", 6, 3000, 5000, 60, 15, "strip"),
            ("8991234021", "Obat Maag", 6, 4000, 7000, 40, 10, "strip"),
            ("8991234022", "Plester Luka", 6, 2000, 4000, 50, 15, "pack"),
            ("8991234023", "Pulpen", 7, 2000, 4000, 100, 20, "pcs"),
            ("8991234024", "Buku Tulis A5", 7, 3000, 5500, 80, 20, "pcs"),
            ("8991234025", "Tissue 250 sheets", 8, 5000, 9000, 60, 15, "pack"),
        ]
        cursor.executemany("""
            INSERT INTO products (barcode, name, category_id, buy_price, sell_price, stock, min_stock, unit)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        """, sample_products)
        conn.commit()

    # Insert sample transactions for demo
    cursor.execute("SELECT COUNT(*) FROM transactions")
    if cursor.fetchone()[0] == 0:
        _generate_sample_transactions(conn)

    conn.close()


def _generate_sample_transactions(conn):
    """Generate sample transaction data for the last 30 days"""
    cursor = conn.cursor()
    products = cursor.execute("SELECT id, name, sell_price, stock FROM products").fetchall()

    for day_offset in range(30, 0, -1):
        date = datetime.now() - timedelta(days=day_offset)
        num_transactions = random.randint(3, 10)

        for t in range(num_transactions):
            invoice = f"INV-{date.strftime('%Y%m%d')}-{t+1:04d}"
            num_items = random.randint(1, 5)
            selected = random.sample(list(products), min(num_items, len(products)))

            total = 0
            items = []
            for p in selected:
                qty = random.randint(1, 3)
                subtotal = p["sell_price"] * qty
                total += subtotal
                items.append((p["id"], p["name"], qty, p["sell_price"], 0, subtotal))

            tax = round(total * 0.11, 2)
            final = total + tax
            payment = (int(final / 10000) + 1) * 10000

            tx_time = date.replace(
                hour=random.randint(8, 21),
                minute=random.randint(0, 59),
                second=random.randint(0, 59)
            )

            cursor.execute("""
                INSERT INTO transactions (invoice_number, user_id, total_amount, tax_amount,
                    final_amount, payment_method, payment_amount, change_amount, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            """, (invoice, 1, total, tax, final, "cash", payment, payment - final,
                  tx_time.strftime("%Y-%m-%d %H:%M:%S")))

            tx_id = cursor.lastrowid
            for item in items:
                cursor.execute("""
                    INSERT INTO transaction_items (transaction_id, product_id, product_name,
                        quantity, unit_price, discount, subtotal)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                """, (tx_id, *item))

    conn.commit()


# ==================== USER OPERATIONS ====================

def authenticate_user(username, password):
    conn = get_connection()
    user = conn.execute(
        "SELECT * FROM users WHERE username=? AND password=? AND is_active=1",
        (username, hash_password(password))
    ).fetchone()
    if user:
        conn.execute("UPDATE users SET last_login=? WHERE id=?",
                      (datetime.now().strftime("%Y-%m-%d %H:%M:%S"), user["id"]))
        conn.commit()
    conn.close()
    return dict(user) if user else None


def get_all_users():
    conn = get_connection()
    rows = conn.execute("SELECT * FROM users ORDER BY id").fetchall()
    conn.close()
    return [dict(r) for r in rows]


def add_user(username, password, full_name, role):
    conn = get_connection()
    try:
        conn.execute(
            "INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,?)",
            (username, hash_password(password), full_name, role)
        )
        conn.commit()
        conn.close()
        return True, "User berhasil ditambahkan"
    except sqlite3.IntegrityError:
        conn.close()
        return False, "Username sudah digunakan"


def update_user(user_id, full_name, role, is_active, password=None):
    conn = get_connection()
    if password:
        conn.execute(
            "UPDATE users SET full_name=?, role=?, is_active=?, password=? WHERE id=?",
            (full_name, role, is_active, hash_password(password), user_id)
        )
    else:
        conn.execute(
            "UPDATE users SET full_name=?, role=?, is_active=? WHERE id=?",
            (full_name, role, is_active, user_id)
        )
    conn.commit()
    conn.close()


def delete_user(user_id):
    conn = get_connection()
    conn.execute("DELETE FROM users WHERE id=? AND id != 1", (user_id,))
    conn.commit()
    conn.close()


# ==================== CATEGORY OPERATIONS ====================

def get_all_categories():
    conn = get_connection()
    rows = conn.execute("SELECT * FROM categories ORDER BY name").fetchall()
    conn.close()
    return [dict(r) for r in rows]


def add_category(name, description="", color="#3498db"):
    conn = get_connection()
    try:
        conn.execute(
            "INSERT INTO categories (name, description, color) VALUES (?,?,?)",
            (name, description, color)
        )
        conn.commit()
        conn.close()
        return True, "Kategori berhasil ditambahkan"
    except sqlite3.IntegrityError:
        conn.close()
        return False, "Nama kategori sudah ada"


def update_category(cat_id, name, description, color):
    conn = get_connection()
    conn.execute(
        "UPDATE categories SET name=?, description=?, color=? WHERE id=?",
        (name, description, color, cat_id)
    )
    conn.commit()
    conn.close()


def delete_category(cat_id):
    conn = get_connection()
    conn.execute("UPDATE products SET category_id=NULL WHERE category_id=?", (cat_id,))
    conn.execute("DELETE FROM categories WHERE id=?", (cat_id,))
    conn.commit()
    conn.close()


# ==================== PRODUCT OPERATIONS ====================

def get_all_products(search="", category_id=None, active_only=True):
    conn = get_connection()
    query = """
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE 1=1
    """
    params = []
    if active_only:
        query += " AND p.is_active = 1"
    if search:
        query += " AND (p.name LIKE ? OR p.barcode LIKE ?)"
        params.extend([f"%{search}%", f"%{search}%"])
    if category_id:
        query += " AND p.category_id = ?"
        params.append(category_id)
    query += " ORDER BY p.name"
    rows = conn.execute(query, params).fetchall()
    conn.close()
    return [dict(r) for r in rows]


def get_product_by_barcode(barcode):
    conn = get_connection()
    row = conn.execute(
        "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.barcode=? AND p.is_active=1",
        (barcode,)
    ).fetchone()
    conn.close()
    return dict(row) if row else None


def get_product_by_id(product_id):
    conn = get_connection()
    row = conn.execute(
        "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=?",
        (product_id,)
    ).fetchone()
    conn.close()
    return dict(row) if row else None


def add_product(barcode, name, category_id, buy_price, sell_price, stock, min_stock, unit):
    conn = get_connection()
    try:
        conn.execute("""
            INSERT INTO products (barcode, name, category_id, buy_price, sell_price, stock, min_stock, unit)
            VALUES (?,?,?,?,?,?,?,?)
        """, (barcode, name, category_id, buy_price, sell_price, stock, min_stock, unit))
        conn.commit()
        conn.close()
        return True, "Produk berhasil ditambahkan"
    except sqlite3.IntegrityError:
        conn.close()
        return False, "Barcode sudah digunakan"


def update_product(product_id, barcode, name, category_id, buy_price, sell_price, stock, min_stock, unit):
    conn = get_connection()
    try:
        conn.execute("""
            UPDATE products SET barcode=?, name=?, category_id=?, buy_price=?, sell_price=?,
            stock=?, min_stock=?, unit=?, updated_at=? WHERE id=?
        """, (barcode, name, category_id, buy_price, sell_price, stock, min_stock, unit,
              datetime.now().strftime("%Y-%m-%d %H:%M:%S"), product_id))
        conn.commit()
        conn.close()
        return True, "Produk berhasil diupdate"
    except sqlite3.IntegrityError:
        conn.close()
        return False, "Barcode sudah digunakan produk lain"


def delete_product(product_id):
    conn = get_connection()
    conn.execute("UPDATE products SET is_active=0 WHERE id=?", (product_id,))
    conn.commit()
    conn.close()


def update_stock(product_id, quantity, movement_type, reference="", notes="", user_id=None):
    conn = get_connection()
    product = conn.execute("SELECT stock FROM products WHERE id=?", (product_id,)).fetchone()
    if not product:
        conn.close()
        return False, "Produk tidak ditemukan"

    prev_stock = product["stock"]
    if movement_type == "in":
        new_stock = prev_stock + quantity
    elif movement_type == "out":
        if prev_stock < quantity:
            conn.close()
            return False, "Stok tidak mencukupi"
        new_stock = prev_stock - quantity
    elif movement_type == "adjustment":
        new_stock = quantity
    else:
        conn.close()
        return False, "Tipe movement tidak valid"

    conn.execute("UPDATE products SET stock=?, updated_at=? WHERE id=?",
                 (new_stock, datetime.now().strftime("%Y-%m-%d %H:%M:%S"), product_id))
    conn.execute("""
        INSERT INTO stock_movements (product_id, movement_type, quantity, previous_stock, new_stock, reference, notes, user_id)
        VALUES (?,?,?,?,?,?,?,?)
    """, (product_id, movement_type, quantity, prev_stock, new_stock, reference, notes, user_id))
    conn.commit()
    conn.close()
    return True, "Stok berhasil diupdate"


def get_low_stock_products():
    conn = get_connection()
    rows = conn.execute("""
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.stock <= p.min_stock AND p.is_active = 1
        ORDER BY p.stock ASC
    """).fetchall()
    conn.close()
    return [dict(r) for r in rows]


def get_stock_movements(product_id=None, limit=100):
    conn = get_connection()
    query = """
        SELECT sm.*, p.name as product_name, u.full_name as user_name
        FROM stock_movements sm
        LEFT JOIN products p ON sm.product_id = p.id
        LEFT JOIN users u ON sm.user_id = u.id
    """
    params = []
    if product_id:
        query += " WHERE sm.product_id = ?"
        params.append(product_id)
    query += " ORDER BY sm.created_at DESC LIMIT ?"
    params.append(limit)
    rows = conn.execute(query, params).fetchall()
    conn.close()
    return [dict(r) for r in rows]


# ==================== TRANSACTION OPERATIONS ====================

def generate_invoice_number():
    now = datetime.now()
    conn = get_connection()
    count = conn.execute(
        "SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = DATE('now', 'localtime')"
    ).fetchone()[0]
    conn.close()
    return f"INV-{now.strftime('%Y%m%d')}-{count+1:04d}"


def create_transaction(user_id, items, customer_name="Umum", discount=0,
                       payment_method="cash", payment_amount=0, notes=""):
    conn = get_connection()
    invoice = generate_invoice_number()

    total = sum(item["subtotal"] for item in items)
    settings = get_all_settings()
    tax_rate = float(settings.get("tax_rate", "11")) / 100
    tax = round(total * tax_rate, 2)
    final = total - discount + tax
    change = payment_amount - final if payment_amount >= final else 0

    cursor = conn.cursor()
    cursor.execute("""
        INSERT INTO transactions (invoice_number, user_id, customer_name, total_amount,
            discount_amount, tax_amount, final_amount, payment_method, payment_amount,
            change_amount, notes)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    """, (invoice, user_id, customer_name, total, discount, tax, final,
          payment_method, payment_amount, change, notes))

    tx_id = cursor.lastrowid

    for item in items:
        cursor.execute("""
            INSERT INTO transaction_items (transaction_id, product_id, product_name,
                quantity, unit_price, discount, subtotal)
            VALUES (?,?,?,?,?,?,?)
        """, (tx_id, item["product_id"], item["product_name"], item["quantity"],
              item["unit_price"], item.get("discount", 0), item["subtotal"]))

        # Update stock
        cursor.execute("UPDATE products SET stock = stock - ? WHERE id=?",
                       (item["quantity"], item["product_id"]))
        # Record movement
        product = conn.execute("SELECT stock FROM products WHERE id=?",
                               (item["product_id"],)).fetchone()
        cursor.execute("""
            INSERT INTO stock_movements (product_id, movement_type, quantity,
                previous_stock, new_stock, reference, notes, user_id)
            VALUES (?,?,?,?,?,?,?,?)
        """, (item["product_id"], "out", item["quantity"],
              product["stock"] + item["quantity"], product["stock"],
              invoice, "Penjualan", user_id))

    conn.commit()
    conn.close()
    return invoice, final, change


def get_transactions(start_date=None, end_date=None, search="", limit=500):
    conn = get_connection()
    query = """
        SELECT t.*, u.full_name as cashier_name
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        WHERE 1=1
    """
    params = []
    if start_date:
        query += " AND DATE(t.created_at) >= ?"
        params.append(start_date)
    if end_date:
        query += " AND DATE(t.created_at) <= ?"
        params.append(end_date)
    if search:
        query += " AND (t.invoice_number LIKE ? OR t.customer_name LIKE ?)"
        params.extend([f"%{search}%", f"%{search}%"])
    query += " ORDER BY t.created_at DESC LIMIT ?"
    params.append(limit)

    rows = conn.execute(query, params).fetchall()
    conn.close()
    return [dict(r) for r in rows]


def get_transaction_items(transaction_id):
    conn = get_connection()
    rows = conn.execute("""
        SELECT ti.*, p.barcode
        FROM transaction_items ti
        LEFT JOIN products p ON ti.product_id = p.id
        WHERE ti.transaction_id = ?
    """, (transaction_id,)).fetchall()
    conn.close()
    return [dict(r) for r in rows]


def void_transaction(transaction_id, user_id):
    """Void a transaction and restore stock"""
    conn = get_connection()
    tx = conn.execute("SELECT * FROM transactions WHERE id=?", (transaction_id,)).fetchone()
    if not tx:
        conn.close()
        return False, "Transaksi tidak ditemukan"
    if tx["status"] == "voided":
        conn.close()
        return False, "Transaksi sudah di-void sebelumnya"

    items = conn.execute("SELECT * FROM transaction_items WHERE transaction_id=?",
                         (transaction_id,)).fetchall()
    for item in items:
        conn.execute("UPDATE products SET stock = stock + ? WHERE id=?",
                     (item["quantity"], item["product_id"]))
        product = conn.execute("SELECT stock FROM products WHERE id=?",
                               (item["product_id"],)).fetchone()
        conn.execute("""
            INSERT INTO stock_movements (product_id, movement_type, quantity,
                previous_stock, new_stock, reference, notes, user_id)
            VALUES (?,?,?,?,?,?,?,?)
        """, (item["product_id"], "in", item["quantity"],
              product["stock"] - item["quantity"], product["stock"],
              tx["invoice_number"], "Void transaksi", user_id))

    conn.execute("UPDATE transactions SET status='voided' WHERE id=?", (transaction_id,))
    conn.commit()
    conn.close()
    return True, "Transaksi berhasil di-void"


# ==================== DASHBOARD / REPORT OPERATIONS ====================

def get_dashboard_stats():
    conn = get_connection()
    today = datetime.now().strftime("%Y-%m-%d")

    # Today's sales
    today_sales = conn.execute("""
        SELECT COALESCE(SUM(final_amount), 0) as total,
               COUNT(*) as count
        FROM transactions
        WHERE DATE(created_at) = ? AND status='completed'
    """, (today,)).fetchone()

    # This month sales
    month_start = datetime.now().replace(day=1).strftime("%Y-%m-%d")
    month_sales = conn.execute("""
        SELECT COALESCE(SUM(final_amount), 0) as total,
               COUNT(*) as count
        FROM transactions
        WHERE DATE(created_at) >= ? AND status='completed'
    """, (month_start,)).fetchone()

    # Total products
    total_products = conn.execute(
        "SELECT COUNT(*) FROM products WHERE is_active=1"
    ).fetchone()[0]

    # Low stock count
    low_stock = conn.execute(
        "SELECT COUNT(*) FROM products WHERE stock <= min_stock AND is_active=1"
    ).fetchone()[0]

    # Daily sales for chart (last 7 days)
    daily_sales = []
    for i in range(6, -1, -1):
        date = (datetime.now() - timedelta(days=i)).strftime("%Y-%m-%d")
        result = conn.execute("""
            SELECT COALESCE(SUM(final_amount), 0) as total
            FROM transactions
            WHERE DATE(created_at) = ? AND status='completed'
        """, (date,)).fetchone()
        daily_sales.append({
            "date": date,
            "total": result["total"]
        })

    # Top 5 products
    top_products = conn.execute("""
        SELECT ti.product_name, SUM(ti.quantity) as total_qty, SUM(ti.subtotal) as total_sales
        FROM transaction_items ti
        JOIN transactions t ON ti.transaction_id = t.id
        WHERE t.status='completed' AND DATE(t.created_at) >= ?
        GROUP BY ti.product_name
        ORDER BY total_qty DESC
        LIMIT 5
    """, (month_start,)).fetchall()

    # Recent transactions
    recent = conn.execute("""
        SELECT t.*, u.full_name as cashier_name
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC LIMIT 10
    """).fetchall()

    conn.close()
    return {
        "today_sales": dict(today_sales),
        "month_sales": dict(month_sales),
        "total_products": total_products,
        "low_stock_count": low_stock,
        "daily_sales": daily_sales,
        "top_products": [dict(r) for r in top_products],
        "recent_transactions": [dict(r) for r in recent]
    }


def get_sales_report(start_date, end_date):
    conn = get_connection()
    rows = conn.execute("""
        SELECT DATE(created_at) as date,
               COUNT(*) as num_transactions,
               COALESCE(SUM(total_amount), 0) as total_sales,
               COALESCE(SUM(discount_amount), 0) as total_discount,
               COALESCE(SUM(tax_amount), 0) as total_tax,
               COALESCE(SUM(final_amount), 0) as final_total
        FROM transactions
        WHERE DATE(created_at) BETWEEN ? AND ? AND status='completed'
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at)
    """, (start_date, end_date)).fetchall()
    conn.close()
    return [dict(r) for r in rows]


def get_product_sales_report(start_date, end_date):
    conn = get_connection()
    rows = conn.execute("""
        SELECT ti.product_name,
               SUM(ti.quantity) as total_qty,
               SUM(ti.subtotal) as total_sales,
               p.buy_price,
               (SUM(ti.subtotal) - SUM(ti.quantity) * p.buy_price) as profit
        FROM transaction_items ti
        JOIN transactions t ON ti.transaction_id = t.id
        JOIN products p ON ti.product_id = p.id
        WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.status='completed'
        GROUP BY ti.product_id
        ORDER BY total_sales DESC
    """, (start_date, end_date)).fetchall()
    conn.close()
    return [dict(r) for r in rows]


# ==================== SUPPLIER OPERATIONS ====================

def get_all_suppliers():
    conn = get_connection()
    rows = conn.execute("SELECT * FROM suppliers ORDER BY name").fetchall()
    conn.close()
    return [dict(r) for r in rows]


def add_supplier(name, contact_person, phone, email, address):
    conn = get_connection()
    conn.execute("""
        INSERT INTO suppliers (name, contact_person, phone, email, address)
        VALUES (?,?,?,?,?)
    """, (name, contact_person, phone, email, address))
    conn.commit()
    conn.close()


def update_supplier(sup_id, name, contact_person, phone, email, address):
    conn = get_connection()
    conn.execute("""
        UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=? WHERE id=?
    """, (name, contact_person, phone, email, address, sup_id))
    conn.commit()
    conn.close()


def delete_supplier(sup_id):
    conn = get_connection()
    conn.execute("DELETE FROM suppliers WHERE id=?", (sup_id,))
    conn.commit()
    conn.close()


# ==================== EXPENSE OPERATIONS ====================

def get_expenses(start_date=None, end_date=None):
    conn = get_connection()
    query = "SELECT e.*, u.full_name as user_name FROM expenses e LEFT JOIN users u ON e.user_id = u.id WHERE 1=1"
    params = []
    if start_date:
        query += " AND DATE(e.created_at) >= ?"
        params.append(start_date)
    if end_date:
        query += " AND DATE(e.created_at) <= ?"
        params.append(end_date)
    query += " ORDER BY e.created_at DESC"
    rows = conn.execute(query, params).fetchall()
    conn.close()
    return [dict(r) for r in rows]


def add_expense(category, amount, description, user_id):
    conn = get_connection()
    conn.execute(
        "INSERT INTO expenses (category, amount, description, user_id) VALUES (?,?,?,?)",
        (category, amount, description, user_id)
    )
    conn.commit()
    conn.close()


# ==================== SETTINGS OPERATIONS ====================

def get_all_settings():
    conn = get_connection()
    rows = conn.execute("SELECT * FROM settings").fetchall()
    conn.close()
    return {r["key"]: r["value"] for r in rows}


def update_setting(key, value):
    conn = get_connection()
    conn.execute("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)", (key, value))
    conn.commit()
    conn.close()


if __name__ == "__main__":
    init_database()
    print("Database initialized successfully!")
