<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kak extends Model
{
    use HasFactory;

    protected $table = 'kaks';

    protected $fillable = [
        'judul',
        'tahun',
        'unit_kerja',
        'pegawai_id',
        'dasar_hukum',
        'gambaran_umum',
        'maksud_tujuan',
        'keluaran',
        'pelaksana_kegiatan',
        'jadwal_lokasi',
        'sumber_pembiayaan',
        'penutup',
        'tempat_penandatanganan',
        'tanggal_penandatanganan',
        'ttd_kiri_jabatan',
        'ttd_kiri_nama',
        'ttd_kiri_nip',
        'ttd_kanan_jabatan',
        'ttd_kanan_nama',
        'ttd_kanan_nip',
        'docx_original_path',
        'docx_edited_path',
    ];

    protected $casts = [
        'tanggal_penandatanganan' => 'date',
    ];

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function anggarans(): HasMany
    {
        return $this->hasMany(KakAnggaran::class)->orderBy('urutan');
    }
}
