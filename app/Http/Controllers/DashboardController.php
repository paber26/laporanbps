<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\MasterPembiayaan;
use App\Models\Pegawai;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $now = now();

        $stats = [
            'total_laporan' => Laporan::count(),
            'laporan_bulan_ini' => Laporan::whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month)
                ->count(),
            'total_pegawai' => Pegawai::count(),
            'total_pembiayaan' => MasterPembiayaan::count(),
        ];

        // Jumlah laporan per petugas.
        $laporanPerPetugas = Pegawai::withCount('laporans')
            ->orderByDesc('laporans_count')
            ->take(10)
            ->get();

        $laporanTerbaru = Laporan::with('pegawai')
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact('stats', 'laporanPerPetugas', 'laporanTerbaru'));
    }
}
