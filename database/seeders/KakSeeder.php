<?php

namespace Database\Seeders;

use App\Models\Kak;
use App\Models\User;
use App\Support\KakExample;
use Illuminate\Database\Seeder;

class KakSeeder extends Seeder
{
    /**
     * Menyediakan contoh KAK (Perjalanan Rakornas SE2026) di daftar /kak.
     * Idempotent: tidak membuat duplikat bila contoh sudah ada.
     */
    public function run(): void
    {
        $fields = KakExample::fields();

        $kak = Kak::firstOrCreate(
            ['judul' => $fields['judul'], 'tahun' => $fields['tahun']],
            $fields
        );

        // Pemilik (bukan mass-assignable) di-set ke admin bila belum ada.
        if (! $kak->user_id) {
            $adminId = User::where('email', 'admin@bps.go.id')->value('id')
                ?? User::orderBy('id')->value('id');
            if ($adminId) {
                $kak->user_id = $adminId;
                $kak->save();
            }
        }

        if ($kak->anggarans()->count() === 0) {
            foreach (KakExample::anggarans() as $i => $baris) {
                $kak->anggarans()->create($baris + ['urutan' => $i + 1]);
            }
        }
    }
}
