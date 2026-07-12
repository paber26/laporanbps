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
     * Uraian dipecah menjadi potongan sepanjang ~2-4 kalimat (bukan seluruh
     * paragraf) untuk dicetak sebagai baris tabel tersendiri di PDF. dompdf
     * memindahkan satu <tr> secara utuh ke halaman berikutnya bila tidak
     * muat — dengan paragraf penuh sebagai satu baris, ini bisa menyisakan
     * banyak ruang kosong di akhir halaman. Memecah per beberapa kalimat
     * (bukan per kata) menjaga agar tiap baris tabel tetap berisi teks yang
     * mengalir wajar di dalam selnya (word-wrap normal, tanpa jarak baris
     * buatan), sambil membatasi maksimum ruang yang terbuang saat halaman
     * terpaksa berpindah di tengah paragraf panjang.
     *
     * @return array<int, array{text: string, new_paragraph: bool}>
     */
    public function getUraianChunksAttribute(): array
    {
        $text = trim((string) $this->uraian_text);
        if ($text === '') {
            return [];
        }

        // Ambil paragraf mentah (teks biasa maupun HTML dari editor WYSIWYG),
        // lalu buang semua tag agar hanya teks polos — spasi antar-baris jadi
        // konsisten terlepas dari sumbernya.
        if ($text !== strip_tags($text)) {
            if (preg_match_all('/<(p|div)\b[^>]*>(.*?)<\/\1>/is', $text, $m)) {
                $paragraphs = $m[2];
            } else {
                $paragraphs = preg_split('/(?:<br\s*\/?>\s*){2,}/i', $text) ?: [$text];
            }
        } else {
            $paragraphs = preg_split('/\n\s*\n/', $text) ?: [$text];
        }

        $maxLen = 320;
        $chunks = [];
        foreach ($paragraphs as $para) {
            $withBreaks = preg_replace('/<br\s*\/?>/i', "\n", $para);
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($withBreaks)));
            if ($plain === '') {
                continue;
            }

            // Pecah per kalimat (akhiri dengan . ! ? diikuti spasi/akhir teks),
            // lalu gabungkan kalimat berurutan sampai mendekati $maxLen karakter.
            preg_match_all('/[^.!?]+[.!?]+(?=\s|$)|[^.!?]+$/', $plain, $sm);
            $sentences = $sm[0] ?: [$plain];

            $buffer = '';
            $isFirst = true;
            foreach ($sentences as $sentence) {
                $sentence = trim($sentence);
                if ($sentence === '') {
                    continue;
                }
                $candidate = $buffer === '' ? $sentence : $buffer.' '.$sentence;
                if ($buffer !== '' && mb_strlen($candidate) > $maxLen) {
                    $chunks[] = ['text' => e($buffer), 'new_paragraph' => $isFirst];
                    $isFirst = false;
                    $buffer = $sentence;
                } else {
                    $buffer = $candidate;
                }
            }
            if ($buffer !== '') {
                $chunks[] = ['text' => e($buffer), 'new_paragraph' => $isFirst];
            }
        }

        return $chunks;
    }
}
