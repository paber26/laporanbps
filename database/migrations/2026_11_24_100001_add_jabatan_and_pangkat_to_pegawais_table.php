<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah jabatan & pangkat/golongan pada master pegawai agar dapat dipakai
     * langsung pada Surat Tugas & SPD pegawai organik.
     */
    public function up(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->string('jabatan')->nullable()->after('nama');
            $table->string('pangkat_golongan')->nullable()->after('jabatan');
        });
    }

    public function down(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropColumn(['jabatan', 'pangkat_golongan']);
        });
    }
};
