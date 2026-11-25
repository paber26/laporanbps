<?php

namespace App\Http\Controllers;

use App\Models\MasterPembiayaan;
use App\Models\Pegawai;
use App\Models\SuratTugas;
use App\Services\SuratTugasWordExporter;
use App\Support\ChromePdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class SuratTugasController extends Controller
{
    public function index(Request $request): View
    {
        $query = SuratTugas::with(['pegawai', 'rutes', 'user']);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_st', 'like', "%{$search}%")
                    ->orWhere('nomor_spd', 'like', "%{$search}%")
                    ->orWhere('tujuan_kegiatan', 'like', "%{$search}%")
                    ->orWhere('menimbang', 'like', "%{$search}%")
                    ->orWhereHas('pegawai', function ($pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%")
                            ->orWhere('nip', 'like', "%{$search}%");
                    });
            });
        }

        if ($pegawaiId = $request->input('pegawai_id')) {
            $query->where('pegawai_id', $pegawaiId);
        }

        if ($tahun = $request->input('tahun')) {
            $query->where('tahun', (int) $tahun);
        }

        if ($bulan = $request->input('bulan')) {
            $query->whereMonth('tanggal_st', (int) $bulan);
        }

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $suratTugasList = $query->latest('tanggal_st')->latest('id')->paginate($perPage)->withQueryString();

        $pegawais = Pegawai::orderBy('nama')->get(['id', 'nama', 'nip']);

        $tahuns = SuratTugas::query()
            ->select('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        if ($tahuns->isEmpty()) {
            $tahuns = collect([now()->year]);
        }

        $bulans = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('surat_tugas.index', compact(
            'suratTugasList',
            'pegawais',
            'tahuns',
            'bulans',
            'perPage'
        ));
    }

    public function create(): View
    {
        return view('surat_tugas.create', $this->formContext());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $suratTugas = DB::transaction(function () use ($request, $data) {
            $rutes = $data['rutes'];
            unset($data['rutes']);

            $suratTugas = $request->user()->suratTugas()->create($data);
            $this->syncRutes($suratTugas, $rutes);

            return $suratTugas;
        });

        return redirect()
            ->route('surat-tugas.show', $suratTugas)
            ->with('status', 'Surat Tugas berhasil dibuat.');
    }

    public function show(SuratTugas $suratTugas): View
    {
        $suratTugas->load(['pegawai', 'pembiayaan', 'rutes']);

        return view('surat_tugas.show', compact('suratTugas'));
    }

    public function edit(SuratTugas $suratTugas): View
    {
        $this->authorize('update', $suratTugas);

        $suratTugas->load(['pegawai', 'pembiayaan', 'rutes']);

        return view('surat_tugas.edit', array_merge(
            $this->formContext(),
            ['suratTugas' => $suratTugas]
        ));
    }

    public function update(Request $request, SuratTugas $suratTugas): RedirectResponse
    {
        $this->authorize('update', $suratTugas);

        $data = $this->validated($request);

        DB::transaction(function () use ($suratTugas, $data) {
            $rutes = $data['rutes'];
            unset($data['rutes']);

            $suratTugas->update($data);
            $suratTugas->rutes()->delete();
            $this->syncRutes($suratTugas, $rutes);
        });

        return redirect()
            ->route('surat-tugas.show', $suratTugas)
            ->with('status', 'Surat Tugas berhasil diperbarui.');
    }

    public function destroy(SuratTugas $suratTugas): RedirectResponse
    {
        $this->authorize('delete', $suratTugas);

        $suratTugas->delete();

        return redirect()
            ->route('surat-tugas.index')
            ->with('status', 'Surat Tugas berhasil dihapus.');
    }

    /**
     * Duplikat Surat Tugas beserta seluruh rute Visum-nya ke user aktif.
     */
    public function duplicate(Request $request, SuratTugas $suratTugas): RedirectResponse
    {
        $suratTugas->load('rutes');

        $copy = DB::transaction(function () use ($request, $suratTugas) {
            $copy = $suratTugas->replicate();
            $copy->user_id = $request->user()->id;
            $copy->nomor_st_urut = $suratTugas->nomor_st_urut.'-salinan';
            $copy->nomor_st = 'B-'.$copy->nomor_st_urut.'/'.config('laporan.surat_tugas.kode_satker', '71050').'/'.$copy->kode_klasifikasi.'/'.$copy->tahun;
            $copy->save();

            foreach ($suratTugas->rutes as $rute) {
                $copy->rutes()->create([
                    'urutan' => $rute->urutan,
                    'kecamatan_tujuan' => $rute->kecamatan_tujuan,
                    'tanggal_tiba' => $rute->tanggal_tiba,
                    'tanggal_berangkat' => $rute->tanggal_berangkat,
                ]);
            }

            return $copy;
        });

        return redirect()
            ->route('surat-tugas.edit', $copy)
            ->with('status', 'Surat Tugas berhasil diduplikat. Silakan sesuaikan nomor ST/SPD dan detailnya.');
    }

    /**
     * Cetak Surat Tugas + SPD + Visum (3 halaman) sebagai PDF via Chrome headless.
     */
    public function exportPdf(Request $request, SuratTugas $suratTugas): Response
    {
        $suratTugas->load(['pegawai', 'pembiayaan', 'rutes']);

        $ukuran = strtolower((string) $request->query('ukuran', 'f4'));
        $paperCss = $this->paperCss($ukuran);

        $html = view('surat_tugas.pdf-chrome', compact('suratTugas', 'paperCss'))->render();
        $pdf = ChromePdf::fromHtml($html);

        $namaFile = $this->downloadFilename($suratTugas, 'pdf');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$namaFile.'"',
        ]);
    }

    /**
     * Cetak Surat Tugas + SPD + Visum (3 halaman) sebagai dokumen Word (.docx).
     */
    public function exportWord(Request $request, SuratTugas $suratTugas): BinaryFileResponse
    {
        $suratTugas->load(['pegawai', 'pembiayaan', 'rutes']);

        $ukuran = strtolower((string) $request->query('ukuran', 'f4'));
        $path = app(SuratTugasWordExporter::class)->generate($suratTugas, $ukuran);
        $namaFile = $this->downloadFilename($suratTugas, 'docx');

        return response()->download($path, $namaFile)->deleteFileAfterSend(true);
    }

    /**
     * Data pendukung form create/edit.
     *
     * @return array<string, mixed>
     */
    protected function formContext(): array
    {
        $pegawais = Pegawai::orderBy('nama')->get();
        $pembiayaans = MasterPembiayaan::orderBy('program')->get();
        $pembiayaanParsed = $pembiayaans->mapWithKeys(fn (MasterPembiayaan $p) => [
            $p->id => SuratTugas::parsePembiayaan($p),
        ]);

        return [
            'pegawais' => $pegawais,
            'pembiayaans' => $pembiayaans,
            'pembiayaanParsed' => $pembiayaanParsed,
            'kecamatans' => config('laporan.surat_tugas.kecamatans', []),
            'defaults' => config('laporan.surat_tugas', []),
        ];
    }

    /**
     * Validasi & normalisasi data Surat Tugas.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'pegawai_id' => ['required', 'exists:pegawais,id'],
            'pembiayaan_id' => ['nullable', 'exists:master_pembiayaans,id'],

            'nomor_st_urut' => ['required', 'string', 'max:50'],
            'kode_klasifikasi' => ['required', 'string', 'max:50'],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'nomor_st' => ['required', 'string', 'max:255'],
            'nomor_spd' => ['nullable', 'string', 'max:255'],
            'tempat_st' => ['required', 'string', 'max:255'],
            'tanggal_st' => ['required', 'date'],

            'menimbang' => ['required', 'string'],
            'mengingat' => ['required', 'string'],
            'tujuan_kegiatan' => ['required', 'string'],
            'waktu_pelaksanaan' => ['required', 'string', 'max:255'],

            'jabatan' => ['nullable', 'string', 'max:255'],
            'pangkat_golongan' => ['nullable', 'string', 'max:255'],
            'unit_kerja' => ['required', 'string', 'max:255'],
            'tingkat_biaya' => ['required', 'string', 'max:20'],
            'alat_angkutan' => ['required', 'string', 'max:255'],
            'tempat_berangkat' => ['required', 'string', 'max:255'],
            'tempat_tujuan' => ['required', 'string', 'max:255'],
            'kecamatan_asal' => ['required', 'string', 'max:255'],
            'jumlah_hari' => ['required', 'integer', 'min:1', 'max:365'],
            'tanggal_berangkat' => ['required', 'date'],
            'tanggal_kembali' => ['required', 'date', 'after_or_equal:tanggal_berangkat'],
            'pengikut' => ['nullable', 'string'],
            'keterangan_lain' => ['nullable', 'string'],
            'tembusan' => ['nullable', 'string'],

            'mata_anggaran' => ['nullable', 'string', 'max:255'],
            'program_kode' => ['nullable', 'string', 'max:50'],
            'program_nama' => ['nullable', 'string', 'max:255'],
            'kegiatan_kode' => ['nullable', 'string', 'max:50'],
            'kegiatan_nama' => ['nullable', 'string', 'max:255'],
            'komponen_kode' => ['nullable', 'string', 'max:50'],
            'komponen_nama' => ['nullable', 'string', 'max:255'],
            'instansi_anggaran' => ['required', 'string', 'max:255'],

            'kepala_jabatan' => ['required', 'string', 'max:255'],
            'kepala_nama' => ['required', 'string', 'max:255'],
            'kepala_nip' => ['required', 'string', 'max:50'],
            'ppk_jabatan' => ['required', 'string', 'max:255'],
            'ppk_sub_jabatan' => ['nullable', 'string', 'max:255'],
            'ppk_nama' => ['required', 'string', 'max:255'],
            'ppk_nip' => ['required', 'string', 'max:50'],
            'sertakan_ttd_kepala' => ['nullable', 'boolean'],

            'rutes' => ['required', 'array', 'min:1', 'max:4'],
            'rutes.*.kecamatan_tujuan' => ['required', 'string', 'max:255'],
            'rutes.*.tanggal_tiba' => ['required', 'date'],
            'rutes.*.tanggal_berangkat' => ['nullable', 'date'],
        ]);

        $validated['sertakan_ttd_kepala'] = $request->boolean('sertakan_ttd_kepala');

        return $validated;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rutes
     */
    protected function syncRutes(SuratTugas $suratTugas, array $rutes): void
    {
        foreach (array_values($rutes) as $idx => $rute) {
            $suratTugas->rutes()->create([
                'urutan' => $idx + 1,
                'kecamatan_tujuan' => trim((string) $rute['kecamatan_tujuan']),
                'tanggal_tiba' => $rute['tanggal_tiba'],
                'tanggal_berangkat' => ! empty($rute['tanggal_berangkat']) ? $rute['tanggal_berangkat'] : $rute['tanggal_tiba'],
            ]);
        }
    }

    protected function paperCss(string $ukuran): string
    {
        return match ($ukuran) {
            'legal' => '216mm 356mm',
            'a4' => '210mm 297mm',
            default => '215mm 330mm', // F4 / Folio
        };
    }

    protected function downloadFilename(SuratTugas $suratTugas, string $ext): string
    {
        $no = str($suratTugas->nomor_st_urut)->slug();
        $nama = str($suratTugas->pegawai?->nama ?? 'pegawai')->slug();

        return "ST-{$no}-{$nama}.{$ext}";
    }
}
