<?php

namespace App\Console\Commands;

use App\Models\LaporanDokumentasi;
use App\Support\ImageCompressor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateDokumentasiThumbnails extends Command
{
    protected $signature = 'dokumentasi:generate-thumbs {--force : Timpa thumbnail yang sudah ada}';

    protected $description = 'Kompresi dan buat berkas thumbnail untuk seluruh foto dokumentasi di sistem.';

    public function handle(): int
    {
        $docs = LaporanDokumentasi::all();
        $disk = Storage::disk('public');

        if ($docs->isEmpty()) {
            $this->info('Tidak ada dokumentasi foto di database.');
            return self::SUCCESS;
        }

        $this->info("Memproses {$docs->count()} foto dokumentasi...");
        $bar = $this->output->createProgressBar($docs->count());
        $bar->start();

        $generated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($docs as $doc) {
            $thumbRelative = $doc->thumbnail_path;

            if (! $this->option('force') && $disk->exists($thumbRelative)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if (! $doc->image_path || ! $disk->exists($doc->image_path)) {
                $failed++;
                $bar->advance();
                continue;
            }

            if (ImageCompressor::createThumbnail($doc->image_path, $thumbRelative)) {
                $generated++;
            } else {
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Selesai! Thumbnail dibuat: {$generated}, Dilewati: {$skipped}, Gagal: {$failed}.");

        return self::SUCCESS;
    }
}
