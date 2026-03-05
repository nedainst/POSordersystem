package database

import (
	"crypto/sha256"
	"database/sql"
	"fmt"
	"math/rand"
	"os"
	"path/filepath"
	"time"

	_ "github.com/mattn/go-sqlite3"
)

var db *sql.DB

// ── Initialization ───────────────────────────────────────────

func InitDatabase() error {
	exePath, _ := os.Executable()
	dbPath := filepath.Join(filepath.Dir(exePath), "pos_system.db")
	// fallback to current dir
	if _, err := os.Stat(filepath.Dir(exePath)); err != nil {
		dbPath = "pos_system.db"
	}

	var err error
	db, err = sql.Open("sqlite3", dbPath+"?_foreign_keys=on&_journal_mode=WAL")
	if err != nil {
		return err
	}
	db.SetMaxOpenConns(1)

	if err := createTables(); err != nil {
		return err
	}
	if err := insertDefaults(); err != nil {
		return err
	}
	return nil
}

func DB() *sql.DB { return db }

func HashPassword(password string) string {
	h := sha256.Sum256([]byte(password))
	return fmt.Sprintf("%x", h)
}

func FormatRupiah(amount float64) string {
	neg := ""
	if amount < 0 {
		neg = "-"
		amount = -amount
	}
	whole := int64(amount)
	s := fmt.Sprintf("%d", whole)
	n := len(s)
	if n <= 3 {
		return neg + "Rp " + s
	}
	var result []byte
	for i, ch := range s {
		if (n-i)%3 == 0 && i != 0 {
			result = append(result, '.')
		}
		result = append(result, byte(ch))
	}
	return neg + "Rp " + string(result)
}

func createTables() error {
	tables := []string{
		`CREATE TABLE IF NOT EXISTS users (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			username TEXT UNIQUE NOT NULL,
			password TEXT NOT NULL,
			full_name TEXT NOT NULL,
			role TEXT NOT NULL DEFAULT 'cashier',
			is_active INTEGER DEFAULT 1,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			last_login TIMESTAMP
		)`,
		`CREATE TABLE IF NOT EXISTS categories (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			name TEXT UNIQUE NOT NULL,
			description TEXT,
			color TEXT DEFAULT '#3498db',
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		)`,
		`CREATE TABLE IF NOT EXISTS products (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			barcode TEXT UNIQUE,
			name TEXT NOT NULL,
			category_id INTEGER,
			buy_price REAL NOT NULL DEFAULT 0,
			sell_price REAL NOT NULL DEFAULT 0,
			stock INTEGER NOT NULL DEFAULT 0,
			min_stock INTEGER DEFAULT 5,
			unit TEXT DEFAULT 'pcs',
			is_active INTEGER DEFAULT 1,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			FOREIGN KEY (category_id) REFERENCES categories(id)
		)`,
		`CREATE TABLE IF NOT EXISTS transactions (
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
		)`,
		`CREATE TABLE IF NOT EXISTS transaction_items (
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
		)`,
		`CREATE TABLE IF NOT EXISTS stock_movements (
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
		)`,
		`CREATE TABLE IF NOT EXISTS suppliers (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			name TEXT NOT NULL,
			contact_person TEXT,
			phone TEXT,
			email TEXT,
			address TEXT,
			is_active INTEGER DEFAULT 1,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		)`,
		`CREATE TABLE IF NOT EXISTS expenses (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			category TEXT NOT NULL,
			amount REAL NOT NULL,
			description TEXT,
			user_id INTEGER,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			FOREIGN KEY (user_id) REFERENCES users(id)
		)`,
		`CREATE TABLE IF NOT EXISTS settings (
			key TEXT PRIMARY KEY,
			value TEXT
		)`,
	}
	for _, q := range tables {
		if _, err := db.Exec(q); err != nil {
			return fmt.Errorf("create table: %w", err)
		}
	}
	return nil
}

