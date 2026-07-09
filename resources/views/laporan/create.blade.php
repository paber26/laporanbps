<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buat Laporan Baru</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @include('laporan._form', [
                'action' => route('laporan.store'),
                'method' => 'POST',
                'laporan' => null,
            ])
        </div>
    </div>
</x-app-layout>
