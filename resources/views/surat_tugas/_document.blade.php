@php
    $forPdf = $forPdf ?? false;

    $logoSrc = $forPdf
        ? \App\Support\PdfImage::dataUri(public_path('images/logo-bps-st.png'), 300)
        : (is_file(public_path('images/logo-bps-st.png')) ? asset('images/logo-bps-st.png') : null);

    $ttdPath = storage_path('app/'.config('laporan.surat_tugas.kepala_bps.ttd_scan_path', 'assets/ttd-kepala-bps.png'));
    $ttdKepalaSrc = ($suratTugas->sertakan_ttd_kepala && is_file($ttdPath))
        ? \App\Support\PdfImage::dataUri($ttdPath, 400)
        : null;

    $nama = $suratTugas->pegawai?->nama ?? '';
    $nip = trim((string) ($suratTugas->pegawai?->nip ?? ''));
    $jabatan = $suratTugas->jabatan ?: ($suratTugas->pegawai?->jabatan ?? '');
    $pangkat = $suratTugas->pangkat_golongan ?: ($suratTugas->pegawai?->pangkat_golongan ?? '-');
    $unitKerja = $suratTugas->unit_kerja ?: ($suratTugas->pegawai?->unit_kerja ?? config('laporan.surat_tugas.instansi'));
    $asalLabel = \App\Models\SuratTugas::formatKecamatan($suratTugas->kecamatan_asal ?: config('laporan.surat_tugas.tempat_kedudukan'));
    $rutes = $suratTugas->rutes->values();
    $firstRute = $rutes->get(0);
    $visumCount = max(2, $rutes->count());
    $mengingats = $suratTugas->mengingatList();
    $tembusans = $suratTugas->tembusanList();

    $kepalaJab1 = $suratTugas->kepala_jabatan;
    $kepalaJab2 = '';
    if (preg_match('/^(.*?BPS)\s+(.+)$/i', trim($suratTugas->kepala_jabatan), $m)) {
        $kepalaJab1 = $m[1];
        $kepalaJab2 = $m[2];
    }
@endphp