func insertDefaults() error {
	// Default users
	var count int
	db.QueryRow("SELECT COUNT(*) FROM users").Scan(&count)
	if count == 0 {
		db.Exec("INSERT INTO users (username,password,full_name,role) VALUES (?,?,?,?)",
			"admin", HashPassword("admin123"), "Administrator", "admin")
		db.Exec("INSERT INTO users (username,password,full_name,role) VALUES (?,?,?,?)",
			"kasir", HashPassword("kasir123"), "Kasir Default", "cashier")
	}

	// Default settings
	defaults := map[string]string{
		"store_name":      "TOKO SAYA",
		"store_address":   "Jl. Contoh No. 123, Kota",
		"store_phone":     "021-1234567",
		"tax_rate":        "11",
		"currency_symbol": "Rp",
		"receipt_footer":  "Terima kasih atas kunjungan Anda!",
		"low_stock_alert": "5",
	}
	for k, v := range defaults {
		db.Exec("INSERT OR IGNORE INTO settings (key,value) VALUES (?,?)", k, v)
	}

	// Default categories
	db.QueryRow("SELECT COUNT(*) FROM categories").Scan(&count)
	if count == 0 {
		cats := []struct{ name, desc, color string }{
			{"Makanan", "Produk makanan", "#e74c3c"},
			{"Minuman", "Produk minuman", "#3498db"},
			{"Snack", "Makanan ringan", "#f39c12"},
			{"Kebutuhan Rumah", "Peralatan rumah tangga", "#2ecc71"},
			{"Elektronik", "Produk elektronik", "#9b59b6"},
			{"Obat-obatan", "Produk kesehatan", "#1abc9c"},
			{"Alat Tulis", "Peralatan tulis kantor", "#e67e22"},
			{"Lainnya", "Produk lainnya", "#95a5a6"},
		}
		for _, c := range cats {
			db.Exec("INSERT INTO categories (name,description,color) VALUES (?,?,?)", c.name, c.desc, c.color)
		}
	}

	// Sample products
	db.QueryRow("SELECT COUNT(*) FROM products").Scan(&count)
	if count == 0 {
		prods := []struct {
			barcode  string
			name     string
			catID    int
			buy, sell float64
			stock, min int
			unit     string
		}{
			{"8991234001", "Nasi Goreng Instan", 1, 2500, 4000, 150, 20, "pcs"},
			{"8991234002", "Mie Goreng Instan", 1, 2000, 3500, 200, 20, "pcs"},
			{"8991234003", "Roti Tawar", 1, 8000, 14000, 30, 5, "pcs"},
			{"8991234004", "Sarden Kaleng", 1, 10000, 15000, 50, 10, "pcs"},
			{"8991234005", "Teh Botol 500ml", 2, 3000, 5000, 120, 24, "botol"},
			{"8991234006", "Air Mineral 600ml", 2, 1500, 3000, 200, 48, "botol"},
			{"8991234007", "Kopi Sachet", 2, 1000, 2000, 300, 50, "pcs"},
			{"8991234008", "Susu UHT 250ml", 2, 4000, 6500, 80, 24, "pcs"},
			{"8991234009", "Jus Buah 200ml", 2, 3500, 5500, 60, 12, "pcs"},
			{"8991234010", "Keripik Kentang", 3, 5000, 9000, 70, 15, "pcs"},
			{"8991234011", "Cokelat Batang", 3, 7000, 12000, 50, 10, "pcs"},
			{"8991234012", "Biskuit Kaleng", 3, 25000, 40000, 25, 5, "pcs"},
			{"8991234013", "Kacang Kulit 200g", 3, 8000, 13000, 40, 10, "pcs"},
			{"8991234014", "Sabun Mandi", 4, 3000, 5500, 80, 20, "pcs"},
			{"8991234015", "Shampoo 170ml", 4, 12000, 18000, 45, 10, "botol"},
			{"8991234016", "Deterjen 1kg", 4, 15000, 22000, 35, 10, "pcs"},
			{"8991234017", "Pasta Gigi", 4, 8000, 13000, 55, 15, "pcs"},
			{"8991234018", "Baterai AA (2pcs)", 5, 7000, 12000, 40, 10, "pack"},
			{"8991234019", "Charger HP", 5, 25000, 45000, 15, 5, "pcs"},
			{"8991234020", "Obat Sakit Kepala", 6, 3000, 5000, 60, 15, "strip"},
			{"8991234021", "Obat Maag", 6, 4000, 7000, 40, 10, "strip"},
			{"8991234022", "Plester Luka", 6, 2000, 4000, 50, 15, "pack"},
			{"8991234023", "Pulpen", 7, 2000, 4000, 100, 20, "pcs"},
			{"8991234024", "Buku Tulis A5", 7, 3000, 5500, 80, 20, "pcs"},
			{"8991234025", "Tissue 250 sheets", 8, 5000, 9000, 60, 15, "pack"},
		}
		for _, p := range prods {
			db.Exec(`INSERT INTO products (barcode,name,category_id,buy_price,sell_price,stock,min_stock,unit) VALUES (?,?,?,?,?,?,?,?)`,
				p.barcode, p.name, p.catID, p.buy, p.sell, p.stock, p.min, p.unit)
		}
	}

	// Sample transactions
	db.QueryRow("SELECT COUNT(*) FROM transactions").Scan(&count)
	if count == 0 {
		generateSampleTransactions()
	}
	return nil
}

