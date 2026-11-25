<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Preview Surat Tugas</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $suratTugas->nomor_st }} &middot; {{ $suratTugas->pegawai?->nama }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('laporan.create', ['from_st' => $suratTugas->id]) }}"
                   class="px-3 py-2 text-sm bg-emerald-600 text-white rounded-md hover:bg-emerald-700">
                    + Buat Laporan dari ST ini
                </a>
                <form action="{{ route('surat-tugas.duplicate', $suratTugas) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-2 text-sm bg-slate-600 text-white rounded-md hover:bg-slate-700">Duplikat</button>
                </form>
                @can('update', $suratTugas)
                    <a href="{{ route('surat-tugas.edit', $suratTugas) }}"
                       class="px-3 py-2 text-sm bg-amber-500 text-white rounded-md hover:bg-amber-600">Edit</a>
                @endcan
                <a href="{{ route('surat-tugas.index') }}"
                   class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:underline">Kembali</a>
            </div>
        </div>
    </x-slot>

    @include('surat_tugas._document_styles', ['forPdf' => false])

    <div class="py-8">
        <div class="w-full mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Panel Cetak --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 flex flex-wrap items-center gap-3">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Cetak Dokumen (3 Halaman: ST + SPD + Visum):</span>
                <label class="text-sm text-gray-600 dark:text-gray-400">Ukuran Kertas
                    <select id="ukuran-kertas-st"
                            class="ml-1 border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md text-sm">
                        <option value="f4" selected>F4 / Folio</option>
                        <option value="legal">Legal (sesuai template asli)</option>
                        <option value="a4">A4</option>
                    </select>
                </label>
                <a id="btn-st-pdf" href="{{ route('surat-tugas.pdf', $suratTugas) }}?ukuran=f4" target="_blank"
                   class="px-3.5 py-2 text-sm bg-rose-600 text-white rounded-md hover:bg-rose-700">Cetak PDF</a>
                <a id="btn-st-word" href="{{ route('surat-tugas.word', $suratTugas) }}?ukuran=f4"
                   class="px-3.5 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">Cetak Word (.docx)</a>
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-auto">
                    TTD Kepala BPS: <strong>{{ $suratTugas->sertakan_ttd_kepala ? 'Sertakan TTD Scan' : 'Kosong (TTD Basah / TTE)' }}</strong>
                </span>
            </div>

            {{-- Preview WYSIWYG 3 Halaman (identik dengan hasil PDF) --}}
            @include('surat_tugas._document', ['suratTugas' => $suratTugas, 'forPdf' => false])
        </div>
    </div>

    <script>
        document.getElementById('ukuran-kertas-st')?.addEventListener('change', function () {
            const u = encodeURIComponent(this.value);
            document.getElementById('btn-st-pdf').href = "{{ route('surat-tugas.pdf', $suratTugas) }}?ukuran=" + u;
            document.getElementById('btn-st-word').href = "{{ route('surat-tugas.word', $suratTugas) }}?ukuran=" + u;
        });
    </script>
</x-app-layout>
