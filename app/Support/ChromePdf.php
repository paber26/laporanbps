<?php

namespace App\Support;

use RuntimeException;

/**
 * Render HTML menjadi PDF memakai Chromium/Chrome headless (`--print-to-pdf`).
 *
 * Dipakai menggantikan dompdf agar page-break persis seperti di browser:
 * paragraf mengalir inline DAN terpecah mulus antar-halaman tanpa whitespace.
 *
 * Ukuran kertas & margin diatur lewat CSS `@page` di dalam HTML (bukan argumen
 * CLI), jadi template cukup menyetel `@page { size: ...; margin: ...; }`.
 */
class ChromePdf
{
    /**
     * @return string  Byte mentah PDF.
     */
    public static function fromHtml(string $html): string
    {
        $bin = self::binary();

        // Pakai direktori temp sistem (/tmp, world-writable & sticky) agar bisa
        // ditulis user mana pun — php-fpm (www-data) maupun CLI (root) — tanpa
        // masalah kepemilikan direktori.
        $dir = sys_get_temp_dir();
        $id = uniqid('lapbps_pdf_', true);
        $htmlFile = $dir.'/'.$id.'.html';
        $pdfFile = $dir.'/'.$id.'.pdf';
        $profile = $dir.'/'.$id.'_profile';

        file_put_contents($htmlFile, $html);

        $args = [
            '--headless=new',
            '--no-sandbox',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--disable-extensions',
            '--no-first-run',
            '--no-pdf-header-footer',            // tanpa header/footer bawaan (url, tanggal)
            '--run-all-compositor-stages-before-draw',
            '--virtual-time-budget=15000',       // beri waktu layout/gambar selesai
            '--user-data-dir='.$profile,
            '--print-to-pdf='.$pdfFile,
            'file://'.$htmlFile,
        ];

        $cmd = escapeshellarg($bin);
        foreach ($args as $a) {
            $cmd .= ' '.escapeshellarg($a);
        }
        $cmd .= ' 2>&1';

        $output = [];
        $code = 0;
        @exec($cmd, $output, $code);

        $pdf = is_file($pdfFile) ? file_get_contents($pdfFile) : false;

        // Bersihkan berkas sementara.
        @unlink($htmlFile);
        @unlink($pdfFile);
        self::removeDir($profile);

        if ($pdf === false || $pdf === '') {
            throw new RuntimeException(
                'Gagal membuat PDF via Chrome (exit '.$code.'): '.trim(implode("\n", $output))
            );
        }

        return $pdf;
    }

    /**
     * Temukan binary Chrome/Chromium. Bisa dipaksa lewat env CHROME_BIN.
     */
    public static function binary(): string
    {
        $candidates = array_filter([
            env('CHROME_BIN'),
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/Applications/Chromium.app/Contents/MacOS/Chromium',
        ]);

        foreach ($candidates as $bin) {
            if ($bin && is_file($bin)) {
                return $bin;
            }
        }

        throw new RuntimeException('Chrome/Chromium tidak ditemukan. Set env CHROME_BIN ke path binary.');
    }

    private static function removeDir(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }
        $items = @scandir($path) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path.'/'.$item;
            is_dir($full) ? self::removeDir($full) : @unlink($full);
        }
        @rmdir($path);
    }
}
