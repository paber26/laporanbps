# Admin Dashboard Laporan BPS

Sistem untuk mendigitalkan pembuatan **Laporan Perjalanan Dinas** BPS (mengacu pada
contoh *Laporan GC PBI JKN*). Output utama berupa dokumen **PDF** dan **Word (.docx)**
yang tata letaknya menyerupai dokumen resmi.

Dibangun sesuai [docs/arsitektur.md](docs/arsitektur.md).

## Stack

- **Laravel 12** (PHP 8.2) — backend & frontend
- **MySQL / MariaDB** (dikonfigurasi memakai MySQL bawaan XAMPP)
- **Laravel Breeze** (Blade + Tailwind CSS + Alpine) — auth & layout admin
- **CKEditor 5** — WYSIWYG editor (mirip Word) untuk uraian kegiatan
- **barryvdh/laravel-dompdf** — generate PDF (mendukung ukuran A4 / F4 / Legal)
- **phpoffice/phpword** — generate Word `.docx`

## Fitur

- Autentikasi (login + registrasi mandiri dengan NIP & unit kerja untuk petugas luar kantor)
- Dashboard statistik (total laporan, laporan bulan ini, laporan per petugas)
- CRUD Laporan dengan **form dinamis**:
  - *Repeater* Lampiran 1 (Uraian Kegiatan) — tambah/hapus baris
  - *Multiple upload* Lampiran 2 (Dokumentasi/foto)
  - Dropdown NIP & Pembiayaan (auto-fill data)
- Preview laporan dalam bentuk web
- Cetak **PDF** (pilih ukuran kertas A4 / F4 / Legal) & **Word (.docx)**
- Master data: Pegawai & Pembiayaan

## Skema Database

`users` · `pegawais` · `master_pembiayaans` · `laporans` · `laporan_uraians` · `laporan_dokumentasis`

## Cara Menjalankan

```bash
# 1. Dependency
composer install
npm install

# 2. Konfigurasi (.env sudah diarahkan ke MySQL: database "laporanbps")
cp .env.example .env      # jika belum ada .env
php artisan key:generate

# 3. Pastikan MySQL (XAMPP) menyala, lalu buat database:
#    CREATE DATABASE laporanbps;

# 4. Migrasi + data awal (master pembiayaan + akun demo)
php artisan migrate --seed

# 5. Symlink storage (untuk menampilkan foto dokumentasi)
php artisan storage:link

# 6. Build asset & jalankan
npm run build       # atau: npm run dev
php artisan serve
```

### Akun Demo

| Email               | Password   |
| ------------------- | ---------- |
| `admin@bps.go.id`   | `password` |

## Konfigurasi Database (.env)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laporanbps
DB_USERNAME=root
DB_PASSWORD=
```

> Menggunakan MySQL/MariaDB bawaan XAMPP (`/Applications/XAMPP/xamppfiles/bin/mysql`).
