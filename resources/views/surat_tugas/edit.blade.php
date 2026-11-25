<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit Surat Tugas — {{ $suratTugas->nomor_st }}</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('surat-tugas.show', $suratTugas) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Preview</a>
                <a href="{{ route('surat-tugas.index') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:underline">Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full mx-auto sm:px-6 lg:px-8">
            @include('surat_tugas._form', [
                'action' => route('surat-tugas.update', $suratTugas),
                'method' => 'PUT',
                'suratTugas' => $suratTugas,
            ])
        </div>
    </div>
</x-app-layout>
