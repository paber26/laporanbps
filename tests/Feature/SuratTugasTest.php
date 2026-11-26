<?php

namespace Tests\Feature;

use App\Models\MasterPembiayaan;
use App\Models\Pegawai;
use App\Models\SuratTugas;
use App\Models\User;
use App\Services\SuratTugasWordExporter;
use App\Support\ChromePdf;
use Database\Seeders\PegawaiOrganikSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class SuratTugasTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role = 'user'): User
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->save();

        return $user;
    }

    protected function makePegawai(): Pegawai
    {
        return Pegawai::create([
            'nip' => '19980620 202104 2 003',
            'nama' => 'Melati Sukmaningtyas, S.Tr.Stat',
            'jabatan' => 'Statistisi Ahli Pertama',
            'pangkat_golongan' => 'Penata Muda Tk.I (III/b)',
            'unit_kerja' => 'BPS Kabupaten Minahasa Selatan',
        ]);
    }

    protected function validPayload(Pegawai $pegawai, ?MasterPembiayaan $pembiayaan = null, array $overrides = []): array
    {
        return array_merge([
            'pegawai_id' => $pegawai->id,
            'pembiayaan_id' => $pembiayaan?->id,
            'nomor_st_urut' => '902',
            'kode_klasifikasi' => 'KP.650',
            'tahun' => 2026,
            'nomor_st' => 'B-902/71050/KP.650/2026',
            'nomor_spd' => '722/SPD_71050/2906/11/2026',
            'tempat_st' => 'Minahasa Selatan',
            'tanggal_st' => '2026-08-31',
            'menimbang' => 'Bahwa dalam rangka tersedianya data Susenas September 2026 yang terjamin kualitasnya, dipandang perlu dilakukan pengawasan lapangan.',
            'mengingat' => implode("\n", config('laporan.surat_tugas.default_mengingat')),
            'tujuan_kegiatan' => 'Melaksanakan pengawasan pemutakhiran lapangan Susenas September 2026',
            'waktu_pelaksanaan' => '3-4 September 2026',
            'jabatan' => 'Statistisi Ahli Pertama',
            'pangkat_golongan' => 'Penata Muda Tk.I (III/b)',
            'unit_kerja' => 'BPS Kabupaten Minahasa Selatan',
            'tingkat_biaya' => 'C',
            'alat_angkutan' => 'Lainnya',
            'tempat_berangkat' => 'Kabupaten Minahasa Selatan',
            'tempat_tujuan' => 'Kabupaten Minahasa Selatan',
            'kecamatan_asal' => 'Amurang Barat',
            'jumlah_hari' => 2,
            'tanggal_berangkat' => '2026-09-03',
            'tanggal_kembali' => '2026-09-04',
            'mata_anggaran' => 'GG 2906 BMA 006 052 A 524113',
            'program_kode' => 'GG',
            'program_nama' => 'Program Penyediaan dan Pelayanan Informasi Statistik',
            'kegiatan_kode' => '2906',
            'kegiatan_nama' => 'Penyediaan dan Pengembangan Statistik Kesejahteraan Rakyat',
            'komponen_kode' => '052',
            'komponen_nama' => 'Pengumpulan Data',
            'instansi_anggaran' => 'Badan Pusat Statistik Minahasa Selatan',
            'kepala_jabatan' => 'Kepala BPS Kabupaten Minahasa Selatan',
            'kepala_nama' => 'Irena Listianawati SST, SE., M.Si',
            'kepala_nip' => '19810706 200312 2 005',
            'ppk_jabatan' => 'PEJABAT PEMBUAT KOMITMEN',
            'ppk_sub_jabatan' => 'Program Dukungan Manajemen',
            'ppk_nama' => 'Afwin Fauzy Akhsan S.Tr.Stat.',
            'ppk_nip' => '199609222019011002',
            'sertakan_ttd_kepala' => '0',
            'rutes' => [
                [
                    'kecamatan_tujuan' => 'Modoinding',
                    'tanggal_tiba' => '2026-09-03',
                    'tanggal_berangkat' => '2026-09-03',
                ],
                [
                    'kecamatan_tujuan' => 'Maesaan',
                    'tanggal_tiba' => '2026-09-04',
                    'tanggal_berangkat' => '2026-09-04',
                ],
            ],
        ], $overrides);
    }

    public function test_pegawai_organik_seeder_populates_29_records_idempotently(): void
    {
        $this->seed(PegawaiOrganikSeeder::class);
        $this->assertSame(29, Pegawai::count());

        $bernaldo = Pegawai::where('nip', '20001111 202302 1 002')->first();
        $this->assertNotNull($bernaldo);
        $this->assertSame('Pranata Komputer Pertama', $bernaldo->jabatan);
        $this->assertSame('Penata Muda (III/a)', $bernaldo->pangkat_golongan);

        // Jalankan ulang untuk memastikan idempoten (tidak menggandakan baris).
        $this->seed(PegawaiOrganikSeeder::class);
        $this->assertSame(29, Pegawai::count());
    }

    public function test_user_can_create_view_filter_update_duplicate_and_delete_surat_tugas(): void
    {
        $user = $this->makeUser();
        $pegawai = $this->makePegawai();

        $this->actingAs($user)
            ->get(route('surat-tugas.create'))
            ->assertOk()
            ->assertSee('Buat Surat Tugas Baru');

        $response = $this->actingAs($user)
            ->post(route('surat-tugas.store'), $this->validPayload($pegawai));

        $st = SuratTugas::with('rutes')->firstOrFail();
        $response->assertRedirect(route('surat-tugas.show', $st));
        $this->assertSame($user->id, $st->user_id);
        $this->assertCount(2, $st->rutes);

        // Preview 3 halaman: NIP pegawai tampil di SPD, "/ tiba ditempat baru *)" dihapus,
        // Visum I tetap menampilkan tujuan pertama, dan kotak Visum tujuan dikosongkan.
        $this->actingAs($user)
            ->get(route('surat-tugas.show', $st))
            ->assertOk()
            ->assertSee('B-902/71050/KP.650/2026')
            ->assertSee('722/SPD_71050/2906/11/2026')
            ->assertSee('Nama / NIP pegawai yang melaksanakan perjalanan dinas')
            ->assertSee('NIP. 19980620 202104 2 003')
            ->assertSee('Tanggal harus kembali')
            ->assertDontSee('tiba ditempat baru')
            ->assertSee('Kecamatan Modoinding')
            ->assertDontSee('Kecamatan Maesaan');

        // Daftar & pencarian
        $this->actingAs($user)
            ->get(route('surat-tugas.index', ['search' => 'B-902']))
            ->assertOk()
            ->assertSee('Melati Sukmaningtyas');

        // Edit & Update
        $this->actingAs($user)
            ->put(route('surat-tugas.update', $st), $this->validPayload($pegawai, null, [
                'nomor_st_urut' => '1163d',
                'nomor_st' => 'B-1163d/71050/KP.650/2026',
            ]))
            ->assertRedirect(route('surat-tugas.show', $st));

        $this->assertSame('B-1163d/71050/KP.650/2026', $st->fresh()->nomor_st);

        // Duplikat
        $dupResp = $this->actingAs($user)->post(route('surat-tugas.duplicate', $st));
        $this->assertSame(2, SuratTugas::count());
        $copy = SuratTugas::latest('id')->first();
        $dupResp->assertRedirect(route('surat-tugas.edit', $copy));
        $this->assertCount(2, $copy->rutes);

        // Hapus
        $this->actingAs($user)
            ->delete(route('surat-tugas.destroy', $copy))
            ->assertRedirect(route('surat-tugas.index'));
        $this->assertSame(1, SuratTugas::count());
    }

    public function test_policy_blocks_non_owner_edit_and_delete_but_allows_admin(): void
    {
        $owner = $this->makeUser('user');
        $other = $this->makeUser('user');
        $admin = $this->makeUser('admin');
        $pegawai = $this->makePegawai();

        $this->actingAs($owner)->post(route('surat-tugas.store'), $this->validPayload($pegawai));
        $st = SuratTugas::firstOrFail();

        // Non-pemilik boleh lihat & duplikat, tapi ditolak edit/update/delete.
        $this->actingAs($other)->get(route('surat-tugas.show', $st))->assertOk();
        $this->actingAs($other)->get(route('surat-tugas.edit', $st))->assertForbidden();
        $this->actingAs($other)->put(route('surat-tugas.update', $st), $this->validPayload($pegawai))->assertForbidden();
        $this->actingAs($other)->delete(route('surat-tugas.destroy', $st))->assertForbidden();

        // Admin diizinkan edit & hapus milik siapa pun.
        $this->actingAs($admin)->get(route('surat-tugas.edit', $st))->assertOk();
        $this->actingAs($admin)->delete(route('surat-tugas.destroy', $st))->assertRedirect(route('surat-tugas.index'));
    }

    public function test_word_export_generates_clean_docx_for_2_and_3_rutes_and_optional_ttd(): void
    {
        $user = $this->makeUser();
        $pegawai = $this->makePegawai();

        $this->actingAs($user)->post(route('surat-tugas.store'), $this->validPayload($pegawai, null, [
            'sertakan_ttd_kepala' => '1',
            'rutes' => [
                ['kecamatan_tujuan' => 'Modoinding', 'tanggal_tiba' => '2026-09-03', 'tanggal_berangkat' => '2026-09-03'],
                ['kecamatan_tujuan' => 'Maesaan', 'tanggal_tiba' => '2026-09-04', 'tanggal_berangkat' => '2026-09-04'],
                ['kecamatan_tujuan' => 'Tompaso Baru', 'tanggal_tiba' => '2026-09-05', 'tanggal_berangkat' => '2026-09-05'],
            ],
            'jumlah_hari' => 3,
            'tanggal_kembali' => '2026-09-05',
        ]));

        $st = SuratTugas::with(['pegawai', 'rutes'])->firstOrFail();

        // Uji download route.
        $this->actingAs($user)
            ->get(route('surat-tugas.word', $st).'?ukuran=f4')
            ->assertOk()
            ->assertHeader('content-disposition');

        // Inspeksi langsung berkas DOCX hasil generator.
        $path = app(SuratTugasWordExporter::class)->generate($st, 'f4');
        $this->assertFileExists($path);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertIsString($xml);
        // Pastikan tidak ada placeholder ${...} yang tertinggal.
        $this->assertDoesNotMatchRegularExpression('/\$\{[a-zA-Z0-9_]+\}/', $xml);
        $this->assertStringContainsString('B-902/71050/KP.650/2026', $xml);
        $this->assertStringContainsString('722/SPD_71050/2906/11/2026', $xml);
        $this->assertStringContainsString('NIP. 19980620 202104 2 003', $xml);
        $this->assertStringNotContainsString('tiba ditempat baru', $xml);
        $this->assertStringContainsString('Kecamatan Modoinding', $xml);
        // Kotak Visum tujuan (Blok II..N) dikosongkan untuk pengisian di lapangan.
        $this->assertStringNotContainsString('Kecamatan Maesaan', $xml);
        $this->assertStringNotContainsString('Kecamatan Tompaso Baru', $xml);
    }

    public function test_rute_visum_accepts_kecamatan_tujuan_without_dates(): void
    {
        $user = $this->makeUser();
        $pegawai = $this->makePegawai();

        $this->actingAs($user)
            ->post(route('surat-tugas.store'), $this->validPayload($pegawai, null, [
                'rutes' => [
                    ['kecamatan_tujuan' => 'Sinonsayang'],
                ],
            ]))
            ->assertRedirect();

        $st = SuratTugas::with('rutes')->firstOrFail();
        $this->assertCount(1, $st->rutes);
        $this->assertSame('Sinonsayang', $st->rutes->first()->kecamatan_tujuan);
    }

    public function test_pdf_view_and_chrome_pdf_export_work(): void
    {
        $user = $this->makeUser();
        $pegawai = $this->makePegawai();

        $this->actingAs($user)->post(route('surat-tugas.store'), $this->validPayload($pegawai));
        $st = SuratTugas::with(['pegawai', 'rutes'])->firstOrFail();

        $html = view('surat_tugas.pdf-chrome', [
            'suratTugas' => $st,
            'paperCss' => '215mm 330mm',
        ])->render();

        $this->assertStringContainsString('SURAT TUGAS', $html);
        $this->assertStringContainsString('B-902/71050/KP.650/2026', $html);
        $this->assertStringContainsString('Pejabat Pembuat Komitmen', $html);
        $this->assertStringContainsString('Kecamatan Modoinding', $html);

        // Jika Chrome tersedia di mesin lokal, verifikasi output binary PDF asli.
        try {
            ChromePdf::binary();
            $resp = $this->actingAs($user)->get(route('surat-tugas.pdf', $st).'?ukuran=f4');
            $resp->assertOk();
            $resp->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $resp->getContent());
        } catch (\RuntimeException $e) {
            $this->assertTrue(true);
        }
    }

    public function test_shortcut_buat_laporan_dari_st_prefills_laporan_create_form(): void
    {
        $user = $this->makeUser();
        $pegawai = $this->makePegawai();
        $pembiayaan = MasterPembiayaan::create([
            'program' => '(054.01.GG) Penyediaan dan Pelayanan Informasi Statistik',
            'kegiatan' => '(2906) Penyediaan dan Pengembangan Statistik Kesejahteraan Rakyat',
            'ro' => 'BMA.006 Publikasi/Laporan Survei Sosial Ekonomi Nasional',
            'komponen' => '(052) Pengumpulan Data',
            'akun' => '(524113) Belanja Perjalanan Dinas Dalam Kota',
        ]);

        $this->actingAs($user)->post(route('surat-tugas.store'), $this->validPayload($pegawai, $pembiayaan));
        $st = SuratTugas::firstOrFail();

        $this->actingAs($user)
            ->get(route('laporan.create', ['from_st' => $st->id]))
            ->assertOk()
            ->assertSee('B-902/71050/KP.650/2026')
            ->assertSee('Kecamatan Modoinding, Maesaan')
            ->assertSee('Melaksanakan pengawasan pemutakhiran lapangan Susenas September 2026');
    }
}
