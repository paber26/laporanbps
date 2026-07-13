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
        .ttd-mengetahui { width: 100%; margin-top: 24pt; }
        .ttd-mengetahui td { width: 50%; vertical-align: top; text-align: center; }
        /* Tinggi label kiri/kanan disamakan (jabatan bisa membungkus 1-3 baris
           tergantung panjang teks) agar baris nama tetap sejajar, tidak
           tergantung berapa baris label di atasnya. */
        .ttd-mengetahui .label { min-height: 52pt; }
        .ttd-mengetahui .space { height: 60pt; }
        .ttd-mengetahui .nama { text-decoration: underline; font-weight: bold; }
        .page-break { page-break-before: always; }
        .lampiran-title { text-align: center; font-weight: bold; text-transform: uppercase; margin: 0 0 14pt; }
        table.uraian { width: 100%; border-collapse: collapse; }
        table.uraian th, table.uraian td { border: 1px solid #000; padding: 5pt 6pt; vertical-align: top; text-align: left; font-size: 11pt; }
        table.uraian th { text-align: center; font-weight: bold; }
        /* Uraian panjang dipecah menjadi banyak baris kecil (~12 kata) agar teks
           bisa mengalir mengisi halaman — dompdf memindahkan satu <tr> secara utuh
           ke halaman berikutnya bila tak muat, jadi baris besar menyisakan banyak
           ruang kosong. Batas atas/bawah antar-baris dalam satu hari dihilangkan
           sehingga tetap tampil sebagai satu blok utuh; jarak antar-baris dibuat
           rapat & konsisten, dengan sedikit jarak ekstra hanya di awal paragraf
           baru agar paragraf tetap terlihat terpisah. */
        table.uraian tr.chunk td { padding-top: 1pt; padding-bottom: 1pt; }
        table.uraian tr.chunk.para-start td:last-child { padding-top: 7pt; }
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
                    $chunks = $u->uraian_chunks;
                    if (empty($chunks)) { $chunks = [['text' => '', 'new_paragraph' => false]]; }
                    $last = count($chunks) - 1;
                    $tgl = $u->tanggal_kegiatan?->translatedFormat('l') . '/' . $u->tanggal_kegiatan?->translatedFormat('j F Y');
                    $jam = trim(($u->jam_mulai ?? '') . (($u->jam_mulai && $u->jam_selesai) ? '-' : '') . ($u->jam_selesai ?? ''));
                @endphp
                @foreach ($chunks as $ci => $chunk)
                    <tr class="chunk {{ $chunk['new_paragraph'] ? 'para-start' : '' }} {{ $ci > 0 ? 'cont' : '' }} {{ $ci < $last ? 'open' : '' }}">
                        <td class="col-tgl">{{ $ci === 0 ? $tgl : '' }}</td>
                        <td class="col-jam">{{ $ci === 0 ? $jam : '' }}</td>
                        <td>{{ $chunk['text'] }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="3" class="center">Belum ada uraian kegiatan.</td></tr>
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
