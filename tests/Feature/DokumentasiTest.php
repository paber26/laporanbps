<?php

namespace Tests\Feature;

use App\Models\Laporan;
use App\Models\LaporanDokumentasi;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DokumentasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dokumentasi_page_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $pegawai = Pegawai::create([
            'nama' => 'Bernaldo Napitupulu',
            'nip' => '199501012020011001',
            'jabatan' => 'Statistisi Ahli Pertama',
            'pangkat_golongan' => 'Penata Muda / III/a',
            'unit_kerja' => 'BPS Minahasa Selatan',
        ]);

        $laporan = Laporan::create([
            'pegawai_id' => $pegawai->id,
            'pembiayaan_id' => null,
            'judul_laporan' => 'Laporan Supervisi',
            'perihal_laporan' => 'Sosialisasi SE2026',
            'tujuan_surat' => 'Kepala BPS',
            'tempat_laporan' => 'Amurang Barat',
            'tanggal_laporan' => '2026-06-29',
            'lokasi_tujuan' => 'Popontolen',
        ]);

        $dok = LaporanDokumentasi::create([
            'laporan_id' => $laporan->id,
            'image_path' => 'dokumentasi/sample.jpg',
            'keterangan' => 'Foto bersama peserta sosialisasi',
            'urutan' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('dokumentasi.index'));

        $response->assertOk();
        $response->assertSee('Galeri Foto Dokumentasi');
        $response->assertSee('Foto bersama peserta sosialisasi');
        $response->assertSee('Sosialisasi SE2026');
        $response->assertSee('Bernaldo Napitupulu');
        $response->assertSee('Popontolen');
    }

    public function test_dokumentasi_search_and_filter(): void
    {
        $user = User::factory()->create();

        $pegawaiA = Pegawai::create([
            'nama' => 'Petugas Satu',
            'nip' => '111111111111111111',
            'jabatan' => 'Statistisi',
            'pangkat_golongan' => 'III/a',
            'unit_kerja' => 'BPS',
        ]);

        $pegawaiB = Pegawai::create([
            'nama' => 'Petugas Dua',
            'nip' => '222222222222222222',
            'jabatan' => 'Statistisi',
            'pangkat_golongan' => 'III/a',
            'unit_kerja' => 'BPS',
        ]);

        $laporanA = Laporan::create([
            'pegawai_id' => $pegawaiA->id,
            'pembiayaan_id' => null,
            'judul_laporan' => 'Kegiatan Alpha',
            'perihal_laporan' => 'Perihal Alpha',
            'tujuan_surat' => 'Kepala BPS',
            'tempat_laporan' => 'Amurang',
            'tanggal_laporan' => '2026-05-10',
            'lokasi_tujuan' => 'Desa Alpha',
        ]);

        $laporanB = Laporan::create([
            'pegawai_id' => $pegawaiB->id,
            'pembiayaan_id' => null,
            'judul_laporan' => 'Kegiatan Beta',
            'perihal_laporan' => 'Perihal Beta',
            'tujuan_surat' => 'Kepala BPS',
            'tempat_laporan' => 'Amurang',
            'tanggal_laporan' => '2026-06-20',
            'lokasi_tujuan' => 'Desa Beta',
        ]);

        LaporanDokumentasi::create([
            'laporan_id' => $laporanA->id,
            'image_path' => 'dokumentasi/a.jpg',
            'keterangan' => 'Foto Unik Alpha',
            'urutan' => 1,
        ]);

        LaporanDokumentasi::create([
            'laporan_id' => $laporanB->id,
            'image_path' => 'dokumentasi/b.jpg',
            'keterangan' => 'Foto Unik Beta',
            'urutan' => 1,
        ]);

        // Filter search Alpha
        $responseAlpha = $this->actingAs($user)->get(route('dokumentasi.index', ['search' => 'Alpha']));
        $responseAlpha->assertOk();
        $responseAlpha->assertSee('Foto Unik Alpha');
        $responseAlpha->assertDontSee('Foto Unik Beta');

        // Filter pegawai B
        $responsePegawaiB = $this->actingAs($user)->get(route('dokumentasi.index', ['pegawai_id' => $pegawaiB->id]));
        $responsePegawaiB->assertOk();
        $responsePegawaiB->assertSee('Foto Unik Beta');
        $responsePegawaiB->assertDontSee('Foto Unik Alpha');
    }
}
