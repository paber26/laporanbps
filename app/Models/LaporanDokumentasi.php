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

    protected static function booted(): void
    {
        static::deleting(function (LaporanDokumentasi $dok) {
            $disk = Storage::disk('public');
            if ($dok->image_path && $disk->exists($dok->image_path)) {
                $disk->delete($dok->image_path);
            }
            if ($disk->exists($dok->thumbnail_path)) {
                $disk->delete($dok->thumbnail_path);
            }
        });
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class);
    }

    /**
     * URL publik gambar asli (untuk preview web & modal ukuran asli).
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->image_path);
    }

    /**
     * Path thumbnail relatif di disk public.
     */
    public function getThumbnailPathAttribute(): string
    {
        return 'thumbnails/'.$this->id.'.jpg';
    }

    /**
     * URL thumbnail terkompresi (untuk grid galeri / preview cepat).
     */
    public function getThumbnailUrlAttribute(): string
    {
        try {
            $thumbRelative = $this->thumbnail_path;
            $disk = Storage::disk('public');

            // Sudah ada → sajikan berkas statis (paling cepat, langsung via nginx).
            if ($disk->exists($thumbRelative)) {
                return Storage::url($thumbRelative);
            }

            // Belum ada → JANGAN generate inline saat render halaman (24 foto dalam
            // satu request = load pertama lambat/berat). Arahkan ke route thumbnail
            // yang membuatnya per-gambar di request terpisah (lazy, paralel) lalu
            // di-cache. Grid galeri selalu memakai thumbnail kualitas rendah, tak
            // pernah gambar asli penuh.
            return route('dokumentasi.thumb', $this);
        } catch (\Throwable $e) {
            return $this->url;
        }
    }

    /**
     * Path absolut di filesystem (untuk embed di PDF/Word).
     */
    public function getAbsolutePathAttribute(): ?string
    {
        return Storage::disk('public')->path($this->image_path);
    }
}
