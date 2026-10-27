<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah pemilik (pembuat) laporan untuk otorisasi. Data lama di-backfill:
     * pemilik = user yang terhubung ke pegawai laporan (pegawai.user_id), atau
     * jatuh ke admin bila pegawai tak punya akun.
     */
    public function up(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });

        $adminId = DB::table('users')->where('email', 'admin@bps.go.id')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        // Backfill DB-agnostik (MySQL produksi & SQLite test): tentukan pemilik
        // per laporan dari pegawai terkait, fallback ke admin.
        DB::table('laporans')->whereNull('user_id')->orderBy('id')->chunkById(500, function ($laporans) use ($adminId) {
            foreach ($laporans as $laporan) {
                $ownerId = DB::table('pegawais')->where('id', $laporan->pegawai_id)->value('user_id') ?: $adminId;

                if ($ownerId) {
                    DB::table('laporans')->where('id', $laporan->id)->update(['user_id' => $ownerId]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