<div class="st-doc-root {{ $forPdf ? '' : 'st-preview-wrap' }}">

    {{-- ==================== HALAMAN 1: SURAT TUGAS ==================== --}}
    <div class="{{ $forPdf ? '' : 'st-sheet-block' }}">
        @if (! $forPdf)
            <div class="st-sheet-label">Halaman 1 — Surat Tugas</div>
        @endif
        <div class="st-sheet">
            <div class="st-kop">
                @if ($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logo BPS" class="st-kop-logo">
                @endif
                <div class="st-kop-title">BADAN PUSAT STATISTIK</div>
                <div class="st-kop-title">KABUPATEN MINAHASA SELATAN</div>
            </div>

            <div class="st-heading">
                <div>SURAT TUGAS</div>
                <div>NOMOR : {{ $suratTugas->nomor_st }}</div>
            </div>

            <table class="st-main-tbl">
                <colgroup>
                    <col style="width: 15.65%;">
                    <col style="width: 3.50%;">
                    <col style="width: 21.73%;">
                    <col style="width: 2.63%;">
                    <col style="width: 56.49%;">
                </colgroup>
                <tbody>
                    <tr>
                        <td>Menimbang</td>
                        <td class="st-sep">:</td>
                        <td colspan="3" class="st-justify">{{ $suratTugas->menimbang }}</td>
                    </tr>
                    <tr class="st-gap-top">
                        <td>Mengingat</td>
                        <td class="st-sep">:</td>
                        <td colspan="3">
                            <table class="st-mengingat-tbl">
                                @foreach ($mengingats as $idx => $item)
                                    <tr>
                                        <td class="m-no">{{ $idx + 1 }}.</td>
                                        <td class="m-txt">{{ preg_replace('/^\d+\.\s*/', '', $item) }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    <tr class="st-gap-top">
                        <td>Kepada</td>
                        <td class="st-sep">:</td>
                        <td>Nama</td>
                        <td class="st-sep">:</td>
                        <td>{{ $nama }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td>Jabatan</td>
                        <td class="st-sep">:</td>
                        <td>{{ $jabatan }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>{{ $unitKerja }}</td>
                    </tr>
                    <tr class="st-gap-top">
                        <td>Untuk</td>
                        <td class="st-sep">:</td>
                        <td colspan="3" class="st-justify">{{ $suratTugas->tujuan_kegiatan }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td>Waktu Pelaksanaan</td>
                        <td class="st-sep">:</td>
                        <td>{{ $suratTugas->waktu_pelaksanaan }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td>Mata Anggaran</td>
                        <td class="st-sep">:</td>
                        <td>{{ $suratTugas->mata_anggaran ?: '-' }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="st-ttd-wrap">
                <div class="st-ttd-box">
                    <div>{{ $suratTugas->tempat_st }}, {{ \App\Models\SuratTugas::formatTanggal($suratTugas->tanggal_st) }}</div>
                    <div>{{ $suratTugas->kepala_jabatan }}</div>
                    <div class="st-ttd-space">
                        @if ($ttdKepalaSrc)
                            <img src="{{ $ttdKepalaSrc }}" alt="TTD Kepala BPS">
                        @endif
                    </div>
                    <div>{{ $suratTugas->kepala_nama }}</div>
                    <div>NIP. {{ $suratTugas->kepala_nip }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== HALAMAN 2: SPD ==================== --}}
    <div class="{{ $forPdf ? 'st-sheet-page-break' : 'st-sheet-block' }}">
        @if (! $forPdf)
            <div class="st-sheet-label">Halaman 2 — Surat Perjalanan Dinas (SPD)</div>
        @endif
        <div class="st-sheet">
            <table class="spd-header-tbl">
                <colgroup>
                    <col style="width: 46%;">
                    <col style="width: 11%;">
                    <col style="width: 43%;">
                </colgroup>
                <tr>
                    <td></td>
                    <td>Nomor</td>
                    <td>: {{ $suratTugas->nomor_spd ?: '-' }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td>Lembar</td>
                    <td>:</td>
                </tr>
                <tr>
                    <td colspan="3">{{ $suratTugas->instansi_anggaran ?: config('laporan.surat_tugas.instansi_panjang') }}</td>
                </tr>
            </table>

            <table class="spd-main-tbl">
                <colgroup>
                    <col style="width: 4.83%;">
                    <col style="width: 3.66%;">
                    <col style="width: 23.18%;">
                    <col style="width: 7.22%;">
                    <col style="width: 11.76%;">
                    <col style="width: 9.18%;">
                    <col style="width: 5.27%;">
                    <col style="width: 34.90%;">
                </colgroup>
                <tbody>
                    {{-- 1. Pejabat Pembuat Komitmen --}}
                    <tr>
                        <td class="c-no bt-thick bb-med">1.</td>
                        <td colspan="4" class="bt-thick bb-med br-thick">Pejabat Pembuat Komitmen</td>
                        <td colspan="3" class="bt-thick bb-med bl-thick">{{ $suratTugas->ppk_nama }}</td>
                    </tr>
                    {{-- 2. Nama / NIP pegawai --}}
                    <tr>
                        <td class="c-no bt-med bb-med">2.</td>
                        <td colspan="4" class="bt-med bb-med br-thick">Nama / NIP pegawai yang melaksanakan perjalanan dinas</td>
                        <td colspan="3" class="bt-med bb-med bl-thick">
                            <div>{{ $nama }}</div>
                            @if ($nip !== '')
                                <div>NIP. {{ $nip }}</div>
                            @endif
                        </td>
                    </tr>
                    {{-- 3a, 3b, 3c --}}
                    <tr>
                        <td class="c-no bt-med bb-none">3.</td>
                        <td class="bt-med bb-none br-none">a.</td>
                        <td colspan="3" class="bt-med bb-none bl-none br-thick">Pangkat dan golongan</td>
                        <td colspan="3" class="bt-med bb-none bl-thick">{{ $pangkat }}</td>
                    </tr>
                    <tr>
                        <td class="bt-none bb-none"></td>
                        <td class="bt-none bb-none br-none">b.</td>
                        <td colspan="3" class="bt-none bb-none bl-none br-thick">Jabatan/ instansi</td>
                        <td colspan="3" class="bt-none bb-none bl-thick">{{ trim($jabatan.'/'.$unitKerja, '/') }}</td>
                    </tr>
                    <tr>
                        <td class="bt-none bb-med"></td>
                        <td class="bt-none bb-med br-none">c.</td>
                        <td colspan="3" class="bt-none bb-med bl-none br-thick">Tingkat Biaya Perjalanan Dinas</td>
                        <td colspan="3" class="bt-none bb-med bl-thick">{{ $suratTugas->tingkat_biaya ?: 'C' }}</td>
                    </tr>
                    {{-- 4. Maksud perjalanan dinas --}}
                    <tr>
                        <td class="c-no bt-med bb-med">4.</td>
                        <td colspan="4" class="bt-med bb-med br-thick">Maksud perjalanan dinas</td>
                        <td colspan="3" class="bt-med bb-med bl-thick st-justify">{{ $suratTugas->tujuan_kegiatan }}</td>
                    </tr>
                    {{-- 5. Alat Angkutan --}}
                    <tr>
                        <td class="c-no bt-med bb-med">5.</td>
                        <td colspan="4" class="bt-med bb-med br-thick">Alat Angkutan yang dipergunakan</td>
                        <td colspan="3" class="bt-med bb-med bl-thick">{{ $suratTugas->alat_angkutan ?: 'Lainnya' }}</td>
                    </tr>
                    {{-- 6a, 6b --}}
                    <tr>
                        <td class="c-no bt-med bb-none">6.</td>
                        <td class="bt-med bb-none br-none">a.</td>
                        <td colspan="3" class="bt-med bb-none bl-none br-thick">Tempat keberangkatan</td>
                        <td colspan="3" class="bt-med bb-none bl-thick">{{ $suratTugas->tempat_berangkat }}</td>
                    </tr>
                    <tr>
                        <td class="bt-none bb-med"></td>
                        <td class="bt-none bb-med br-none">b.</td>
                        <td colspan="3" class="bt-none bb-med bl-none br-thick">Tempat tujuan</td>
                        <td colspan="3" class="bt-none bb-med bl-thick">{{ $suratTugas->tempat_tujuan }}</td>
                    </tr>
                    {{-- 7a, 7b, 7c --}}
                    <tr>
                        <td class="c-no bt-med bb-none">7.</td>
                        <td class="bt-med bb-none br-none">a.</td>
                        <td colspan="3" class="bt-med bb-none bl-none br-thick">Lamanya perjalanan Dinas</td>
                        <td colspan="3" class="bt-med bb-none bl-thick">{{ $suratTugas->jumlah_hari }} hari</td>
                    </tr>
                    <tr>
                        <td class="bt-none bb-none"></td>
                        <td class="bt-none bb-none br-none">b.</td>
                        <td colspan="3" class="bt-none bb-none bl-none br-thick">Tanggal Berangkat</td>
                        <td colspan="3" class="bt-none bb-none bl-thick">{{ \App\Models\SuratTugas::formatTanggal($suratTugas->tanggal_berangkat) }}</td>
                    </tr>
                    <tr>
                        <td class="bt-none bb-med"></td>
                        <td class="bt-none bb-med br-none">c.</td>
                        <td colspan="3" class="bt-none bb-med bl-none br-thick">Tanggal harus kembali</td>
                        <td colspan="3" class="bt-none bb-med bl-thick">{{ \App\Models\SuratTugas::formatTanggal($suratTugas->tanggal_kembali) }}</td>
                    </tr>
                    {{-- 8. Pengikut --}}
                    <tr>
                        <td class="c-no bt-med bb-med">8.</td>
                        <td colspan="2" class="bt-med bb-med br-none">Pengikut:</td>
                        <td colspan="2" class="bt-med bb-med bl-none br-thick">
                            <div>Nama</div>
                            @if ($suratTugas->pengikut)
                                <div style="margin-top: 1mm;">{!! nl2br(e($suratTugas->pengikut)) !!}</div>
                            @endif
                        </td>
                        <td class="bt-med bb-med bl-thick br-thick">Umur</td>
                        <td colspan="2" class="bt-med bb-med bl-thick">Hubungan keluarga/keterangan</td>
                    </tr>
                    {{-- 9. Pembebanan anggaran (persis 5 baris template asli: Program/Kegiatan/Komponen di sisi KIRI garis tengah!) --}}
                    <tr>
                        <td class="c-no bt-med bb-none">9.</td>
                        <td colspan="3" class="bt-med bb-none br-none">Pembebanan anggaran</td>
                        <td class="bt-med bb-none bl-none br-thick">Program</td>
                        <td class="bt-med bb-none bl-thick br-none">{{ $suratTugas->program_kode }}</td>
                        <td colspan="2" class="bt-med bb-none bl-none">{{ $suratTugas->program_nama }}</td>
                    </tr>
                    <tr>
                        <td class="bt-none bb-none"></td>
                        <td colspan="3" class="bt-none bb-none br-none"></td>
                        <td class="bt-none bb-none bl-none br-thick">Kegiatan</td>
                        <td class="bt-none bb-none bl-thick br-none">{{ $suratTugas->kegiatan_kode }}</td>
                        <td colspan="2" class="bt-none bb-none bl-none">{{ $suratTugas->kegiatan_nama }}</td>
                    </tr>
                    <tr>
                        <td class="bt-none bb-none"></td>
                        <td colspan="3" class="bt-none bb-none br-none"></td>
                        <td class="bt-none bb-none bl-none br-thick">Komponen</td>
                        <td class="bt-none bb-none bl-thick br-none">{{ $suratTugas->komponen_kode }}</td>
                        <td colspan="2" class="bt-none bb-none bl-none">{{ $suratTugas->komponen_nama }}</td>
                    </tr>
                    <tr>
                        <td class="bt-none bb-none"></td>
                        <td class="bt-none bb-none br-none">a.</td>
                        <td colspan="3" class="bt-none bb-none bl-none br-thick">Instansi</td>
                        <td colspan="3" class="bt-none bb-none bl-thick">{{ $suratTugas->instansi_anggaran ?: config('laporan.surat_tugas.instansi_panjang') }}</td>
                    </tr>
                    <tr>
                        <td class="bt-none bb-med"></td>
                        <td class="bt-none bb-med br-none">b.</td>
                        <td colspan="3" class="bt-none bb-med bl-none br-thick">Mata anggaran</td>
                        <td colspan="3" class="bt-none bb-med bl-thick">{{ $suratTugas->mata_anggaran ?: '-' }}</td>
                    </tr>
                    {{-- 10. Keterangan lain-lain --}}
                    <tr>
                        <td class="c-no bt-med bb-thick">10.</td>
                        <td colspan="4" class="bt-med bb-thick br-thick">Keterangan lain-lain</td>
                        <td colspan="3" class="bt-med bb-thick bl-thick">{!! nl2br(e($suratTugas->keterangan_lain ?? '')) !!}</td>
                    </tr>
                </tbody>
            </table>

            <div class="spd-sign-section">
                <table class="spd-keluar-tbl">
                    <colgroup>
                        <col style="width: 51%;">
                        <col style="width: 19%;">
                        <col style="width: 30%;">
                    </colgroup>
                    <tr>
                        <td></td>
                        <td>Dikeluarkan di</td>
                        <td>: {{ $suratTugas->tempat_st }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>Pada Tanggal</td>
                        <td>: {{ \App\Models\SuratTugas::formatTanggal($suratTugas->tanggal_st) }}</td>
                    </tr>
                </table>

                <div class="spd-ppk-wrap">
                    <div>{{ $suratTugas->ppk_jabatan }}</div>
                    @if ($suratTugas->ppk_sub_jabatan)
                        <div>{{ $suratTugas->ppk_sub_jabatan }}</div>
                    @endif
                    <div class="spd-ppk-space"></div>
                    <div class="spd-ppk-name">{{ $suratTugas->ppk_nama }}</div>
                    <div>NIP. {{ $suratTugas->ppk_nip }}</div>
                </div>

                <div class="spd-tembusan">
                    <div>Tembusan disampaikan kepada:</div>
                    @if (count($tembusans) > 0)
                        @foreach ($tembusans as $idx => $t)
                            <div>{{ $idx + 1 }}. {{ preg_replace('/^\d+\.\s*/', '', $t) }}</div>
                        @endforeach
                    @else
                        <div>1.</div>
                        <div>2.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== HALAMAN 3: VISUM SPD ==================== --}}
    <div class="{{ $forPdf ? 'st-sheet-page-break' : 'st-sheet-block' }}">
        @if (! $forPdf)
            <div class="st-sheet-label">Halaman 3 — Visum SPD</div>
        @endif
        <div class="st-sheet">
            <table class="visum-tbl">
                <colgroup>
                    <col style="width: 17.67%;">
                    <col style="width: 4.19%;">
                    <col style="width: 29.50%;">
                    <col style="width: 16.92%;">
                    <col style="width: 2.23%;">
                    <col style="width: 29.49%;">
                </colgroup>
                <tbody>
                    {{-- BLOK I: Berangkat dari Tempat Kedudukan --}}
                    <tr>
                        <td class="v-bt v-bl"></td>
                        <td class="v-bt"></td>
                        <td class="v-bt"></td>
                        <td class="v-bt v-bl">Berangkat dari</td>
                        <td class="v-bt">:</td>
                        <td class="v-bt v-br">{{ $asalLabel }}</td>
                    </tr>
                    <tr>
                        <td class="v-bl"></td>
                        <td></td>
                        <td></td>
                        <td class="v-bl"></td>
                        <td></td>
                        <td class="v-br">(Tempat Kedudukan)</td>
                    </tr>
                    <tr>
                        <td class="v-bl"></td>
                        <td></td>
                        <td></td>
                        <td class="v-bl">Pada Tanggal</td>
                        <td>:</td>
                        <td class="v-br">{{ \App\Models\SuratTugas::formatTanggal($suratTugas->tanggal_berangkat) }}</td>
                    </tr>
                    <tr>
                        <td class="v-bl"></td>
                        <td></td>
                        <td></td>
                        <td class="v-bl">Ke</td>
                        <td>:</td>
                        <td class="v-br">{{ $firstRute ? \App\Models\SuratTugas::formatKecamatan($firstRute->kecamatan_tujuan) : $suratTugas->tempat_tujuan }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="v-bl"></td>
                        <td colspan="3" class="v-bl v-br visum-sig-cell">
                            <div>{{ $kepalaJab1 }}</div>
                            @if ($kepalaJab2)
                                <div>{{ $kepalaJab2 }}</div>
                            @endif
                            <div class="visum-sig-gap"></div>
                            <div>{{ $suratTugas->kepala_nama }}</div>
                            <div>NIP. {{ $suratTugas->kepala_nip }}</div>
                        </td>
                    </tr>

                    {{-- BLOK II..N: Rute Tujuan (dikosongkan untuk diisi & disahkan di lapangan) --}}
                    @for ($i = 0; $i < $visumCount; $i++)
                        <tr>
                            <td class="v-bt v-bl">Tiba</td>
                            <td class="v-bt">:</td>
                            <td class="v-bt"></td>
                            <td class="v-bt v-bl">Berangkat dari</td>
                            <td class="v-bt">:</td>
                            <td class="v-bt v-br"></td>
                        </tr>
                        <tr>
                            <td class="v-bl">Pada tanggal</td>
                            <td>:</td>
                            <td></td>
                            <td class="v-bl">Ke</td>
                            <td>:</td>
                            <td class="v-br"></td>
                        </tr>
                        <tr>
                            <td class="v-bl"></td>
                            <td></td>
                            <td></td>
                            <td class="v-bl">Pada tanggal</td>
                            <td>:</td>
                            <td class="v-br"></td>
                        </tr>
                        <tr class="{{ $visumCount > 2 ? 'visum-blank-row-compact' : 'visum-blank-row' }}">
                            <td colspan="3" class="v-bl"></td>
                            <td colspan="3" class="v-bl v-br"></td>
                        </tr>
                    @endfor

                    {{-- BLOK AKHIR: Tiba Kembali di Tempat Kedudukan + Pengesahan PPK --}}
                    <tr>
                        <td colspan="3" class="v-bt v-bl" style="padding: 0;">
                            <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
                                <colgroup>
                                    <col style="width: 34.4%;">
                                    <col style="width: 8.2%;">
                                    <col style="width: 57.4%;">
                                </colgroup>
                                <tr>
                                    <td style="padding: 0.9mm 1.4mm;">Tiba</td>
                                    <td style="padding: 0.9mm 1.4mm;">:</td>
                                    <td style="padding: 0.9mm 1.4mm;">{{ $asalLabel }}<br>(Tempat Kedudukan)</td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.9mm 1.4mm;">Pada tanggal</td>
                                    <td style="padding: 0.9mm 1.4mm;">:</td>
                                    <td style="padding: 0.9mm 1.4mm;">{{ \App\Models\SuratTugas::formatTanggal($suratTugas->tanggal_kembali) }}</td>
                                </tr>
                            </table>
                        </td>
                        <td colspan="3" class="v-bt v-bl v-br st-justify" style="padding-top: 1.5mm;">
                            Telah diperiksa dengan keterangan bahwa perjalanan tersebut atas perintahnya dan semata-mata untuk kepentingan jabatan dalam waktu yang sesingkat-singkatnya
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="v-bl visum-sig-cell">
                            <div>Pejabat Pembuat Komitmen</div>
                            <div class="visum-sig-gap"></div>
                            <div class="visum-bold">{{ $suratTugas->ppk_nama }}</div>
                            <div>NIP. {{ $suratTugas->ppk_nip }}</div>
                        </td>
                        <td colspan="3" class="v-bl v-br visum-sig-cell">
                            <div>Pejabat Pembuat Komitmen</div>
                            <div class="visum-sig-gap"></div>
                            <div class="visum-bold">{{ $suratTugas->ppk_nama }}</div>
                            <div>NIP. {{ $suratTugas->ppk_nip }}</div>
                        </td>
                    </tr>

                    {{-- CATATAN LAIN-LAIN --}}
                    <tr>
                        <td colspan="6" class="v-bt v-bl v-br visum-note-cell">
                            <div>CATATAN LAIN - LAIN :</div>
                            @if ($suratTugas->keterangan_lain)
                                <div style="margin-top: 1mm;">{!! nl2br(e($suratTugas->keterangan_lain)) !!}</div>
                            @endif
                        </td>
                    </tr>

                    {{-- PERHATIAN --}}
                    <tr>
                        <td class="v-bl v-bb visum-perhatian-cell">PERHATIAN</td>
                        <td class="v-bb visum-perhatian-cell" style="text-align: right; padding-right: 1.5mm;">:</td>
                        <td colspan="4" class="v-bb v-br visum-perhatian-cell st-justify">
                            Pejabat yang berwenang menerbitkan SPD pegawai yang melakukan perjalanan dinas para pejabat yang mengesahkan tanggal berangkat/tiba serta bendaharawan bertanggung jawab berdasarkan peraturan-peraturan keuangan Negara apabila Negara menderita rugi akibat kesalahan, kelalaian dan kealpaannya.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
