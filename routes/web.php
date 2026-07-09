<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MasterPembiayaanController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Cetak dokumen (didefinisikan sebelum resource agar tidak bentrok dengan {laporan}).
    Route::get('laporan/{laporan}/pdf', [LaporanController::class, 'exportPdf'])->name('laporan.pdf');
    Route::get('laporan/{laporan}/word', [LaporanController::class, 'exportWord'])->name('laporan.word');

    Route::resource('laporan', LaporanController::class);
    Route::resource('master-pembiayaan', MasterPembiayaanController::class)
        ->parameters(['master-pembiayaan' => 'masterPembiayaan'])
        ->except('show');
    Route::resource('pegawai', PegawaiController::class)->except('show');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
