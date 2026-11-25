@php
    /** @var \App\Models\SuratTugas|null $suratTugas */
    $isEdit = isset($suratTugas) && $suratTugas?->exists;
    $kodeSatker = $defaults['kode_satker'] ?? '71050';
    $defaultMengingat = implode("\n", $defaults['default_mengingat'] ?? []);

    $val = function (string $field, $default = '') use ($isEdit, $suratTugas) {
        $modelVal = $isEdit ? $suratTugas->{$field} : $default;
        if ($modelVal instanceof \Carbon\CarbonInterface) {
            $modelVal = $modelVal->format('Y-m-d');
        }

        return old($field, $modelVal ?? $default);
    };

    $ruteRows = old('rutes');
    if ($ruteRows === null) {
        if ($isEdit && $suratTugas->rutes->isNotEmpty()) {
            $ruteRows = $suratTugas->rutes->map(fn ($r) => [
                'kecamatan_tujuan' => $r->kecamatan_tujuan,
                'tanggal_tiba' => optional($r->tanggal_tiba)->format('Y-m-d'),
                'tanggal_berangkat' => optional($r->tanggal_berangkat)->format('Y-m-d'),
            ])->toArray();
        } else {
            $today = now()->format('Y-m-d');
            $ruteRows = [[
                'kecamatan_tujuan' => '',
                'tanggal_tiba' => $today,
                'tanggal_berangkat' => $today,
            ]];
        }
    }

    $pegawaiMeta = $pegawais->mapWithKeys(fn ($p) => [
        $p->id => [
            'jabatan' => $p->jabatan ?? '',
            'pangkat_golongan' => $p->pangkat_golongan ?? '',
            'unit_kerja' => $p->unit_kerja ?? ($defaults['instansi'] ?? 'BPS Kabupaten Minahasa Selatan'),
        ],
    ]);
@endphp

