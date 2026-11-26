<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gabungkan duplikat pada master_pembiayaans dengan memindahkan referensi
     * laporans & surat_tugas ke baris kanonis sebelum menghapus duplikat.
     */
    public function up(): void
    {
        if (! Schema::hasTable('master_pembiayaans')) {
            return;
        }

        DB::transaction(function () {
            $rows = DB::table('master_pembiayaans')->orderBy('id')->get();
            if ($rows->count() <= 1) {
                return;
            }

            $lapCounts = Schema::hasTable('laporans')
                ? DB::table('laporans')
                    ->whereNotNull('pembiayaan_id')
                    ->selectRaw('pembiayaan_id, COUNT(*) as total')
                    ->groupBy('pembiayaan_id')
                    ->pluck('total', 'pembiayaan_id')
                : collect();

            $stCounts = Schema::hasTable('surat_tugas')
                ? DB::table('surat_tugas')
                    ->whereNotNull('pembiayaan_id')
                    ->selectRaw('pembiayaan_id, COUNT(*) as total')
                    ->groupBy('pembiayaan_id')
                    ->pluck('total', 'pembiayaan_id')
                : collect();

            $groups = $rows->groupBy(function ($row) {
                return implode("\x1F", [
                    trim((string) $row->program),
                    trim((string) $row->kegiatan),
                    trim((string) $row->ro),
                    trim((string) $row->komponen),
                    trim((string) $row->akun),
                ]);
            });

            foreach ($groups as $group) {
                if ($group->count() <= 1) {
                    continue;
                }

                // Pertahankan baris dengan referensi terbanyak; bila seri, pilih ID terkecil (asli).
                $sorted = $group->sortBy(function ($row) use ($lapCounts, $stCounts) {
                    $refs = (int) ($lapCounts[$row->id] ?? 0) + (int) ($stCounts[$row->id] ?? 0);

                    return sprintf('%010d_%010d', 1000000000 - $refs, $row->id);
                })->values();

                $keep = $sorted->first();
                $dupIds = $sorted->slice(1)->pluck('id')->all();

                if (Schema::hasTable('laporans')) {
                    DB::table('laporans')
                        ->whereIn('pembiayaan_id', $dupIds)
                        ->update(['pembiayaan_id' => $keep->id]);
                }

                if (Schema::hasTable('surat_tugas')) {
                    DB::table('surat_tugas')
                        ->whereIn('pembiayaan_id', $dupIds)
                        ->update(['pembiayaan_id' => $keep->id]);
                }

                DB::table('master_pembiayaans')->whereIn('id', $dupIds)->delete();
            }
        });
    }

    public function down(): void
    {
        // Deduplikasi data tidak dapat dikembalikan.
    }
};
