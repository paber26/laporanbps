<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit DOCX KAK</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('kak.show', $kak) }}" class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100">Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div id="docx-editor-root"
                 data-kak-id="{{ $kak->id }}"
                 data-original-url="{{ route('kak.docx.original', $kak) }}"
                 data-save-url="{{ route('kak.docx.save', $kak) }}"
                 data-edited-url="{{ route('kak.docx.edited', $kak) }}"
                 data-pdf-url="{{ route('kak.pdf', $kak) }}"
                 data-edited-pdf-url="{{ route('kak.edit-docx.pdf', $kak) }}"
                 data-csrf-token="{{ csrf_token() }}"
                 class="w-full"></div>
        </div>
    </div>

    @vite(['resources/js/docx-editor-entry.jsx'])
</x-app-layout>
