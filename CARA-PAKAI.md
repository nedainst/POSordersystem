# 🍜 Warung Order System - Portable Edition

## Cara Penggunaan (Siap Pakai!)

### Pertama Kali di Komputer Baru:

1. **Copy** seluruh folder `ordersystem` ke komputer tujuan
2. **Double-klik** `install.bat`
   - Script ini akan otomatis:
     - Download PHP portable (jika belum ada)
     - Setup database SQLite (tidak perlu MySQL!)
     - Konfigurasi semua yang diperlukan
     - Isi data awal (kategori, menu, dll)
3. **Double-klik** `start.bat` untuk menjalankan aplikasi
4. Browser akan otomatis terbuka di `http://localhost:8080`

### Sehari-hari (Setelah Install):

- **Jalankan**: Double-klik `start.bat`
- **Hentikan**: Tekan `Ctrl+C` di jendela server, atau double-klik `stop.bat`

---

## Login Admin

| | |
|---|---|
| **URL** | http://localhost:8080/admin |
| **Email** | admin@warung.com |
| **Password** | password |

---

## Struktur File Penting

```
ordersystem/
├── start.bat          ← Jalankan aplikasi (double-klik)
├── stop.bat           ← Hentikan aplikasi  
├── install.bat        ← Setup pertama kali
├── .env.portable      ← Konfigurasi portable
├── php/               ← PHP portable (otomatis di-download)
│   └── php.exe
├── database/
│   └── database.sqlite ← Database (semua data tersimpan di sini)
├── storage/
│   └── app/public/    ← File upload (gambar menu, dll)
└── ...
```

---

## FAQ

### ❓ Apakah perlu install apa-apa?
**Tidak!** Script `install.bat` akan otomatis download PHP portable. Yang dibutuhkan hanya:
- Windows 10/11
- Koneksi internet (hanya saat pertama kali, untuk download PHP)

### ❓ Bagaimana pindah ke komputer lain?
1. Copy seluruh folder `ordersystem`
2. Di komputer baru, jalankan `install.bat` (jika PHP belum ada)
3. Jalankan `start.bat`

**Tips**: Jika folder `php/` sudah berisi PHP portable, cukup copy seluruh folder dan langsung jalankan `start.bat` tanpa perlu internet!

### ❓ Bagaimana backup data?
Data tersimpan di file `database/database.sqlite`. Cukup copy file ini untuk backup.

### ❓ Bagaimana restore data?
Ganti file `database/database.sqlite` dengan file backup, lalu restart server.

### ❓ Port 8080 sudah dipakai?
Script akan otomatis mencoba port 8888. Atau edit `.env` dan ubah `APP_URL` sesuai port yang diinginkan.

### ❓ Bagaimana akses dari HP/tablet (jaringan lokal)?
1. Pastikan komputer server dan HP terhubung ke WiFi yang sama
2. Cari IP komputer server (buka CMD, ketik `ipconfig`)
3. Di HP, buka browser dan akses `http://[IP-KOMPUTER]:8080`
   - Contoh: `http://192.168.1.100:8080`

### ❓ Bagaimana reset semua data?
1. Hentikan server (`stop.bat`)
2. Hapus file `database/database.sqlite`
3. Jalankan `install.bat` lagi

---

## Persyaratan Minimum

- **OS**: Windows 10 atau lebih baru (64-bit)
- **RAM**: Minimal 2 GB
- **Disk**: ±200 MB (termasuk PHP portable)
- **Internet**: Hanya diperlukan saat pertama kali install (download PHP)

---

## Troubleshooting

### "PHP tidak ditemukan"
- Jalankan `install.bat` untuk download PHP portable
- Atau download manual dari https://windows.php.net/download/ (pilih VS16 x64 Non Thread Safe ZIP)
- Extract ke folder `php/` di dalam folder aplikasi

### "Port sudah digunakan"
- Jalankan `stop.bat` terlebih dahulu
- Atau restart komputer

### "Aplikasi tidak bisa diakses"
- Pastikan Windows Firewall mengizinkan koneksi
- Cek apakah antivirus memblokir `php.exe`
