<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Daftar Surat Tugas (ST &amp; SPD)</h2>
            <a href="{{ route('surat-tugas.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                + Buat Surat Tugas
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Filter & Pencarian --}}
            <form method="GET" action="{{ route('surat-tugas.index') }}"
                  class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div class="md:col-span-4">
                    <label for="search" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Cari Nomor ST / SPD / Pegawai / Kegiatan</label>
                    <input id="search" type="text" name="search" value="{{ request('search') }}"
                           placeholder="Ketik kata kunci..."
                           class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md text-sm">
                </div>
                <div class="md:col-span-3">
                    <label for="pegawai_id" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Pegawai</label>
                    <select id="pegawai_id" name="pegawai_id"
                            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md text-sm">
                        <option value="">Semua Pegawai</option>
                        @foreach ($pegawais as $p)
                            <option value="{{ $p->id }}" @selected(request('pegawai_id') == $p->id)>{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="bulan" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Bulan ST</label>
                    <select id="bulan" name="bulan"
                            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md text-sm">
                        <option value="">Semua</option>
                        @foreach ($bulans as $num => $label)
                            <option value="{{ $num }}" @selected(request('bulan') == $num)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label for="tahun" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tahun</label>
                    <select id="tahun" name="tahun"
                            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md text-sm">
                        <option value="">Semua</option>
                        @foreach ($tahuns as $thn)
                            <option value="{{ $thn }}" @selected(request('tahun') == $thn)>{{ $thn }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="px-3 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 w-full">Filter</button>
                    @if (request()->hasAny(['search', 'pegawai_id', 'bulan', 'tahun']))
                        <a href="{{ route('surat-tugas.index') }}"
                           class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">Reset</a>
                    @endif
                </div>
            </form>

            {{-- Tabel Daftar Surat Tugas --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-left text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Nomor ST &amp; SPD</th>
                            <th class="px-4 py-3">Pegawai</th>
                            <th class="px-4 py-3">Tujuan Kegiatan &amp; Lokasi</th>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                        @forelse ($suratTugasList as $st)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 align-top whitespace-nowrap">
                                    <a href="{{ route('surat-tugas.show', $st) }}" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ $st->nomor_st }}
                                    </a>
                                    @if ($st->nomor_spd)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 font-mono mt-0.5">SPD: {{ $st->nomor_spd }}</div>
                                    @endif
                                    <div class="text-xs text-gray-400 mt-0.5">Tgl ST: {{ \App\Models\SuratTugas::formatTanggal($st->tanggal_st) }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $st->pegawai?->nama }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $st->jabatan ?: $st->pegawai?->jabatan }}</div>
                                    @if ($st->pangkat_golongan)
                                        <div class="text-xs text-gray-400">{{ $st->pangkat_golongan }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top max-w-md">
                                    <div class="line-clamp-2 text-gray-800 dark:text-gray-200">{{ $st->tujuan_kegiatan }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Rute: {{ $st->lokasiRingkas() }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top whitespace-nowrap">
                                    <div>{{ $st->waktu_pelaksanaan }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $st->jumlah_hari }} hari</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-center justify-end gap-2 whitespace-nowrap text-xs font-medium">
                                        <a href="{{ route('surat-tugas.show', $st) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Preview</a>
                                        <a href="{{ route('surat-tugas.pdf', $st) }}?ukuran=f4" target="_blank" class="text-rose-600 dark:text-rose-400 hover:underline">PDF</a>
                                        <a href="{{ route('surat-tugas.word', $st) }}?ukuran=f4" class="text-blue-600 dark:text-blue-400 hover:underline">Word</a>
                                        <form action="{{ route('surat-tugas.duplicate', $st) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-emerald-600 dark:text-emerald-400 hover:underline">Duplikat</button>
                                        </form>
                                        @can('update', $st)
                                            <a href="{{ route('surat-tugas.edit', $st) }}" class="text-amber-600 dark:text-amber-400 hover:underline">Edit</a>
                                        @endcan
                                        @can('delete', $st)
                                            <form action="{{ route('surat-tugas.destroy', $st) }}" method="POST"
                                                  data-confirm="Hapus Surat Tugas ini? Tindakan ini tidak dapat dibatalkan."
                                                  data-confirm-title="Hapus Surat Tugas" data-confirm-ok="Hapus" data-confirm-variant="danger">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-rose-600 dark:text-rose-400 hover:underline">Hapus</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                    Belum ada Surat Tugas. Klik <strong>+ Buat Surat Tugas</strong> untuk mulai membuat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $suratTugasList->links() }}</div>
        </div>
    </div>
</x-app-layout>
