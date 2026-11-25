<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Buat Laporan Baru</h2>
    </x-slot>

    <div class="py-8">
        <div class="w-full mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (! empty($prefill['from_st_nomor']))
                <div class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 rounded-md text-sm">
                    Form diisi otomatis dari Surat Tugas <strong>{{ $prefill['from_st_nomor'] }}</strong>. Silakan lengkapi uraian kegiatan dan dokumentasi.
                </div>
            @endif
            @include('laporan._form', [
                'action' => route('laporan.store'),
                'method' => 'POST',
                'laporan' => null,
                'prefill' => $prefill ?? [],
            ])
        </div>
    </div>
</x-app-layout>
