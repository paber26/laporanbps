<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blok "Mengetahui"
    |--------------------------------------------------------------------------
    |
    | Nama & jabatan pejabat yang mengetahui/menyetujui laporan (mis. Kepala
    | BPS Kabupaten). Sama untuk semua laporan — ubah lewat .env bila
    | pejabatnya berganti, tanpa perlu mengubah data tiap laporan.
    |
    */

    'mengetahui' => [
        'jabatan' => env('LAPORAN_MENGETAHUI_JABATAN', 'Kepala Badan Pusat Statistik Kabupaten Minahasa Selatan'),
        'nama' => env('LAPORAN_MENGETAHUI_NAMA', 'Irena Listianawati, SST, SE, M.Si.'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pengaturan Default Surat Tugas (ST + SPD + Visum)
    |--------------------------------------------------------------------------
    |
    | Nilai bawaan kop, kode satker, pejabat penandatangan (Kepala BPS & PPK),
    | dasar hukum (Mengingat), dan daftar 17 kecamatan di Kabupaten Minahasa
    | Selatan. Pejabat penandatangan tetap bisa diubah per-Surat Tugas di form.
    |
    */

    'surat_tugas' => [
        'kode_satker' => env('ST_KODE_SATKER', '71050'),
        'kode_klasifikasi' => env('ST_KODE_KLASIFIKASI', 'KP.650'),
        'tempat_st' => env('ST_TEMPAT', 'Minahasa Selatan'),
        'tempat_kedudukan' => env('ST_TEMPAT_KEDUDUKAN', 'Amurang Barat'),
        'kabupaten' => env('ST_KABUPATEN', 'Kabupaten Minahasa Selatan'),
        'instansi' => env('ST_INSTANSI', 'BPS Kabupaten Minahasa Selatan'),
        'instansi_panjang' => env('ST_INSTANSI_PANJANG', 'Badan Pusat Statistik Minahasa Selatan'),

        'kepala_bps' => [
            'jabatan' => env('ST_KEPALA_JABATAN', 'Kepala BPS Kabupaten Minahasa Selatan'),
            'nama' => env('ST_KEPALA_NAMA', 'Irena Listianawati SST, SE., M.Si'),
            'nip' => env('ST_KEPALA_NIP', '19810706 200312 2 005'),
            'ttd_scan_path' => env('ST_KEPALA_TTD_PATH', 'assets/ttd-kepala-bps.png'),
        ],

        'ppk' => [
            'jabatan' => env('ST_PPK_JABATAN', 'PEJABAT PEMBUAT KOMITMEN'),
            'sub_jabatan' => env('ST_PPK_SUB_JABATAN', 'Program Dukungan Manajemen'),
            'nama' => env('ST_PPK_NAMA', 'Afwin Fauzy Akhsan S.Tr.Stat.'),
            'nip' => env('ST_PPK_NIP', '199609222019011002'),
        ],

        'default_mengingat' => [
            'Undang-Undang Nomor 16 Tahun 1997 tentang Statistik;',
            'Peraturan Pemerintah Nomor 51 Tahun 1999 tentang Penyelenggaraan Statistik;',
            'Peraturan Presiden Nomor 86 Tahun 2007 tentang Badan Pusat Statistik sebagaimana diubah dengan Peraturan Presiden Republik Indonesia Nomor 1 Tahun 2025 tentang Perubahan atas Peraturan Presiden Nomor 86 Tahun 2007 tentang Badan Pusat Statistik;',
            'Peraturan Badan Pusat Statistik Nomor 1 Tahun 2023 Tentang Pedoman Tata Naskah Dinas Badan Pusat Statistik;',
            'Peraturan Badan Pusat Statistik Nomor 5 Tahun 2023 tentang Organisasi dan Tata Kerja Badan Pusat Statistik Provinsi dan Badan Pusat Statistik Kabupaten/Kota;',
        ],

        'kecamatans' => [
            'Amurang',
            'Amurang Barat',
            'Amurang Timur',
            'Kumelembuai',
            'Maesaan',
            'Modoinding',
            'Motoling',
            'Motoling Barat',
            'Motoling Timur',
            'Ranoyapo',
            'Sinonsayang',
            'Suluun Tareran',
            'Tareran',
            'Tatapaan',
            'Tenga',
            'Tompaso Baru',
            'Tumpaan',
        ],
    ],

];
