<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Master Pembiayaan</h2>
    </x-slot>
    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @include('master_pembiayaan._form', ['action' => route('master-pembiayaan.store'), 'method' => 'POST'])
        </div>
    </div>
</x-app-layout>
