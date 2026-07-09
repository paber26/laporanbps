<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laporan extends Model
{
    use HasFactory;

    protected $table = 'laporans';

    protected $fillable = [
        'pegawai_id',
        'pembiayaan_id',
        'judul_laporan',
        'perihal_laporan',
        'tujuan_surat',
        'tempat_laporan',
        'tanggal_laporan',
        'lokasi_tujuan',
    ];

    protected $casts = [
        'tanggal_laporan' => 'date',
    ];

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function pembiayaan(): BelongsTo
    {
        return $this->belongsTo(MasterPembiayaan::class, 'pembiayaan_id');
    }

    public function uraians(): HasMany
    {
        return $this->hasMany(LaporanUraian::class)->orderBy('urutan')->orderBy('tanggal_kegiatan');
    }

    public function dokumentasis(): HasMany
    {
        return $this->hasMany(LaporanDokumentasi::class)->orderBy('urutan');
    }
}
