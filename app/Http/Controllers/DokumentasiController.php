<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\LaporanDokumentasi;
use App\Models\Pegawai;
use App\Support\ImageCompressor;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DokumentasiController extends Controller
{
    /**
     * Sajikan berkas thumbnail foto terkompresi.
     */
    public function thumbnail(LaporanDokumentasi $dokumentasi): Response
    {
        $thumbRelative = $dokumentasi->thumbnail_path;
        $disk = Storage::disk('public');

        if (! $disk->exists($thumbRelative)) {
            if ($dokumentasi->image_path && $disk->exists($dokumentasi->image_path)) {
                ImageCompressor::createThumbnail($dokumentasi->image_path, $thumbRelative);
            }
        }

        if ($disk->exists($thumbRelative)) {
            return response()->file($disk->path($thumbRelative), [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        // Fallback bila thumbnail belum bisa dibuat: alihkan ke gambar asli
        return redirect($dokumentasi->url);
    }
    /**
     * Tampilkan galeri foto dokumentasi kegiatan.
     */
    public function index(Request $request): View
    {
        $query = LaporanDokumentasi::with([
            'laporan.pegawai',
            'laporan.pembiayaan',
            'laporan.uraians:id,laporan_id,tanggal_kegiatan,urutan',
        ])->latest('id');

        // Filter berdasarkan pencarian kata kunci
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('keterangan', 'like', "%{$search}%")
                    ->orWhereHas('laporan', function ($lq) use ($search) {
                        $lq->where('perihal_laporan', 'like', "%{$search}%")
                            ->orWhere('tempat_laporan', 'like', "%{$search}%")
                            ->orWhere('lokasi_tujuan', 'like', "%{$search}%")
                            ->orWhere('judul_laporan', 'like', "%{$search}%")
                            ->orWhereHas('pegawai', function ($pq) use ($search) {
                                $pq->where('nama', 'like', "%{$search}%")
                                    ->orWhere('nip', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // Filter berdasarkan pegawai tertentu
        if ($pegawaiId = $request->input('pegawai_id')) {
            $query->whereHas('laporan', function ($lq) use ($pegawaiId) {
                $lq->where('pegawai_id', $pegawaiId);
            });
        }

        // Filter berdasarkan laporan tertentu
        if ($laporanId = $request->input('laporan_id')) {
            $query->where('laporan_id', $laporanId);
        }

        // Filter tahun tanggal laporan
        if ($tahun = $request->input('tahun')) {
            $query->whereHas('laporan', function ($lq) use ($tahun) {
                $lq->whereYear('tanggal_laporan', $tahun);
            });
        }

        // Filter bulan tanggal laporan
        if ($bulan = $request->input('bulan')) {
            $query->whereHas('laporan', function ($lq) use ($bulan) {
                $lq->whereMonth('tanggal_laporan', $bulan);
            });
        }

        $dokumentasis = $query->paginate(24)->withQueryString();

        // Data statistik ringkas
        $totalFoto = LaporanDokumentasi::count();
        $totalLaporanDenganFoto = Laporan::has('dokumentasis')->count();

        // Dropdown options
        $pegawais = Pegawai::orderBy('nama')->get(['id', 'nama']);
        $laporans = Laporan::has('dokumentasis')
            ->with(['uraians:id,laporan_id,tanggal_kegiatan,urutan'])
            ->orderByDesc('id')
            ->get(['id', 'perihal_laporan', 'lokasi_tujuan', 'tempat_laporan', 'tanggal_laporan']);

        // Daftar tahun unik untuk filter
        $tahuns = Laporan::has('dokumentasis')
            ->pluck('tanggal_laporan')
            ->filter()
            ->map(function ($d) {
                return $d instanceof CarbonInterface ? $d->year : Carbon::parse($d)->year;
            })
            ->unique()
            ->sortDesc()
            ->values();

        return view('dokumentasi.index', compact(
            'dokumentasis',
            'totalFoto',
            'totalLaporanDenganFoto',
            'pegawais',
            'laporans',
            'tahuns'
        ));
    }
}
