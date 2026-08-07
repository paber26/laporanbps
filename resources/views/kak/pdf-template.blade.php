<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>KAK — {{ $kak->judul }}</title>
    <style>
        * { font-family: 'Times New Roman', serif; }
        body { font-size: 12pt; color: #000; line-height: 1.5; }
        .kop { text-align: center; font-weight: bold; font-size: 14pt; text-transform: uppercase; }
        .logo { text-align: center; margin-bottom: 4px; }
        .logo img { width: 90px; height: 70px; }
        .judul-wrap { text-align: center; margin-top: 24px; }
        .judul-kak { font-size: 13pt; font-weight: bold; text-transform: uppercase; }
        .bagian-judul { font-weight: bold; text-transform: uppercase; margin: 18px 0 8px; }
        .bagian-body { text-align: justify; }
        table { border-collapse: collapse; width: 100%; }
        table, th, td { border: 1px solid #000; }
        th, td { padding: 6px 8px; vertical-align: top; font-size: 11pt; }
        th { text-align: center; }
        td.r { text-align: right; }
        td.c { text-align: center; }
        .ttd-kop { text-align: center; margin-top: 60px; }
        .ttd-col { width: 100%; }
        .ttd-nama { font-weight: bold; text-decoration: underline; }
        p { margin: 0 0 8px; }
    </style>
    @php
        $logoUri = \App\Support\PdfImage::dataUri(public_path('logo-small.png'), 300);
    @endphp
</head>
<body>
    @if ($logoUri)
        <div class="logo"><img src="{{ $logoUri }}" alt="Logo BPS"></div>
    @endif
    <div class="kop">{{ $kak->unit_kerja ?? 'Badan Pusat Statistik' }}</div>

    <div class="judul-wrap">
        <div>KERANGKA ACUAN KERJA (KAK)</div>
        <div class="judul-kak">{{ $kak->judul }}</div>
        <div>{{ strtoupper(trim(($kak->unit_kerja ?? '') . ($kak->tahun ? ' T.A. ' . $kak->tahun : ''))) }}</div>
    </div>

    @php
        $bagian = [
            'A' => ['Dasar Hukum', 'dasar_hukum'],
            'B' => ['Gambaran Umum', 'gambaran_umum'],
            'C' => ['Maksud dan Tujuan', 'maksud_tujuan'],
            'D' => ['Keluaran/Output', 'keluaran'],
            'E' => ['Pelaksana Kegiatan', 'pelaksana_kegiatan'],
            'F' => ['Jadwal dan Lokasi Kegiatan', 'jadwal_lokasi'],
            'G' => ['Sumber Pembiayaan', 'sumber_pembiayaan'],
            'H' => ['Penutup', 'penutup'],
        ];
        $rupiah = fn ($v) => number_format((float) $v, 0, ',', '.');
    @endphp

    @foreach ($bagian as $huruf => [$label, $kolom])
        @if (trim((string) $kak->{$kolom}))
            <div class="bagian-judul">{{ $huruf }}. {{ strtoupper($label) }}</div>
            <div class="bagian-body">{!! $kak->{$kolom} !!}</div>

            @if ($huruf === 'G' && $kak->anggarans->count())
                <table>
                    <thead>
                        <tr>
                            <th>Kode MAK</th>
                            <th>Deskripsi</th>
                            <th>Volume</th>
                            <th>Satuan</th>
                            <th>Harga Satuan (Rp)</th>
                            <th>Jumlah Biaya (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kak->anggarans as $a)
                            <tr>
                                <td>{{ $a->kode_mak }}</td>
                                <td>{{ $a->deskripsi }}</td>
                                <td class="c">{{ $rupiah($a->volume) }}</td>
                                <td class="c">{{ $a->satuan }}</td>
                                <td class="r">{{ $rupiah($a->harga_satuan) }}</td>
                                <td class="r">{{ $rupiah($a->jumlah_biaya) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td></td>
                            <td><b>Jumlah</b></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="r"><b>{{ $rupiah($kak->anggarans->sum('jumlah_biaya')) }}</b></td>
                        </tr>
                    </tbody>
                </table>
            @endif
        @endif
    @endforeach

    @if ($kak->ttd_kiri_nama || $kak->ttd_kiri_jabatan || $kak->ttd_kanan_nama || $kak->ttd_kanan_jabatan)
        <div class="ttd-kop">
            <div>{{ trim(($kak->tempat_penandatanganan ?? '') . ($kak->tempat_penandatanganan && $kak->tanggal_penandatanganan ? ', ' : ' ') . ($kak->tanggal_penandatanganan?->translatedFormat('j F Y') ?? '')) }}</div>
            <div><b>{{ strtoupper($kak->unit_kerja ?? '') }}</b></div>
        </div>
        <table class="ttd-col" style="border: none; margin-top: 40px;">
            <tr>
                <td style="border: none; text-align: center; width: 50%;">
                    <div>{{ $kak->ttd_kiri_jabatan }}</div>
                    <br><br><br>
                    <div class="ttd-nama">({{ $kak->ttd_kiri_nama }})</div>
                    @if ($kak->ttd_kiri_nip)
                        <div>NIP. {{ $kak->ttd_kiri_nip }}</div>
                    @endif
                </td>
                <td style="border: none; text-align: center; width: 50%;">
                    <div>{{ $kak->ttd_kanan_jabatan }}</div>
                    <br><br><br>
                    <div class="ttd-nama">({{ $kak->ttd_kanan_nama }})</div>
                    @if ($kak->ttd_kanan_nip)
                        <div>NIP. {{ $kak->ttd_kanan_nip }}</div>
                    @endif
                </td>
            </tr>
        </table>
    @endif
</body>
</html>
