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
     * Cetak PDF dari DOCX hasil upload (dari Word).
     *
     * DOCX diurai langsung dari XML sehingga penomoran list, tabel, dan
     * gambar dipertahankan. HTML output dibalut CSS yang sama dengan
     * template PDF KAK agar tampilannya seragam.
     */
    public function exportEditedPdf(Request $request, Kak $kak): Response
    {
        $ukuran = strtolower($request->query('ukuran', 'a4'));

        $disk = Storage::disk('local');
        $editedPath = $kak->docx_edited_path ?? "kak/{$kak->id}/edited.docx";

        if (! $disk->exists($editedPath)) {
            abort(404, 'Dokumen belum diunggah.');
        }

        $converter = new \App\Services\DocxToPdfConverter;
        $body = $converter->toHtml($disk->path($editedPath));

        // Ukuran kertas: query ?ukuran=a4|f4 menimpa ukuran dari dokumen.
        $paper = $ukuran === 'a4' || $ukuran === 'f4' || $ukuran === 'folio'
            ? $this->paperSize($ukuran)
            : $converter->paperSize();

        $paperSizeCss = is_array($paper) ? sprintf('%fpt %fpt', $paper[2], $paper[3]) : $paper;

        $fullHtml = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>KAK — {$kak->judul}</title>
<style>
    @page { size: {$paperSizeCss}; }
    * { font-family: 'Times New Roman', 'DejaVu Serif', serif; }
    body { font-size: 12pt; color: #000; line-height: 1.5; }
    p { margin: 0 0 8px; }
    p.spacer { margin: 0; line-height: 1.2; }
    p.logo { text-align: center; margin-bottom: 4px; }
    p.logo img { width: 90px; height: 70px; }
    p.bagian-judul { font-weight: bold; text-transform: uppercase; margin: 18px 0 8px; }
    ol { margin: 0 0 8px; padding-left: 24px; text-align: justify; }
    ol li { margin-bottom: 4px; }
    table { border-collapse: collapse; width: 100%; margin: 8px 0; }
    table.data-table, .data-table td { border: 1px solid #000; }
    .data-table td { padding: 6px 8px; vertical-align: top; font-size: 11pt; }
    table.ttd-table { border: none; margin-top: 40px; }
    .ttd-table td { border: none; text-align: center; vertical-align: top; width: 50%; padding: 0 12px; }
    img { max-width: 100%; height: auto; }
</style>
</head>
<body>
{$body}
</body>
</html>
HTML;

        $pdf = Pdf::loadHTML($fullHtml)
            ->setPaper($paper, 'portrait')
            ->setWarnings(false);

        $namaFile = 'KAK-'.str($kak->judul)->slug().'-edited.pdf';

        return $pdf->stream($namaFile);
    }

    /**
     * Unggah DOCX hasil edit dari Word (multipart) lalu simpan sebagai
     * edited.docx untuk dicetak via exportEditedPdf.
     */
    public function uploadDocx(Request $request, Kak $kak): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:docx', 'max:10240'],
        ]);

        $disk = Storage::disk('local');
        $edited = 'kak/'.$kak->id.'/edited.docx';

        $disk->putFileAs(dirname($edited), $request->file('file'), basename($edited));
        $kak->update(['docx_edited_path' => $edited]);

        return back()->with('status', 'Dokumen DOCX berhasil diunggah.');
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
