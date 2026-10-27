<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah pemilik (pembuat) KAK untuk otorisasi. Data lama di-backfill:
     * pemilik = user yang terhubung ke pegawai KAK (pegawai.user_id), atau
     * jatuh ke admin bila pegawai kosong/tak punya akun.
     */
    public function up(): void
    {
        Schema::table('kaks', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });

        $adminId = DB::table('users')->where('email', 'admin@bps.go.id')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        DB::table('kaks')->whereNull('user_id')->orderBy('id')->chunkById(500, function ($kaks) use ($adminId) {
            foreach ($kaks as $kak) {
                $ownerId = $kak->pegawai_id
                    ? (DB::table('pegawais')->where('id', $kak->pegawai_id)->value('user_id') ?: $adminId)
                    : $adminId;

                if ($ownerId) {
                    DB::table('kaks')->where('id', $kak->id)->update(['user_id' => $ownerId]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('kaks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
