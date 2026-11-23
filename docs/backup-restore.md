# Prosedur Backup dan Restore Database Inventaris

## 1. Tujuan

Dokumentasi ini menjelaskan prosedur pencadangan (backup) dan pemulihan (restore) database pada project Dashboard Inventaris yang berjalan menggunakan Docker Compose di Ubuntu/WSL.

Tujuannya adalah:

- Melindungi data inventaris dari kehilangan.
- Memastikan backup database berhasil dibuat.
- Memastikan file backup dapat digunakan untuk memulihkan database.
- Menyediakan panduan pemulihan ketika terjadi masalah pada database.

## 2. Lingkungan Sistem

| Komponen               | Keterangan                        |
| ---------------------- | --------------------------------- |
| Project                | Dashboard Inventaris              |
| Direktori project      | `~/inventaris-dashboard`          |
| Database utama         | `inventaris_db`                   |
| Container database     | `inventaris-mysql`                |
| Service Docker Compose | `mysql`                           |
| Folder backup          | `~/inventaris-dashboard/backups/` |
| Folder dokumentasi     | `~/inventaris-dashboard/docs/`    |
| Aplikasi web           | `http://localhost:5000` (Laravel) |
| Database engine        | MySQL 8.4                         |
| Lingkungan             | Ubuntu/WSL dan Docker Compose     |

## 3. Struktur File Penting

```text
inventaris-dashboard/
├── backups/
│   ├── inventaris_YYYYMMDD_HHMMSS.sql
│   └── cron.log
├── docs/
│   └── backup-restore.md
├── backup.sh
├── compose.yaml
└── .env
```

Keterangan:

- `backups/`: menyimpan file backup database.
- `backup.sh`: skrip untuk membuat dan membersihkan backup lama.
- `cron.log`: catatan hasil eksekusi backup terjadwal.
- `compose.yaml`: konfigurasi layanan Docker Compose.
- `.env`: menyimpan konfigurasi lingkungan, termasuk kredensial database. File ini tidak boleh dibagikan secara publik.

## 4. Prosedur Backup Manual

### 4.1 Pastikan layanan berjalan

Jalankan:

```bash
cd ~/inventaris-dashboard
docker compose ps
```

Pastikan service MySQL berjalan sebelum melakukan backup.

### 4.2 Jalankan skrip backup

```bash
cd ~/inventaris-dashboard
./backup.sh
```

Skrip akan membuat file SQL dengan nama berdasarkan tanggal dan waktu, memeriksa hasil backup, lalu membersihkan backup yang lebih lama dari tujuh hari sesuai konfigurasi skrip.

### 4.3 Periksa hasil backup

```bash
ls -lh ~/inventaris-dashboard/backups/
```

Tampilkan backup terbaru:

```bash
ls -lt ~/inventaris-dashboard/backups/*.sql
```

Periksa log backup:

```bash
tail -n 50 ~/inventaris-dashboard/backups/cron.log
```

Jika skrip dijalankan secara manual, hasilnya juga dapat dilihat langsung pada terminal.

### 4.4 Periksa isi file backup

Ganti nama file contoh dengan file backup yang tersedia:

```bash
grep -E 'CREATE TABLE|Dump completed on' \
  ~/inventaris-dashboard/backups/inventaris_YYYYMMDD_HHMMSS.sql
```

Pastikan file berisi struktur tabel yang diharapkan, seperti `admin` dan `aset`, serta memiliki penanda penyelesaian dump.

## 5. Backup Otomatis Menggunakan Cron

Backup dijadwalkan berjalan setiap hari pukul 23.00 waktu lokal WSL melalui cron.

Periksa jadwal:

```bash
crontab -l
```

Jadwal yang digunakan:

```cron
0 23 * * * /home/firdaus/inventaris-dashboard/backup.sh >> /home/firdaus/inventaris-dashboard/backups/cron.log 2>&1
```

Periksa log:

```bash
tail -n 50 ~/inventaris-dashboard/backups/cron.log
```

**Catatan penting:**

- WSL, Docker, dan layanan MySQL harus berjalan pada waktu backup.
- Cron tidak otomatis mengejar jadwal yang terlewat ketika WSL mati.
- Periksa log secara berkala untuk memastikan backup berjalan.
- Skrip menyimpan backup selama tujuh hari sesuai kebijakan pembersihannya.
- Simpan salinan backup penting di lokasi lain yang aman agar tidak bergantung hanya pada komputer yang sama.

## 6. Prosedur Uji Restore ke Database Terpisah

Uji restore harus dilakukan pada database pengujian, bukan database utama.

### 6.1 Tentukan file backup

Contoh file backup:

```text
backups/inventaris_20261021_122904.sql
```

Ganti nama file tersebut jika ingin menguji backup yang berbeda.

### 6.2 Siapkan database pengujian

Contoh nama database pengujian:

```text
inventaris_restore_test_20261021_122904
```

