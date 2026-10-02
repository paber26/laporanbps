<?php

namespace Tests\Feature;

use App\Models\Laporan;
use App\Models\LaporanUraian;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_laporan_index_renders_lokasi_and_tanggal_kegiatan_columns(): void
    {
        $user = User::factory()->create();

        $pegawai = Pegawai::create([
            'nama' => 'Budi Santoso',
            'nip' => '198701012010011001',
            'jabatan' => 'Pranata Komputer',
            'pangkat_golongan' => 'Penata / III/c',
            'unit_kerja' => 'BPS Kabupaten Minahasa Selatan',
        ]);

        $laporan = Laporan::create([
            'pegawai_id' => $pegawai->id,
            'pembiayaan_id' => null,
            'judul_laporan' => 'Laporan Supervisi',
            'perihal_laporan' => 'Supervisi Sensus Ekonomi 2026',
            'tujuan_surat' => 'Kepala BPS',
            'tempat_laporan' => 'Amurang Barat',
            'tanggal_laporan' => '2026-06-29',
            'lokasi_tujuan' => 'Popontolen',
        ]);

        LaporanUraian::create([
            'laporan_id' => $laporan->id,
            'tanggal_kegiatan' => '2026-06-28',
            'jam_mulai' => '08:00',
            'jam_selesai' => '16:00',
            'uraian_text' => 'Kegiatan hari pertama',
            'urutan' => 1,
        ]);

        LaporanUraian::create([
            'laporan_id' => $laporan->id,
            'tanggal_kegiatan' => '2026-06-29',
            'jam_mulai' => '08:00',
            'jam_selesai' => '16:00',
            'uraian_text' => 'Kegiatan hari kedua',
            'urutan' => 2,
        ]);

        $this->assertCount(2, $laporan->tanggal_kegiatan_list);

        $response = $this->actingAs($user)->get(route('laporan.index'));

        $response->assertOk();
        $response->assertSee('Lokasi Tujuan Kegiatan');
        $response->assertSee('Tanggal Kegiatan');
        $response->assertSee('Popontolen');
        $response->assertSee('28 Juni 2026');
        $response->assertSee('29 Juni 2026');
    }

    public function test_laporan_index_handles_single_date_and_no_uraian(): void
    {
        $user = User::factory()->create();

        $pegawai = Pegawai::create([
            'nama' => 'Siti Rahma',
            'nip' => '199001012015012001',
            'jabatan' => 'Statistisi Ahli Pertama',
            'pangkat_golongan' => 'Penata Muda / III/a',
            'unit_kerja' => 'BPS Kabupaten Minahasa Selatan',
        ]);

        $laporanEmpty = Laporan::create([
            'pegawai_id' => $pegawai->id,
            'pembiayaan_id' => null,
            'judul_laporan' => 'Laporan Kosong',
            'perihal_laporan' => 'Perihal Kosong',
            'tujuan_surat' => 'Kepala BPS',
            'tempat_laporan' => 'Amurang Barat',
            'tanggal_laporan' => '2026-07-01',
            'lokasi_tujuan' => 'Tumpaan',
        ]);

        $laporanSingle = Laporan::create([
            'pegawai_id' => $pegawai->id,
            'pembiayaan_id' => null,
            'judul_laporan' => 'Laporan Single',
            'perihal_laporan' => 'Perihal Single',
            'tujuan_surat' => 'Kepala BPS',
            'tempat_laporan' => 'Amurang Barat',
            'tanggal_laporan' => '2026-07-02',
            'lokasi_tujuan' => 'Motoling',
        ]);

        LaporanUraian::create([
            'laporan_id' => $laporanSingle->id,
            'tanggal_kegiatan' => '2026-07-02',
            'jam_mulai' => '09:00',
            'jam_selesai' => '17:00',
            'uraian_text' => 'Kegiatan satu hari',
            'urutan' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('laporan.index'));
        $response->assertOk();
        $response->assertSee('Tumpaan');
        $response->assertSee('Motoling');
        $response->assertSee('2 Juli 2026');
    }

    public function test_laporan_index_filters_by_search_and_pegawai(): void
    {
        $user = User::factory()->create();

        $pegawaiA = Pegawai::create([
            'nama' => 'Petugas Pertama',
            'nip' => '123456789012345671',
            'jabatan' => 'Statistisi',
            'pangkat_golongan' => 'III/a',
            'unit_kerja' => 'BPS',
        ]);

        $pegawaiB = Pegawai::create([
            'nama' => 'Petugas Kedua',
            'nip' => '123456789012345672',
            'jabatan' => 'Statistisi',
            'pangkat_golongan' => 'III/a',
            'unit_kerja' => 'BPS',
        ]);

        Laporan::create([
            'pegawai_id' => $pegawaiA->id,
            'pembiayaan_id' => null,
            'judul_laporan' => 'Judul Alpha',
            'perihal_laporan' => 'Kegiatan Sensus Khusus',
            'tujuan_surat' => 'Kepala BPS',
            'tempat_laporan' => 'Amurang',
            'tanggal_laporan' => '2026-06-10',
            'lokasi_tujuan' => 'Desa Popontolen',
        ]);

        Laporan::create([
            'pegawai_id' => $pegawaiB->id,
            'pembiayaan_id' => null,
            'judul_laporan' => 'Judul Beta',
            'perihal_laporan' => 'Pencacahan Survei Rutin',
            'tujuan_surat' => 'Kepala BPS',
            'tempat_laporan' => 'Amurang',
            'tanggal_laporan' => '2026-07-20',
            'lokasi_tujuan' => 'Desa Modoinding',
        ]);

        // Filter search "Khusus"
        $resSearch = $this->actingAs($user)->get(route('laporan.index', ['search' => 'Khusus']));
        $resSearch->assertOk();
        $resSearch->assertSee('Kegiatan Sensus Khusus');
        $resSearch->assertDontSee('Pencacahan Survei Rutin');

        // Filter pegawai B
        $resPegawai = $this->actingAs($user)->get(route('laporan.index', ['pegawai_id' => $pegawaiB->id]));
        $resPegawai->assertOk();
        $resPegawai->assertSee('Pencacahan Survei Rutin');
        $resPegawai->assertDontSee('Kegiatan Sensus Khusus');
    }

    public function test_laporan_index_supports_per_page_selection(): void
    {
        $user = User::factory()->create();

        $pegawai = Pegawai::create([
            'nama' => 'Petugas Batch',
            'nip' => '123456789012345679',
            'jabatan' => 'Statistisi',
            'pangkat_golongan' => 'III/a',
            'unit_kerja' => 'BPS',
        ]);

        for ($i = 1; $i <= 15; $i++) {
            Laporan::create([
                'pegawai_id' => $pegawai->id,
                'pembiayaan_id' => null,
                'judul_laporan' => "Judul {$i}",
                'perihal_laporan' => "Laporan Unik Nomor {$i}",
                'tujuan_surat' => 'Kepala BPS',
                'tempat_laporan' => 'Amurang',
                'tanggal_laporan' => '2026-08-01',
                'lokasi_tujuan' => 'Lokasi Test',
            ]);
        }

        // Default 10 rows: Page 1 should see Nomor 15 to Nomor 6, but not Nomor 5
        $res10 = $this->actingAs($user)->get(route('laporan.index', ['per_page' => '10']));
        $res10->assertOk();
        $res10->assertSee('Laporan Unik Nomor 15');
        $res10->assertDontSee('Laporan Unik Nomor 5');

        // 25 rows: should see all 15 reports on page 1
        $res25 = $this->actingAs($user)->get(route('laporan.index', ['per_page' => '25']));
        $res25->assertOk();
        $res25->assertSee('Laporan Unik Nomor 15');
        $res25->assertSee('Laporan Unik Nomor 1');

        // 'all' rows: should see all 15 reports
        $resAll = $this->actingAs($user)->get(route('laporan.index', ['per_page' => 'all']));
        $resAll->assertOk();
        $resAll->assertSee('Laporan Unik Nomor 15');
        $resAll->assertSee('Laporan Unik Nomor 1');
    }
}
