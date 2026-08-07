<?php

namespace App\Support;

/**
 * Data contoh KAK (berdasar KAK Perjalanan Rakornas SE2026).
 * Dipakai sebagai isian awal form "Buat KAK" dan oleh KakSeeder
 * agar contoh yang sama tampil di daftar /kak.
 */
class KakExample
{
    /**
     * @return array<string, mixed>
     */
    public static function fields(): array
    {
        return [
            'judul' => 'Perjalanan Dalam Rangka Rapat Koordinasi Nasional Sensus Ekonomi 2026',
            'tahun' => '2026',
            'unit_kerja' => 'Badan Pusat Statistik Kabupaten Minahasa Selatan',

            'dasar_hukum' => '<p>Dasar hukum yang digunakan dalam kegiatan ini adalah sebagai berikut:</p>'
                .'<ol>'
                .'<li>Undang-Undang Nomor 16 Tahun 1997 tentang Statistik (Lembaran Negara Republik Indonesia Tahun 1997 Nomor 39, Tambahan Lembaran Negara Republik Indonesia Nomor 3683);</li>'
                .'<li>Undang-Undang Nomor 17 Tahun 2003 tentang Keuangan Negara;</li>'
                .'<li>Peraturan Pemerintah Nomor 51 Tahun 1999 tentang Penyelenggaraan Statistik (Lembaran Negara Republik Indonesia Tahun 1999 Nomor 96, Tambahan Lembaran Negara Republik Indonesia Nomor 3854);</li>'
                .'<li>Peraturan Presiden Nomor 39 Tahun 2019 tentang Satu Data Indonesia (Lembaran Negara Republik Indonesia Tahun 2019 Nomor 112);</li>'
                .'<li>Peraturan Menteri Keuangan Republik Indonesia Nomor 51/PMK.02/2014 tentang Perubahan Atas Peraturan Menteri Keuangan Nomor 71/PMK.02/2013 tentang Pedoman Standar Biaya, Standar Struktur Biaya, dan Indeksasi dalam Penyusunan Rencana Kerja dan Anggaran Kementerian Negara/Lembaga (Berita Negara Republik Indonesia Tahun 2014 Nomor 342);</li>'
                .'<li>Peraturan Menteri Keuangan Republik Indonesia Nomor 62 Tahun 2023 tentang Perencanaan Anggaran, Pelaksanaan Anggaran serta Akuntansi dan Pelaporan Keuangan (Berita Negara Republik Indonesia Tahun 2023 Nomor 472) sebagaimana telah diubah dengan Peraturan Menteri Keuangan Republik Indonesia Nomor 107 Tahun 2024;</li>'
                .'<li>Peraturan Menteri Keuangan Republik Indonesia Nomor 32 Tahun 2025 tentang Standar Biaya Masukan Tahun Anggaran 2026;</li>'
                .'<li>Peraturan Menteri Keuangan Republik Indonesia Nomor 119 Tahun 2023 tentang Perubahan Atas Peraturan Menteri Keuangan Nomor 113/PMK.05/2012 tentang Perjalanan Dinas Dalam Negeri Bagi Pejabat Negara, Pegawai Negeri, dan Pegawai Tidak Tetap.</li>'
                .'</ol>',

            'gambaran_umum' => '<p>Sensus Ekonomi 2026 (SE2026) merupakan Sensus Ekonomi yang kelima yang akan dilaksanakan di Indonesia. Sensus Ekonomi dilaksanakan setiap 10 tahun sekali di tahun yang berakhiran 6 sesuai dengan amanat Undang-Undang Nomor 16 Tahun 1997 tentang Statistik.</p>'
                .'<p>SE2026 dilaksanakan dengan tujuan umum untuk menyediakan data dasar seluruh kegiatan ekonomi, kecuali lapangan usaha Pertanian, Kehutanan, dan Perikanan (A), Administrasi Pemerintahan, Pertahanan, dan Jaminan Sosial Wajib (O), dan Aktivitas Rumah Tangga sebagai Pemberi Kerja; Aktivitas yang Menghasilkan Barang Jasa oleh Rumah Tangga (T), sebagai landasan bagi penyusunan kebijakan dan perencanaan pembangunan nasional.</p>'
                .'<p>Adapun tujuan khusus dilaksanakannya SE2026 yaitu menyediakan informasi struktur ekonomi, menyediakan informasi karakteristik usaha, dan menyediakan informasi ekonomi digital dan ekonomi lingkungan. SE2026 direncanakan dilaksanakan pada bulan Mei 2026 untuk Perusahaan Besar dengan metode CAWI untuk pengumpulan datanya, dan bulan Juni-Juli 2026 untuk Perusahaan Mikro, Kecil, dan Menengah dengan metode CAPI untuk pengumpulan datanya.</p>',

            'maksud_tujuan' => '<p>Maksud dan tujuan dilaksanakannya kegiatan ini yaitu memperkuat koordinasi antara BPS Pusat dan BPS Provinsi/Kabupaten/Kota dalam rangka persiapan pelaksanaan kegiatan Sensus Ekonomi 2026.</p>',

            'keluaran' => '<p>Terlaksananya koordinasi yang efektif antara BPS Pusat dan BPS Provinsi/Kabupaten/Kota dalam rangka persiapan pelaksanaan kegiatan Sensus Ekonomi 2026.</p>',

            'pelaksana_kegiatan' => '<p>Rancangan pelaksana kegiatan adalah Kepala BPS Kabupaten Minahasa Selatan.</p>',

            'jadwal_lokasi' => '<p>Kegiatan dijadwalkan akan dilaksanakan pada rentang 31 Januari - 3 Februari 2026 bertempat di wilayah Provinsi DKI Jakarta.</p>',

            'sumber_pembiayaan' => '<p>Biaya yang dibutuhkan dalam kegiatan ini adalah sebesar Rp. 7.000.000,- (Tujuh Juta Rupiah) yang akan dibebankan pada DIPA BPS Kabupaten Minahasa Selatan T.A. 2026 dengan rincian anggaran biaya sebagai berikut:</p>',

            'penutup' => '<ol>'
                .'<li>Apabila terdapat hal-hal yang bertentangan dengan ketentuan, peraturan, pedoman, dan kebijaksanaan pemerintah yang berlaku, maka segala yang termaktub di dalam Kerangka Acuan Kegiatan (KAK) akan diteliti kembali.</li>'
                .'<li>Hal-hal yang belum diatur dalam KAK akan ditetapkan lebih lanjut.</li>'
                .'<li>Demikian KAK ini dibuat untuk dipergunakan semestinya.</li>'
                .'</ol>',

            'tempat_penandatanganan' => 'Minahasa Selatan',
            'tanggal_penandatanganan' => '2026-01-28',

            'ttd_kiri_jabatan' => 'Ketua Tim Pelaksana SE2026',
            'ttd_kiri_nama' => 'Johanes, S.ST',
            'ttd_kiri_nip' => '198005112002121003',

            'ttd_kanan_jabatan' => 'Pejabat Pembuat Komitmen',
            'ttd_kanan_nama' => 'Afwin Fauzy Akhsan S.Tr.Stat',
            'ttd_kanan_nip' => '199609222019011002',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function anggarans(): array
    {
        return [
            [
                'kode_mak' => '2902.BMA.006',
                'deskripsi' => 'Perjalanan dinas dalam rangka rakornas',
                'volume' => 1,
                'satuan' => 'O-P',
                'harga_satuan' => 7000000,
                'jumlah_biaya' => 7000000,
            ],
        ];
    }
}