Jika database pengujian belum dibuat, buat database khusus pengujian terlebih dahulu. Jangan menggunakan nama `inventaris_db` untuk pengujian restore.

### 6.3 Import file SQL

Gunakan kredensial database yang sesuai dengan konfigurasi project. Jangan menuliskan password secara langsung ke perintah atau membagikannya di log.

Contoh pola import:

```bash
cd ~/inventaris-dashboard

docker compose exec -T mysql sh -c '
set -eu
MYSQL_CNF=$(mktemp)
trap '\''rm -f "$MYSQL_CNF"'\'' EXIT
chmod 600 "$MYSQL_CNF"
printf "[client]\nuser=%s\npassword=%s\n" \
  "$MYSQL_USER" "$MYSQL_PASSWORD" > "$MYSQL_CNF"

mysql --defaults-extra-file="$MYSQL_CNF" \
  inventaris_restore_test_20261021_122904
' < backups/inventaris_20261021_122904.sql
```

**Peringatan:** Database tujuan pada perintah di atas harus sudah dibuat terlebih dahulu. Pastikan nama database tujuan benar sebelum menjalankan import.

### 6.4 Verifikasi hasil restore

Periksa tabel yang berhasil dipulihkan:

```bash
docker compose exec mysql sh -c '
mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" \
  -D inventaris_restore_test_20261021_122904 \
  -e "SHOW TABLES;"
'
```

Periksa jumlah data:

```bash
docker compose exec mysql sh -c '
mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" \
  -D inventaris_restore_test_20261021_122904 \
  -e "SELECT COUNT(*) AS jumlah_admin FROM admin;
      SELECT COUNT(*) AS jumlah_aset FROM aset;"
'
```

Bandingkan tabel dan jumlah data dengan isi backup yang diharapkan.

**Catatan:** Ganti nama database pada perintah jika menggunakan database pengujian lain. Hindari memasukkan password ke riwayat shell; untuk penggunaan rutin, gunakan file konfigurasi client sementara dengan izin akses terbatas.

## 7. Prosedur Pemulihan Database Utama

Pemulihan ke database utama hanya dilakukan jika benar-benar diperlukan dan setelah file backup diverifikasi.

1. Hentikan sementara aktivitas yang dapat mengubah data inventaris.
2. Pastikan file backup tersedia dan tidak kosong.
3. Catat kondisi database utama sebelum pemulihan.
4. Pastikan backup yang dipilih merupakan versi yang benar.
5. Buat cadangan tambahan dari kondisi database saat ini sebelum melakukan perubahan.
6. Lakukan pemulihan dengan prosedur yang sudah diuji pada database pengujian.
7. Verifikasi tabel, jumlah data, dan fungsi aplikasi setelah pemulihan.
8. Lanjutkan penggunaan aplikasi setelah hasil verifikasi dinyatakan sesuai.

**Peringatan:** Jangan mengimpor backup langsung ke `inventaris_db` tanpa memeriksa dampaknya. Import SQL dapat menimpa atau mengubah data yang ada, tergantung isi file backup.

## 8. Troubleshooting

### Backup tidak terbentuk

Periksa layanan dan skrip:

```bash
cd ~/inventaris-dashboard
docker compose ps
ls -l backup.sh
tail -n 50 backups/cron.log
```

Pastikan skrip dapat dijalankan dan database aktif.

### Akses database ditolak

Periksa konfigurasi database pada `.env` dan pastikan kredensial yang digunakan sesuai dengan akun database yang sudah diinisialisasi.

Jangan membagikan isi `.env`, karena dapat berisi password dan secret aplikasi.

### File backup kosong atau tidak lengkap

Jangan gunakan file tersebut untuk pemulihan. Jalankan backup ulang dan pastikan skrip menyatakan backup berhasil.

### Aplikasi menampilkan HTTP 500

Periksa log aplikasi:

```bash
cd ~/inventaris-dashboard
docker compose logs --tail=100 web
```

Periksa log database:

```bash
docker compose logs --tail=100 mysql
```

Jangan menghapus volume database sebagai langkah awal troubleshooting.

## 9. Checklist Pemeliharaan

- [x] Skrip backup tersedia.
- [x] Backup SQL berhasil dibuat.
- [x] Backup memuat tabel `admin` dan `aset`.
- [x] Restore telah diuji pada database terpisah.
- [x] Jumlah data hasil restore telah diverifikasi.
- [x] Jadwal backup otomatis telah dikonfigurasi.
- [x] Log backup dapat diperiksa.
- [x] Salp penting disimpan di lokasi lain yang aman.
- [x] Pemeriksaan backup dilakukan secara berkala.

## 10. Kesimpulan

Backup database merupakan langkah penting untuk menjaga keamanan data Dashboard Inventaris. Backup perlu diperiksa secara berkala, dan prosedur restore harus diuji pada database terpisah sebelum digunakan untuk memulihkan database utama.

Jangan menghapus volume MySQL atau menjalankan perintah yang menghapus data tanpa memastikan bahwa backup valid dan dapat digunakan.