func generateSampleTransactions() {
	type prod struct {
		id    int
		name  string
		price float64
	}
	rows, _ := db.Query("SELECT id, name, sell_price FROM products")
	var products []prod
	for rows.Next() {
		var p prod
		rows.Scan(&p.id, &p.name, &p.price)
		products = append(products, p)
	}
	rows.Close()

	rng := rand.New(rand.NewSource(42))
	now := time.Now()

	for dayOff := 30; dayOff > 0; dayOff-- {
		date := now.AddDate(0, 0, -dayOff)
		numTx := rng.Intn(8) + 3

		for t := 0; t < numTx; t++ {
			invoice := fmt.Sprintf("INV-%s-%04d", date.Format("20060102"), t+1)
			numItems := rng.Intn(5) + 1
			if numItems > len(products) {
				numItems = len(products)
			}

			perm := rng.Perm(len(products))
			var total float64
			type item struct {
				prodID int
				name   string
				qty    int
				price  float64
				sub    float64
			}
			var items []item
			for i := 0; i < numItems; i++ {
				p := products[perm[i]]
				qty := rng.Intn(3) + 1
				sub := p.price * float64(qty)
				total += sub
				items = append(items, item{p.id, p.name, qty, p.price, sub})
			}

			tax := float64(int(total*0.11*100)) / 100
			final := total + tax
			payment := float64((int(final/10000) + 1) * 10000)

			txTime := time.Date(date.Year(), date.Month(), date.Day(),
				rng.Intn(14)+8, rng.Intn(60), rng.Intn(60), 0, time.Local)

			res, err := db.Exec(`INSERT INTO transactions
				(invoice_number,user_id,total_amount,tax_amount,final_amount,
				 payment_method,payment_amount,change_amount,created_at)
				VALUES (?,?,?,?,?,?,?,?,?)`,
				invoice, 1, total, tax, final, "cash", payment, payment-final,
				txTime.Format("2006-01-02 15:04:05"))
			if err != nil {
				continue
			}
			txID, _ := res.LastInsertId()
			for _, it := range items {
				db.Exec(`INSERT INTO transaction_items
					(transaction_id,product_id,product_name,quantity,unit_price,discount,subtotal)
					VALUES (?,?,?,?,?,?,?)`,
					txID, it.prodID, it.name, it.qty, it.price, 0, it.sub)
			}
		}
	}
}

// ── User Operations ──────────────────────────────────────────

type User struct {
	ID        int
	Username  string
	Password  string
	FullName  string
	Role      string
	IsActive  int
	CreatedAt string
	LastLogin sql.NullString
}

func Authenticate(username, password string) (*User, error) {
	row := db.QueryRow(
		"SELECT id,username,password,full_name,role,is_active,created_at,last_login FROM users WHERE username=? AND password=? AND is_active=1",
		username, HashPassword(password))
	u := &User{}
	err := row.Scan(&u.ID, &u.Username, &u.Password, &u.FullName, &u.Role, &u.IsActive, &u.CreatedAt, &u.LastLogin)
	if err != nil {
		return nil, fmt.Errorf("login gagal")
	}
	db.Exec("UPDATE users SET last_login=? WHERE id=?", time.Now().Format("2006-01-02 15:04:05"), u.ID)
	return u, nil
}

func GetAllUsers() []User {
	rows, err := db.Query("SELECT id,username,password,full_name,role,is_active,created_at,last_login FROM users ORDER BY id")
	if err != nil {
		return nil
	}
	defer rows.Close()
	var users []User
	for rows.Next() {
		var u User
		rows.Scan(&u.ID, &u.Username, &u.Password, &u.FullName, &u.Role, &u.IsActive, &u.CreatedAt, &u.LastLogin)
		users = append(users, u)
	}
	return users
}

func AddUser(username, password, fullName, role string) (bool, string) {
	_, err := db.Exec("INSERT INTO users (username,password,full_name,role) VALUES (?,?,?,?)",
		username, HashPassword(password), fullName, role)
	if err != nil {
		return false, "Username sudah digunakan"
	}
	return true, "User berhasil ditambahkan"
}

func UpdateUser(userID int, fullName, role string, isActive int, password string) {
	if password != "" {
		db.Exec("UPDATE users SET full_name=?,role=?,is_active=?,password=? WHERE id=?",
			fullName, role, isActive, HashPassword(password), userID)
	} else {
		db.Exec("UPDATE users SET full_name=?,role=?,is_active=? WHERE id=?",
			fullName, role, isActive, userID)
	}
}

func DeleteUser(userID int) {
	db.Exec("DELETE FROM users WHERE id=? AND id != 1", userID)
}

// ── Category Operations ──────────────────────────────────────

type Category struct {
	ID          int
	Name        string
	Description string
	Color       string
}

func GetAllCategories() []Category {
	rows, err := db.Query("SELECT id,name,COALESCE(description,''),COALESCE(color,'#3498db') FROM categories ORDER BY name")
	if err != nil {
		return nil
	}
	defer rows.Close()
	var cats []Category
	for rows.Next() {
		var c Category
		rows.Scan(&c.ID, &c.Name, &c.Description, &c.Color)
		cats = append(cats, c)
	}
	return cats
}