<form action="{{ $action }}" method="POST" id="surat-tugas-form" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 px-4 py-3 rounded-md text-sm">
            <p class="font-semibold">Periksa kembali isian berikut:</p>
            <ul class="list-disc list-inside mt-1 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <datalist id="kecamatan-list">
        @foreach ($kecamatans as $kec)
            <option value="{{ $kec }}"></option>
        @endforeach
    </datalist>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- ================= KOLOM KIRI: IDENTITAS & PEGAWAI ================= --}}
        <div class="lg:col-span-5 space-y-6">
            {{-- Kartu 1: Identitas Surat & Penomoran --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 border-b dark:border-gray-700 pb-2">1. Identitas &amp; Penomoran ST / SPD</h3>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <x-input-label for="nomor_st_urut" value="No. Urut ST *" />
                        <x-text-input id="nomor_st_urut" name="nomor_st_urut" type="text" class="mt-1 block w-full"
                                      :value="$val('nomor_st_urut')" placeholder="mis: 902 / 1163d" required />
                        <x-input-error :messages="$errors->get('nomor_st_urut')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="kode_klasifikasi" value="Kode Klasifikasi *" />
                        <x-text-input id="kode_klasifikasi" name="kode_klasifikasi" type="text" class="mt-1 block w-full"
                                      :value="$val('kode_klasifikasi', $defaults['kode_klasifikasi'] ?? 'KP.650')" required />
                        <x-input-error :messages="$errors->get('kode_klasifikasi')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="tahun" value="Tahun *" />
                        <x-text-input id="tahun" name="tahun" type="number" class="mt-1 block w-full"
                                      :value="$val('tahun', now()->year)" required />
                        <x-input-error :messages="$errors->get('tahun')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-input-label for="nomor_st" value="Nomor ST Lengkap (otomatis &amp; bisa diedit) *" />
                    <x-text-input id="nomor_st" name="nomor_st" type="text" class="mt-1 block w-full font-mono text-sm"
                                  :value="$val('nomor_st')" placeholder="B-902/71050/KP.650/2026" required />
                    <x-input-error :messages="$errors->get('nomor_st')" class="mt-1" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <x-input-label for="nomor_spd_urut_helper" value="Bantu No. Urut SPD" />
                        <x-text-input id="nomor_spd_urut_helper" type="text" class="mt-1 block w-full" placeholder="mis: 722" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="nomor_spd" value="Nomor SPD Lengkap" />
                        <x-text-input id="nomor_spd" name="nomor_spd" type="text" class="mt-1 block w-full font-mono text-sm"
                                      :value="$val('nomor_spd')" placeholder="722/SPD_71050/2906/11/2026" />
                        <x-input-error :messages="$errors->get('nomor_spd')" class="mt-1" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="tempat_st" value="Tempat Dikeluarkan *" />
                        <x-text-input id="tempat_st" name="tempat_st" type="text" class="mt-1 block w-full"
                                      :value="$val('tempat_st', $defaults['tempat_st'] ?? 'Minahasa Selatan')" required />
                        <x-input-error :messages="$errors->get('tempat_st')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="tanggal_st" value="Tanggal ST *" />
                        <x-text-input id="tanggal_st" name="tanggal_st" type="date" class="mt-1 block w-full"
                                      :value="$val('tanggal_st', now()->format('Y-m-d'))" required />
                        <x-input-error :messages="$errors->get('tanggal_st')" class="mt-1" />
                    </div>
                </div>
            </div>

            {{-- Kartu 2: Pegawai yang Ditugaskan --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 border-b dark:border-gray-700 pb-2">2. Pegawai Organik yang Ditugaskan</h3>

                <div>
                    <x-input-label for="pegawai_id" value="Pilih Pegawai *" />
                    <select id="pegawai_id" name="pegawai_id"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm" required>
                        <option value="">-- Pilih Pegawai --</option>
                        @foreach ($pegawais as $p)
                            <option value="{{ $p->id }}" @selected($val('pegawai_id') == $p->id)>
                                {{ $p->nama }} ({{ $p->nip }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('pegawai_id')" class="mt-1" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="jabatan" value="Jabatan" />
                        <x-text-input id="jabatan" name="jabatan" type="text" class="mt-1 block w-full text-sm"
                                      :value="$val('jabatan')" placeholder="Otomatis dari Master Pegawai" />
                        <x-input-error :messages="$errors->get('jabatan')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="pangkat_golongan" value="Pangkat / Golongan" />
                        <x-text-input id="pangkat_golongan" name="pangkat_golongan" type="text" class="mt-1 block w-full text-sm"
                                      :value="$val('pangkat_golongan')" placeholder="mis: Penata Muda Tk.I (III/b)" />
                        <x-input-error :messages="$errors->get('pangkat_golongan')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-input-label for="unit_kerja" value="Instansi / Unit Kerja *" />
                    <x-text-input id="unit_kerja" name="unit_kerja" type="text" class="mt-1 block w-full text-sm"
                                  :value="$val('unit_kerja', $defaults['instansi'] ?? 'BPS Kabupaten Minahasa Selatan')" required />
                    <x-input-error :messages="$errors->get('unit_kerja')" class="mt-1" />
                </div>
            </div>

            {{-- Kartu 3: Pembebanan Anggaran --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 border-b dark:border-gray-700 pb-2">3. Pembebanan Anggaran (ST &amp; SPD Poin 9)</h3>

                <div>
                    <x-input-label for="pembiayaan_id" value="Ambil dari Master Pembiayaan (opsional)" />
                    <select id="pembiayaan_id" name="pembiayaan_id"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm">
                        <option value="">-- Pilih untuk auto-isi, atau isi manual di bawah --</option>
                        @foreach ($pembiayaans as $mp)
                            <option value="{{ $mp->id }}" @selected($val('pembiayaan_id') == $mp->id)>
                                {{ $mp->kegiatan }} — {{ $mp->ro }} ({{ $mp->akun }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="mata_anggaran" value="Mata Anggaran" />
                    <x-text-input id="mata_anggaran" name="mata_anggaran" type="text" class="mt-1 block w-full font-mono text-sm"
                                  :value="$val('mata_anggaran')" placeholder="GG 2906 BMA 006 052 A 524113" />
                    <x-input-error :messages="$errors->get('mata_anggaran')" class="mt-1" />
                </div>

                <div class="grid grid-cols-12 gap-2">
                    <div class="col-span-4">
                        <x-input-label for="program_kode" value="Kode Program" />
                        <x-text-input id="program_kode" name="program_kode" type="text" class="mt-1 block w-full text-sm" :value="$val('program_kode', 'GG')" />
                    </div>
                    <div class="col-span-8">
                        <x-input-label for="program_nama" value="Nama Program" />
                        <x-text-input id="program_nama" name="program_nama" type="text" class="mt-1 block w-full text-sm" :value="$val('program_nama', 'Program Penyediaan dan Pelayanan Informasi Statistik')" />
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-2">
                    <div class="col-span-4">
                        <x-input-label for="kegiatan_kode" value="Kode Kegiatan" />
                        <x-text-input id="kegiatan_kode" name="kegiatan_kode" type="text" class="mt-1 block w-full text-sm" :value="$val('kegiatan_kode', '2906')" />
                    </div>
                    <div class="col-span-8">
                        <x-input-label for="kegiatan_nama" value="Nama Kegiatan" />
                        <x-text-input id="kegiatan_nama" name="kegiatan_nama" type="text" class="mt-1 block w-full text-sm" :value="$val('kegiatan_nama')" />
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-2">
                    <div class="col-span-4">
                        <x-input-label for="komponen_kode" value="Kode Komponen" />
                        <x-text-input id="komponen_kode" name="komponen_kode" type="text" class="mt-1 block w-full text-sm" :value="$val('komponen_kode', '052')" />
                    </div>
                    <div class="col-span-8">
                        <x-input-label for="komponen_nama" value="Nama Komponen" />
                        <x-text-input id="komponen_nama" name="komponen_nama" type="text" class="mt-1 block w-full text-sm" :value="$val('komponen_nama', 'Pengumpulan Data')" />
                    </div>
                </div>

                <div>
                    <x-input-label for="instansi_anggaran" value="Instansi Pembebanan *" />
                    <x-text-input id="instansi_anggaran" name="instansi_anggaran" type="text" class="mt-1 block w-full text-sm"
                                  :value="$val('instansi_anggaran', $defaults['instansi_panjang'] ?? 'Badan Pusat Statistik Minahasa Selatan')" required />
                </div>
            </div>
        </div>

        {{-- ================= KOLOM KANAN: ISI ST, SPD, VISUM, PEJABAT ================= --}}
        <div class="lg:col-span-7 space-y-6">
            {{-- Kartu 4: Isi Surat Tugas --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 border-b dark:border-gray-700 pb-2">4. Substansi Surat Tugas (Halaman 1)</h3>

                <div>
                    <x-input-label for="menimbang" value="Menimbang *" />
                    <textarea id="menimbang" name="menimbang" rows="3" required
                              placeholder="Bahwa dalam rangka tersedianya data ... yang terjamin kualitasnya, dipandang perlu dilakukan ... yang dibekali dengan surat tugas"
                              class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm">{{ $val('menimbang') }}</textarea>
                    <x-input-error :messages="$errors->get('menimbang')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="tujuan_kegiatan" value="Untuk / Maksud Perjalanan Dinas *" />
                    <textarea id="tujuan_kegiatan" name="tujuan_kegiatan" rows="2" required
                              placeholder="mis: Melaksanakan pengawasan pemutakhiran lapangan Susenas September dan Seruti Triwulan III 2026"
                              class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm">{{ $val('tujuan_kegiatan') }}</textarea>
                    <x-input-error :messages="$errors->get('tujuan_kegiatan')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="mengingat" value="Mengingat (1 butir per baris) *" />
                    <textarea id="mengingat" name="mengingat" rows="5" required
                              class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-xs leading-relaxed">{{ $val('mengingat', $defaultMengingat) }}</textarea>
                    <x-input-error :messages="$errors->get('mengingat')" class="mt-1" />
                </div>
            </div>

            {{-- Kartu 5: Rincian SPD & Rute Visum --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 border-b dark:border-gray-700 pb-2">5. Jadwal, Rincian SPD &amp; Rute Visum (Halaman 2 &amp; 3)</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <x-input-label for="tanggal_berangkat" value="Tanggal Berangkat *" />
                        <x-text-input id="tanggal_berangkat" name="tanggal_berangkat" type="date" class="mt-1 block w-full text-sm"
                                      :value="$val('tanggal_berangkat', now()->format('Y-m-d'))" required />
                        <x-input-error :messages="$errors->get('tanggal_berangkat')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="tanggal_kembali" value="Tanggal Kembali *" />
                        <x-text-input id="tanggal_kembali" name="tanggal_kembali" type="date" class="mt-1 block w-full text-sm"
                                      :value="$val('tanggal_kembali', now()->format('Y-m-d'))" required />
                        <x-input-error :messages="$errors->get('tanggal_kembali')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="jumlah_hari" value="Lamanya (Hari) *" />
                        <x-text-input id="jumlah_hari" name="jumlah_hari" type="number" min="1" max="365" class="mt-1 block w-full text-sm"
                                      :value="$val('jumlah_hari', 1)" required />
                        <x-input-error :messages="$errors->get('jumlah_hari')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-input-label for="waktu_pelaksanaan" value="Teks Waktu Pelaksanaan di ST *" />
                    <x-text-input id="waktu_pelaksanaan" name="waktu_pelaksanaan" type="text" class="mt-1 block w-full text-sm"
                                  :value="$val('waktu_pelaksanaan')" placeholder="mis: 3 September 2026 atau 19-23 Oktober 2026" required />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Otomatis dirakit dari tanggal berangkat–kembali dan tetap bisa diedit manual.</p>
                    <x-input-error :messages="$errors->get('waktu_pelaksanaan')" class="mt-1" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <x-input-label for="tingkat_biaya" value="Tingkat Biaya *" />
                        <x-text-input id="tingkat_biaya" name="tingkat_biaya" type="text" class="mt-1 block w-full text-sm"
                                      :value="$val('tingkat_biaya', 'C')" required />
                    </div>
                    <div>
                        <x-input-label for="alat_angkutan" value="Alat Angkutan *" />
                        <x-text-input id="alat_angkutan" name="alat_angkutan" type="text" class="mt-1 block w-full text-sm"
                                      :value="$val('alat_angkutan', 'Lainnya')" required />
                    </div>
                    <div>
                        <x-input-label for="kecamatan_asal" value="Kecamatan Asal (Visum) *" />
                        <x-text-input id="kecamatan_asal" name="kecamatan_asal" list="kecamatan-list" type="text" class="mt-1 block w-full text-sm"
                                      :value="$val('kecamatan_asal', $defaults['tempat_kedudukan'] ?? 'Amurang Barat')" required />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="tempat_berangkat" value="Tempat Keberangkatan (SPD 6a) *" />
                        <x-text-input id="tempat_berangkat" name="tempat_berangkat" type="text" class="mt-1 block w-full text-sm"
                                      :value="$val('tempat_berangkat', $defaults['kabupaten'] ?? 'Kabupaten Minahasa Selatan')" required />
                    </div>
                    <div>
                        <x-input-label for="tempat_tujuan" value="Tempat Tujuan (SPD 6b) *" />
                        <x-text-input id="tempat_tujuan" name="tempat_tujuan" type="text" class="mt-1 block w-full text-sm"
                                      :value="$val('tempat_tujuan', $defaults['kabupaten'] ?? 'Kabupaten Minahasa Selatan')" required />
                    </div>
                </div>

                {{-- Repeater Rute Visum SPD (1..4 Tujuan) --}}
                <div class="border dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900/40 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Rute Kecamatan Tujuan (Visum Halaman 3) *</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Pilih dari 17 kecamatan Minahasa Selatan atau ketik bebas (mis. Kota Manado) untuk luar daerah. Maksimal 4 tujuan.</p>
                        </div>
                        <button type="button" id="btn-add-rute"
                                class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-md hover:bg-indigo-700">
                            + Tambah Tujuan
                        </button>
                    </div>

                    <div id="rutes-container" class="space-y-2.5">
                        @foreach ($ruteRows as $idx => $rute)
                            <div class="rute-row grid grid-cols-1 md:grid-cols-12 gap-2 items-end bg-white dark:bg-gray-800 p-3 rounded border dark:border-gray-700">
                                <div class="md:col-span-5">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Kecamatan / Lokasi Tujuan <span class="rute-no">{{ $idx + 1 }}</span></label>
                                    <input type="text" name="rutes[{{ $idx }}][kecamatan_tujuan]" list="kecamatan-list"
                                           value="{{ $rute['kecamatan_tujuan'] ?? '' }}" required placeholder="mis: Modoinding"
                                           class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md text-sm">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tgl Tiba</label>
                                    <input type="date" name="rutes[{{ $idx }}][tanggal_tiba]"
                                           value="{{ $rute['tanggal_tiba'] ?? '' }}" required
                                           class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md text-sm">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tgl Berangkat Lanjut</label>
                                    <input type="date" name="rutes[{{ $idx }}][tanggal_berangkat]"
                                           value="{{ $rute['tanggal_berangkat'] ?? '' }}"
                                           class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md text-sm">
                                </div>
                                <div class="md:col-span-1 text-right">
                                    <button type="button" class="btn-remove-rute text-xs text-rose-600 dark:text-rose-400 hover:underline py-2">Hapus</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('rutes')" class="mt-1" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <x-input-label for="pengikut" value="Pengikut (opsional)" />
                        <textarea id="pengikut" name="pengikut" rows="2" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md text-xs">{{ $val('pengikut') }}</textarea>
                    </div>
                    <div>
                        <x-input-label for="keterangan_lain" value="Keterangan Lain-lain (opsional)" />
                        <textarea id="keterangan_lain" name="keterangan_lain" rows="2" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md text-xs">{{ $val('keterangan_lain') }}</textarea>
                    </div>
                    <div>
                        <x-input-label for="tembusan" value="Tembusan (1 per baris, opsional)" />
                        <textarea id="tembusan" name="tembusan" rows="2" placeholder="Kosongkan untuk 1. &amp; 2. kosong" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md text-xs">{{ $val('tembusan') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Kartu 6: Pejabat Penandatangan & Opsi TTD --}}
            <details class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6" {{ $isEdit ? '' : '' }} open>
                <summary class="font-semibold text-gray-800 dark:text-gray-200 cursor-pointer select-none">
                    6. Pejabat Penandatangan (Kepala BPS &amp; PPK) &amp; Opsi TTD
                </summary>
                <div class="mt-4 space-y-4 border-t dark:border-gray-700 pt-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Penandatangan ST &amp; Visum I (Kepala BPS)</p>
                            <div>
                                <x-input-label for="kepala_jabatan" value="Jabatan *" />
                                <x-text-input id="kepala_jabatan" name="kepala_jabatan" type="text" class="mt-1 block w-full text-sm"
                                              :value="$val('kepala_jabatan', $defaults['kepala_bps']['jabatan'] ?? 'Kepala BPS Kabupaten Minahasa Selatan')" required />
                            </div>
                            <div>
                                <x-input-label for="kepala_nama" value="Nama *" />
                                <x-text-input id="kepala_nama" name="kepala_nama" type="text" class="mt-1 block w-full text-sm"
                                              :value="$val('kepala_nama', $defaults['kepala_bps']['nama'] ?? '')" required />
                            </div>
                            <div>
                                <x-input-label for="kepala_nip" value="NIP *" />
                                <x-text-input id="kepala_nip" name="kepala_nip" type="text" class="mt-1 block w-full text-sm font-mono"
                                              :value="$val('kepala_nip', $defaults['kepala_bps']['nip'] ?? '')" required />
                            </div>
                        </div>

                        <div class="space-y-3">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Penandatangan SPD &amp; Visum Akhir (PPK)</p>
                            <div>
                                <x-input-label for="ppk_jabatan" value="Jabatan *" />
                                <x-text-input id="ppk_jabatan" name="ppk_jabatan" type="text" class="mt-1 block w-full text-sm"
                                              :value="$val('ppk_jabatan', $defaults['ppk']['jabatan'] ?? 'PEJABAT PEMBUAT KOMITMEN')" required />
                            </div>
                            <div>
                                <x-input-label for="ppk_sub_jabatan" value="Sub Jabatan" />
                                <x-text-input id="ppk_sub_jabatan" name="ppk_sub_jabatan" type="text" class="mt-1 block w-full text-sm"
                                              :value="$val('ppk_sub_jabatan', $defaults['ppk']['sub_jabatan'] ?? 'Program Dukungan Manajemen')" />
                            </div>
                            <div>
                                <x-input-label for="ppk_nama" value="Nama *" />
                                <x-text-input id="ppk_nama" name="ppk_nama" type="text" class="mt-1 block w-full text-sm"
                                              :value="$val('ppk_nama', $defaults['ppk']['nama'] ?? '')" required />
                            </div>
                            <div>
                                <x-input-label for="ppk_nip" value="NIP *" />
                                <x-text-input id="ppk_nip" name="ppk_nip" type="text" class="mt-1 block w-full text-sm font-mono"
                                              :value="$val('ppk_nip', $defaults['ppk']['nip'] ?? '')" required />
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-t dark:border-gray-700">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="hidden" name="sertakan_ttd_kepala" value="0">
                            <input type="checkbox" name="sertakan_ttd_kepala" value="1"
                                   @checked((bool) $val('sertakan_ttd_kepala', false))
                                   class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                            <span>Sertakan gambar TTD scan Kepala BPS pada halaman Surat Tugas (kosongkan untuk TTD basah / TTE Srikandi)</span>
                        </label>
                    </div>
                </div>
            </details>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('surat-tugas.index') }}" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:underline">Batal</a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 shadow-sm">
                    {{ $isEdit ? 'Simpan Perubahan' : 'Simpan & Lihat Preview' }}
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const pegawaiMeta = @json($pegawaiMeta);
    const pembiayaanParsed = @json($pembiayaanParsed);
    const kodeSatker = @json($kodeSatker);
    const bulanId = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    // 1) Auto-isi Jabatan, Pangkat/Golongan, Unit Kerja saat Pegawai dipilih.
    const selPegawai = document.getElementById('pegawai_id');
    const inpJab = document.getElementById('jabatan');
    const inpPangkat = document.getElementById('pangkat_golongan');
    const inpUnit = document.getElementById('unit_kerja');

    selPegawai?.addEventListener('change', () => {
        const meta = pegawaiMeta[selPegawai.value];
        if (!meta) return;
        inpJab.value = meta.jabatan || '';
        inpPangkat.value = meta.pangkat_golongan || '';
        if (meta.unit_kerja) inpUnit.value = meta.unit_kerja;
    });

    // 2) Auto-rakit Nomor ST (B-{urut}/71050/{kode}/{tahun}) selama user belum edit manual.
    const inpUrut = document.getElementById('nomor_st_urut');
    const inpKode = document.getElementById('kode_klasifikasi');
    const inpTahun = document.getElementById('tahun');
    const inpNomorSt = document.getElementById('nomor_st');
    let nomorStManual = Boolean(inpNomorSt.value.trim());

    const syncNomorSt = () => {
        if (nomorStManual) return;
        const u = inpUrut.value.trim();
        const k = inpKode.value.trim() || 'KP.650';
        const t = inpTahun.value.trim() || new Date().getFullYear();
        inpNomorSt.value = u ? `B-${u}/${kodeSatker}/${k}/${t}` : '';
    };
    inpNomorSt?.addEventListener('input', () => { nomorStManual = inpNomorSt.value.trim() !== ''; });
    [inpUrut, inpKode, inpTahun].forEach(el => el?.addEventListener('input', syncNomorSt));

    // 3) Auto-rakit Nomor SPD dari input bantu ({urut}/SPD_71050/{keg}/{bln}/{tahun}).
    const inpSpdHelper = document.getElementById('nomor_spd_urut_helper');
    const inpNomorSpd = document.getElementById('nomor_spd');
    const inpKegKode = document.getElementById('kegiatan_kode');
    const inpTglSt = document.getElementById('tanggal_st');

    const syncNomorSpd = () => {
        const u = inpSpdHelper.value.trim();
        if (!u) return;
        const keg = inpKegKode.value.trim() || '2906';
        const tgl = inpTglSt.value ? new Date(inpTglSt.value + 'T00:00:00') : new Date();
        const bln = String(tgl.getMonth() + 1).padStart(2, '0');
        const thn = inpTahun.value.trim() || tgl.getFullYear();
        inpNomorSpd.value = `${u}/SPD_${kodeSatker}/${keg}/${bln}/${thn}`;
    };
    [inpSpdHelper, inpKegKode, inpTglSt, inpTahun].forEach(el => el?.addEventListener('input', syncNomorSpd));

    // 4) Auto-isi Pembebanan Anggaran dari Master Pembiayaan.
    const selPemb = document.getElementById('pembiayaan_id');
    selPemb?.addEventListener('change', () => {
        const parsed = pembiayaanParsed[selPemb.value];
        if (!parsed) return;
        ['program_kode','program_nama','kegiatan_kode','kegiatan_nama','komponen_kode','komponen_nama','mata_anggaran'].forEach(k => {
            const el = document.getElementById(k);
            if (el && parsed[k] !== undefined) el.value = parsed[k];
        });
        syncNomorSpd();
    });

    // 5) Auto-hitung jumlah hari & format Waktu Pelaksanaan dari tanggal berangkat/kembali.
    const inpTglBerangkat = document.getElementById('tanggal_berangkat');
    const inpTglKembali = document.getElementById('tanggal_kembali');
    const inpHari = document.getElementById('jumlah_hari');
    const inpWaktu = document.getElementById('waktu_pelaksanaan');
    let waktuManual = Boolean(inpWaktu.value.trim());

    inpWaktu?.addEventListener('input', () => { waktuManual = inpWaktu.value.trim() !== ''; });

    const syncDurasi = () => {
        if (!inpTglBerangkat.value) return;
        if (!inpTglKembali.value || inpTglKembali.value < inpTglBerangkat.value) {
            inpTglKembali.value = inpTglBerangkat.value;
        }
        const d1 = new Date(inpTglBerangkat.value + 'T00:00:00');
        const d2 = new Date(inpTglKembali.value + 'T00:00:00');
        const diff = Math.max(1, Math.round((d2 - d1) / 86400000) + 1);
        inpHari.value = diff;

        if (!waktuManual) {
            if (inpTglBerangkat.value === inpTglKembali.value) {
                inpWaktu.value = `${d1.getDate()} ${bulanId[d1.getMonth()]} ${d1.getFullYear()}`;
            } else if (d1.getMonth() === d2.getMonth() && d1.getFullYear() === d2.getFullYear()) {
                inpWaktu.value = `${d1.getDate()}-${d2.getDate()} ${bulanId[d1.getMonth()]} ${d1.getFullYear()}`;
            } else {
                inpWaktu.value = `${d1.getDate()} ${bulanId[d1.getMonth()]} - ${d2.getDate()} ${bulanId[d2.getMonth()]} ${d2.getFullYear()}`;
            }
        }
    };
    [inpTglBerangkat, inpTglKembali].forEach(el => el?.addEventListener('change', syncDurasi));
    if (!inpWaktu.value.trim()) syncDurasi();

    // 6) Repeater Rute Visum SPD (1..4 tujuan).
    const container = document.getElementById('rutes-container');
    const btnAdd = document.getElementById('btn-add-rute');

    const reindexRutes = () => {
        const rows = container.querySelectorAll('.rute-row');
        rows.forEach((row, i) => {
            row.querySelector('.rute-no').textContent = i + 1;
            row.querySelectorAll('input').forEach(inp => {
                inp.name = inp.name.replace(/rutes\[\d+\]/, `rutes[${i}]`);
            });
            const rm = row.querySelector('.btn-remove-rute');
            rm.style.visibility = rows.length > 1 ? 'visible' : 'hidden';
        });
        btnAdd.disabled = rows.length >= 4;
        btnAdd.classList.toggle('opacity-50', rows.length >= 4);
    };

    btnAdd?.addEventListener('click', () => {
        const rows = container.querySelectorAll('.rute-row');
        if (rows.length >= 4) return;
        const clone = rows[rows.length - 1].cloneNode(true);
        const defaultDate = inpTglKembali.value || inpTglBerangkat.value || '';
        clone.querySelectorAll('input').forEach(inp => {
            if (inp.type === 'text') inp.value = '';
            if (inp.type === 'date') inp.value = defaultDate;
        });
        container.appendChild(clone);
        reindexRutes();
    });

    container?.addEventListener('click', (e) => {
        if (!e.target.classList.contains('btn-remove-rute')) return;
        const rows = container.querySelectorAll('.rute-row');
        if (rows.length <= 1) return;
        e.target.closest('.rute-row').remove();
        reindexRutes();
    });

    reindexRutes();
});
</script>
