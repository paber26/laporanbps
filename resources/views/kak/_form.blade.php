@php
    /** @var \App\Models\Kak|null $kak */
    $isEdit = isset($kak) && $kak?->exists;

    // Nilai awal pada halaman "Buat KAK": memakai contoh dari controller.
    $defaults = $defaults ?? [];

    $val = fn ($field, $default = '') => old($field, $isEdit ? ($kak->{$field} instanceof \Carbon\Carbon ? $kak->{$field}->format('Y-m-d') : $kak->{$field}) : ($defaults[$field] ?? $default));

    // Baris anggaran awal: prioritaskan old(), lalu data model, lalu contoh.
    $anggaranRows = old('anggarans');
    if ($anggaranRows === null) {
        if ($isEdit && $kak->anggarans->count()) {
            $anggaranRows = $kak->anggarans->map(fn ($a) => [
                'kode_mak' => $a->kode_mak,
                'deskripsi' => $a->deskripsi,
                'volume' => $a->volume,
                'satuan' => $a->satuan,
                'harga_satuan' => $a->harga_satuan,
                'jumlah_biaya' => $a->jumlah_biaya,
            ])->toArray();
        } else {
            $anggaranRows = $defaultAnggarans ?? [['kode_mak' => '', 'deskripsi' => '', 'volume' => '', 'satuan' => '', 'harga_satuan' => '', 'jumlah_biaya' => '']];
        }
    }

    // Bagian isi KAK sesuai struktur dokumen asli (A s.d. H).
    $bagianEditor = [
        'dasar_hukum' => 'A. Dasar Hukum',
        'gambaran_umum' => 'B. Gambaran Umum',
        'maksud_tujuan' => 'C. Maksud dan Tujuan',
        'keluaran' => 'D. Keluaran/Output',
        'pelaksana_kegiatan' => 'E. Pelaksana Kegiatan',
        'jadwal_lokasi' => 'F. Jadwal dan Lokasi Kegiatan',
        'sumber_pembiayaan' => 'G. Sumber Pembiayaan',
        'penutup' => 'H. Penutup',
    ];
@endphp