func AddCategory(name, description, color string) (bool, string) {
	_, err := db.Exec("INSERT INTO categories (name,description,color) VALUES (?,?,?)", name, description, color)
	if err != nil {
		return false, "Nama kategori sudah ada"
	}
	return true, "Kategori berhasil ditambahkan"
}

func UpdateCategory(id int, name, description, color string) {
	db.Exec("UPDATE categories SET name=?,description=?,color=? WHERE id=?", name, description, color, id)
}

func DeleteCategory(id int) {
	db.Exec("UPDATE products SET category_id=NULL WHERE category_id=?", id)
	db.Exec("DELETE FROM categories WHERE id=?", id)
}

// ── Product Operations ───────────────────────────────────────

type Product struct {
	ID           int
	Barcode      string
	Name         string
	CategoryID   sql.NullInt64
	CategoryName string
	BuyPrice     float64
	SellPrice    float64
	Stock        int
	MinStock     int
	Unit         string
	IsActive     int
	CreatedAt    string
}

func GetAllProducts(search string, categoryID int, activeOnly bool) []Product {
	query := `SELECT p.id,COALESCE(p.barcode,''),p.name,p.category_id,COALESCE(c.name,''),
		p.buy_price,p.sell_price,p.stock,p.min_stock,COALESCE(p.unit,'pcs'),p.is_active,p.created_at
		FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE 1=1`
	var args []interface{}
	if activeOnly {
		query += " AND p.is_active=1"
	}
	if search != "" {
		query += " AND (p.name LIKE ? OR p.barcode LIKE ?)"
		args = append(args, "%"+search+"%", "%"+search+"%")
	}
	if categoryID > 0 {
		query += " AND p.category_id=?"
		args = append(args, categoryID)
	}
	query += " ORDER BY p.name"

	rows, err := db.Query(query, args...)
	if err != nil {
		return nil
	}
	defer rows.Close()
	var products []Product
	for rows.Next() {
		var p Product
		rows.Scan(&p.ID, &p.Barcode, &p.Name, &p.CategoryID, &p.CategoryName,
			&p.BuyPrice, &p.SellPrice, &p.Stock, &p.MinStock, &p.Unit, &p.IsActive, &p.CreatedAt)
		products = append(products, p)
	}
	return products
}

func GetProductByBarcode(barcode string) *Product {
	p := &Product{}
	err := db.QueryRow(`SELECT p.id,COALESCE(p.barcode,''),p.name,p.category_id,COALESCE(c.name,''),
		p.buy_price,p.sell_price,p.stock,p.min_stock,COALESCE(p.unit,'pcs'),p.is_active,p.created_at
		FROM products p LEFT JOIN categories c ON p.category_id=c.id
		WHERE p.barcode=? AND p.is_active=1`, barcode).Scan(
		&p.ID, &p.Barcode, &p.Name, &p.CategoryID, &p.CategoryName,
		&p.BuyPrice, &p.SellPrice, &p.Stock, &p.MinStock, &p.Unit, &p.IsActive, &p.CreatedAt)
	if err != nil {
		return nil
	}
	return p
}

func GetProductByID(id int) *Product {
	p := &Product{}
	err := db.QueryRow(`SELECT p.id,COALESCE(p.barcode,''),p.name,p.category_id,COALESCE(c.name,''),
		p.buy_price,p.sell_price,p.stock,p.min_stock,COALESCE(p.unit,'pcs'),p.is_active,p.created_at
		FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=?`, id).Scan(
		&p.ID, &p.Barcode, &p.Name, &p.CategoryID, &p.CategoryName,
		&p.BuyPrice, &p.SellPrice, &p.Stock, &p.MinStock, &p.Unit, &p.IsActive, &p.CreatedAt)
	if err != nil {
		return nil
	}
	return p
}

func AddProduct(barcode, name string, catID int, buy, sell float64, stock, minStock int, unit string) (bool, string) {
	_, err := db.Exec(`INSERT INTO products (barcode,name,category_id,buy_price,sell_price,stock,min_stock,unit)
		VALUES (?,?,?,?,?,?,?,?)`, barcode, name, catID, buy, sell, stock, minStock, unit)
	if err != nil {
		return false, "Barcode sudah digunakan"
	}
	return true, "Produk berhasil ditambahkan"
}

func UpdateProduct(id int, barcode, name string, catID int, buy, sell float64, stock, minStock int, unit string) (bool, string) {
	_, err := db.Exec(`UPDATE products SET barcode=?,name=?,category_id=?,buy_price=?,sell_price=?,
		stock=?,min_stock=?,unit=?,updated_at=? WHERE id=?`,
		barcode, name, catID, buy, sell, stock, minStock, unit, time.Now().Format("2006-01-02 15:04:05"), id)
	if err != nil {
		return false, "Barcode sudah digunakan produk lain"
	}
	return true, "Produk berhasil diupdate"
}

