# 🍜 Warung Order System

Warung Order System adalah aplikasi web berbasis Laravel yang dirancang untuk mempermudah manajemen pesanan, pembayaran, dan menu di warung, kafe, atau restoran. Aplikasi ini bersifat portable, sehingga dapat dijalankan tanpa instalasi rumit.

## Fitur Utama
- Pemesanan menu oleh pelanggan melalui QR/tablet
- Manajemen kategori dan menu makanan/minuman
- Manajemen meja dengan QR code
- Tracking status pesanan (pending, diproses, siap, selesai, dibatalkan)
- Pembayaran: tunai, QRIS, transfer, e-wallet
- Laporan dan rekap pesanan
- Admin dashboard
- POS (Point of Sale) untuk kasir
- Backup dan restore database dengan mudah

## Teknologi yang Digunakan
- **Framework**: Laravel 12
- **Bahasa Pemrograman**: PHP 8.2
- **Database**: SQLite (tanpa MySQL)
- **Frontend**: Tailwind CSS, Vite, Axios

## Cara Instalasi
1. Clone repository ini ke komputer Anda:
   ```bash
   git clone https://github.com/username/repo-name.git
   ```
2. Masuk ke direktori project:
   ```bash
   cd ordersystem
   ```
3. Jalankan script instalasi:
   ```bash
   ./install.bat
   ```
4. Jalankan aplikasi:
   ```bash
   ./start.bat
   ```
5. Buka browser dan akses `http://localhost:8080`.

## Login Admin
- **URL**: `http://localhost:8080/admin`
- **Email**: `admin@warung.com`
- **Password**: `password`

## Kontribusi
Kami sangat terbuka untuk kontribusi! Jika Anda ingin membantu mengembangkan aplikasi ini, silakan:
1. Fork repository ini.
2. Buat branch baru untuk fitur atau perbaikan Anda.
3. Kirim pull request ke branch utama.

## Lisensi
Proyek ini dilisensikan di bawah lisensi MIT. Silakan lihat file LICENSE untuk detail lebih lanjut.

---
© 2026 Warung Order System
