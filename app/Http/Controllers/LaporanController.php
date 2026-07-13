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
     * Unggah foto dari CKEditor (Uraian Kegiatan).
     */
    public function uploadUraianImage(Request $request)
    {
        if ($request->hasFile('upload')) {
            $file = $request->file('upload');
            // Simpan ke tmp/uraian, secara permanen jika tidak dibersihkan cron. 
            // Atau lebih baik langsung simpan ke folder ckeditor agar permanen.
            $path = $this->storeImage($file, 'dokumentasi/uraian');
            return response()->json([
                'url' => Storage::disk('public')->url($path)
            ]);
        }

        return response()->json(['error' => ['message' => 'Upload gagal.']], 400);
    }

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
            $data = $this->withPembiayaan($request, $data);
            $laporan = Laporan::create($data);

            $this->syncUraians($request, $laporan);
            $this->storeDokumentasi($request, $laporan);

            return $laporan;
        });

        return redirect()
            ->route('laporan.show', $laporan)
            ->with('status', 'Laporan berhasil dibuat.')
            ->with('clear_draft_key', 'laporan_draft:create');
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
            $data = $this->withPembiayaan($request, $data);
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
            ->with('status', 'Laporan berhasil diperbarui.')
            ->with('clear_draft_key', 'laporan_draft:edit:'.$laporan->id);
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
     * Duplikat laporan beserta relasinya.
     */
    public function duplicate(Laporan $laporan): RedirectResponse
    {
        $laporan->load(['pembiayaan', 'uraians', 'dokumentasis']);

        $new = DB::transaction(function () use ($laporan) {
            $new = $laporan->replicate();
            $new->judul_laporan = $new->judul_laporan . ' (Salinan)';
            $new->save();

            if ($laporan->pembiayaan) {
                $new->pembiayaan()->create($laporan->pembiayaan->toArray());
            }

            foreach ($laporan->uraians as $u) {
                $new->uraians()->create($u->toArray());
            }

            foreach ($laporan->dokumentasis as $dok) {
                $oldPath = $dok->image_path;
                $newPath = 'dokumentasi/' . $new->id . '/' . \Illuminate\Support\Str::random(40) . '.' . pathinfo($oldPath, PATHINFO_EXTENSION);
                
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->copy($oldPath, $newPath);
                }

                $new->dokumentasis()->create([
                    'image_path' => $newPath,
                    'keterangan' => $dok->keterangan,
                    'urutan'     => $dok->urutan,
                ]);
            }
            
            return $new;
        });

        return redirect()
            ->route('laporan.edit', $new)
            ->with('status', 'Laporan berhasil diduplikat. Anda sedang mengedit salinannya.');
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

        return $pdf->stream($namaFile);
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
     * Unggah 1 foto dokumentasi sebagai draft (langsung tersimpan ke storage
     * di dokumentasi/tmp/{userId}) sebelum laporan disubmit. Dipanggil via AJAX.
     */
    public function uploadDraft(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $dir = 'dokumentasi/tmp/'.$request->user()->id;
        $path = $this->storeImage($request->file('file'), $dir);

        if (! $path) {
            return response()->json([
                'message' => 'Format foto tidak didukung atau gagal dikonversi. Gunakan JPG/PNG/WEBP/HEIC.',
            ], 422);
        }

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'name' => $request->file('file')->getClientOriginalName(),
        ]);
    }

    /**
     * Hapus 1 foto draft (hanya berkas milik pengguna di folder tmp-nya).
     */
    public function deleteDraft(Request $request): \Illuminate\Http\JsonResponse
    {
        $path = (string) $request->input('path');
        $prefix = 'dokumentasi/tmp/'.$request->user()->id.'/';

        if (str_starts_with($path, $prefix) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Simpan berkas gambar ke disk public. Foto HEIC/HEIF dikonversi ke JPEG
     * agar bisa ditampilkan di web dan dirender di PDF/Word.
     *
     * @return string|null Path relatif hasil simpan, atau null bila format tak didukung/gagal.
     */
    protected function storeImage(\Illuminate\Http\UploadedFile $file, string $dir): ?string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'];
        if (! in_array($ext, $allowed, true)) {
            return null;
        }

        $path = $file->store($dir, 'public');

        if (in_array($ext, ['heic', 'heif'], true)) {
            $converted = $this->convertToJpeg($path);
            if (! $converted) {
                Storage::disk('public')->delete($path);

                return null;
            }
            $path = $converted;
        }

        return $path;
    }

    /**
     * Konversi berkas gambar (mis. HEIC) menjadi JPEG. Memakai Imagick bila
     * tersedia, jika tidak memakai `sips` (bawaan macOS).
     */
    protected function convertToJpeg(string $path): ?string
    {
        $disk = Storage::disk('public');
        $abs = $disk->path($path);
        $newPath = preg_replace('/\.(heic|heif)$/i', '.jpg', $path);
        if ($newPath === $path) {
            $newPath = $path.'.jpg';
        }
        $newAbs = $disk->path($newPath);

        $ok = false;

        if (extension_loaded('imagick')) {
            try {
                $im = new \Imagick();
                $im->readImage($abs);
                $im->setImageFormat('jpeg');
                $im->setImageCompressionQuality(88);
                $im->writeImage($newAbs);
                $im->clear();
                $ok = is_file($newAbs);
            } catch (\Throwable $e) {
                $ok = false;
            }
        }

        if (! $ok && function_exists('exec') && is_file('/usr/bin/sips')) {
            @exec('/usr/bin/sips -s format jpeg '.escapeshellarg($abs).' --out '.escapeshellarg($newAbs).' 2>&1', $out, $code);
            $ok = ($code === 0 && is_file($newAbs));
        }

        if ($ok) {
            if ($newPath !== $path) {
                $disk->delete($path);
            }

            return $newPath;
        }

        return null;
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
            // Pembiayaan bersifat dinamis: pilih dari master atau ketik baru.
            // Kombinasi 5 field ini di-firstOrCreate menjadi master_pembiayaan.
            'program' => ['nullable', 'string', 'max:255'],
            'kegiatan' => ['nullable', 'required_with:program', 'string', 'max:255'],
            'ro' => ['nullable', 'required_with:program', 'string', 'max:255'],
            'komponen' => ['nullable', 'required_with:program', 'string', 'max:255'],
            'akun' => ['nullable', 'required_with:program', 'string', 'max:255'],
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
            'dokumentasi.*.file' => ['nullable', 'file', 'max:5120'],
            'dokumentasi.*.path' => ['nullable', 'string', 'max:255'],
            'dokumentasi.*.keterangan' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * Ganti field pembiayaan (program..akun) pada $data dengan pembiayaan_id.
     * Kombinasi pembiayaan di-firstOrCreate ke master_pembiayaans sehingga
     * pembiayaan baru otomatis tersimpan dan yang sudah ada dipakai ulang.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withPembiayaan(Request $request, array $data): array
    {
        foreach (['program', 'kegiatan', 'ro', 'komponen', 'akun'] as $f) {
            unset($data[$f]);
        }

        $program = trim((string) $request->input('program'));
        if ($program === '') {
            $data['pembiayaan_id'] = null;

            return $data;
        }

        $pembiayaan = MasterPembiayaan::firstOrCreate([
            'program' => $program,
            'kegiatan' => trim((string) $request->input('kegiatan')),
            'ro' => trim((string) $request->input('ro')),
            'komponen' => trim((string) $request->input('komponen')),
            'akun' => trim((string) $request->input('akun')),
        ]);

        $data['pembiayaan_id'] = $pembiayaan->id;

        return $data;
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
        $disk = Storage::disk('public');
        $tmpPrefix = 'dokumentasi/tmp/'.$request->user()->id.'/';

        // Gunakan key asli dari request agar akses file (dokumentasi.$i.file) tepat,
        // meski indeks tidak berurutan karena ada baris yang dihapus di form.
        foreach ($request->input('dokumentasi', []) as $i => $dok) {
            $path = null;

            // 1) Unggahan langsung (fallback tanpa JS / AJAX gagal).
            $file = $request->file("dokumentasi.$i.file");
            if ($file) {
                $path = $this->storeImage($file, 'dokumentasi/'.$laporan->id);
            }
            // 2) Foto draft yang sudah terunggah lebih dulu: pindahkan dari tmp.
            elseif (! empty($dok['path'])) {
                $src = $dok['path'];
                // Hanya izinkan memindahkan berkas draft milik pengguna ini.
                if (str_starts_with($src, $tmpPrefix) && $disk->exists($src)) {
                    $path = 'dokumentasi/'.$laporan->id.'/'.basename($src);
                    $disk->makeDirectory('dokumentasi/'.$laporan->id);
                    $disk->move($src, $path);
                }
            }

            if (! $path) {
                continue;
            }

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
