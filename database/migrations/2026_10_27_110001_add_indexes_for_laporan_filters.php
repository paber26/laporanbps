<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index untuk mempercepat filter & pengurutan daftar laporan:
     * - laporan_uraians.tanggal_kegiatan: dipakai subquery MIN/MAX saat sort
     *   "tanggal kegiatan" dan filter whereYear/whereMonth/whereHas.
     * - laporans.tanggal_laporan: dipakai filter tahun/bulan & fallback sort.
     */
    public function up(): void
    {
        Schema::table('laporan_uraians', function (Blueprint $table) {
            $table->index('tanggal_kegiatan');
        });

        Schema::table('laporans', function (Blueprint $table) {
            $table->index('tanggal_laporan');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_uraians', function (Blueprint $table) {
            $table->dropIndex(['tanggal_kegiatan']);
        });

        Schema::table('laporans', function (Blueprint $table) {
            $table->dropIndex(['tanggal_laporan']);
        });
    }
};
