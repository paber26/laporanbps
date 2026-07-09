<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data pegawai/petugas yang membuat laporan.
     * Terhubung ke users (opsional) untuk mengakomodasi pendaftaran mandiri
     * pengguna dari luar kantor BPS asal.
     */
    public function up(): void
    {
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nip')->unique();
            $table->string('nama');
            $table->string('unit_kerja')->comment('Asal / Nama Kantor BPS');
            $table->string('tanda_tangan_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};
