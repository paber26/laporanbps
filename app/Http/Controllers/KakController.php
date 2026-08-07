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
}
