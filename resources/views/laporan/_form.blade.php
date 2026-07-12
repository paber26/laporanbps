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

    // Kombinasi pembiayaan yang sudah ada untuk saran datalist bertingkat.
    $pembiayaanCombos = $pembiayaans->map(fn ($p) => [
        'program' => $p->program,
        'kegiatan' => $p->kegiatan,
        'ro' => $p->ro,
        'komponen' => $p->komponen,
        'akun' => $p->akun,
    ])->values();
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

        {{-- ===== Pembiayaan Kegiatan (bertingkat & bisa tambah baru) ===== --}}
        @php
            $pemb = $isEdit ? $laporan->pembiayaan : null;
            $pv = fn ($f) => old($f, $pemb?->{$f} ?? '');
        @endphp
        <div class="border rounded-lg p-4 bg-gray-50 space-y-3">
            <div>
                <p class="text-sm font-medium text-gray-700">Pembiayaan Kegiatan</p>
                <p class="text-xs text-gray-500">Pilih dari daftar (Program → Kegiatan → RO → Komponen → Akun). Bila belum ada, pilih <b>“➕ Tambah … baru”</b> lalu ketik nilainya. Kosongkan bila tidak diisi.</p>
            </div>
            @foreach (['program' => 'Program', 'kegiatan' => 'Kegiatan', 'ro' => 'RO (Rincian Output)', 'komponen' => 'Komponen', 'akun' => 'Akun'] as $field => $label)
                <div>
                    <x-input-label :value="$label" />
                    <div class="pemb-group mt-1" data-level="{{ $field }}">
                        <select class="pemb-select block w-full border-gray-300 rounded-md shadow-sm text-sm" data-label="{{ $label }}">
                            <option value="">-- Pilih {{ $label }} --</option>
                            <option value="__new__">➕ Tambah {{ strtolower($label) }} baru…</option>
                        </select>
                        <input type="text" class="pemb-new hidden mt-2 block w-full border-gray-300 rounded-md shadow-sm text-sm"
                            placeholder="Ketik {{ strtolower($label) }} baru">
                        <input type="hidden" name="{{ $field }}" class="pemb-value" value="{{ $pv($field) }}">
                    </div>
                    <x-input-error :messages="$errors->get($field)" class="mt-1" />
                </div>
            @endforeach
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
                        {{-- Tanpa atribut `required`: CKEditor menyembunyikan textarea sehingga
                             validasi `required` bawaan browser akan memblokir submit. Validasi
                             tetap dijalankan di sisi server. --}}
                        <textarea class="uraian-editor mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"
                            name="uraians[{{ $i }}][uraian_text]" rows="6">{{ $row['uraian_text'] ?? '' }}</textarea>
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

        <div id="dok-container" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Saat validasi gagal, bangun ulang baris foto dari input sebelumnya (server-side). --}}
            @foreach (old('dokumentasi', []) as $i => $d)
                @php $dp = $d['path'] ?? ''; $durl = $dp ? \Storage::url($dp) : ''; @endphp
                <div class="dok-row border rounded-lg p-4 bg-gray-50"
                    @if ($dp) data-path="{{ $dp }}" data-url="{{ $durl }}" data-name="{{ basename($dp) }}" @endif>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600">Foto</span>
                        <button type="button" class="btn-remove-dok text-rose-600 text-sm hover:underline">Hapus</button>
                    </div>
                    <input type="file" name="dokumentasi[{{ $i }}][file]" accept="image/*,.heic,.heif" class="dok-file block w-full text-sm mb-2">
                    <input type="hidden" class="dok-path" name="dokumentasi[{{ $i }}][path]" value="{{ $dp }}">
                    <img class="dok-preview {{ $dp ? '' : 'hidden' }} mb-2 h-32 w-full object-cover rounded border" src="{{ $durl }}" alt="preview foto">
                    <p class="dok-saved-note {{ $dp ? '' : 'hidden' }} text-xs text-emerald-600 mb-2">✓ Gambar terunggah ke server — tetap ada setelah refresh.</p>
                    <p class="dok-uploading hidden text-xs text-gray-500 mb-2">Mengunggah…</p>
                    <input type="text" name="dokumentasi[{{ $i }}][keterangan]" value="{{ $d['keterangan'] ?? '' }}" placeholder="Keterangan (opsional)" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                </div>
            @endforeach
        </div>
        <div class="mt-3 p-3 bg-blue-50 border border-blue-100 rounded-md">
            <p class="text-xs text-blue-800">
                <span class="font-semibold">Tips:</span> Anda bisa langsung <kbd class="px-1 py-0.5 bg-white border border-blue-200 rounded text-blue-900">Ctrl+V</kbd> / <kbd class="px-1 py-0.5 bg-white border border-blue-200 rounded text-blue-900">Paste</kbd> foto dari clipboard di mana saja pada halaman ini untuk otomatis menambah Lampiran Foto.
            </p>
        </div>
        <p class="text-xs text-gray-400 mt-2">Format: JPG/PNG/WEBP/HEIC, maks 5MB per foto (HEIC dari iPhone otomatis dikonversi ke JPG). Foto yang dipilih langsung terunggah &amp; tersimpan di server, jadi tetap muncul walau halaman di-refresh (belum perlu disubmit).</p>
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
                <textarea class="uraian-editor mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" name="uraians[__I__][uraian_text]" rows="6"></textarea>
            </div>
        </div>
    </template>

    <template id="tpl-dok">
        <div class="dok-row border rounded-lg p-4 bg-gray-50">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600">Foto</span>
                <button type="button" class="btn-remove-dok text-rose-600 text-sm hover:underline">Hapus</button>
            </div>
            <input type="file" name="dokumentasi[__I__][file]" accept="image/*,.heic,.heif" class="dok-file block w-full text-sm mb-2">
            <input type="hidden" class="dok-path" name="dokumentasi[__I__][path]">
            <img class="dok-preview hidden mb-2 h-32 w-full object-cover rounded border" alt="preview foto">
            <p class="dok-saved-note hidden text-xs text-emerald-600 mb-2">✓ Gambar terunggah ke server — tetap ada setelah refresh.</p>
            <p class="dok-uploading hidden text-xs text-gray-500 mb-2">Mengunggah…</p>
            <input type="text" name="dokumentasi[__I__][keterangan]" placeholder="Keterangan (opsional)" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
        </div>
    </template>

    <div class="flex items-center justify-between gap-3 mt-6">
        <div class="flex items-center gap-3 text-sm">
            <span id="draft-status" class="text-gray-400"></span>
            <button type="button" id="btn-clear-draft" class="text-rose-600 hover:underline hidden">Hapus draf</button>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('laporan.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</a>
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                {{ $isEdit ? 'Perbarui Laporan' : 'Simpan Laporan' }}
            </button>
        </div>
    </div>
</form>

{{-- CKEditor 5 (WYSIWYG mirip Word). Jika CDN gagal dimuat, textarea biasa tetap berfungsi. --}}
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
(function () {
    let uraianIndex = {{ count($uraianRows) }};
    let dokIndex = {{ count(old('dokumentasi', [])) }};

    // Kunci draft: beda antara form buat-baru dan form edit tiap laporan.
    const DRAFT_KEY = @json($isEdit ? 'laporan_draft:edit:'.$laporan->id : 'laporan_draft:create');
    const CSRF = @json(csrf_token());
    const UPLOAD_URL = @json(route('laporan.dokumentasi.draft'));
    const DELETE_URL = @json(route('laporan.dokumentasi.draft.delete'));
    // Apakah halaman ini render ulang karena validasi gagal (ada input lama)?
    const SERVER_HAS_OLD = {{ $errors->any() ? 'true' : 'false' }};

    const hasCK = typeof window.ClassicEditor !== 'undefined';
    const form = document.getElementById('laporan-form');
    const uraianContainer = document.getElementById('uraian-container');
    const dokContainer = document.getElementById('dok-container');
    const SIMPLE_FIELDS = ['pegawai_id', 'program', 'kegiatan', 'ro', 'komponen', 'akun',
        'judul_laporan', 'perihal_laporan', 'tujuan_surat', 'tempat_laporan', 'tanggal_laporan', 'lokasi_tujuan'];

    // Semua kombinasi pembiayaan yang sudah ada (untuk saran datalist bertingkat).
    const PEMBIAYAAN = @json($pembiayaanCombos);
    const PEMB_FIELDS = ['program', 'kegiatan', 'ro', 'komponen', 'akun'];

    let restoring = false; // cegah autosave saat sedang memulihkan draft

    // ====== Foto langsung diunggah ke storage Laravel (draft) ======
    // Saat file dipilih, foto diunggah ke server (dokumentasi/tmp/{user}) sehingga
    // tetap ada setelah refresh dan ikut tersimpan saat laporan disubmit.
    function setRowFile(row, path, url) {
        row.dataset.path = path || '';
        row.dataset.url = url || '';
        const hidden = row.querySelector('.dok-path');
        if (hidden) hidden.value = path || '';
        const img = row.querySelector('.dok-preview');
        const note = row.querySelector('.dok-saved-note');
        if (img && url) { img.src = url; img.classList.remove('hidden'); }
        if (note) note.classList.toggle('hidden', !path);
    }

    function toggleUploading(row, on) {
        const el = row.querySelector('.dok-uploading');
        if (el) el.classList.toggle('hidden', !on);
    }

    async function deleteServerFile(path) {
        if (!path) return;
        try {
            await fetch(DELETE_URL, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ path }),
            });
        } catch (e) { /* abaikan */ }
    }

    async function uploadFileToRow(row, file) {
        if (!row || !file) return;

        // Hapus foto lama baris ini (bila mengganti file).
        if (row.dataset.path) deleteServerFile(row.dataset.path);

        toggleUploading(row, true);
        const fd = new FormData();
        fd.append('file', file);
        fd.append('_token', CSRF);
        try {
            const res = await fetch(UPLOAD_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: fd,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || ('HTTP ' + res.status));
            row.dataset.name = data.name || file.name;
            setRowFile(row, data.path, data.url);
            // Kosongkan input file agar tidak terunggah dua kali saat submit (pakai path).
            const input = row.querySelector('.dok-file');
            if (input) input.value = '';
        } catch (e) {
            console.error('Gagal mengunggah foto:', e);
            alert(e.message || 'Gagal mengunggah foto. Coba lagi.');
        } finally {
            toggleUploading(row, false);
        }
        scheduleSave();
    }

    async function onDokFileChange(input) {
        const row = input.closest('.dok-row');
        const file = input.files && input.files[0];
        uploadFileToRow(row, file);
    }

    function initEditor(textarea) {
        if (!hasCK || textarea._editor) return;
        window.ClassicEditor
            .create(textarea, {
                toolbar: ['heading', '|', 'bold', 'italic', '|', 'bulletedList', 'numberedList', '|', 'outdent', 'indent', '|', 'undo', 'redo']
            })
            .then(editor => {
                textarea._editor = editor;
                editor.model.document.on('change:data', () => {
                    editor.updateSourceElement();
                    scheduleSave();
                });
            })
            .catch(err => console.error('CKEditor gagal:', err));
    }

    function renumberUraian() {
        uraianContainer.querySelectorAll('.uraian-row .uraian-num')
            .forEach((el, i) => el.textContent = i + 1);
    }

    // Tambah satu baris uraian (opsional dengan data awal untuk pemulihan draft).
    function addUraianRow(data) {
        const tpl = document.getElementById('tpl-uraian').innerHTML.replaceAll('__I__', uraianIndex++);
        const wrap = document.createElement('div');
        wrap.innerHTML = tpl.trim();
        const node = wrap.firstElementChild;
        if (data) {
            const set = (name, val) => { const el = node.querySelector('[name$="[' + name + ']"]'); if (el) el.value = val || ''; };
            set('tanggal_kegiatan', data.tanggal_kegiatan);
            set('jam_mulai', data.jam_mulai);
            set('jam_selesai', data.jam_selesai);
            // Isi textarea SEBELUM editor dibuat agar CKEditor memuat kontennya.
            const ta = node.querySelector('.uraian-editor');
            if (ta) ta.value = data.uraian_text || '';
        }
        uraianContainer.appendChild(node);
        initEditor(node.querySelector('.uraian-editor'));
        renumberUraian();
        return node;
    }

    function addDokRow(data) {
        const tpl = document.getElementById('tpl-dok').innerHTML.replaceAll('__I__', dokIndex++);
        const wrap = document.createElement('div');
        wrap.innerHTML = tpl.trim();
        const node = wrap.firstElementChild;
        if (data) {
            const ket = node.querySelector('[name$="[keterangan]"]');
            if (ket) ket.value = data.keterangan || '';
        }
        dokContainer.appendChild(node);
        // Pulihkan foto yang sudah terunggah ke server.
        if (data && data.path) {
            node.dataset.name = data.name || '';
            setRowFile(node, data.path, data.url);
        }
        return node;
    }

    document.getElementById('btn-add-uraian').addEventListener('click', () => { addUraianRow(); scheduleSave(); });
    document.getElementById('btn-add-dok').addEventListener('click', () => { addDokRow(); scheduleSave(); });

    // Simpan gambar begitu dipilih (sebelum submit).
    dokContainer.addEventListener('change', function (e) {
        if (e.target.matches('input[type=file]')) onDokFileChange(e.target);
    });

    // Hapus baris (delegasi)
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-remove-uraian')) {
            const row = e.target.closest('.uraian-row');
            if (uraianContainer.querySelectorAll('.uraian-row').length <= 1) {
                alert('Minimal harus ada satu uraian kegiatan.');
                return;
            }
            const ta = row.querySelector('.uraian-editor');
            if (ta && ta._editor) { ta._editor.destroy().catch(() => {}); }
            row.remove();
            renumberUraian();
            scheduleSave();
        }
        if (e.target.classList.contains('btn-remove-dok')) {
            const drow = e.target.closest('.dok-row');
            if (drow.dataset.path) { deleteServerFile(drow.dataset.path); }
            drow.remove();
            scheduleSave();
        }
    });

    // ============ AUTOSAVE / RESTORE DRAFT (localStorage) ============
    const statusEl = document.getElementById('draft-status');
    const clearBtn = document.getElementById('btn-clear-draft');
    let saveTimer = null;

    function collect() {
        const data = { fields: {}, uraians: [], dokumentasi: [] };
        SIMPLE_FIELDS.forEach(name => {
            const el = form.querySelector('[name="' + name + '"]');
            if (el) data.fields[name] = el.value;
        });
        uraianContainer.querySelectorAll('.uraian-row').forEach(row => {
            const g = (n) => { const el = row.querySelector('[name$="[' + n + ']"]'); return el ? el.value : ''; };
            const ta = row.querySelector('.uraian-editor');
            data.uraians.push({
                tanggal_kegiatan: g('tanggal_kegiatan'),
                jam_mulai: g('jam_mulai'),
                jam_selesai: g('jam_selesai'),
                uraian_text: (ta && ta._editor) ? ta._editor.getData() : (ta ? ta.value : ''),
            });
        });
        dokContainer.querySelectorAll('.dok-row').forEach(row => {
            const ket = row.querySelector('[name$="[keterangan]"]');
            data.dokumentasi.push({
                keterangan: ket ? ket.value : '',
                path: row.dataset.path || '',
                url: row.dataset.url || '',
                name: row.dataset.name || '',
            });
        });
        return data;
    }

    function isEmptyDraft(d) {
        const anyField = SIMPLE_FIELDS.some(n => (d.fields[n] || '').trim() !== '');
        const anyUraian = d.uraians.some(u => (u.uraian_text || '').trim() !== '' || (u.tanggal_kegiatan || '') !== '' || (u.jam_mulai || '') !== '' || (u.jam_selesai || '') !== '');
        const anyDok = d.dokumentasi.some(k => (k.keterangan || '').trim() !== '' || (k.path || '') !== '');
        return !(anyField || anyUraian || anyDok);
    }

    function save() {
        if (restoring) return;
        const data = collect();
        if (isEmptyDraft(data)) { return; }
        try {
            localStorage.setItem(DRAFT_KEY, JSON.stringify(data));
            const t = new Date().toLocaleTimeString('id-ID');
            statusEl.textContent = 'Draf tersimpan otomatis · ' + t;
            statusEl.className = 'text-emerald-600';
            clearBtn.classList.remove('hidden');
        } catch (err) {
            statusEl.textContent = 'Gagal menyimpan draf (penyimpanan penuh)';
            statusEl.className = 'text-rose-500';
        }
    }

    function scheduleSave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(save, 400);
    }

    function clearDraft() {
        localStorage.removeItem(DRAFT_KEY);
        statusEl.textContent = '';
        clearBtn.classList.add('hidden');
    }

    function restore() {
        let raw;
        try { raw = localStorage.getItem(DRAFT_KEY); } catch (e) { raw = null; }
        if (!raw) return false;
        let d;
        try { d = JSON.parse(raw); } catch (e) { return false; }
        if (!d || isEmptyDraft(d)) return false;

        restoring = true;

        SIMPLE_FIELDS.forEach(name => {
            if (d.fields[name] !== undefined) {
                const el = form.querySelector('[name="' + name + '"]');
                if (el) el.value = d.fields[name];
            }
        });

        // Bangun ulang baris uraian dari draft (buang baris bawaan server).
        uraianContainer.querySelectorAll('.uraian-row .uraian-editor').forEach(ta => {
            if (ta._editor) ta._editor.destroy().catch(() => {});
        });
        uraianContainer.innerHTML = '';
        (d.uraians && d.uraians.length ? d.uraians : [null]).forEach(u => addUraianRow(u));

        // Bangun ulang baris dokumentasi + pulihkan foto dari storage server.
        dokContainer.innerHTML = '';
        (d.dokumentasi || []).forEach(k => addDokRow(k));

        restoring = false;
        statusEl.textContent = 'Draf sebelumnya dipulihkan';
        statusEl.className = 'text-emerald-600';
        clearBtn.classList.remove('hidden');
        return true;
    }

    clearBtn.addEventListener('click', function () {
        if (!confirm('Hapus draf tersimpan dan muat ulang halaman dengan data dari server?')) return;
        clearDraft();
        // Muat ulang agar form kembali ke data server (draf lokal tidak lagi memengaruhi
        // tampilan) — tanpa reload, form tetap menampilkan isian saat ini meski draf
        // sudah terhapus dari localStorage, sehingga terlihat seperti "tidak berubah".
        window.location.reload();
    });

    // Simpan saat mengetik / mengubah field mana pun.
    form.addEventListener('input', scheduleSave);
    form.addEventListener('change', scheduleSave);

    // Sinkronkan editor -> textarea sebelum submit. Draft TIDAK dihapus di sini —
    // pembersihan dilakukan setelah simpan sukses (via flash di halaman preview),
    // agar data tetap aman bila validasi gagal.
    form.addEventListener('submit', function () {
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

    // ====== Pembiayaan bertingkat (dropdown + opsi "Tambah baru") ======
    function pembGroup(level) { return document.querySelector('.pemb-group[data-level="' + level + '"]'); }
    function pembSelect(level) { const g = pembGroup(level); return g ? g.querySelector('.pemb-select') : null; }
    function pembNew(level) { const g = pembGroup(level); return g ? g.querySelector('.pemb-new') : null; }
    function pembHidden(level) { const g = pembGroup(level); return g ? g.querySelector('.pemb-value') : null; }
    function pembVal(level) { const h = pembHidden(level); return h ? h.value.trim() : ''; }

    // Nilai distinct suatu level, difilter sesuai pilihan level-level di atasnya.
    function optionsFor(level) {
        const idx = PEMB_FIELDS.indexOf(level);
        const parents = PEMB_FIELDS.slice(0, idx);
        const pv = {};
        parents.forEach(f => pv[f] = pembVal(f));
        const set = new Set();
        PEMBIAYAAN.forEach(row => {
            for (const f of parents) { if (pv[f] && row[f] !== pv[f]) return; }
            if (row[level]) set.add(row[level]);
        });
        return Array.from(set).sort((a, b) => a.localeCompare(b, 'id'));
    }

    // Selaraskan tampilan (select / kolom ketik) dengan nilai tersembunyi.
    function applyValue(level, value) {
        const sel = pembSelect(level), ni = pembNew(level), hid = pembHidden(level);
        value = (value || '').trim();
        if (hid) hid.value = value;
        const inOpts = value && Array.from(sel.options).some(o => o.value === value);
        if (value && !inOpts) {
            sel.value = '__new__';
            ni.classList.remove('hidden');
            if (ni.value !== value) ni.value = value;
        } else {
            sel.value = value;
            ni.classList.add('hidden');
        }
    }

    function buildSelect(level) {
        const sel = pembSelect(level);
        if (!sel) return;
        const label = sel.dataset.label || level;
        const current = pembVal(level);
        sel.innerHTML = '';
        const blank = document.createElement('option');
        blank.value = ''; blank.textContent = '-- Pilih ' + label + ' --';
        sel.appendChild(blank);
        optionsFor(level).forEach(v => {
            const o = document.createElement('option');
            o.value = v; o.textContent = v;
            sel.appendChild(o);
        });
        const on = document.createElement('option');
        on.value = '__new__'; on.textContent = '➕ Tambah ' + label.toLowerCase() + ' baru…';
        sel.appendChild(on);
        applyValue(level, current);
    }

    // Kosongkan & bangun ulang semua level di bawah `level` (cascading).
    function clearDescendants(level) {
        const idx = PEMB_FIELDS.indexOf(level);
        PEMB_FIELDS.slice(idx + 1).forEach(f => {
            const h = pembHidden(f); if (h) h.value = '';
            const ni = pembNew(f); if (ni) { ni.value = ''; ni.classList.add('hidden'); }
            buildSelect(f);
        });
    }

    PEMB_FIELDS.forEach(level => {
        const sel = pembSelect(level), ni = pembNew(level), hid = pembHidden(level);
        if (!sel) return;
        sel.addEventListener('change', () => {
            if (sel.value === '__new__') {
                ni.classList.remove('hidden'); ni.value = ''; if (hid) hid.value = ''; ni.focus();
            } else {
                ni.classList.add('hidden'); if (hid) hid.value = sel.value;
            }
            clearDescendants(level);
            scheduleSave();
        });
        ni.addEventListener('input', () => {
            if (hid) hid.value = ni.value.trim();
            clearDescendants(level);
            scheduleSave();
        });
    });

    function syncPembiayaan() { PEMB_FIELDS.forEach(buildSelect); }

    // ============ INISIALISASI ============
    // Jika halaman render ulang karena validasi gagal, pakai data server (old())
    // dan jangan pulihkan dari draft agar tidak dobel.
    const restored = SERVER_HAS_OLD ? false : restore();
    if (!restored) {
        uraianContainer.querySelectorAll('.uraian-editor').forEach(initEditor);
    }
    showPeg();
    syncPembiayaan();

    // ============ GLOBAL PASTE LISTENER ============
    document.addEventListener('paste', function (e) {
        const items = (e.clipboardData || window.clipboardData).items;
        let imagePasted = false;
        
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                const blob = items[i].getAsFile();
                if (!blob) continue;
                
                // Mencegah default action jika dirasa perlu, tetapi umumnya aman dibiarkan 
                // agar teks tetap terpaste bila ada. Jika ingin mencegah gambar masuk ke textarea:
                // e.preventDefault();
                
                const ext = blob.type.split('/')[1] || 'png';
                const validExt = ['png', 'jpg', 'jpeg', 'webp', 'heic', 'heif'].includes(ext) ? ext : 'png';
                const file = new File([blob], 'pasted_image_' + Date.now() + '.' + validExt, { type: blob.type });
                
                const row = addDokRow();
                uploadFileToRow(row, file);
                
                imagePasted = true;
            }
        }
        
        if (imagePasted) {
            scheduleSave();
            // Scroll ke bagian dokumentasi agar user tahu foto berhasil di-paste
            const dokContainer = document.getElementById('dok-container');
            if (dokContainer) {
                // Beri sedikit jeda agar baris baru selesai di-render
                setTimeout(() => {
                    dokContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            }
        }
    });

})();
</script>
