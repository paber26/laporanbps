<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Buat Surat Tugas Baru (ST + SPD + Visum)</h2>
            <a href="{{ route('surat-tugas.index') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:underline">Kembali</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full mx-auto sm:px-6 lg:px-8">
            @include('surat_tugas._form', [
                'action' => route('surat-tugas.store'),
                'method' => 'POST',
                'suratTugas' => null,
            ])
        </div>
    </div>
</x-app-layout>
