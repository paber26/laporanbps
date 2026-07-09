<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data master anggaran/pembiayaan (referensi dropdown agar tidak
     * perlu diketik ulang di setiap laporan).
     */
    public function up(): void
    {
        Schema::create('master_pembiayaans', function (Blueprint $table) {
            $table->id();
            $table->string('program');
            $table->string('kegiatan');
            $table->string('ro')->comment('Rincian Output');
            $table->string('komponen');
            $table->string('akun');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_pembiayaans');
    }
};
