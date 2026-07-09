<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterPembiayaan extends Model
{
    use HasFactory;

    protected $table = 'master_pembiayaans';

    protected $fillable = [
        'program',
        'kegiatan',
        'ro',
        'komponen',
        'akun',
    ];

    public function laporans(): HasMany
    {
        return $this->hasMany(Laporan::class, 'pembiayaan_id');
    }

    /**
     * Label ringkas untuk dropdown.
     */
    public function getLabelAttribute(): string
    {
        return $this->program.' — '.$this->kegiatan;
    }
}
