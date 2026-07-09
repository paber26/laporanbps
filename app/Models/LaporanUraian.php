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
}
