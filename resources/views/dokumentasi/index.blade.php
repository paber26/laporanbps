<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Galeri Foto Dokumentasi</span>
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Kumpulan seluruh arsip foto pelaksanaan kegiatan dan perjalanan dinas BPS
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('laporan.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 shadow-sm transition">
                    + Buat Laporan Baru
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8"
         x-data="{
             openModal: false,
             currentIndex: 0,
             imageLoading: true,
             photos: {{ Js::from($dokumentasis->map(fn($d) => [
                 'id' => $d->id,
                 'url' => $d->url,
                 'thumb_url' => $d->thumbnail_url,
                 'keterangan' => $d->keterangan ?: '',
                 'perihal' => $d->laporan?->perihal_laporan ?? 'Laporan Tanpa Judul',
                 'petugas' => $d->laporan?->pegawai?->nama ?? '-',
                 'nip' => $d->laporan?->pegawai?->nip ?? '',
                 'lokasi' => $d->laporan?->lokasi_tujuan ?: ($d->laporan?->tempat_laporan ?? '-'),
                 'tanggal' => $d->laporan?->tanggal_kegiatan_formatted ?? ($d->laporan?->tanggal_laporan?->format('d-m-Y') ?? '-'),
                 'laporan_url' => $d->laporan ? route('laporan.show', $d->laporan) : '#',
             ])) }},
             open(idx) {
                 this.currentIndex = idx;
                 this.imageLoading = true;
                 this.openModal = true;
                 document.body.style.overflow = 'hidden';
             },
             close() {
                 this.openModal = false;
                 document.body.style.overflow = '';
             },
             next() {
                 if (this.photos.length > 0) {
                     this.imageLoading = true;
                     this.currentIndex = (this.currentIndex + 1) % this.photos.length;
                 }
             },
             prev() {
                 if (this.photos.length > 0) {
                     this.imageLoading = true;
                     this.currentIndex = (this.currentIndex - 1 + this.photos.length) % this.photos.length;
                 }
             }
         }"
         @keydown.escape.window="close()"
         @keydown.arrow-right.window="if(openModal) next()"
         @keydown.arrow-left.window="if(openModal) prev()">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- ===================== STATISTIK RINGKAS ===================== --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalFoto) }}</div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Foto Dokumentasi</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalLaporanDenganFoto) }}</div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Laporan Memiliki Dokumentasi</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($dokumentasis->total()) }}</div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Foto Sesuai Filter</div>
                    </div>
                </div>
            </div>

            {{-- ===================== FILTER TOOLBAR ===================== --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700/60">
                <form method="GET" action="{{ route('dokumentasi.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        {{-- Keyword search --}}
                        <div class="md:col-span-4">
                            <label for="search" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Cari Kata Kunci</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <input type="text" id="search" name="search" value="{{ request('search') }}"
                                       placeholder="Perihal, keterangan, lokasi, nama petugas..."
                                       class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-800/40">
                            </div>
                        </div>

                        {{-- Petugas filter --}}
                        <div class="md:col-span-3">
                            <label for="pegawai_id" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Petugas</label>
                            <select id="pegawai_id" name="pegawai_id"
                                    class="w-full py-2 px-3 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-800/40">
                                <option value="">Semua Petugas</option>
                                @foreach ($pegawais as $p)
                                    <option value="{{ $p->id }}" @selected(request('pegawai_id') == $p->id)>{{ $p->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Laporan filter --}}
                        <div class="md:col-span-3">
                            <label for="laporan_id" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Laporan Kegiatan</label>
                            <select id="laporan_id" name="laporan_id"
                                    class="w-full py-2 px-3 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-800/40">
                                <option value="">Semua Laporan</option>
                                @foreach ($laporans as $lap)
                                    @php
                                        $lokasi = $lap->lokasi_tujuan ?: ($lap->tempat_laporan ?: '-');
                                        $tgl = $lap->tanggal_kegiatan_formatted;
                                    @endphp
                                    <option value="{{ $lap->id }}"
                                            title="#{{ $lap->id }} - {{ $lap->perihal_laporan }} (Lokasi: {{ $lokasi }} | Tgl: {{ $tgl }})"
                                            @selected(request('laporan_id') == $lap->id)>
                                        #{{ $lap->id }} - {{ Str::limit($lap->perihal_laporan, 50) }} (Lokasi: {{ $lokasi }} | Tgl: {{ $tgl }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Tahun filter --}}
                        <div class="md:col-span-2">
                            <label for="tahun" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Tahun</label>
                            <select id="tahun" name="tahun"
                                    class="w-full py-2 px-3 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-800/40">
                                <option value="">Semua Tahun</option>
                                @foreach ($tahuns as $thn)
                                    <option value="{{ $thn }}" @selected(request('tahun') == $thn)>{{ $thn }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700/60">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Menampilkan <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $dokumentasis->firstItem() ?? 0 }}</span>
                            - <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $dokumentasis->lastItem() ?? 0 }}</span>
                            dari <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $dokumentasis->total() }}</span> foto dokumentasi
                        </div>
                        <div class="flex items-center gap-2">
                            @if (request()->hasAny(['search', 'pegawai_id', 'laporan_id', 'tahun', 'bulan']))
                                <a href="{{ route('dokumentasi.index') }}" class="px-3 py-1.5 text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:underline">
                                    Reset Filter
                                </a>
                            @endif
                            <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg shadow-sm transition">
                                Terapkan Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- ===================== GALLERY GRID ===================== --}}
            @if ($dokumentasis->count())
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                    @foreach ($dokumentasis as $index => $dok)
                        <div class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md border border-gray-100 dark:border-gray-700/60 overflow-hidden flex flex-col transition-all duration-200">
                            {{-- Image Container with Hover Overlay --}}
                            <div class="relative aspect-[4/3] bg-gray-100 dark:bg-gray-900 overflow-hidden cursor-pointer"
                                 @click="open({{ $index }})"
                                 title="Klik untuk memperbesar foto">
                                <img src="{{ $dok->thumbnail_url }}"
                                     alt="{{ $dok->keterangan ?: 'Dokumentasi ' . ($dok->laporan?->perihal_laporan ?? '') }}"
                                     loading="lazy"
                                     decoding="async"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">

                                {{-- Dark gradient on hover --}}
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end justify-between p-3 text-white">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium bg-black/60 backdrop-blur-sm px-2.5 py-1 rounded-full">
                                        <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                        </svg>
                                        Ukuran Asli
                                    </span>
                                    <span class="text-[11px] bg-black/60 backdrop-blur-sm px-2 py-0.5 rounded text-gray-200">
                                        #{{ $dok->laporan_id }}
                                    </span>
                                </div>

                                {{-- Badge lokasi di sudut atas --}}
                                @if ($dok->laporan?->lokasi_tujuan)
                                    <div class="absolute top-2.5 left-2.5">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-white/90 dark:bg-gray-900/90 text-gray-700 dark:text-gray-200 backdrop-blur-sm shadow-sm">
                                            <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ $dok->laporan->lokasi_tujuan }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            {{-- Card Details --}}
                            <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                                <div>
                                    {{-- Keterangan Foto --}}
                                    @if ($dok->keterangan)
                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 line-clamp-2 mb-1" title="{{ $dok->keterangan }}">
                                            {{ $dok->keterangan }}
                                        </p>
                                    @else
                                        <p class="text-xs italic text-gray-400 dark:text-gray-500 mb-1">
                                            Tanpa keterangan foto
                                        </p>
                                    @endif

                                    {{-- Info Laporan Terkait --}}
                                    @if ($dok->laporan)
                                        <a href="{{ route('laporan.show', $dok->laporan) }}"
                                           class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline line-clamp-2 font-medium block"
                                           title="{{ $dok->laporan->perihal_laporan }}">
                                            {{ $dok->laporan->perihal_laporan }}
                                        </a>
                                    @endif
                                </div>

                                <div class="pt-2 border-t border-gray-100 dark:border-gray-700/60 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                    {{-- Petugas --}}
                                    <div class="flex items-center gap-1.5 truncate">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span class="truncate" title="{{ $dok->laporan?->pegawai?->nama }}">{{ $dok->laporan?->pegawai?->nama ?? '-' }}</span>
                                    </div>

                                    {{-- Lokasi Tujuan --}}
                                    @if ($dok->laporan?->lokasi_tujuan)
                                        <div class="flex items-center gap-1.5 truncate">
                                            <svg class="w-3.5 h-3.5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="truncate" title="Lokasi Tujuan: {{ $dok->laporan->lokasi_tujuan }}">{{ $dok->laporan->lokasi_tujuan }}</span>
                                        </div>
                                    @endif

                                    {{-- Tanggal Kegiatan --}}
                                    <div class="flex items-center gap-1.5 truncate">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>{{ $dok->laporan?->tanggal_kegiatan_formatted ?? ($dok->laporan?->tanggal_laporan?->format('d-m-Y') ?? '-') }}</span>
                                    </div>
                                </div>

                                {{-- Action Button --}}
                                <div class="pt-2 flex items-center justify-between gap-2">
                                    <button type="button"
                                            @click="open({{ $index }})"
                                            class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Lihat
                                    </button>

                                    @if ($dok->laporan)
                                        <a href="{{ route('laporan.show', $dok->laporan) }}"
                                           class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                            <span>Ke Laporan</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination Links --}}
                <div class="pt-4">
                    {{ $dokumentasis->links() }}
                </div>
            @else
                {{-- Empty State --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-sm border border-gray-100 dark:border-gray-700/60 space-y-4">
                    <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700/50 flex items-center justify-center mx-auto text-gray-400 dark:text-gray-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Tidak ada foto dokumentasi ditemukan</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                            @if (request()->hasAny(['search', 'pegawai_id', 'laporan_id', 'tahun', 'bulan']))
                                Tidak ada foto yang cocok dengan kriteria filter pencarian Anda. Silakan coba sesuaikan kata kunci atau reset filter.
                            @else
                                Belum ada foto dokumentasi yang tersimpan di sistem. Foto dokumentasi akan muncul otomatis ketika Anda menambahkan foto pada lampiran laporan.
                            @endif
                        </p>
                    </div>
                    @if (request()->hasAny(['search', 'pegawai_id', 'laporan_id', 'tahun', 'bulan']))
                        <div>
                            <a href="{{ route('dokumentasi.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                                Reset Filter Pencarian
                            </a>
                        </div>
                    @endif
                </div>
            @endif

        </div>

        {{-- ===================== LIGHTBOX MODAL ===================== --}}
        <div x-show="openModal"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/85 backdrop-blur-md"
             role="dialog"
             aria-modal="true">

            {{-- Close button --}}
            <button type="button"
                    @click="close()"
                    class="absolute top-4 right-4 z-50 text-white/80 hover:text-white bg-black/40 hover:bg-black/70 p-2.5 rounded-full transition"
                    title="Tutup (ESC)">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            {{-- Prev button --}}
            <button type="button"
                    x-show="photos.length > 1"
                    @click="prev()"
                    class="absolute left-3 sm:left-6 top-1/2 -translate-y-1/2 z-50 text-white/80 hover:text-white bg-black/40 hover:bg-black/70 p-3 rounded-full transition"
                    title="Foto Sebelumnya (Panah Kiri)">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            {{-- Next button --}}
            <button type="button"
                    x-show="photos.length > 1"
                    @click="next()"
                    class="absolute right-3 sm:right-6 top-1/2 -translate-y-1/2 z-50 text-white/80 hover:text-white bg-black/40 hover:bg-black/70 p-3 rounded-full transition"
                    title="Foto Berikutnya (Panah Kanan)">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            {{-- Modal Content Box --}}
            <div class="relative w-full max-w-5xl max-h-[92vh] flex flex-col bg-gray-900 rounded-2xl overflow-hidden shadow-2xl border border-gray-800"
                 @click.outside="close()">

                {{-- Image Display Area --}}
                <div class="relative flex-1 flex items-center justify-center bg-black/70 min-h-[320px] max-h-[68vh] p-2 overflow-hidden">
                    {{-- Spinner saat foto ukuran asli sedang dimuat --}}
                    <div x-show="imageLoading" class="absolute inset-0 flex flex-col items-center justify-center gap-2.5 text-gray-400 z-10">
                        <svg class="animate-spin w-8 h-8 text-indigo-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-xs font-medium text-gray-300">Memuat foto ukuran asli...</span>
                    </div>

                    <template x-if="photos.length > 0">
                        <img :src="photos[currentIndex].url"
                             :alt="photos[currentIndex].keterangan || photos[currentIndex].perihal"
                             @load="imageLoading = false"
                             class="max-h-[66vh] max-w-full w-auto object-contain rounded-lg shadow-xl select-none transition-opacity duration-300"
                             :class="imageLoading ? 'opacity-0' : 'opacity-100'">
                    </template>
                </div>

                {{-- Detail Caption Bar at Bottom --}}
                <div class="bg-gray-900/95 border-t border-gray-800 p-4 sm:p-5 text-gray-200">
                    <template x-if="photos.length > 0">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="space-y-1.5 max-w-2xl">
                                {{-- Counter badge & Ukuran Asli label --}}
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-[11px] font-mono uppercase tracking-wider bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded">
                                        Foto <span x-text="currentIndex + 1"></span> dari <span x-text="photos.length"></span>
                                    </span>
                                    <span class="text-[11px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Ukuran Asli
                                    </span>
                                    <span class="text-xs text-gray-400" x-text="photos[currentIndex].tanggal"></span>
                                    <span class="text-gray-500">•</span>
                                    <span class="text-xs text-gray-400 flex items-center gap-1">
                                        <svg class="w-3 h-3 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span x-text="photos[currentIndex].lokasi"></span>
                                    </span>
                                </div>

                                {{-- Keterangan / Caption --}}
                                <h4 class="text-base font-semibold text-white line-clamp-2"
                                    x-text="photos[currentIndex].keterangan || 'Tanpa keterangan foto'">
                                </h4>

                                {{-- Perihal Laporan & Petugas --}}
                                <div class="text-xs text-gray-400 flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <span>
                                        Laporan: <strong class="text-gray-300" x-text="photos[currentIndex].perihal"></strong>
                                    </span>
                                    <span>•</span>
                                    <span>
                                        Petugas: <strong class="text-gray-300" x-text="photos[currentIndex].petugas"></strong>
                                    </span>
                                </div>
                            </div>

                            {{-- Actions in Lightbox --}}
                            <div class="flex items-center gap-2 shrink-0">
                                <a :href="photos[currentIndex].url"
                                   target="_blank"
                                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-800 hover:bg-gray-700 text-gray-200 text-xs font-medium rounded-lg border border-gray-700 transition"
                                   title="Buka ukuran penuh di tab baru">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Ukuran Penuh
                                </a>

                                <a :href="photos[currentIndex].laporan_url"
                                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition"
                                   title="Buka laporan terkait foto ini">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Buka Laporan
                                </a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
