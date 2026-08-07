<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KakAnggaran extends Model
{
    use HasFactory;

    protected $table = 'kak_anggarans';

    protected $fillable = [
        'kak_id',
        'kode_mak',
        'deskripsi',
        'volume',
        'satuan',
        'harga_satuan',
        'jumlah_biaya',
        'urutan',
    ];

    protected $casts = [
        'volume' => 'float',
        'harga_satuan' => 'float',
        'jumlah_biaya' => 'float',
    ];

    public function kak(): BelongsTo
    {
        return $this->belongsTo(Kak::class);
    }

    /**
     * Format angka ribuan ala Indonesia (contoh: 7.000.000).
     */
    public function getHargaSatuanFormattedAttribute(): string
    {
        return number_format($this->harga_satuan, 0, ',', '.');
    }

    public function getJumlahBiayaFormattedAttribute(): string
    {
        return number_format($this->jumlah_biaya, 0, ',', '.');
    }
}
