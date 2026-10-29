<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' — Buat Laporan' : 'Buat Laporan' }}</title>
        <link rel="icon" type="image/png" href="{{ asset('logo-small.png') }}">

        {{-- Terapkan tema (terang/gelap) SEBELUM render agar tidak berkedip (FOUC). --}}
        <script>
            (function () {
                try {
                    var t = localStorage.getItem('theme');
                    if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                        document.documentElement.classList.add('dark');
                    }
                } catch (e) {}
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Pusatkan gambar CKEditor pada preview uraian (show & modal) agar sama
             seperti di editor; hormati perataan kiri/kanan eksplisit. --}}
        <style>
            .uraian-html figure.image { display: table; margin: 0.75rem auto; max-width: 100%; }
            .uraian-html figure.image img { display: block; width: 100%; height: auto; }
            .uraian-html figure.image.image-style-align-left { margin-left: 0; margin-right: auto; }
            .uraian-html figure.image.image-style-align-right { margin-left: auto; margin-right: 0; }
            .uraian-html figure.image > figcaption { text-align: center; font-size: 0.8rem; color: #6b7280; }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        {{-- Notifikasi global (toast + modal konfirmasi) --}}
        @include('partials.notifications')
    </body>
</html>
