<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lampiran 2 - Dokumentasi/foto kegiatan (One to Many dari laporan).
     */
    public function up(): void
    {
        Schema::create('laporan_dokumentasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporans')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('keterangan')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_dokumentasis');
    }
};
