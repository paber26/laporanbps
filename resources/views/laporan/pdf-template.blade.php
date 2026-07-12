<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan {{ $laporan->id }}</title>
    <style>
        @page { margin: 2.5cm 2.5cm 2.5cm 3cm; }
        * { box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12pt; color: #000; line-height: 1.4; }
        p { margin: 0 0 8pt; text-align: justify; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        h1.judul { font-size: 12pt; font-weight: bold; text-align: center; text-transform: uppercase; margin: 0 0 18pt; }
        .perihal-row { width: 100%; }
        .perihal-row td { vertical-align: top; padding: 0; }
        .list { margin: 0 0 8pt; padding: 0; list-style: none; }
        .list > li { margin-bottom: 2pt; }
        .sub { margin-left: 22pt; }
        .ttd { width: 100%; margin-top: 18pt; }
        .ttd td { vertical-align: top; }
        .ttd .space { height: 60pt; }
        .page-break { page-break-before: always; }
        .lampiran-title { text-align: center; font-weight: bold; text-transform: uppercase; margin: 0 0 14pt; }
        table.uraian { width: 100%; border-collapse: collapse; }
        table.uraian th, table.uraian td { border: 1px solid #000; padding: 5pt 6pt; vertical-align: top; text-align: left; font-size: 11pt; }
        table.uraian th { text-align: center; font-weight: bold; }
        table.uraian td p { margin: 0 0 6pt; }
        table.uraian td p:last-child { margin-bottom: 0; }
        /* Uraian panjang dipecah per paragraf menjadi beberapa baris agar teks
           mengalir mengisi halaman. Batas atas/bawah antar-baris dalam satu hari
           dihilangkan sehingga tetap tampil sebagai satu blok utuh. */
        table.uraian tr.cont td { border-top: none; }
        table.uraian tr.open td { border-bottom: none; }
        .col-tgl { width: 22%; }
        .col-jam { width: 15%; }
        .foto-grid { width: 100%; border-collapse: collapse; }
        .foto-grid td { width: 50%; text-align: center; padding: 8pt; vertical-align: top; }
        .foto-grid img { max-width: 100%; max-height: 220pt; border: 1px solid #333; }
        .foto-cap { font-size: 10pt; margin-top: 4pt; }
    </style>
</head>
<body>

    {{-- ================= HALAMAN 1: SURAT PENGANTAR ================= --}}
    <h1 class="judul">{{ $laporan->judul_laporan }}</h1>

    <table class="perihal-row">
        <tr>
            <td style="width:62%;">Perihal : {{ $laporan->perihal_laporan }}</td>
            <td class="right" style="width:38%;">({{ $laporan->tempat_laporan }}, {{ $laporan->tanggal_laporan?->translatedFormat('j F Y') }})</td>
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

    <table class="ttd">
        <tr>
            <td style="width:58%;">&nbsp;</td>
            <td class="center" style="width:42%;">
                Petugas
                @php
                    $ttd = null;
                    $ttdPath = $laporan->pegawai->tanda_tangan_path;
                    if ($ttdPath) {
                        $ttd = \App\Support\PdfImage::dataUri(\Storage::disk('public')->path($ttdPath), 600);
                    }
                @endphp
                @if ($ttd)
                    <div><img src="{{ $ttd }}" style="height:60pt; margin:4pt 0;" alt="ttd"></div>
                @else
                    <div class="space"></div>
                @endif
                ({{ $laporan->pegawai->nama }})
            </td>
        </tr>
    </table>

    {{-- ================= HALAMAN 2: LAMPIRAN 1 ================= --}}
    <div class="page-break"></div>
    <p class="bold" style="margin-bottom:12pt;">Lampiran 1</p>
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
                    $paras = $u->uraian_paragraphs;
                    if (empty($paras)) { $paras = ['']; }
                    $last = count($paras) - 1;
                    $tgl = $u->tanggal_kegiatan?->translatedFormat('l') . '/' . $u->tanggal_kegiatan?->translatedFormat('j F Y');
                    $jam = trim(($u->jam_mulai ?? '') . (($u->jam_mulai && $u->jam_selesai) ? '-' : '') . ($u->jam_selesai ?? ''));
                @endphp
                @foreach ($paras as $pi => $para)
                    <tr class="{{ $pi > 0 ? 'cont' : '' }} {{ $pi < $last ? 'open' : '' }}">
                        <td class="col-tgl">{{ $pi === 0 ? $tgl : '' }}</td>
                        <td class="col-jam">{{ $pi === 0 ? $jam : '' }}</td>
                        <td>{!! $para !!}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="3" class="center">Belum ada uraian kegiatan.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ================= HALAMAN 3: LAMPIRAN 2 ================= --}}
    <div class="page-break"></div>
    <p class="bold" style="margin-bottom:12pt;">Lampiran 2</p>
    <div class="lampiran-title">
        Dokumentasi Kegiatan<br>
        {{ $laporan->perihal_laporan }}
    </div>

    @if ($laporan->dokumentasis->count())
        <table class="foto-grid">
            @foreach ($laporan->dokumentasis->chunk(2) as $chunk)
                <tr>
                    @foreach ($chunk as $dok)
                        @php
                            $img = \App\Support\PdfImage::dataUri($dok->absolute_path);
                        @endphp
                        <td>
                            @if ($img)<img src="{{ $img }}" alt="dokumentasi">@endif
                            @if ($dok->keterangan)<div class="foto-cap">{{ $dok->keterangan }}</div>@endif
                        </td>
                    @endforeach
                    @if ($chunk->count() < 2)<td>&nbsp;</td>@endif
                </tr>
            @endforeach
        </table>
    @else
        <p>Tidak ada dokumentasi.</p>
    @endif

</body>
</html>
