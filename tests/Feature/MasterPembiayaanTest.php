<?php

namespace Tests\Feature;

use App\Models\Laporan;
use App\Models\LaporanUraian;
use App\Models\MasterPembiayaan;
use App\Models\Pegawai;
use App\Models\SuratTugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterPembiayaanTest extends TestCase
{
    use RefreshDatabase;

    protected function pembiayaanData(array $overrides = []): array
    {
        return array_merge([
            'program' => '(054.01.GG) Penyediaan dan Pelayanan Informasi Statistik',
            'kegiatan' => '(2902) Penyediaan dan Pengembangan Statistik Distribusi',
            'ro' => '(BMA.006) Publikasi/Laporan Sensus Ekonomi',
            'komponen' => '(530) Pelaksanaan SE2026',
            'akun' => '(524113) Belanja Perjalanan Dinas Dalam Kota',
        ], $overrides);
    }

    public function test_duplicating_laporan_reuses_pembiayaan_without_creating_duplicate_master(): void
    {
        $user = User::factory()->create();
        $pegawai = Pegawai::create([
            'nip' => '20001111 202302 1 002',
            'nama' => 'Bernaldo Napitupulu, S.Tr.Stat',
            'unit_kerja' => 'BPS Kabupaten Minahasa Selatan',
        ]);
        $pembiayaan = MasterPembiayaan::create($this->pembiayaanData());

        $laporan = $user->laporans()->create([
            'pegawai_id' => $pegawai->id,
            'pembiayaan_id' => $pembiayaan->id,
            'judul_laporan' => 'Laporan Supervisi SE2026',
            'perihal_laporan' => 'Supervisi',
            'tujuan_surat' => 'Kepala BPS',
            'tempat_laporan' => 'Amurang Barat',
            'tanggal_laporan' => '2026-11-03',
            'lokasi_tujuan' => 'Sinonsayang',
        ]);

        LaporanUraian::create([
            'laporan_id' => $laporan->id,
            'tanggal_kegiatan' => '2026-11-03',
            'uraian_text' => 'Kegiatan supervisi',
            'urutan' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('laporan.duplicate', $laporan))
            ->assertRedirect();

        $this->assertSame(1, MasterPembiayaan::count());
        $this->assertSame(2, Laporan::count());
        $copy = Laporan::latest('id')->first();
        $this->assertSame($pembiayaan->id, $copy->pembiayaan_id);
    }

    public function test_master_pembiayaan_rejects_duplicate_combination_on_store_and_update(): void
    {
        $user = User::factory()->create();
        $first = MasterPembiayaan::create($this->pembiayaanData());
        $second = MasterPembiayaan::create($this->pembiayaanData([
            'komponen' => '(529) Penerapan Prelist SBR untuk SE2026',
        ]));

        // Store dengan kombinasi sama persis (termasuk spasi ekstra) ditolak.
        $this->actingAs($user)
            ->post(route('master-pembiayaan.store'), $this->pembiayaanData([
                'program' => '  '.$first->program.'  ',
            ]))
            ->assertSessionHasErrors('program');

        $this->assertSame(2, MasterPembiayaan::count());

        // Update ke kombinasi milik baris lain ditolak.
        $this->actingAs($user)
            ->put(route('master-pembiayaan.update', $second), $this->pembiayaanData())
            ->assertSessionHasErrors('program');
    }

    public function test_deduplicate_migration_merges_references_and_removes_duplicates(): void
    {
        $user = User::factory()->create();
        $pegawai = Pegawai::create([
            'nip' => '20001111 202302 1 002',
            'nama' => 'Bernaldo Napitupulu, S.Tr.Stat',
            'unit_kerja' => 'BPS Kabupaten Minahasa Selatan',
        ]);

        $keep = MasterPembiayaan::create($this->pembiayaanData());
        $dup1 = MasterPembiayaan::create($this->pembiayaanData());
        $dup2 = MasterPembiayaan::create($this->pembiayaanData([
            'program' => ' '.$keep->program.' ',
        ]));

        $lap1 = $user->laporans()->create([
            'pegawai_id' => $pegawai->id,
            'pembiayaan_id' => $keep->id,
            'judul_laporan' => 'Lap 1',
            'perihal_laporan' => 'Perihal 1',
            'tujuan_surat' => 'Kepala BPS',
            'tempat_laporan' => 'Amurang Barat',
            'tanggal_laporan' => '2026-11-03',
            'lokasi_tujuan' => 'Sinonsayang',
        ]);

        $lap2 = $user->laporans()->create([
            'pegawai_id' => $pegawai->id,
            'pembiayaan_id' => $dup1->id,
            'judul_laporan' => 'Lap 2',
            'perihal_laporan' => 'Perihal 2',
            'tujuan_surat' => 'Kepala BPS',
            'tempat_laporan' => 'Amurang Barat',
            'tanggal_laporan' => '2026-11-04',
            'lokasi_tujuan' => 'Modoinding',
        ]);

        $st = $user->suratTugas()->create([
            'pegawai_id' => $pegawai->id,
            'pembiayaan_id' => $dup2->id,
            'nomor_st_urut' => '100',
            'kode_klasifikasi' => 'KP.650',
            'tahun' => 2026,
            'nomor_st' => 'B-100/71050/KP.650/2026',
            'tempat_st' => 'Minahasa Selatan',
            'tanggal_st' => '2026-11-03',
            'menimbang' => 'Menimbang',
            'mengingat' => 'Mengingat',
            'tujuan_kegiatan' => 'Tujuan',
            'waktu_pelaksanaan' => '3 November 2026',
            'instansi_anggaran' => 'BPS Minahasa Selatan',
            'tanggal_berangkat' => '2026-11-03',
            'tanggal_kembali' => '2026-11-03',
            'kepala_jabatan' => 'Kepala BPS',
            'kepala_nama' => 'Irena',
            'kepala_nip' => '19810706 200312 2 005',
            'ppk_jabatan' => 'PPK',
            'ppk_nama' => 'Afwin',
            'ppk_nip' => '199609222019011002',
        ]);

        $migration = require database_path('migrations/2026_11_26_000001_deduplicate_master_pembiayaans.php');
        $migration->up();

        $this->assertSame(1, MasterPembiayaan::count());
        $this->assertSame($keep->id, $lap1->fresh()->pembiayaan_id);
        $this->assertSame($keep->id, $lap2->fresh()->pembiayaan_id);
        $this->assertSame($keep->id, $st->fresh()->pembiayaan_id);
    }
}
