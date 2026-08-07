<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Dashboard') }}</h2>
            <div class="flex items-center gap-2">
                <a href="https://drive.google.com/drive/u/0/folders/197tXagiU0qyapO-ev3XAPpuSomVI6MmI" target="_blank" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
                    Drive
                </a>
                <a href="{{ route('laporan.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    + Buat Laporan
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 dark:bg-green-900/40 border border-green-300 dark:border-green-700 text-green-800 dark:text-green-200 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Kartu statistik --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @php
                    $cards = [
                        ['label' => 'Total Laporan', 'value' => $stats['total_laporan'], 'border' => 'border-indigo-500'],
                        ['label' => 'Laporan Bulan Ini', 'value' => $stats['laporan_bulan_ini'], 'border' => 'border-emerald-500'],
                        ['label' => 'Jumlah Pegawai', 'value' => $stats['total_pegawai'], 'border' => 'border-amber-500'],
                        ['label' => 'Master Pembiayaan', 'value' => $stats['total_pembiayaan'], 'border' => 'border-rose-500'],
                    ];
                @endphp
                @foreach ($cards as $c)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6 border-l-4 {{ $c['border'] }}">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $c['label'] }}</div>
                        <div class="mt-2 text-3xl font-bold text-gray-800 dark:text-gray-100">{{ $c['value'] }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Menu pintasan --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('kak.create') }}" class="group bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 border-l-4 border-sky-500 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Kerangka Acuan Kerja</div>
                            <div class="mt-1 text-lg font-bold text-gray-800 dark:text-gray-100 group-hover:text-sky-600 dark:group-hover:text-sky-400">Buat KAK</div>
                        </div>
                        <svg class="h-8 w-8 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Laporan per petugas --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">Laporan per Petugas</h3>
                    @forelse ($laporanPerPetugas as $p)
                        <div class="flex items-center justify-between py-2 border-b dark:border-gray-700 last:border-0">
                            <div>
                                <div class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $p->nama }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $p->unit_kerja }}</div>
                            </div>
                            <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-200">
                                {{ $p->laporans_count }} laporan
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada data.</p>
                    @endforelse
                </div>

                {{-- Laporan terbaru --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">Laporan Terbaru</h3>
                    @forelse ($laporanTerbaru as $l)
                        <div class="flex items-center justify-between py-2 border-b dark:border-gray-700 last:border-0">
                            <div class="min-w-0 pr-3">
                                <a href="{{ route('laporan.show', $l) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline truncate block">
                                    {{ $l->perihal_laporan }}
                                </a>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $l->pegawai->nama }} &middot; {{ $l->created_at->diffForHumans() }}</div>
                            </div>
                            <a href="{{ route('laporan.show', $l) }}" class="text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">Lihat &rarr;</a>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada laporan.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
