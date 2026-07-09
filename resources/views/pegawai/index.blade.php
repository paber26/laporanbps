<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Data Pegawai</h2>
            <a href="{{ route('pegawai.create') }}" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">+ Tambah</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-3">NIP</th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Unit Kerja</th>
                            <th class="px-4 py-3">TTD</th>
                            <th class="px-4 py-3 text-center">Laporan</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($pegawais as $p)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-xs">{{ $p->nip }}</td>
                                <td class="px-4 py-3">{{ $p->nama }}</td>
                                <td class="px-4 py-3">{{ $p->unit_kerja }}</td>
                                <td class="px-4 py-3">
                                    @if ($p->tanda_tangan_path)
                                        <img src="{{ $p->tanda_tangan_path ? \Storage::url($p->tanda_tangan_path) : '' }}" class="h-8" alt="ttd">
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">{{ $p->laporans_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                        <a href="{{ route('pegawai.edit', $p) }}" class="text-amber-600 hover:underline">Edit</a>
                                        <form action="{{ route('pegawai.destroy', $p) }}" method="POST" onsubmit="return confirm('Hapus pegawai ini?')">
                                            @csrf @method('DELETE')
                                            <button class="text-rose-600 hover:underline">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada pegawai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $pegawais->links() }}</div>
        </div>
    </div>
</x-app-layout>
