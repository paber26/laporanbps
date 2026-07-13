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
     * Uraian dipecah menjadi potongan (maksimal ~beberapa kalimat) untuk
     * dicetak sebagai baris tabel tersendiri di PDF. dompdf memindahkan satu
     * <tr> secara utuh ke halaman berikutnya bila tidak muat — bila satu
     * paragraf panjang jadi satu baris, ini bisa menyisakan banyak ruang
     * kosong di akhir halaman, atau (untuk paragraf yang lebih tinggi dari satu
     * halaman) tidak muat sama sekali. Memecah pada batas kalimat membuat teks
     * mengalir mengisi & menyambung antar-halaman.
     *
     * Pemisahan memakai preg_split (BUKAN preg_match_all yang diam-diam membuang
     * teks yang tak cocok pola) dengan lookbehind/lookahead yang hanya memisah
     * pada . ! ? yang benar-benar mengakhiri kalimat (diikuti spasi lalu huruf
     * kapital). Dengan begitu angka desimal (08.00, Rp820.000), persen (17,07%),
     * maupun kode (HD-5.1) TIDAK ikut terpecah dan tidak ada teks yang hilang.
     *
     * @return array<int, array{text: string, new_paragraph: bool}>
     */
    public function getUraianChunksAttribute(): array
    {
        $text = trim((string) $this->uraian_text);
        if ($text === '') {
            return [];
        }

        // Ambil blok mentah: tiap <p>/<div>/<figure> untuk HTML (editor WYSIWYG)
        if ($text !== strip_tags($text)) {
            // Ini adalah HTML dari editor. Kita ubah gambar menjadi base64 dan tidak memecahnya.
            // Gunakan preg_replace_callback untuk mengganti src="..." menjadi base64 data URI.
            $html = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', function ($matches) {
                $url = $matches[1];
                $appUrl = config('app.url');
                $storageUrl = \Illuminate\Support\Facades\Storage::url(''); // misal /storage/
                
                // Jika URL mengarah ke storage kita
                if (str_starts_with($url, $appUrl . $storageUrl) || str_starts_with($url, $storageUrl)) {
                    $path = str_replace([$appUrl . $storageUrl, $storageUrl], '', $url);
                    $path = ltrim($path, '/');
                    $absPath = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
                    
                    if ($base64 = \App\Support\PdfImage::dataUri($absPath)) {
                        return str_replace($url, $base64, $matches[0]);
                    }
                }
                return $matches[0];
            }, $text);
            
            // Kita kumpulkan elemen block-level (p, div, figure, table, ul, ol, h[1-6])
            // agar setiap block menjadi 1 chunk, sehingga dompdf bisa memecah halaman antar-block.
            if (preg_match_all('/<(p|div|figure|table|ul|ol|h[1-6])\b[^>]*>.*?<\/\1>/is', $html, $m)) {
                $chunks = [];
                foreach ($m[0] as $i => $blockHtml) {
                    // Coba pecah paragraf panjang yang HANYA berisi teks (tanpa tag inline seperti <strong> dll)
                    if (preg_match('/^<(p|div)\b[^>]*>(.*)<\/\1>$/is', trim($blockHtml), $match)) {
                        $innerHtml = trim($match[2]);
                        if ($innerHtml !== '' && strip_tags($innerHtml) === $innerHtml) {
                            $sentences = preg_split('/(?<=[.!?])\s+(?=[A-Z"\'])/u', $innerHtml, -1, PREG_SPLIT_NO_EMPTY) ?: [$innerHtml];
                            $buffer = '';
                            $isFirstChunk = true;
                            $maxLen = 480;
                            
                            foreach ($sentences as $sentence) {
                                $candidate = $buffer === '' ? $sentence : $buffer.' '.$sentence;
                                if ($buffer !== '' && mb_strlen($candidate) > $maxLen) {
                                    $chunks[] = [
                                        'text' => '<div style="margin:0; text-align:justify;">' . e($buffer) . '</div>', 
                                        'new_paragraph' => $isFirstChunk
                                    ];
                                    $isFirstChunk = false;
                                    $buffer = $sentence;
                                } else {
                                    $buffer = $candidate;
                                }
                            }
                            if ($buffer !== '') {
                                $chunks[] = [
                                    'text' => '<div style="margin:0; text-align:justify;">' . e($buffer) . '</div>', 
                                    'new_paragraph' => $isFirstChunk
                                ];
                            }
                            continue;
                        }
                    }
                    
                    $chunks[] = ['text' => trim($blockHtml), 'new_paragraph' => true];
                }
                if (!empty($chunks)) {
                    return $chunks;
                }
            }
            
            // Fallback jika regex block tidak cocok
            return [['text' => $html, 'new_paragraph' => true]];
        }

        // Teks biasa (tidak mengandung tag HTML)
        $blocks = preg_split('/\n\s*\n/', $text) ?: [$text];

        // Tiap blok non-kosong = satu paragraf (sesuai input pengguna).
        $paragraphs = [];
        foreach ($blocks as $block) {
            $plain = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $block));
            if ($plain !== '') {
                $paragraphs[] = $plain;
            }
        }

        $maxLen = 480;
        $chunks = [];
        foreach ($paragraphs as $pi => $paragraph) {
            // Pisah aman pada batas kalimat; tidak ada teks yang hilang.
            $sentences = preg_split('/(?<=[.!?])\s+(?=[A-Z"\'])/u', $paragraph, -1, PREG_SPLIT_NO_EMPTY) ?: [$paragraph];

            // Gabungkan kalimat berurutan sampai mendekati $maxLen karakter.
            $buffer = '';
            $isFirstChunk = true;
            foreach ($sentences as $sentence) {
                $candidate = $buffer === '' ? $sentence : $buffer.' '.$sentence;
                if ($buffer !== '' && mb_strlen($candidate) > $maxLen) {
                    $chunks[] = ['text' => e($buffer), 'new_paragraph' => $pi > 0 && $isFirstChunk];
                    $isFirstChunk = false;
                    $buffer = $sentence;
                } else {
                    $buffer = $candidate;
                }
            }
            if ($buffer !== '') {
                $chunks[] = ['text' => e($buffer), 'new_paragraph' => $pi > 0 && $isFirstChunk];
            }
        }

        return $chunks;
    }
}