<form action="{{ $action }}" method="POST" id="kak-form">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 px-4 py-3 rounded-md mb-6">
            <p class="font-medium">Periksa kembali isian berikut:</p>
            <ul class="list-disc list-inside text-sm mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- ===================== KOLOM KIRI ===================== --}}
        <div class="lg:col-span-4 space-y-6">
            {{-- ===================== DATA UMUM ===================== --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 border-b pb-2">Data Umum KAK</h3>

                <div>
                    <x-input-label for="judul" value="Judul Kegiatan" />
                    <x-text-input id="judul" name="judul" type="text" class="mt-1 block w-full"
                        :value="$val('judul')" required placeholder="mis: Perjalanan Dalam Rangka Rapat Koordinasi Nasional Sensus Ekonomi 2026" />
                    <x-input-error :messages="$errors->get('judul')" class="mt-1" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="tahun" value="Tahun Anggaran" />
                        <x-text-input id="tahun" name="tahun" type="text" class="mt-1 block w-full"
                            :value="$val('tahun')" placeholder="mis: 2026" />
                        <x-input-error :messages="$errors->get('tahun')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="unit_kerja" value="Unit Kerja (kop)" />
                        <x-text-input id="unit_kerja" name="unit_kerja" type="text" class="mt-1 block w-full"
                            :value="$val('unit_kerja')" placeholder="mis: Badan Pusat Statistik Kabupaten Minahasa Selatan" />
                        <x-input-error :messages="$errors->get('unit_kerja')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-input-label for="pegawai_id" value="Petugas (opsional, utk tanda tangan foto)" />
                    <select id="pegawai_id" name="pegawai_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm">
                        <option value="">-- Pilih Petugas --</option>
                        @foreach ($pegawais as $p)
                            <option value="{{ $p->id }}" @selected($val('pegawai_id') == $p->id)>
                                {{ $p->nama }} — {{ $p->nip }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('pegawai_id')" class="mt-1" />
                </div>
            </div>

            {{-- ===================== RINCIAN ANGGARAN ===================== --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between border-b pb-2 mb-4">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200">Rincian Anggaran (bagian G)</h3>
                    <button type="button" id="btn-add-anggaran" class="px-3 py-1.5 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700">+ Tambah Baris</button>
                </div>

                <div id="anggaran-container" class="space-y-4">
                    @foreach ($anggaranRows as $i => $row)
                        <div class="anggaran-row border dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900/40">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Baris #<span class="anggaran-num">{{ $i + 1 }}</span></span>
                                <button type="button" class="btn-remove-anggaran text-rose-600 text-sm hover:underline">Hapus</button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">Kode MAK</label>
                                    <input type="text" name="anggarans[{{ $i }}][kode_mak]" value="{{ $row['kode_mak'] ?? '' }}" placeholder="mis: 2902.BMA.006"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">Satuan</label>
                                    <input type="text" name="anggarans[{{ $i }}][satuan]" value="{{ $row['satuan'] ?? '' }}" placeholder="mis: O-P"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="text-xs text-gray-500 dark:text-gray-400">Deskripsi</label>
                                <input type="text" name="anggarans[{{ $i }}][deskripsi]" value="{{ $row['deskripsi'] ?? '' }}" placeholder="mis: Perjalanan dinas dalam rangka rakornas"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm">
                            </div>
                            <div class="grid grid-cols-3 gap-3 mt-3">
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">Volume</label>
                                    <input type="number" step="0.01" name="anggarans[{{ $i }}][volume]" value="{{ $row['volume'] ?? '' }}" placeholder="1"
                                        class="ang-volume mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">Harga Satuan (Rp)</label>
                                    <input type="number" step="0.01" name="anggarans[{{ $i }}][harga_satuan]" value="{{ $row['harga_satuan'] ?? '' }}" placeholder="7000000"
                                        class="ang-harga mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">Jumlah Biaya (Rp)</label>
                                    <input type="number" step="0.01" name="anggarans[{{ $i }}][jumlah_biaya]" value="{{ $row['jumlah_biaya'] ?? '' }}" placeholder="otomatis"
                                        class="ang-jumlah mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm">
                                    <p class="text-[10px] text-gray-400 mt-1">Kosongkan utk otomatis = Volume × Harga.</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ===================== PENANDATANGANAN ===================== --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 border-b pb-2">Penandatanganan</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="tempat_penandatanganan" value="Tempat" />
                        <x-text-input id="tempat_penandatanganan" name="tempat_penandatanganan" type="text" class="mt-1 block w-full"
                            :value="$val('tempat_penandatanganan')" placeholder="mis: Minahasa Selatan" />
                        <x-input-error :messages="$errors->get('tempat_penandatanganan')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="tanggal_penandatanganan" value="Tanggal" />
                        <x-text-input id="tanggal_penandatanganan" name="tanggal_penandatanganan" type="date" class="mt-1 block w-full"
                            :value="$val('tanggal_penandatanganan')" />
                        <x-input-error :messages="$errors->get('tanggal_penandatanganan')" class="mt-1" />
                    </div>
                </div>

                <div class="border dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900/40 space-y-3">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Kolom Kiri — Ketua Tim Pelaksana</p>
                    <x-text-input name="ttd_kiri_jabatan" type="text" class="block w-full" :value="$val('ttd_kiri_jabatan')" placeholder="mis: Ketua Tim Pelaksana SE2026" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <x-text-input name="ttd_kiri_nama" type="text" class="block w-full" :value="$val('ttd_kiri_nama')" placeholder="mis: Johanes, S.ST" />
                        <x-text-input name="ttd_kiri_nip" type="text" class="block w-full" :value="$val('ttd_kiri_nip')" placeholder="NIP" />
                    </div>
                </div>

                <div class="border dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900/40 space-y-3">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Kolom Kanan — Pejabat Pembuat Komitmen</p>
                    <x-text-input name="ttd_kanan_jabatan" type="text" class="block w-full" :value="$val('ttd_kanan_jabatan')" placeholder="mis: Pejabat Pembuat Komitmen" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <x-text-input name="ttd_kanan_nama" type="text" class="block w-full" :value="$val('ttd_kanan_nama')" placeholder="mis: Afwin Fauzy Akhsan S.Tr.Stat" />
                        <x-text-input name="ttd_kanan_nip" type="text" class="block w-full" :value="$val('ttd_kanan_nip')" placeholder="NIP" />
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================== KOLOM KANAN ===================== --}}
        <div class="lg:col-span-8 space-y-6">
            @foreach ($bagianEditor as $kolom => $label)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 border-b pb-2 mb-4">{{ $label }}</h3>
                    <textarea class="kak-editor block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm"
                        name="{{ $kolom }}" rows="5">{{ $val($kolom) }}</textarea>
                    <x-input-error :messages="$errors->get($kolom)" class="mt-1" />
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Kosongkan bila bagian ini tidak diperlukan — bagian yang kosong otomatis dilewati saat cetak.</p>
                </div>
            @endforeach

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('kak.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</a>
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    {{ $isEdit ? 'Perbarui KAK' : 'Simpan KAK' }}
                </button>
            </div>
        </div>
    </div>
</form>

<template id="tpl-anggaran">
    <div class="anggaran-row border dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900/40">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Baris #<span class="anggaran-num"></span></span>
            <button type="button" class="btn-remove-anggaran text-rose-600 text-sm hover:underline">Hapus</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="text-xs text-gray-500 dark:text-gray-400">Kode MAK</label>
                <input type="text" name="anggarans[__I__][kode_mak]" placeholder="mis: 2902.BMA.006"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 dark:text-gray-400">Satuan</label>
                <input type="text" name="anggarans[__I__][satuan]" placeholder="mis: O-P"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm">
            </div>
        </div>
        <div class="mt-3">
            <label class="text-xs text-gray-500 dark:text-gray-400">Deskripsi</label>
            <input type="text" name="anggarans[__I__][deskripsi]" placeholder="mis: Perjalanan dinas dalam rangka rakornas"
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm">
        </div>
        <div class="grid grid-cols-3 gap-3 mt-3">
            <div>
                <label class="text-xs text-gray-500 dark:text-gray-400">Volume</label>
                <input type="number" step="0.01" name="anggarans[__I__][volume]" placeholder="1"
                    class="ang-volume mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 dark:text-gray-400">Harga Satuan (Rp)</label>
                <input type="number" step="0.01" name="anggarans[__I__][harga_satuan]" placeholder="7000000"
                    class="ang-harga mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 dark:text-gray-400">Jumlah Biaya (Rp)</label>
                <input type="number" step="0.01" name="anggarans[__I__][jumlah_biaya]" placeholder="otomatis"
                    class="ang-jumlah mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm">
            </div>
        </div>
    </div>
</template>

@vite(['resources/js/ckeditor-setup.js'])
<script>
(function () {
    // CKEditor bundle dimuat async lewat Vite. Tunggu bila belum siap.
    let hasCK = !!window.CKEditorBundle;
    function initAll() {
        document.querySelectorAll('.kak-editor').forEach(ta => {
            if (ta._editor) return;
            const { ClassicEditor, plugins } = window.CKEditorBundle;
            ClassicEditor.create(ta, {
                licenseKey: 'GPL',
                plugins,
                toolbar: {
                    items: [
                        'undo', 'redo', '|',
                        'selectAll', '|',
                        'heading', '|',
                        'fontFamily', 'fontSize', 'fontColor', 'fontBackgroundColor', '|',
                        'bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', 'code', 'removeFormat', '|',
                        'alignment', '|',
                        'bulletedList', 'numberedList', 'todoList', '|',
                        'outdent', 'indent', '|',
                        'link', 'blockQuote', 'codeBlock', 'insertTable', 'horizontalLine', 'highlight', 'specialCharacters', '|',
                        'sourceEditing', 'showBlocks', 'fullscreen',
                    ],
                    shouldNotGroupWhenFull: false,
                },
                table: {
                    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties'],
                },
            }).then(editor => {
                ta._editor = editor;
            }).catch(err => console.error('CKEditor gagal:', err));
        });
    }
    if (hasCK) {
        initAll();
    } else {
        window.addEventListener('ckeditor5:ready', initAll, { once: true });
    }

    // Sinkronkan editor -> textarea sebelum submit.
    document.getElementById('kak-form').addEventListener('submit', function () {
        document.querySelectorAll('.kak-editor').forEach(ta => {
            if (ta._editor) ta._editor.updateSourceElement();
        });
    });

    // ====== Repeater rincian anggaran ======
    const container = document.getElementById('anggaran-container');
    let angIndex = {{ count($anggaranRows) }};

    function renumber() {
        container.querySelectorAll('.anggaran-row .anggaran-num').forEach((el, i) => el.textContent = i + 1);
    }

    function addAnggaranRow() {
        const tpl = document.getElementById('tpl-anggaran').innerHTML.replaceAll('__I__', angIndex++);
        const wrap = document.createElement('div');
        wrap.innerHTML = tpl.trim();
        container.appendChild(wrap.firstElementChild);
        renumber();
    }

    document.getElementById('btn-add-anggaran').addEventListener('click', addAnggaranRow);

    container.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-remove-anggaran')) {
            e.target.closest('.anggaran-row').remove();
            renumber();
        }
    });

    // Hitung otomatis Jumlah Biaya = Volume × Harga Satuan (hanya jika Jumlah kosong).
    container.addEventListener('input', function (e) {
        if (!e.target.classList.contains('ang-volume') && !e.target.classList.contains('ang-harga')) return;
        const row = e.target.closest('.anggaran-row');
        const vol = parseFloat(row.querySelector('.ang-volume').value) || 0;
        const harga = parseFloat(row.querySelector('.ang-harga').value) || 0;
        const jumlah = row.querySelector('.ang-jumlah');
        if (jumlah.value === '' || !isNaN(parseFloat(jumlah.value)) && parseFloat(jumlah.value) === 0) {
            jumlah.value = (vol * harga) ? (vol * harga) : '';
        }
    });
})();
</script>
