<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratTugas extends Model
{
    use HasFactory;

    protected $table = 'surat_tugas';

    /**
     * Kolom `user_id` tidak dimasukkan ke $fillable agar kepemilikan
     * selalu ditetapkan melalui relasi `$user->suratTugas()->create(...)`.
     */
    protected $fillable = [
        'pegawai_id',
        'pembiayaan_id',
        'nomor_st_urut',
        'kode_klasifikasi',
        'tahun',
        'nomor_st',
        'nomor_spd',
        'tempat_st',
        'tanggal_st',
        'menimbang',
        'mengingat',
        'tujuan_kegiatan',
        'waktu_pelaksanaan',
        'jabatan',
        'pangkat_golongan',
        'unit_kerja',
        'tingkat_biaya',
        'alat_angkutan',
        'tempat_berangkat',
        'tempat_tujuan',
        'kecamatan_asal',
        'jumlah_hari',
        'tanggal_berangkat',
        'tanggal_kembali',
        'pengikut',
        'keterangan_lain',
        'tembusan',
        'mata_anggaran',
        'program_kode',
        'program_nama',
        'kegiatan_kode',
        'kegiatan_nama',
        'komponen_kode',
        'komponen_nama',
        'instansi_anggaran',
        'kepala_jabatan',
        'kepala_nama',
        'kepala_nip',
        'ppk_jabatan',
        'ppk_sub_jabatan',
        'ppk_nama',
        'ppk_nip',
        'sertakan_ttd_kepala',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'jumlah_hari' => 'integer',
            'tanggal_st' => 'date',
            'tanggal_berangkat' => 'date',
            'tanggal_kembali' => 'date',
            'sertakan_ttd_kepala' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function pembiayaan(): BelongsTo
    {
        return $this->belongsTo(MasterPembiayaan::class, 'pembiayaan_id');
    }

    public function rutes(): HasMany
    {
        return $this->hasMany(SuratTugasRute::class)->orderBy('urutan');
    }

    /**
     * Format tanggal Indonesia "j F Y" (mis. "3 September 2026").
     */
    public static function formatTanggal(?CarbonInterface $date): string
    {
        return $date ? $date->copy()->locale('id')->translatedFormat('j F Y') : '';
    }

    /**
     * Pecah daftar dasar hukum (Mengingat) menjadi array baris non-kosong.
     *
     * @return list<string>
     */
    public function mengingatList(): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $this->mengingat) ?: [];

        return array_values(array_filter(array_map('trim', $lines), fn ($v) => $v !== ''));
    }

    /**
     * Pecah tembusan per baris (minimal 2 baris bernomor bila kosong seperti template).
     *
     * @return list<string>
     */
    public function tembusanList(): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $this->tembusan) ?: [];
        $clean = array_values(array_filter(array_map('trim', $lines), fn ($v) => $v !== ''));

        return $clean;
    }

    /**
     * Ringkasan lokasi tujuan (untuk tampilan tabel & prefill form Laporan).
     */
    public function lokasiRingkas(): string
    {
        $kecs = $this->rutes
            ->pluck('kecamatan_tujuan')
            ->filter()
            ->unique()
            ->values();

        if ($kecs->isNotEmpty()) {
            return 'Kecamatan '.$kecs->implode(', ');
        }

        return (string) ($this->tempat_tujuan ?: config('laporan.surat_tugas.kabupaten'));
    }

    /**
     * Format nama kecamatan dengan prefiks "Kecamatan " bila belum diawali kata Kecamatan/Kota/Kabupaten.
     */
    public static function formatKecamatan(?string $nama): string
    {
        $nama = trim((string) $nama);
        if ($nama === '') {
            return '';
        }

        if (preg_match('/^(kecamatan|kec\.|kota|kabupaten|kab\.)\s/i', $nama)) {
            return $nama;
        }

        return 'Kecamatan '.$nama;
    }

    /**
     * Ekstrak rincian kode/nama Program, Kegiatan, Komponen, & Mata Anggaran
     * dari satu baris MasterPembiayaan untuk auto-isi form Surat Tugas.
     *
     * @return array<string, string>
     */
    public static function parsePembiayaan(MasterPembiayaan $p): array
    {
        // Contoh program: "(054.01.GG) Penyediaan dan Pelayanan Informasi Statistik"
        $progKode = '';
        $progNama = trim((string) $p->program);
        if (preg_match('/^\(([^)]+)\)\s*(.+)$/', $progNama, $m)) {
            $rawKode = trim($m[1]);
            $parts = explode('.', $rawKode);
            $progKode = end($parts) ?: $rawKode;
            $progNama = trim($m[2]);
            if (! str_starts_with(mb_strtolower($progNama), 'program ')) {
                $progNama = 'Program '.$progNama;
            }
        }

        // Contoh kegiatan: "(2906) Penyediaan dan Pengembangan Statistik Kesejahteraan Rakyat"
        $kegKode = '';
        $kegNama = trim((string) $p->kegiatan);
        if (preg_match('/^\(([^)]+)\)\s*(.+)$/', $kegNama, $m)) {
            $kegKode = trim($m[1]);
            $kegNama = trim($m[2]);
        }

        // Contoh RO: "BMA.006 Publikasi/Laporan ..."
        $roKode = '';
        if (preg_match('/^([A-Z0-9]+)[.\s]+([A-Z0-9]+)/i', trim((string) $p->ro), $m)) {
            $roKode = strtoupper($m[1]).' '.$m[2];
        }

        // Contoh komponen: "(052) Pengumpulan Data"
        $kompKode = '';
        $kompNama = trim((string) $p->komponen);
        if (preg_match('/^\(([^)]+)\)\s*(.+)$/', $kompNama, $m)) {
            $kompKode = trim($m[1]);
            $kompNama = trim($m[2]);
        }

        // Contoh akun: "(524113) Belanja Perjalanan Dinas Dalam Kota"
        $akunKode = '';
        if (preg_match('/^\(([^)]+)\)/', trim((string) $p->akun), $m)) {
            $akunKode = trim($m[1]);
        }

        $mataParts = array_filter([$progKode, $kegKode, $roKode, $kompKode, $kompKode ? 'A' : '', $akunKode]);

        return [
            'program_kode' => $progKode,
            'program_nama' => $progNama,
            'kegiatan_kode' => $kegKode,
            'kegiatan_nama' => $kegNama,
            'komponen_kode' => $kompKode,
            'komponen_nama' => $kompNama,
            'mata_anggaran' => implode(' ', $mataParts),
        ];
    }
}
