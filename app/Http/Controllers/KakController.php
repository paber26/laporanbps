<?php

namespace App\Http\Controllers;

use App\Models\Kak;
use App\Models\Pegawai;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            'defaults' => \App\Support\KakExample::fields(),
            'defaultAnggarans' => \App\Support\KakExample::anggarans(),
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
    public function exportWord(Request $request, Kak $kak): BinaryFileResponse
    {
        $kak->load(['anggarans']);

        $ukuran = strtolower($request->query('ukuran', 'a4'));

        $path = app(\App\Services\KakWordExporter::class)->generate($kak, $ukuran);

        $namaFile = 'KAK-'.str($kak->judul)->slug().'.docx';

        return response()->download($path, $namaFile)->deleteFileAfterSend(true);
    }

    /**
     * Cetak KAK sebagai PDF dengan pilihan ukuran kertas (A4 / F4).
     */
    public function exportPdf(Request $request, Kak $kak): Response
    {
        $kak->load(['anggarans']);

        $ukuran = strtolower($request->query('ukuran', 'a4'));
        $paper = $this->paperSize($ukuran);

        $pdf = Pdf::loadView('kak.pdf-template', compact('kak'))
            ->setPaper($paper, 'portrait');

        $namaFile = 'KAK-'.str($kak->judul)->slug().'.pdf';

        return $pdf->stream($namaFile);
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
            default => 'a4',
        };
    }

    /**
     * Halaman editor DOCX WYSIWYG di browser.
     */
    public function editDocx(Kak $kak): View
    {
        // Pastikan DOCX asli sudah tersedia di penyimpanan sebelum editor dibuka.
        $this->generateDocx($kak);

        $kak->load(['anggarans']);

        return view('kak.edit-docx', compact('kak'));
    }

    /**
     * Generate & simpan DOCX asli (original) ke penyimpanan bila belum ada.
     * Original tetap utuh; versi edit disimpan terpisah.
     *
     * @return array{original_exists: bool, edited_exists: bool}
     */
    public function generateDocx(Kak $kak): array
    {
        $kak->load(['anggarans']);

        $disk = Storage::disk('local');
        $dir = 'kak/'.$kak->id;
        $original = $dir.'/original.docx';
        $edited = $dir.'/edited.docx';

        if (! $disk->exists($original)) {
            $path = app(\App\Services\KakWordExporter::class)->generate($kak, 'a4');
            $disk->put($original, file_get_contents($path));
            @unlink($path);

            $kak->update(['docx_original_path' => $original]);
        }

        if ($disk->exists($edited) && $kak->docx_edited_path !== $edited) {
            $kak->update(['docx_edited_path' => $edited]);
        }

        return [
            'original_exists' => $disk->exists($original),
            'edited_exists' => $disk->exists($edited),
        ];
    }

    /**
     * Kirim file DOCX asli untuk dibuka editor (inline, bukan download).
     */
    public function getOriginalDocx(Kak $kak): Response
    {
        return $this->streamDocx($kak->docx_original_path, 'KAK-'.str($kak->judul)->slug().'-original.docx');
    }

    /**
     * Kirim file DOCX hasil edit.
     */
    public function getEditedDocx(Kak $kak): Response
    {
        return $this->streamDocx($kak->docx_edited_path, 'KAK-'.str($kak->judul)->slug().'-edited.docx');
    }

    /**
     * Simpan versi edit DOCX (overwrite edited.docx).
     */
    public function saveEditedDocx(Request $request, Kak $kak): Response
    {
        $bytes = $request->getContent();

        if ($bytes === '' || strlen($bytes) < 10) {
            return response()->json(['error' => 'Berkas kosong atau tidak valid.'], 422);
        }

        $disk = Storage::disk('local');
        $edited = 'kak/'.$kak->id.'/edited.docx';

        $disk->put($edited, $bytes);
        $kak->update(['docx_edited_path' => $edited]);

        return response()->json(['ok' => true]);
    }

    /**
     * Alirkan file DOCX sebagai inline response bila ada.
     */
    protected function streamDocx(?string $path, string $namaFile): Response
    {
        $disk = Storage::disk('local');

        if (! $path || ! $disk->exists($path)) {
            return response()->json(['error' => 'Dokumen belum tersedia.'], 404);
        }

        return response($disk->get($path), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'inline; filename="'.$namaFile.'"',
        ]);
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
}