func DeleteProduct(id int) {
	db.Exec("UPDATE products SET is_active=0 WHERE id=?", id)
}

func UpdateStock(productID, quantity int, movementType, reference, notes string, userID int) (bool, string) {
	var prevStock int
	err := db.QueryRow("SELECT stock FROM products WHERE id=?", productID).Scan(&prevStock)
	if err != nil {
		return false, "Produk tidak ditemukan"
	}

	var newStock int
	switch movementType {
	case "in":
		newStock = prevStock + quantity
	case "out":
		if prevStock < quantity {
			return false, "Stok tidak mencukupi"
		}
		newStock = prevStock - quantity
	case "adjustment":
		newStock = quantity
	default:
		return false, "Tipe movement tidak valid"
	}

	db.Exec("UPDATE products SET stock=?,updated_at=? WHERE id=?", newStock, time.Now().Format("2006-01-02 15:04:05"), productID)
	db.Exec(`INSERT INTO stock_movements (product_id,movement_type,quantity,previous_stock,new_stock,reference,notes,user_id)
		VALUES (?,?,?,?,?,?,?,?)`, productID, movementType, quantity, prevStock, newStock, reference, notes, userID)
	return true, "Stok berhasil diupdate"
}

func GetLowStockProducts() []Product {
	rows, err := db.Query(`SELECT p.id,COALESCE(p.barcode,''),p.name,p.category_id,COALESCE(c.name,''),
		p.buy_price,p.sell_price,p.stock,p.min_stock,COALESCE(p.unit,'pcs'),p.is_active,p.created_at
		FROM products p LEFT JOIN categories c ON p.category_id=c.id
		WHERE p.stock<=p.min_stock AND p.is_active=1 ORDER BY p.stock ASC`)
	if err != nil {
		return nil
	}
	defer rows.Close()
	var products []Product
	for rows.Next() {
		var p Product
		rows.Scan(&p.ID, &p.Barcode, &p.Name, &p.CategoryID, &p.CategoryName,
			&p.BuyPrice, &p.SellPrice, &p.Stock, &p.MinStock, &p.Unit, &p.IsActive, &p.CreatedAt)
		products = append(products, p)
	}
	return products
}

type StockMovement struct {
	ID           int
	ProductID    int
	ProductName  string
	MovementType string
	Quantity     int
	PrevStock    int
	NewStock     int
	Reference    string
	Notes        string
	UserName     string
	CreatedAt    string
}

func GetStockMovements(productID, limit int) []StockMovement {
	query := `SELECT sm.id,sm.product_id,COALESCE(p.name,''),sm.movement_type,sm.quantity,
		COALESCE(sm.previous_stock,0),COALESCE(sm.new_stock,0),COALESCE(sm.reference,''),
		COALESCE(sm.notes,''),COALESCE(u.full_name,''),sm.created_at
		FROM stock_movements sm LEFT JOIN products p ON sm.product_id=p.id
		LEFT JOIN users u ON sm.user_id=u.id`
	var args []interface{}
	if productID > 0 {
		query += " WHERE sm.product_id=?"
		args = append(args, productID)
	}
	query += " ORDER BY sm.created_at DESC LIMIT ?"
	args = append(args, limit)

	rows, err := db.Query(query, args...)
	if err != nil {
		return nil
	}
	defer rows.Close()
	var mvs []StockMovement
	for rows.Next() {
		var m StockMovement
		rows.Scan(&m.ID, &m.ProductID, &m.ProductName, &m.MovementType, &m.Quantity,
			&m.PrevStock, &m.NewStock, &m.Reference, &m.Notes, &m.UserName, &m.CreatedAt)
		mvs = append(mvs, m)
	}
	return mvs
}

// ── Transaction Operations ───────────────────────────────────

type Transaction struct {
	ID            int
	InvoiceNumber string
	UserID        int
	CashierName   string
	CustomerName  string
	TotalAmount   float64
	DiscountAmount float64
	TaxAmount     float64
	FinalAmount   float64
	PaymentMethod string
	PaymentAmount float64
	ChangeAmount  float64
	Status        string
	Notes         string
	CreatedAt     string
}

type TransactionItem struct {
	ID          int
	TxID        int
	ProductID   int
	ProductName string
	Barcode     string
	Quantity    int
	UnitPrice   float64
	Discount    float64
	Subtotal    float64
}

func GenerateInvoiceNumber() string {
	now := time.Now()
	var count int
	db.QueryRow("SELECT COUNT(*) FROM transactions WHERE DATE(created_at)=DATE('now','localtime')").Scan(&count)
	return fmt.Sprintf("INV-%s-%04d", now.Format("20060102"), count+1)
}

type CartItem struct {
	ProductID   int
	ProductName string
	Quantity    int
	UnitPrice   float64
	Discount    float64
	Subtotal    float64
}

