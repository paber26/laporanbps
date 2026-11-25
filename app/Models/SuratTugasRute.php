<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratTugasRute extends Model
{
    use HasFactory;

    protected $table = 'surat_tugas_rutes';

    protected $fillable = [
        'surat_tugas_id',
        'urutan',
        'kecamatan_tujuan',
        'tanggal_tiba',
        'tanggal_berangkat',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'tanggal_tiba' => 'date',
            'tanggal_berangkat' => 'date',
        ];
    }

    public function suratTugas(): BelongsTo
    {
        return $this->belongsTo(SuratTugas::class);
    }
}
