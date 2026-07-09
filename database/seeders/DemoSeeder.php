<?php

namespace Database\Seeders;

use App\Models\Laporan;
use App\Models\LaporanUraian;
use App\Models\MasterPembiayaan;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@bps.go.id'],
            [
                'name' => 'Bernaldo Napitupulu',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $pegawai = Pegawai::firstOrCreate(
            ['nip' => '200011112023021002'],
            [
                'user_id' => $user->id,
                'nama' => 'Bernaldo Napitupulu',
                'unit_kerja' => 'BPS Kabupaten Minahasa Selatan',
            ]
        );

        $pembiayaan = MasterPembiayaan::query()->first();

        $laporan = Laporan::firstOrCreate(
            ['pegawai_id' => $pegawai->id, 'perihal_laporan' => 'Laporan Perjalanan Dinas Groundcheck Variabel Sosial Ekonomi PBI JKN Tahun 2026'],
            [
                'pembiayaan_id' => $pembiayaan?->id,
                'judul_laporan' => 'Laporan Perjalanan Dinas Supervisi Pelaksanaan Groundcheck Variabel Sosial Ekonomi PBI JKN Tahun 2026',
                'tujuan_surat' => 'Kepala BPS Kabupaten Minahasa Selatan',
                'tempat_laporan' => 'Amurang Barat',
                'tanggal_laporan' => '2026-03-06',
                'lokasi_tujuan' => 'Minahasa Selatan',
            ]
        );

        if ($laporan->uraians()->count() === 0) {
            LaporanUraian::create([
                'laporan_id' => $laporan->id,
                'tanggal_kegiatan' => '2026-03-04',
                'jam_mulai' => '08.00',
                'jam_selesai' => '17.45 WITA',
                'urutan' => 1,
                'uraian_text' => '<p>Pada pukul 08.00 WITA, kegiatan diawali dengan keberangkatan dari Kantor BPS menuju lokasi groundcheck Susenas di Kecamatan Ranoyapo. Setibanya di lokasi, dilakukan koordinasi awal dengan aparat setempat untuk menyampaikan maksud dan tujuan pelaksanaan groundcheck, yaitu melakukan verifikasi terhadap beberapa anomali data serta inkonsistensi jawaban hasil pencacahan sebelumnya.</p>'.
                    '<p>Kegiatan kemudian dilanjutkan dengan kunjungan ke beberapa rumah tangga responden terpilih. Proses groundcheck dilakukan melalui pendekatan klarifikasi langsung kepada responden, dengan menelusuri kembali jawaban yang terindikasi tidak konsisten serta melakukan pendalaman (probing) terhadap variabel-variabel penting, khususnya terkait konsumsi rumah tangga dan kondisi sosial ekonomi.</p>'.
                    '<p>Groundcheck dilaksanakan hingga pukul 17.45 WITA. Setelah kegiatan selesai, saya kembali ke kantor untuk melakukan rekapitulasi dan pelaporan hasil groundcheck.</p>',
            ]);
        }
    }
}
