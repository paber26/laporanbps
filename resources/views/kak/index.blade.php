<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Daftar KAK (Kerangka Acuan Kerja)</h2>
            <a href="{{ route('kak.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                + Buat KAK
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
                                <th class="px-4 py-3">Judul KAK</th>
                                <th class="px-4 py-3">Tahun</th>
                                <th class="px-4 py-3">Unit Kerja</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                            @forelse ($kaks as $kak)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $kak->id }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('kak.show', $kak) }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                            {{ $kak->judul }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3">{{ $kak->tahun }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $kak->unit_kerja ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                            <a href="{{ route('kak.show', $kak) }}" class="text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">Lihat</a>
                                            <a href="{{ route('kak.edit', $kak) }}" class="text-gray-600 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400">Edit</a>
                                            <form action="{{ route('kak.destroy', $kak) }}" method="POST" onsubmit="return confirm('Hapus KAK ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-gray-600 dark:text-gray-300 hover:text-rose-600 dark:hover:text-rose-400">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada KAK. Silakan buat KAK baru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $kaks->links() }}</div>
        </div>
    </div>
</x-app-layout>
