<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Master Pembiayaan</h2>
            <a href="{{ route('master-pembiayaan.create') }}" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">+ Tambah</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-100 dark:bg-green-900/40 border border-green-300 dark:border-green-700 text-green-800 dark:text-green-200 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-left text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Program</th>
                            <th class="px-4 py-3">Kegiatan</th>
                            <th class="px-4 py-3">RO</th>
                            <th class="px-4 py-3">Komponen</th>
                            <th class="px-4 py-3">Akun</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                        @forelse ($pembiayaans as $p)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 align-top">
                                <td class="px-4 py-3">{{ $p->program }}</td>
                                <td class="px-4 py-3">{{ $p->kegiatan }}</td>
                                <td class="px-4 py-3">{{ $p->ro }}</td>
                                <td class="px-4 py-3">{{ $p->komponen }}</td>
                                <td class="px-4 py-3">{{ $p->akun }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                        <a href="{{ route('master-pembiayaan.edit', $p) }}" class="text-amber-600 dark:text-amber-400 hover:underline">Edit</a>
                                        <form action="{{ route('master-pembiayaan.destroy', $p) }}" method="POST" onsubmit="return confirm('Hapus data ini?')">
                                            @csrf @method('DELETE')
                                            <button class="text-rose-600 dark:text-rose-400 hover:underline">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada data pembiayaan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $pembiayaans->links() }}</div>
        </div>
    </div>
</x-app-layout>
