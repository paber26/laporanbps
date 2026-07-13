<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Daftar Laporan</h2>
            <a href="{{ route('laporan.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                + Buat Laporan
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-100 dark:bg-green-900/40 border border-green-300 dark:border-green-700 text-green-800 dark:text-green-200 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr class="text-left text-gray-500 dark:text-gray-400">
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Perihal</th>
                                <th class="px-4 py-3">Petugas</th>
                                <th class="px-4 py-3">Tempat/Tanggal</th>
                                <th class="px-4 py-3 text-center">Uraian</th>
                                <th class="px-4 py-3 text-center">Foto</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                            @forelse ($laporans as $laporan)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $laporan->id }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('laporan.show', $laporan) }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                            {{ $laporan->perihal_laporan }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3">{{ $laporan->pegawai->nama }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                        {{ $laporan->tempat_laporan }},<br>
                                        {{ $laporan->tanggal_laporan?->translatedFormat('j F Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-center">{{ $laporan->uraians_count }}</td>
                                    <td class="px-4 py-3 text-center">{{ $laporan->dokumentasis_count }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                            <a href="{{ route('laporan.show', $laporan) }}" class="text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">Lihat</a>
                                            <a href="{{ route('laporan.edit', $laporan) }}" class="text-gray-600 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400">Edit</a>
                                            <form action="{{ route('laporan.duplicate', $laporan) }}" method="POST" onsubmit="return confirm('Duplikat laporan ini?')">
                                               @csrf
                                               <button type="submit" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium px-2 py-1 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/40 dark:hover:bg-blue-900/60 rounded transition-colors text-sm">Duplikat</button>
                                           </form>

                                           <form action="{{ route('laporan.destroy', $laporan) }}" method="POST" onsubmit="return confirm('Hapus laporan ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-gray-600 dark:text-gray-300 hover:text-rose-600 dark:hover:text-rose-400">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada laporan. Silakan buat laporan baru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $laporans->links() }}</div>
        </div>
    </div>
</x-app-layout>
