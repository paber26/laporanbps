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
     * Uraian dipecah menjadi array paragraf HTML. Dipakai di PDF agar tiap
     * paragraf menjadi baris tabel tersendiri sehingga teks yang panjang bisa
     * mengalir mengisi halaman (dompdf tidak memindahkan satu baris raksasa ke
     * halaman berikutnya dan menyisakan halaman kosong).
     *
     * @return array<int, string>
     */
    public function getUraianParagraphsAttribute(): array
    {
        $text = trim((string) $this->uraian_text);
        if ($text === '') {
            return [];
        }

        // Mengandung HTML (editor WYSIWYG): pisah per blok <p>/<div>,
        // jika tidak ada, pisah per baris kosong (<br><br>).
        if ($text !== strip_tags($text)) {
            if (preg_match_all('/<(p|div)\b[^>]*>(.*?)<\/\1>/is', $text, $m)) {
                $paras = array_map('trim', $m[2]);
            } else {
                $paras = preg_split('/(?:<br\s*\/?>\s*){2,}/i', $text) ?: [$text];
                $paras = array_map('trim', $paras);
            }

            $paras = array_filter(
                $paras,
                fn ($p) => trim(strip_tags($p)) !== '' || str_contains($p, '<img')
            );

            return $paras ? array_values($paras) : [$text];
        }

        // Teks biasa: pisah per baris kosong (paragraf), pertahankan baris tunggal.
        $parts = preg_split('/\n\s*\n/', $text) ?: [$text];
        $parts = array_map(fn ($p) => nl2br(e(trim($p))), $parts);
        $parts = array_filter($parts, fn ($p) => $p !== '');

        return $parts ? array_values($parts) : [nl2br(e($text))];
    }
}
