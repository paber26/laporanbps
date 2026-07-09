# Arsitektur Sistem Admin Dashboard Laporan BPS

Sistem ini dirancang menggunakan **Laravel** (Backend & Frontend) dan **MySQL** (Database) untuk mendigitalkan proses pembuatan "Laporan Perjalanan Dinas" (seperti contoh Laporan GC PBI JKN). Output utama dari sistem ini adalah dokumen PDF yang tata letaknya menyerupai dokumen asli.

## 1. Stack Teknologi
*   **Backend:** Laravel (PHP)
*   **Database:** MySQL
*   **Frontend (Admin Panel):** Laravel Blade dikombinasikan dengan Bootstrap atau Tailwind CSS. *Rekomendasi:* Menggunakan **Filament PHP** (sebuah TALL stack admin panel untuk Laravel) untuk mempercepat pembuatan CRUD dan form dinamis.
*   **WYSIWYG Editor:** Sangat direkomendasikan menggunakan **TinyMCE** versi gratis atau **CKEditor**. Keduanya memiliki tampilan antarmuka dan *toolbar* yang sangat mirip dengan Microsoft Word, sehingga pengguna akan sangat familiar saat mengedit isian laporan.
*   **Document Generator:** 
    *   **PDF:** `barryvdh/laravel-dompdf` (HTML/CSS sederhana) atau `spatie/laravel-pdf` (rendering CSS presisi).
    *   **Word (.docx):** `phpoffice/phpword` (library PHP untuk generate file MS Word).

---

## 2. Desain Skema Database (MySQL)

Berdasarkan struktur PDF laporan yang ada, kita membutuhkan skema relasional berikut:

### `users` & `pegawais` (Data Pegawai)
Menyimpan data petugas yang login dan membuat laporan. Dilengkapi dengan fitur registrasi sederhana untuk mengakomodasi pengguna dari luar kantor BPS asal.
*   `id`, `user_id` (FK), `nip`, `nama`, `unit_kerja` (atau `asal_bps` / Nama Kantor BPS), `tanda_tangan_path` (opsional untuk *digital signature*).

### `master_pembiayaans` (Data Master Anggaran)
Menyimpan referensi pembiayaan agar tidak perlu diketik ulang (Dropdown pilihan).
*   `id`, `program`, `kegiatan`, `ro`, `komponen`, `akun`.

### `laporans` (Tabel Utama Laporan)
Menyimpan informasi header dan surat pengantar laporan.
*   `id`, `pegawai_id` (FK), `pembiayaan_id` (FK), `perihal_laporan`, `tempat_laporan` (misal: Amurang Barat), `tanggal_laporan`, `lokasi_tujuan`, `created_at`, `updated_at`.

### `laporan_uraians` (Lampiran 1 - Uraian Kegiatan)
Menyimpan tabel detail kegiatan (1 Laporan memiliki Banyak Uraian - relasi *One to Many*).
*   `id`, `laporan_id` (FK), `tanggal_kegiatan`, `jam_mulai`, `jam_selesai`, `uraian_text` (tipe Text/LongText).

### `laporan_dokumentasis` (Lampiran 2 - Dokumentasi)
Menyimpan foto-foto kegiatan (1 Laporan memiliki Banyak Foto - relasi *One to Many*).
*   `id`, `laporan_id` (FK), `image_path`.

---

## 3. Struktur Aplikasi (MVC)

### Models
*   `Laporan`: Memiliki relasi `hasMany` ke `LaporanUraian` dan `LaporanDokumentasi`, serta `belongsTo` ke `Pegawai` dan `MasterPembiayaan`.

### Controllers
*   `DashboardController`: Menampilkan statistik laporan (jumlah laporan bulan ini, laporan per petugas, dsb).
*   `LaporanController`:
    *   `create()` & `store()`: Mengelola form input pembuatan laporan baru. Form ini harus dinamis (mendukung penambahan baris untuk Uraian Kegiatan dan *multiple file upload* untuk foto Dokumentasi).
    *   `show()`: Preview laporan dalam bentuk web HTML sebelum dicetak.
    *   `exportPdf()`: Fungsi untuk memuat view laporan dan merender file PDF yang siap diunduh dengan parameter **ukuran kertas** (misal: A4, F4/Legal).
    *   `exportWord()`: Fungsi untuk men-generate dokumen Microsoft Word (`.docx`) menggunakan *template processing* sehingga pengguna bisa men-download lalu mengedit file Word tersebut.

### Views (Blade Templates)
*   **Layout Admin:** Struktur dasar dashboard (Sidebar navigasi, header, content area).
*   **Form Laporan (`laporan/create.blade.php`):** Form kompleks dengan integrasi JavaScript untuk fitur *repeater* (menambah/menghapus baris form uraian secara dinamis) dan unggah banyak gambar.
*   **Template Cetak PDF (`laporan/pdf-template.blade.php`):** View khusus yang didesain sedemikian rupa untuk memastikan hasil cetak sesuai dengan format resmi:
    *   *Halaman 1:* Kop Surat, Header, List Pegawai, Detail Pembiayaan, Tanda Tangan.
    *   *Halaman 2:* Table Lampiran 1 (Uraian Kegiatan).
    *   *Halaman 3:* Grid Foto Lampiran 2 (Dokumentasi).

---

## 4. Alur Kerja (Workflow) Sistem

1.  **Akses Sistem:** Petugas BPS login ke Dashboard.
2.  **Input Data (Create Laporan):**
    *   Petugas mengisi form awal: Perihal, Tempat, Tanggal, dan Lokasi Tujuan.
    *   Memilih NIP dan Pembiayaan dari *dropdown* yang ada (Auto-fill data).
    *   **Isi Lampiran 1:** Petugas mengisi uraian kegiatan. Petugas dapat menekan tombol "Tambah Uraian" untuk setiap log kegiatan yang berbeda waktunya.
    *   **Isi Lampiran 2:** Petugas mengunggah satu atau lebih foto dokumentasi kegiatan.
3.  **Simpan:** Data disimpan dengan aman ke dalam tabel-tabel MySQL (`laporans`, `laporan_uraians`, `laporan_dokumentasis`).
4.  **Cetak Dokumen:** Petugas memilih opsi **ukuran kertas cetak** (seperti A4 atau F4), lalu menekan tombol "Cetak PDF" atau "Cetak Word". Sistem akan memproses dan mengunduh file `.pdf` atau `.docx` sesuai pilihan.
