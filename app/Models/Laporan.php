<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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

    /**
     * Dapatkan daftar tanggal kegiatan unik dari uraian, diurutkan.
     *
     * @return \Illuminate\Support\Collection<int, \Carbon\CarbonInterface>
     */
    public function getTanggalKegiatanListAttribute()
    {
        return $this->uraians
            ->pluck('tanggal_kegiatan')
            ->filter()
            ->map(function ($d) {
                $c = $d instanceof \Carbon\CarbonInterface ? $d->copy() : Carbon::parse($d);
                return $c->locale('id');
            })
            ->unique(fn ($d) => $d->format('Y-m-d'))
            ->sort(fn ($a, $b) => $a->timestamp <=> $b->timestamp)
            ->values();
    }

    /**
     * Format ringkas tanggal kegiatan (atau tanggal laporan jika uraian belum memiliki tanggal).
     */
    public function getTanggalKegiatanFormattedAttribute(): string
    {
        $dates = $this->tanggal_kegiatan_list;

        if ($dates->isEmpty()) {
            if ($this->tanggal_laporan) {
                $tgl = $this->tanggal_laporan instanceof \Carbon\CarbonInterface
                    ? $this->tanggal_laporan->copy()
                    : Carbon::parse($this->tanggal_laporan);
                return $tgl->format('d-m-Y');
            }
            return '-';
        }

        if ($dates->count() === 1) {
            return $dates[0]->format('d-m-Y');
        }

        if ($dates->count() === 2) {
            return $dates[0]->format('d-m-Y') . ' & ' . $dates[1]->format('d-m-Y');
        }

        return $dates->first()->format('d-m-Y') . ' s.d. ' . $dates->last()->format('d-m-Y');
    }
}
