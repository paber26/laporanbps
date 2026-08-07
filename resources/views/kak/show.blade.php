<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Preview KAK</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('kak.edit', $kak) }}" class="px-3 py-2 text-sm bg-amber-500 text-white rounded-md hover:bg-amber-600">Edit</a>
                <a href="{{ route('kak.index') }}" class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100">Kembali</a>
            </div>
        </div>
    </x-slot>

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

    <div class="py-8">
        <div class="w-full mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-green-100 dark:bg-green-900/40 border border-green-300 dark:border-green-700 text-green-800 dark:text-green-200 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif

            {{-- Panel cetak --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 flex flex-wrap items-center gap-3">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Cetak Dokumen:</span>
                <label class="text-sm text-gray-600 dark:text-gray-400">Ukuran Kertas
                    <select id="ukuran-kertas" class="ml-1 border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md text-sm">
                        <option value="a4">A4</option>
                        <option value="f4">F4 / Folio</option>
                    </select>
                </label>
                <a id="btn-word" href="{{ route('kak.word', $kak) }}?ukuran=a4" class="px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">Cetak Word (.docx)</a>
                <a id="btn-pdf" href="{{ route('kak.pdf', $kak) }}?ukuran=a4" target="_blank" class="px-3 py-2 text-sm bg-rose-600 text-white rounded-md hover:bg-rose-700">Cetak PDF</a>
                <a href="{{ route('kak.docx.download', $kak) }}" class="px-3 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">Unduh DOCX Hasil Edit</a>

                <form action="{{ route('kak.docx.upload', $kak) }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 ml-4 pl-4 border-l border-gray-300 dark:border-gray-600">
                    @csrf
                    <label class="text-sm text-gray-600 dark:text-gray-400">Unggah DOCX
                        <input type="file" name="file" accept=".docx" required
                               class="ml-1 text-sm text-gray-600 dark:text-gray-300 file:mr-2 file:px-3 file:py-1.5 file:rounded-md file:border-0 file:bg-gray-200 dark:file:bg-gray-700 file:text-gray-700 dark:file:text-gray-200 file:text-sm hover:file:bg-gray-300 dark:hover:file:bg-gray-600">
                    </label>
                    <button type="submit" class="px-3 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Unggah</button>
                </form>
            </div>

            {{-- Dokumen (mirip hasil cetak) --}}
            <div class="bg-white shadow-sm rounded-lg p-10 leading-relaxed text-[15px] text-gray-900 max-w-[21cm] mx-auto" style="font-family: 'Times New Roman', serif;">
                <div class="text-center"><img src="{{ asset('logo-small.png') }}" alt="Logo BPS" class="w-[90px] h-[70px] mx-auto"></div>
                <h1 class="text-center font-bold text-lg uppercase mt-2">{{ $kak->unit_kerja ?? 'Badan Pusat Statistik' }}</h1>

                <div class="text-center mt-6">
                    <div class="font-bold text-base">Kerangka Acuan Kerja (KAK)</div>
                    <div class="font-bold uppercase mt-2">{{ $kak->judul }}</div>
                    <div class="font-bold uppercase mt-1">{{ trim(($kak->unit_kerja ?? '') . ($kak->tahun ? ' T.A. ' . $kak->tahun : '')) }}</div>
                </div>

                @foreach ($bagian as $huruf => [$label, $kolom])
                    @if (trim((string) $kak->{$kolom}))
                        <div class="mt-6">
                            <h3 class="font-bold uppercase mb-2">{{ $huruf }}. {{ $label }}</h3>
                            <div class="text-justify prose max-w-none">{!! $kak->{$kolom} !!}</div>
                        </div>

                        {{-- Tabel rincian anggaran hanya di bagian G --}}
                        @if ($huruf === 'G' && $kak->anggarans->count())
                            <table class="w-full border border-collapse text-sm mt-4">
                                <thead>
                                    <tr>
                                        <th class="border p-2 text-left">Kode MAK</th>
                                        <th class="border p-2 text-left">Deskripsi</th>
                                        <th class="border p-2 text-center">Volume</th>
                                        <th class="border p-2 text-center">Satuan</th>
                                        <th class="border p-2 text-right">Harga Satuan (Rp)</th>
                                        <th class="border p-2 text-right">Jumlah Biaya (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($kak->anggarans as $a)
                                        <tr>
                                            <td class="border p-2">{{ $a->kode_mak }}</td>
                                            <td class="border p-2">{{ $a->deskripsi }}</td>
                                            <td class="border p-2 text-center">{{ $rupiah($a->volume) }}</td>
                                            <td class="border p-2 text-center">{{ $a->satuan }}</td>
                                            <td class="border p-2 text-right">{{ $rupiah($a->harga_satuan) }}</td>
                                            <td class="border p-2 text-right">{{ $rupiah($a->jumlah_biaya) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="font-bold">
                                        <td class="border p-2"></td>
                                        <td class="border p-2">Jumlah</td>
                                        <td class="border p-2"></td>
                                        <td class="border p-2"></td>
                                        <td class="border p-2"></td>
                                        <td class="border p-2 text-right">{{ $rupiah($kak->anggarans->sum('jumlah_biaya')) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        @endif
                    @endif
                @endforeach

                @if ($kak->ttd_kiri_nama || $kak->ttd_kiri_jabatan || $kak->ttd_kanan_nama || $kak->ttd_kanan_jabatan)
                    <div class="mt-12 text-center">
                        <div>{{ trim(($kak->tempat_penandatanganan ?? '') . ($kak->tempat_penandatanganan && $kak->tanggal_penandatanganan ? ', ' : ' ') . ($kak->tanggal_penandatanganan?->translatedFormat('j F Y') ?? '')) }}</div>
                        <div class="font-bold uppercase mt-1">{{ $kak->unit_kerja ?? '' }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-8 mt-8">
                        <div class="text-center">
                            <div>{{ $kak->ttd_kiri_jabatan }}</div>
                            <div class="h-20"></div>
                            <div class="font-bold underline">({{ $kak->ttd_kiri_nama }})</div>
                            @if ($kak->ttd_kiri_nip)
                                <div>NIP. {{ $kak->ttd_kiri_nip }}</div>
                            @endif
                        </div>
                        <div class="text-center">
                            <div>{{ $kak->ttd_kanan_jabatan }}</div>
                            <div class="h-20"></div>
                            <div class="font-bold underline">({{ $kak->ttd_kanan_nama }})</div>
                            @if ($kak->ttd_kanan_nip)
                                <div>NIP. {{ $kak->ttd_kanan_nip }}</div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function () {
            const sel = document.getElementById('ukuran-kertas');
            const btnWord = document.getElementById('btn-word');
            const btnPdf = document.getElementById('btn-pdf');
            const baseWord = "{{ route('kak.word', $kak) }}";
            const basePdf = "{{ route('kak.pdf', $kak) }}";
            sel.addEventListener('change', () => {
                btnWord.href = baseWord + '?ukuran=' + sel.value;
                btnPdf.href = basePdf + '?ukuran=' + sel.value;
            });
        })();
    </script>
</x-app-layout>
