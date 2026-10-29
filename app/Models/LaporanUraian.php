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
     * dompdf (mesin cetak) TIDAK memecah satu <tr> pada batas halaman — ia
     * memindahkan seluruh baris ke halaman baru bila tak muat, menyisakan ruang
     * kosong besar di akhir halaman sebelumnya. Maka uraian dipecah menjadi
     * potongan kecil PER-KALIMAT sehingga tiap baris mudah muat & teks mengalir
     * mengisi halaman penuh (termasuk melintasi batas halaman). Garis horizontal
     * antar-potongan disembunyikan (kelas cont/para/open di template) sehingga
     * potongan dalam satu hari tampil menyatu sebagai satu blok.
     *
     * Setiap elemen: ['html' => <HTML sel>, 'new_para' => <bool awal paragraf>].
     * 'new_para' dipakai template untuk memberi jeda antar-paragraf (bukan
     * antar-kalimat).
     *
     * @return array<int, array{html: string, new_para: bool}>
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
            // <ul>, <table>, dst.).
            $blocks = [];
            if (preg_match_all('/<(p|div|figure|table|ul|ol|h[1-6]|blockquote|pre)\b[^>]*>.*?<\/\1>/is', $html, $m)) {
                $blocks = $m[0];
            }
            if (empty($blocks)) {
                $blocks = [$html];
            }

            $chunks = [];
            foreach ($blocks as $block) {
                foreach ($this->splitBlockIntoSentences($block) as $c) {
                    $chunks[] = $c;
                }
            }

            return $chunks;
        }

        // ---- Teks biasa (tanpa tag HTML) ----
        $blocks = preg_split('/\n\s*\n/', $text) ?: [$text];
        $chunks = [];
        foreach ($blocks as $block) {
            $plain = trim($block);
            if ($plain === '') {
                continue;
            }
            $sentences = $this->splitTextIntoSentences($plain);
            foreach ($sentences as $i => $sentence) {
                $chunks[] = [
                    'html' => '<p>' . nl2br(e($sentence)) . '</p>',
                    'new_para' => $i === 0,
                ];
            }
        }

        return $chunks;
    }

    /**
     * Pecah teks menjadi kalimat pada batas .!? yang diikuti spasi lalu non-spasi.
     *
     * @return array<int, string>
     */
    private function splitTextIntoSentences(string $text): array
    {
        $parts = preg_split('/(?<=[.!?])\s+(?=\S)/u', trim($text)) ?: [$text];

        return array_values(array_filter(array_map('trim', $parts), fn ($s) => $s !== ''));
    }

    /**
     * Pecah satu block HTML menjadi potongan per-kalimat (HTML-safe). Hanya
     * paragraf <p>...</p> yang dipecah; block lain (figure/table/ul/ol/...) utuh.
     * Tag inline (mis. <em>, <strong>) yang terbuka di batas kalimat ditutup di
     * akhir potongan & dibuka kembali di potongan berikutnya agar HTML valid.
     *
     * @return array<int, array{html: string, new_para: bool}>
     */
    private function splitBlockIntoSentences(string $block): array
    {
        $trimmed = trim($block);

        // Hanya paragraf teks yang dipecah per-kalimat.
        if (! preg_match('/^<p\b([^>]*)>(.*)<\/p>$/is', $trimmed, $m)) {
            return [['html' => $trimmed, 'new_para' => true]];
        }

        $attr = $m[1];
        $inner = $m[2];

        // Paragraf pendek dibiarkan UTUH (mengalir inline normal — tampilan tak
        // berubah). Hanya paragraf panjang yang dipecah per-kalimat agar bisa
        // mengalir antar-halaman tanpa menyisakan whitespace besar. Gambar/konten
        // non-teks (mis. <img> inline) juga jangan dipecah.
        if (mb_strlen(trim(strip_tags($inner))) <= 300 || str_contains($inner, '<img')) {
            return [['html' => $trimmed, 'new_para' => true]];
        }

        // Tokenisasi: setiap token berupa tag <...> atau teks.
        preg_match_all('/<[^>]+>|[^<]+/su', $inner, $tk);
        $tokens = $tk[0];

        $sentences = [];
        $open = [];   // stack tag inline terbuka: ['name' => ..., 'tag' => '<...>']
        $buf = '';

        $closeOpen = function () use (&$open) {
            $s = '';
            for ($i = count($open) - 1; $i >= 0; $i--) {
                $s .= '</' . $open[$i]['name'] . '>';
            }
            return $s;
        };
        $reopen = function () use (&$open) {
            $s = '';
            foreach ($open as $o) {
                $s .= $o['tag'];
            }
            return $s;
        };

        $flush = function () use (&$sentences, &$buf, $attr, $closeOpen, $reopen) {
            if (trim(strip_tags($buf)) === '') {
                return; // jangan buat potongan tanpa teks
            }
            $sentences[] = '<p' . $attr . '>' . $buf . $closeOpen() . '</p>';
            $buf = $reopen();
        };

        foreach ($tokens as $t) {
            if ($t !== '' && $t[0] === '<') {
                if (preg_match('/^<\s*([a-zA-Z0-9]+)[^>]*?(\/?)>$/s', $t, $tm)) {
                    $name = strtolower($tm[1]);
                    $selfClose = $tm[2] === '/' || in_array($name, ['br', 'img', 'hr', 'wbr'], true);
                    $buf .= $t;
                    if (! $selfClose) {
                        $open[] = ['name' => $name, 'tag' => $t];
                    }
                } elseif (preg_match('/^<\s*\/\s*([a-zA-Z0-9]+)\s*>$/s', $t, $tm)) {
                    $name = strtolower($tm[1]);
                    for ($i = count($open) - 1; $i >= 0; $i--) {
                        if ($open[$i]['name'] === $name) {
                            array_splice($open, $i, 1);
                            break;
                        }
                    }
                    $buf .= $t;
                } else {
                    $buf .= $t;
                }
            } else {
                $parts = preg_split('/(?<=[.!?])\s+(?=\S)/u', $t);
                $last = count($parts) - 1;
                foreach ($parts as $i => $p) {
                    $buf .= $p;
                    if ($i < $last) {
                        $buf .= ' ';
                        $flush();
                    }
                }
            }
        }

        if (trim(strip_tags($buf)) !== '') {
            $sentences[] = '<p' . $attr . '>' . $buf . $closeOpen() . '</p>';
        }

        if (empty($sentences)) {
            return [['html' => $trimmed, 'new_para' => true]];
        }

        $out = [];
        foreach ($sentences as $i => $html) {
            $out[] = ['html' => $html, 'new_para' => $i === 0];
        }

        return $out;
    }
}
