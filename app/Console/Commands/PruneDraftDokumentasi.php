<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneDraftDokumentasi extends Command
{
    protected $signature = 'laporan:prune-draft-dokumentasi {--hours=24 : Usia minimal berkas (jam) untuk dihapus}';

    protected $description = 'Hapus foto dokumentasi draft (dokumentasi/tmp) yang terlantar dan belum ikut disimpan ke laporan.';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $threshold = now()->subHours((int) $this->option('hours'))->getTimestamp();

        $deleted = 0;
        foreach ($disk->allFiles('dokumentasi/tmp') as $file) {
            if ($disk->lastModified($file) < $threshold) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $this->info("Berkas draft dihapus: {$deleted}");

        return self::SUCCESS;
    }
}
