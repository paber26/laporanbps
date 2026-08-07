<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit KAK #{{ $kak->id }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="w-full mx-auto sm:px-6 lg:px-8">
            @include('kak._form', [
                'action' => route('kak.update', $kak),
                'method' => 'PUT',
            ])
        </div>
    </div>
</x-app-layout>
