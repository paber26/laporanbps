<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kerangka Acuan Kerja (KAK).
     * Mengikuti struktur KAK perjalanan dinas BPS: bagian A s.d. H,
     * tabel rincian anggaran (kak_anggarans), dan blok penandatanganan
     * dua kolom (Ketua Tim Pelaksana & Pejabat Pembuat Komitmen).
     */
    public function up(): void
    {
        Schema::create('kaks', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('tahun')->nullable();
            $table->string('unit_kerja')->nullable();
            $table->foreignId('pegawai_id')->nullable()->constrained('pegawais')->nullOnDelete();

            $table->longText('dasar_hukum')->nullable();
            $table->longText('gambaran_umum')->nullable();
            $table->longText('maksud_tujuan')->nullable();
            $table->longText('keluaran')->nullable();
            $table->longText('pelaksana_kegiatan')->nullable();
            $table->longText('jadwal_lokasi')->nullable();
            $table->longText('sumber_pembiayaan')->nullable();
            $table->longText('penutup')->nullable();

            $table->string('tempat_penandatanganan')->nullable();
            $table->date('tanggal_penandatanganan')->nullable();

            // Kolom kiri (Ketua Tim Pelaksana).
            $table->string('ttd_kiri_jabatan')->nullable();
            $table->string('ttd_kiri_nama')->nullable();
            $table->string('ttd_kiri_nip')->nullable();

            // Kolom kanan (Pejabat Pembuat Komitmen).
            $table->string('ttd_kanan_jabatan')->nullable();
            $table->string('ttd_kanan_nama')->nullable();
            $table->string('ttd_kanan_nip')->nullable();

            $table->timestamps();
        });

        Schema::create('kak_anggarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kak_id')->constrained('kaks')->cascadeOnDelete();
            $table->string('kode_mak')->nullable();
            $table->string('deskripsi')->nullable();
            $table->decimal('volume', 12, 2)->default(0);
            $table->string('satuan')->nullable();
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->decimal('jumlah_biaya', 15, 2)->default(0);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kak_anggarans');
        Schema::dropIfExists('kaks');
    }
};
