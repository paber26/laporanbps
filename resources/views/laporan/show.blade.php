<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Preview Laporan</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('laporan.edit', $laporan) }}" class="px-3 py-2 text-sm bg-amber-500 text-white rounded-md hover:bg-amber-600">Edit</a>
                <a href="{{ route('laporan.index') }}" class="px-3 py-2 text-sm text-gray-600 hover:text-gray-800">Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif

            {{-- Panel cetak --}}
            <div class="bg-white shadow-sm rounded-lg p-4 flex flex-wrap items-center gap-3">
                <span class="text-sm font-medium text-gray-700">Cetak Dokumen:</span>
                <label class="text-sm text-gray-600">Ukuran Kertas
                    <select id="ukuran-kertas" class="ml-1 border-gray-300 rounded-md text-sm">
                        <option value="a4">A4</option>
                        <option value="f4">F4 / Folio</option>
                        <option value="legal">Legal</option>
                    </select>
                </label>
                <a id="btn-pdf" href="{{ route('laporan.pdf', $laporan) }}?ukuran=a4" target="_blank"
                   class="px-3 py-2 text-sm bg-rose-600 text-white rounded-md hover:bg-rose-700">Cetak PDF</a>
                <a href="{{ route('laporan.word', $laporan) }}"
                   class="px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">Cetak Word (.docx)</a>
            </div>

            {{-- Dokumen (mirip hasil cetak) --}}
            <div class="bg-white shadow-sm rounded-lg p-10 leading-relaxed text-[15px] text-gray-900 max-w-[21cm] mx-auto" style="font-family: 'Times New Roman', serif;">
                <h1 class="text-center font-bold uppercase mb-8">{{ $laporan->judul_laporan }}</h1>

                <div class="flex justify-between mb-6">
                    <div>Perihal : {{ $laporan->perihal_laporan }}</div>
                    <div>({{ $laporan->tempat_laporan }}, {{ $laporan->tanggal_laporan?->translatedFormat('j F Y') }})</div>
                </div>

                <div class="mb-6">
                    <div>Kepada Yang Terhormat:</div>
                    <div class="font-semibold">{{ $laporan->tujuan_surat }}</div>
                    <div>di</div>
                    <div>Tempat</div>
                </div>

                <p class="mb-4 text-justify">Bersama ini disampaikan laporan perjalanan dinas dalam rangka {{ $laporan->perihal_laporan }} sebagai berikut.</p>

                <ol class="space-y-1 mb-4" style="list-style: none;">
                    <li>1. Nama : {{ $laporan->pegawai->nama }}</li>
                    <li>2. NIP : {{ $laporan->pegawai->nip }}</li>
                    <li>3. Unit Kerja : {{ $laporan->pegawai->unit_kerja }}</li>
                    <li>4. Lokasi Tujuan Kegiatan : {{ $laporan->lokasi_tujuan }}</li>
                    <li>5. Pembiayaan Kegiatan
                        @if ($laporan->pembiayaan)
                            <div class="ml-6">
                                <div>Program : {{ $laporan->pembiayaan->program }}</div>
                                <div>Kegiatan : {{ $laporan->pembiayaan->kegiatan }}</div>
                                <div>RO : {{ $laporan->pembiayaan->ro }}</div>
                                <div>Komponen : {{ $laporan->pembiayaan->komponen }}</div>
                                <div>Akun : {{ $laporan->pembiayaan->akun }}</div>
                            </div>
                        @endif
                    </li>
                    <li>6. Uraian kegiatan perjalanan dinas dapat dilihat pada Lampiran 1.</li>
                    <li>7. Dokumentasi perjalanan dinas dapat dilihat pada Lampiran 2.</li>
                </ol>

                <p class="mb-10 text-justify">Demikian laporan yang dapat disampaikan untuk dijadikan sebagai bahan evaluasi.</p>

                <div class="flex justify-end">
                    <div class="text-center">
                        <div>Petugas</div>
                        @if ($laporan->pegawai->tanda_tangan_path)
                            <img src="{{ \Storage::url($laporan->pegawai->tanda_tangan_path) }}" class="h-20 mx-auto my-1" alt="ttd">
                        @else
                            <div class="h-16"></div>
                        @endif
                        <div>({{ $laporan->pegawai->nama }})</div>
                    </div>
                </div>

                {{-- Lampiran 1 --}}
                <hr class="my-10 border-dashed">
                <div class="font-bold mb-2">Lampiran 1</div>
                <div class="text-center font-bold uppercase mb-4">Uraian Kegiatan<br>{{ $laporan->perihal_laporan }}<br>{{ $laporan->pegawai->unit_kerja }}</div>
                <table class="w-full border border-collapse text-sm">
                    <thead>
                        <tr>
                            <th class="border p-2 w-40">Hari/Tanggal</th>
                            <th class="border p-2 w-28">Jam</th>
                            <th class="border p-2">Uraian Kegiatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($laporan->uraians as $u)
                            <tr>
                                <td class="border p-2 align-top">{{ $u->tanggal_kegiatan?->translatedFormat('l') }}/{{ $u->tanggal_kegiatan?->translatedFormat('j F Y') }}</td>
                                <td class="border p-2 align-top">{{ trim(($u->jam_mulai ?? '') . (($u->jam_mulai && $u->jam_selesai) ? '-' : '') . ($u->jam_selesai ?? '')) }}</td>
                                <td class="border p-2 align-top prose max-w-none">{!! $u->uraian_html !!}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="border p-2 text-center text-gray-500">Belum ada uraian.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Lampiran 2 --}}
                <hr class="my-10 border-dashed">
                <div class="font-bold mb-2">Lampiran 2</div>
                <div class="text-center font-bold uppercase mb-4">Dokumentasi Kegiatan<br>{{ $laporan->perihal_laporan }}</div>
                @if ($laporan->dokumentasis->count())
                    <div class="grid grid-cols-2 gap-4">
                        @foreach ($laporan->dokumentasis as $dok)
                            <figure class="text-center">
                                <img src="{{ $dok->url }}" class="w-full h-56 object-cover border rounded" alt="dokumentasi">
                                @if ($dok->keterangan)
                                    <figcaption class="text-sm text-gray-600 mt-1">{{ $dok->keterangan }}</figcaption>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm">Belum ada dokumentasi.</p>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function () {
            const sel = document.getElementById('ukuran-kertas');
            const btn = document.getElementById('btn-pdf');
            const base = "{{ route('laporan.pdf', $laporan) }}";
            sel.addEventListener('change', () => { btn.href = base + '?ukuran=' + sel.value; });
        })();
    </script>

    @if (session('clear_draft_key'))
        {{-- Laporan baru saja tersimpan: bersihkan draft form terkait dari browser. --}}
        <script>
            try { localStorage.removeItem(@json(session('clear_draft_key'))); } catch (e) {}
        </script>
    @endif
</x-app-layout>
