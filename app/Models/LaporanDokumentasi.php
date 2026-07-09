<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LaporanDokumentasi extends Model
{
    use HasFactory;

    protected $table = 'laporan_dokumentasis';

    protected $fillable = [
        'laporan_id',
        'image_path',
        'keterangan',
        'urutan',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class);
    }

    /**
     * URL publik gambar (untuk preview web).
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->image_path);
    }

    /**
     * Path absolut di filesystem (untuk embed di PDF/Word).
     */
    public function getAbsolutePathAttribute(): ?string
    {
        return Storage::disk('public')->path($this->image_path);
    }
}
