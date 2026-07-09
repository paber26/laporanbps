@php
    /** @var \App\Models\Laporan|null $laporan */
    $isEdit = isset($laporan) && $laporan?->exists;

    // Baris uraian awal: prioritaskan old() (validasi gagal), lalu data model, lalu 1 baris kosong.
    $uraianRows = old('uraians');
    if ($uraianRows === null) {
        if ($isEdit && $laporan->uraians->count()) {
            $uraianRows = $laporan->uraians->map(fn ($u) => [
                'tanggal_kegiatan' => optional($u->tanggal_kegiatan)->format('Y-m-d'),
                'jam_mulai' => $u->jam_mulai,
                'jam_selesai' => $u->jam_selesai,
                'uraian_text' => $u->uraian_text,
            ])->toArray();
        } else {
            $uraianRows = [['tanggal_kegiatan' => '', 'jam_mulai' => '', 'jam_selesai' => '', 'uraian_text' => '']];
        }
    }

    $val = fn ($field, $default = '') => old($field, $isEdit ? ($laporan->{$field} instanceof \Carbon\Carbon ? $laporan->{$field}->format('Y-m-d') : $laporan->{$field}) : $default);
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" id="laporan-form">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-md mb-6">
            <p class="font-medium">Periksa kembali isian berikut:</p>
            <ul class="list-disc list-inside text-sm mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===================== DATA UMUM ===================== --}}
    <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
        <h3 class="font-semibold text-gray-800 border-b pb-2">Data Umum Laporan</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="pegawai_id" value="Petugas (Pilih NIP)" />
                <select id="pegawai_id" name="pegawai_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    <option value="">-- Pilih Petugas --</option>
                    @foreach ($pegawais as $p)
                        <option value="{{ $p->id }}" data-unit="{{ $p->unit_kerja }}"
                            @selected($val('pegawai_id') == $p->id)>
                            {{ $p->nip }} — {{ $p->nama }}
                        </option>
                    @endforeach
                </select>
                <p id="pegawai-info" class="text-xs text-gray-500 mt-1"></p>
                <x-input-error :messages="$errors->get('pegawai_id')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="pembiayaan_id" value="Pembiayaan Kegiatan" />
                <select id="pembiayaan_id" name="pembiayaan_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">-- Pilih Pembiayaan --</option>
                    @foreach ($pembiayaans as $p)
                        <option value="{{ $p->id }}"
                            data-program="{{ $p->program }}" data-kegiatan="{{ $p->kegiatan }}"
                            data-ro="{{ $p->ro }}" data-komponen="{{ $p->komponen }}" data-akun="{{ $p->akun }}"
                            @selected($val('pembiayaan_id') == $p->id)>
                            {{ Str::limit($p->program, 40) }} — {{ Str::limit($p->kegiatan, 40) }}
                        </option>
                    @endforeach
                </select>
                <div id="pembiayaan-info" class="text-xs text-gray-600 mt-1 hidden bg-gray-50 border rounded p-2 space-y-0.5"></div>
                <x-input-error :messages="$errors->get('pembiayaan_id')" class="mt-1" />
            </div>
        </div>

        <div>
            <x-input-label for="judul_laporan" value="Judul Laporan (kop besar)" />
            <x-text-input id="judul_laporan" name="judul_laporan" type="text" class="mt-1 block w-full"
                :value="$val('judul_laporan')" required placeholder="mis: Laporan Perjalanan Dinas Supervisi Pelaksanaan Groundcheck ..." />
            <x-input-error :messages="$errors->get('judul_laporan')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="perihal_laporan" value="Perihal Laporan" />
            <x-text-input id="perihal_laporan" name="perihal_laporan" type="text" class="mt-1 block w-full"
                :value="$val('perihal_laporan')" required placeholder="mis: Laporan Perjalanan Dinas Groundcheck ..." />
            <x-input-error :messages="$errors->get('perihal_laporan')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="tujuan_surat" value="Kepada Yth. (Tujuan Surat)" />
            <x-text-input id="tujuan_surat" name="tujuan_surat" type="text" class="mt-1 block w-full"
                :value="$val('tujuan_surat')" required placeholder="mis: Kepala BPS Kabupaten Minahasa Selatan" />
            <x-input-error :messages="$errors->get('tujuan_surat')" class="mt-1" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <x-input-label for="tempat_laporan" value="Tempat Penandatanganan" />
                <x-text-input id="tempat_laporan" name="tempat_laporan" type="text" class="mt-1 block w-full"
                    :value="$val('tempat_laporan')" required placeholder="mis: Amurang Barat" />
                <x-input-error :messages="$errors->get('tempat_laporan')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="tanggal_laporan" value="Tanggal Laporan" />
                <x-text-input id="tanggal_laporan" name="tanggal_laporan" type="date" class="mt-1 block w-full"
                    :value="$val('tanggal_laporan')" required />
                <x-input-error :messages="$errors->get('tanggal_laporan')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="lokasi_tujuan" value="Lokasi Tujuan Kegiatan" />
                <x-text-input id="lokasi_tujuan" name="lokasi_tujuan" type="text" class="mt-1 block w-full"
                    :value="$val('lokasi_tujuan')" required placeholder="mis: Minahasa Selatan" />
                <x-input-error :messages="$errors->get('lokasi_tujuan')" class="mt-1" />
            </div>
        </div>
    </div>

    {{-- ===================== LAMPIRAN 1: URAIAN ===================== --}}
    <div class="bg-white shadow-sm rounded-lg p-6 mt-6">
        <div class="flex items-center justify-between border-b pb-2 mb-4">
            <h3 class="font-semibold text-gray-800">Lampiran 1 — Uraian Kegiatan</h3>
            <button type="button" id="btn-add-uraian" class="px-3 py-1.5 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700">+ Tambah Uraian</button>
        </div>

        <div id="uraian-container" class="space-y-4">
            @foreach ($uraianRows as $i => $row)
                <div class="uraian-row border rounded-lg p-4 bg-gray-50">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-gray-600">Kegiatan #<span class="uraian-num">{{ $i + 1 }}</span></span>
                        <button type="button" class="btn-remove-uraian text-rose-600 text-sm hover:underline">Hapus</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                        <div>
                            <label class="text-xs text-gray-500">Tanggal Kegiatan</label>
                            <input type="date" name="uraians[{{ $i }}][tanggal_kegiatan]" value="{{ $row['tanggal_kegiatan'] ?? '' }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Jam Mulai</label>
                            <input type="text" name="uraians[{{ $i }}][jam_mulai]" value="{{ $row['jam_mulai'] ?? '' }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="08.00">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Jam Selesai</label>
                            <input type="text" name="uraians[{{ $i }}][jam_selesai]" value="{{ $row['jam_selesai'] ?? '' }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="17.45 WITA">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Uraian Kegiatan</label>
                        <textarea class="uraian-editor mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"
                            name="uraians[{{ $i }}][uraian_text]" rows="6" required>{{ $row['uraian_text'] ?? '' }}</textarea>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ===================== LAMPIRAN 2: DOKUMENTASI ===================== --}}
    <div class="bg-white shadow-sm rounded-lg p-6 mt-6">
        <div class="flex items-center justify-between border-b pb-2 mb-4">
            <h3 class="font-semibold text-gray-800">Lampiran 2 — Dokumentasi (Foto)</h3>
            <button type="button" id="btn-add-dok" class="px-3 py-1.5 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700">+ Tambah Foto</button>
        </div>

        @if ($isEdit && $laporan->dokumentasis->count())
            <div class="mb-4">
                <p class="text-sm font-medium text-gray-600 mb-2">Foto yang sudah ada</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach ($laporan->dokumentasis as $dok)
                        <label class="border rounded-lg p-2 block cursor-pointer">
                            <img src="{{ $dok->url }}" class="w-full h-28 object-cover rounded" alt="dokumentasi">
                            <p class="text-xs text-gray-500 mt-1 truncate">{{ $dok->keterangan }}</p>
                            <span class="mt-1 inline-flex items-center gap-1 text-xs text-rose-600">
                                <input type="checkbox" name="hapus_dokumentasi[]" value="{{ $dok->id }}"> Hapus
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <div id="dok-container" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
        <p class="text-xs text-gray-400 mt-2">Format: JPG/PNG/WEBP, maks 5MB per foto.</p>
    </div>

    {{-- ===================== TEMPLATE (untuk JS repeater) ===================== --}}
    <template id="tpl-uraian">
        <div class="uraian-row border rounded-lg p-4 bg-gray-50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-600">Kegiatan #<span class="uraian-num"></span></span>
                <button type="button" class="btn-remove-uraian text-rose-600 text-sm hover:underline">Hapus</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                <div>
                    <label class="text-xs text-gray-500">Tanggal Kegiatan</label>
                    <input type="date" name="uraians[__I__][tanggal_kegiatan]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Jam Mulai</label>
                    <input type="text" name="uraians[__I__][jam_mulai]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="08.00">
                </div>
                <div>
                    <label class="text-xs text-gray-500">Jam Selesai</label>
                    <input type="text" name="uraians[__I__][jam_selesai]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="17.45 WITA">
                </div>
            </div>
            <div>
                <label class="text-xs text-gray-500">Uraian Kegiatan</label>
                <textarea class="uraian-editor mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" name="uraians[__I__][uraian_text]" rows="6" required></textarea>
            </div>
        </div>
    </template>

    <template id="tpl-dok">
        <div class="dok-row border rounded-lg p-4 bg-gray-50">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600">Foto</span>
                <button type="button" class="btn-remove-dok text-rose-600 text-sm hover:underline">Hapus</button>
            </div>
            <input type="file" name="dokumentasi[__I__][file]" accept="image/*" class="block w-full text-sm mb-2">
            <input type="text" name="dokumentasi[__I__][keterangan]" placeholder="Keterangan (opsional)" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
        </div>
    </template>

    <div class="flex items-center justify-end gap-3 mt-6">
        <a href="{{ route('laporan.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</a>
        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
            {{ $isEdit ? 'Perbarui Laporan' : 'Simpan Laporan' }}
        </button>
    </div>