func CreateTransaction(userID int, items []CartItem, customerName string, discount float64, paymentMethod string, paymentAmount float64) (string, float64, float64) {
	invoice := GenerateInvoiceNumber()
	var total float64
	for _, it := range items {
		total += it.Subtotal
	}

	settings := GetAllSettings()
	taxRate := 11.0
	if v, ok := settings["tax_rate"]; ok {
		fmt.Sscanf(v, "%f", &taxRate)
	}
	tax := float64(int(total*taxRate/100*100)) / 100
	final := total - discount + tax
	change := 0.0
	if paymentAmount >= final {
		change = paymentAmount - final
	}

	res, err := db.Exec(`INSERT INTO transactions
		(invoice_number,user_id,customer_name,total_amount,discount_amount,tax_amount,
		 final_amount,payment_method,payment_amount,change_amount)
		VALUES (?,?,?,?,?,?,?,?,?,?)`,
		invoice, userID, customerName, total, discount, tax, final, paymentMethod, paymentAmount, change)
	if err != nil {
		return "", 0, 0
	}
	txID, _ := res.LastInsertId()

	for _, it := range items {
		db.Exec(`INSERT INTO transaction_items (transaction_id,product_id,product_name,quantity,unit_price,discount,subtotal)
			VALUES (?,?,?,?,?,?,?)`, txID, it.ProductID, it.ProductName, it.Quantity, it.UnitPrice, it.Discount, it.Subtotal)

		// Update stock
		db.Exec("UPDATE products SET stock=stock-? WHERE id=?", it.Quantity, it.ProductID)
		var curStock int
		db.QueryRow("SELECT stock FROM products WHERE id=?", it.ProductID).Scan(&curStock)
		db.Exec(`INSERT INTO stock_movements (product_id,movement_type,quantity,previous_stock,new_stock,reference,notes,user_id)
			VALUES (?,?,?,?,?,?,?,?)`, it.ProductID, "out", it.Quantity, curStock+it.Quantity, curStock, invoice, "Penjualan", userID)
	}
	return invoice, final, change
}

func GetTransactions(startDate, endDate, search string, limit int) []Transaction {
	query := `SELECT t.id,t.invoice_number,t.user_id,COALESCE(u.full_name,''),COALESCE(t.customer_name,'Umum'),
		t.total_amount,COALESCE(t.discount_amount,0),COALESCE(t.tax_amount,0),t.final_amount,
		COALESCE(t.payment_method,'cash'),COALESCE(t.payment_amount,0),COALESCE(t.change_amount,0),
		COALESCE(t.status,'completed'),COALESCE(t.notes,''),t.created_at
		FROM transactions t LEFT JOIN users u ON t.user_id=u.id WHERE 1=1`
	var args []interface{}
	if startDate != "" {
		query += " AND DATE(t.created_at)>=?"
		args = append(args, startDate)
	}
	if endDate != "" {
		query += " AND DATE(t.created_at)<=?"
		args = append(args, endDate)
	}
	if search != "" {
		query += " AND (t.invoice_number LIKE ? OR t.customer_name LIKE ?)"
		args = append(args, "%"+search+"%", "%"+search+"%")
	}
	query += " ORDER BY t.created_at DESC LIMIT ?"
	if limit <= 0 {
		limit = 500
	}
	args = append(args, limit)

	rows, err := db.Query(query, args...)
	if err != nil {
		return nil
	}
	defer rows.Close()
	var txs []Transaction
	for rows.Next() {
		var t Transaction
		rows.Scan(&t.ID, &t.InvoiceNumber, &t.UserID, &t.CashierName, &t.CustomerName,
			&t.TotalAmount, &t.DiscountAmount, &t.TaxAmount, &t.FinalAmount,
			&t.PaymentMethod, &t.PaymentAmount, &t.ChangeAmount, &t.Status, &t.Notes, &t.CreatedAt)
		txs = append(txs, t)
	}
	return txs
}

func GetTransactionItems(txID int) []TransactionItem {
	rows, err := db.Query(`SELECT ti.id,ti.transaction_id,ti.product_id,ti.product_name,
		COALESCE(p.barcode,''),ti.quantity,ti.unit_price,COALESCE(ti.discount,0),ti.subtotal
		FROM transaction_items ti LEFT JOIN products p ON ti.product_id=p.id WHERE ti.transaction_id=?`, txID)
	if err != nil {
		return nil
	}
	defer rows.Close()
	var items []TransactionItem
	for rows.Next() {
		var it TransactionItem
		rows.Scan(&it.ID, &it.TxID, &it.ProductID, &it.ProductName, &it.Barcode,
			&it.Quantity, &it.UnitPrice, &it.Discount, &it.Subtotal)
		items = append(items, it)
	}
	return items
}

