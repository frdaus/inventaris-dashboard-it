# IT Inventory Dashboard

Dashboard inventaris IT berbasis Laravel 13, PHP 8.5, dan MySQL 8.4.

## Menjalankan dengan Docker

Pastikan `.env` di root berisi konfigurasi MySQL dan rahasia. Untuk instalasi
baru, salin `.env.example` menjadi `.env`, lalu isi password MySQL dan `APP_KEY`.
`DB_PASSWORD` dan `MYSQL_PASSWORD` harus sama. Jangan menimpa `.env` yang sudah
berisi konfigurasi lokal. Jalankan:

```bash
docker compose up --build -d
```

Buka `http://localhost:5000`. Database MySQL yang sudah ada tetap digunakan;
migrasi Laravel hanya membuat tabel inventaris jika belum ada. Login memakai
akun admin yang sudah terdaftar. Hash password Werkzeug lama diverifikasi saat
login pertama, lalu diperbarui ke hash Laravel tanpa meminta reset password.

Untuk membuat akun admin baru:

```bash
docker compose exec web php artisan admin:create
```

Password dimasukkan melalui prompt tersembunyi dan disimpan sebagai hash.
Jika digunakan di server baru, atur `APP_KEY` secara aman di environment;
secara lokal, jika belum diatur, Laravel memakai kunci turunan dari
`FLASK_SECRET_KEY` yang sudah ada.

## Pengembangan dan pengujian

```bash
cd laravel-app
composer install
composer test
```

Fitur mencakup login dengan pembatasan percobaan, dashboard statistik, CRUD
aset dan audit log, pencarian/filter/paginasi, serta export Excel.