</form>

{{-- CKEditor 5 (WYSIWYG mirip Word). Jika CDN gagal dimuat, textarea biasa tetap berfungsi. --}}
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
(function () {
    let uraianIndex = {{ count($uraianRows) }};
    let dokIndex = 0;

    const hasCK = typeof window.ClassicEditor !== 'undefined';

    function initEditor(textarea) {
        if (!hasCK || textarea._editor) return;
        window.ClassicEditor
            .create(textarea, {
                toolbar: ['heading', '|', 'bold', 'italic', '|', 'bulletedList', 'numberedList', '|', 'outdent', 'indent', '|', 'undo', 'redo']
            })
            .then(editor => { textarea._editor = editor; })
            .catch(err => console.error('CKEditor gagal:', err));
    }

    function initAllEditors() {
        document.querySelectorAll('#uraian-container .uraian-editor').forEach(initEditor);
    }

    function renumberUraian() {
        document.querySelectorAll('#uraian-container .uraian-row .uraian-num')
            .forEach((el, i) => el.textContent = i + 1);
    }

    // Tambah uraian
    document.getElementById('btn-add-uraian').addEventListener('click', function () {
        const tpl = document.getElementById('tpl-uraian').innerHTML.replaceAll('__I__', uraianIndex++);
        const wrap = document.createElement('div');
        wrap.innerHTML = tpl.trim();
        const node = wrap.firstElementChild;
        document.getElementById('uraian-container').appendChild(node);
        initEditor(node.querySelector('.uraian-editor'));
        renumberUraian();
    });

    // Tambah dokumentasi
    document.getElementById('btn-add-dok').addEventListener('click', function () {
        const tpl = document.getElementById('tpl-dok').innerHTML.replaceAll('__I__', dokIndex++);
        const wrap = document.createElement('div');
        wrap.innerHTML = tpl.trim();
        document.getElementById('dok-container').appendChild(wrap.firstElementChild);
    });

    // Hapus baris (delegasi)
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-remove-uraian')) {
            const row = e.target.closest('.uraian-row');
            if (document.querySelectorAll('#uraian-container .uraian-row').length <= 1) {
                alert('Minimal harus ada satu uraian kegiatan.');
                return;
            }
            const ta = row.querySelector('.uraian-editor');
            if (ta && ta._editor) { ta._editor.destroy().catch(() => {}); }
            row.remove();
            renumberUraian();
        }
        if (e.target.classList.contains('btn-remove-dok')) {
            e.target.closest('.dok-row').remove();
        }
    });

    // Sinkronkan editor -> textarea sebelum submit
    document.getElementById('laporan-form').addEventListener('submit', function () {
        document.querySelectorAll('.uraian-editor').forEach(ta => {
            if (ta._editor) ta._editor.updateSourceElement();
        });
    });

    // Auto-fill info petugas & pembiayaan
    const pegSel = document.getElementById('pegawai_id');
    const pegInfo = document.getElementById('pegawai-info');
    function showPeg() {
        const o = pegSel.options[pegSel.selectedIndex];
        pegInfo.textContent = (o && o.value) ? ('Unit Kerja: ' + (o.dataset.unit || '')) : '';
    }
    pegSel.addEventListener('change', showPeg);

    const pemSel = document.getElementById('pembiayaan_id');
    const pemInfo = document.getElementById('pembiayaan-info');
    function showPem() {
        const o = pemSel.options[pemSel.selectedIndex];
        if (o && o.value) {
            pemInfo.classList.remove('hidden');
            pemInfo.innerHTML =
                '<div><b>Program:</b> ' + (o.dataset.program || '') + '</div>' +
                '<div><b>Kegiatan:</b> ' + (o.dataset.kegiatan || '') + '</div>' +
                '<div><b>RO:</b> ' + (o.dataset.ro || '') + '</div>' +
                '<div><b>Komponen:</b> ' + (o.dataset.komponen || '') + '</div>' +
                '<div><b>Akun:</b> ' + (o.dataset.akun || '') + '</div>';
        } else {
            pemInfo.classList.add('hidden');
            pemInfo.innerHTML = '';
        }
    }
    pemSel.addEventListener('change', showPem);

    // Inisialisasi
    initAllEditors();
    showPeg();
    showPem();
})();
</script>
