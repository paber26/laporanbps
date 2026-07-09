<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\MasterPembiayaan;
use App\Models\Pegawai;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class LaporanController extends Controller
{
    /**
     * Daftar laporan.
     */
    public function index(): View
    {
        $laporans = Laporan::with(['pegawai', 'pembiayaan'])
            ->withCount(['uraians', 'dokumentasis'])
            ->latest()
            ->paginate(10);

        return view('laporan.index', compact('laporans'));
    }

    /**
     * Form pembuatan laporan baru.
     */
    public function create(): View
    {
        return view('laporan.create', [
            'pegawais' => Pegawai::orderBy('nama')->get(),
            'pembiayaans' => MasterPembiayaan::orderBy('program')->get(),
        ]);
    }

    /**
     * Simpan laporan baru beserta uraian & dokumentasi.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateLaporan($request);

        $laporan = DB::transaction(function () use ($request, $data) {
            $laporan = Laporan::create($data);

            $this->syncUraians($request, $laporan);
            $this->storeDokumentasi($request, $laporan);

            return $laporan;
        });

        return redirect()
            ->route('laporan.show', $laporan)
            ->with('status', 'Laporan berhasil dibuat.');
    }

    /**
     * Preview laporan (HTML) sebelum dicetak.
     */
    public function show(Laporan $laporan): View
    {
        $laporan->load(['pegawai', 'pembiayaan', 'uraians', 'dokumentasis']);

        return view('laporan.show', compact('laporan'));
    }

    /**
     * Form edit laporan.
     */
    public function edit(Laporan $laporan): View
    {
        $laporan->load(['uraians', 'dokumentasis']);

        return view('laporan.edit', [
            'laporan' => $laporan,
            'pegawais' => Pegawai::orderBy('nama')->get(),
            'pembiayaans' => MasterPembiayaan::orderBy('program')->get(),
        ]);
    }

    /**
     * Perbarui laporan.
     */
    public function update(Request $request, Laporan $laporan): RedirectResponse
    {
        $data = $this->validateLaporan($request);

        DB::transaction(function () use ($request, $laporan, $data) {
            $laporan->update($data);

            // Uraian: hapus lama, buat ulang dari input (murah karena hanya teks).
            $laporan->uraians()->delete();
            $this->syncUraians($request, $laporan);

            // Dokumentasi: hapus yang ditandai, tambah yang baru.
            $this->deleteMarkedDokumentasi($request, $laporan);
            $this->storeDokumentasi($request, $laporan);
        });

        return redirect()
            ->route('laporan.show', $laporan)
            ->with('status', 'Laporan berhasil diperbarui.');
    }

    /**
     * Hapus laporan beserta berkas dokumentasinya.
     */
    public function destroy(Laporan $laporan): RedirectResponse
    {
        DB::transaction(function () use ($laporan) {
            foreach ($laporan->dokumentasis as $dok) {
                Storage::disk('public')->delete($dok->image_path);
            }
            $laporan->delete();
        });

        return redirect()
            ->route('laporan.index')
            ->with('status', 'Laporan berhasil dihapus.');
    }

    /**
     * Cetak PDF dengan pilihan ukuran kertas (A4 / F4 / Legal).
     */
    public function exportPdf(Request $request, Laporan $laporan): Response
    {
        $laporan->load(['pegawai', 'pembiayaan', 'uraians', 'dokumentasis']);

        $ukuran = strtolower($request->query('ukuran', 'a4'));
        $paper = $this->paperSize($ukuran);

        $pdf = Pdf::loadView('laporan.pdf-template', compact('laporan'))
            ->setPaper($paper, 'portrait');

        $namaFile = 'Laporan-'.$laporan->id.'-'.str($laporan->pegawai->nama)->slug().'.pdf';

        return $pdf->download($namaFile);
    }

    /**
     * Cetak dokumen Microsoft Word (.docx).
     */
    public function exportWord(Laporan $laporan): BinaryFileResponse
    {
        $laporan->load(['pegawai', 'pembiayaan', 'uraians', 'dokumentasis']);

        $path = app(\App\Services\LaporanWordExporter::class)->generate($laporan);

        $namaFile = 'Laporan-'.$laporan->id.'-'.str($laporan->pegawai->nama)->slug().'.docx';

        return response()->download($path, $namaFile)->deleteFileAfterSend(true);
    }

    /**
     * Validasi field laporan.
     *
     * @return array<string, mixed>
     */
    protected function validateLaporan(Request $request): array
    {
        return $request->validate([
            'pegawai_id' => ['required', 'exists:pegawais,id'],
            'pembiayaan_id' => ['nullable', 'exists:master_pembiayaans,id'],
            'judul_laporan' => ['required', 'string', 'max:255'],
            'perihal_laporan' => ['required', 'string', 'max:255'],
            'tujuan_surat' => ['required', 'string', 'max:255'],
            'tempat_laporan' => ['required', 'string', 'max:255'],
            'tanggal_laporan' => ['required', 'date'],
            'lokasi_tujuan' => ['required', 'string', 'max:255'],

            'uraians' => ['required', 'array', 'min:1'],
            'uraians.*.tanggal_kegiatan' => ['required', 'date'],
            'uraians.*.jam_mulai' => ['nullable', 'string', 'max:20'],
            'uraians.*.jam_selesai' => ['nullable', 'string', 'max:20'],
            'uraians.*.uraian_text' => ['required', 'string'],

            'dokumentasi' => ['nullable', 'array'],
            'dokumentasi.*.file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'dokumentasi.*.keterangan' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * Buat baris uraian dari input repeater.
     */
    protected function syncUraians(Request $request, Laporan $laporan): void
    {
        foreach (array_values($request->input('uraians', [])) as $i => $uraian) {
            $laporan->uraians()->create([
                'tanggal_kegiatan' => $uraian['tanggal_kegiatan'],
                'jam_mulai' => $uraian['jam_mulai'] ?? null,
                'jam_selesai' => $uraian['jam_selesai'] ?? null,
                'uraian_text' => $uraian['uraian_text'],
                'urutan' => $i + 1,
            ]);
        }
    }

    /**
     * Simpan foto dokumentasi yang diunggah.
     */
    protected function storeDokumentasi(Request $request, Laporan $laporan): void
    {
        $mulai = (int) $laporan->dokumentasis()->max('urutan');

        // Gunakan key asli dari request agar akses file (dokumentasi.$i.file) tepat,
        // meski indeks tidak berurutan karena ada baris yang dihapus di form.
        foreach ($request->input('dokumentasi', []) as $i => $dok) {
            $file = $request->file("dokumentasi.$i.file");
            if (! $file) {
                continue;
            }

            $path = $file->store('dokumentasi/'.$laporan->id, 'public');

            $laporan->dokumentasis()->create([
                'image_path' => $path,
                'keterangan' => $dok['keterangan'] ?? null,
                'urutan' => ++$mulai,
            ]);
        }
    }

    /**
     * Hapus dokumentasi yang ditandai untuk dihapus (mode edit).
     */
    protected function deleteMarkedDokumentasi(Request $request, Laporan $laporan): void
    {
        $ids = $request->input('hapus_dokumentasi', []);
        if (empty($ids)) {
            return;
        }

        $docs = $laporan->dokumentasis()->whereIn('id', $ids)->get();
        foreach ($docs as $doc) {
            Storage::disk('public')->delete($doc->image_path);
            $doc->delete();
        }
    }

    /**
     * Peta ukuran kertas untuk dompdf.
     *
     * @return string|array<int, float>
     */
    protected function paperSize(string $ukuran): string|array
    {
        return match ($ukuran) {
            // F4 / Folio = 215mm x 330mm (dalam poin, 1mm = 2.83465pt).
            'f4', 'folio' => [0, 0, 609.45, 935.43],
            'legal' => 'legal',
            default => 'a4',
        };
    }
}
