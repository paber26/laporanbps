<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Tambah Pegawai</h2>
    </x-slot>
    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @include('pegawai._form', ['action' => route('pegawai.store'), 'method' => 'POST'])
        </div>
    </div>
</x-app-layout>
