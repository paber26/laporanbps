<?php

namespace Tests\Feature;

use App\Models\Kak;
use App\Models\Laporan;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function petugas(): User
    {
        return User::factory()->create();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function makeLaporan(User $owner): Laporan
    {
        $pegawai = Pegawai::create([
            'user_id' => $owner->id,
            'nama' => 'Pegawai '.$owner->id,
            'nip' => str_pad((string) (900000000000000000 + $owner->id), 18, '0', STR_PAD_LEFT),
            'unit_kerja' => 'BPS Minahasa Selatan',
        ]);

        return $owner->laporans()->create([
            'pegawai_id' => $pegawai->id,
            'judul_laporan' => 'Judul Milik '.$owner->id,
            'perihal_laporan' => 'Perihal Milik '.$owner->id,
            'tujuan_surat' => 'Kepala BPS',
            'tempat_laporan' => 'Amurang',
            'tanggal_laporan' => '2026-06-01',
            'lokasi_tujuan' => 'Lokasi Test',
        ]);
    }

    private function makeKak(User $owner): Kak
    {
        return $owner->kaks()->create([
            'judul' => 'KAK Milik '.$owner->id,
            'tahun' => '2026',
        ]);
    }

    // ---- Laporan: kepemilikan (lihat semua, edit miliknya) ----

    public function test_pemilik_bisa_membuka_form_edit_laporan_miliknya(): void
    {
        $owner = $this->petugas();
        $laporan = $this->makeLaporan($owner);

        $this->actingAs($owner)->get(route('laporan.edit', $laporan))->assertOk();
    }

    public function test_bukan_pemilik_ditolak_edit_update_hapus_laporan_orang_lain(): void
    {
        $owner = $this->petugas();
        $other = $this->petugas();
        $laporan = $this->makeLaporan($owner);

        $this->actingAs($other)->get(route('laporan.edit', $laporan))->assertForbidden();
        $this->actingAs($other)->put(route('laporan.update', $laporan), [])->assertForbidden();
        $this->actingAs($other)->delete(route('laporan.destroy', $laporan))->assertForbidden();

        $this->assertDatabaseHas('laporans', ['id' => $laporan->id]);
    }

    public function test_admin_bisa_edit_dan_hapus_laporan_siapa_pun(): void
    {
        $owner = $this->petugas();
        $admin = $this->admin();
        $laporan = $this->makeLaporan($owner);

        $this->actingAs($admin)->get(route('laporan.edit', $laporan))->assertOk();
        $this->actingAs($admin)->delete(route('laporan.destroy', $laporan))->assertRedirect(route('laporan.index'));

        $this->assertDatabaseMissing('laporans', ['id' => $laporan->id]);
    }

    public function test_semua_pengguna_boleh_melihat_daftar_dan_detail_laporan(): void
    {
        $owner = $this->petugas();
        $other = $this->petugas();
        $laporan = $this->makeLaporan($owner);

        $this->actingAs($other)->get(route('laporan.index'))->assertOk()->assertSee('Perihal Milik '.$owner->id);
        $this->actingAs($other)->get(route('laporan.show', $laporan))->assertOk();
    }

    public function test_store_laporan_menetapkan_pemilik_ke_pembuat(): void
    {
        $pembuat = $this->petugas();
        $pegawai = Pegawai::create([
            'nama' => 'Pegawai Subjek',
            'nip' => '123456789012345670',
            'unit_kerja' => 'BPS',
        ]);

        $response = $this->actingAs($pembuat)->post(route('laporan.store'), [
            'pegawai_id' => $pegawai->id,
            'judul_laporan' => 'Laporan Baru',
            'perihal_laporan' => 'Perihal Baru',
            'tujuan_surat' => 'Kepala BPS',
            'tempat_laporan' => 'Amurang',
            'tanggal_laporan' => '2026-06-10',
            'lokasi_tujuan' => 'Popontolen',
            'uraians' => [
                ['tanggal_kegiatan' => '2026-06-09', 'uraian_text' => 'Kegiatan uji'],
            ],
        ]);

        $laporan = Laporan::where('judul_laporan', 'Laporan Baru')->firstOrFail();
        $response->assertRedirect(route('laporan.show', $laporan));
        $this->assertSame($pembuat->id, $laporan->user_id);
    }

    public function test_duplikat_laporan_dimiliki_oleh_yang_menduplikat(): void
    {
        $owner = $this->petugas();
        $duplicator = $this->petugas();
        $laporan = $this->makeLaporan($owner);

        $this->actingAs($duplicator)->post(route('laporan.duplicate', $laporan))->assertRedirect();

        $salinan = Laporan::where('judul_laporan', $laporan->judul_laporan.' (Salinan)')->firstOrFail();
        $this->assertSame($duplicator->id, $salinan->user_id);

        // Penduplikat kini pemilik salinan → boleh mengeditnya.
        $this->actingAs($duplicator)->get(route('laporan.edit', $salinan))->assertOk();
    }

    // ---- KAK: kepemilikan ----

    public function test_bukan_pemilik_ditolak_edit_update_hapus_kak(): void
    {
        $owner = $this->petugas();
        $other = $this->petugas();
        $kak = $this->makeKak($owner);

        $this->actingAs($other)->get(route('kak.edit', $kak))->assertForbidden();
        $this->actingAs($other)->put(route('kak.update', $kak), [])->assertForbidden();
        $this->actingAs($other)->delete(route('kak.destroy', $kak))->assertForbidden();

        $this->assertDatabaseHas('kaks', ['id' => $kak->id]);
    }

    public function test_pemilik_dan_admin_bisa_mengakses_edit_kak(): void
    {
        $owner = $this->petugas();
        $admin = $this->admin();
        $kak = $this->makeKak($owner);

        $this->actingAs($owner)->get(route('kak.edit', $kak))->assertOk();
        $this->actingAs($admin)->get(route('kak.edit', $kak))->assertOk();
    }

    public function test_store_kak_menetapkan_pemilik_ke_pembuat(): void
    {
        $pembuat = $this->petugas();

        $this->actingAs($pembuat)->post(route('kak.store'), [
            'judul' => 'KAK Baru Uji',
            'tahun' => '2026',
        ])->assertRedirect();

        $kak = Kak::where('judul', 'KAK Baru Uji')->firstOrFail();
        $this->assertSame($pembuat->id, $kak->user_id);
    }

    public function test_bukan_pemilik_ditolak_unggah_docx_kak(): void
    {
        Storage::fake('local');
        $owner = $this->petugas();
        $other = $this->petugas();
        $kak = $this->makeKak($owner);

        $docx = UploadedFile::fake()->create(
            'edited.docx', 20,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );

        $this->actingAs($other)
            ->post(route('kak.docx.upload', $kak), ['file' => $docx])
            ->assertForbidden();

        $this->assertNull($kak->fresh()->docx_edited_path);

        // Pemilik boleh.
        $this->actingAs($owner)
            ->post(route('kak.docx.upload', $kak), ['file' => $docx])
            ->assertRedirect();

        $this->assertNotNull($kak->fresh()->docx_edited_path);
    }

    // ---- Master Pegawai: admin-only (verb mutasi) ----

    public function test_non_admin_ditolak_update_dan_hapus_pegawai(): void
    {
        $petugas = $this->petugas();
        $pegawai = Pegawai::create([
            'nama' => 'Target',
            'nip' => '555666777888999000',
            'unit_kerja' => 'BPS',
        ]);

        $this->actingAs($petugas)->put(route('pegawai.update', $pegawai), [
            'nip' => '555666777888999000',
            'nama' => 'Diubah',
            'unit_kerja' => 'BPS',
        ])->assertForbidden();

        $this->actingAs($petugas)->delete(route('pegawai.destroy', $pegawai))->assertForbidden();

        $this->assertDatabaseHas('pegawais', ['id' => $pegawai->id, 'nama' => 'Target']);
    }

    // ---- Master Pegawai: admin-only ----

    public function test_non_admin_ditolak_mengakses_manajemen_pegawai(): void
    {
        $petugas = $this->petugas();

        $this->actingAs($petugas)->get(route('pegawai.index'))->assertForbidden();
        $this->actingAs($petugas)->get(route('pegawai.create'))->assertForbidden();
        $this->actingAs($petugas)->post(route('pegawai.store'), [
            'nip' => '111222333444555666',
            'nama' => 'Coba',
            'unit_kerja' => 'BPS',
        ])->assertForbidden();

        $this->assertDatabaseMissing('pegawais', ['nip' => '111222333444555666']);
    }

    public function test_admin_bisa_mengakses_manajemen_pegawai(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('pegawai.index'))->assertOk();
        $this->actingAs($admin)->get(route('pegawai.create'))->assertOk();
    }

    // ---- Registrasi mandiri → role petugas ----

    public function test_registrasi_mandiri_menghasilkan_role_petugas(): void
    {
        $this->post(route('register'), [
            'name' => 'Petugas Baru',
            'nip' => '777888999000111222',
            'unit_kerja' => 'BPS Kabupaten X',
            'email' => 'petugasbaru@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'petugasbaru@example.com')->firstOrFail();
        $this->assertFalse($user->isAdmin());
        $this->assertSame('petugas', $user->role);
    }
}