func VoidTransaction(txID, userID int) (bool, string) {
	var status string
	err := db.QueryRow("SELECT status FROM transactions WHERE id=?", txID).Scan(&status)
	if err != nil {
		return false, "Transaksi tidak ditemukan"
	}
	if status == "voided" {
		return false, "Transaksi sudah di-void"
	}

	var invoice string
	db.QueryRow("SELECT invoice_number FROM transactions WHERE id=?", txID).Scan(&invoice)

	rows, _ := db.Query("SELECT product_id,quantity FROM transaction_items WHERE transaction_id=?", txID)
	defer rows.Close()
	for rows.Next() {
		var prodID, qty int
		rows.Scan(&prodID, &qty)
		db.Exec("UPDATE products SET stock=stock+? WHERE id=?", qty, prodID)
		var curStock int
		db.QueryRow("SELECT stock FROM products WHERE id=?", prodID).Scan(&curStock)
		db.Exec(`INSERT INTO stock_movements (product_id,movement_type,quantity,previous_stock,new_stock,reference,notes,user_id)
			VALUES (?,?,?,?,?,?,?,?)`, prodID, "in", qty, curStock-qty, curStock, invoice, "Void transaksi", userID)
	}
	db.Exec("UPDATE transactions SET status='voided' WHERE id=?", txID)
	return true, "Transaksi berhasil di-void"
}

// ── Dashboard / Report ───────────────────────────────────────

type DashboardStats struct {
	TodaySalesTotal  float64
	TodaySalesCount  int
	MonthSalesTotal  float64
	MonthSalesCount  int
	TotalProducts    int
	LowStockCount    int
	DailySales       []DailySale
	TopProducts      []TopProduct
	RecentTx         []Transaction
}

type DailySale struct {
	Date  string
	Total float64
}

type TopProduct struct {
	Name     string
	TotalQty int
	TotalSales float64
}

func GetDashboardStats() DashboardStats {
	var s DashboardStats
	today := time.Now().Format("2006-01-02")
	monthStart := time.Now().Format("2006-01") + "-01"

	db.QueryRow("SELECT COALESCE(SUM(final_amount),0),COUNT(*) FROM transactions WHERE DATE(created_at)=? AND status='completed'", today).
		Scan(&s.TodaySalesTotal, &s.TodaySalesCount)
	db.QueryRow("SELECT COALESCE(SUM(final_amount),0),COUNT(*) FROM transactions WHERE DATE(created_at)>=? AND status='completed'", monthStart).
		Scan(&s.MonthSalesTotal, &s.MonthSalesCount)
	db.QueryRow("SELECT COUNT(*) FROM products WHERE is_active=1").Scan(&s.TotalProducts)
	db.QueryRow("SELECT COUNT(*) FROM products WHERE stock<=min_stock AND is_active=1").Scan(&s.LowStockCount)

	// Daily sales last 7 days
	for i := 6; i >= 0; i-- {
		date := time.Now().AddDate(0, 0, -i).Format("2006-01-02")
		var total float64
		db.QueryRow("SELECT COALESCE(SUM(final_amount),0) FROM transactions WHERE DATE(created_at)=? AND status='completed'", date).Scan(&total)
		s.DailySales = append(s.DailySales, DailySale{Date: date, Total: total})
	}

	// Top 5 products this month
	rows, _ := db.Query(`SELECT ti.product_name,SUM(ti.quantity),SUM(ti.subtotal)
		FROM transaction_items ti JOIN transactions t ON ti.transaction_id=t.id
		WHERE t.status='completed' AND DATE(t.created_at)>=?
		GROUP BY ti.product_name ORDER BY SUM(ti.quantity) DESC LIMIT 5`, monthStart)
	if rows != nil {
		defer rows.Close()
		for rows.Next() {
			var tp TopProduct
			rows.Scan(&tp.Name, &tp.TotalQty, &tp.TotalSales)
			s.TopProducts = append(s.TopProducts, tp)
		}
	}

	// Recent 10 transactions
	s.RecentTx = GetTransactions("", "", "", 10)
	return s
}

type SalesReportRow struct {
	Date            string
	NumTransactions int
	TotalSales      float64
	TotalDiscount   float64
	TotalTax        float64
	FinalTotal      float64
}

func GetSalesReport(startDate, endDate string) []SalesReportRow {
	rows, err := db.Query(`SELECT DATE(created_at),COUNT(*),COALESCE(SUM(total_amount),0),
		COALESCE(SUM(discount_amount),0),COALESCE(SUM(tax_amount),0),COALESCE(SUM(final_amount),0)
		FROM transactions WHERE DATE(created_at) BETWEEN ? AND ? AND status='completed'
		GROUP BY DATE(created_at) ORDER BY DATE(created_at)`, startDate, endDate)
	if err != nil {
		return nil
	}
	defer rows.Close()
	var result []SalesReportRow
	for rows.Next() {
		var r SalesReportRow
		rows.Scan(&r.Date, &r.NumTransactions, &r.TotalSales, &r.TotalDiscount, &r.TotalTax, &r.FinalTotal)
		result = append(result, r)
	}
	return result
}

