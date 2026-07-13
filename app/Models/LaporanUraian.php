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

        // Ambil blok mentah: tiap <p>/<div> untuk HTML dari editor WYSIWYG,
        // atau tiap baris untuk teks biasa.
        if ($text !== strip_tags($text)) {
            if (preg_match_all('/<(p|div)\b[^>]*>(.*?)<\/\1>/is', $text, $m)) {
                $blocks = $m[2];
            } else {
                $blocks = preg_split('/<br\s*\/?>/i', $text) ?: [$text];
            }
        } else {
            $blocks = preg_split('/\n/', $text) ?: [$text];
        }

        // Kelompokkan blok jadi paragraf: blok kosong (mis. <p>&nbsp;</p> dari
        // baris kosong di editor, atau baris kosong di teks biasa) adalah
        // PEMISAH paragraf sungguhan, bukan teks untuk ditampilkan. Blok
        // berurutan TANPA pemisah kosong di antaranya digabung jadi satu
        // paragraf — jadi setiap <p>/Enter dari pengguna TIDAK otomatis
        // dianggap paragraf baru; hanya baris kosong (Enter dua kali) yang
        // menandai paragraf baru, sesuai yang benar-benar diketik pengguna.
        $groups = [[]];
        foreach ($blocks as $block) {
            $withBreaks = preg_replace('/<br\s*\/?>/i', "\n", $block);
            $plain = html_entity_decode(strip_tags($withBreaks), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $plain = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $plain));

            if ($plain === '') {
                if (! empty(end($groups))) {
                    $groups[] = [];
                }

                continue;
            }

            $groups[count($groups) - 1][] = $plain;
        }

        $maxLen = 320;
        $chunks = [];
        foreach ($groups as $group) {
            if (empty($group)) {
                continue;
            }
            $paragraphText = implode(' ', $group);

            // Pecah per kalimat (akhiri dengan . ! ? diikuti spasi/akhir teks),
            // lalu gabungkan kalimat berurutan sampai mendekati $maxLen karakter.
            preg_match_all('/[^.!?]+[.!?]+(?=\s|$)|[^.!?]+$/', $paragraphText, $sm);
            $sentences = $sm[0] ?: [$paragraphText];

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
