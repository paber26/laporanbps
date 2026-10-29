<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan {{ $laporan->id }}</title>
    <style>
        @page { size: {{ $paperCss ?? '210mm 297mm' }}; margin: 2.5cm 2.5cm 2.5cm 3cm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        html, body { margin: 0; padding: 0; }
        body { font-family: "Times New Roman", "Liberation Serif", "Tinos", "Noto Serif", "FreeSerif", Times, serif; font-size: 12pt; color: #000; line-height: 1.5; }
        p { margin: 0 0 8pt; text-align: justify; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        h1.judul { font-size: 12pt; font-weight: bold; text-align: center; text-transform: uppercase; margin: 0 0 18pt; }

        /* Baris Perihal: label + nilai (hanging-indent) + tempat/tanggal nowrap. */
        .perihal-row { width: 100%; border-collapse: collapse; }
        .perihal-row td { vertical-align: top; padding: 0; }
        .perihal-row .p-label { white-space: nowrap; }
        .perihal-row .p-sep { white-space: nowrap; padding: 0 6pt 0 2pt; }
        .perihal-row .p-place { white-space: nowrap; text-align: right; padding-left: 18pt; }

        .list { margin: 0 0 8pt; padding: 0; list-style: none; }
        .list > li { margin-bottom: 2pt; }
        .sub { margin-left: 22pt; }

        .ttd { width: 100%; margin-top: 18pt; }
        .ttd td { vertical-align: top; }
        .ttd .space { height: 60pt; }
        .ttd-mengetahui { width: 100%; margin-top: 24pt; }
        .ttd-mengetahui td { width: 50%; vertical-align: top; text-align: center; }
        .ttd-mengetahui .label { min-height: 52pt; }
        .ttd-mengetahui .space { height: 60pt; }
        .ttd-mengetahui .nama { text-decoration: underline; font-weight: bold; }

        /* Mulai lampiran di halaman baru (break pada heading). */
        .lampiran-start { break-before: page; page-break-before: always; margin-top: 0; }
        .lampiran-title { text-align: center; font-weight: bold; text-transform: uppercase; margin: 0 0 14pt; }

        /* ===== Tabel Uraian ===== Satu sel per hari; Chrome memecah baris/sel
           mengalir antar-halaman (paragraf tetap menyambung, tanpa whitespace). */
        table.uraian { width: 100%; border-collapse: collapse; }
        table.uraian th, table.uraian td { border: 1px solid #000; padding: 6pt 7pt; vertical-align: top; text-align: left; font-size: 11pt; }
        table.uraian th { text-align: center; font-weight: bold; }
        table.uraian thead { display: table-header-group; } /* header berulang tiap halaman */
        table.uraian td.uraian-cell { text-align: justify; }
        table.uraian td.uraian-cell p { margin: 0 0 7pt; }
        table.uraian td.uraian-cell p:last-child { margin-bottom: 0; }
        .col-tgl { width: 22%; }
        .col-jam { width: 15%; }

        /* Gambar CKEditor dalam uraian: pusatkan seperti di editor. */
        table.uraian img { max-width: 100%; height: auto; }
        table.uraian figure { margin: 6pt 0; }
        table.uraian figure.image { display: table; margin: 6pt auto; max-width: 100%; }
        table.uraian figure.image img { display: block; width: 100%; height: auto; }
        table.uraian figure.image.image-style-align-left,
        table.uraian figure.image.image-style-block-align-left { margin-left: 0; margin-right: auto; }
        table.uraian figure.image.image-style-align-right,
        table.uraian figure.image.image-style-block-align-right { margin-left: auto; margin-right: 0; }
        table.uraian figure.image > figcaption { font-size: 9pt; color: #555; text-align: center; }

        /* ===== Grid Dokumentasi ===== kotak seragam, foto terpusat. */
        .foto-grid { width: 100%; border-collapse: collapse; }
        .foto-grid tr { break-inside: avoid; page-break-inside: avoid; }
        .foto-grid td { width: 50%; height: 175pt; text-align: center; padding: 8pt 10pt; vertical-align: middle; }
        .foto-grid img { max-width: 100%; max-height: 145pt; width: auto; height: auto; border: 1px solid #999; }
        .foto-cap { font-size: 9.5pt; margin-top: 5pt; color: #333; font-style: italic; line-height: 1.3; }
    </style>
</head>
<body>

    {{-- ================= HALAMAN 1: SURAT PENGANTAR ================= --}}
    <h1 class="judul">{{ $laporan->judul_laporan }}</h1>

    <table class="perihal-row">
        <tr>
            <td class="p-label">Perihal</td>
            <td class="p-sep">:</td>
            <td>{{ $laporan->perihal_laporan }}</td>
            <td class="p-place">({{ $laporan->tempat_laporan }}, {{ $laporan->tanggal_laporan?->translatedFormat('j F Y') }})</td>
        </tr>
    </table>

    <p style="margin-top:14pt;">
        Kepada Yang Terhormat:<br>
        <span class="bold">{{ $laporan->tujuan_surat }}</span><br>
        di<br>
        Tempat
    </p>

    <p>Bersama ini disampaikan laporan perjalanan dinas dalam rangka {{ $laporan->perihal_laporan }} sebagai berikut.</p>

    <ol class="list">
        <li>1.&nbsp; Nama : {{ $laporan->pegawai->nama }}</li>
        <li>2.&nbsp; NIP : {{ $laporan->pegawai->nip }}</li>
        <li>3.&nbsp; Unit Kerja : {{ $laporan->pegawai->unit_kerja }}</li>
        <li>4.&nbsp; Lokasi Tujuan Kegiatan : {{ $laporan->lokasi_tujuan }}</li>
        <li>5.&nbsp; Pembiayaan Kegiatan
            @if ($laporan->pembiayaan)
                <div class="sub">Program : {{ $laporan->pembiayaan->program }}</div>
                <div class="sub">Kegiatan : {{ $laporan->pembiayaan->kegiatan }}</div>
                <div class="sub">RO : {{ $laporan->pembiayaan->ro }}</div>
                <div class="sub">Komponen : {{ $laporan->pembiayaan->komponen }}</div>
                <div class="sub">Akun : {{ $laporan->pembiayaan->akun }}</div>
            @endif
        </li>
        <li>6.&nbsp; Uraian kegiatan perjalanan dinas dapat dilihat pada Lampiran 1.</li>
        <li>7.&nbsp; Dokumentasi perjalanan dinas dapat dilihat pada Lampiran 2.</li>
    </ol>

    <p>Demikian laporan yang dapat disampaikan untuk dijadikan sebagai bahan evaluasi.</p>

    @php
        $ttd = null;
        $ttdPath = $laporan->pegawai->tanda_tangan_path;
        if ($ttdPath) {
            $ttd = \App\Support\PdfImage::dataUri(\Storage::disk('public')->path($ttdPath), 600);
        }
    @endphp

    <table class="ttd">
        <tr>
            <td style="width:58%;">&nbsp;</td>
            <td class="center" style="width:42%;">
                Petugas
                @if ($ttd)
                    <div><img src="{{ $ttd }}" style="height:60pt; margin:4pt 0;" alt="ttd"></div>
                @else
                    <div class="space"></div>
                @endif
                ({{ $laporan->pegawai->nama }})
            </td>
        </tr>
    </table>

    {{-- ================= LAMPIRAN 1: URAIAN ================= --}}
    <p class="bold lampiran-start" style="margin-bottom:12pt;">Lampiran 1</p>
    <div class="lampiran-title">
        Uraian Kegiatan<br>
        {{ $laporan->perihal_laporan }}<br>
        {{ $laporan->pegawai->unit_kerja }}
    </div>

    <table class="uraian">
        <thead>
            <tr>
                <th class="col-tgl">Hari/Tanggal</th>
                <th class="col-jam">Jam</th>
                <th>Uraian Kegiatan</th>
            </tr>
            <tr>
                <th>(1)</th><th>(2)</th><th>(3)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($laporan->uraians as $u)
                @php
                    $tgl = $u->tanggal_kegiatan?->translatedFormat('l') . '/' . $u->tanggal_kegiatan?->translatedFormat('j F Y');
                    $jam = trim(($u->jam_mulai ?? '') . (($u->jam_mulai && $u->jam_selesai) ? '-' : '') . ($u->jam_selesai ?? ''));
                @endphp
                <tr>
                    <td class="col-tgl">{{ $tgl }}</td>
                    <td class="col-jam">{{ $jam }}</td>
                    <td class="uraian-cell">{!! $u->uraian_html_pdf ?: '&nbsp;' !!}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">Belum ada uraian kegiatan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="ttd-mengetahui">
        <tr>
            <td>
                <div class="label">
                    Mengetahui,<br>
                    {{ config('laporan.mengetahui.jabatan') }}
                </div>
                <div class="space"></div>
                <div class="nama">{{ config('laporan.mengetahui.nama') }}</div>
            </td>
            <td>
                <div class="label">
                    &nbsp;<br>
                    {{ $laporan->tempat_laporan }}, {{ $laporan->tanggal_laporan?->translatedFormat('j F Y') }}<br>
                    Pelaku Perjalanan Dinas
                </div>
                @if ($ttd)
                    <div><img src="{{ $ttd }}" style="height:60pt; margin:4pt 0;" alt="ttd"></div>
                @else
                    <div class="space"></div>
                @endif
                <div class="nama">{{ $laporan->pegawai->nama }}</div>
            </td>
        </tr>
    </table>

    {{-- ================= LAMPIRAN 2: DOKUMENTASI ================= --}}
    <p class="bold lampiran-start" style="margin-bottom:12pt;">Lampiran 2</p>
    <div class="lampiran-title">
        Dokumentasi Kegiatan<br>
        {{ $laporan->perihal_laporan }}
    </div>

    @if ($laporan->dokumentasis->count())
        <table class="foto-grid">
            @foreach ($laporan->dokumentasis->chunk(2) as $chunk)
                <tr>
                    @if ($chunk->count() == 1)
                        @php
                            $dok = $chunk->first();
                            $img = \App\Support\PdfImage::dataUri($dok->absolute_path);
                        @endphp
                        <td colspan="2" align="center">
                            <div style="width: 50%; margin: 0 auto;">
                                @if ($img)<img src="{{ $img }}" alt="dokumentasi">@endif
                                @if ($dok->keterangan)<div class="foto-cap">{{ $dok->keterangan }}</div>@endif
                            </div>
                        </td>
                    @else
                        @foreach ($chunk as $dok)
                            @php
                                $img = \App\Support\PdfImage::dataUri($dok->absolute_path);
                            @endphp
                            <td>
                                @if ($img)<img src="{{ $img }}" alt="dokumentasi">@endif
                                @if ($dok->keterangan)<div class="foto-cap">{{ $dok->keterangan }}</div>@endif
                            </td>
                        @endforeach
                    @endif
                </tr>
            @endforeach
        </table>
    @else
        <p>Tidak ada dokumentasi.</p>
    @endif

</body>
</html>
