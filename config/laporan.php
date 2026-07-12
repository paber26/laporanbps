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

];
