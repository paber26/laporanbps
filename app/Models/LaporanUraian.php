<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanUraian extends Model
{
    use HasFactory;

    protected $table = 'laporan_uraians';

    protected $fillable = [
        'laporan_id',
        'tanggal_kegiatan',
        'jam_mulai',
        'jam_selesai',
        'uraian_text',
        'urutan',
    ];

    protected $casts = [
        'tanggal_kegiatan' => 'date',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class);
    }

    /**
     * Kembalikan uraian sebagai HTML aman untuk dicetak.
     * Jika input hanya teks biasa (tanpa tag), pertahankan baris baru.
     */
    public function getUraianHtmlAttribute(): string
    {
        $text = (string) $this->uraian_text;

        if ($text !== strip_tags($text)) {
            // Sudah mengandung HTML (dari editor WYSIWYG).
            return $text;
        }

        return nl2br(e($text));
    }

    /**
     * Uraian dipecah menjadi sekumpulan potongan HTML kecil, satu potongan =
     * satu baris tabel di PDF.
     *
     * dompdf (mesin cetak) TIDAK memecah satu <tr> pada batas halaman selama
     * baris itu masih muat di halaman berikutnya — ia justru memindahkan
     * seluruh baris ke halaman baru, menyisakan ruang kosong besar di akhir
     * halaman sebelumnya (persis bug "PDF kacau" yang terlihat). Dengan
     * memecah uraian panjang menjadi banyak baris kecil (per-paragraf, dan
     * per-kalimat untuk paragraf teks-murni yang panjang), tiap baris mudah
     * muat sehingga teks mengalir mengisi halaman tanpa celah.
     *
     * Setiap elemen array adalah HTML siap-tempel untuk satu sel Uraian.
     *
     * @return array<int, string>
     */
    public function getUraianChunksAttribute(): array
    {
        $text = trim((string) $this->uraian_text);
        if ($text === '') {
            return [];
        }

        // ---- HTML dari editor WYSIWYG (CKEditor) ----
        if ($text !== strip_tags($text)) {
            // Ubah <img src="/storage/..."> menjadi data URI base64 agar tampil
            // di PDF (dompdf tidak memuat berkas remote di sini).
            $html = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', function ($matches) {
                $url = $matches[1];
                $appUrl = rtrim((string) config('app.url'), '/');
                $storageUrl = \Illuminate\Support\Facades\Storage::url(''); // mis. /storage/

                if (str_starts_with($url, $appUrl . $storageUrl) || str_starts_with($url, $storageUrl)) {
                    $path = ltrim(str_replace([$appUrl . $storageUrl, $storageUrl], '', $url), '/');
                    $absPath = \Illuminate\Support\Facades\Storage::disk('public')->path($path);

                    if ($base64 = \App\Support\PdfImage::dataUri($absPath)) {
                        return str_replace($url, $base64, $matches[0]);
                    }
                }

                return $matches[0];
            }, $text);

            // Pecah menjadi elemen block-level level-atas (tiap <p>, <figure>,
            // <ul>, <table>, dst.) agar tiap block bisa jatuh di halaman berbeda.
            $blocks = [];
            if (preg_match_all('/<(p|div|figure|table|ul|ol|h[1-6]|blockquote|pre)\b[^>]*>.*?<\/\1>/is', $html, $m)) {
                $blocks = $m[0];
            }
            if (empty($blocks)) {
                $blocks = [$html];
            }

            return $blocks;
        }

        // ---- Teks biasa (tanpa tag HTML) ----
        $blocks = preg_split('/\n\s*\n/', $text) ?: [$text];
        $chunks = [];
        foreach ($blocks as $block) {
            $plain = trim($block);
            if ($plain === '') {
                continue;
            }
            // Langsung masukkan satu paragraf utuh tanpa dipecah per kalimat
            $chunks[] = '<p>' . nl2br(e($plain)) . '</p>';
        }

        return $chunks;
    }
}
