<?php

namespace App\Http\Controllers;

use App\Models\Kak;
use App\Models\Pegawai;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class KakController extends Controller
{
    public function index(): View
    {
        $kaks = Kak::with(['pegawai'])
            ->latest()
            ->paginate(10);

        return view('kak.index', compact('kaks'));
    }

    public function create(): View
    {
        return view('kak.create', [
            'pegawais' => Pegawai::orderBy('nama')->get(),
            'defaults' => $this->exampleDefaults(),
            'defaultAnggarans' => $this->exampleAnggarans(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $kak = DB::transaction(function () use ($request, $data) {
            $kak = Kak::create($data);
            $this->syncAnggaran($request, $kak);

            return $kak;
        });

        return redirect()
            ->route('kak.show', $kak)
            ->with('status', 'KAK berhasil dibuat.');
    }

    public function show(Kak $kak): View
    {
        $kak->load(['pegawai', 'anggarans']);

        return view('kak.show', compact('kak'));
    }

    public function edit(Kak $kak): View
    {
        $kak->load(['anggarans']);

        return view('kak.edit', [
            'kak' => $kak,
            'pegawais' => Pegawai::orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, Kak $kak): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $kak, $data) {
            $kak->update($data);
            $this->syncAnggaran($request, $kak);
        });

        return redirect()
            ->route('kak.show', $kak)
            ->with('status', 'KAK berhasil diperbarui.');
    }

    public function destroy(Kak $kak): RedirectResponse
    {
        $kak->delete();

        return redirect()
            ->route('kak.index')
            ->with('status', 'KAK berhasil dihapus.');
    }

    /**
     * Cetak KAK sebagai dokumen Microsoft Word (.docx).
     */
    public function exportWord(Kak $kak): BinaryFileResponse
    {
        $kak->load(['anggarans']);

        $path = app(\App\Services\KakWordExporter::class)->generate($kak);

        $namaFile = 'KAK-'.str($kak->judul)->slug().'.docx';

        return response()->download($path, $namaFile)->deleteFileAfterSend(true);
    }

    /**
     * Cetak KAK sebagai PDF.
     */
    public function exportPdf(Kak $kak): Response
    {
        $kak->load(['anggarans']);

        $pdf = Pdf::loadView('kak.pdf-template', compact('kak'))
            ->setPaper('a4', 'portrait');

        $namaFile = 'KAK-'.str($kak->judul)->slug().'.pdf';

        return $pdf->stream($namaFile);
    }

    /**
     * Validasi field KAK.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'tahun' => ['nullable', 'string', 'max:20'],
            'unit_kerja' => ['nullable', 'string', 'max:255'],
            'pegawai_id' => ['nullable', 'exists:pegawais,id'],
            'dasar_hukum' => ['nullable', 'string'],
            'gambaran_umum' => ['nullable', 'string'],
            'maksud_tujuan' => ['nullable', 'string'],
            'keluaran' => ['nullable', 'string'],
            'pelaksana_kegiatan' => ['nullable', 'string'],
            'jadwal_lokasi' => ['nullable', 'string'],
            'sumber_pembiayaan' => ['nullable', 'string'],
            'penutup' => ['nullable', 'string'],
            'tempat_penandatanganan' => ['nullable', 'string', 'max:255'],
            'tanggal_penandatanganan' => ['nullable', 'date'],
            'ttd_kiri_jabatan' => ['nullable', 'string', 'max:255'],
            'ttd_kiri_nama' => ['nullable', 'string', 'max:255'],
            'ttd_kiri_nip' => ['nullable', 'string', 'max:255'],
            'ttd_kanan_jabatan' => ['nullable', 'string', 'max:255'],
            'ttd_kanan_nama' => ['nullable', 'string', 'max:255'],
            'ttd_kanan_nip' => ['nullable', 'string', 'max:255'],

            'anggarans' => ['nullable', 'array'],
            'anggarans.*.kode_mak' => ['nullable', 'string', 'max:255'],
            'anggarans.*.deskripsi' => ['nullable', 'string', 'max:255'],
            'anggarans.*.volume' => ['nullable', 'numeric'],
            'anggarans.*.satuan' => ['nullable', 'string', 'max:50'],
            'anggarans.*.harga_satuan' => ['nullable', 'numeric'],
            'anggarans.*.jumlah_biaya' => ['nullable', 'numeric'],
        ]);
    }

    /**
     * Sinkronkan baris rincian anggaran dari repeater form.
     */
    protected function syncAnggaran(Request $request, Kak $kak): void
    {
        $kak->anggarans()->delete();

        foreach (array_values($request->input('anggarans', [])) as $i => $baris) {
            $deskripsi = trim((string) ($baris['deskripsi'] ?? ''));
            $kode = trim((string) ($baris['kode_mak'] ?? ''));

            if ($deskripsi === '' && $kode === '') {
                continue;
            }

            $volume = (float) ($baris['volume'] ?? 0);
            $harga = (float) ($baris['harga_satuan'] ?? 0);
            $jumlah = $baris['jumlah_biaya'] !== null && $baris['jumlah_biaya'] !== ''
                ? (float) $baris['jumlah_biaya']
                : $volume * $harga;

            $kak->anggarans()->create([
                'kode_mak' => $kode,
                'deskripsi' => $deskripsi,
                'volume' => $volume,
                'satuan' => trim((string) ($baris['satuan'] ?? '')),
                'harga_satuan' => $harga,
                'jumlah_biaya' => $jumlah,
                'urutan' => $i + 1,
            ]);
        }
    }

    /**
     * Nilai awal contoh KAK (contoh: KAK Perjalanan Rakornas SE2026).
     * Dipakai sebagai isian awal pada halaman "Buat KAK" agar pengguna
     * tinggal menyunting. Bagian-bagian diisi sebagai HTML (CKEditor).
     *
     * @return array<string, mixed>
     */
    protected function exampleDefaults(): array
    {
        return [
            'judul' => 'Perjalanan Dalam Rangka Rapat Koordinasi Nasional Sensus Ekonomi 2026',
            'tahun' => '2026',
            'unit_kerja' => 'Badan Pusat Statistik Kabupaten Minahasa Selatan',

            'dasar_hukum' => '<p>Dasar hukum yang digunakan dalam kegiatan ini adalah sebagai berikut:</p>'
                .'<ol>'
                .'<li>Undang-Undang Nomor 16 Tahun 1997 tentang Statistik (Lembaran Negara Republik Indonesia Tahun 1997 Nomor 39, Tambahan Lembaran Negara Republik Indonesia Nomor 3683);</li>'
                .'<li>Undang-Undang Nomor 17 Tahun 2003 tentang Keuangan Negara;</li>'
                .'<li>Peraturan Pemerintah Nomor 51 Tahun 1999 tentang Penyelenggaraan Statistik (Lembaran Negara Republik Indonesia Tahun 1999 Nomor 96, Tambahan Lembaran Negara Republik Indonesia Nomor 3854);</li>'
                .'<li>Peraturan Presiden Nomor 39 Tahun 2019 tentang Satu Data Indonesia (Lembaran Negara Republik Indonesia Tahun 2019 Nomor 112);</li>'
                .'<li>Peraturan Menteri Keuangan Republik Indonesia Nomor 51/PMK.02/2014 tentang Perubahan Atas Peraturan Menteri Keuangan Nomor 71/PMK.02/2013 tentang Pedoman Standar Biaya, Standar Struktur Biaya, dan Indeksasi dalam Penyusunan Rencana Kerja dan Anggaran Kementerian Negara/Lembaga (Berita Negara Republik Indonesia Tahun 2014 Nomor 342);</li>'
                .'<li>Peraturan Menteri Keuangan Republik Indonesia Nomor 62 Tahun 2023 tentang Perencanaan Anggaran, Pelaksanaan Anggaran serta Akuntansi dan Pelaporan Keuangan (Berita Negara Republik Indonesia Tahun 2023 Nomor 472) sebagaimana telah diubah dengan Peraturan Menteri Keuangan Republik Indonesia Nomor 107 Tahun 2024;</li>'
                .'<li>Peraturan Menteri Keuangan Republik Indonesia Nomor 32 Tahun 2025 tentang Standar Biaya Masukan Tahun Anggaran 2026;</li>'
                .'<li>Peraturan Menteri Keuangan Republik Indonesia Nomor 119 Tahun 2023 tentang Perubahan Atas Peraturan Menteri Keuangan Nomor 113/PMK.05/2012 tentang Perjalanan Dinas Dalam Negeri Bagi Pejabat Negara, Pegawai Negeri, dan Pegawai Tidak Tetap.</li>'
                .'</ol>',

            'gambaran_umum' => '<p>Sensus Ekonomi 2026 (SE2026) merupakan Sensus Ekonomi yang kelima yang akan dilaksanakan di Indonesia. Sensus Ekonomi dilaksanakan setiap 10 tahun sekali di tahun yang berakhiran 6 sesuai dengan amanat Undang-Undang Nomor 16 Tahun 1997 tentang Statistik.</p>'
                .'<p>SE2026 dilaksanakan dengan tujuan umum untuk menyediakan data dasar seluruh kegiatan ekonomi, kecuali lapangan usaha Pertanian, Kehutanan, dan Perikanan (A), Administrasi Pemerintahan, Pertahanan, dan Jaminan Sosial Wajib (O), dan Aktivitas Rumah Tangga sebagai Pemberi Kerja; Aktivitas yang Menghasilkan Barang Jasa oleh Rumah Tangga (T), sebagai landasan bagi penyusunan kebijakan dan perencanaan pembangunan nasional.</p>'
                .'<p>Adapun tujuan khusus dilaksanakannya SE2026 yaitu menyediakan informasi struktur ekonomi, menyediakan informasi karakteristik usaha, dan menyediakan informasi ekonomi digital dan ekonomi lingkungan. SE2026 direncanakan dilaksanakan pada bulan Mei 2026 untuk Perusahaan Besar dengan metode CAWI untuk pengumpulan datanya, dan bulan Juni-Juli 2026 untuk Perusahaan Mikro, Kecil, dan Menengah dengan metode CAPI untuk pengumpulan datanya.</p>',

            'maksud_tujuan' => '<p>Maksud dan tujuan dilaksanakannya kegiatan ini yaitu memperkuat koordinasi antara BPS Pusat dan BPS Provinsi/Kabupaten/Kota dalam rangka persiapan pelaksanaan kegiatan Sensus Ekonomi 2026.</p>',

            'keluaran' => '<p>Terlaksananya koordinasi yang efektif antara BPS Pusat dan BPS Provinsi/Kabupaten/Kota dalam rangka persiapan pelaksanaan kegiatan Sensus Ekonomi 2026.</p>',

            'pelaksana_kegiatan' => '<p>Rancangan pelaksana kegiatan adalah Kepala BPS Kabupaten Minahasa Selatan.</p>',

            'jadwal_lokasi' => '<p>Kegiatan dijadwalkan akan dilaksanakan pada rentang 31 Januari - 3 Februari 2026 bertempat di wilayah Provinsi DKI Jakarta.</p>',

            'sumber_pembiayaan' => '<p>Biaya yang dibutuhkan dalam kegiatan ini adalah sebesar Rp. 7.000.000,- (Tujuh Juta Rupiah) yang akan dibebankan pada DIPA BPS Kabupaten Minahasa Selatan T.A. 2026 dengan rincian anggaran biaya sebagai berikut:</p>',

            'penutup' => '<ol>'
                .'<li>Apabila terdapat hal-hal yang bertentangan dengan ketentuan, peraturan, pedoman, dan kebijaksanaan pemerintah yang berlaku, maka segala yang termaktub di dalam Kerangka Acuan Kegiatan (KAK) akan diteliti kembali.</li>'
                .'<li>Hal-hal yang belum diatur dalam KAK akan ditetapkan lebih lanjut.</li>'
                .'<li>Demikian KAK ini dibuat untuk dipergunakan semestinya.</li>'
                .'</ol>',

            'tempat_penandatanganan' => 'Minahasa Selatan',
            'tanggal_penandatanganan' => '2026-01-28',

            'ttd_kiri_jabatan' => 'Ketua Tim Pelaksana SE2026',
            'ttd_kiri_nama' => 'Johanes, S.ST',
            'ttd_kiri_nip' => '198005112002121003',

            'ttd_kanan_jabatan' => 'Pejabat Pembuat Komitmen',
            'ttd_kanan_nama' => 'Afwin Fauzy Akhsan S.Tr.Stat',
            'ttd_kanan_nip' => '199609222019011002',
        ];
    }

    /**
     * Baris contoh rincian anggaran (bagian G).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function exampleAnggarans(): array
    {
        return [
            [
                'kode_mak' => '2902.BMA.006',
                'deskripsi' => 'Perjalanan dinas dalam rangka rakornas',
                'volume' => 1,
                'satuan' => 'O-P',
                'harga_satuan' => 7000000,
                'jumlah_biaya' => 7000000,
            ],
        ];
    }
}
