@php
    $modalData = $laporans->getCollection()->mapWithKeys(function ($lap) {
        return [
            $lap->id => [
                'id' => $lap->id,
                'judul' => $lap->judul_laporan,
                'perihal' => $lap->perihal_laporan,
                'tujuan_surat' => $lap->tujuan_surat,
                'tempat_laporan' => $lap->tempat_laporan,
                'tanggal_laporan' => $lap->tanggal_laporan?->format('d-m-Y') ?? '-',
                'lokasi_tujuan' => $lap->lokasi_tujuan ?: '-',
                'tanggal_kegiatan' => $lap->tanggal_kegiatan_formatted,
                'pegawai' => [
                    'nama' => $lap->pegawai->nama ?? '-',
                    'nip' => $lap->pegawai->nip ?? '-',
                    'jabatan' => $lap->pegawai->jabatan ?? '-',
                    'pangkat_golongan' => $lap->pegawai->pangkat_golongan ?? '-',
                    'unit_kerja' => $lap->pegawai->unit_kerja ?? '-',
                ],
                'pembiayaan' => $lap->pembiayaan ? [
                    'program' => $lap->pembiayaan->program,
                    'kegiatan' => $lap->pembiayaan->kegiatan,
                    'ro' => $lap->pembiayaan->ro,
                    'komponen' => $lap->pembiayaan->komponen,
                    'akun' => $lap->pembiayaan->akun,
                ] : null,
                'uraians' => $lap->uraians->map(function ($u) {
                    return [
                        'tanggal' => $u->tanggal_kegiatan ? $u->tanggal_kegiatan->format('d-m-Y') : '-',
                        'jam' => trim(($u->jam_mulai ?? '') . (($u->jam_mulai && $u->jam_selesai) ? ' - ' : '') . ($u->jam_selesai ?? '')),
                        'html' => $u->uraian_html,
                    ];
                })->values(),
                'dokumentasis' => $lap->dokumentasis->map(function ($d) {
                    return [
                        'id' => $d->id,
                        'url' => $d->url,
                        'thumb_url' => $d->thumbnail_url,
                        'keterangan' => $d->keterangan ?: '',
                    ];
                })->values(),
                'show_url' => route('laporan.show', $lap),
                'edit_url' => route('laporan.edit', $lap),
                'pdf_url' => route('laporan.pdf', $lap) . '?ukuran=f4',
                'word_url' => route('laporan.word', $lap),
            ]
        ];
    });

    $activeFilterCount = 0;
    if (request('search')) $activeFilterCount++;
    if (request('pegawai_id')) $activeFilterCount++;
    if (request('tahun')) $activeFilterCount++;
    if (request('bulan')) $activeFilterCount++;
    if (request('per_page') && request('per_page') != '10') $activeFilterCount++;
    if (request('sort') === 'tanggal_kegiatan') $activeFilterCount++;
    $hasActiveFilter = $activeFilterCount > 0;

    $removeFilterUrl = fn (string $key) => $activeFilterCount <= 1
        ? route('laporan.index', ['reset' => 1])
        : request()->fullUrlWithQuery([$key => null]);

    $removeSortUrl = $activeFilterCount <= 1
        ? route('laporan.index', ['reset' => 1])
        : request()->fullUrlWithQuery(['sort' => null, 'direction' => null]);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Daftar Laporan</h2>
            <div class="flex items-center gap-2">
                <button type="button"
                        x-data
                        @click="$dispatch('open-filter-drawer')"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium rounded-lg transition {{ $hasActiveFilter ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-700' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }} shadow-sm">
                    <svg class="w-4 h-4 {{ $hasActiveFilter ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Filter</span>
                    @if ($hasActiveFilter)
                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[11px] font-bold text-white bg-indigo-600 rounded-full">
                            {{ $activeFilterCount }}
                        </span>
                    @endif
                </button>

                <a href="{{ route('laporan.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 shadow-sm transition">
                    + Buat Laporan
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8"
         x-data="{
             showModal: false,
             selected: null,
             showFilterDrawer: false,
             laporans: {{ Js::from($modalData) }},
             openModal(id) {
                 this.selected = this.laporans[id] || null;
                 if (this.selected) {
                     this.showModal = true;
                     document.body.style.overflow = 'hidden';
                 }
             },
             closeModal() {
                 this.showModal = false;
                 this.selected = null;
                 if (!this.showFilterDrawer) {
                     document.body.style.overflow = '';
                 }
             },
             openFilterDrawer() {
                 this.showFilterDrawer = true;
                 document.body.style.overflow = 'hidden';
             },
             closeFilterDrawer() {
                 this.showFilterDrawer = false;
                 if (!this.showModal) {
                     document.body.style.overflow = '';
                 }
             },
             handleEscape() {
                 if (this.showModal) {
                     this.closeModal();
                 } else if (this.showFilterDrawer) {
                     this.closeFilterDrawer();
                 }
             }
         }"
         @open-filter-drawer.window="openFilterDrawer()"
         @keydown.escape.window="handleEscape()">
        <div class="w-full mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-100 dark:bg-green-900/40 border border-green-300 dark:border-green-700 text-green-800 dark:text-green-200 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif

            {{-- Toolbar Ringkas & Status Filter Aktif --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-gray-800 px-4 py-3 rounded-lg border border-gray-100 dark:border-gray-700/60 shadow-sm">
                <div class="flex items-center gap-2 flex-wrap text-xs text-gray-500 dark:text-gray-400">
                    <div>
                        Menampilkan <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $laporans->firstItem() ?? 0 }}</span>
                        - <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $laporans->lastItem() ?? 0 }}</span>
                        dari <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $laporans->total() }}</span> laporan
                    </div>

                    @if ($hasActiveFilter)
                        <div class="flex items-center gap-1.5 flex-wrap ml-1">
                            @if (request('search'))
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                    "{{ Str::limit(request('search'), 20) }}"
                                    <a href="{{ $removeFilterUrl('search') }}" class="hover:text-red-500 font-bold">&times;</a>
                                </span>
                            @endif
                            @if (request('pegawai_id'))
                                @php $pNama = $pegawais->firstWhere('id', request('pegawai_id'))?->nama; @endphp
                                @if ($pNama)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                        {{ Str::limit($pNama, 20) }}
                                        <a href="{{ $removeFilterUrl('pegawai_id') }}" class="hover:text-red-500 font-bold">&times;</a>
                                    </span>
                                @endif
                            @endif
                            @if (request('tahun'))
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                    Tahun: {{ request('tahun') }}
                                    <a href="{{ $removeFilterUrl('tahun') }}" class="hover:text-red-500 font-bold">&times;</a>
                                </span>
                            @endif
                            @if (request('bulan'))
                                @php $bNama = $bulans[(int)request('bulan')] ?? request('bulan'); @endphp
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                    Bulan: {{ $bNama }}
                                    <a href="{{ $removeFilterUrl('bulan') }}" class="hover:text-red-500 font-bold">&times;</a>
                                </span>
                            @endif
                            @if (request('per_page') && request('per_page') != '10')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                    {{ request('per_page') == 'all' ? 'Semua Baris' : request('per_page') . ' Baris' }}
                                    <a href="{{ $removeFilterUrl('per_page') }}" class="hover:text-red-500 font-bold">&times;</a>
                                </span>
                            @endif
                            @if (request('sort') === 'tanggal_kegiatan')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                    Urut: {{ request('direction') === 'asc' ? 'Kegiatan (Terendah ke Tertinggi)' : 'Kegiatan (Tertinggi ke Rendah)' }}
                                    <a href="{{ $removeSortUrl }}" class="hover:text-red-500 font-bold" title="Hapus urutan">&times;</a>
                                </span>
                            @endif

                            <a href="{{ route('laporan.index', ['reset' => 1]) }}"
                               class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-medium ml-1">
                                Reset Semua
                            </a>
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="openFilterDrawer()"
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg transition {{ $hasActiveFilter ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-700 shadow-sm' : 'bg-gray-50 dark:bg-gray-700/60 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 shadow-sm' }}">
                        <svg class="w-3.5 h-3.5 {{ $hasActiveFilter ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span>Filter & Pencarian</span>
                        @if ($hasActiveFilter)
                            <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold text-white bg-indigo-600 rounded-full">
                                {{ $activeFilterCount }}
                            </span>
                        @endif
                    </button>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr class="text-left text-gray-500 dark:text-gray-400">
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Perihal</th>
                                <th class="px-4 py-3">Petugas</th>
                                <th class="px-4 py-3">Lokasi Tujuan Kegiatan</th>
                                <th class="px-4 py-3 select-none">
                                    @php
                                        $isSortedTanggal = request('sort') === 'tanggal_kegiatan';
                                        $currentDir = strtolower(request('direction', 'desc'));
                                        $nextDir = ($isSortedTanggal && $currentDir === 'desc') ? 'asc' : 'desc';
                                        $sortTanggalUrl = request()->fullUrlWithQuery([
                                            'sort' => 'tanggal_kegiatan',
                                            'direction' => $nextDir,
                                            'page' => 1,
                                        ]);
                                    @endphp
                                    <a href="{{ $sortTanggalUrl }}"
                                       class="group inline-flex items-center gap-1.5 font-semibold transition-colors duration-150 {{ $isSortedTanggal ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}"
                                       title="{{ $isSortedTanggal ? ($currentDir === 'asc' ? 'Saat ini: Terendah ke Tertinggi. Klik untuk urutkan Tertinggi ke Rendah' : 'Saat ini: Tertinggi ke Rendah. Klik untuk urutkan Terendah ke Tertinggi') : 'Klik untuk mengurutkan dari nilai tertinggi ke rendah' }}">
                                        <span>Tanggal Kegiatan</span>
                                        <span class="inline-flex flex-col items-center justify-center text-[10px] leading-[8px] {{ $isSortedTanggal ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}">
                                            <svg class="w-2.5 h-2.5 {{ ($isSortedTanggal && $currentDir === 'asc') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'opacity-40' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                            </svg>
                                            <svg class="w-2.5 h-2.5 -mt-0.5 {{ ($isSortedTanggal && $currentDir === 'desc') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'opacity-40' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                        @if ($isSortedTanggal)
                                            <span class="text-[10px] uppercase tracking-wider font-bold px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300">
                                                {{ $currentDir === 'asc' ? 'Terendah' : 'Tertinggi' }}
                                            </span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3">Tempat/Tanggal</th>
                                <th class="px-4 py-3 text-center">Uraian</th>
                                <th class="px-4 py-3 text-center">Foto</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                            @forelse ($laporans as $laporan)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $laporan->id }}</td>
                                    <td class="px-4 py-3">
                                        <button type="button"
                                                @click="openModal({{ $laporan->id }})"
                                                class="font-medium text-left text-indigo-600 dark:text-indigo-400 hover:underline">
                                            {{ $laporan->perihal_laporan }}
                                        </button>
                                    </td>
                                    <td class="px-4 py-3">{{ $laporan->pegawai->nama }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                        {{ $laporan->lokasi_tujuan ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                        @php
                                            $tglKegiatan = $laporan->tanggal_kegiatan_list;
                                        @endphp
                                        @if ($tglKegiatan->isEmpty())
                                            <span class="text-gray-400">-</span>
                                        @elseif ($tglKegiatan->count() === 1)
                                            {{ $tglKegiatan[0]->format('d-m-Y') }}
                                        @elseif ($tglKegiatan->count() === 2)
                                            {{ $tglKegiatan[0]->format('d-m-Y') }}<br>
                                            {{ $tglKegiatan[1]->format('d-m-Y') }}
                                        @else
                                            <span title="{{ $tglKegiatan->map(fn($d) => $d->format('d-m-Y'))->implode(', ') }}">
                                                {{ $tglKegiatan->first()->format('d-m-Y') }} s.d.<br>
                                                {{ $tglKegiatan->last()->format('d-m-Y') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                        {{ $laporan->tempat_laporan }},<br>
                                        {{ $laporan->tanggal_laporan?->format('d-m-Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-center">{{ $laporan->uraians_count }}</td>
                                    <td class="px-4 py-3 text-center">{{ $laporan->dokumentasis_count }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                            <button type="button"
                                                    @click="openModal({{ $laporan->id }})"
                                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium">
                                                Lihat
                                            </button>
                                            <a href="{{ route('laporan.edit', $laporan) }}" class="text-gray-600 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400">Edit</a>
                                            <form action="{{ route('laporan.duplicate', $laporan) }}" method="POST" onsubmit="return confirm('Duplikat laporan ini?')">
                                               @csrf
                                               <button type="submit" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium px-2 py-1 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/40 dark:hover:bg-blue-900/60 rounded transition-colors text-sm">Duplikat</button>
                                           </form>

                                           <form action="{{ route('laporan.destroy', $laporan) }}" method="POST" onsubmit="return confirm('Hapus laporan ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-gray-600 dark:text-gray-300 hover:text-rose-600 dark:hover:text-rose-400">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        @if (request()->hasAny(['search', 'pegawai_id', 'tahun', 'bulan']) && (request('search') || request('pegawai_id') || request('tahun') || request('bulan')))
                                            Tidak ada laporan yang sesuai dengan filter pencarian.
                                            <a href="{{ route('laporan.index') }}" class="text-indigo-600 dark:text-indigo-400 underline ml-1">Reset filter</a>
                                        @else
                                            Belum ada laporan. Silakan buat laporan baru.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
                <div>
                    {{ $laporans->links() }}
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 self-end sm:self-auto">
                    <span>Tampilkan per halaman:</span>
                    <select onchange="window.location.href = this.value"
                            class="py-1 px-2.5 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 focus:ring-indigo-500">
                        @foreach (['10' => '10 baris', '25' => '25 baris', '50' => '50 baris', '100' => '100 baris', 'all' => 'Semua'] as $val => $lbl)
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => $val, 'page' => 1]) }}"
                                    @selected(request('per_page', '10') == $val)>
                                {{ $lbl }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ===================== MODAL PREVIEW POPUP ===================== --}}
        <div x-show="showModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            
            {{-- Backdrop --}}
            <div x-show="showModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="closeModal()"
                 class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>

            {{-- Dialog panel --}}
            <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
                <div x-show="showModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     @click.stop
                     class="relative w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-middle shadow-2xl transition-all border border-gray-100 dark:border-gray-700/80 flex flex-col max-h-[90vh]">
                    
                    <template x-if="selected">
                        <div class="flex flex-col h-full overflow-hidden">
                            {{-- Modal Header --}}
                            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700/80 flex items-start justify-between gap-4 bg-gray-50/50 dark:bg-gray-900/40 shrink-0">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 text-xs font-mono font-semibold rounded bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                            Laporan #<span x-text="selected.id"></span>
                                        </span>
                                        <span class="text-xs text-gray-400" x-text="selected.tanggal_laporan"></span>
                                    </div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 leading-snug"
                                        x-text="selected.perihal"></h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1"
                                       x-text="selected.judul"></p>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    {{-- Tombol Detail di Header --}}
                                    <a :href="selected.show_url"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg shadow-sm transition">
                                        <span>Detail</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>

                                    <button type="button"
                                            @click="closeModal()"
                                            class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                            title="Tutup (Esc)">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Modal Body (Scrollable) --}}
                            <div class="p-6 overflow-y-auto space-y-6 text-sm text-gray-700 dark:text-gray-300">
                                {{-- Card Informasi Utama --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Kolom Kiri: Petugas & Tujuan --}}
                                    <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60 space-y-3">
                                        <div class="flex items-start gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <div class="space-y-0.5">
                                                <div class="text-xs text-gray-500 dark:text-gray-400">Petugas / Pelaksana</div>
                                                <div class="font-semibold text-gray-900 dark:text-gray-100" x-text="selected.pegawai.nama"></div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono" x-text="'NIP: ' + selected.pegawai.nip"></div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="selected.pegawai.unit_kerja"></div>
                                            </div>
                                        </div>

                                        <div class="pt-2 border-t border-gray-200/60 dark:border-gray-700/60 text-xs space-y-1">
                                            <div class="text-gray-500 dark:text-gray-400">Kepada Yth. (Tujuan Surat):</div>
                                            <div class="font-medium text-gray-800 dark:text-gray-200" x-text="selected.tujuan_surat || '-'"></div>
                                        </div>
                                    </div>

                                    {{-- Kolom Kanan: Lokasi & Tanggal --}}
                                    <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60 space-y-3">
                                        <div class="flex items-start gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </div>
                                            <div class="space-y-0.5">
                                                <div class="text-xs text-gray-500 dark:text-gray-400">Lokasi Tujuan Kegiatan</div>
                                                <div class="font-semibold text-gray-900 dark:text-gray-100" x-text="selected.lokasi_tujuan"></div>
                                            </div>
                                        </div>

                                        <div class="pt-2 border-t border-gray-200/60 dark:border-gray-700/60 text-xs space-y-1.5">
                                            <div class="flex justify-between">
                                                <span class="text-gray-500 dark:text-gray-400">Tanggal Kegiatan:</span>
                                                <span class="font-medium text-gray-800 dark:text-gray-200" x-text="selected.tanggal_kegiatan"></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500 dark:text-gray-400">Tempat Penandatanganan:</span>
                                                <span class="font-medium text-gray-800 dark:text-gray-200" x-text="selected.tempat_laporan"></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500 dark:text-gray-400">Tanggal Laporan:</span>
                                                <span class="font-medium text-gray-800 dark:text-gray-200" x-text="selected.tanggal_laporan"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Pembiayaan Kegiatan --}}
                                <template x-if="selected.pembiayaan">
                                    <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60">
                                        <div class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-2">
                                            Pembiayaan Kegiatan
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 text-xs">
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Program:</span>
                                                <div class="font-medium text-gray-800 dark:text-gray-200" x-text="selected.pembiayaan.program || '-'"></div>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Kegiatan:</span>
                                                <div class="font-medium text-gray-800 dark:text-gray-200" x-text="selected.pembiayaan.kegiatan || '-'"></div>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">RO:</span>
                                                <div class="font-medium text-gray-800 dark:text-gray-200" x-text="selected.pembiayaan.ro || '-'"></div>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Komponen:</span>
                                                <div class="font-medium text-gray-800 dark:text-gray-200" x-text="selected.pembiayaan.komponen || '-'"></div>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Akun:</span>
                                                <div class="font-medium text-gray-800 dark:text-gray-200" x-text="selected.pembiayaan.akun || '-'"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                {{-- Lampiran 1 — Uraian Kegiatan --}}
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between border-b pb-2 border-gray-200 dark:border-gray-700">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-800 dark:text-gray-200">Lampiran 1 — Uraian Kegiatan</span>
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300"
                                                  x-text="selected.uraians.length + ' item'"></span>
                                        </div>
                                    </div>

                                    <template x-if="selected.uraians.length > 0">
                                        <div class="space-y-4">
                                            <template x-for="(u, idx) in selected.uraians" :key="idx">
                                                <div class="p-5 sm:p-6 rounded-xl bg-gray-50/80 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 space-y-3"
                                                     style="padding: 1.25rem 1.5rem;">
                                                    <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-gray-200/80 dark:border-gray-700/80 text-xs">
                                                        <span class="font-bold text-sm text-indigo-600 dark:text-indigo-400" x-text="'Kegiatan #' + (idx + 1)"></span>
                                                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 font-medium">
                                                            <span x-text="u.tanggal"></span>
                                                            <span x-show="u.jam" x-text="'• ' + u.jam"></span>
                                                        </div>
                                                    </div>
                                                    <div class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 leading-relaxed max-w-none space-y-2 [&>p]:mb-2.5 last:[&>p]:mb-0"
                                                         style="line-height: 1.65;"
                                                         x-html="u.html"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="selected.uraians.length === 0">
                                        <div class="text-xs text-center py-4 text-gray-400 italic">Belum ada uraian kegiatan.</div>
                                    </template>
                                </div>

                                {{-- Lampiran 2 — Dokumentasi Foto --}}
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between border-b pb-2 border-gray-200 dark:border-gray-700">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-800 dark:text-gray-200">Lampiran 2 — Foto Dokumentasi</span>
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300"
                                                  x-text="selected.dokumentasis.length + ' foto'"></span>
                                        </div>
                                    </div>

                                    <template x-if="selected.dokumentasis.length > 0">
                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                            <template x-for="(dok, didx) in selected.dokumentasis" :key="didx">
                                                <div class="group relative rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-black/5">
                                                    <a :href="dok.url" target="_blank" class="block aspect-video overflow-hidden">
                                                        <img :src="dok.thumb_url"
                                                             :alt="dok.keterangan || 'Dokumentasi'"
                                                             class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                                    </a>
                                                    <p x-show="dok.keterangan"
                                                       class="p-1.5 text-[11px] text-gray-600 dark:text-gray-300 truncate"
                                                       :title="dok.keterangan"
                                                       x-text="dok.keterangan"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="selected.dokumentasis.length === 0">
                                        <div class="text-xs text-center py-4 text-gray-400 italic">Belum ada foto dokumentasi.</div>
                                    </template>
                                </div>
                            </div>

                            {{-- Modal Footer --}}
                            <div class="px-6 py-4 bg-gray-50/70 dark:bg-gray-900/60 border-t border-gray-100 dark:border-gray-700/80 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                                <div class="flex items-center gap-2 w-full sm:w-auto">
                                    <a :href="selected.pdf_url" target="_blank"
                                       class="px-3 py-1.5 text-xs bg-rose-600 hover:bg-rose-700 text-white rounded-lg transition font-medium">
                                        Cetak PDF
                                    </a>
                                    <a :href="selected.word_url"
                                       class="px-3 py-1.5 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium">
                                        Word (.docx)
                                    </a>
                                    <a :href="selected.edit_url"
                                       class="px-3 py-1.5 text-xs bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition font-medium">
                                        Edit
                                    </a>
                                </div>

                                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                                    <button type="button"
                                            @click="closeModal()"
                                            class="px-4 py-2 text-xs text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                        Tutup
                                    </button>

                                    {{-- TOMBOL DETAIL UTAMA --}}
                                    <a :href="selected.show_url"
                                       class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                                        <span>Detail</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ===================== SLIDE-OVER DRAWER FILTER (SEBELAH KANAN) ===================== --}}
        <div x-cloak
             x-show="showFilterDrawer"
             class="relative z-50"
             aria-labelledby="slide-over-filter-title"
             role="dialog"
             aria-modal="true">

            {{-- Backdrop background --}}
            <div x-show="showFilterDrawer"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="closeFilterDrawer()"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                        <div x-show="showFilterDrawer"
                             x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400"
                             x-transition:enter-start="translate-x-full"
                             x-transition:enter-end="translate-x-0"
                             x-transition:leave="transform transition ease-in-out duration-300 sm:duration-400"
                             x-transition:leave-start="translate-x-0"
                             x-transition:leave-end="translate-x-full"
                             class="pointer-events-auto w-screen max-w-md">

                            <form method="GET" action="{{ route('laporan.index') }}"
                                  class="flex h-full flex-col bg-white dark:bg-gray-800 shadow-2xl border-l border-gray-200 dark:border-gray-700">

                                @if (request('sort'))
                                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                                @endif
                                @if (request('direction'))
                                    <input type="hidden" name="direction" value="{{ request('direction') }}">
                                @endif

                                {{-- Drawer Header --}}
                                <div class="px-6 py-5 bg-gray-50/90 dark:bg-gray-900/90 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between shrink-0">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100" id="slide-over-filter-title">
                                                Filter & Pencarian
                                            </h2>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                Saring daftar laporan kegiatan
                                            </p>
                                        </div>
                                    </div>

                                    <button type="button"
                                            @click="closeFilterDrawer()"
                                            class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-200/50 dark:hover:bg-gray-700 transition">
                                        <span class="sr-only">Tutup panel</span>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                {{-- Drawer Body --}}
                                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                                    {{-- Cari kata kunci --}}
                                    <div class="space-y-1.5">
                                        <label for="drawer_search" class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                            Cari Kata Kunci
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                            </span>
                                            <input type="text" id="drawer_search" name="search" value="{{ request('search') }}"
                                                   placeholder="Perihal, lokasi tujuan, petugas, uraian..."
                                                   class="w-full pl-9 pr-3 py-2.5 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-800/40">
                                        </div>
                                        <p class="text-[11px] text-gray-400">Mencakup judul, perihal, petugas, lokasi tujuan, dan uraian.</p>
                                    </div>

                                    {{-- Petugas --}}
                                    <div class="space-y-1.5">
                                        <label for="drawer_pegawai_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                            Petugas Pelaksana
                                        </label>
                                        <select id="drawer_pegawai_id" name="pegawai_id"
                                                class="w-full py-2.5 px-3 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-800/40">
                                            <option value="">Semua Petugas</option>
                                            @foreach ($pegawais as $p)
                                                <option value="{{ $p->id }}" @selected(request('pegawai_id') == $p->id)>
                                                    {{ $p->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Tahun & Bulan --}}
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1.5">
                                            <label for="drawer_tahun" class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                                Tahun
                                            </label>
                                            <select id="drawer_tahun" name="tahun"
                                                    class="w-full py-2.5 px-3 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-800/40">
                                                <option value="">Semua Tahun</option>
                                                @foreach ($tahuns as $thn)
                                                    <option value="{{ $thn }}" @selected(request('tahun') == $thn)>
                                                        {{ $thn }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="space-y-1.5">
                                            <label for="drawer_bulan" class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                                Bulan
                                            </label>
                                            <select id="drawer_bulan" name="bulan"
                                                    class="w-full py-2.5 px-3 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-800/40">
                                                <option value="">Semua Bulan</option>
                                                @foreach ($bulans as $num => $namaBulan)
                                                    <option value="{{ $num }}" @selected(request('bulan') == $num)>
                                                        {{ $namaBulan }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    {{-- Pilihan Jumlah Baris --}}
                                    <div class="space-y-1.5">
                                        <label for="drawer_per_page" class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                            Baris Per Halaman
                                        </label>
                                        <select id="drawer_per_page" name="per_page"
                                                class="w-full py-2.5 px-3 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-800/40">
                                            <option value="10" @selected(request('per_page', '10') == '10')>10 Baris (Standar)</option>
                                            <option value="25" @selected(request('per_page') == '25')>25 Baris</option>
                                            <option value="50" @selected(request('per_page') == '50')>50 Baris</option>
                                            <option value="100" @selected(request('per_page') == '100')>100 Baris</option>
                                            <option value="all" @selected(request('per_page') == 'all')>Semua Baris (Tanpa Paginasi)</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Drawer Footer --}}
                                <div class="px-6 py-4 bg-gray-50/90 dark:bg-gray-900/90 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-3 shrink-0">
                                    @if ($hasActiveFilter)
                                        <a href="{{ route('laporan.index', ['reset' => 1]) }}"
                                           class="px-3 py-2 text-xs font-medium text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 hover:underline">
                                            Reset Semua
                                        </a>
                                    @else
                                        <button type="button"
                                                @click="closeFilterDrawer()"
                                                class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                                            Batal
                                        </button>
                                    @endif

                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                @click="closeFilterDrawer()"
                                                class="px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                                            Tutup
                                        </button>
                                        <button type="submit"
                                                class="inline-flex items-center gap-2 px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>Terapkan Filter</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
