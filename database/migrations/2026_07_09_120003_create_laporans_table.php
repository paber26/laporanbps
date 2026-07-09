<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel utama laporan (header & surat pengantar).
     */
    public function up(): void
    {
        Schema::create('laporans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawais')->cascadeOnDelete();
            $table->foreignId('pembiayaan_id')->nullable()->constrained('master_pembiayaans')->nullOnDelete();
            $table->string('judul_laporan')->comment('Judul besar di kop, mis: Laporan Perjalanan Dinas Supervisi ...');
            $table->string('perihal_laporan');
            $table->string('tujuan_surat')->comment('Kepada Yth, mis: Kepala BPS Kabupaten Minahasa Selatan');
            $table->string('tempat_laporan')->comment('Kota/tempat penandatanganan, mis: Amurang Barat');
            $table->date('tanggal_laporan');
            $table->string('lokasi_tujuan')->comment('Lokasi tujuan kegiatan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporans');
    }
};
