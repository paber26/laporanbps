<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Buat KAK Baru</h2>
    </x-slot>

    <div class="py-8">
        <div class="w-full mx-auto sm:px-6 lg:px-8">
            @include('kak._form', [
                'action' => route('kak.store'),
                'method' => 'POST',
                'kak' => null,
            ])
        </div>
    </div>
</x-app-layout>
