<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daftar Laporan</h2>
            <a href="{{ route('laporan.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                + Buat Laporan
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-gray-500">
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Perihal</th>
                                <th class="px-4 py-3">Petugas</th>
                                <th class="px-4 py-3">Tempat/Tanggal</th>
                                <th class="px-4 py-3 text-center">Uraian</th>
                                <th class="px-4 py-3 text-center">Foto</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($laporans as $laporan)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-500">{{ $laporan->id }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('laporan.show', $laporan) }}" class="font-medium text-indigo-600 hover:underline">
                                            {{ Str::limit($laporan->perihal_laporan, 60) }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3">{{ $laporan->pegawai->nama }}</td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $laporan->tempat_laporan }},<br>
                                        {{ $laporan->tanggal_laporan?->translatedFormat('j F Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-center">{{ $laporan->uraians_count }}</td>
                                    <td class="px-4 py-3 text-center">{{ $laporan->dokumentasis_count }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                            <a href="{{ route('laporan.show', $laporan) }}" class="text-gray-600 hover:text-indigo-600">Lihat</a>
                                            <a href="{{ route('laporan.edit', $laporan) }}" class="text-gray-600 hover:text-amber-600">Edit</a>
                                            <form action="{{ route('laporan.duplicate', $laporan) }}" method="POST" onsubmit="return confirm('Duplikat laporan ini?')">
                                               @csrf
                                               <button type="submit" class="text-blue-500 hover:text-blue-700 font-medium px-2 py-1 bg-blue-50 hover:bg-blue-100 rounded transition-colors text-sm">Duplikat</button>
                                           </form>

                                           <form action="{{ route('laporan.destroy', $laporan) }}" method="POST" onsubmit="return confirm('Hapus laporan ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-gray-600 hover:text-rose-600">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada laporan. Silakan buat laporan baru.</td>
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