type ProductSalesRow struct {
	ProductName string
	TotalQty    int
	TotalSales  float64
	BuyPrice    float64
	Profit      float64
}

func GetProductSalesReport(startDate, endDate string) []ProductSalesRow {
	rows, err := db.Query(`SELECT ti.product_name,SUM(ti.quantity),SUM(ti.subtotal),p.buy_price,
		(SUM(ti.subtotal)-SUM(ti.quantity)*p.buy_price)
		FROM transaction_items ti JOIN transactions t ON ti.transaction_id=t.id
		JOIN products p ON ti.product_id=p.id
		WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.status='completed'
		GROUP BY ti.product_id ORDER BY SUM(ti.subtotal) DESC`, startDate, endDate)
	if err != nil {
		return nil
	}
	defer rows.Close()
	var result []ProductSalesRow
	for rows.Next() {
		var r ProductSalesRow
		rows.Scan(&r.ProductName, &r.TotalQty, &r.TotalSales, &r.BuyPrice, &r.Profit)
		result = append(result, r)
	}
	return result
}

// ── Supplier Operations ──────────────────────────────────────

type Supplier struct {
	ID            int
	Name          string
	ContactPerson string
	Phone         string
	Email         string
	Address       string
	IsActive      int
	CreatedAt     string
}

func GetAllSuppliers() []Supplier {
	rows, err := db.Query(`SELECT id,name,COALESCE(contact_person,''),COALESCE(phone,''),
		COALESCE(email,''),COALESCE(address,''),is_active,created_at FROM suppliers ORDER BY name`)
	if err != nil {
		return nil
	}
	defer rows.Close()
	var sups []Supplier
	for rows.Next() {
		var s Supplier
		rows.Scan(&s.ID, &s.Name, &s.ContactPerson, &s.Phone, &s.Email, &s.Address, &s.IsActive, &s.CreatedAt)
		sups = append(sups, s)
	}
	return sups
}

func AddSupplier(name, contact, phone, email, address string) {
	db.Exec("INSERT INTO suppliers (name,contact_person,phone,email,address) VALUES (?,?,?,?,?)",
		name, contact, phone, email, address)
}

func UpdateSupplier(id int, name, contact, phone, email, address string) {
	db.Exec("UPDATE suppliers SET name=?,contact_person=?,phone=?,email=?,address=? WHERE id=?",
		name, contact, phone, email, address, id)
}

func DeleteSupplier(id int) {
	db.Exec("DELETE FROM suppliers WHERE id=?", id)
}

// ── Expense Operations ───────────────────────────────────────

type Expense struct {
	ID          int
	Category    string
	Amount      float64
	Description string
	UserID      int
	UserName    string
	CreatedAt   string
}

func GetExpenses(startDate, endDate string) []Expense {
	query := `SELECT e.id,e.category,e.amount,COALESCE(e.description,''),COALESCE(e.user_id,0),
		COALESCE(u.full_name,''),e.created_at FROM expenses e LEFT JOIN users u ON e.user_id=u.id WHERE 1=1`
	var args []interface{}
	if startDate != "" {
		query += " AND DATE(e.created_at)>=?"
		args = append(args, startDate)
	}
	if endDate != "" {
		query += " AND DATE(e.created_at)<=?"
		args = append(args, endDate)
	}
	query += " ORDER BY e.created_at DESC"

	rows, err := db.Query(query, args...)
	if err != nil {
		return nil
	}
	defer rows.Close()
	var exps []Expense
	for rows.Next() {
		var e Expense
		rows.Scan(&e.ID, &e.Category, &e.Amount, &e.Description, &e.UserID, &e.UserName, &e.CreatedAt)
		exps = append(exps, e)
	}
	return exps
}

func AddExpense(category string, amount float64, description string, userID int) {
	db.Exec("INSERT INTO expenses (category,amount,description,user_id) VALUES (?,?,?,?)",
		category, amount, description, userID)
}

func DeleteExpense(id int) {
	db.Exec("DELETE FROM expenses WHERE id=?", id)
}

// ── Settings Operations ──────────────────────────────────────

func GetAllSettings() map[string]string {
	rows, err := db.Query("SELECT key,value FROM settings")
	if err != nil {
		return map[string]string{}
	}
	defer rows.Close()
	m := make(map[string]string)
	for rows.Next() {
		var k, v string
		rows.Scan(&k, &v)
		m[k] = v
	}
	return m
}

func UpdateSetting(key, value string) {
	db.Exec("INSERT OR REPLACE INTO settings (key,value) VALUES (?,?)", key, value)
}
