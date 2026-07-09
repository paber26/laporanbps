<?php

namespace Database\Seeders;

use App\Models\MasterPembiayaan;
use Illuminate\Database\Seeder;

class MasterPembiayaanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'program' => '(054.01.GG) Penyediaan dan Pelayanan Informasi Statistik',
                'kegiatan' => '(2906) Penyediaan dan Pengembangan Statistik Kesejahteraan Rakyat',
                'ro' => 'BMA.006 Publikasi/Laporan Survei Sosial Ekonomi Nasional',
                'komponen' => '(052) Pengumpulan Data',
                'akun' => '(524113) Belanja Perjalanan Dinas Dalam Kota',
            ],
            [
                'program' => '(054.01.GG) Penyediaan dan Pelayanan Informasi Statistik',
                'kegiatan' => '(2908) Penyediaan dan Pengembangan Statistik Distribusi',
                'ro' => 'BMA.007 Publikasi/Laporan Statistik Harga',
                'komponen' => '(052) Pengumpulan Data',
                'akun' => '(524111) Belanja Perjalanan Dinas Biasa',
            ],
            [
                'program' => '(054.01.WA) Pemenuhan Data dan Informasi Statistik',
                'kegiatan' => '(2895) Pengembangan Sistem Informasi Statistik',
                'ro' => 'BMA.001 Layanan Data dan Informasi Statistik',
                'komponen' => '(051) Persiapan',
                'akun' => '(524113) Belanja Perjalanan Dinas Dalam Kota',
            ],
        ];

        foreach ($data as $row) {
            MasterPembiayaan::firstOrCreate(
                ['kegiatan' => $row['kegiatan'], 'ro' => $row['ro']],
                $row
            );
        }
    }
}
