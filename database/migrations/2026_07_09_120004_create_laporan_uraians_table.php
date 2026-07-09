<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lampiran 1 - Uraian Kegiatan (One to Many dari laporan).
     */
    public function up(): void
    {
        Schema::create('laporan_uraians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporans')->cascadeOnDelete();
            $table->date('tanggal_kegiatan');
            $table->string('jam_mulai')->nullable();
            $table->string('jam_selesai')->nullable();
            $table->longText('uraian_text');
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_uraians');
    }
};
